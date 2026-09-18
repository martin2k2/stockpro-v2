<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $conexion = (new Conexion())->conectar();

    $nombre = trim($_POST["nombre"] ?? "");

    if ($nombre === "") {

        $error = "El nombre de la categoría es obligatorio.";

    } else {

        try {

            $stmt = $conexion->prepare("
                INSERT INTO categorias (nombre)
                VALUES (?)
            ");

            $stmt->execute([$nombre]);

            header("Location: index.php?ok=1");
            exit;

        } catch (PDOException $e) {

            $error = "Error al guardar categoría: " . $e->getMessage();

        }

    }

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Nueva Categoría</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<link
rel="stylesheet"
href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main">

<div class="content">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

<h3>

<i class="bi bi-tags"></i>

Nueva Categoría

</h3>

<a
href="index.php"
class="btn btn-secondary">

<i class="bi bi-arrow-left"></i>

Volver

</a>

</div>

<?php if (isset($error)): ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<div class="card shadow">

<div class="card-header bg-primary text-white">

<i class="bi bi-plus-circle"></i>

Nueva Categoría

</div>

<div class="card-body">

<form method="POST">

<div class="mb-3">

<label class="form-label">

Nombre de la categoría

</label>

<input
type="text"
name="nombre"
class="form-control"
maxlength="150"
required
autofocus>

</div>

<div class="d-flex gap-2">

<button
type="submit"
class="btn btn-success">

<i class="bi bi-check-circle"></i>

Guardar

</button>

<a
href="index.php"
class="btn btn-secondary">

Cancelar

</a>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

</body>

</html>