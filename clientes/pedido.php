<?php

session_start();

require_once "../config/conexion.php";


if(!isset($_SESSION['cliente_id'])){

header("Location: login.php");
exit;

}


$db=Conexion::conectar();


$id=(int)$_GET['id'];



$stmt=$db->prepare("

SELECT *

FROM pedidos

WHERE id=?

AND cliente_id=?

");


$stmt->execute([

$id,
$_SESSION['cliente_id']

]);



$pedido=$stmt->fetch();



if(!$pedido){

die("Pedido inexistente");

}



$stmt=$db->prepare("

SELECT

pd.*,

p.nombre

FROM pedido_detalle pd

INNER JOIN productos p

ON p.id=pd.producto_id

WHERE pd.pedido_id=?

");


$stmt->execute([$id]);


$detalle=$stmt->fetchAll();


?>


<!DOCTYPE html>

<html lang="es">

<head>


<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">


<title>Detalle Pedido</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


</head>


<body class="bg-light">


<div class="container mt-5">


<div class="card shadow">


<div class="card-header bg-success text-white">


<h4>

Pedido #<?=$pedido['id']?>

</h4>


</div>


<div class="card-body">


<p>

<strong>Fecha:</strong>

<?=date("d/m/Y H:i",strtotime($pedido['fecha']))?>

</p>


<p>

<strong>Estado:</strong>

<?=$pedido['estado']?>

</p>


<p>

<strong>Método de pago:</strong>

<?=$pedido['metodo_pago']?>

</p>



<table class="table table-bordered">


<thead>

<tr>

<th>Producto</th>

<th>Cantidad</th>

<th>Precio</th>

<th>Subtotal</th>

</tr>

</thead>



<tbody>


<?php foreach($detalle as $d){ ?>


<tr>

<td>

<?=$d['nombre']?>

</td>


<td>

<?=$d['cantidad']?>

</td>


<td>

$ <?=number_format($d['precio'],2,",",".")?>

</td>


<td>

$ <?=number_format($d['subtotal'],2,",",".")?>

</td>


</tr>


<?php } ?>


</tbody>


</table>


<h4 class="text-end">

TOTAL:

$ <?=number_format($pedido['total'],2,",",".")?>

</h4>



<a href="mis_pedidos.php" class="btn btn-secondary">

Volver

</a>


</div>


</div>


</div>


</body>

</html>