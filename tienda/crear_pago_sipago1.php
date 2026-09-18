<?php
// /stockpro-v2/tienda/crear_pago_sipago.php

session_start();

require_once "../config/conexion.php";

header("Content-Type: application/json; charset=utf-8");


/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN
|--------------------------------------------------------------------------
*/

$client_id = "3c21db0f-6913-43db-88d6-2ced87b99a91";

$client_secret = "ft6z30q2ftsmu90au0mp";

$auth_url = "https://auth.stg.geopagos.io/oauth/token";

$api_url = "https://api-cabal.preprod.geopagos.com";


/*
|--------------------------------------------------------------------------
| URLS
|--------------------------------------------------------------------------
|
| CAMBIAR cuando el sistema esté publicado.
|
*/

$base_web = "https://TU-DOMINIO.com/stockpro-v2/tienda";

$success_url = $base_web . "/sipago_success.php";

$failed_url = $base_web . "/sipago_failed.php";

$webhook_url = $base_web . "/sipago_webhook.php";


/*
|--------------------------------------------------------------------------
| VALIDAR PEDIDO
|--------------------------------------------------------------------------
*/

$pedido_id = isset($_POST["pedido_id"])
    ? (int)$_POST["pedido_id"]
    : 0;


if ($pedido_id <= 0) {

    echo json_encode([

        "ok" => false,

        "error" => "Pedido inválido."

    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| CONEXIÓN
|--------------------------------------------------------------------------
*/

try {

    $pdo = Conexion::conectar();


    /*
    |--------------------------------------------------------------------------
    | BUSCAR PEDIDO
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.cliente_id,
            p.estado,
            p.total,
            p.metodo_pago
        FROM pedidos p
        WHERE p.id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $pedido_id
    ]);

    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$pedido) {

        throw new Exception(
            "El pedido no existe."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR ESTADO
    |--------------------------------------------------------------------------
    */

    $estado = strtoupper(
        trim((string)$pedido["estado"])
    );


    if (
        $estado === "PAGADO" ||
        $estado === "SUCCESS"
    ) {

        throw new Exception(
            "El pedido ya se encuentra pagado."
        );
    }


    if ($estado === "PAGO RECHAZADO") {

        throw new Exception(
            "El pedido tiene un pago rechazado."
        );
    }


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
            p.nombre
        FROM pedido_detalle pd
        INNER JOIN productos p
            ON p.id = pd.producto_id
        WHERE pd.pedido_id = ?
        ORDER BY pd.id ASC
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
    | OBTENER TOKEN
    |--------------------------------------------------------------------------
    */

    $ch = curl_init($auth_url);


    curl_setopt_array($ch, [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_POST => true,

        CURLOPT_POSTFIELDS => http_build_query([

            "grant_type" => "client_credentials",

            "client_id" => $client_id,

            "client_secret" => $client_secret,

            "scope" => "*"

        ]),

        CURLOPT_HTTPHEADER => [

            "Content-Type: application/x-www-form-urlencoded",

            "Accept: application/json"

        ],

        CURLOPT_TIMEOUT => 30,

        CURLOPT_SSL_VERIFYPEER => true,

        CURLOPT_SSL_VERIFYHOST => 2

    ]);


    $token_response = curl_exec($ch);

    $token_http = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $token_error = curl_error($ch);

    curl_close($ch);


    if (
        $token_response === false ||
        !empty($token_error)
    ) {

        throw new Exception(
            "No se pudo conectar con el servidor de autenticación de SiPago."
        );
    }


    $token_data = json_decode(
        $token_response,
        true
    );


    if (
        !is_array($token_data) ||
        empty($token_data["access_token"])
    ) {

        throw new Exception(
            "SiPago no devolvió un access_token. HTTP: "
            . $token_http
        );
    }


    $access_token = $token_data["access_token"];


    /*
    |--------------------------------------------------------------------------
    | CONSTRUIR ITEMS
    |--------------------------------------------------------------------------
    */

    $items = [];


    foreach ($detalles as $detalle) {

        $cantidad = (int)$detalle["cantidad"];

        $precio = (float)$detalle["precio"];

        $producto_id = (int)$detalle["producto_id"];

        $nombre = trim(
            (string)$detalle["nombre"]
        );


        if ($cantidad <= 0) {

            throw new Exception(
                "Cantidad inválida en el producto "
                . $producto_id
            );
        }


        if ($precio < 0) {

            throw new Exception(
                "Precio inválido en el producto "
                . $producto_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SI PAGO TRABAJA CON MONTOS ENTEROS
        |--------------------------------------------------------------------------
        |
        | Ejemplo:
        | $1000,50 -> 100050
        |
        */

        $amount = (int)round(
            $precio * 100
        );


        $items[] = [

            "id" => $producto_id,

            "name" => $nombre,

            "unitPrice" => [

                "currency" => "032",

                "amount" => $amount

            ],

            "quantity" => $cantidad

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR ORDEN SIPAGO
    |--------------------------------------------------------------------------
    */

    $payload = [

        "data" => [

            "attributes" => [

                "redirect_urls" => [

                    "success" => $success_url
                        . "?pedido_id="
                        . $pedido_id,

                    "failed" => $failed_url
                        . "?pedido_id="
                        . $pedido_id

                ],

                "webhookUrl" => $webhook_url,

                "currency" => "032",

                "items" => $items

            ]

        ]

    ];


    /*
    |--------------------------------------------------------------------------
    | ENVIAR ORDEN
    |--------------------------------------------------------------------------
    */

    $ch = curl_init(
        $api_url . "/api/v2/orders"
    );


    curl_setopt_array($ch, [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_POST => true,

        CURLOPT_POSTFIELDS => json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
        ),

        CURLOPT_HTTPHEADER => [

            "Authorization: Bearer "
                . $access_token,

            "Content-Type: application/vnd.api+json",

            "Accept: application/vnd.api+json"

        ],

        CURLOPT_TIMEOUT => 30,

        CURLOPT_SSL_VERIFYPEER => true,

        CURLOPT_SSL_VERIFYHOST => 2

    ]);


    $response = curl_exec($ch);

    $http_code = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $curl_error = curl_error($ch);

    curl_close($ch);


    if (
        $response === false ||
        !empty($curl_error)
    ) {

        throw new Exception(
            "Error de conexión con SiPago: "
            . $curl_error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA SIPAGO
    |--------------------------------------------------------------------------
    */

    $response_data = json_decode(
        $response,
        true
    );


    if (!is_array($response_data)) {

        throw new Exception(
            "SiPago devolvió una respuesta inválida."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR CHECKOUT
    |--------------------------------------------------------------------------
    */

    $checkout = $response_data["data"]["links"]["checkout"]
        ?? null;


    if (empty($checkout)) {

        echo json_encode([

            "ok" => false,

            "error" =>
                "SiPago no devolvió el enlace de Checkout.",

            "http_code" => $http_code,

            "respuesta" => $response_data

        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | DATOS DE LA ORDEN
    |--------------------------------------------------------------------------
    */

    $order_uuid =
        $response_data["data"]["id"]
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | GUARDAR REFERENCIA SIPAGO
    |--------------------------------------------------------------------------
    |
    | Solo se ejecuta si existen estas columnas.
    |
    | Si todavía no las tenés en pedidos,
    | agregaremos esas columnas en el próximo paso.
    |
    */

    try {

        $stmt = $pdo->prepare("
            UPDATE pedidos
            SET
                sipago_order_uuid = ?,
                sipago_checkout_url = ?
            WHERE id = ?
        ");

        $stmt->execute([

            $order_uuid,

            $checkout,

            $pedido_id

        ]);

    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | NO DETENER EL CHECKOUT
        |--------------------------------------------------------------------------
        */

    }


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "ok" => true,

        "pedido_id" => $pedido_id,

        "order_uuid" => $order_uuid,

        "checkout" => $checkout

    ], JSON_UNESCAPED_UNICODE);

    exit;


} catch (Throwable $e) {

    echo json_encode([

        "ok" => false,

        "error" => $e->getMessage()

    ], JSON_UNESCAPED_UNICODE);

    exit;
}
?>