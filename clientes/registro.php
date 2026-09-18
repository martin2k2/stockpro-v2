<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Registro Cliente</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">


<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-8">


<div class="card shadow">


<div class="card-header bg-success text-white">

<h4>Crear Cuenta</h4>

</div>


<div class="card-body">


<form action="guardar.php" method="POST">


<div class="row">


<div class="col-md-6 mb-3">

<label>DNI</label>

<input 
type="text"
name="dni"
class="form-control"
required>

</div>



<div class="col-md-6 mb-3">

<label>Teléfono</label>

<input 
type="text"
name="telefono"
class="form-control">

</div>



<div class="col-md-6 mb-3">

<label>Apellido</label>

<input 
type="text"
name="apellido"
class="form-control"
required>

</div>



<div class="col-md-6 mb-3">

<label>Nombre</label>

<input 
type="text"
name="nombre"
class="form-control"
required>

</div>



<div class="col-md-12 mb-3">

<label>Dirección</label>

<input 
type="text"
name="direccion"
class="form-control">

</div>



<div class="col-md-12 mb-3">

<label>Email</label>

<input 
type="email"
name="email"
class="form-control"
required>

</div>



<div class="col-md-6 mb-3">

<label>Contraseña</label>

<input 
type="password"
name="password"
class="form-control"
required>

</div>



<div class="col-md-6 mb-3">

<label>Confirmar Contraseña</label>

<input 
type="password"
name="password2"
class="form-control"
required>

</div>



</div>


<button class="btn btn-success">

Registrarse

</button>


<a href="login.php" class="btn btn-secondary">

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