
```php
<?php

session_start();

require_once "../config/conexion.php";

$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| VALIDAR CLIENTE
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["cliente_id"])) {

    header("Location: ../clientes/login.php");
    exit;
}

$cliente_id = (int)$_SESSION["cliente_id"];


/*
|--------------------------------------------------------------------------
| OBTENER ID DEL PEDIDO
|--------------------------------------------------------------------------
*/

$pedido_id = isset($_GET["pedido"])
    ? (int)$_GET["pedido"]
    : (int)($_SESSION["pedido_id"] ?? 0);


if ($pedido_id <= 0) {

    header("Location: ../tienda/index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER PEDIDO
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        id,
        cliente_id,
        fecha,
        total,
        estado,
        metodo_pago,
        sipago_id
    FROM pedidos
    WHERE id = ?
      AND cliente_id = ?
    LIMIT 1
");

$stmt->execute([
    $pedido_id,
    $cliente_id
]);

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$pedido) {

    header("Location: ../tienda/index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER CLIENTE
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        id,
        dni,
        apellido,
        nombre,
        telefono,
        direccion,
        email
    FROM clientes
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $cliente_id
]);

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$cliente) {

    header("Location: ../clientes/login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER DETALLE DEL PEDIDO
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        pd.producto_id,
        pd.cantidad,
        pd.precio,
        pd.subtotal,
        pr.codigo,
        pr.nombre
    FROM pedido_detalle pd
    INNER JOIN productos pr
        ON pr.id = pd.producto_id
    WHERE pd.pedido_id = ?
    ORDER BY pd.id ASC
");

$stmt->execute([
    $pedido_id
]);

$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ESTADO
|--------------------------------------------------------------------------
*/

$estado = strtoupper(
    trim(
        (string)$pedido["estado"]
    )
);

$estadoPagado = ($estado === "PAGADO");


/*
|--------------------------------------------------------------------------
| LIMPIAR DATOS TEMPORALES
|--------------------------------------------------------------------------
|
| No eliminamos cliente_id porque la sesión del cliente
| debe continuar activa.
|
*/

unset($_SESSION["carrito"]);
unset($_SESSION["pedido_id"]);
unset($_SESSION["pedido_total"]);
unset($_SESSION["sipago_uuid"]);
unset($_SESSION["sipago_checkout"]);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>
Compra realizada - Tienda Urqui
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<style>

body {
    background: #f4f6f9;
}

.icono-estado {
    font-size: 70px;
}

.numero-pedido {
    font-size: 24px;
    font-weight: 700;
}

.total {
    font-size: 28px;
    font-weight: 700;
    color: #198754;
}

.tabla-detalle th,
.tabla-detalle td {
    vertical-align: middle;
}

@media (max-width: 576px) {

    .container {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }

    .card-body {
        padding: 20px !important;
    }

    .icono-estado {
        font-size: 55px;
    }

    .numero-pedido {
        font-size: 21px;
    }

    .total {
        font-size: 24px;
    }

}

</style>

</head>


<body>


<div class="container py-5">


<div class="row justify-content-center">


<div class="col-12 col-lg-9">


<div class="card shadow-sm border-0">


<div class="card-body p-4">


<!--
|--------------------------------------------------------------------------
| CABECERA
|--------------------------------------------------------------------------
-->

<div class="text-center mb-4">


<?php if ($estadoPagado): ?>

<i
    class="bi bi-check-circle-fill text-success icono-estado"
></i>


<h2 class="mt-3 text-success">

¡Pago realizado correctamente!

</h2>


<p class="text-muted mb-0">

Tu compra fue confirmada correctamente.

</p>


<?php else: ?>

<i
    class="bi bi-clock-history text-warning icono-estado"
></i>


<h2 class="mt-3 text-warning">

Pedido recibido

</h2>


<p class="text-muted mb-0">

El pedido fue registrado y está pendiente
de confirmación del pago.

</p>

<?php endif; ?>


</div>


<!--
|--------------------------------------------------------------------------
| NÚMERO DE PEDIDO
|--------------------------------------------------------------------------
-->

<div class="alert alert-light border text-center">


<div class="text-muted">

Número de pedido

</div>


<div class="numero-pedido">

#<?= (int)$pedido["id"] ?>

</div>


<?php if (!empty($pedido["sipago_id"])): ?>

<div class="small text-muted mt-2">

ID SiPago:

<strong>

<?= htmlspecialchars(
    $pedido["sipago_id"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</strong>

</div>

<?php endif; ?>


</div>


<!--
|--------------------------------------------------------------------------
| DATOS DEL CLIENTE
|--------------------------------------------------------------------------
-->

<div class="card border mb-4">


<div class="card-header bg-light">

<strong>

<i class="bi bi-person-check"></i>

Datos del cliente

</strong>

</div>


<div class="card-body">


<div class="row">


<div class="col-md-6 mb-3">

<strong>

Apellido y nombre

</strong>

<div>

<?= htmlspecialchars(
    $cliente["apellido"] . " " . $cliente["nombre"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="col-md-6 mb-3">

<strong>

DNI

</strong>

<div>

<?= htmlspecialchars(
    $cliente["dni"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="col-md-6 mb-3">

<strong>

Email

</strong>

<div>

<?= htmlspecialchars(
    $cliente["email"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="col-md-6 mb-3">

<strong>

Teléfono

</strong>

<div>

<?= htmlspecialchars(
    $cliente["telefono"] ?: "No informado",
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="col-12">

<strong>

Dirección

</strong>

<div>

<?= htmlspecialchars(
    $cliente["direccion"] ?: "No informada",
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


</div>


</div>

</div>


<!--
|--------------------------------------------------------------------------
| INFORMACIÓN DEL PEDIDO
|--------------------------------------------------------------------------
-->

<div class="row mb-4">


<div class="col-md-4 mb-3">


<div class="card border h-100">


<div class="card-body">


<div class="text-muted small">

Fecha

</div>


<strong>

<?= htmlspecialchars(
    date(
        "d/m/Y H:i",
        strtotime($pedido["fecha"])
    ),
    ENT_QUOTES,
    "UTF-8"
) ?>

</strong>


</div>

</div>


</div>


<div class="col-md-4 mb-3">


<div class="card border h-100">


<div class="card-body">


<div class="text-muted small">

Método de pago

</div>


<strong>

<?= htmlspecialchars(
    $pedido["metodo_pago"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</strong>


</div>

</div>


</div>


<div class="col-md-4 mb-3">


<div class="card border h-100">


<div class="card-body">


<div class="text-muted small">

Estado

</div>


<?php if ($estado === "PAGADO"): ?>

<span class="badge bg-success">

Pagado

</span>


<?php elseif ($estado === "RECHAZADO"): ?>

<span class="badge bg-danger">

Rechazado

</span>


<?php elseif ($estado === "CANCELADO"): ?>

<span class="badge bg-secondary">

Cancelado

</span>


<?php else: ?>

<span class="badge bg-warning text-dark">

Pendiente

</span>

<?php endif; ?>


</div>

</div>


</div>


</div>


<!--
|--------------------------------------------------------------------------
| DETALLE DE LA COMPRA
|--------------------------------------------------------------------------
-->

<h5 class="mb-3">

<i class="bi bi-bag-check"></i>

Detalle de la compra

</h5>


<div class="table-responsive">


<table class="table table-bordered tabla-detalle">


<thead class="table-light">

<tr>

<th>

Producto

</th>

<th class="text-center">

Cantidad

</th>

<th class="text-end">

Precio

</th>

<th class="text-end">

Subtotal

</th>

</tr>

</thead>


<tbody>


<?php if (!empty($detalles)): ?>


<?php foreach ($detalles as $detalle): ?>


<tr>


<td>


<div class="fw-bold">

<?= htmlspecialchars(
    $detalle["nombre"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>


<?php if (!empty($detalle["codigo"])): ?>

<small class="text-muted">

Código:

<?= htmlspecialchars(
    $detalle["codigo"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</small>

<?php endif; ?>


</td>


<td class="text-center">

<?= (int)$detalle["cantidad"] ?>

</td>


<td class="text-end">

$

<?= number_format(
    (float)$detalle["precio"],
    2,
    ",",
    "."
) ?>

</td>


<td class="text-end fw-bold">

$

<?= number_format(
    (float)$detalle["subtotal"],
    2,
    ",",
    "."
) ?>

</td>


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td
    colspan="4"
    class="text-center text-muted"
>

No hay productos registrados.

</td>

</tr>


<?php endif; ?>


</tbody>


<tfoot>


<tr>

<th
    colspan="3"
    class="text-end"
>

TOTAL

</th>


<th class="text-end total">

$

<?= number_format(
    (float)$pedido["total"],
    2,
    ",",
    "."
) ?>

</th>

</tr>


</tfoot>


</table>


</div>


<!--
|--------------------------------------------------------------------------
| MENSAJE DE ESTADO
|--------------------------------------------------------------------------
-->

<?php if ($estadoPagado): ?>


<div class="alert alert-success mt-4">


<i class="bi bi-check-circle"></i>


<strong>

Pago confirmado.

</strong>


<div class="mt-1">

Tu pedido fue registrado correctamente
y el stock correspondiente fue actualizado.

</div>


</div>


<?php elseif ($estado === "RECHAZADO"): ?>


<div class="alert alert-danger mt-4">


<i class="bi bi-x-circle"></i>


<strong>

Pago rechazado.

</strong>


<div class="mt-1">

El pago no fue aprobado.
No se descontó stock.

</div>


</div>


<?php elseif ($estado === "CANCELADO"): ?>


<div class="alert alert-secondary mt-4">


<i class="bi bi-x-circle"></i>


<strong>

Pago cancelado.

</strong>


<div class="mt-1">

El pago fue cancelado.
No se descontó stock.

</div>


</div>


<?php else: ?>


<div class="alert alert-warning mt-4">


<i class="bi bi-info-circle"></i>


<strong>

Pago pendiente.

</strong>


<div class="mt-1">

El pedido fue registrado, pero todavía no
recibimos la confirmación definitiva del pago.

</div>


</div>


<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| BOTONES
|--------------------------------------------------------------------------
-->

<div
    class="d-flex justify-content-center flex-wrap gap-2 mt-4"
>


<a
    href="../tienda/index.php"
    class="btn btn-primary"
>

<i class="bi bi-shop"></i>

Volver a la tienda

</a>


<a
    href="../clientes/pedidos.php"
    class="btn btn-outline-secondary"
>

<i class="bi bi-receipt"></i>

Mis pedidos

</a>


</div>


</div>

</div>


</div>

</div>


</div>


</body>

</html>
```
