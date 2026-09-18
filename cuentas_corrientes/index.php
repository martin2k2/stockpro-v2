<?php
// cuentas_corrientes/index.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$sql = "

SELECT

cc.id,
cc.fecha,
cc.vencimiento,
cc.total,
cc.entregado,
cc.saldo,
cc.estado,

c.apellido,
c.nombre,

v.id AS venta

FROM cuentas_corrientes cc

INNER JOIN clientes c
ON c.id=cc.cliente_id

INNER JOIN ventas v
ON v.id=cc.venta_id

ORDER BY

CASE cc.estado
WHEN 'VENCIDA' THEN 1
WHEN 'PENDIENTE' THEN 2
WHEN 'PARCIAL' THEN 3
WHEN 'PAGADA' THEN 4
END,

cc.fecha DESC

";

$cuentas = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Cuentas Corrientes</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="content">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

<h3>

<i class="bi bi-wallet2"></i>

Cuentas Corrientes

</h3>

<a
href="nueva.php"
class="btn btn-success">

<i class="bi bi-plus-circle"></i>

Nueva Cuenta

</a>

</div>

<div class="card shadow">

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover datatable">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>Fecha</th>

<th>Cliente</th>

<th>Venta</th>

<th>Total</th>

<th>Entregado</th>

<th>Saldo</th>

<th>Vencimiento</th>

<th>Estado</th>

<th width="170">

Acciones

</th>

</tr>

</thead>

<tbody>

<?php foreach($cuentas as $cc): ?>

<tr>

<td>

<?=$cc["id"]?>

</td>

<td>

<?=date("d/m/Y",strtotime($cc["fecha"]))?>

</td>

<td>

<?=htmlspecialchars($cc["apellido"])?>,
<?=htmlspecialchars($cc["nombre"])?>

</td>

<td>

#<?=$cc["venta"]?>

</td>

<td>

$

<?=number_format($cc["total"],2,",",".")?>

</td>

<td>

$

<?=number_format($cc["entregado"],2,",",".")?>

</td>

<td>

<strong>

$

<?=number_format($cc["saldo"],2,",",".")?>

</strong>

</td>

<td>

<?=$cc["vencimiento"] ? date("d/m/Y",strtotime($cc["vencimiento"])) : "-"?>

</td>

<td>

<?php

switch($cc["estado"]){

case "PENDIENTE":

echo '<span class="badge bg-warning">PENDIENTE</span>';

break;

case "PARCIAL":

echo '<span class="badge bg-info">PARCIAL</span>';

break;

case "PAGADA":

echo '<span class="badge bg-success">PAGADA</span>';

break;

case "VENCIDA":

echo '<span class="badge bg-danger">VENCIDA</span>';

break;

}

?>

</td>

<td>

<a
href="detalle.php?id=<?=$cc["id"]?>"
class="btn btn-primary btn-sm">

<i class="bi bi-eye"></i>

</a>

<a
href="cobrar.php?id=<?=$cc["id"]?>"
class="btn btn-success btn-sm">

<i class="bi bi-cash-stack"></i>

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

pageLength:10,

order:[[0,"desc"]]

});

});

</script>

</body>

</html>