<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../config/conexion.php";

$pdo = Conexion::conectar();

$id = (int)$_GET["id"];

$stmt = $pdo->prepare("SELECT * FROM usuarios_stock WHERE id=?");
$stmt->execute([$id]);

$usuario = $stmt->fetch();

if(!$usuario){
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

<div class="card-header bg-warning">

<h3>
<i class="bi bi-pencil"></i>
Editar Usuario
</h3>

</div>

<div class="card-body">

<form action="actualizar.php" method="POST">

<input type="hidden" name="id" value="<?= $usuario['id'] ?>">

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">Nombre</label>

<input
type="text"
name="nombre"
class="form-control"
value="<?= htmlspecialchars($usuario['nombre']) ?>"
required>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">Usuario</label>

<input
type="text"
name="usuario"
class="form-control"
value="<?= htmlspecialchars($usuario['usuario']) ?>"
required>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">Rol</label>

<select name="rol" class="form-select">

<option value="ADMIN" <?= $usuario['rol']=="ADMIN"?"selected":"" ?>>

Administrador

</option>

<option value="OPERADOR" <?= $usuario['rol']=="OPERADOR"?"selected":"" ?>>

Operador

</option>

</select>

</div>

</div>

<button class="btn btn-warning">

<i class="bi bi-save"></i>

Actualizar

</button>

<a href="index.php" class="btn btn-secondary">

Cancelar

</a>

</form>

</div>

</div>

</div>

<?php require_once "../includes/footer.php"; ?>