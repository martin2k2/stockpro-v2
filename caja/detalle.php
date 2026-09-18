<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| ID CAJA
|--------------------------------------------------------------------------
*/

$id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if ($id <= 0) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| OBTENER CAJA
|--------------------------------------------------------------------------
*/

$stmt = $conexion->prepare("
    SELECT
        c.*,
        u.nombre AS usuario
    FROM cajas c

    INNER JOIN usuarios_stock u
        ON u.id = c.usuario_id

    WHERE c.id = ?

    LIMIT 1
");

$stmt->execute([
    $id
]);

$caja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$caja) {

    die("Caja inexistente.");

}


/*
|--------------------------------------------------------------------------
| DATOS DE FECHA Y USUARIO DE LA CAJA
|--------------------------------------------------------------------------
*/

$usuarioCajaId = (int)$caja["usuario_id"];

$fechaApertura = $caja["fecha_apertura"];

$fechaCierre = $caja["fecha_cierre"];


/*
|--------------------------------------------------------------------------
| VENTAS DE LA CAJA
|--------------------------------------------------------------------------
|
| Algunas ventas antiguas pueden no tener caja_id.
| Por eso se buscan:
|
| 1. Por caja_id cuando existe.
| 2. Si caja_id no está asignado, por usuario y período de la caja.
|
*/

$sqlVentas = "
    SELECT
        v.id,
        v.fecha,
        v.total,
        v.forma_pago,
        v.estado,

        CONCAT(
            COALESCE(cl.apellido, ''),
            ' ',
            COALESCE(cl.nombre, '')
        ) AS cliente

    FROM ventas v

    LEFT JOIN clientes cl
        ON cl.id = v.cliente_id

    WHERE
        (
            v.caja_id = :caja_id
        )

        OR
        (
            (
                v.caja_id IS NULL
                OR v.caja_id = 0
            )

            AND v.usuario_id = :usuario_id

            AND v.fecha >= :fecha_apertura
";


if (!empty($fechaCierre)) {

    $sqlVentas .= "
            AND v.fecha <= :fecha_cierre
    ";

} else {

    $sqlVentas .= "
            AND v.fecha >= :fecha_apertura_abierta
    ";

}


$sqlVentas .= "
        )

    AND
    (
        v.estado IS NULL
        OR UPPER(TRIM(v.estado)) <> 'ANULADA'
    )

    ORDER BY
        v.fecha DESC,
        v.id DESC
";


$stmt = $conexion->prepare($sqlVentas);


$parametrosVentas = [

    ":caja_id" => $id,

    ":usuario_id" => $usuarioCajaId,

    ":fecha_apertura" => $fechaApertura

];


if (!empty($fechaCierre)) {

    $parametrosVentas[
        ":fecha_cierre"
    ] = $fechaCierre;

} else {

    $parametrosVentas[
        ":fecha_apertura_abierta"
    ] = $fechaApertura;

}


$stmt->execute(
    $parametrosVentas
);

$ventas = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| TOTALES POR FORMA DE PAGO
|--------------------------------------------------------------------------
*/

$totalesPago = [

    "EFECTIVO" => 0,

    "TRANSFERENCIA" => 0,

    "DEBITO" => 0,

    "DÉBITO" => 0,

    "CREDITO" => 0,

    "CRÉDITO" => 0,

    "CUENTA CORRIENTE" => 0,

    "CUENTA" => 0

];


$totalVentas = 0;


foreach ($ventas as $venta) {


    $formaPago = strtoupper(
        trim(
            (string)(
                $venta["forma_pago"] ?? ""
            )
        )
    );


    $totalVenta = (float)(
        $venta["total"] ?? 0
    );


    $totalVentas += $totalVenta;


    if (
        isset(
            $totalesPago[$formaPago]
        )
    ) {

        $totalesPago[$formaPago] +=
            $totalVenta;

    }

}


/*
|--------------------------------------------------------------------------
| NORMALIZAR DÉBITO Y CRÉDITO
|--------------------------------------------------------------------------
*/

$ventasDebito =
    $totalesPago["DEBITO"]
    +
    $totalesPago["DÉBITO"];


$ventasCredito =
    $totalesPago["CREDITO"]
    +
    $totalesPago["CRÉDITO"];


$ventasCuentaCorriente =
    $totalesPago["CUENTA CORRIENTE"]
    +
    $totalesPago["CUENTA"];


/*
|--------------------------------------------------------------------------
| MOVIMIENTOS DE CAJA
|--------------------------------------------------------------------------
*/

$stmt = $conexion->prepare("
    SELECT
        mc.id,
        mc.tipo,
        mc.importe,
        mc.concepto,
        mc.fecha,
        u.nombre AS usuario

    FROM movimientos_caja mc

    LEFT JOIN usuarios_stock u
        ON u.id = mc.usuario_id

    WHERE mc.caja_id = ?

    ORDER BY
        mc.fecha DESC,
        mc.id DESC
");

$stmt->execute([
    $id
]);

$movimientosCaja = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| TOTALES MOVIMIENTOS
|--------------------------------------------------------------------------
*/

$totalIngresosMovimientos = 0;

$totalEgresosMovimientos = 0;


foreach ($movimientosCaja as $movimiento) {


    $tipo = strtoupper(
        trim(
            (string)(
                $movimiento["tipo"] ?? ""
            )
        )
    );


    $importe = (float)(
        $movimiento["importe"] ?? 0
    );


    if ($tipo === "INGRESO") {

        $totalIngresosMovimientos +=
            $importe;

    }


    if ($tipo === "EGRESO") {

        $totalEgresosMovimientos +=
            $importe;

    }

}


/*
|--------------------------------------------------------------------------
| VALORES DE CAJA
|--------------------------------------------------------------------------
*/

$saldoInicial = (float)(
    $caja["saldo_inicial"] ?? 0
);


$ventasEfectivo =
    $totalesPago["EFECTIVO"];


/*
|--------------------------------------------------------------------------
| EFECTIVO ESPERADO
|--------------------------------------------------------------------------
*/

$efectivoEsperado =

    $saldoInicial

    + $ventasEfectivo

    + $totalIngresosMovimientos

    - $totalEgresosMovimientos;


/*
|--------------------------------------------------------------------------
| ESTADO
|--------------------------------------------------------------------------
*/

$estadoCaja = strtoupper(
    trim(
        (string)(
            $caja["estado"] ?? ""
        )
    )
);


$cajaAbierta =
    $estadoCaja === "ABIERTA";


$diferencia = (float)(
    $caja["diferencia"] ?? 0
);

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Detalle Caja - Stock PRO</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="../assets/css/estilos.css"
>

<style>

.resumen-item {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    padding: 9px 0;

    border-bottom: 1px solid #eee;

}

.resumen-item:last-child {

    border-bottom: 0;

}

.importe {

    font-weight: 600;

    white-space: nowrap;

}

.card-header h5 {

    margin: 0;

}

.tabla-ventas td,
.tabla-ventas th {

    vertical-align: middle;

}

.movimiento-ingreso {

    color: #198754;

    font-weight: 600;

}

.movimiento-egreso {

    color: #dc3545;

    font-weight: 600;

}

.total-destacado {

    font-size: 1.25rem;

    font-weight: 700;

}

.card-resumen {

    height: 100%;

}

@media (max-width: 576px) {

    .resumen-item {

        flex-direction: column;

        align-items: flex-start;

        gap: 3px;

    }

}

</style>

</head>

<body>

<div class="content">

<div class="container-fluid">


<div
    class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"
>

    <h3 class="mb-0">

        <i class="bi bi-cash-stack"></i>

        Detalle de Caja #<?= $id ?>

    </h3>


    <a
        href="index.php"
        class="btn btn-secondary"
    >

        <i class="bi bi-arrow-left"></i>

        Volver

    </a>

</div>


<div class="row g-3 mb-4">


<div class="col-lg-3 col-md-6">

<div class="card shadow border-0 card-resumen">

<div class="card-body">

<div class="text-muted small">

Ventas totales

</div>

<div class="total-destacado">

$

<?= number_format(
    $totalVentas,
    2,
    ",",
    "."
) ?>

</div>

</div>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="card shadow border-0 card-resumen">

<div class="card-body">

<div class="text-muted small">

Ventas en efectivo

</div>

<div class="total-destacado text-success">

$

<?= number_format(
    $ventasEfectivo,
    2,
    ",",
    "."
) ?>

</div>

</div>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="card shadow border-0 card-resumen">

<div class="card-body">

<div class="text-muted small">

Ingresos manuales

</div>

<div class="total-destacado text-success">

$

<?= number_format(
    $totalIngresosMovimientos,
    2,
    ",",
    "."
) ?>

</div>

</div>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="card shadow border-0 card-resumen">

<div class="card-body">

<div class="text-muted small">

Egresos

</div>

<div class="total-destacado text-danger">

$

<?= number_format(
    $totalEgresosMovimientos,
    2,
    ",",
    "."
) ?>

</div>

</div>

</div>

</div>


</div>


<div class="row g-4">


<div class="col-lg-4">


<div class="card shadow border-0 mb-4">

<div class="card-header bg-primary text-white">

<h5>

<i class="bi bi-info-circle"></i>

Información de Caja

</h5>

</div>


<div class="card-body">


<div class="resumen-item">

<span>Usuario</span>

<strong>

<?= htmlspecialchars(
    $caja["usuario"] ?? "-"
) ?>

</strong>

</div>


<div class="resumen-item">

<span>Apertura</span>

<strong>

<?= !empty($caja["fecha_apertura"])
    ? date(
        "d/m/Y H:i",
        strtotime(
            $caja["fecha_apertura"]
        )
    )
    : "-"
?>

</strong>

</div>


<div class="resumen-item">

<span>Cierre</span>

<strong>

<?= !empty($caja["fecha_cierre"])
    ? date(
        "d/m/Y H:i",
        strtotime(
            $caja["fecha_cierre"]
        )
    )
    : "-"
?>

</strong>

</div>


<div class="resumen-item">

<span>Estado</span>

<?php if ($cajaAbierta): ?>

<span class="badge bg-success">

ABIERTA

</span>

<?php else: ?>

<span class="badge bg-secondary">

CERRADA

</span>

<?php endif; ?>

</div>


<hr>


<div class="resumen-item">

<span>Saldo inicial</span>

<span class="importe">

$

<?= number_format(
    $saldoInicial,
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="resumen-item">

<span>Ventas</span>

<span class="importe">

$

<?= number_format(
    $totalVentas,
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="resumen-item">

<span>Ingresos</span>

<span class="importe text-success">

$

<?= number_format(
    $totalIngresosMovimientos,
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="resumen-item">

<span>Egresos</span>

<span class="importe text-danger">

$

<?= number_format(
    $totalEgresosMovimientos,
    2,
    ",",
    "."
) ?>

</span>

</div>


<hr>


<div class="resumen-item">

<strong>

Efectivo esperado

</strong>

<strong class="text-success">

$

<?= number_format(
    $efectivoEsperado,
    2,
    ",",
    "."
) ?>

</strong>

</div>


<?php if (!$cajaAbierta): ?>


<div class="resumen-item">

<span>Saldo final</span>

<span class="importe">

$

<?= number_format(
    (float)(
        $caja["saldo_final"] ?? 0
    ),
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="resumen-item">

<span>Saldo real</span>

<span class="importe">

$

<?= number_format(
    (float)(
        $caja["saldo_real"] ?? 0
    ),
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="resumen-item">

<span>Diferencia</span>

<span
    class="importe <?= $diferencia > 0
        ? 'text-success'
        : (
            $diferencia < 0
                ? 'text-danger'
                : 'text-muted'
        )
    ?>"
>

$

<?= number_format(
    $diferencia,
    2,
    ",",
    "."
) ?>

</span>

</div>


<?php endif; ?>


</div>

</div>


<div class="card shadow border-0 mb-4">


<div class="card-header bg-dark text-white">

<h5>

<i class="bi bi-credit-card"></i>

Ventas por forma de pago

</h5>

</div>


<div class="card-body">


<div class="resumen-item">

<span>

Efectivo

</span>

<span class="importe">

$

<?= number_format(
    $totalesPago["EFECTIVO"],
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="resumen-item">

<span>

Transferencia

</span>

<span class="importe">

$

<?= number_format(
    $totalesPago["TRANSFERENCIA"],
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="resumen-item">

<span>

Débito

</span>

<span class="importe">

$

<?= number_format(
    $ventasDebito,
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="resumen-item">

<span>

Crédito

</span>

<span class="importe">

$

<?= number_format(
    $ventasCredito,
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="resumen-item">

<span>

Cuenta corriente

</span>

<span class="importe">

$

<?= number_format(
    $ventasCuentaCorriente,
    2,
    ",",
    "."
) ?>

</span>

</div>


<hr>


<div class="resumen-item">

<strong>

Total ventas

</strong>

<strong>

$

<?= number_format(
    $totalVentas,
    2,
    ",",
    "."
) ?>

</strong>

</div>


</div>

</div>


</div>


<div class="col-lg-8">


<div class="card shadow border-0 mb-4">


<div class="card-header bg-success text-white">

<h5>

<i class="bi bi-receipt"></i>

Ventas realizadas

</h5>

</div>


<div class="card-body">


<div class="table-responsive">


<table
    class="table table-bordered table-hover tabla-ventas"
>


<thead class="table-dark">

<tr>

<th>ID</th>

<th>Fecha</th>

<th>Cliente</th>

<th>Pago</th>

<th>Estado</th>

<th>Total</th>

</tr>

</thead>


<tbody>


<?php if (empty($ventas)): ?>


<tr>

<td
    colspan="6"
    class="text-center text-muted py-4"
>

<i
    class="bi bi-receipt fs-3 d-block mb-2"
></i>

No hay ventas registradas
en esta caja.

</td>

</tr>


<?php else: ?>


<?php foreach ($ventas as $v): ?>


<?php

$pago = strtoupper(
    trim(
        (string)(
            $v["forma_pago"] ?? ""
        )
    )
);


$estadoVenta = strtoupper(
    trim(
        (string)(
            $v["estado"] ?? ""
        )
    )
);

?>


<tr>


<td>

<?= (int)$v["id"] ?>

</td>


<td>

<?= !empty($v["fecha"])
    ? date(
        "d/m/Y H:i",
        strtotime(
            $v["fecha"]
        )
    )
    : "-"
?>

</td>


<td>

<?= htmlspecialchars(
    trim(
        $v["cliente"] ?? ""
    ) ?: "Consumidor Final"
) ?>

</td>


<td>


<?php if ($pago === "EFECTIVO"): ?>

<span class="badge bg-success">

EFECTIVO

</span>


<?php elseif ($pago === "TRANSFERENCIA"): ?>

<span class="badge bg-primary">

TRANSFERENCIA

</span>


<?php elseif (
    $pago === "DEBITO"
    ||
    $pago === "DÉBITO"
): ?>

<span class="badge bg-info text-dark">

DÉBITO

</span>


<?php elseif (
    $pago === "CREDITO"
    ||
    $pago === "CRÉDITO"
): ?>

<span class="badge bg-warning text-dark">

CRÉDITO

</span>


<?php elseif (
    $pago === "CUENTA CORRIENTE"
    ||
    $pago === "CUENTA"
): ?>

<span class="badge bg-secondary">

CUENTA CORRIENTE

</span>


<?php else: ?>

<span class="badge bg-dark">

<?= htmlspecialchars(
    $v["forma_pago"] ?? "-"
) ?>

</span>

<?php endif; ?>


</td>


<td>


<?php if ($estadoVenta === "ANULADA"): ?>

<span class="badge bg-danger">

ANULADA

</span>

<?php else: ?>

<span class="badge bg-success">

PAGADA

</span>

<?php endif; ?>


</td>


<td>

<strong>

$

<?= number_format(
    (float)$v["total"],
    2,
    ",",
    "."
) ?>

</strong>

</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>


<?php if (!empty($ventas)): ?>


<tfoot>

<tr class="table-light">

<th
    colspan="5"
    class="text-end"
>

TOTAL

</th>

<th>

$

<?= number_format(
    $totalVentas,
    2,
    ",",
    "."
) ?>

</th>

</tr>

</tfoot>


<?php endif; ?>


</table>


</div>

</div>

</div>


<div class="card shadow border-0">


<div class="card-header bg-warning">

<h5 class="mb-0">

<i class="bi bi-arrow-left-right"></i>

Ingresos y egresos

</h5>

</div>


<div class="card-body">


<div class="table-responsive">


<table class="table table-bordered table-hover">


<thead class="table-dark">

<tr>

<th>ID</th>

<th>Fecha</th>

<th>Tipo</th>

<th>Concepto</th>

<th>Usuario</th>

<th>Importe</th>

</tr>

</thead>


<tbody>


<?php if (empty($movimientosCaja)): ?>


<tr>

<td
    colspan="6"
    class="text-center text-muted py-4"
>

<i
    class="bi bi-cash-stack fs-3 d-block mb-2"
></i>

No hay movimientos manuales
registrados en esta caja.

</td>

</tr>


<?php else: ?>


<?php foreach ($movimientosCaja as $m): ?>


<?php

$tipoMovimiento = strtoupper(
    trim(
        (string)(
            $m["tipo"] ?? ""
        )
    )
);


$esIngreso =
    $tipoMovimiento === "INGRESO";

?>


<tr>


<td>

<?= (int)$m["id"] ?>

</td>


<td>

<?= !empty($m["fecha"])
    ? date(
        "d/m/Y H:i",
        strtotime(
            $m["fecha"]
        )
    )
    : "-"
?>

</td>


<td>


<?php if ($esIngreso): ?>

<span class="badge bg-success">

INGRESO

</span>

<?php else: ?>

<span class="badge bg-danger">

EGRESO

</span>

<?php endif; ?>


</td>


<td>

<?= htmlspecialchars(
    $m["concepto"] ?? "-"
) ?>

</td>


<td>

<?= htmlspecialchars(
    $m["usuario"] ?? "-"
) ?>

</td>


<td>


<strong
    class="<?= $esIngreso
        ? 'movimiento-ingreso'
        : 'movimiento-egreso'
    ?>"
>

<?= $esIngreso ? '+' : '-' ?>

$

<?= number_format(
    (float)$m["importe"],
    2,
    ",",
    "."
) ?>

</strong>


</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>


<?php if (!empty($movimientosCaja)): ?>


<tfoot>


<tr class="table-light">

<th
    colspan="5"
    class="text-end"
>

Total ingresos

</th>

<th class="text-success">

+

$

<?= number_format(
    $totalIngresosMovimientos,
    2,
    ",",
    "."
) ?>

</th>

</tr>


<tr class="table-light">

<th
    colspan="5"
    class="text-end"
>

Total egresos

</th>

<th class="text-danger">

-

$

<?= number_format(
    $totalEgresosMovimientos,
    2,
    ",",
    "."
) ?>

</th>

</tr>


</tfoot>


<?php endif; ?>


</table>


</div>

</div>

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