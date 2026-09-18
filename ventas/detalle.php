<?php
// ventas/detalle.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET["id"];

/*====================================
=            CABECERA VENTA          =
====================================*/

$sql = "

SELECT

v.*,

IFNULL(CONCAT(c.apellido,', ',c.nombre),'Consumidor Final') AS cliente,

u.nombre AS vendedor

FROM ventas v

LEFT JOIN clientes c
ON c.id=v.cliente_id

INNER JOIN usuarios u
ON u.id=v.usuario_id

WHERE v.id=?

";

$stmt = $conexion->prepare($sql);
$stmt->execute([$id]);

$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$venta){
    die("La venta no existe.");
}

/*====================================
=             DETALLE                =
====================================*/

$sql = "

SELECT

d.*,

p.codigo,
p.nombre

FROM detalle_ventas d

INNER JOIN productos p
ON p.id=d.producto_id

WHERE d.venta_id=?

ORDER BY d.id

";

$stmt = $conexion->prepare($sql);
$stmt->execute([$id]);

$detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Detalle de Venta</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main">

<div class="content">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

<h3>

<i class="bi bi-receipt"></i>

Detalle de Venta

</h3>

<div>

<a href="ticket.php?id=<?=$venta["id"]?>" class="btn btn-secondary">

<i class="bi bi-printer"></i>

Ticket

</a>

<a href="index.php" class="btn btn-primary">

<i class="bi bi-arrow-left"></i>

Volver

</a>

</div>

</div>

<div class="row">

<div class="col-lg-4">

<div class="card shadow mb-4">

<div class="card-header bg-success text-white">

Datos de la Venta

</div>

<div class="card-body">

<p>

<strong>N° Venta:</strong><br>

<?= $venta["id"] ?>

</p>

<p>

<strong>Fecha:</strong><br>

<?= date("d/m/Y H:i",strtotime($venta["fecha"])) ?>

</p>

<p>

<strong>Cliente:</strong><br>

<?= htmlspecialchars($venta["cliente"]) ?>

</p>

<p>

<strong>Vendedor:</strong><br>

<?= htmlspecialchars($venta["vendedor"]) ?>

</p>

<p>

<strong>Forma de Pago:</strong><br>

<?= htmlspecialchars($venta["forma_pago"]) ?>

</p>

<p>

<strong>Estado:</strong><br>

<?php if($venta["estado"]=="ANULADA"){ ?>

<span class="badge bg-danger">

ANULADA

</span>

<?php }else{ ?>

<span class="badge bg-success">

<?= htmlspecialchars($venta["estado"]) ?>

</span>

<?php } ?>

</p>

<?php if(!empty($venta["observacion"])){ ?>

<p>

<strong>Observación</strong><br>

<?= nl2br(htmlspecialchars($venta["observacion"])) ?>

</p>

<?php } ?>

</div>

</div>

</div>

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-primary text-white">

Productos Vendidos

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>Código</th>

<th>Producto</th>

<th>Cantidad</th>

<th>Precio</th>

<th>Desc.</th>

<th>Subtotal</th>

</tr>

</thead>

<tbody>

<?php foreach($detalle as $item){ ?>

<tr>

<td><?= htmlspecialchars($item["codigo"]) ?></td>

<td><?= htmlspecialchars($item["nombre"]) ?></td>

<td><?= $item["cantidad"] ?></td>

<td>$ <?= number_format($item["precio"],2,",",".") ?></td>

<td>$ <?= number_format($item["descuento"],2,",",".") ?></td>

<td>$ <?= number_format($item["subtotal"],2,",",".") ?></td>

</tr>

<?php } ?>

</tbody>

<tfoot>

<tr class="table-secondary">

<th colspan="5" class="text-end">

TOTAL

</th>

<th>

$

<?= number_format($venta["total"],2,",",".") ?>

</th>

</tr>

</tfoot>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

</body>

</html>