<?php

session_start();

require_once "../config/conexion.php";


if(!isset($_SESSION['cliente_id'])){

header("Location: login.php");
exit;

}


$db = Conexion::conectar();



$stmt=$db->prepare("

SELECT *
FROM clientes
WHERE id=?

");


$stmt->execute([$_SESSION['cliente_id']]);


$cliente=$stmt->fetch();



if(!$cliente){

session_destroy();

header("Location: login.php");
exit;

}

?>


<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Mi Perfil</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>


<body class="bg-light">


<div class="container mt-5">


<div class="row justify-content-center">


<div class="col-md-8">


<div class="card shadow">


<div class="card-header bg-success text-white">

<h4>Mi Perfil</h4>

</div>



<div class="card-body">



<?php if(isset($_GET['ok'])){ ?>

<div class="alert alert-success">

Datos actualizados correctamente

</div>

<?php } ?>



<form action="actualizar.php" method="POST">



<div class="row">



<div class="col-md-6 mb-3">

<label>DNI</label>

<input 
type="text"
name="dni"
class="form-control"
value="<?=htmlspecialchars($cliente['dni'])?>">

</div>




<div class="col-md-6 mb-3">

<label>Teléfono</label>

<input 
type="text"
name="telefono"
class="form-control"
value="<?=htmlspecialchars($cliente['telefono'])?>">

</div>




<div class="col-md-6 mb-3">

<label>Apellido</label>

<input 
type="text"
name="apellido"
class="form-control"
value="<?=htmlspecialchars($cliente['apellido'])?>">

</div>




<div class="col-md-6 mb-3">

<label>Nombre</label>

<input 
type="text"
name="nombre"
class="form-control"
value="<?=htmlspecialchars($cliente['nombre'])?>">

</div>




<div class="col-md-12 mb-3">

<label>Dirección</label>

<input 
type="text"
name="direccion"
class="form-control"
value="<?=htmlspecialchars($cliente['direccion'])?>">

</div>




<div class="col-md-12 mb-3">

<label>Email</label>

<input 
type="email"
name="email"
class="form-control"
value="<?=htmlspecialchars($cliente['email'])?>">

</div>



</div>



<button class="btn btn-success">

Guardar cambios

</button>



<a href="mis_pedidos.php" class="btn btn-primary">

Mis pedidos

</a>



<a href="logout.php" class="btn btn-danger">

Salir

</a>



</form>



</div>


</div>


</div>


</div>


</div>


</body>

</html>