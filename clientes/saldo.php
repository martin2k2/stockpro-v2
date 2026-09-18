<?php
session_start();

require_once("../config/conexion.php");

header("Content-Type: application/json");

$pdo = Conexion::conectar();

$id = intval($_GET["id"] ?? 0);

$sql = $pdo->prepare("
    SELECT
        IFNULL(SUM(saldo),0) AS saldo
    FROM cuentas_corrientes
    WHERE cliente_id=?
    AND saldo>0
");

$sql->execute([$id]);

echo json_encode($sql->fetch(PDO::FETCH_ASSOC));
