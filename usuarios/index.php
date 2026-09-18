<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../config/conexion.php";

$pdo = Conexion::conectar();

$sql = "SELECT id, nombre, usuario, rol
        FROM usuarios_stock
        ORDER BY nombre";

$usuarios = $pdo->query($sql)->fetchAll();

require_once "../includes/header.php";
//require_once "../includes/navbar.php";
require_once "../includes/sidebar.php";
?>

<div class="main">

<?php require_once "../includes/navbar.php"; ?>

<br><br>
<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

<h2>
<i class="bi bi-people"></i>
Usuarios
</h2>

<a href="nuevo.php" class="btn btn-success">
<i class="bi bi-plus-circle"></i>
Nuevo Usuario
</a>
<a href="../dashboard/index.php" class="btn btn-secondary"> <i class="bi bi-arrow-left"></i> Volver</a>


</div>

<table class="table table-bordered table-striped table-hover datatable">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Nombre</th>
<th>Usuario</th>
<th>Rol</th>
<th width="180">Acciones</th>

</tr>

</thead>

<tbody>

<?php foreach($usuarios as $u): ?>

<tr>

<td><?= $u["id"] ?></td>

<td><?= htmlspecialchars($u["nombre"]) ?></td>

<td><?= htmlspecialchars($u["usuario"]) ?></td>

<td>

<?php if($u["rol"]=="ADMIN"): ?>

<span class="badge bg-danger">Administrador</span>

<?php else: ?>

<span class="badge bg-primary"><?= htmlspecialchars($u["rol"]) ?></span>

<?php endif; ?>

</td>

<td>

<a href="editar.php?id=<?= $u["id"] ?>"
class="btn btn-warning btn-sm me-1">

<i class="bi bi-pencil"></i>

</a>

<a href="cambiar_password.php?id=<?= $u["id"] ?>"
class="btn btn-info btn-sm me-1">

<i class="bi bi-key"></i>

</a>

<?php if($u["id"] != 1): ?>

<a href="eliminar.php?id=<?= $u["id"] ?>"
class="btn btn-danger btn-sm btn-eliminar">

<i class="bi bi-trash"></i>

</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php require_once "../includes/footer.php"; ?>