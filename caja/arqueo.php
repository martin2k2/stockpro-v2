<?php
// caja/arqueo.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$stmt = $conexion->prepare("
SELECT *
FROM caja
WHERE estado='ABIERTA'
AND usuario_id=?
LIMIT 1
");

$stmt->execute([$_SESSION["usuario_id"]]);

$caja = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$caja){
    die("No hay una caja abierta.");
}

//---------------------------------------------
// Ventas
//---------------------------------------------

$stmt = $conexion->prepare("
SELECT IFNULL(SUM(total),0)
FROM ventas
WHERE estado='PAGADA'
AND usuario_id=?
AND fecha>=?
");

$stmt->execute([
$_SESSION["usuario_id"],
$caja["fecha_apertura"]
]);

$ventas = $stmt->fetchColumn();

//---------------------------------------------
// Ingresos
//---------------------------------------------

$stmt = $conexion->prepare("
SELECT IFNULL(SUM(importe),0)
FROM movimientos_caja
WHERE caja_id=?
AND tipo='INGRESO'
");

$stmt->execute([$caja["id"]]);

$ingresos = $stmt->fetchColumn();

//---------------------------------------------
// Egresos
//---------------------------------------------

$stmt = $conexion->prepare("
SELECT IFNULL(SUM(importe),0)
FROM movimientos_caja
WHERE caja_id=?
AND tipo='EGRESO'
");

$stmt->execute([$caja["id"]]);

$egresos = $stmt->fetchColumn();

//---------------------------------------------

$esperado =

$caja["saldo_inicial"]
+
$ventas
+
$ingresos
-
$egresos;

$diferencia = 0;

if($_SERVER["REQUEST_METHOD"]=="POST"){

$real = (float)$_POST["saldo_real"];

$diferencia = $real - $esperado;

$stmt = $conexion->prepare("

UPDATE caja

SET

ventas=?,
ingresos=?,
egresos=?,
saldo_final=?,
saldo_real=?,
diferencia=?

WHERE id=?

");

$stmt->execute([

$ventas,
$ingresos,
$egresos,
$esperado,
$real,
$diferencia,
$caja["id"]

]);

header("Location:detalle.php?id=".$caja["id"]);

exit;

}

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<title>Arqueo de Caja</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="content">

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-6">

<div class="card shadow">

<div class="card-header bg-warning">

<h5 class="mb-0">

<i class="bi bi-calculator"></i>

Arqueo de Caja

</h5>

</div>

<div class="card-body">

<table class="table">

<tr>

<th>Saldo Inicial</th>

<td>

$ <?=number_format($caja["saldo_inicial"],2,",",".")?>

</td>

</tr>

<tr>

<th>Ventas</th>

<td>

$ <?=number_format($ventas,2,",",".")?>

</td>

</tr>

<tr>

<th>Ingresos</th>

<td>

$ <?=number_format($ingresos,2,",",".")?>

</td>

</tr>

<tr>

<th>Egresos</th>

<td>

$ <?=number_format($egresos,2,",",".")?>

</td>

</tr>

<tr class="table-success">

<th>Saldo Esperado</th>

<td>

<strong>

$ <?=number_format($esperado,2,",",".")?>

</strong>

</td>

</tr>

</table>

<form method="post">

<div class="mb-3">

<label>

Saldo contado

</label>

<input

type="number"

step="0.01"

name="saldo_real"

class="form-control"

required

>

</div>

<div class="d-grid">

<button class="btn btn-warning">

Guardar Arqueo

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