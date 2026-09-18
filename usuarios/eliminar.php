<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../config/conexion.php";

$id = (int)$_GET["id"];

// No permitir eliminar el administrador principal
if($id == 1){
    header("Location:index.php?error=admin");
    exit;
}

$pdo = Conexion::conectar();

$stmt = $pdo->prepare("DELETE FROM usuarios_stock WHERE id=?");
$stmt->execute([$id]);

header("Location:index.php?ok=1");
exit;