<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../config/conexion.php";

$pdo = Conexion::conectar();

$id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $password = trim($_POST["password"]);

    if ($password == "") {
        header("Location: cambiar_password.php?id=".$id."&error=1");
        exit;
    }

    $password = md5($password); // Compatible con tu login actual

    $stmt = $pdo->prepare("
        UPDATE usuarios_stock
        SET password=?
        WHERE id=?
    ");

    $stmt->execute([$password, $id]);

    header("Location:index.php?ok=1");
    exit;
}

$stmt = $pdo->prepare("SELECT nombre FROM usuarios_stock WHERE id=?");
$stmt->execute([$id]);

$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location:index.php");
    exit;
}

require_once "../includes/header.php";
//require_once "../includes/navbar.php";
require_once "../includes/sidebar.php";
?>

<div class="main">

<?php require_once "../includes/navbar.php"; ?>
<br><br>
<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-info text-white">

<h3>
<i class="bi bi-key"></i>
Cambiar Contraseña
</h3>

</div>

<div class="card-body">

<?php if(isset($_GET["error"])): ?>

<div class="alert alert-danger">
La contraseña no puede estar vacía.
</div>

<?php endif; ?>

<form method="POST">

<input type="hidden" name="id" value="<?= $id ?>">

<div class="mb-3">

<label class="form-label">Usuario</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($usuario["nombre"]) ?>"
readonly>

</div>

<div class="mb-3">

<label class="form-label">Nueva contraseña</label>

<input
type="password"
name="password"
class="form-control"
required>

</div>

<button class="btn btn-info">

<i class="bi bi-save"></i>

Actualizar Contraseña

</button>