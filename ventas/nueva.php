<?php
// ventas/nueva.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

/* =========================================
   CLIENTES
========================================= */

$stmt = $conexion->query("
    SELECT
        id,
        apellido,
        nombre
    FROM clientes
    WHERE estado = 1
    ORDER BY apellido, nombre
");

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   PRODUCTOS
========================================= */

$stmt = $conexion->query("
    SELECT
        id,
        codigo,
        nombre,
        precio_venta,
        stock
    FROM productos
    WHERE activo = 1
    AND stock > 0
    ORDER BY nombre
");

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Nueva Venta - Stock PRO</title>

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

<!-- =========================================
     ENCABEZADO
========================================= -->

<div class="d-flex justify-content-between align-items-center mb-4">

<h3 class="mb-0">

<i class="bi bi-cart-plus"></i>

Nueva Venta

</h3>

<a
href="index.php"
class="btn btn-secondary">

<i class="bi bi-arrow-left"></i>

Volver

</a>

</div>


<form
action="guardar.php"
method="POST"
id="formVenta">


<div class="row g-4">


<!-- =========================================
     PRODUCTOS
========================================= -->

<div class="col-lg-8">

<div class="card shadow-sm">

<div class="card-header bg-primary text-white">

<h5 class="mb-0">

<i class="bi bi-box-seam"></i>

Productos

</h5>

</div>


<div class="card-body">

<!-- PRODUCTO -->

<div class="row g-3">

    <div class="col-md-6">

        <label class="form-label">
            Producto
        </label>

        <select
            id="producto"
            class="form-select">

            <option value="">
                Seleccionar producto...
            </option>

            <?php foreach ($productos as $p): ?>

                <option
                    value="<?= (int)$p['id'] ?>"
                    data-codigo="<?= htmlspecialchars($p['codigo']) ?>"
                    data-precio="<?= htmlspecialchars($p['precio_venta']) ?>"
                    data-stock="<?= (int)$p['stock'] ?>">

                    <?= htmlspecialchars($p['codigo']) ?>
                    -
                    <?= htmlspecialchars($p['nombre']) ?>
                    -
                    Stock: <?= (int)$p['stock'] ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <div class="col-md-2">

        <label class="form-label">
            Cantidad
        </label>

        <input
            type="number"
            id="cantidad"
            class="form-control"
            value="1"
            min="1">

    </div>


    <div class="col-md-2">

        <label class="form-label">
            Precio
        </label>

        <input
            type="number"
            id="precio"
            class="form-control"
            step="0.01"
            min="0">

    </div>


    <div class="col-md-2 d-grid">

        <label class="form-label">
            &nbsp;
        </label>

        <button
            type="button"
            id="agregarProducto"
            class="btn btn-success">

            <i class="bi bi-plus-circle"></i>
            Agregar

        </button>

    </div>

</div>


<hr>


<!-- =========================================
     TABLA CARRITO
========================================= -->

<div class="table-responsive">

<table
class="table table-bordered table-hover align-middle"
id="tablaVenta">

<thead class="table-dark">

<tr>

<th>Código</th>

<th>Producto</th>

<th>Cantidad</th>

<th>Precio</th>

<th>Descuento</th>

<th>Subtotal</th>

<th></th>

</tr>

</thead>

<tbody id="detalleVenta">

<tr>

<td
colspan="7"
class="text-center text-muted">

No hay productos agregados

</td>

</tr>

</tbody>


<tfoot>

<tr>

<th
colspan="5"
class="text-end">

TOTAL

</th>

<th id="totalVenta">

$ 0,00

</th>

<th></th>

</tr>

</tfoot>

</table>

</div>

</div>

</div>

</div>


<!-- =========================================
     DATOS DE VENTA
========================================= -->

<div class="col-lg-4">

<div class="card shadow-sm">

<div class="card-header bg-success text-white">

<h5 class="mb-0">

<i class="bi bi-receipt"></i>

Datos de la Venta

</h5>

</div>


<div class="card-body">


<!-- CLIENTE -->

<div class="mb-3">

<label
class="form-label">

Cliente

</label>

<select
name="cliente_id"
id="cliente_id"
class="form-select">

<option value="6">

Consumidor Final

</option>

<?php foreach ($clientes as $c): ?>

<?php if ((int)$c["id"] !== 6): ?>

<option
value="<?= (int)$c["id"] ?>">

<?= htmlspecialchars($c["apellido"]) ?>,
<?= htmlspecialchars($c["nombre"]) ?>

</option>

<?php endif; ?>

<?php endforeach; ?>

</select>

</div>


<!-- FORMA DE PAGO -->

<div class="mb-3">

<label
class="form-label">

Forma de Pago

</label>

<select
name="forma_pago"
id="forma_pago"
class="form-select">

<option value="Efectivo">

Efectivo

</option>

<option value="Débito">

Débito

</option>

<option value="Crédito">

Crédito

</option>

<option value="Transferencia">

Transferencia

</option>

<option value="Cuenta Corriente">

Cuenta Corriente

</option>

</select>

</div>


<!-- OBSERVACION -->

<div class="mb-3">

<label
class="form-label">

Observación

</label>

<textarea
name="observacion"
id="observacion"
class="form-control"
rows="4"></textarea>

</div>


<!-- DETALLE -->

<input
type="hidden"
name="detalle"
id="detalle">

<!-- TOTAL GRANDE -->
<!-- TOTAL GRANDE 

<div class="alert alert-success text-center">

<div class="small">

TOTAL A PAGAR

</div>

<div
id="totalGrande"
class="fs-2 fw-bold">

$ 0,00

</div>

</div>-->


<div class="text-center mb-4">

    <div class="fw-bold">
        TOTAL A PAGAR
    </div>

    <div
        id="totalGrande"
        class="text-success fw-bold"
        style="font-size:42px;">

        $ 0,00

    </div>

</div>


<!-- GUARDAR -->

<button
type="submit"
class="btn btn-primary btn-lg w-100">

<i class="bi bi-check-circle"></i>

Finalizar Venta

</button>

</div>

</div>

</div>

</div>

</form>

</div>

</div>

</div>


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!--<script src="../assets/js/ventas.js"></script>-->
<script src="../assets/js/ventas.js?v=10"></script>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const producto = document.getElementById("producto");
    const precio = document.getElementById("precio");
    const cantidad = document.getElementById("cantidad");

    if (producto) {

        producto.addEventListener("change", function () {

            const option =
                this.options[this.selectedIndex];

            if (!option.value) {

                precio.value = "";

                return;

            }

            precio.value =
                option.dataset.precio || "";

            cantidad.value = 1;

        });

    }

});

</script>

</body>

</html>