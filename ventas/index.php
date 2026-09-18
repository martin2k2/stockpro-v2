<?php

session_start();

require_once "../config/conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login/index.php");
    exit;
}

$pdo = Conexion::conectar();

$usuarioId = (int)$_SESSION["usuario_id"];

$rol = strtoupper(
    trim(
        (string)($_SESSION["rol"] ?? "")
    )
);

if (!in_array($rol, ["ADMIN", "OPERADOR"], true)) {
    die("No tiene permisos para acceder al Punto de Venta.");
}


/*
|--------------------------------------------------------------------------
| CAJA ABIERTA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        fecha_apertura,
        saldo_inicial,
        ingresos,
        egresos,
        ventas,
        estado
    FROM cajas
    WHERE usuario_id = ?
    AND estado = 'ABIERTA'
    ORDER BY id DESC
    LIMIT 1
");

$stmt->execute([
    $usuarioId
]);

$caja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$caja) {
    header("Location: ../caja/abrir.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| PRODUCTOS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.id,
        p.codigo,
        p.nombre,
        p.precio_venta,
        p.stock,
        p.descripcion,
        p.imagen,
        c.nombre AS categoria,

        EXISTS(
            SELECT 1
            FROM producto_variantes pv
            WHERE pv.producto_id = p.id
            AND pv.activo = 1
        ) AS tiene_variantes,

        (
            SELECT COALESCE(SUM(pv.stock), 0)
            FROM producto_variantes pv
            WHERE pv.producto_id = p.id
            AND pv.activo = 1
        ) AS stock_variantes

    FROM productos p

    LEFT JOIN categorias c
        ON c.id = p.categoria_id

    WHERE
        p.stock > 0

        OR EXISTS(
            SELECT 1
            FROM producto_variantes pv
            WHERE pv.producto_id = p.id
            AND pv.activo = 1
            AND pv.stock > 0
        )

    ORDER BY p.nombre ASC
");

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| VARIANTES
|--------------------------------------------------------------------------
*/

$variantesPorProducto = [];

$stmtVariantes = $pdo->query("
    SELECT
        id,
        producto_id,
        talle,
        color,
        codigo,
        stock,
        activo
    FROM producto_variantes
    WHERE activo = 1
    AND stock > 0
    ORDER BY producto_id, talle, color
");

$variantes = $stmtVariantes->fetchAll(PDO::FETCH_ASSOC);

foreach ($variantes as $variante) {

    $productoId = (int)$variante["producto_id"];

    if (!isset($variantesPorProducto[$productoId])) {
        $variantesPorProducto[$productoId] = [];
    }

    $variantesPorProducto[$productoId][] = $variante;
}


/*
|--------------------------------------------------------------------------
| CLIENTES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        dni,
        apellido,
        nombre
    FROM clientes
    WHERE estado IS NULL
    OR UPPER(TRIM(estado)) = 'ACTIVO'
    OR estado = '1'
    ORDER BY apellido ASC, nombre ASC
");

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Punto de Venta - Stock PRO</title>

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

.topbar {
    background: #212529;
    color: #fff;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.topbar-titulo {
    font-size: 20px;
    font-weight: 600;
}

.topbar-usuario {
    display: flex;
    align-items: center;
    gap: 8px;
}

.caja-info {
    background: #fff;
    border-radius: 10px;
    padding: 15px 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
}

.producto-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 15px;
    height: 100%;
    transition: .2s;
}

.producto-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,.08);
}

.producto-nombre {
    font-weight: 600;
    min-height: 45px;
}

.producto-precio {
    font-size: 21px;
    font-weight: 700;
}

.carrito-panel {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
    overflow: hidden;
    position: sticky;
    top: 15px;
}

.carrito-header {
    background: #212529;
    color: #fff;
    padding: 15px;
}

.carrito-body {
    padding: 15px;
}

.tabla-carrito th,
.tabla-carrito td {
    vertical-align: middle;
}

.total-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}

.total-importe {
    font-size: 30px;
    font-weight: 700;
}

.buscador {
    font-size: 18px;
    height: 52px;
}

.variante-box {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px;
}

@media (max-width: 991px) {

    .carrito-panel {
        position: static;
    }

}

</style>

</head>

<body>


<div class="topbar">

    <div class="topbar-titulo">

        <i class="bi bi-cart-check"></i>

        Punto de Venta

    </div>


    <div class="topbar-usuario">

        <i class="bi bi-person-circle"></i>

        <?= htmlspecialchars(
            $_SESSION["usuario"] ?? "Usuario",
            ENT_QUOTES,
            "UTF-8"
        ) ?>

        <span class="badge bg-success">

            <?= htmlspecialchars(
                $rol,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </span>

    </div>

</div>


<div class="container-fluid py-3">


<div class="caja-info mb-3">

    <div class="row align-items-center">

        <div class="col-md-4">

            <strong>

                <i class="bi bi-safe2"></i>

                Caja #<?= (int)$caja["id"] ?>

            </strong>

            <span class="badge bg-success ms-2">

                ABIERTA

            </span>

        </div>


        <div class="col-md-4">

            <small class="text-muted d-block">

                Apertura

            </small>

            <strong>

                <?= date(
                    "d/m/Y H:i",
                    strtotime($caja["fecha_apertura"])
                ) ?>

            </strong>

        </div>


        <div class="col-md-4 text-md-end">

            <a
                href="../caja/index.php"
                class="btn btn-outline-dark btn-sm"
            >

                <i class="bi bi-safe"></i>

                Caja

            </a>

        </div>

    </div>

</div>


<div class="row g-3">


<div class="col-lg-7">

<div class="card shadow-sm">

<div class="card-body">


<h5 class="mb-3">

    <i class="bi bi-box-seam"></i>

    Productos

</h5>


<div class="input-group mb-3">

    <span class="input-group-text">

        <i class="bi bi-search"></i>

    </span>


    <input
        type="text"
        id="buscadorProducto"
        class="form-control buscador"
        placeholder="Buscar por código o nombre..."
        autocomplete="off"
    >


    <button
        type="button"
        id="btnLimpiarBusqueda"
        class="btn btn-outline-secondary"
    >

        <i class="bi bi-x-lg"></i>

    </button>

</div>


<div
    class="row g-3"
    id="listaProductos"
>


<?php foreach ($productos as $producto): ?>

<?php

$productoId = (int)$producto["id"];

$tieneVariantes =
    (int)$producto["tiene_variantes"] === 1;

$stockMostrar =
    $tieneVariantes
    ? (int)$producto["stock_variantes"]
    : (int)$producto["stock"];

$variantesProducto =
    $variantesPorProducto[$productoId] ?? [];

?>


<div
    class="col-md-6 producto-item"
    data-codigo="<?= htmlspecialchars(
        strtolower((string)$producto["codigo"]),
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    data-nombre="<?= htmlspecialchars(
        strtolower((string)$producto["nombre"]),
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<div class="producto-card">


<div class="d-flex justify-content-between">

    <span class="badge bg-secondary">

        <?= htmlspecialchars(
            $producto["codigo"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </span>


    <span class="badge bg-light text-dark">

        Stock: <?= $stockMostrar ?>

    </span>

</div>


<div class="producto-nombre mt-2">

    <?= htmlspecialchars(
        $producto["nombre"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>

</div>


<?php if (!empty($producto["categoria"])): ?>

<small class="text-muted">

    <?= htmlspecialchars(
        $producto["categoria"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>

</small>

<?php endif; ?>


<div class="producto-precio mt-2">

    $
    <?= number_format(
        (float)$producto["precio_venta"],
        2,
        ",",
        "."
    ) ?>

</div>


<?php if ($tieneVariantes): ?>


<div class="variante-box mt-3">

    <label class="form-label small fw-bold">

        <i class="bi bi-tags"></i>

        Elegir talle / color

    </label>


    <select
        class="form-select form-select-sm selector-variante"
    >

        <option value="">

            Seleccionar variante

        </option>


        <?php foreach ($variantesProducto as $variante): ?>

        <?php

        $textoVariante = $variante["talle"];

        if (!empty($variante["color"])) {
            $textoVariante .=
                " - " .
                $variante["color"];
        }

        if (!empty($variante["codigo"])) {
            $textoVariante .=
                " (" .
                $variante["codigo"] .
                ")";
        }

        ?>


        <option
            value="<?= (int)$variante["id"] ?>"
            data-talle="<?= htmlspecialchars(
                $variante["talle"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            data-color="<?= htmlspecialchars(
                $variante["color"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            data-codigo="<?= htmlspecialchars(
                $variante["codigo"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            data-stock="<?= (int)$variante["stock"] ?>"
        >

            <?= htmlspecialchars(
                $textoVariante,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

            - Stock:
            <?= (int)$variante["stock"] ?>

        </option>

        <?php endforeach; ?>

    </select>


    <small
        class="text-muted d-block mt-2 stock-variante"
    >

        Seleccioná una variante.

    </small>

</div>

<?php endif; ?>


<div class="d-flex gap-2 mt-3">


<input
    type="number"
    class="form-control cantidad-producto"
    value="1"
    min="1"
    max="<?= $stockMostrar ?>"
>


<button
    type="button"
    class="btn btn-primary btn-agregar"
    data-id="<?= $productoId ?>"
    data-codigo="<?= htmlspecialchars(
        $producto["codigo"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    data-nombre="<?= htmlspecialchars(
        $producto["nombre"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    data-precio="<?= htmlspecialchars(
        $producto["precio_venta"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    data-stock="<?= $stockMostrar ?>"
    data-tiene-variantes="<?= $tieneVariantes ? '1' : '0' ?>"
>

    <i class="bi bi-cart-plus"></i>

    Agregar

</button>

</div>


<button
    type="button"
    class="btn btn-outline-secondary btn-sm w-100 mt-2 btn-ver-detalle"
    data-id="<?= $productoId ?>"
    data-codigo="<?= htmlspecialchars(
        $producto["codigo"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    data-nombre="<?= htmlspecialchars(
        $producto["nombre"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    data-precio="<?= htmlspecialchars(
        $producto["precio_venta"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    data-stock="<?= $stockMostrar ?>"
    data-categoria="<?= htmlspecialchars(
        $producto["categoria"] ?? "",
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    data-descripcion="<?= htmlspecialchars(
        $producto["descripcion"] ?? "",
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    data-imagen="<?= htmlspecialchars(
        $producto["imagen"] ?? "",
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

    <i class="bi bi-eye"></i>

    Ver detalle

</button>


</div>

</div>

<?php endforeach; ?>

</div>


<div
    id="sinResultados"
    class="alert alert-warning d-none mt-3"
>

    No se encontraron productos.

</div>


</div>

</div>

</div>


<div class="col-lg-5">

<div class="carrito-panel">


<div class="carrito-header">

<div
    class="d-flex justify-content-between align-items-center"
>

<h5 class="mb-0">

    <i class="bi bi-cart3"></i>

    Venta actual

</h5>


<span
    class="badge bg-light text-dark"
    id="cantidadItems"
>

0

</span>

</div>

</div>


<div class="carrito-body">


<div class="mb-3">

<label class="form-label fw-semibold">

Cliente

</label>


<select
    id="cliente_id"
    class="form-select"
>

<option value="">

Consumidor Final

</option>


<?php foreach ($clientes as $cliente): ?>

<option value="<?= (int)$cliente["id"] ?>">

<?= htmlspecialchars(
    trim(
        $cliente["apellido"] .
        " " .
        $cliente["nombre"]
    ),
    ENT_QUOTES,
    "UTF-8"
) ?>

-

<?= htmlspecialchars(
    $cliente["dni"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="table-responsive">

<table class="table table-sm tabla-carrito">

<thead>

<tr>

<th>Producto</th>

<th class="text-center">

Cant.

</th>

<th class="text-end">

Subtotal

</th>

<th></th>

</tr>

</thead>


<tbody id="carritoBody">

<tr>

<td
    colspan="4"
    class="text-center text-muted py-4"
>

No hay productos agregados.

</td>

</tr>

</tbody>

</table>

</div>


<div class="total-box mt-3">

<div class="d-flex justify-content-between">

<span>

Total

</span>


<span
    class="total-importe"
    id="totalVenta"
>

$ 0,00

</span>

</div>

</div>


<div class="mt-3">

<label class="form-label fw-semibold">

Forma de pago

</label>


<select
    id="forma_pago"
    class="form-select"
>

<option value="EFECTIVO">

Efectivo

</option>

<option value="TRANSFERENCIA">

Transferencia

</option>

<option value="DEBITO">

Débito

</option>

<option value="CREDITO">

Crédito

</option>

</select>

</div>


<div
    id="bloqueCuentaCorriente"
    class="mt-3 d-none"
>

<input
    type="number"
    id="entrega"
    class="form-control"
    value="0"
>

<input
    type="date"
    id="vencimiento"
    class="form-control mt-2"
>

<textarea
    id="observaciones_cc"
    class="form-control mt-2"
></textarea>

</div>


<div class="d-grid gap-2 mt-4">


<button
    type="button"
    id="btnFinalizarVenta"
    class="btn btn-success btn-lg"
>

<i class="bi bi-check-circle"></i>

Finalizar venta

</button>


<button
    type="button"
    id="btnVaciarCarrito"
    class="btn btn-outline-danger"
>

<i class="bi bi-trash"></i>

Vaciar venta

</button>


<a
    href="../dashboard/index.php"
    class="btn btn-outline-secondary"
>

Volver al Dashboard

</a>


</div>

</div>

</div>

</div>

</div>

</div>


<div
    class="modal fade"
    id="modalDetalleProducto"
    tabindex="-1"
    aria-hidden="true"
>

<div class="modal-dialog modal-dialog-centered">

<div class="modal-content">


<div class="modal-header">

<h5 class="modal-title">

<i class="bi bi-box-seam"></i>

Detalle del producto

</h5>


<button
    type="button"
    class="btn-close"
    data-bs-dismiss="modal"
></button>

</div>


<div class="modal-body">


<div
    id="detalleImagenContenedor"
    class="text-center mb-3 d-none"
>

<img
    id="detalleImagen"
    src=""
    alt=""
    class="img-fluid rounded"
    style="
        max-height:250px;
        object-fit:contain;
    "
>

</div>


<div class="mb-3">

<small class="text-muted">

Código

</small>

<div
    id="detalleCodigo"
    class="fw-semibold"
></div>

</div>


<div class="mb-3">

<small class="text-muted">

Producto

</small>

<h4 id="detalleNombre"></h4>

</div>


<div class="mb-3">

<small class="text-muted">

Categoría

</small>

<div id="detalleCategoria"></div>

</div>


<div class="row mb-3">


<div class="col-6">

<small class="text-muted">

Precio

</small>

<div
    id="detallePrecio"
    class="fw-bold fs-5"
></div>

</div>


<div class="col-6">

<small class="text-muted">

Stock

</small>

<div
    id="detalleStock"
    class="fw-bold fs-5"
></div>

</div>


</div>


<div>

<small class="text-muted">

Descripción

</small>

<div
    id="detalleDescripcion"
    class="mt-1"
></div>

</div>


</div>


<div class="modal-footer">

<button
    type="button"
    class="btn btn-secondary"
    data-bs-dismiss="modal"
>

Cerrar

</button>

</div>


</div>

</div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        let carrito = [];


        const carritoBody =
            document.getElementById(
                "carritoBody"
            );

        const totalVenta =
            document.getElementById(
                "totalVenta"
            );

        const cantidadItems =
            document.getElementById(
                "cantidadItems"
            );

        const formaPago =
            document.getElementById(
                "forma_pago"
            );

        const buscador =
            document.getElementById(
                "buscadorProducto"
            );

        const sinResultados =
            document.getElementById(
                "sinResultados"
            );


        function escapeHtml(texto) {

            const div =
                document.createElement("div");

            div.textContent =
                texto ?? "";

            return div.innerHTML;

        }


        function formatoDinero(valor) {

            return new Intl.NumberFormat(
                "es-AR",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ).format(valor);

        }


        /*
        |--------------------------------------------------------------------------
        | MODAL DETALLE PRODUCTO
        |--------------------------------------------------------------------------
        */

        const modalDetalleElemento =
            document.getElementById(
                "modalDetalleProducto"
            );

        const modalDetalle =
            new bootstrap.Modal(
                modalDetalleElemento
            );


        /*
        |--------------------------------------------------------------------------
        | SELECTOR DE VARIANTES
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                ".selector-variante"
            )
            .forEach(
                function (selector) {

                    selector.addEventListener(
                        "change",
                        function () {

                            const card =
                                this.closest(
                                    ".producto-card"
                                );

                            const opcion =
                                this.options[
                                    this.selectedIndex
                                ];

                            const stockTexto =
                                card.querySelector(
                                    ".stock-variante"
                                );

                            const inputCantidad =
                                card.querySelector(
                                    ".cantidad-producto"
                                );


                            if (!this.value) {

                                stockTexto.textContent =
                                    "Seleccioná una variante.";

                                return;

                            }


                            const stock =
                                parseInt(
                                    opcion.dataset.stock
                                ) || 0;

                            const talle =
                                opcion.dataset.talle || "";

                            const color =
                                opcion.dataset.color || "";

                            const codigo =
                                opcion.dataset.codigo || "";


                            let texto =
                                "Talle: " +
                                talle;


                            if (color) {

                                texto +=
                                    " | Color: " +
                                    color;

                            }


                            if (codigo) {

                                texto +=
                                    " | Código: " +
                                    codigo;

                            }


                            texto +=
                                " | Stock disponible: " +
                                stock;


                            stockTexto.textContent =
                                texto;


                            inputCantidad.max =
                                stock;


                            if (
                                parseInt(
                                    inputCantidad.value
                                ) > stock
                            ) {

                                inputCantidad.value =
                                    stock > 0
                                    ? stock
                                    : 1;

                            }

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR CARRITO
        |--------------------------------------------------------------------------
        */

        function actualizarCarrito() {

            carritoBody.innerHTML = "";


            if (carrito.length === 0) {

                carritoBody.innerHTML = `
                    <tr>
                        <td
                            colspan="4"
                            class="text-center text-muted py-4"
                        >
                            No hay productos agregados.
                        </td>
                    </tr>
                `;


                totalVenta.textContent =
                    "$ 0,00";


                cantidadItems.textContent =
                    "0";


                return;

            }


            let total = 0;

            let cantidadTotal = 0;


            carrito.forEach(
                function (item, index) {

                    const subtotal =
                        item.precio *
                        item.cantidad;


                    total += subtotal;

                    cantidadTotal +=
                        item.cantidad;


                    let varianteTexto = "";


                    if (item.variante_id) {

                        varianteTexto =
                            `<small class="text-primary">
                                Talle: ${escapeHtml(item.talle)}
                                ${
                                    item.color
                                    ? " | Color: " +
                                      escapeHtml(item.color)
                                    : ""
                                }
                            </small>`;

                    }


                    const tr =
                        document.createElement("tr");


                    tr.innerHTML = `

                        <td>

                            <div class="fw-semibold">

                                ${escapeHtml(
                                    item.nombre
                                )}

                            </div>

                            <small class="text-muted">

                                ${escapeHtml(
                                    item.codigo
                                )}

                            </small>

                            <br>

                            ${varianteTexto}

                            <div>

                                $ ${formatoDinero(
                                    item.precio
                                )}

                            </div>

                        </td>


                        <td class="text-center">

                            <input
                                type="number"
                                class="form-control form-control-sm input-cantidad"
                                value="${item.cantidad}"
                                min="1"
                                max="${item.stock}"
                                data-index="${index}"
                                style="width:75px;margin:auto;"
                            >

                        </td>


                        <td class="text-end fw-semibold">

                            $ ${formatoDinero(
                                subtotal
                            )}

                        </td>


                        <td>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-eliminar"
                                data-index="${index}"
                            >

                                <i class="bi bi-x-lg"></i>

                            </button>

                        </td>

                    `;


                    carritoBody.appendChild(tr);

                }
            );


            totalVenta.textContent =
                "$ " +
                formatoDinero(total);


            cantidadItems.textContent =
                cantidadTotal;


            document
                .querySelectorAll(
                    ".btn-eliminar"
                )
                .forEach(
                    function (btn) {

                        btn.addEventListener(
                            "click",
                            function () {

                                const index =
                                    parseInt(
                                        this.dataset.index
                                    );


                                carrito.splice(
                                    index,
                                    1
                                );


                                actualizarCarrito();

                            }
                        );

                    }
                );


            document
                .querySelectorAll(
                    ".input-cantidad"
                )
                .forEach(
                    function (input) {

                        input.addEventListener(
                            "change",
                            function () {

                                const index =
                                    parseInt(
                                        this.dataset.index
                                    );

                                let cantidad =
                                    parseInt(
                                        this.value
                                    );

                                const stock =
                                    carrito[index].stock;


                                if (
                                    isNaN(cantidad) ||
                                    cantidad < 1
                                ) {

                                    cantidad = 1;

                                }


                                if (cantidad > stock) {

                                    cantidad = stock;

                                }


                                carrito[index]
                                    .cantidad =
                                    cantidad;


                                actualizarCarrito();

                            }
                        );

                    }
                );

        }


        /*
        |--------------------------------------------------------------------------
        | VER DETALLE DEL PRODUCTO
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                ".btn-ver-detalle"
            )
            .forEach(
                function (btn) {

                    btn.addEventListener(
                        "click",
                        function () {


                            const codigo =
                                this.dataset.codigo || "";

                            const nombre =
                                this.dataset.nombre || "";

                            const precio =
                                parseFloat(
                                    this.dataset.precio
                                ) || 0;

                            const stock =
                                this.dataset.stock || "0";

                            const categoria =
                                this.dataset.categoria || "";

                            const descripcion =
                                this.dataset.descripcion || "";

                            const imagen =
                                this.dataset.imagen || "";


                            document.getElementById(
                                "detalleCodigo"
                            ).textContent =
                                codigo;


                            document.getElementById(
                                "detalleNombre"
                            ).textContent =
                                nombre;


                            document.getElementById(
                                "detalleCategoria"
                            ).textContent =
                                categoria ||
                                "Sin categoría";


                            document.getElementById(
                                "detallePrecio"
                            ).textContent =
                                "$ " +
                                formatoDinero(precio);


                            document.getElementById(
                                "detalleStock"
                            ).textContent =
                                stock;


                            document.getElementById(
                                "detalleDescripcion"
                            ).textContent =
                                descripcion ||
                                "Este producto no tiene descripción.";


                            const imagenElemento =
                                document.getElementById(
                                    "detalleImagen"
                                );


                            const imagenContenedor =
                                document.getElementById(
                                    "detalleImagenContenedor"
                                );


                            if (imagen) {

                                imagenElemento.src =
                                    "../uploads/productos/" +
                                    imagen;

                                imagenElemento.alt =
                                    nombre;

                                imagenContenedor.classList.remove(
                                    "d-none"
                                );

                            } else {

                                imagenElemento.src =
                                    "";

                                imagenElemento.alt =
                                    "";

                                imagenContenedor.classList.add(
                                    "d-none"
                                );

                            }


                            modalDetalle.show();

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | AGREGAR PRODUCTO
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                ".btn-agregar"
            )
            .forEach(
                function (btn) {

                    btn.addEventListener(
                        "click",
                        function () {


                            const card =
                                this.closest(
                                    ".producto-card"
                                );


                            const id =
                                parseInt(
                                    this.dataset.id
                                );

                            let codigo =
                                this.dataset.codigo;

                            const nombre =
                                this.dataset.nombre;

                            const precio =
                                parseFloat(
                                    this.dataset.precio
                                );

                            const tieneVariantes =
                                this.dataset
                                    .tieneVariantes ===
                                "1";


                            let varianteId =
                                null;

                            let talle = "";

                            let color = "";

                            let stock =
                                parseInt(
                                    this.dataset.stock
                                );


                            if (tieneVariantes) {

                                const selector =
                                    card.querySelector(
                                        ".selector-variante"
                                    );


                                if (
                                    !selector ||
                                    !selector.value
                                ) {

                                    Swal.fire({
                                        icon: "warning",
                                        title:
                                            "Seleccione una variante",
                                        text:
                                            "Debe seleccionar talle o color antes de agregar el producto."
                                    });

                                    return;

                                }


                                const opcion =
                                    selector.options[
                                        selector.selectedIndex
                                    ];


                                varianteId =
                                    parseInt(
                                        selector.value
                                    );


                                talle =
                                    opcion.dataset.talle ||
                                    "";

                                color =
                                    opcion.dataset.color ||
                                    "";

                                stock =
                                    parseInt(
                                        opcion.dataset.stock
                                    ) || 0;


                                if (
                                    opcion.dataset.codigo
                                ) {

                                    codigo =
                                        opcion.dataset.codigo;

                                }

                            }


                            const input =
                                card.querySelector(
                                    ".cantidad-producto"
                                );


                            let cantidad =
                                parseInt(
                                    input.value
                                );


                            if (
                                isNaN(cantidad) ||
                                cantidad < 1
                            ) {

                                cantidad = 1;

                            }


                            if (stock <= 0) {

                                Swal.fire({
                                    icon: "warning",
                                    title: "Sin stock",
                                    text:
                                        "La variante seleccionada no tiene stock."
                                });

                                return;

                            }


                            if (cantidad > stock) {

                                Swal.fire({
                                    icon: "warning",
                                    title:
                                        "Stock insuficiente",
                                    text:
                                        "Solo hay " +
                                        stock +
                                        " unidades disponibles."
                                });

                                return;

                            }


                            const existente =
                                carrito.find(
                                    function (item) {

                                        if (varianteId) {

                                            return (
                                                item.id === id &&
                                                item.variante_id === varianteId
                                            );

                                        }


                                        return (
                                            item.id === id &&
                                            !item.variante_id
                                        );

                                    }
                                );


                            if (existente) {

                                const nuevaCantidad =
                                    existente.cantidad +
                                    cantidad;


                                if (
                                    nuevaCantidad >
                                    existente.stock
                                ) {

                                    Swal.fire({
                                        icon: "warning",
                                        title:
                                            "Stock insuficiente",
                                        text:
                                            "No puede agregar más unidades."
                                    });

                                    return;

                                }


                                existente.cantidad =
                                    nuevaCantidad;

                            } else {

                                carrito.push({

                                    id: id,

                                    variante_id:
                                        varianteId,

                                    codigo:
                                        codigo,

                                    nombre:
                                        nombre,

                                    talle:
                                        talle,

                                    color:
                                        color,

                                    precio:
                                        precio,

                                    stock:
                                        stock,

                                    cantidad:
                                        cantidad

                                });

                            }


                            input.value = 1;


                            actualizarCarrito();


                            Swal.fire({
                                icon: "success",
                                title:
                                    "Producto agregado",
                                text:
                                    varianteId
                                    ? nombre +
                                      " - " +
                                      talle +
                                      (
                                          color
                                          ? " / " +
                                            color
                                          : ""
                                      )
                                    : nombre,
                                timer: 900,
                                showConfirmButton: false
                            });

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | BUSCADOR
        |--------------------------------------------------------------------------
        */

        buscador.addEventListener(
            "input",
            function () {

                const texto =
                    this.value
                        .trim()
                        .toLowerCase();


                let encontrados = 0;


                document
                    .querySelectorAll(
                        ".producto-item"
                    )
                    .forEach(
                        function (item) {

                            const codigo =
                                item.dataset.codigo;

                            const nombre =
                                item.dataset.nombre;


                            const coincide =
                                codigo.includes(
                                    texto
                                ) ||
                                nombre.includes(
                                    texto
                                );


                            if (coincide) {

                                item.classList.remove(
                                    "d-none"
                                );

                                encontrados++;

                            } else {

                                item.classList.add(
                                    "d-none"
                                );

                            }

                        }
                    );


                if (
                    encontrados === 0 &&
                    texto !== ""
                ) {

                    sinResultados.classList.remove(
                        "d-none"
                    );

                } else {

                    sinResultados.classList.add(
                        "d-none"
                    );

                }

            }
        );


        document
            .getElementById(
                "btnLimpiarBusqueda"
            )
            .addEventListener(
                "click",
                function () {

                    buscador.value = "";


                    document
                        .querySelectorAll(
                            ".producto-item"
                        )
                        .forEach(
                            function (item) {

                                item.classList.remove(
                                    "d-none"
                                );

                            }
                        );


                    sinResultados.classList.add(
                        "d-none"
                    );


                    buscador.focus();

                }
            );


        /*
        |--------------------------------------------------------------------------
        | VACIAR CARRITO
        |--------------------------------------------------------------------------
        */

        document
            .getElementById(
                "btnVaciarCarrito"
            )
            .addEventListener(
                "click",
                function () {

                    carrito = [];

                    actualizarCarrito();

                }
            );


        /*
        |--------------------------------------------------------------------------
        | FINALIZAR VENTA
        |--------------------------------------------------------------------------
        */

        document
            .getElementById(
                "btnFinalizarVenta"
            )
            .addEventListener(
                "click",
                function () {


                    if (carrito.length === 0) {

                        Swal.fire({
                            icon: "warning",
                            title:
                                "Venta vacía",
                            text:
                                "Agregue al menos un producto."
                        });

                        return;

                    }


                    let total = 0;


                    carrito.forEach(
                        function (item) {

                            total +=
                                item.precio *
                                item.cantidad;

                        }
                    );


                    const datos = {

                        productos:

                            carrito.map(
                                function (item) {

                                    return {

                                        id:
                                            item.id,

                                        variante_id:
                                            item.variante_id,

                                        cantidad:
                                            item.cantidad

                                    };

                                }
                            ),


                        cliente_id:

                            document
                                .getElementById(
                                    "cliente_id"
                                )
                                .value,


                        forma_pago:

                            formaPago.value,


                        entrega: 0,


                        vencimiento: "",


                        observaciones_cc: ""

                    };


                    fetch(
                        "finalizar.php",
                        {

                            method: "POST",

                            headers: {

                                "Content-Type":
                                    "application/json"

                            },

                            body:
                                JSON.stringify(datos)

                        }
                    )

                    .then(
                        respuesta =>
                            respuesta.json()
                    )

                    .then(
                        function (data) {

                            if (!data.ok) {

                                throw new Error(
                                    data.error ||
                                    "No se pudo registrar la venta."
                                );

                            }


                            Swal.fire({

                                icon: "success",

                                title:
                                    "Venta registrada",

                                text:
                                    "Venta #" +
                                    data.venta

                            })
                            .then(
                                function () {

                                    carrito = [];

                                    actualizarCarrito();

                                    location.reload();

                                }
                            );

                        }
                    )

                    .catch(
                        function (error) {

                            Swal.fire({

                                icon: "error",

                                title: "Error",

                                text:
                                    error.message

                            });

                        }
                    );

                }
            );


        actualizarCarrito();


    }
);

</script>

</body>

</html>