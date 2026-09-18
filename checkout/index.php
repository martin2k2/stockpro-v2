<?php

session_start();

require_once "../config/conexion.php";


/*
|--------------------------------------------------------------------------
| VALIDAR CLIENTE
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["cliente_id"]) || (int)$_SESSION["cliente_id"] <= 0) {

    header("Location: ../clientes/login.php");
    exit;
}


$cliente_id = (int)$_SESSION["cliente_id"];


/*
|--------------------------------------------------------------------------
| VALIDAR CARRITO
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["carrito"])) {

    header("Location: ../carrito/index.php");
    exit;
}


$db = Conexion::conectar();


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
        email,
        estado
    FROM clientes
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$cliente_id]);

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$cliente) {

    unset($_SESSION["cliente_id"]);
    unset($_SESSION["cliente_nombre"]);
    unset($_SESSION["cliente_email"]);

    header("Location: ../clientes/login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR ESTADO DEL CLIENTE
|--------------------------------------------------------------------------
*/

$estado_cliente = strtoupper(
    trim((string)($cliente["estado"] ?? ""))
);


if (
    $estado_cliente === "INACTIVO" ||
    $estado_cliente === "0"
) {

    unset($_SESSION["cliente_id"]);
    unset($_SESSION["cliente_nombre"]);
    unset($_SESSION["cliente_email"]);

    header("Location: ../clientes/login.php?error=inactivo");
    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER PRODUCTOS DEL CARRITO
|--------------------------------------------------------------------------
*/

$total = 0;

$productos = [];


foreach ($_SESSION["carrito"] as $clave => $item) {


    /*
    |--------------------------------------------------------------------------
    | PRODUCTO CON VARIANTE
    |--------------------------------------------------------------------------
    */

    if (is_array($item)) {

        $producto_id = (int)(
            $item["producto_id"] ?? 0
        );

        $variante_id = (int)(
            $item["variante_id"] ?? 0
        );

        $cantidad = (int)(
            $item["cantidad"] ?? 0
        );

    } else {

        /*
        |--------------------------------------------------------------------------
        | COMPATIBILIDAD CON CARRITO ANTERIOR
        |--------------------------------------------------------------------------
        */

        $producto_id = (int)$clave;

        $variante_id = 0;

        $cantidad = (int)$item;

    }


    if (
        $producto_id <= 0 ||
        $cantidad <= 0
    ) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER PRODUCTO
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT
            id,
            codigo,
            nombre,
            precio_venta,
            stock
        FROM productos
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $producto_id
    ]);

    $p = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$p) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | DATOS DE VARIANTE
    |--------------------------------------------------------------------------
    */

    $talle = null;

    $color = null;

    $stockDisponible = (int)$p["stock"];

    $codigoVariante = null;


    if ($variante_id > 0) {


        $stmtVariante = $db->prepare("
            SELECT
                id,
                producto_id,
                talle,
                color,
                stock,
                codigo,
                activo
            FROM producto_variantes
            WHERE id = ?
              AND producto_id = ?
            LIMIT 1
        ");

        $stmtVariante->execute([

            $variante_id,

            $producto_id

        ]);

        $variante =
            $stmtVariante->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$variante) {

            continue;

        }


        if ((int)$variante["activo"] !== 1) {

            continue;

        }


        $talle =
            $variante["talle"] ?? null;


        $color =
            $variante["color"] ?? null;


        $stockDisponible =
            (int)$variante["stock"];


        $codigoVariante =
            $variante["codigo"] ?? null;

    }


    /*
    |--------------------------------------------------------------------------
    | CONTROL DE STOCK
    |--------------------------------------------------------------------------
    */

    if ($stockDisponible <= 0) {

        continue;

    }


    if ($cantidad > $stockDisponible) {

        $cantidad =
            $stockDisponible;

    }


    if ($cantidad <= 0) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | PRECIO
    |--------------------------------------------------------------------------
    */

    $precio =
        (float)$p["precio_venta"];


    $subtotal =
        $cantidad * $precio;


    /*
    |--------------------------------------------------------------------------
    | AGREGAR DATOS
    |--------------------------------------------------------------------------
    */

    $p["cantidad"] =
        $cantidad;


    $p["subtotal"] =
        $subtotal;


    $p["variante_id"] =
        $variante_id;


    $p["talle"] =
        $talle;


    $p["color"] =
        $color;


    $p["stock_disponible"] =
        $stockDisponible;


    $p["codigo_variante"] =
        $codigoVariante;


    $productos[] =
        $p;


    $total +=
        $subtotal;

}


/*
|--------------------------------------------------------------------------
| SI NO HAY PRODUCTOS DISPONIBLES
|--------------------------------------------------------------------------
*/

if (
    empty($productos) ||
    $total <= 0
) {

    unset($_SESSION["carrito"]);

    header("Location: ../carrito/index.php");

    exit;

}

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Checkout - Stock PRO</title>


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

.variante {
    margin-top: 5px;
}

.badge-variante {
    font-size: 12px;
}

</style>

</head>


<body class="bg-light">


<div class="container py-5">


<div class="row justify-content-center">


<div class="col-lg-9">


<div class="card shadow border-0">


<div class="card-header bg-success text-white">

<h3 class="mb-0">

<i class="bi bi-cart-check"></i>

Finalizar compra

</h3>

</div>


<div class="card-body p-4">


<!--
|--------------------------------------------------------------------------
| CLIENTE
|--------------------------------------------------------------------------
-->

<div class="card border-success mb-4">


<div class="card-header bg-success text-white">

<h5 class="mb-0">

<i class="bi bi-person-check"></i>

Cliente que realiza la compra

</h5>

</div>


<div class="card-body">


<div class="row">


<div class="col-md-6 mb-3">

<strong>Apellido y nombre</strong>

<div class="form-control bg-light">

<?= htmlspecialchars(
    $cliente["apellido"] .
    " " .
    $cliente["nombre"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="col-md-6 mb-3">

<strong>DNI</strong>

<div class="form-control bg-light">

<?= htmlspecialchars(
    $cliente["dni"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="col-md-6 mb-3">

<strong>Email</strong>

<div class="form-control bg-light">

<?= htmlspecialchars(
    $cliente["email"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="col-md-6 mb-3">

<strong>Teléfono</strong>

<div class="form-control bg-light">

<?= htmlspecialchars(
    $cliente["telefono"] ?: "No informado",
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="col-12">

<strong>Dirección de entrega</strong>

<div class="form-control bg-light">

<?= htmlspecialchars(
    $cliente["direccion"] ?: "No informada",
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


</div>


<div class="alert alert-info mt-3 mb-0">

<i class="bi bi-info-circle"></i>

La compra se registrará automáticamente a nombre de este cliente.

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


<table class="table table-bordered table-hover align-middle">


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


<?php foreach ($productos as $p): ?>


<tr>


<td>


<div class="fw-bold">

<?= htmlspecialchars(
    $p["nombre"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>


<?php if (!empty($p["codigo"])): ?>

<small class="text-muted">

Código:

<?= htmlspecialchars(
    $p["codigo"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</small>

<?php endif; ?>


<?php if ((int)$p["variante_id"] > 0): ?>


<div class="variante">


<span class="badge bg-primary badge-variante">

<i class="bi bi-tags"></i>

Talle:

<?= htmlspecialchars(
    $p["talle"] ?? "",
    ENT_QUOTES,
    "UTF-8"
) ?>

</span>


<?php if (!empty($p["color"])): ?>

<span class="badge bg-secondary badge-variante">

Color:

<?= htmlspecialchars(
    $p["color"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</span>

<?php endif; ?>


</div>


<?php endif; ?>


</td>


<td class="text-center">

<?= (int)$p["cantidad"] ?>

</td>


<td class="text-end">

$

<?= number_format(
    (float)$p["precio_venta"],
    2,
    ",",
    "."
) ?>

</td>


<td class="text-end fw-bold">

$

<?= number_format(
    (float)$p["subtotal"],
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
    $total,
    2,
    ",",
    "."
) ?>

</th>

</tr>


</tfoot>


</table>

</div>


<hr>


<form
    action="confirmar.php"
    method="POST"
>


<input
    type="hidden"
    name="total"
    value="<?= htmlspecialchars(
        (string)$total,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>


<input
    type="hidden"
    name="cliente_id"
    value="<?= $cliente_id ?>"
>


<div class="alert alert-warning">

<i class="bi bi-info-circle"></i>

<strong>Medios de pago:</strong>

<br>

<strong>Pago online:</strong>
transferencia bancaria.

<br>

<strong>Pago presencial:</strong>
efectivo o transferencia bancaria.

</div>


<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">


<a
    href="../carrito/index.php"
    class="btn btn-outline-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver al carrito

</a>


<button
    type="submit"
    class="btn btn-success btn-lg"
>

<i class="bi bi-credit-card"></i>

Continuar con el pago

</button>


</div>


</form>


</div>

</div>


</div>

</div>


</div>


</body>

</html>