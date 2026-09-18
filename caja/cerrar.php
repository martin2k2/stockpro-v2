<?php
// caja/cerrar.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$usuario_id = (int)($_SESSION["usuario_id"] ?? 0);

//---------------------------------------------------------
// Buscar caja abierta
//---------------------------------------------------------

$stmt = $conexion->prepare("
    SELECT
        c.*,
        u.nombre AS usuario_nombre
    FROM cajas c
    LEFT JOIN usuarios_stock u
        ON u.id = c.usuario_id
    WHERE c.estado = 'ABIERTA'
      AND c.usuario_id = ?
    LIMIT 1
");

$stmt->execute([$usuario_id]);

$caja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$caja) {
    die("No hay una caja abierta.");
}

//---------------------------------------------------------
// Calcular ventas de la caja
//---------------------------------------------------------

$stmt = $conexion->prepare("
    SELECT
        COUNT(*) AS cantidad,
        COALESCE(SUM(total), 0) AS total
    FROM ventas
    WHERE caja_id = ?
      AND (estado IS NULL OR UPPER(TRIM(estado)) <> 'ANULADA')
");

$stmt->execute([$caja["id"]]);

$ventas = $stmt->fetch(PDO::FETCH_ASSOC);

$cantidadVentas = (int)$ventas["cantidad"];
$totalVentas    = (float)$ventas["total"];

//---------------------------------------------------------
// Calcular ventas en efectivo
//---------------------------------------------------------

$stmt = $conexion->prepare("
    SELECT
        COALESCE(SUM(total), 0) AS total
    FROM ventas
    WHERE caja_id = ?
      AND UPPER(TRIM(forma_pago)) = 'EFECTIVO'
      AND (estado IS NULL OR UPPER(TRIM(estado)) <> 'ANULADA')
");

$stmt->execute([$caja["id"]]);

$ventasEfectivo = (float)$stmt->fetchColumn();

//---------------------------------------------------------
// Ingresos y egresos manuales
//---------------------------------------------------------

$ingresos = (float)($caja["ingresos"] ?? 0);
$egresos  = (float)($caja["egresos"] ?? 0);

$saldoInicial = (float)($caja["saldo_inicial"] ?? 0);

//---------------------------------------------------------
// Saldo esperado
//
// NO sumamos cajas.ingresos como ventas.
// Las ventas en efectivo se toman desde ventas.
// Los ingresos son movimientos manuales.
// Los egresos son movimientos manuales.
//---------------------------------------------------------

$saldoEsperado =
    $saldoInicial
    + $ventasEfectivo
    + $ingresos
    - $egresos;

//---------------------------------------------------------
// Cerrar caja
//---------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $saldoReal = isset($_POST["saldo_real"])
        ? (float)$_POST["saldo_real"]
        : 0;

    if ($saldoReal < 0) {
        die("El saldo real no puede ser negativo.");
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
            throw new Exception(
                "La caja ya fue cerrada o no está disponible."
            );
        }

        //-------------------------------------------------
        // Recalcular ventas
        //-------------------------------------------------

        $stmt = $conexion->prepare("
            SELECT
                COALESCE(SUM(total), 0) AS total
            FROM ventas
            WHERE caja_id = ?
              AND (estado IS NULL OR UPPER(TRIM(estado)) <> 'ANULADA')
        ");

        $stmt->execute([$caja["id"]]);

        $totalVentasFinal = (float)$stmt->fetchColumn();

        //-------------------------------------------------
        // Recalcular efectivo
        //-------------------------------------------------

        $stmt = $conexion->prepare("
            SELECT
                COALESCE(SUM(total), 0) AS total
            FROM ventas
            WHERE caja_id = ?
              AND UPPER(TRIM(forma_pago)) = 'EFECTIVO'
              AND (estado IS NULL OR UPPER(TRIM(estado)) <> 'ANULADA')
        ");

        $stmt->execute([$caja["id"]]);

        $ventasEfectivoFinal = (float)$stmt->fetchColumn();

        //-------------------------------------------------
        // Obtener ingresos y egresos actuales
        //-------------------------------------------------

        $ingresosFinal = (float)(
            $cajaBloqueada["ingresos"] ?? 0
        );

        $egresosFinal = (float)(
            $cajaBloqueada["egresos"] ?? 0
        );

        $saldoInicialFinal = (float)(
            $cajaBloqueada["saldo_inicial"] ?? 0
        );

        //-------------------------------------------------
        // Saldo esperado definitivo
        //-------------------------------------------------

        $saldoFinal =
            $saldoInicialFinal
            + $ventasEfectivoFinal
            + $ingresosFinal
            - $egresosFinal;

        //-------------------------------------------------
        // Diferencia
        //-------------------------------------------------

        $diferencia = $saldoReal - $saldoFinal;

        //-------------------------------------------------
        // Actualizar caja
        //-------------------------------------------------

        $stmt = $conexion->prepare("
            UPDATE cajas
            SET
                fecha_cierre = NOW(),
                ventas = ?,
                ingresos = ?,
                egresos = ?,
                saldo_final = ?,
                saldo_real = ?,
                diferencia = ?,
                estado = 'CERRADA'
            WHERE id = ?
        ");

        $stmt->execute([
            $totalVentasFinal,
            $ingresosFinal,
            $egresosFinal,
            $saldoFinal,
            $saldoReal,
            $diferencia,
            $cajaBloqueada["id"]
        ]);

        $conexion->commit();

        header("Location: index.php?ok=cerrada");
        exit;

    } catch (Throwable $e) {

        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        die(
            "Error al cerrar la caja: "
            . htmlspecialchars($e->getMessage())
        );
    }
}

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Cerrar Caja</title>

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

<div class="col-lg-7">

<div class="card shadow">

<div class="card-header bg-danger text-white">

    <i class="bi bi-lock-fill"></i>

    Cerrar Caja

</div>

<div class="card-body">

<div class="alert alert-warning">

    <i class="bi bi-exclamation-triangle"></i>

    Revise los valores antes de cerrar la caja.
    Una vez cerrada, no podrá continuar registrando
    ventas en esta caja.

</div>

<!-- RESUMEN -->

<div class="table-responsive mb-4">

<table class="table table-bordered">

<tbody>

<tr>

<th width="60%">
    Saldo inicial
</th>

<td class="text-end">

    $<?= number_format(
        $saldoInicial,
        2,
        ",",
        "."
    ) ?>

</td>

</tr>

<tr>

<th>
    Ventas en efectivo
</th>

<td class="text-end text-success">

    + $<?= number_format(
        $ventasEfectivo,
        2,
        ",",
        "."
    ) ?>

</td>

</tr>

<tr>

<th>
    Ingresos manuales
</th>

<td class="text-end text-success">

    + $<?= number_format(
        $ingresos,
        2,
        ",",
        "."
    ) ?>

</td>

</tr>

<tr>

<th>
    Egresos
</th>

<td class="text-end text-danger">

    - $<?= number_format(
        $egresos,
        2,
        ",",
        "."
    ) ?>

</td>

</tr>

<tr class="table-primary">

<th>
    Saldo esperado
</th>

<td class="text-end fw-bold fs-5">

    $<?= number_format(
        $saldoEsperado,
        2,
        ",",
        "."
    ) ?>

</td>

</tr>

<tr>

<th>
    Cantidad de ventas
</th>

<td class="text-end">

    <?= $cantidadVentas ?>

</td>

</tr>

<tr>

<th>
    Total de ventas
</th>

<td class="text-end">

    $<?= number_format(
        $totalVentas,
        2,
        ",",
        "."
    ) ?>

</td>

</tr>

</tbody>

</table>

</div>

<!-- FORMULARIO -->

<form method="post" id="formCerrarCaja">

<div class="mb-4">

<label class="form-label fw-bold">

    Dinero real en caja

</label>

<div class="input-group input-group-lg">

<span class="input-group-text">$</span>

<input
type="number"
name="saldo_real"
id="saldo_real"
class="form-control"
step="0.01"
min="0"
required
placeholder="0.00"
>

</div>

<div class="form-text">

    Ingrese el importe que realmente tiene físicamente
    en la caja.

</div>

</div>

<div class="d-grid gap-2">

<button
type="submit"
class="btn btn-danger btn-lg">

    <i class="bi bi-lock-fill"></i>

    Cerrar Caja

</button>

<a
href="index.php"
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

<script>

document
.getElementById("formCerrarCaja")
.addEventListener("submit", function(event) {

    const saldoReal = parseFloat(
        document.getElementById("saldo_real").value
    );

    if (isNaN(saldoReal) || saldoReal < 0) {

        event.preventDefault();

        alert("Ingrese un saldo real válido.");

        return;
    }

    const confirmar = confirm(
        "¿Está seguro de cerrar la caja?\n\n" +
        "Esta operación no se puede deshacer."
    );

    if (!confirmar) {
        event.preventDefault();
    }

});

</script>

</body>

</html>