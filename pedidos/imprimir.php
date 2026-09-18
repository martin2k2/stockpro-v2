<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

requireAdmin();


if(!isset($_SESSION['usuario'])){

header("Location: ../login.php");

exit;

}


$db=Conexion::conectar();


$id=(int)$_GET['id'];



$stmt=$db->prepare("

SELECT

p.*,

CONCAT(c.apellido,' ',c.nombre) cliente

FROM pedidos p

INNER JOIN clientes c

ON c.id=p.cliente_id

WHERE p.id=?

");


$stmt->execute([$id]);


$pedido=$stmt->fetch();



$stmt=$db->prepare("

SELECT

pd.*,

pr.nombre

FROM pedido_detalle pd

INNER JOIN productos pr

ON pr.id=pd.producto_id

WHERE pd.pedido_id=?

");


$stmt->execute([$id]);


$detalle=$stmt->fetchAll();


?>


<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<title>Pedido <?=$id?></title>


<style>

body{

font-family:Arial;

font-size:14px;

}


table{

width:100%;

border-collapse:collapse;

}


td,th{

border:1px solid #000;

padding:8px;

}


h2{

text-align:center;

}


</style>


</head>


<body>


<h2>

COMPROBANTE DE PEDIDO

</h2>


<p>

Pedido:

#<?=$pedido['id']?>

</p>


<p>

Cliente:

<?=$pedido['cliente']?>

</p>


<p>

Fecha:

<?=$pedido['fecha']?>

</p>



<table>


<tr>

<th>Producto</th>

<th>Cantidad</th>

<th>Precio</th>

<th>Total</th>

</tr>



<?php foreach($detalle as $d){ ?>


<tr>


<td><?=$d['nombre']?></td>

<td><?=$d['cantidad']?></td>

<td>$ <?=$d['precio']?></td>

<td>$ <?=$d['subtotal']?></td>


</tr>


<?php } ?>


</table>



<h3>

TOTAL:

$ <?=$pedido['total']?>

</h3>



<script>

window.print();

</script>


</body>

</html>