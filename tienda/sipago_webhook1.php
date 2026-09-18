<?php
// /stockpro-v2/tienda/sipago_webhook.php

require_once "../config/conexion.php";

$pdo = Conexion::conectar();

header("Content-Type: application/json; charset=utf-8");


/*
|--------------------------------------------------------------------------
| LEER WEBHOOK
|--------------------------------------------------------------------------
*/

$raw = file_get_contents("php://input");

if (!$raw) {

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "error" => "Webhook vacío."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$data = json_decode($raw, true);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "error" => "JSON inválido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| EXTRAER INFORMACIÓN DE SIPAGO
|--------------------------------------------------------------------------
*/

$order = $data["data"]["order"] ?? [];

$payment = $data["data"]["payment"] ?? [];


$order_uuid = $order["uuid"] ?? null;

$order_status = strtoupper(
    trim((string)($order["status"] ?? ""))
);

$payment_id = $payment["id"] ?? null;

$payment_status = strtoupper(
    trim((string)($payment["status"] ?? ""))
);

$authorization_code =
    $payment["authorizationCode"] ?? null;

$ref_number =
    $payment["refNumber"] ?? null;


/*
|--------------------------------------------------------------------------
| VALIDAR ORDER UUID
|--------------------------------------------------------------------------
*/

if (!$order_uuid) {

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "error" => "No se recibió order.uuid."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | BUSCAR PEDIDO
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            estado,
            total,
            sipago_order_uuid
        FROM pedidos
        WHERE sipago_order_uuid = ?
        LIMIT 1
    ");

    $stmt->execute([
        $order_uuid
    ]);

    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | SI NO EXISTE
    |--------------------------------------------------------------------------
    */

    if (!$pedido) {

        http_response_code(404);

        echo json_encode([
            "ok" => false,
            "error" => "Pedido no encontrado.",
            "order_uuid" => $order_uuid
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    $pedido_id = (int)$pedido["id"];


    /*
    |--------------------------------------------------------------------------
    | TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | BLOQUEAR PEDIDO
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            estado
        FROM pedidos
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([
        $pedido_id
    ]);

    $pedidoBloqueado = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$pedidoBloqueado) {

        throw new Exception(
            "El pedido no existe."
        );
    }


    $estado_actual = strtoupper(
        trim((string)$pedidoBloqueado["estado"])
    );


    /*
    |--------------------------------------------------------------------------
    | PAGO APROBADO
    |--------------------------------------------------------------------------
    */

    if (
        $order_status === "SUCCESS" &&
        $payment_status === "APPROVED"
    ) {


        /*
        |--------------------------------------------------------------------------
        | EVITAR PROCESAR DOS VECES
        |--------------------------------------------------------------------------
        */

        if ($estado_actual !== "Pagado") {

            /*
            |--------------------------------------------------------------------------
            | BUSCAR DETALLE
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    pd.producto_id,
                    pd.cantidad,
                    pd.precio,
                    pd.subtotal,
                    p.nombre,
                    p.stock
                FROM pedido_detalle pd
                INNER JOIN productos p
                    ON p.id = pd.producto_id
                WHERE pd.pedido_id = ?
                FOR UPDATE
            ");

            $stmt->execute([
                $pedido_id
            ]);

            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);


            if (!$detalles) {

                throw new Exception(
                    "El pedido no tiene productos."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | VERIFICAR STOCK
            |--------------------------------------------------------------------------
            */

            foreach ($detalles as $detalle) {

                $cantidad = (float)$detalle["cantidad"];

                $stock = (float)$detalle["stock"];


                if ($cantidad <= 0) {

                    throw new Exception(
                        "Cantidad inválida para "
                        . $detalle["nombre"]
                    );
                }


                if ($stock < $cantidad) {

                    throw new Exception(
                        "Stock insuficiente para: "
                        . $detalle["nombre"]
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | CREAR VENTA
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO ventas
                (
                    fecha,
                    usuario_id,
                    cliente_id,
                    total,
                    forma_pago,
                    estado
                )
                SELECT
                    NOW(),
                    NULL,
                    cliente_id,
                    total,
                    'SIPAGO',
                    'PAGADA'
                FROM pedidos
                WHERE id = ?
            ");

            $stmt->execute([
                $pedido_id
            ]);

            $venta_id = (int)$pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | PREPARAR DETALLE VENTA
            |--------------------------------------------------------------------------
            */

            $stmtDetalle = $pdo->prepare("
                INSERT INTO detalle_ventas
                (
                    venta_id,
                    producto_id,
                    cantidad,
                    precio,
                    descuento,
                    subtotal
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


            /*
            |--------------------------------------------------------------------------
            | DESCONTAR STOCK
            |--------------------------------------------------------------------------
            */

            $stmtStock = $pdo->prepare("
                UPDATE productos
                SET stock = stock - ?
                WHERE id = ?
            ");


            /*
            |--------------------------------------------------------------------------
            | MOVIMIENTO
            |--------------------------------------------------------------------------
            */

            $stmtMovimiento = $pdo->prepare("
                INSERT INTO movimientos
                (
                    producto_id,
                    tipo,
                    cantidad,
                    observacion,
                    usuario_id,
                    fecha
                )
                VALUES
                (
                    ?,
                    'SALIDA',
                    ?,
                    ?,
                    NULL,
                    NOW()
                )
            ");


            /*
            |--------------------------------------------------------------------------
            | PROCESAR PRODUCTOS
            |--------------------------------------------------------------------------
            */

            foreach ($detalles as $detalle) {

                $producto_id =
                    (int)$detalle["producto_id"];

                $cantidad =
                    (float)$detalle["cantidad"];

                $precio =
                    (float)$detalle["precio"];

                $subtotal =
                    (float)$detalle["subtotal"];


                $stmtDetalle->execute([

                    $venta_id,

                    $producto_id,

                    $cantidad,

                    $precio,

                    0,

                    $subtotal

                ]);


                $stmtStock->execute([

                    $cantidad,

                    $producto_id

                ]);


                $stmtMovimiento->execute([

                    $producto_id,

                    $cantidad,

                    "Venta web SiPago - Pedido #"
                    . $pedido_id

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | ACTUALIZAR PEDIDO
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE pedidos
                SET
                    estado = 'Pagado',
                    metodo_pago = 'SIPAGO'
                WHERE id = ?
            ");

            $stmt->execute([
                $pedido_id
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | GUARDAR DATOS DEL PAGO
        |--------------------------------------------------------------------------
        */

        try {

            $stmt = $pdo->prepare("
                UPDATE pedidos
                SET
                    sipago_payment_id = ?,
                    sipago_authorization_code = ?,
                    sipago_ref_number = ?
                WHERE id = ?
            ");

            $stmt->execute([

                $payment_id,

                $authorization_code,

                $ref_number,

                $pedido_id

            ]);

        } catch (Throwable $e) {

            /*
            | Las columnas pueden agregarse después.
            */

        }
    }


    /*
    |--------------------------------------------------------------------------
    | PAGO RECHAZADO
    |--------------------------------------------------------------------------
    */

    elseif (
        $payment_status === "DENIED" ||
        $order_status === "FAILED" ||
        $order_status === "FAILED_CHECKOUT"
    ) {

        $stmt = $pdo->prepare("
            UPDATE pedidos
            SET estado = 'PAGO RECHAZADO'
            WHERE id = ?
            AND estado <> 'Pagado'
        ");

        $stmt->execute([
            $pedido_id
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | PEDIDO PENDIENTE
    |--------------------------------------------------------------------------
    */

    elseif (
        $order_status === "PENDING"
    ) {

        $stmt = $pdo->prepare("
            UPDATE pedidos
            SET estado = 'Pendiente'
            WHERE id = ?
            AND estado <> 'Pagado'
        ");

        $stmt->execute([
            $pedido_id
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | EXPIRADO
    |--------------------------------------------------------------------------
    */

    elseif (
        $order_status === "EXPIRED"
    ) {

        $stmt = $pdo->prepare("
            UPDATE pedidos
            SET estado = 'Cancelado'
            WHERE id = ?
            AND estado <> 'Pagado'
        ");

        $stmt->execute([
            $pedido_id
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA
    |--------------------------------------------------------------------------
    */

    http_response_code(200);

    echo json_encode([

        "ok" => true,

        "pedido_id" => $pedido_id,

        "order_uuid" => $order_uuid,

        "order_status" => $order_status,

        "payment_status" => $payment_status

    ], JSON_UNESCAPED_UNICODE);

    exit;


} catch (Throwable $e) {


    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    http_response_code(500);

    echo json_encode([

        "ok" => false,

        "error" => $e->getMessage()

    ], JSON_UNESCAPED_UNICODE);

    exit;
}
?>