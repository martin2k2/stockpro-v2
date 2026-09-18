<?php
// caja/movimientos.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$usuario_id = (int)($_SESSION["usuario_id"] ?? 0);

//---------------------------------------------------------
// Buscar caja abierta del usuario
//---------------------------------------------------------

$stmt = $conexion->prepare("
    SELECT
        c.*,
        u.nombre AS usuario_nombre
    FROM cajas c
    LEFT JOIN usuarios_stock u ON u.id = c.usuario_id
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
// Movimientos de caja
//---------------------------------------------------------

$stmt = $conexion->prepare("
    SELECT
        mc.id,
        mc.caja_id,
        mc.usuario_id,
        mc.tipo,
        mc.importe,
        mc.concepto,
        mc.fecha,
        u.nombre AS usuario_nombre
    FROM movimientos_caja mc
    LEFT JOIN usuarios_stock u
        ON u.id = mc.usuario_id
    WHERE mc.caja_id = ?
    ORDER BY mc.fecha DESC, mc.id DESC
");

$stmt->execute([$caja["id"]]);

$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

//---------------------------------------------------------
// Totales
//---------------------------------------------------------

$totalIngresos = 0;
$totalEgresos  = 0;

foreach ($movimientos as $m) {

    if (strtoupper(trim($m["tipo"])) === "INGRESO") {
        $totalIngresos += (float)$m["importe"];
    } else {
        $totalEgresos += (float)$m["importe"];
    }
}

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Movimientos de Caja</title>

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



<div class="content">

<div class="container-fluid">

<!-- ENCABEZADO -->

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

    <div>

        <h3 class="mb-1">

            <i class="bi bi-cash-coin"></i>

            Movimientos de Caja

        </h3>

        <small class="text-muted">

            Caja #<?= (int)$caja["id"] ?>

            -

            <?= htmlspecialchars($caja["usuario_nombre"] ?? "Usuario") ?>

        </small>

    </div>

    <div class="d-flex gap-2">

        <a
        href="ingreso.php"
        class="btn btn-success">

            <i class="bi bi-plus-circle"></i>

            Ingreso

        </a>

        <a
        href="egreso.php"
        class="btn btn-danger">

            <i class="bi bi-dash-circle"></i>

            Egreso

        </a>

    </div>
    </a>
<a href="../caja/index.php" class="btn btn-secondary">

Volver

</a>
</div>


<!-- RESUMEN -->

<div class="row mb-4">

    <div class="col-md-4 mb-3">

        <div class="card shadow-sm border-success h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted">
                            Ingresos
                        </small>

                        <h4 class="text-success mb-0">

                            $<?= number_format(
                                $totalIngresos,
                                2,
                                ",",
                                "."
                            ) ?>

                        </h4>

                    </div>

                    <i class="bi bi-arrow-down-circle text-success fs-1"></i>

                </div>

            </div>

        </div>

    </div>


    <div class="col-md-4 mb-3">

        <div class="card shadow-sm border-danger h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted">
                            Egresos
                        </small>

                        <h4 class="text-danger mb-0">

                            $<?= number_format(
                                $totalEgresos,
                                2,
                                ",",
                                "."
                            ) ?>

                        </h4>

                    </div>

                    <i class="bi bi-arrow-up-circle text-danger fs-1"></i>

                </div>

            </div>

        </div>

    </div>


    <div class="col-md-4 mb-3">

        <div class="card shadow-sm border-primary h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted">
                            Neto
                        </small>

                        <h4 class="text-primary mb-0">

                            $<?= number_format(
                                $totalIngresos - $totalEgresos,
                                2,
                                ",",
                                "."
                            ) ?>

                        </h4>

                    </div>

                    <i class="bi bi-wallet2 text-primary fs-1"></i>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- TABLA -->

<div class="card shadow">

    <div class="card-header">

        <strong>

            <i class="bi bi-list-ul"></i>

            Historial de movimientos

        </strong>

    </div>

    <div class="card-body">

        <?php if (!$movimientos): ?>

            <div class="text-center text-muted py-5">

                <i class="bi bi-inbox fs-1"></i>

                <p class="mt-3 mb-0">
                    No hay movimientos registrados en esta caja.
                </p>

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>Fecha</th>

                            <th>Tipo</th>

                            <th>Concepto</th>

                            <th class="text-end">Importe</th>

                            <th>Usuario</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($movimientos as $m): ?>

                        <?php

                        $tipo = strtoupper(trim((string)$m["tipo"]));

                        $esIngreso = ($tipo === "INGRESO");

                        ?>

                        <tr>

                            <td>

                                <?= date(
                                    "d/m/Y H:i",
                                    strtotime($m["fecha"])
                                ) ?>

                            </td>

                            <td>

                                <?php if ($esIngreso): ?>

                                    <span class="badge bg-success">

                                        <i class="bi bi-plus-circle"></i>

                                        INGRESO

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-danger">

                                        <i class="bi bi-dash-circle"></i>

                                        EGRESO

                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $m["concepto"]
                                ) ?>

                            </td>

                            <td class="text-end fw-bold">

                                <span class="<?= $esIngreso
                                    ? 'text-success'
                                    : 'text-danger'
                                ?>">

                                    <?= $esIngreso ? '+' : '-' ?>

                                    $<?= number_format(
                                        (float)$m["importe"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </span>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $m["usuario_nombre"] ?? "Usuario"
                                ) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                    <tfoot class="table-light">

                        <tr>

                            <th colspan="3" class="text-end">

                                Total ingresos:

                            </th>

                            <th class="text-end text-success">

                                $<?= number_format(
                                    $totalIngresos,
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </th>

                            <th></th>

                        </tr>

                        <tr>

                            <th colspan="3" class="text-end">

                                Total egresos:

                            </th>

                            <th class="text-end text-danger">

                                $<?= number_format(
                                    $totalEgresos,
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </th>

                            <th></th>

                        </tr>

                        <tr>

                            <th colspan="3" class="text-end">

                                Neto:

                            </th>

                            <th class="text-end text-primary">

                                $<?= number_format(
                                    $totalIngresos - $totalEgresos,
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </th>

                            <th></th>

                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

</div>

</div>

</body>

</html>