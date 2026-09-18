<?php
// ventas/historial.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$sql = "

SELECT

v.id,
v.numero,
v.fecha,
v.total,
v.forma_pago,

c.nombre AS cliente,
u.nombre AS vendedor

FROM ventas v

LEFT JOIN clientes c
ON c.id=v.cliente_id

INNER JOIN usuarios u
ON u.id=v.usuario_id

ORDER BY v.id DESC

";

$ventas = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Historial de Ventas</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/estilos.css">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="content">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

<h3>

<i class="bi bi-receipt"></i>

Historial de Ventas

</h3>

<a href="index.php" class="btn btn-primary">

<i class="bi bi-plus-circle"></i>

Nueva Venta

</a>

</div>

<div class="card shadow">

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover datatable align-middle">

<thead class="table-dark">

<tr>

<th>N°</th>

<th>Fecha</th>

<th>Cliente</th>

<th>Vendedor</th>

<th>Pago</th>

<th>Total</th>

<th width="180">Acciones</th>

</tr>

</thead>

<tbody>

<?php foreach($ventas as $v): ?>

<tr>

<td>

<?=str_pad($v["numero"],6,"0",STR_PAD_LEFT)?>

</td>

<td>

<?=date("d/m/Y H:i",strtotime($v["fecha"]))?>

</td>

<td>

<?=htmlspecialchars($v["cliente"] ?: "Consumidor Final")?>

</td>

<td>

<?=htmlspecialchars($v["vendedor"])?>

</td>

<td>

<?=$v["forma_pago"]?>

</td>

<td>

$ <?=number_format($v["total"],2,",",".")?>

</td>

<td>

<a
href="detalle.php?id=<?=$v["id"]?>"
class="btn btn-info btn-sm">

<i class="bi bi-eye"></i>

</a>

<a
href="ticket.php?id=<?=$v["id"]?>"
class="btn btn-success btn-sm">

<i class="bi bi-printer"></i>

</a>

<a
href="pdf.php?id=<?=$v["id"]?>"
class="btn btn-danger btn-sm">

<i class="bi bi-file-earmark-pdf"></i>

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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>

$(function(){

$('.datatable').DataTable({

language:{

url:"https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"

},

order:[[0,"desc"]],

pageLength:10

});

});

</script>

</body>

</html>