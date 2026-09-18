<?php

session_start();

require_once "../config/conexion.php";


/*
|--------------------------------------------------------------------------
| OBTENER PEDIDO
|--------------------------------------------------------------------------
*/

$pedido_id = isset($_SESSION['pedido_id'])
    ? (int)$_SESSION['pedido_id']
    : 0;


if ($pedido_id <= 0) {

    header("Location: ../tienda/index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CONEXIÓN
|--------------------------------------------------------------------------
*/

$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| OBTENER PEDIDO
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.id,
        p.fecha,
        p.total,
        p.estado,
        p.metodo_pago,
        c.nombre,
        c.apellido
    FROM pedidos p
    INNER JOIN clientes c
        ON c.id = p.cliente_id
    WHERE p.id = ?
    LIMIT 1
");

$stmt->execute([$pedido_id]);

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$pedido) {

    unset($_SESSION['pedido_id']);

    header("Location: ../tienda/index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER DETALLE
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        pd.cantidad,
        pd.precio,
        pd.subtotal,
        pr.nombre
    FROM pedido_detalle pd
    INNER JOIN productos pr
        ON pr.id = pd.producto_id
    WHERE pd.pedido_id = ?
    ORDER BY pd.id ASC
");

$stmt->execute([$pedido_id]);

$detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| LIMPIAR SESIÓN
|--------------------------------------------------------------------------
*/

unset($_SESSION['pedido_id']);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Compra realizada - Stock PRO</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


</head>


<body class="bg-light">


<div class="container py-5">


<div class="row justify-content-center">


<div class="col-lg-8">


<div class="card shadow border-0">


<div class="card-header bg-success text-white text-center py-4">

<i
    class="bi bi-check-circle-fill"
    style="font-size:60px;"
></i>


<h2 class="mt-2 mb-0">

¡Compra realizada!

</h2>

</div>


<div class="card-body p-4">


<div class="text-center mb-4">

<h4>

Gracias por tu compra,
<?= htmlspecialchars(
    trim($pedido['nombre'] . ' ' . $pedido['apellido'])
) ?>

</h4>


<p class="text-muted mb-0">

Tu pedido fue registrado correctamente.

</p>

</div>


<div class="row g-3 mb-4">


<div class="col-md-4">

<div class="border rounded p-3 text-center">

<div class="text-muted">

Pedido

</div>

<div class="fs-4 fw-bold">

#<?= (int)$pedido['id'] ?>

</div>

</div>

</div>


<div class="col-md-4">

<div class="border rounded p-3 text-center">

<div class="text-muted">

Estado

</div>

<div class="fw-bold text-warning">

<?= htmlspecialchars($pedido['estado']) ?>

</div>

</div>

</div>


<div class="col-md-4">

<div class="border rounded p-3 text-center">

<div class="text-muted">

Forma de pago

</div>

<div class="fw-bold">

<?= htmlspecialchars($pedido['metodo_pago']) ?>

</div>

</div>

</div>


</div>


<h5 class="mb-3">

Detalle del pedido

</h5>


<div class="table-responsive">


<table class="table table-bordered align-middle">


<thead class="table-light">

<tr>

<th>Producto</th>

<th class="text-center">Cantidad</th>

<th class="text-end">Precio</th>

<th class="text-end">Subtotal</th>

</tr>

</thead>


<tbody>


<?php foreach ($detalle as $item): ?>


<tr>

<td>

<?= htmlspecialchars($item['nombre']) ?>

</td>


<td class="text-center">

<?= (int)$item['cantidad'] ?>

</td>


<td class="text-end">

$

<?= number_format(
    (float)$item['precio'],
    2,
    ",",
    "."
) ?>

</td>


<td class="text-end">

$

<?= number_format(
    (float)$item['subtotal'],
    2,
    ",",
    "."
) ?>

</td>

</tr>


<?php endforeach; ?>


</tbody>


<tfoot>

<tr>

<th colspan="3" class="text-end">

TOTAL

</th>

<th class="text-end text-success fs-4">

$

<?= number_format(
    (float)$pedido['total'],
    2,
    ",",
    "."
) ?>

</th>

</tr>

</tfoot>


</table>

</div>


<div class="alert alert-info mt-4">

<i class="bi bi-info-circle"></i>

Tu pedido quedó registrado como

<strong>
<?= htmlspecialchars($pedido['estado']) ?>
</strong>.

Será procesado desde Stock PRO.

</div>


<div class="d-flex justify-content-center gap-2 flex-wrap mt-4">


<a
    href="../clientes/mis_pedidos.php"
    class="btn btn-primary"
>

<i class="bi bi-receipt"></i>

Ver mis pedidos

</a>


<a
    href="../tienda/index.php"
    class="btn btn-success"
>

<i class="bi bi-shop"></i>

Seguir comprando

</a>


</div>


</div>

</div>


</div>

</div>


</div>


</body>

</html>