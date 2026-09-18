<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../config/conexion.php";

$id       = (int)$_POST["id"];
$nombre   = trim($_POST["nombre"]);
$usuario  = trim($_POST["usuario"]);
$rol      = $_POST["rol"];

$pdo = Conexion::conectar();

// Verificar que no exista otro usuario con el mismo nombre
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM usuarios
    WHERE usuario = ? AND id <> ?
");
$stmt->execute([$usuario, $id]);

if($stmt->fetchColumn() > 0){
    header("Location:editar.php?id=".$id."&error=existe");
    exit;
}

$stmt = $pdo->prepare("
    UPDATE usuarios_stock
    SET nombre=?, usuario=?, rol=?
    WHERE id=?
");

$stmt->execute([
    $nombre,
    $usuario,
    $rol,
    $id
]);

header("Location:index.php?ok=1");
exit;