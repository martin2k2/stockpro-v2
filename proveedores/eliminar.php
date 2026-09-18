<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";
requireAdmin();

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {

    header("Location: index.php");
    exit;

}

try {

    $stmt = $conexion->prepare("
        SELECT COUNT(*)
        FROM productos
        WHERE proveedor_id = ?
    ");

    $stmt->execute([$id]);

    $cantidad = (int)$stmt->fetchColumn();

    if ($cantidad > 0) {

        header("Location: index.php?error=usado");
        exit;

    }

    $stmt = $conexion->prepare("
        DELETE FROM proveedores
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    header("Location: index.php?eliminado=1");
    exit;

} catch (PDOException $e) {

    die(
        "Error al eliminar proveedor: "
        . $e->getMessage()
    );

}