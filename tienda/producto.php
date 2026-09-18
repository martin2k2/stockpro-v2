<?php

require_once "../config/conexion.php";


$db=Conexion::conectar();


$id=(int)$_GET['id'];



$stmt=$db->prepare("

SELECT

p.*,

c.nombre categoria

FROM productos p

LEFT JOIN categorias c

ON c.id=p.categoria_id

WHERE p.id=?

");


$stmt->execute([$id]);


$p=$stmt->fetch();



if(!$p){

die("Producto inexistente");

}

?>


<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<title><?=$p['nombre']?></title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


</head>


<body class="bg-light">


<div class="container mt-5">


<div class="card shadow">


<div class="row g-0">



<div class="col-md-5">


<?php if($p['imagen']){ ?>


<img 

src="../uploads/productos/<?=$p['imagen']?>"

class="img-fluid">


<?php } ?>


</div>



<div class="col-md-7">


<div class="card-body">


<h2>

<?=$p['nombre']?>

</h2>


<p>

<?=$p['descripcion']?>

</p>


<h3 class="text-success">

$ <?=number_format($p['precio_venta'],2,",",".")?>

</h3>


<p>

Stock disponible:

<?=$p['stock']?>

</p>



<form action="../carrito/agregar.php" method="POST">


<input type="hidden" name="producto_id" value="<?=$p['id']?>">


<input 

type="number"

name="cantidad"

value="1"

min="1"

max="<?=$p['stock']?>"

class="form-control mb-3">


<button class="btn btn-success">

Agregar carrito

</button>


</form>



</div>


</div>


</div>


</div>


</div>


</body>


</html>