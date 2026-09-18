<?php
// clientes/detalle.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

//----------------------------------------------------
// Cliente
//----------------------------------------------------

$stmt = $conexion->prepare("
SELECT *
FROM clientes
WHERE id=?
");

$stmt->execute([$id]);

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$cliente){
    die("Cliente inexistente.");
}

//----------------------------------------------------
// Ventas
//----------------------------------------------------

$stmt = $conexion->prepare("
SELECT

id,
fecha,
forma_pago,
total,
estado

FROM ventas

WHERE cliente_id=?

ORDER BY fecha DESC

");

$stmt->execute([$id]);

$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalVentas = 0;

foreach($ventas as $v){

    if($v["estado"]!="ANULADA"){

        $totalVentas += $v["total"];

    }

}

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Detalle Cliente</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="content">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

<h3>

<i class="bi bi-person-vcard-fill"></i>

Detalle del Cliente

</h3>

<a href="index.php" class="btn btn-secondary">

<i class="bi bi-arrow-left"></i>

Volver

</a>

</div>

<div class="row">

<div class="col-lg-4">

<div class="card shadow mb-4">

<div class="card-header bg-primary text-white">

Datos

</div>

<div class="card-body">

<p>

<strong>Apellido</strong><br>

<?=htmlspecialchars($cliente["apellido"])?>

</p>

<p>

<strong>Nombre</strong><br>

<?=htmlspecialchars($cliente["nombre"])?>

</p>

<p>

<strong>DNI</strong><br>

<?=htmlspecialchars($cliente["dni"])?>

</p>

<p>

<strong>Teléfono</strong><br>

<?=htmlspecialchars($cliente["telefono"])?>

</p>

<p>

<strong>Email</strong><br>

<?=htmlspecialchars($cliente["email"])?>

</p>

<p>

<strong>Dirección</strong><br>

<?=htmlspecialchars($cliente["direccion"])?>

</p>

<p>

<strong>Estado</strong><br>

<?php if($cliente["estado"]): ?>

<span class="badge bg-success">

ACTIVO

</span>

<?php else: ?>

<span class="badge bg-danger">

INACTIVO

</span>

<?php endif; ?>

</p>

<p>

<strong>Fecha Alta</strong><br>

<?=date("d/m/Y",strtotime($cliente["fecha_alta"]))?>

</p>

<p>

<strong>Observaciones</strong><br>

<?=nl2br(htmlspecialchars($cliente["observaciones"]))?>

</p>

</div>

</div>

<div class="card shadow">

<div class="card-header bg-success text-white">

Resumen

</div>

<div class="card-body">

<h5>

Cantidad de Ventas

</h5>

<h2>

<?=count($ventas)?>

</h2>

<hr>

<h5>

Total Comprado

</h5>

<h2>

$

<?=number_format($totalVentas,2,",",".")?>

</h2>

</div>

</div>

</div>

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-dark text-white">

Historial de Compras

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead>

<tr>

<th>ID</th>

<th>Fecha</th>

<th>Pago</th>

<th>Total</th>

<th>Estado</th>

<th></th>

</tr>

</thead>

<tbody>

<?php foreach($ventas as $v): ?>

<tr>

<td>

<?=$v["id"]?>

</td>

<td>

<?=date("d/m/Y H:i",strtotime($v["fecha"]))?>

</td>

<td>

<?=$v["forma_pago"]?>

</td>

<td>

$

<?=number_format($v["total"],2,",",".")?>

</td>

<td>

<?=$v["estado"]?>

</td>

<td>

<a
href="../ventas/detalle.php?id=<?=$v["id"]?>"
class="btn btn-info btn-sm">

<i class="bi bi-eye"></i>

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

</body>

</html>