<?php

require_once "../config/conexion.php";


$db=Conexion::conectar();


$id=(int)$_GET['id'];



$stmt=$db->prepare("

SELECT

p.*,

c.nombre categoria

FROM productos p

INNER JOIN categorias c

ON c.id=p.categoria_id

WHERE c.id=?

AND p.activo=1

ORDER BY p.nombre

");


$stmt->execute([$id]);


$productos=$stmt->fetchAll();


?>


<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<title>Categoría</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


</head>


<body class="bg-light">


<div class="container mt-5">


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


<p>

<?=$p['categoria']?>

</p>


<h4 class="text-success">

$ <?=number_format($p['precio_venta'],2,",",".")?>

</h4>



<a href="producto.php?id=<?=$p['id']?>"

class="btn btn-primary">

Ver detalle

</a>


</div>


</div>


</div>


<?php } ?>


</div>


</div>


</body>


</html>