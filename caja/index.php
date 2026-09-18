<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$usuario_id = (int)($_SESSION["usuario_id"] ?? 0);

//---------------------------------------------------------
// Todas las cajas
//---------------------------------------------------------

$stmt = $conexion->query("
    SELECT
        c.*,
        u.nombre AS usuario
    FROM cajas c
    INNER JOIN usuarios_stock u
        ON u.id = c.usuario_id
    ORDER BY c.id DESC
");

$cajas = $stmt->fetchAll(PDO::FETCH_ASSOC);

//---------------------------------------------------------
// Caja abierta del usuario actual
//---------------------------------------------------------

$stmt = $conexion->prepare("
    SELECT
        c.*,
        u.nombre AS usuario
    FROM cajas c
    INNER JOIN usuarios_stock u
        ON u.id = c.usuario_id
    WHERE c.estado = 'ABIERTA'
      AND c.usuario_id = ?
    LIMIT 1
");

$stmt->execute([$usuario_id]);

$cajaAbierta = $stmt->fetch(PDO::FETCH_ASSOC);

//---------------------------------------------------------
// Resumen de caja abierta
//---------------------------------------------------------

$ventasEfectivo = 0;
$ventasTotales = 0;
$cantidadVentas = 0;
$saldoEsperado = 0;

if ($cajaAbierta) {

    $stmt = $conexion->prepare("
        SELECT
            COUNT(*) AS cantidad,
            COALESCE(SUM(total), 0) AS total
        FROM ventas
        WHERE caja_id = ?
          AND (
              estado IS NULL
              OR UPPER(TRIM(estado)) <> 'ANULADA'
          )
    ");

    $stmt->execute([$cajaAbierta["id"]]);

    $resumenVentas = $stmt->fetch(PDO::FETCH_ASSOC);

    $cantidadVentas = (int)($resumenVentas["cantidad"] ?? 0);
    $ventasTotales = (float)($resumenVentas["total"] ?? 0);

    //-----------------------------------------------------
    // Ventas en efectivo
    //-----------------------------------------------------

    $stmt = $conexion->prepare("
        SELECT
            COALESCE(SUM(total), 0)
        FROM ventas
        WHERE caja_id = ?
          AND UPPER(TRIM(forma_pago)) = 'EFECTIVO'
          AND (
              estado IS NULL
              OR UPPER(TRIM(estado)) <> 'ANULADA'
          )
    ");

    $stmt->execute([$cajaAbierta["id"]]);

    $ventasEfectivo = (float)$stmt->fetchColumn();

    //-----------------------------------------------------
    // Saldo esperado
    //-----------------------------------------------------

    $saldoInicial = (float)($cajaAbierta["saldo_inicial"] ?? 0);
    $ingresos = (float)($cajaAbierta["ingresos"] ?? 0);
    $egresos = (float)($cajaAbierta["egresos"] ?? 0);

    $saldoEsperado =
        $saldoInicial
        + $ventasEfectivo
        + $ingresos
        - $egresos;
}

?>

<!DOCTYPE html>

<html lang="es">

<head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Caja - Stock PRO</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/estilos.css">

<style>

        .caja-resumen .card {
            height: 100%;
        }

        .caja-valor {
            font-size: 1.35rem;
            font-weight: 700;
        }

        .tabla-caja th,
        .tabla-caja td {
            vertical-align: middle;
        }

    @media (max-width: 768px) {

    .acciones-caja {
        width: 100%;
    }

    .acciones-caja a {
        flex: 1;
    }

    .tabla-caja {
        font-size: 0.85rem;
    }

}

</style>

</head>

<body>



<div class="content">

<div class="container-fluid">

<!--=====================================================
    ENCABEZADO
======================================================-->

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

    <div>

        <h3 class="mb-1">

            <i class="bi bi-cash-stack"></i>

            Caja

        </h3>

        

        <small class="text-muted">

            Gestión de cajas y arqueos

        </small>

        

    </div>
    <a
    href="../dashboard/index.php"
    class="btn btn-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver

</a>
    </a>


    <?php if (!$cajaAbierta): ?>

        <a
            href="abrir.php"
            class="btn btn-success"
        >

            <i class="bi bi-plus-circle"></i>

            Abrir Caja

        </a>

    

    <?php endif; ?>

</div>


<!--=====================================================
    CAJA ABIERTA
======================================================-->

<?php if ($cajaAbierta): ?>

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-success text-white">

        <div class="d-flex justify-content-between align-items-center">

            <strong>

                <i class="bi bi-unlock-fill"></i>

                Caja Abierta #<?= (int)$cajaAbierta["id"] ?>

            </strong>

            <span class="badge bg-light text-success">

                ABIERTA

            </span>

        </div>

    </div>

    <div class="card-body">

        <div class="row caja-resumen">

            <!-- SALDO INICIAL -->

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">

                <div class="card border-primary">

                    <div class="card-body">

                        <small class="text-muted">
                            Saldo inicial
                        </small>

                        <div class="caja-valor text-primary">

                            $<?= number_format(
                                (float)$cajaAbierta["saldo_inicial"],
                                2,
                                ",",
                                "."
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- VENTAS EFECTIVO -->

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">

                <div class="card border-success">

                    <div class="card-body">

                        <small class="text-muted">
                            Ventas efectivo
                        </small>

                        <div class="caja-valor text-success">

                            $<?= number_format(
                                $ventasEfectivo,
                                2,
                                ",",
                                "."
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- INGRESOS -->

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">

                <div class="card border-success">

                    <div class="card-body">

                        <small class="text-muted">
                            Ingresos
                        </small>

                        <div class="caja-valor text-success">

                            $<?= number_format(
                                (float)$cajaAbierta["ingresos"],
                                2,
                                ",",
                                "."
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- EGRESOS -->

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">

                <div class="card border-danger">

                    <div class="card-body">

                        <small class="text-muted">
                            Egresos
                        </small>

                        <div class="caja-valor text-danger">

                            $<?= number_format(
                                (float)$cajaAbierta["egresos"],
                                2,
                                ",",
                                "."
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- VENTAS -->

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">

                <div class="card border-info">

                    <div class="card-body">

                        <small class="text-muted">
                            Ventas totales
                        </small>

                        <div class="caja-valor text-info">

                            $<?= number_format(
                                $ventasTotales,
                                2,
                                ",",
                                "."
                            ) ?>

                        </div>

                        <small class="text-muted">

                            <?= $cantidadVentas ?> ventas

                        </small>

                    </div>

                </div>

            </div>


            <!-- SALDO ESPERADO -->

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">

                <div class="card border-dark">

                    <div class="card-body">

                        <small class="text-muted">
                            Saldo esperado
                        </small>

                        <div class="caja-valor text-dark">

                            $<?= number_format(
                                $saldoEsperado,
                                2,
                                ",",
                                "."
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ACCIONES -->

        <div class="d-flex gap-2 flex-wrap acciones-caja">

            <a
                href="detalle.php?id=<?= (int)$cajaAbierta["id"] ?>"
                class="btn btn-primary"
            >

                <i class="bi bi-eye"></i>

                Detalle

            </a>

            <a
                href="movimientos.php"
                class="btn btn-secondary"
            >

                <i class="bi bi-list-ul"></i>

                Movimientos

            </a>

            <a
                href="ingreso.php"
                class="btn btn-success"
            >

                <i class="bi bi-plus-circle"></i>

                Ingreso

            </a>

            <a
                href="egreso.php"
                class="btn btn-danger"
            >

                <i class="bi bi-dash-circle"></i>

                Egreso

            </a>

            <a
                href="cerrar.php"
                class="btn btn-dark ms-auto"
            >

                <i class="bi bi-lock-fill"></i>

                Cerrar Caja

            </a>

        </div>

    </div>

</div>

<?php else: ?>

<!--=====================================================
    SIN CAJA ABIERTA
======================================================-->

<div class="alert alert-warning shadow-sm">

    <div class="d-flex align-items-center">

        <i class="bi bi-exclamation-triangle fs-3 me-3"></i>

        <div>

            <strong>No hay una caja abierta.</strong>

            <div>

                Para realizar ventas presenciales debe abrir una caja.

            </div>

        </div>

    </div>

</div>

<?php endif; ?>


<!--=====================================================
    HISTORIAL DE CAJAS
======================================================-->

<div class="card shadow border-0">

    <div class="card-header">

        <strong>

            <i class="bi bi-clock-history"></i>

            Historial de Cajas

        </strong>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered table-hover align-middle tabla-caja">

                <thead class="table-dark">

                    <tr>

                        <th>ID</th>

                        <th>Apertura</th>

                        <th>Cierre</th>

                        <th>Usuario</th>

                        <th>Inicial</th>

                        <th>Ingresos</th>

                        <th>Egresos</th>

                        <th>Ventas</th>

                        <th>Final</th>

                        <th>Real</th>

                        <th>Diferencia</th>

                        <th>Estado</th>

                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($cajas)): ?>

                    <tr>

                        <td
                            colspan="13"
                            class="text-center text-muted py-5"
                        >

                            <i class="bi bi-cash-stack fs-1 d-block mb-2"></i>

                            No hay cajas registradas.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($cajas as $c): ?>

                        <?php

                        $estado = strtoupper(
                            trim((string)($c["estado"] ?? ""))
                        );

                        $diferencia = (float)(
                            $c["diferencia"] ?? 0
                        );

                        if ($diferencia > 0) {
                            $claseDiferencia = "text-success";
                        } elseif ($diferencia < 0) {
                            $claseDiferencia = "text-danger";
                        } else {
                            $claseDiferencia = "text-muted";
                        }

                        ?>

                        <tr>

                            <!-- ID -->

                            <td>

                                <?= (int)$c["id"] ?>

                            </td>


                            <!-- APERTURA -->

                            <td>

                                <?= !empty($c["fecha_apertura"])
                                    ? date(
                                        "d/m/Y H:i",
                                        strtotime($c["fecha_apertura"])
                                    )
                                    : "-"
                                ?>

                            </td>


                            <!-- CIERRE -->

                            <td>

                                <?= !empty($c["fecha_cierre"])
                                    ? date(
                                        "d/m/Y H:i",
                                        strtotime($c["fecha_cierre"])
                                    )
                                    : "-"
                                ?>

                            </td>


                            <!-- USUARIO -->

                            <td>

                                <?= htmlspecialchars(
                                    $c["usuario"] ?? "-"
                                ) ?>

                            </td>


                            <!-- INICIAL -->

                            <td>

                                $<?= number_format(
                                    (float)$c["saldo_inicial"],
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </td>


                            <!-- INGRESOS -->

                            <td class="text-success">

                                $<?= number_format(
                                    (float)$c["ingresos"],
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </td>


                            <!-- EGRESOS -->

                            <td class="text-danger">

                                $<?= number_format(
                                    (float)$c["egresos"],
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </td>


                            <!-- VENTAS -->

                            <td>

                                $<?= number_format(
                                    (float)$c["ventas"],
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </td>


                            <!-- FINAL -->

                            <td>

                                $<?= number_format(
                                    (float)$c["saldo_final"],
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </td>


                            <!-- REAL -->

                            <td>

                                $<?= number_format(
                                    (float)$c["saldo_real"],
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </td>


                            <!-- DIFERENCIA -->

                            <td>

                                <span
                                    class="<?= $claseDiferencia ?> fw-bold"
                                >

                                    $<?= number_format(
                                        $diferencia,
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </span>

                            </td>


                            <!-- ESTADO -->

                            <td>

                                <?php if ($estado === "ABIERTA"): ?>

                                    <span class="badge bg-success">

                                        <i class="bi bi-unlock"></i>

                                        ABIERTA

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">

                                        <i class="bi bi-lock"></i>

                                        CERRADA

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACCIONES -->

                            <td class="text-nowrap">

                                <a
                                    href="detalle.php?id=<?= (int)$c["id"] ?>"
                                    class="btn btn-primary btn-sm"
                                    title="Ver detalle"
                                >

                                    <i class="bi bi-eye"></i>

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>