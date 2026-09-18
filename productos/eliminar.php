<?php

session_start();

require_once "../config/conexion.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit;
}

$db = Conexion::conectar();

$id = (int)$_GET['id'];

$stmt = $db->prepare("SELECT imagen FROM productos WHERE id=?");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if ($producto) {

    if (!empty($producto['imagen'])) {

        $archivo = "../uploads/productos/".$producto['imagen'];

        if (file_exists($archivo)) {
            unlink($archivo);
        }
    }

    $stmt = $db->prepare("DELETE FROM productos WHERE id=?");
    $stmt->execute([$id]);
}

header("Location: index.php");
exit;