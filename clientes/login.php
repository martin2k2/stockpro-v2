<?php
session_start();

if(isset($_SESSION['cliente_id'])){
    header("Location: perfil.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Login Cliente</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">


<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-5">


<div class="card shadow">

<div class="card-header bg-success text-white text-center">

<h4>Ingreso Cliente</h4>

</div>


<div class="card-body">


<?php if(isset($_GET['error'])){ ?>

<div class="alert alert-danger">

Usuario o contraseña incorrectos

</div>

<?php } ?>


<form action="validar.php" method="POST">


<div class="mb-3">

<label>Email</label>

<input 
type="email"
name="email"
class="form-control"
required>

</div>



<div class="mb-3">

<label>Contraseña</label>

<input 
type="password"
name="password"
class="form-control"
required>

</div>



<button class="btn btn-success w-100">

Ingresar

</button>


</form>


<hr>


<div class="text-center">

<a href="registro.php">

Crear cuenta

</a>

</div>


</div>

</div>


</div>

</div>

</div>


</body>

</html>