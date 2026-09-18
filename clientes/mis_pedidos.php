<?php

session_start();

require_once "../config/conexion.php";


if(!isset($_SESSION['cliente_id'])){

header("Location: login.php");
exit;

}


$db=Conexion::conectar();



$stmt=$db->prepare("

SELECT *

FROM pedidos

WHERE cliente_id=?

ORDER BY fecha DESC

");


$stmt->execute([$_SESSION['cliente_id']]);


$pedidos=$stmt->fetchAll();

?>


<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Mis Pedidos</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


</head>


<body class="bg-light">


<div class="container mt-5">


<div class="card shadow">


<div class="card-header bg-primary text-white">

<h4>Mis Pedidos</h4>

</div>


<div class="card-body">


<table class="table table-bordered table-striped">


<thead>

<tr>

<th>N°</th>

<th>Fecha</th>

<th>Total</th>

<th>Estado</th>

<th>Pago</th>

<th>Detalle</th>

</tr>

</thead>


<tbody>


<?php foreach($pedidos as $p){ ?>


<tr>


<td>

#<?=$p['id']?>

</td>


<td>

<?=date("d/m/Y H:i",strtotime($p['fecha']))?>

</td>


<td>

$ <?=number_format($p['total'],2,",",".")?>

</td>


<td>

<span class="badge bg-success">

<?=$p['estado']?>

</span>

</td>


<td>

<?=$p['metodo_pago']?>

</td>


<td>

<a 
href="pedido.php?id=<?=$p['id']?>"
class="btn btn-primary btn-sm">

Ver

</a>

</td>


</tr>


<?php } ?>


</tbody>


</table>



<a href="../tienda/index.php" class="btn btn-secondary">

Continuar comprando

</a>



</div>


</div>


</div>


</body>

</html>