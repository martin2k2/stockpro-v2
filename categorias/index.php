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
    FROM categorias
    ORDER BY nombre
");

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Categorías</title>

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

<i class="bi bi-tags"></i>

Categorías

</h3>

<a
href="nuevo.php"
class="btn btn-primary">

<i class="bi bi-plus-circle"></i>

Nueva Categoría

</a>
<a
    href="../dashboard/index.php"
    class="btn btn-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver

</a>

</div>

<?php if (isset($_GET["ok"])): ?>

<div class="alert alert-success">

Categoría guardada correctamente.

</div>

<?php endif; ?>

<?php if (isset($_GET["editado"])): ?>

<div class="alert alert-success">

Categoría actualizada correctamente.

</div>

<?php endif; ?>

<?php if (isset($_GET["eliminado"])): ?>

<div class="alert alert-success">

Categoría eliminada correctamente.

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

<?php if (empty($categorias)): ?>

<tr>

<td
colspan="3"
class="text-center">

No hay categorías registradas.

</td>

</tr>

<?php else: ?>

<?php foreach ($categorias as $categoria): ?>

<tr>

<td>

<?= (int)$categoria["id"] ?>

</td>

<td>

<?= htmlspecialchars($categoria["nombre"]) ?>

</td>

<td>

<a
href="editar.php?id=<?= (int)$categoria["id"] ?>"
class="btn btn-warning btn-sm"
title="Editar">

<i class="bi bi-pencil"></i>

</a>

<a
href="eliminar.php?id=<?= (int)$categoria["id"] ?>"
class="btn btn-danger btn-sm"
title="Eliminar"
onclick="return confirm('¿Desea eliminar esta categoría?');">

<i class="bi bi-trash"></i>

</a>


</td>

</tr>
<?php if (isset($_GET["error"]) && $_GET["error"] === "usada"): ?>

<div class="alert alert-warning">

No se puede eliminar esta categoría porque tiene productos asociados.

</div>

<?php endif; ?>
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