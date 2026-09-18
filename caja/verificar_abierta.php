<?php

// caja/verificar_abierta.php

session_start();

require_once "../config/conexion.php";

header("Content-Type: application/json; charset=utf-8");


if (!isset($_SESSION["usuario_id"])) {

    echo json_encode([
        "abierta" => false
    ]);

    exit;
}


$conexion = (new Conexion())->conectar();


$stmt = $conexion->prepare("
    SELECT id
    FROM cajas
    WHERE usuario_id = ?
      AND estado = 'ABIERTA'
    LIMIT 1
");


$stmt->execute([
    (int)$_SESSION["usuario_id"]
]);


$caja = $stmt->fetch(PDO::FETCH_ASSOC);


echo json_encode([

    "abierta" => !empty($caja),

    "caja_id" => !empty($caja)
        ? (int)$caja["id"]
        : null

]);

exit;