<?php
// cuentas_corrientes/pdf.php

session_start();

require_once "../config/conexion.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

//------------------------------------------------------
// Cuenta
//------------------------------------------------------

$stmt = $conexion->prepare("

SELECT

cc.*,

c.apellido,
c.nombre,
c.dni,
c.telefono,
c.direccion,
c.email

FROM cuentas_corrientes cc

INNER JOIN clientes c
ON c.id=cc.cliente_id

WHERE cc.id=?

");

$stmt->execute([$id]);

$cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$cuenta){

die("Cuenta inexistente.");

}

//------------------------------------------------------
// Pagos
//------------------------------------------------------

$stmt = $conexion->prepare("

SELECT

p.*,

u.nombre usuario

FROM pagos_cuenta_corriente p

LEFT JOIN usuarios u
ON u.id=p.usuario_id

WHERE cuenta_id=?

ORDER BY fecha

");

$stmt->execute([$id]);

$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

//------------------------------------------------------

$options = new Options();

$options->set('isRemoteEnabled',true);

$dompdf = new Dompdf($options);

ob_start();

?>

<!doctype html>

<html>

<head>

<meta charset="utf-8">

<style>

body{

font-family:DejaVu Sans;

font-size:12px;

}

table{

width:100%;

border-collapse:collapse;

margin-top:10px;

}

th,td{

border:1px solid #000;

padding:6px;

}

th{

background:#efefef;

}

h1,h2,h3{

margin:0;

}

.resumen{

margin-top:20px;

}

</style>

</head>

<body>

<h2>

ESTADO DE CUENTA

</h2>

<hr>

<h3>

Cliente

</h3>

<table>

<tr>

<td>

Apellido

</td>

<td>

<?=htmlspecialchars($cuenta["apellido"])?>

</td>

</tr>

<tr>

<td>

Nombre

</td>

<td>

<?=htmlspecialchars($cuenta["nombre"])?>

</td>

</tr>

<tr>

<td>

DNI

</td>

<td>

<?=$cuenta["dni"]?>

</td>

</tr>

<tr>

<td>

Teléfono

</td>

<td>

<?=$cuenta["telefono"]?>

</td>

</tr>

<tr>

<td>

Dirección

</td>

<td>

<?=$cuenta["direccion"]?>

</td>

</tr>

<tr>

<td>

Email

</td>

<td>

<?=$cuenta["email"]?>

</td>

</tr>

</table>

<div class="resumen">

<table>

<tr>

<th>

Venta

</th>

<th>

Fecha

</th>

<th>

Vencimiento

</th>

<th>

Estado

</th>

</tr>

<tr>

<td>

<?=$cuenta["venta_id"]?>

</td>

<td>

<?=date("d/m/Y",strtotime($cuenta["fecha"]))?>

</td>

<td>

<?=$cuenta["vencimiento"]?>

</td>

<td>

<?=$cuenta["estado"]?>

</td>

</tr>

</table>

</div>

<h3 style="margin-top:20px;">

Movimientos

</h3>

<table>

<tr>

<th>

Fecha

</th>

<th>

Forma Pago

</th>

<th>

Importe

</th>

<th>

Usuario

</th>

</tr>

<?php foreach($pagos as $p): ?>

<tr>

<td>

<?=date("d/m/Y H:i",strtotime($p["fecha"]))?>

</td>

<td>

<?=$p["forma_pago"]?>

</td>

<td>

$

<?=number_format($p["importe"],2,",",".")?>

</td>

<td>

<?=$p["usuario"]?>

</td>

</tr>

<?php endforeach; ?>

</table>

<h3 style="margin-top:20px;">

Resumen

</h3>

<table>

<tr>

<td>

Total

</td>

<td>

$

<?=number_format($cuenta["total"],2,",",".")?>

</td>

</tr>

<tr>

<td>

Pagado

</td>

<td>

$

<?=number_format($cuenta["entregado"],2,",",".")?>

</td>

</tr>

<tr>

<td>

Saldo

</td>

<td>

$

<?=number_format($cuenta["saldo"],2,",",".")?>

</td>

</tr>

</table>

</body>

</html>

<?php

$html = ob_get_clean();

$dompdf->loadHtml($html);

$dompdf->setPaper("A4");

$dompdf->render();

$dompdf->stream(

"Cuenta_Corriente_".$cuenta["id"].".pdf",

["Attachment"=>false]

);