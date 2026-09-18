<?php
// caja/pdf.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

$stmt = $conexion->prepare("

SELECT

c.*,
u.nombre usuario

FROM caja c

INNER JOIN usuarios u
ON u.id=c.usuario_id

WHERE c.id=?

");

$stmt->execute([$id]);

$caja = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$caja){

die("Caja inexistente.");

}

//---------------------------------------------
// Movimientos
//---------------------------------------------

$stmt = $conexion->prepare("

SELECT *

FROM movimientos_caja

WHERE caja_id=?

ORDER BY fecha

");

$stmt->execute([$id]);

$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

//---------------------------------------------
// PDF
//---------------------------------------------

$options = new Options();

$options->set('isRemoteEnabled',true);

$pdf = new Dompdf($options);

$html='

<html>

<head>

<meta charset="utf-8">

<style>

body{

font-family:DejaVu Sans;
font-size:12px;

}

h2{

text-align:center;

}

table{

width:100%;
border-collapse:collapse;

}

table th{

background:#333;
color:#fff;

}

table th,
table td{

border:1px solid #777;
padding:6px;

}

.resumen{

margin-bottom:20px;

}

</style>

</head>

<body>

<h2>ARQUEO DE CAJA</h2>

<div class="resumen">

<b>Caja:</b> '.$caja["id"].'<br>

<b>Usuario:</b> '.$caja["usuario"].'<br>

<b>Apertura:</b> '.$caja["fecha_apertura"].'<br>

<b>Cierre:</b> '.($caja["fecha_cierre"] ?: "-").'<br>

<br>

<b>Saldo Inicial:</b> $ '.number_format($caja["saldo_inicial"],2,",",".").'<br>

<b>Ventas:</b> $ '.number_format($caja["ventas"],2,",",".").'<br>

<b>Ingresos:</b> $ '.number_format($caja["ingresos"],2,",",".").'<br>

<b>Egresos:</b> $ '.number_format($caja["egresos"],2,",",".").'<br>

<b>Saldo Esperado:</b> $ '.number_format($caja["saldo_final"],2,",",".").'<br>

<b>Saldo Real:</b> $ '.number_format($caja["saldo_real"],2,",",".").'<br>

<b>Diferencia:</b> $ '.number_format($caja["diferencia"],2,",",".").'

</div>

<h3>Movimientos</h3>

<table>

<tr>

<th>Fecha</th>

<th>Tipo</th>

<th>Concepto</th>

<th>Importe</th>

</tr>

';

foreach($movimientos as $m){

$html.='

<tr>

<td>'.$m["fecha"].'</td>

<td>'.$m["tipo"].'</td>

<td>'.$m["concepto"].'</td>

<td align="right">$ '.number_format($m["importe"],2,",",".").'</td>

</tr>

';

}

$html.='

</table>

</body>

</html>

';

$pdf->loadHtml($html);

$pdf->setPaper("A4","portrait");

$pdf->render();

$pdf->stream(

"Arqueo_Caja_".$caja["id"].".pdf",

["Attachment"=>false]

);

exit;
?>