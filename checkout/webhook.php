<?php

session_start();

require_once "../config/conexion.php";

$db = Conexion::conectar();

/*
|--------------------------------------------------------------------------
| SiPago - Webhook definitivo
|--------------------------------------------------------------------------
| Relación:
|
| SiPago ID
|     ↓
| pedidos.sipago_id
|     ↓
| pedido.id
|     ↓
| pedido_detalle
|     ↓
| productos.stock
|
| El stock solamente se descuenta una vez.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN
|--------------------------------------------------------------------------
*/

$logFile = __DIR__ . "/webhook.log";


/*
|--------------------------------------------------------------------------
| RECIBIR WEBHOOK
|--------------------------------------------------------------------------
*/

$raw = file_get_contents("php://input");


/*
|--------------------------------------------------------------------------
| LOG
|--------------------------------------------------------------------------
*/

file_put_contents(
    $logFile,
    date("Y-m-d H:i:s") .
    " | WEBHOOK | " .
    $raw .
    PHP_EOL,
    FILE_APPEND
);


/*
|--------------------------------------------------------------------------
| VALIDAR JSON
|--------------------------------------------------------------------------
*/

$data = json_decode(
    $raw,
    true
);


if (!is_array($data)) {

    http_response_code(400);

    echo "JSON inválido";

    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER ID SIPAGO
|--------------------------------------------------------------------------
*/

$sipago_id = "";


/*
| Primero intentamos data.id
*/

if (
    isset($data["data"]["id"]) &&
    !empty($data["data"]["id"])
) {

    $sipago_id = trim(
        (string)$data["data"]["id"]
    );

}


/*
| Si no existe, intentamos attributes.uuid
*/

if (
    empty($sipago_id) &&
    isset($data["data"]["attributes"]["uuid"])
) {

    $sipago_id = trim(
        (string)$data["data"]["attributes"]["uuid"]
    );

}


/*
|--------------------------------------------------------------------------
| OBTENER ESTADO
|--------------------------------------------------------------------------
*/

$estadoSipago = "";


/*
| status
*/

if (
    isset($data["data"]["attributes"]["status"])
) {

    $estadoSipago = strtoupper(
        trim(
            (string)$data["data"]["attributes"]["status"]
        )
    );

}


/*
| state
*/

if (
    empty($estadoSipago) &&
    isset($data["data"]["attributes"]["state"])
) {

    $estadoSipago = strtoupper(
        trim(
            (string)$data["data"]["attributes"]["state"]
        )
    );

}


/*
| status directamente en data
*/

if (
    empty($estadoSipago) &&
    isset($data["data"]["status"])
) {

    $estadoSipago = strtoupper(
        trim(
            (string)$data["data"]["status"]
        )
    );

}


/*
|--------------------------------------------------------------------------
| SI NO TENEMOS ID SIPAGO
|--------------------------------------------------------------------------
*/

if (empty($sipago_id)) {

    file_put_contents(
        $logFile,
        date("Y-m-d H:i:s") .
        " | SIN SIPAGO_ID" .
        PHP_EOL,
        FILE_APPEND
    );

    /*
    | Respondemos 200 para que SiPago no
    | reintente indefinidamente.
    */

    http_response_code(200);

    echo "OK";

    exit;
}


/*
|--------------------------------------------------------------------------
| MAPEAR ESTADO
|--------------------------------------------------------------------------
*/

$estadoPedido = null;


switch ($estadoSipago) {

    case "APPROVED":
    case "AUTHORIZED":
    case "PAID":
    case "SUCCESS":
    case "COMPLETED":
    case "SUCCEEDED":

        $estadoPedido = "Pagado";

        break;


    case "PENDING":
    case "CREATED":
    case "IN_PROCESS":
    case "PROCESSING":
    case "WAITING":

        $estadoPedido = "Pendiente";

        break;


    case "REJECTED":
    case "DECLINED":
    case "FAILED":
    case "FAILURE":
    case "DENIED":

        $estadoPedido = "Rechazado";

        break;


    case "CANCELLED":
    case "CANCELED":

        $estadoPedido = "Cancelado";

        break;

}


/*
|--------------------------------------------------------------------------
| ESTADO NO RECONOCIDO
|--------------------------------------------------------------------------
*/

if ($estadoPedido === null) {

    file_put_contents(
        $logFile,
        date("Y-m-d H:i:s") .
        " | ESTADO NO RECONOCIDO | " .
        $estadoSipago .
        PHP_EOL,
        FILE_APPEND
    );

    http_response_code(200);

    echo "OK";

    exit;
}


/*
|--------------------------------------------------------------------------
| PROCESAR PEDIDO
|--------------------------------------------------------------------------
*/

try {


    /*
    |--------------------------------------------------------------------------
    | INICIAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | BUSCAR PEDIDO POR SIPAGO_ID
    |--------------------------------------------------------------------------
    */

    $stmtPedido = $db->prepare("
        SELECT
            id,
            cliente_id,
            total,
            estado,
            metodo_pago
        FROM pedidos
        WHERE sipago_id = ?
        LIMIT 1
        FOR UPDATE
    ");


    $stmtPedido->execute([
        $sipago_id
    ]);


    $pedido = $stmtPedido->fetch(
        PDO::FETCH_ASSOC
    );


    /*
    |--------------------------------------------------------------------------
    | PEDIDO NO ENCONTRADO
    |--------------------------------------------------------------------------
    */

    if (!$pedido) {

        $db->rollBack();


        file_put_contents(
            $logFile,
            date("Y-m-d H:i:s") .
            " | PEDIDO NO ENCONTRADO | SIPAGO_ID=" .
            $sipago_id .
            PHP_EOL,
            FILE_APPEND
        );


        /*
        | Respondemos 200 porque el webhook
        | fue recibido correctamente.
        */

        http_response_code(200);

        echo "OK";

        exit;
    }


    $pedido_id = (int)$pedido["id"];


    /*
    |--------------------------------------------------------------------------
    | SI YA ESTÁ PAGADO
    |--------------------------------------------------------------------------
    |
    | Esto evita descontar nuevamente el stock
    | si SiPago envía el mismo webhook varias veces.
    |--------------------------------------------------------------------------
    */

    if (
        strtoupper(
            trim(
                (string)$pedido["estado"]
            )
        ) === "PAGADO"
    ) {


        /*
        | No hacemos absolutamente nada más.
        */

        $db->commit();


        file_put_contents(
            $logFile,
            date("Y-m-d H:i:s") .
            " | PEDIDO YA PAGADO | PEDIDO=" .
            $pedido_id .
            " | SIPAGO_ID=" .
            $sipago_id .
            PHP_EOL,
            FILE_APPEND
        );


        http_response_code(200);

        echo "OK";

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ESTADO
    |--------------------------------------------------------------------------
    */

    $stmtEstado = $db->prepare("
        UPDATE pedidos
        SET estado = ?
        WHERE id = ?
        LIMIT 1
    ");


    $stmtEstado->execute([
        $estadoPedido,
        $pedido_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | SI NO ES PAGADO
    |--------------------------------------------------------------------------
    |
    | No descontamos stock.
    |--------------------------------------------------------------------------
    */

    if ($estadoPedido !== "Pagado") {

        $db->commit();


        file_put_contents(
            $logFile,
            date("Y-m-d H:i:s") .
            " | PEDIDO=" .
            $pedido_id .
            " | SIPAGO_ID=" .
            $sipago_id .
            " | ESTADO=" .
            $estadoPedido .
            PHP_EOL,
            FILE_APPEND
        );


        http_response_code(200);

        echo "OK";

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER DETALLE DEL PEDIDO
    |--------------------------------------------------------------------------
    */

    $stmtDetalle = $db->prepare("
        SELECT
            pd.producto_id,
            pd.cantidad,
            pr.nombre,
            pr.stock
        FROM pedido_detalle pd
        INNER JOIN productos pr
            ON pr.id = pd.producto_id
        WHERE pd.pedido_id = ?
        FOR UPDATE
    ");


    $stmtDetalle->execute([
        $pedido_id
    ]);


    $detalles = $stmtDetalle->fetchAll(
        PDO::FETCH_ASSOC
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDAR DETALLE
    |--------------------------------------------------------------------------
    */

    if (empty($detalles)) {

        throw new Exception(
            "El pedido #" .
            $pedido_id .
            " no tiene productos."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR STOCK ANTES DE DESCONTAR
    |--------------------------------------------------------------------------
    |
    | Primero verificamos TODOS los productos.
    | Si uno no tiene stock suficiente,
    | no descontamos ninguno.
    |--------------------------------------------------------------------------
    */

    foreach ($detalles as $detalle) {

        $producto_id =
            (int)$detalle["producto_id"];

        $cantidad =
            (int)$detalle["cantidad"];

        $stock =
            (int)$detalle["stock"];


        if (
            $producto_id <= 0 ||
            $cantidad <= 0
        ) {

            throw new Exception(
                "Detalle de pedido inválido."
            );

        }


        if ($stock < $cantidad) {

            throw new Exception(
                "Stock insuficiente para el producto: " .
                $detalle["nombre"] .
                ". Stock disponible: " .
                $stock .
                ". Cantidad solicitada: " .
                $cantidad
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DESCONTAR STOCK
    |--------------------------------------------------------------------------
    */

    foreach ($detalles as $detalle) {

        $producto_id =
            (int)$detalle["producto_id"];

        $cantidad =
            (int)$detalle["cantidad"];


        $stmtStock = $db->prepare("
            UPDATE productos
            SET stock = stock - ?
            WHERE id = ?
              AND stock >= ?
            LIMIT 1
        ");


        $stmtStock->execute([

            $cantidad,

            $producto_id,

            $cantidad

        ]);


        /*
        | Verificar que realmente se haya descontado.
        */

        if (
            $stmtStock->rowCount() !== 1
        ) {

            throw new Exception(
                "No se pudo actualizar el stock del producto ID " .
                $producto_id
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $db->commit();


    /*
    |--------------------------------------------------------------------------
    | LOG FINAL
    |--------------------------------------------------------------------------
    */

    file_put_contents(
        $logFile,
        date("Y-m-d H:i:s") .
        " | PAGO APROBADO | " .
        "PEDIDO=" .
        $pedido_id .
        " | SIPAGO_ID=" .
        $sipago_id .
        " | STOCK DESCONTADO" .
        PHP_EOL,
        FILE_APPEND
    );


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA SIPAGO
    |--------------------------------------------------------------------------
    */

    http_response_code(200);

    echo "OK";


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if (
        $db->inTransaction()
    ) {

        $db->rollBack();

    }


    /*
    |--------------------------------------------------------------------------
    | LOG ERROR
    |--------------------------------------------------------------------------
    */

    file_put_contents(
        $logFile,
        date("Y-m-d H:i:s") .
        " | ERROR | " .
        $e->getMessage() .
        " | SIPAGO_ID=" .
        $sipago_id .
        PHP_EOL,
        FILE_APPEND
    );


    /*
    |--------------------------------------------------------------------------
    | ERROR
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    echo "ERROR";

}