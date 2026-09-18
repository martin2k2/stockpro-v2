<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit;
}

$nombre   = trim($_POST["nombre"]);
$usuario  = trim($_POST["usuario"]);
$password = md5($_POST["password"]); // Compatible con tu login actual
$rol      = $_POST["rol"];

$pdo = Conexion::conectar();

// Verificar si el usuario ya existe
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios_stock WHERE usuario = ?");
$stmt->execute([$usuario]);

if ($stmt->fetchColumn() > 0) {
    header("Location: nuevo.php?error=existe");
    exit;
}

// Insertar usuario
$stmt = $pdo->prepare("
    INSERT INTO usuarios_stock
    (nombre, usuario, password, rol)
    VALUES (?, ?, ?, ?)
");

$stmt->execute([
    $nombre,
    $usuario,
    $password,
    $rol
]);

header("Location: index.php?ok=1");
exit;