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

    // Verificar si existen productos usando esta categoría
    $stmt = $conexion->prepare("
        SELECT COUNT(*) 
        FROM productos
        WHERE categoria_id = ?
    ");

    $stmt->execute([$id]);

    $cantidad = (int)$stmt->fetchColumn();

    if ($cantidad > 0) {

        header("Location: index.php?error=usada");
        exit;
    }

    // Eliminar categoría
    $stmt = $conexion->prepare("
        DELETE FROM categorias
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    header("Location: index.php?eliminado=1");
    exit;

} catch (PDOException $e) {

    die("Error al eliminar categoría: " . $e->getMessage());

}