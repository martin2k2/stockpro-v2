<?php
// ventas/ticket.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

$sql = "

SELECT

v.*,

IFNULL(CONCAT(c.apellido,' ',c.nombre),'Consumidor Final') AS cliente,

u.nombre AS vendedor

FROM ventas v

LEFT JOIN clientes c
ON c.id = v.cliente_id

INNER JOIN usuarios_stock u
ON u.id = v.usuario_id

WHERE v.id = ?

";

$stmt = $conexion->prepare($sql);
$stmt->execute([$id]);

$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    die("Venta inexistente");
}

$sql = "

SELECT

d.*,

p.codigo,
p.nombre

FROM detalle_ventas d

INNER JOIN productos p
ON p.id = d.producto_id

WHERE d.venta_id = ?

ORDER BY d.id

";

$stmt = $conexion->prepare($sql);
$stmt->execute([$id]);

$detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Ticket</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#eee;
}

.ticket{
    width:320px;
    margin:30px auto;
    background:#fff;
    padding:20px;
    border:1px solid #ddd;
    font-family:monospace;
    font-size:14px;
}

.logo{
    text-align:center;
    margin-bottom:10px;
}

.logo img{
    max-width:150px;
}

h3{
    text-align:center;
    margin-bottom:3px;
}

table{
    width:100%;
}

table td{
    padding:2px 0;
}

.total{
    font-size:18px;
    font-weight:bold;
}

.centrado{
    text-align:center;
}

@media print{

.noprint{
    display:none;
}

body{
    background:#fff;
}

.ticket{
    border:none;
    margin:0;
    width:100%;
}

}

</style>

</head>

<body>

<div class="ticket">

<div class="logo">

<img src="../assets/img/logo.png">

</div>

<h3>STOCK PRO</h3>

<div class="centrado">

Control de Stock<br>
Sistema de Ventas

</div>

<hr>

<b>Venta Nº:</b>
<?= $venta["id"] ?>
<br>

<b>Fecha:</b><br>

<?= date("d/m/Y H:i", strtotime($venta["fecha"])) ?>

<br><br>

<b>Cliente:</b><br>

<?= htmlspecialchars($venta["cliente"]) ?>

<br><br>

<b>Vendedor:</b><br>

<?= htmlspecialchars($venta["vendedor"]) ?>

<hr>

<table>

<?php foreach($detalle as $item): ?>

<tr>

<td colspan="2">

<?= htmlspecialchars($item["nombre"]) ?>

</td>

</tr>

<tr>

<td>

<?= $item["cantidad"] ?>

x

$

<?= number_format($item["precio"],2,",",".") ?>

</td>

<td align="right">

$

<?= number_format($item["subtotal"],2,",",".") ?>

</td>

</tr>

<?php endforeach; ?>

</table>

<hr>

<table>

<tr>

<td class="total">

TOTAL

</td>

<td align="right" class="total">

$

<?= number_format($venta["total"],2,",",".") ?>

</td>

</tr>

</table>

<hr>

Forma de Pago<br>

<b>

<?= htmlspecialchars($venta["forma_pago"]) ?>

</b>

<?php if(!empty($venta["observacion"])): ?>

<hr>

<?= nl2br(htmlspecialchars($venta["observacion"])) ?>

<?php endif; ?>

<hr>

<div class="centrado">

¡¡Gracias por su compra!!

</div>

</div>

<div class="text-center mt-3 noprint">

<button
class="btn btn-primary"
onclick="window.print();">

Imprimir

</button>

<a
href="index.php"
class="btn btn-success">

Volver

</a>

</div>

</body>

</html>