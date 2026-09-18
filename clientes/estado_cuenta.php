<?php
// clientes/estado_cuenta.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

//--------------------------------------------------------
// Cliente
//--------------------------------------------------------

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

//--------------------------------------------------------
// Ventas
//--------------------------------------------------------

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

//--------------------------------------------------------
// Pagos
//--------------------------------------------------------

$pagos = [];

if($conexion->query("SHOW TABLES LIKE 'pagos_clientes'")->rowCount()>0){

    $stmt = $conexion->prepare("

    SELECT *

    FROM pagos_clientes

    WHERE cliente_id=?

    ORDER BY fecha DESC

    ");

    $stmt->execute([$id]);

    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

}

//--------------------------------------------------------

$totalVentas = 0;

foreach($ventas as $v){

    if($v["estado"]!="ANULADA"){

        $totalVentas += $v["total"];

    }

}

$totalPagado = 0;

foreach($pagos as $p){

    $totalPagado += $p["importe"];

}

$saldo = $totalVentas - $totalPagado;

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Estado de Cuenta</title>

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

<i class="bi bi-wallet2"></i>

Estado de Cuenta

</h3>

<div>

<a
href="registrar_pago.php?id=<?=$id?>"
class="btn btn-success">

<i class="bi bi-cash"></i>

Registrar Pago

</a>

<a
href="estado_cuenta_pdf.php?id=<?=$id?>"
class="btn btn-danger">

<i class="bi bi-file-earmark-pdf"></i>

PDF

</a>

<a
href="index.php"
class="btn btn-secondary">

Volver

</a>

</div>

</div>

<div class="row">

<div class="col-lg-4">

<div class="card shadow">

<div class="card-header bg-primary text-white">

Cliente

</div>

<div class="card-body">

<h5>

<?=htmlspecialchars($cliente["apellido"])?>,

<?=htmlspecialchars($cliente["nombre"])?>

</h5>

<hr>

<p>

<strong>DNI:</strong>

<?=$cliente["dni"]?>

</p>

<p>

<strong>Teléfono:</strong>

<?=$cliente["telefono"]?>

</p>

<p>

<strong>Email:</strong>

<?=$cliente["email"]?>

</p>

<hr>

<p>

<strong>Total Compras</strong>

</p>

<h3>

$

<?=number_format($totalVentas,2,",",".")?>

</h3>

<p>

<strong>Total Pagado</strong>

</p>

<h3 class="text-success">

$

<?=number_format($totalPagado,2,",",".")?>

</h3>

<p>

<strong>Saldo</strong>

</p>

<h2 class="<?=$saldo>0?'text-danger':'text-success'?>">

$

<?=number_format($saldo,2,",",".")?>

</h2>

</div>

</div>

</div>

<div class="col-lg-8">

<div class="card shadow mb-4">

<div class="card-header bg-dark text-white">

Ventas

</div>

<div class="card-body">

<table class="table table-bordered table-hover">

<thead>

<tr>

<th>ID</th>

<th>Fecha</th>

<th>Pago</th>

<th>Total</th>

<th>Estado</th>

</tr>

</thead>

<tbody>

<?php foreach($ventas as $v): ?>

<tr>

<td><?=$v["id"]?></td>

<td><?=date("d/m/Y",strtotime($v["fecha"]))?></td>

<td><?=$v["forma_pago"]?></td>

<td>

$

<?=number_format($v["total"],2,",",".")?>

</td>

<td><?=$v["estado"]?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<div class="card shadow">

<div class="card-header bg-success text-white">

Pagos

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>

<th>Fecha</th>

<th>Concepto</th>

<th>Importe</th>

</tr>

</thead>

<tbody>

<?php foreach($pagos as $p): ?>

<tr>

<td>

<?=date("d/m/Y",strtotime($p["fecha"]))?>

</td>

<td>

<?=htmlspecialchars($p["concepto"])?>

</td>

<td>

$

<?=number_format($p["importe"],2,",",".")?>

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

</body>

</html>