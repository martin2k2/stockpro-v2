<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;

}

$conexion = (new Conexion())->conectar();

$id = (int)($_POST["id"] ?? 0);

$nombre = trim($_POST["nombre"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$email = trim($_POST["email"] ?? "");
$direccion = trim($_POST["direccion"] ?? "");

if ($id <= 0 || $nombre === "") {

    header("Location: index.php");
    exit;

}

try {

    $stmt = $conexion->prepare("
        UPDATE proveedores
        SET
            nombre = ?,
            telefono = ?,
            email = ?,
            direccion = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $nombre,
        $telefono,
        $email,
        $direccion,
        $id
    ]);

    header("Location: index.php?ok=2");
    exit;

} catch (PDOException $e) {

    die("Error al actualizar proveedor: " . $e->getMessage());

}