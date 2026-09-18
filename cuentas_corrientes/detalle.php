<?php
// cuentas_corrientes/detalle.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

//---------------------------------------------------------
// Cuenta Corriente
//---------------------------------------------------------

$stmt = $conexion->prepare("

SELECT

cc.*,

c.apellido,
c.nombre,
c.dni,
c.telefono,
c.email,

v.fecha fecha_venta,
v.forma_pago

FROM cuentas_corrientes cc

INNER JOIN clientes c
ON c.id=cc.cliente_id

INNER JOIN ventas v
ON v.id=cc.venta_id

WHERE cc.id=?

");

$stmt->execute([$id]);

$cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$cuenta){

die("Cuenta inexistente.");

}

//---------------------------------------------------------
// Pagos
//---------------------------------------------------------

$stmt = $conexion->prepare("

SELECT

p.*,

u.nombre usuario

FROM pagos_cuenta_corriente p

LEFT JOIN usuarios u
ON u.id=p.usuario_id

WHERE p.cuenta_id=?

ORDER BY p.fecha DESC

");

$stmt->execute([$id]);

$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Detalle Cuenta Corriente</title>

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

Detalle Cuenta Corriente

</h3>

<div>

<a
href="cobrar.php?id=<?=$cuenta["id"]?>"
class="btn btn-success">

<i class="bi bi-cash-stack"></i>

Registrar Cobro

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

<div class="card shadow mb-4">

<div class="card-header bg-primary text-white">

Cliente

</div>

<div class="card-body">

<h5>

<?=htmlspecialchars($cuenta["apellido"])?>,

<?=htmlspecialchars($cuenta["nombre"])?>

</h5>

<hr>

<p>

<strong>DNI:</strong>

<?=$cuenta["dni"]?>

</p>

<p>

<strong>Teléfono:</strong>

<?=$cuenta["telefono"]?>

</p>

<p>

<strong>Email:</strong>

<?=$cuenta["email"]?>

</p>

</div>

</div>

<div class="card shadow">

<div class="card-header bg-success text-white">

Resumen

</div>

<div class="card-body">

<p>

Venta Nº

<strong>

<?=$cuenta["venta_id"]?>

</strong>

</p>

<p>

Fecha

<strong>

<?=date("d/m/Y",strtotime($cuenta["fecha"]))?>

</strong>

</p>

<p>

Vencimiento

<strong>

<?=$cuenta["vencimiento"] ? date("d/m/Y",strtotime($cuenta["vencimiento"])) : "-"?>

</strong>

</p>

<hr>

<p>

Total

<strong>

$

<?=number_format($cuenta["total"],2,",",".")?>

</strong>

</p>

<p>

Entregado

<strong>

$

<?=number_format($cuenta["entregado"],2,",",".")?>

</strong>

</p>

<p>

Saldo

<strong class="text-danger">

$

<?=number_format($cuenta["saldo"],2,",",".")?>

</strong>

</p>

<p>

Estado

<strong>

<?=$cuenta["estado"]?>

</strong>

</p>

</div>

</div>

</div>

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-dark text-white">

Cobros Registrados

</div>

<div class="card-body">

<table class="table table-bordered table-hover">

<thead>

<tr>

<th>Fecha</th>

<th>Importe</th>

<th>Forma Pago</th>

<th>Usuario</th>

<th>Observaciones</th>

</tr>

</thead>

<tbody>

<?php foreach($pagos as $p): ?>

<tr>

<td>

<?=date("d/m/Y H:i",strtotime($p["fecha"]))?>

</td>

<td>

$

<?=number_format($p["importe"],2,",",".")?>

</td>

<td>

<?=$p["forma_pago"]?>

</td>

<td>

<?=$p["usuario"]?>

</td>

<td>

<?=htmlspecialchars($p["observaciones"])?>

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