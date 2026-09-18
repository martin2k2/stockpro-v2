<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "../config/conexion.php";


if (!isset($_SESSION["usuario_id"])) {

    echo json_encode([
        "ok" => false,
        "error" => "Sesión no válida."
    ]);

    exit;
}


try {

    $pdo = Conexion::conectar();

    $usuarioId = (int)$_SESSION["usuario_id"];


    $datos = json_decode(
        file_get_contents("php://input"),
        true
    );


    if (
        !$datos ||
        empty($datos["productos"]) ||
        !is_array($datos["productos"])
    ) {

        throw new Exception(
            "No hay productos en la venta."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CAJA ABIERTA
    |--------------------------------------------------------------------------
    */

    $stmtCaja = $pdo->prepare("
        SELECT id
        FROM cajas
        WHERE usuario_id = ?
        AND estado = 'ABIERTA'
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmtCaja->execute([
        $usuarioId
    ]);

    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);


    if (!$caja) {

        throw new Exception(
            "No hay una caja abierta."
        );

    }


    $cajaId = (int)$caja["id"];


    /*
    |--------------------------------------------------------------------------
    | INICIAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | CALCULAR TOTAL Y VALIDAR STOCK
    |--------------------------------------------------------------------------
    */

    $total = 0;

    $productosVenta = [];


    foreach ($datos["productos"] as $item) {


        $productoId = (int)($item["id"] ?? 0);


        $varianteId = !empty($item["variante_id"])
            ? (int)$item["variante_id"]
            : null;


        $cantidad = (int)($item["cantidad"] ?? 0);


        if (
            $productoId <= 0 ||
            $cantidad <= 0
        ) {

            throw new Exception(
                "Producto o cantidad inválida."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCTO CON VARIANTE
        |--------------------------------------------------------------------------
        */

        if ($varianteId !== null) {


            $stmtProducto = $pdo->prepare("
                SELECT
                    pv.id AS variante_id,
                    pv.producto_id,
                    pv.stock,
                    pv.talle,
                    pv.color,
                    p.nombre,
                    p.precio_venta
                FROM producto_variantes pv

                INNER JOIN productos p
                    ON p.id = pv.producto_id

                WHERE
                    pv.id = ?
                    AND pv.producto_id = ?
                    AND pv.activo = 1

                FOR UPDATE
            ");


            $stmtProducto->execute([
                $varianteId,
                $productoId
            ]);


            $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);


            if (!$producto) {

                throw new Exception(
                    "La variante seleccionada no existe."
                );

            }


            if (
                (int)$producto["stock"] < $cantidad
            ) {

                throw new Exception(
                    "Stock insuficiente para " .
                    $producto["nombre"]
                );

            }


            $precio = (float)$producto["precio_venta"];

            $subtotal = $precio * $cantidad;

            $total += $subtotal;


            $productosVenta[] = [

                "producto_id" => $productoId,

                "variante_id" => $varianteId,

                "cantidad" => $cantidad,

                "precio" => $precio,

                "subtotal" => $subtotal,

                "nombre" => $producto["nombre"],

                "talle" => $producto["talle"],

                "color" => $producto["color"]

            ];


        } else {


            /*
            |--------------------------------------------------------------------------
            | PRODUCTO NORMAL
            |--------------------------------------------------------------------------
            */

            $stmtProducto = $pdo->prepare("
                SELECT
                    id,
                    nombre,
                    stock,
                    precio_venta
                FROM productos
                WHERE id = ?

                FOR UPDATE
            ");


            $stmtProducto->execute([
                $productoId
            ]);


            $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);


            if (!$producto) {

                throw new Exception(
                    "Producto no encontrado."
                );

            }


            if (
                (int)$producto["stock"] < $cantidad
            ) {

                throw new Exception(
                    "Stock insuficiente para " .
                    $producto["nombre"]
                );

            }


            $precio = (float)$producto["precio_venta"];

            $subtotal = $precio * $cantidad;

            $total += $subtotal;


            $productosVenta[] = [

                "producto_id" => $productoId,

                "variante_id" => null,

                "cantidad" => $cantidad,

                "precio" => $precio,

                "subtotal" => $subtotal,

                "nombre" => $producto["nombre"],

                "talle" => null,

                "color" => null

            ];

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CLIENTE
    |--------------------------------------------------------------------------
    */

    $clienteId = !empty($datos["cliente_id"])
        ? (int)$datos["cliente_id"]
        : 0;


    if ($clienteId <= 0) {


        $stmtConsumidor = $pdo->prepare("
            SELECT id
            FROM clientes
            WHERE
                UPPER(TRIM(nombre)) = 'CONSUMIDOR FINAL'
                OR UPPER(TRIM(apellido)) = 'CONSUMIDOR FINAL'
                OR CONCAT(
                    UPPER(TRIM(apellido)),
                    ' ',
                    UPPER(TRIM(nombre))
                ) = 'CONSUMIDOR FINAL'
            LIMIT 1
        ");


        $stmtConsumidor->execute();

        $consumidor = $stmtConsumidor->fetch(PDO::FETCH_ASSOC);


        if (!$consumidor) {

            throw new Exception(
                "No existe el cliente Consumidor Final."
            );

        }


        $clienteId = (int)$consumidor["id"];

    }


    /*
    |--------------------------------------------------------------------------
    | FORMA DE PAGO
    |--------------------------------------------------------------------------
    */

    $formaPago = strtoupper(
        trim(
            $datos["forma_pago"]
            ?? "EFECTIVO"
        )
    );


    /*
    |--------------------------------------------------------------------------
    | REGISTRAR VENTA
    |--------------------------------------------------------------------------
    */

    $stmtVenta = $pdo->prepare("
        INSERT INTO ventas
        (
            usuario_id,
            cliente_id,
            caja_id,
            total,
            forma_pago,
            estado
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            'PAGADA'
        )
    ");


    $stmtVenta->execute([

        $usuarioId,

        $clienteId,

        $cajaId,

        $total,

        $formaPago

    ]);


    $ventaId = (int)$pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | CONSULTAS PREPARADAS
    |--------------------------------------------------------------------------
    */

    $stmtDetalle = $pdo->prepare("
        INSERT INTO detalle_ventas
        (
            venta_id,
            producto_id,
            variante_id,
            cantidad,
            precio,
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


    $stmtDescontarVariante = $pdo->prepare("
        UPDATE producto_variantes
        SET stock = stock - ?
        WHERE
            id = ?
            AND producto_id = ?
            AND stock >= ?
    ");


    $stmtDescontarProducto = $pdo->prepare("
        UPDATE productos
        SET stock = stock - ?
        WHERE
            id = ?
            AND stock >= ?
    ");


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
            ?,
            NOW()
        )
    ");


    /*
    |--------------------------------------------------------------------------
    | DETALLE + STOCK + MOVIMIENTO
    |--------------------------------------------------------------------------
    */

    foreach ($productosVenta as $item) {


        $stmtDetalle->execute([

            $ventaId,

            $item["producto_id"],

            $item["variante_id"],

            $item["cantidad"],

            $item["precio"],

            $item["subtotal"]

        ]);


        /*
        |--------------------------------------------------------------------------
        | DESCONTAR STOCK
        |--------------------------------------------------------------------------
        */

        if ($item["variante_id"] !== null) {


            $stmtDescontarVariante->execute([

                $item["cantidad"],

                $item["variante_id"],

                $item["producto_id"],

                $item["cantidad"]

            ]);


            if (
                $stmtDescontarVariante->rowCount() !== 1
            ) {

                throw new Exception(
                    "No se pudo descontar el stock de la variante."
                );

            }


        } else {


            $stmtDescontarProducto->execute([

                $item["cantidad"],

                $item["producto_id"],

                $item["cantidad"]

            ]);


            if (
                $stmtDescontarProducto->rowCount() !== 1
            ) {

                throw new Exception(
                    "No se pudo descontar el stock del producto."
                );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | REGISTRAR MOVIMIENTO
        |--------------------------------------------------------------------------
        */

        $detalleVariante = "";


        if ($item["variante_id"] !== null) {

            $partes = [];


            if (!empty($item["talle"])) {

                $partes[] =
                    "Talle: " .
                    $item["talle"];

            }


            if (!empty($item["color"])) {

                $partes[] =
                    "Color: " .
                    $item["color"];

            }


            if (!empty($partes)) {

                $detalleVariante =
                    " (" .
                    implode(
                        " - ",
                        $partes
                    ) .
                    ")";

            }

        }


        $observacion =
            "Venta presencial #" .
            $ventaId .
            " - " .
            $item["nombre"] .
            $detalleVariante .
            " - Pago: " .
            $formaPago;


        $stmtMovimiento->execute([

            $item["producto_id"],

            $item["cantidad"],

            $observacion,

            $usuarioId

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR CAJA
    |--------------------------------------------------------------------------
    */

    $ingresoEfectivo = 0;


    //if ($formaPago === "EFECTIVO") {

    //    $ingresoEfectivo = $total;

    //}


    $stmtCajaUpdate = $pdo->prepare("
    UPDATE cajas
    SET
        ventas = COALESCE(ventas, 0) + ?
    WHERE id = ?
");

$stmtCajaUpdate->execute([
    $total,
    $cajaId
]);


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    echo json_encode([

        "ok" => true,

        "venta" => $ventaId,

        "caja_id" => $cajaId,

        "total" => $total,

        "forma_pago" => $formaPago

    ]);


} catch (Throwable $e) {


    if (
        isset($pdo) &&
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();

    }


    echo json_encode([

        "ok" => false,

        "error" => $e->getMessage()

    ]);

}

?>