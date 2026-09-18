<?php
// caja/ingreso.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$usuario_id = (int)($_SESSION["usuario_id"] ?? 0);

//---------------------------------------------------------
// Buscar caja abierta
//---------------------------------------------------------

$stmt = $conexion->prepare("
    SELECT *
    FROM cajas
    WHERE estado = 'ABIERTA'
      AND usuario_id = ?
    LIMIT 1
");

$stmt->execute([$usuario_id]);

$caja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$caja) {
    die("No hay una caja abierta.");
}

//---------------------------------------------------------
// Guardar ingreso
//---------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $concepto = trim($_POST["concepto"] ?? "");
    $importe  = (float)($_POST["importe"] ?? 0);

    if ($concepto === "") {
        die("Debe ingresar un concepto.");
    }

    if ($importe <= 0) {
        die("Importe inválido.");
    }

    try {

        $conexion->beginTransaction();

        //-------------------------------------------------
        // Bloquear caja
        //-------------------------------------------------

        $stmt = $conexion->prepare("
            SELECT *
            FROM cajas
            WHERE id = ?
              AND estado = 'ABIERTA'
            FOR UPDATE
        ");

        $stmt->execute([$caja["id"]]);

        $cajaBloqueada = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cajaBloqueada) {
            throw new Exception("La caja ya no está abierta.");
        }

        //-------------------------------------------------
        // Registrar movimiento
        //-------------------------------------------------

        $stmt = $conexion->prepare("
            INSERT INTO movimientos_caja
            (
                caja_id,
                usuario_id,
                tipo,
                concepto,
                importe,
                fecha
            )
            VALUES
            (
                ?,
                ?,
                'INGRESO',
                ?,
                ?,
                NOW()
            )
        ");

        $stmt->execute([
            $cajaBloqueada["id"],
            $usuario_id,
            $concepto,
            $importe
        ]);

        //-------------------------------------------------
        // Actualizar total de ingresos de la caja
        //-------------------------------------------------

        $stmt = $conexion->prepare("
            UPDATE cajas
            SET ingresos = COALESCE(ingresos, 0) + ?
            WHERE id = ?
        ");

        $stmt->execute([
            $importe,
            $cajaBloqueada["id"]
        ]);

        $conexion->commit();

        header("Location: movimientos.php?ok=ingreso");
        exit;

    } catch (Throwable $e) {

        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        die("Error al registrar el ingreso: " . $e->getMessage());
    }
}

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Ingreso de Caja</title>

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

<div class="content">

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-6">

<div class="card shadow">

<div class="card-header bg-success text-white">

    <i class="bi bi-plus-circle"></i>

    Nuevo Ingreso

</div>

<div class="card-body">

<div class="alert alert-info">

    <i class="bi bi-info-circle"></i>

    Caja #<?= (int)$caja["id"] ?>

</div>

<form method="post" autocomplete="off">

<div class="mb-3">

<label class="form-label">

    Concepto

</label>

<input
type="text"
name="concepto"
class="form-control"
maxlength="255"
placeholder="Ej.: Ingreso de efectivo"
required
autofocus>

</div>

<div class="mb-3">

<label class="form-label">

    Importe

</label>

<div class="input-group">

<span class="input-group-text">$</span>

<input
type="number"
step="0.01"
min="0.01"
name="importe"
class="form-control"
placeholder="0.00"
required>

</div>

</div>

<div class="d-grid gap-2">

<button
type="submit"
class="btn btn-success">

    <i class="bi bi-check-circle"></i>

    Guardar Ingreso

</button>

<a
href="movimientos.php"
class="btn btn-secondary">

    <i class="bi bi-arrow-left"></i>

    Cancelar

</a>

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