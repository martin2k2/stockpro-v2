<?php
// ventas/pdf.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    die("Venta inexistente");
}

/*=========================================
=            CABECERA VENTA              =
=========================================*/

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

if (!$venta) {
    die("Venta inexistente");
}

/*=========================================
=               DETALLE                  =
=========================================*/

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

/*=========================================
=                HTML                    =
=========================================*/

$html = '

<style>

body{

font-family:DejaVu Sans,sans-serif;
font-size:12px;
color:#222;

}

h1{

text-align:center;
margin-bottom:5px;

}

table{

width:100%;
border-collapse:collapse;
margin-top:10px;

}

table th{

background:#198754;
color:#fff;
padding:8px;
border:1px solid #ccc;

}

table td{

padding:7px;
border:1px solid #ccc;

}

.total{

font-size:16px;
font-weight:bold;
text-align:right;
margin-top:15px;

}

.info{

margin-bottom:20px;

}

.info b{

display:inline-block;
width:120px;

}

.footer{

margin-top:40px;
text-align:center;
font-size:11px;
color:#777;

}

</style>

<h1>STOCK PRO</h1>

<h3 style="text-align:center;">Comprobante de Venta</h3>

<hr>

<div class="info">

<b>Venta Nº:</b> '.$venta["id"].'<br>

<b>Fecha:</b> '.date("d/m/Y H:i",strtotime($venta["fecha"])).'<br>

<b>Cliente:</b> '.$venta["cliente"].'<br>

<b>Vendedor:</b> '.$venta["vendedor"].'<br>

<b>Forma de Pago:</b> '.$venta["forma_pago"].'<br>

<b>Estado:</b> '.$venta["estado"].'

</div>

<table>

<tr>

<th>Código</th>

<th>Producto</th>

<th>Cant.</th>

<th>Precio</th>

<th>Desc.</th>

<th>Subtotal</th>

</tr>

';

foreach($detalle as $item){

$html .= '

<tr>

<td>'.$item["codigo"].'</td>

<td>'.$item["nombre"].'</td>

<td align="center">'.$item["cantidad"].'</td>

<td align="right">$ '.number_format($item["precio"],2,",",".").'</td>

<td align="right">$ '.number_format($item["descuento"],2,",",".").'</td>

<td align="right">$ '.number_format($item["subtotal"],2,",",".").'</td>

</tr>

';

}

$html .= '

</table>

<p class="total">

TOTAL: $

'.number_format($venta["total"],2,",",".").'

</p>

<div class="footer">

Documento generado por Stock PRO<br>

'.date("d/m/Y H:i:s").'

</div>

';

/*=========================================
=               DOMPDF                   =
=========================================*/

$options = new Options();
$options->set("isRemoteEnabled", true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper("A4","portrait");

$dompdf->render();

$dompdf->stream(

"Venta_".$venta["id"].".pdf",

["Attachment"=>false]

);

exit;