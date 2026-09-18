<?php
// ventas/devolucion.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

if ($_SESSION["rol"] != "Administrador") {
    die("Acceso denegado.");
}

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    header("Location:index.php");
    exit;
}

$stmt = $conexion->prepare("

SELECT

v.*,
c.nombre cliente,
u.nombre vendedor

FROM ventas v

LEFT JOIN clientes c
ON c.id=v.cliente_id

INNER JOIN usuarios u
ON u.id=v.usuario_id

WHERE v.id=?

");

$stmt->execute([$id]);

$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$venta){
    die("Venta inexistente");
}

$stmt = $conexion->prepare("

SELECT

d.*,
p.codigo,
p.nombre,
p.stock

FROM detalle_ventas d

INNER JOIN productos p
ON p.id=d.producto_id

WHERE venta_id=?

ORDER BY p.nombre

");

$stmt->execute([$id]);

$detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Devolución</title>

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

<i class="bi bi-arrow-return-left"></i>

Devolución de Venta

</h3>

<a href="detalle.php?id=<?=$id?>" class="btn btn-secondary">

Volver

</a>

</div>

<div class="card shadow">

<div class="card-body">

<div class="row mb-4">

<div class="col-md-3">

<strong>Venta</strong><br>

#<?=$venta["id"]?>

</div>

<div class="col-md-3">

<strong>Fecha</strong><br>

<?=date("d/m/Y H:i",strtotime($venta["fecha"]))?>

</div>

<div class="col-md-3">

<strong>Cliente</strong><br>

<?=htmlspecialchars($venta["cliente"] ?: "Consumidor Final")?>

</div>

<div class="col-md-3">

<strong>Total</strong><br>

$ <?=number_format($venta["total"],2,",",".")?>

</div>

</div>

<form action="procesar_devolucion.php" method="post">

<input type="hidden" name="venta_id" value="<?=$id?>">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>Código</th>

<th>Producto</th>

<th>Vendidos</th>

<th>Devolver</th>

</tr>

</thead>

<tbody>

<?php foreach($detalle as $d): ?>

<tr>

<td><?=$d["codigo"]?></td>

<td><?=htmlspecialchars($d["nombre"])?></td>

<td><?=$d["cantidad"]?></td>

<td width="140">

<input

type="number"

class="form-control"

name="cantidad[<?=$d["producto_id"]?>]"

min="0"

max="<?=$d["cantidad"]?>"

value="0"

>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<div class="mb-3">

<label>Motivo</label>

<textarea

name="observacion"

class="form-control"

rows="3"

required

></textarea>

</div>

<button class="btn btn-success">

<i class="bi bi-check-circle"></i>

Procesar Devolución

</button>

</form>

</div>

</div>

</div>

</div>

</body>

</html>