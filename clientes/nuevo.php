<?php
// clientes/nuevo.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $dni            = trim($_POST["dni"]);
    $apellido       = trim($_POST["apellido"]);
    $nombre         = trim($_POST["nombre"]);
    $telefono       = trim($_POST["telefono"]);
    $direccion      = trim($_POST["direccion"]);
    $email          = trim($_POST["email"]);
    $password       = trim($_POST["password"]);
    $observaciones  = trim($_POST["observaciones"]);
    $estado         = isset($_POST["estado"]) ? 1 : 0;

    if ($password != "") {
        $password = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $password = NULL;
    }

    $token = bin2hex(random_bytes(32));

    $stmt = $conexion->prepare("

        INSERT INTO clientes(

            dni,
            apellido,
            nombre,
            telefono,
            direccion,
            email,
            password,
            token,
            observaciones,
            estado,
            fecha_alta

        )

        VALUES(

            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW()

        )

    ");

    $stmt->execute([

        $dni,
        $apellido,
        $nombre,
        $telefono,
        $direccion,
        $email,
        $password,
        $token,
        $observaciones,
        $estado

    ]);

    header("Location:index.php?ok=1");
    exit;
}

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Nuevo Cliente</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="content">

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h4 class="mb-0">

<i class="bi bi-person-plus-fill"></i>

Nuevo Cliente

</h4>

</div>

<div class="card-body">

<form method="post">

<div class="row">

<div class="col-md-4 mb-3">

<label>DNI</label>

<input
type="text"
name="dni"
class="form-control">

</div>

<div class="col-md-4 mb-3">

<label>Apellido</label>

<input
type="text"
name="apellido"
class="form-control"
required>

</div>

<div class="col-md-4 mb-3">

<label>Nombre</label>

<input
type="text"
name="nombre"
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

<label>Email</label>

<input
type="email"
name="email"
class="form-control">

</div>

<div class="col-md-12 mb-3">

<label>Dirección</label>

<input
type="text"
name="direccion"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>Contraseña (Portal)</label>

<input
type="password"
name="password"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>Estado</label>

<div class="form-check mt-2">

<input
class="form-check-input"
type="checkbox"
name="estado"
checked>

<label class="form-check-label">

Cliente Activo

</label>

</div>

</div>

<div class="col-md-12 mb-3">

<label>Observaciones</label>

<textarea
name="observaciones"
class="form-control"
rows="4"></textarea>

</div>

</div>

<div class="d-flex justify-content-between">

<a
href="index.php"
class="btn btn-secondary">

Volver

</a>

<button
class="btn btn-success">

<i class="bi bi-check-circle"></i>

Guardar Cliente

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

</div>

</body>

</html>