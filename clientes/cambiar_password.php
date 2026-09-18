<?php

session_start();

require_once "../config/conexion.php";


if(!isset($_SESSION['cliente_id'])){

header("Location: login.php");
exit;

}


$db=Conexion::conectar();


if($_SERVER["REQUEST_METHOD"]=="POST"){


$password_actual=$_POST["password_actual"];

$password_nueva=$_POST["password_nueva"];

$password_confirmar=$_POST["password_confirmar"];



if($password_nueva!=$password_confirmar){

$error="Las contraseñas nuevas no coinciden";

}else{


$stmt=$db->prepare("

SELECT password

FROM clientes

WHERE id=?

");


$stmt->execute([$_SESSION['cliente_id']]);


$cliente=$stmt->fetch();



if(!password_verify($password_actual,$cliente["password"])){

$error="La contraseña actual es incorrecta";

}else{


$nuevo_hash=password_hash(
$password_nueva,
PASSWORD_DEFAULT
);



$stmt=$db->prepare("

UPDATE clientes

SET password=?

WHERE id=?

");


$stmt->execute([

$nuevo_hash,

$_SESSION['cliente_id']

]);



$ok="Contraseña actualizada correctamente";


}



}


}

?>


<!DOCTYPE html>

<html lang="es">

<head>


<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">


<title>Cambiar contraseña</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


</head>


<body class="bg-light">



<div class="container mt-5">


<div class="row justify-content-center">


<div class="col-md-5">



<div class="card shadow">



<div class="card-header bg-warning">

<h4>

Cambiar contraseña

</h4>

</div>



<div class="card-body">



<?php if(isset($error)){ ?>


<div class="alert alert-danger">

<?=$error?>

</div>


<?php } ?>



<?php if(isset($ok)){ ?>


<div class="alert alert-success">

<?=$ok?>

</div>


<?php } ?>



<form method="POST">



<div class="mb-3">

<label>

Contraseña actual

</label>


<input

type="password"

name="password_actual"

class="form-control"

required>


</div>



<div class="mb-3">


<label>

Nueva contraseña

</label>


<input

type="password"

name="password_nueva"

class="form-control"

required>


</div>



<div class="mb-3">


<label>

Confirmar nueva contraseña

</label>


<input

type="password"

name="password_confirmar"

class="form-control"

required>


</div>



<button class="btn btn-warning">

Cambiar contraseña

</button>



<a href="perfil.php" class="btn btn-secondary">

Volver

</a>



</form>



</div>


</div>



</div>


</div>


</div>


</body>


</html>