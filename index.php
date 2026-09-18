
<?php
session_start();

require_once "config/conexion.php";
require_once "includes/auth.php";

$conexion = (new Conexion())->conectar();

// Total productos
$stmt = $conexion->query("SELECT COUNT(*) total FROM productos");
$totalProductos = $stmt->fetch(PDO::FETCH_ASSOC)["total"];

// Stock bajo
$stmt = $conexion->query("
    SELECT COUNT(*) total
    FROM productos
    WHERE stock <= stock_minimo
");
$stockBajo = $stmt->fetch(PDO::FETCH_ASSOC)["total"];

// Valor inventario
$stmt = $conexion->query("
    SELECT IFNULL(SUM(stock * precio_compra),0) total
    FROM productos
");
$valorInventario = $stmt->fetch(PDO::FETCH_ASSOC)["total"];

// Productos
$stmt = $conexion->query("
    SELECT
        codigo,
        nombre,
        stock,
        precio_compra,
        precio_venta
    FROM productos
    ORDER BY nombre
");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="assets/css/estilos.css">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="content">

<div class="container-fluid">

<h3 class="mb-4">
<i class="bi bi-speedometer2"></i>
Dashboard
</h3>

<div class="row">

<div class="col-md-4">

<div class="card shadow mb-3">

<div class="card-body text-center">

<h6>Total Productos</h6>

<h2><?= $totalProductos ?></h2>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card shadow mb-3">

<div class="card-body text-center">

<h6>Stock Bajo</h6>

<h2><?= $stockBajo ?></h2>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card shadow mb-3">

<div class="card-body text-center">

<h6>Valor Inventario</h6>

<h2>$ <?= number_format($valorInventario,2,",",".") ?></h2>

</div>

</div>

</div>

</div>

<div class="card shadow">

<div class="card-header">

Últimos Productos

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-striped table-hover">

<thead class="table-dark">

<tr>

<th>Código</th>
<th>Producto</th>
<th>Stock</th>
<th>Compra</th>
<th>Venta</th>

</tr>

</thead>

<tbody>

<?php foreach($productos as $row): ?>

<tr>

<td><?= htmlspecialchars($row["codigo"]) ?></td>

<td><?= htmlspecialchars($row["nombre"]) ?></td>

<td><?= $row["stock"] ?></td>

<td>$ <?= number_format($row["precio_compra"],2,",",".") ?></td>

<td>$ <?= number_format($row["precio_venta"],2,",",".") ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</body>

</html>
