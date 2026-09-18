<?php

require_once "../config/conexion.php";


$db=Conexion::conectar();


$buscar=trim($_GET['q'] ?? "");



$stmt=$db->prepare("

SELECT

p.*,

c.nombre categoria

FROM productos p

LEFT JOIN categorias c

ON c.id=p.categoria_id

WHERE p.activo=1

AND (

p.nombre LIKE ?

OR p.codigo LIKE ?

)

ORDER BY p.nombre ASC

");



$texto="%".$buscar."%";


$stmt->execute([

$texto,

$texto

]);



$productos=$stmt->fetchAll();


?>


<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<title>Buscar productos</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


</head>


<body class="bg-light">


<div class="container mt-5">


<h3>

Resultados para:

<?=htmlspecialchars($buscar)?>

</h3>



<div class="row">


<?php foreach($productos as $p){ ?>


<div class="col-md-4 mb-4">


<div class="card shadow">


<?php if($p['imagen']){ ?>


<img 

src="../uploads/productos/<?=$p['imagen']?>"

class="card-img-top"

height="220">


<?php } ?>


<div class="card-body">


<h5>

<?=$p['nombre']?>

</h5>



<h4 class="text-success">

$ <?=number_format($p['precio_venta'],2,",",".")?>

</h4>



<a href="producto.php?id=<?=$p['id']?>"

class="btn btn-primary">

Ver producto

</a>



</div>


</div>


</div>


<?php } ?>


</div>


</div>


</body>

</html>