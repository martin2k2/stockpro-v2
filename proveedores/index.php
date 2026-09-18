<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";
requireAdmin();

$conexion = (new Conexion())->conectar();

$stmt = $conexion->query("
    SELECT
        id,
        nombre
    FROM proveedores
    ORDER BY nombre
");

$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Proveedores</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<link
rel="stylesheet"
href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main">

<div class="content">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

<h3>

<i class="bi bi-truck"></i>

Proveedores

</h3>

<a
href="nuevo.php"
class="btn btn-primary">

<i class="bi bi-plus-circle"></i>

Nuevo Proveedor

</a>
<a href="../dashboard/index.php" class="btn btn-secondary"> <i class="bi bi-arrow-left"></i> Volver</a>


</div>

<?php if (isset($_GET["ok"])): ?>

<div class="alert alert-success">

Proveedor guardado correctamente.

</div>

<?php endif; ?>

<?php if (isset($_GET["editado"])): ?>

<div class="alert alert-success">

Proveedor actualizado correctamente.

</div>

<?php endif; ?>

<?php if (isset($_GET["eliminado"])): ?>

<div class="alert alert-success">

Proveedor eliminado correctamente.

</div>

<?php endif; ?>

<?php if (isset($_GET["error"])): ?>

<div class="alert alert-danger">

No se puede eliminar el proveedor porque tiene productos asociados.

</div>

<?php endif; ?>

<div class="card shadow">

<div class="card-body">

<div class="table-responsive">

<table class="table table-striped table-hover align-middle">

<thead class="table-dark">

<tr>

<th width="100">
ID
</th>

<th>
Nombre
</th>

<th width="150">
Acciones
</th>

</tr>

</thead>

<tbody>

<?php if (empty($proveedores)): ?>

<tr>

<td
colspan="3"
class="text-center">

No hay proveedores registrados.

</td>

</tr>

<?php else: ?>

<?php foreach ($proveedores as $proveedor): ?>

<tr>

<td>

<?= (int)$proveedor["id"] ?>

</td>

<td>

<?= htmlspecialchars($proveedor["nombre"]) ?>

</td>

<td>

<a
href="editar.php?id=<?= (int)$proveedor["id"] ?>"
class="btn btn-warning btn-sm"
title="Editar">

<i class="bi bi-pencil"></i>

</a>

<a
href="eliminar.php?id=<?= (int)$proveedor["id"] ?>"
class="btn btn-danger btn-sm"
title="Eliminar"
onclick="return confirm('¿Desea eliminar este proveedor?');">

<i class="bi bi-trash"></i>

</a>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

</body>

</html>