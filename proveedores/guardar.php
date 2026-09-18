<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;

}

$nombre = trim($_POST["nombre"] ?? "");

if ($nombre === "") {

    header("Location: nuevo.php?error=1");
    exit;

}

try {

    $conexion = (new Conexion())->conectar();

    $sql = "
        INSERT INTO proveedores
        (nombre)
        VALUES
        (?)
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $nombre
    ]);

    header("Location: index.php?ok=1");
    exit;

} catch (PDOException $e) {

    die("Error al guardar proveedor: " . $e->getMessage());

}