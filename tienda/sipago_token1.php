<?php
// /stockpro-v2/tienda/sipago_token.php

session_start();

require_once "../config/conexion.php";

header("Content-Type: application/json; charset=utf-8");


/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN SIPAGO DEVELOPMENT
|--------------------------------------------------------------------------
*/

$client_id = "3c21db0f-6913-43db-88d6-2ced87b99a91";  /*credenciales de desarrollo */

$client_secret = "ft6z30q2ftsmu90au0mp";

$auth_url = "https://auth.stg.geopagos.io/oauth/token";


/*
|--------------------------------------------------------------------------
| SOLICITAR TOKEN
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


$respuesta = curl_exec($ch);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$error_curl = curl_error($ch);

curl_close($ch);


/*
|--------------------------------------------------------------------------
| ERROR CURL
|--------------------------------------------------------------------------
*/

if ($respuesta === false || !empty($error_curl)) {

    echo json_encode([

        "ok" => false,

        "error" => "Error de conexión con SiPago.",

        "detalle" => $error_curl

    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| DECODIFICAR RESPUESTA
|--------------------------------------------------------------------------
*/

$data = json_decode($respuesta, true);


if (!is_array($data)) {

    echo json_encode([

        "ok" => false,

        "error" => "Respuesta inválida del servidor de autenticación.",

        "http_code" => $http_code,

        "respuesta" => $respuesta

    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR TOKEN
|--------------------------------------------------------------------------
*/

if (empty($data["access_token"])) {

    echo json_encode([

        "ok" => false,

        "error" => "SiPago no devolvió un access_token.",

        "http_code" => $http_code,

        "respuesta" => $data

    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

echo json_encode([

    "ok" => true,

    "access_token" => $data["access_token"],

    "token_type" => $data["token_type"] ?? "Bearer",

    "expires_in" => $data["expires_in"] ?? null

], JSON_UNESCAPED_UNICODE);

exit;