<?php include("../config/config.php"); ?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Stock Urqui</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>

body{
    background:#f5f6fa;
}

.card{
    border-radius:20px;
    border:none;
}

.logo{
    width:120px;
}

</style>

</head>

<body>

<div class="container">

<div class="row justify-content-center align-items-center min-vh-100">

<div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">

<div class="card shadow-lg p-4">

<div class="text-center mb-4">

<img src="../assets/img/LOGO NUEVO.png"
     class="logo mb-3"
     alt="Logo">

<h2 class="fw-bold">URQUIZA</h2>

<p class="text-muted">

</p>

</div>

<form method="POST" action="validar.php">

<div class="mb-3">

<label class="form-label">
<i class="bi bi-person"></i>
Usuario
</label>

<input
type="text"
name="usuario"
class="form-control form-control-lg"
placeholder=""
required>

</div>

<div class="mb-4">

<label class="form-label">
<i class="bi bi-lock"></i>
Contraseña
</label>

<input
type="password"
name="password"
class="form-control form-control-lg"
placeholder=""
required>

</div>

<button class="btn btn-primary btn-lg w-100">

<i class="bi bi-box-arrow-in-right"></i>

Ingresar

</button>

<?php if(isset($_GET["error"])){ ?>

<div class="alert alert-danger mt-3 text-center">

Usuario o contraseña incorrectos

</div>

<?php } ?>

</form>

</div>

</div>

</div>

</div>

</body>

</html>