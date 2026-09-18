<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();


/* ==========================
   PRODUCTOS
========================== */

$stmt = $conexion->query("
    SELECT COUNT(*)
    FROM productos
    WHERE activo = 1
");

$totalProductos = (int)$stmt->fetchColumn();

/* ==========================
   STOCK BAJO
========================== */

$stmt = $conexion->query("
    SELECT COUNT(*)
    FROM productos p

    WHERE p.activo = 1

    AND (

        /* PRODUCTOS CON VARIANTES */
        (
            EXISTS (
                SELECT 1
                FROM producto_variantes pv
                WHERE pv.producto_id = p.id
                AND pv.activo = 1
            )

            AND COALESCE(
                (
                    SELECT SUM(pv.stock)
                    FROM producto_variantes pv
                    WHERE pv.producto_id = p.id
                    AND pv.activo = 1
                ),
                0
            ) <= p.stock_minimo
        )

        OR

        /* PRODUCTOS SIN VARIANTES */
        (
            NOT EXISTS (
                SELECT 1
                FROM producto_variantes pv
                WHERE pv.producto_id = p.id
                AND pv.activo = 1
            )

            AND p.stock <= p.stock_minimo
        )

    )
");

$stockBajo = (int)$stmt->fetchColumn();



/* ==========================
   PRODUCTOS CON STOCK BAJO
========================== */

$stmt = $conexion->query("
    SELECT

        p.id,
        p.codigo,
        p.nombre,

        p.stock_minimo,

        c.nombre AS categoria,

        CASE

            /* SI TIENE VARIANTES */
            WHEN EXISTS (

                SELECT 1
                FROM producto_variantes pv
                WHERE pv.producto_id = p.id
                AND pv.activo = 1

            )

            THEN COALESCE(

                (
                    SELECT SUM(pv.stock)
                    FROM producto_variantes pv
                    WHERE pv.producto_id = p.id
                    AND pv.activo = 1
                ),

                0

            )

            /* SI NO TIENE VARIANTES */

            ELSE p.stock

        END AS stock,

        CASE

            WHEN EXISTS (

                SELECT 1
                FROM producto_variantes pv
                WHERE pv.producto_id = p.id
                AND pv.activo = 1

            )

            THEN 1

            ELSE 0

        END AS tiene_variantes

    FROM productos p

    LEFT JOIN categorias c
        ON c.id = p.categoria_id

    WHERE p.activo = 1

    AND (

        /* PRODUCTOS CON VARIANTES */

        (
            EXISTS (

                SELECT 1
                FROM producto_variantes pv
                WHERE pv.producto_id = p.id
                AND pv.activo = 1

            )

            AND COALESCE(

                (
                    SELECT SUM(pv.stock)
                    FROM producto_variantes pv
                    WHERE pv.producto_id = p.id
                    AND pv.activo = 1
                ),

                0

            ) <= p.stock_minimo

        )

        OR

        /* PRODUCTOS SIN VARIANTES */

        (
            NOT EXISTS (

                SELECT 1
                FROM producto_variantes pv
                WHERE pv.producto_id = p.id
                AND pv.activo = 1

            )

            AND p.stock <= p.stock_minimo

        )

    )

    ORDER BY
        stock ASC,
        p.nombre ASC
");

$productosStockBajo =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
   VALOR INVENTARIO
========================== */

$stmt = $conexion->query("
    SELECT

        COALESCE(

            SUM(

                (
                    CASE

                        /* PRODUCTO CON VARIANTES */

                        WHEN EXISTS (

                            SELECT 1
                            FROM producto_variantes pv
                            WHERE pv.producto_id = p.id
                            AND pv.activo = 1

                        )

                        THEN COALESCE(

                            (
                                SELECT SUM(pv.stock)
                                FROM producto_variantes pv
                                WHERE pv.producto_id = p.id
                                AND pv.activo = 1
                            ),

                            0

                        )

                        /* PRODUCTO NORMAL */

                        ELSE p.stock

                    END

                ) * p.precio_compra

            ),

            0

        ) AS total

    FROM productos p

    WHERE p.activo = 1
");

$valorInventario = (float)$stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| DETALLE DEL VALOR DE INVENTARIO
|--------------------------------------------------------------------------
*/

$stmt = $conexion->query("
    SELECT
        p.id,
        p.codigo,
        p.nombre,
        p.stock,
        p.precio_compra,
        (p.stock * p.precio_compra) AS valor_inventario
    FROM productos p
    WHERE p.activo = 1
    ORDER BY valor_inventario DESC, p.nombre ASC
");

$productosInventario = $stmt->fetchAll(PDO::FETCH_ASSOC);





/* ==========================
   VENTAS DE HOY
========================== */

$stmt = $conexion->query("
    SELECT
        COUNT(*) AS cantidad,
        COALESCE(SUM(total), 0) AS total
    FROM ventas
    WHERE DATE(fecha) = CURDATE()
    AND (estado IS NULL OR estado <> 'ANULADA')
");

$ventasHoy = $stmt->fetch(PDO::FETCH_ASSOC);


/* ==========================
   ÚLTIMOS MOVIMIENTOS
========================== */

$stmt = $conexion->query("
    SELECT
        m.id,
        m.fecha,
        m.tipo,
        m.cantidad,
        m.observacion,
        p.codigo,
        p.nombre AS producto,
        u.nombre AS usuario
    FROM movimientos m

    INNER JOIN productos p
        ON p.id = m.producto_id

    LEFT JOIN usuarios_stock u
        ON u.id = m.usuario_id

    ORDER BY m.id DESC

    LIMIT 10
");

$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php

/* =========================================================
   VERIFICAR CAJA ABIERTA
========================================================= */

$stmt = $conexion->prepare("
    SELECT id
    FROM cajas
    WHERE usuario_id = ?
      AND estado = 'ABIERTA'
    LIMIT 1
");

$stmt->execute([
    $_SESSION["usuario_id"]
]);

$cajaAbierta = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Dashboard - Stock PRO</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">


<link
rel="stylesheet"
href="../assets/css/estilos.css">


<style>
    
/* =========================================================
   TARJETAS PRINCIPALES DEL DASHBOARD
========================================================= */

.dashboard-card-col {
    display: flex;
}

.dashboard-card-col .card-info {
    width: 100%;
    min-height: 145px;
    height: 145px;

    display: flex;
    align-items: center;

    padding: 22px;

    box-sizing: border-box;
}



/* =========================================================
   TARJETA STOCK BAJO
========================================================= */

.stock-bajo-card {

    cursor: pointer;

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}

.stock-bajo-card:hover {

    transform: translateY(-3px);

    box-shadow:
        0 8px 20px rgba(0,0,0,.18);

}


/* =========================================================
   TABLA STOCK BAJO
========================================================= */

.tabla-stock-bajo th {

    white-space: nowrap;

}

.tabla-stock-bajo td {

    vertical-align: middle;

}


/* =========================================================
   BADGES
========================================================= */

.badge-stock {

    font-size: .85rem;

}


/* =========================================================
   PRODUCTO SIN STOCK
========================================================= */

.fila-sin-stock {

    background-color: rgba(220,53,69,.08);

}


/* =========================================================
   MODAL
========================================================= */

.modal-stock-bajo .modal-header {

    background: #dc3545;

    color: white;

}

.modal-stock-bajo .modal-header .btn-close {

    filter: brightness(0) invert(1);

}

.topbar {
    width: 27%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 25px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    box-sizing: border-box;
}

.topbar-titulo {
    font-size: 22px;
    font-weight: 200;
    color: #1f2937;
}

.topbar-usuario {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 200;
    color: #374151;
}

.topbar-usuario i {
    font-size: 24px;
}

</style>

</head>


<body>


<?php include "../includes/sidebar.php"; ?>


<div class="main">


<div class="content">


<div class="container-fluid">


<!-- =========================================================
     ENCABEZADO
========================================================= -->

<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h2 class="mb-1">

<i class="bi bi-speedometer2"></i>

Sistema de stock-urqui

</h2>

<p class="text-muted mb-0">

Resumen general del sistema

</p>

</div>
<div class="topbar">
    <div class="topbar-titulo">
        Usuario:
    </div>

    <div class="topbar-usuario">
        <i class="bi bi-person-circle"></i>
        <span><?= htmlspecialchars($_SESSION["usuario"] ?? "Usuario") ?></span>
    </div>
</div>

</div>


<!-- =========================================================
     TARJETAS
========================================================= -->

<div class="row">


<!-- =========================================================
     PRODUCTOS
========================================================= -->

<!--<div class="col-lg-3 col-md-6 mb-4">-->
<div class="col-lg-3 col-md-6 mb-4 dashboard-card-col"> 

<div class="card-info azul">

<div class="d-flex justify-content-between align-items-center">

<div>

<h6>Total Productos</h6>

<h2>

<?= $totalProductos ?>

</h2>
<br>
</div>

<i class="bi bi-box-seam fs-1"></i>

</div>

</div>

</div>


<!-- =========================================================
     STOCK BAJO
========================================================= -->

<!--<div class="col-lg-3 col-md-6 mb-4">-->
<div class="col-lg-3 col-md-6 mb-4 dashboard-card-col"> 

<div
class="card-info rojo stock-bajo-card"
data-bs-toggle="modal"
data-bs-target="#modalStockBajo"
title="Ver productos con stock bajo">


<div class="d-flex justify-content-between align-items-center">


<div>

<h6>

Stock Bajo

<i
class="bi bi-info-circle ms-1"
style="font-size:.85rem;">
</i>

</h6>


<h2>

<?= $stockBajo ?>

</h2>


<?php if ($stockBajo > 0): ?>

<small>

Ver productos

</small>

<?php else: ?>

<small>

No hay productos con stock bajo

</small>

<?php endif; ?>


</div>


<i class="bi bi-exclamation-triangle fs-1"></i>


</div>


</div>


</div>


<!-- =========================================================
     VENTAS
========================================================= -->

<!--<div class="col-lg-3 col-md-6 mb-4">-->
<div class="col-lg-3 col-md-6 mb-4 dashboard-card-col"> 
<div class="card-info verde">

<div class="d-flex justify-content-between align-items-center">

<div>

<h6>Ventas Hoy</h6>


<h2>

<?= (int)$ventasHoy["cantidad"] ?>

</h2>
<br>

</div>

<i class="bi bi-cart-check fs-1"></i>

</div>

</div>

</div>


<!-- =========================================================
     INVENTARIO
========================================================= -->

<?php if (strtoupper(trim((string)($_SESSION["rol"] ?? ""))) === "ADMIN"): ?>

<div class="col-lg-3 col-md-6 mb-4 dashboard-card-col">

    <div
        class="card-info naranja stock-bajo-card"
        data-bs-toggle="modal"
        data-bs-target="#modalValorInventario"
        title="Ver detalle del valor del inventario"
    >

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h6>
                    Valor Inventario

                    <i
                        class="bi bi-info-circle ms-1"
                        style="font-size:.85rem;"
                    ></i>
                </h6>

                <h2>

                    $

                    <?= number_format(
                        $valorInventario,
                        0,
                        ',',
                        '.'
                    ) ?>

                </h2>

                <small>
                    Ver detalle
                </small>

            </div>

            <i class="bi bi-cash-stack fs-1"></i>

        </div>

    </div>

</div>

<?php endif; ?>




<!-- =========================================================
     VENTA DEL DÍA
========================================================= -->

<div class="card shadow mb-4">


<div class="card-body">


<div class="row align-items-center">


<div class="col-md-6">


<h5 class="mb-1">

<i class="bi bi-currency-dollar"></i>

Total vendido hoy

</h5>


<small class="text-muted">

Ventas realizadas durante el día

</small>


</div>


<div class="col-md-6 text-md-end">


<h2 class="text-success mb-0">

$

<?= number_format(
    $ventasHoy["total"],
    2,
    ",",
    "."
) ?>

</h2>


</div>


</div>


</div>


</div>


<!-- =========================================================
     MOVIMIENTOS
========================================================= -->

<div class="card shadow">


<div class="card-header bg-primary text-white">


<div class="d-flex justify-content-between align-items-center">


<div>

<i class="bi bi-arrow-left-right"></i>

<strong>

Últimos movimientos

</strong>

</div>


<a
href="../movimientos/"
class="btn btn-light btn-sm">

Ver todos

</a>


</div>


</div>


<div class="card-body">


<div class="table-responsive">


<table class="table table-striped table-hover align-middle mb-0">


<thead class="table-dark">


<tr>

<th>Fecha</th>

<th>Código</th>

<th>Producto</th>

<th>Tipo</th>

<th>Cantidad</th>

<th>Observación</th>

<th>Usuario</th>

</tr>


</thead>


<tbody>


<?php if (empty($movimientos)): ?>


<tr>


<td
colspan="7"
class="text-center py-4">


<i
class="bi bi-inbox fs-2 text-muted">
</i>


<br>


No hay movimientos registrados.


</td>


</tr>


<?php else: ?>


<?php foreach ($movimientos as $m): ?>


<tr>


<td>

<?= date(
    "d/m/Y H:i",
    strtotime($m["fecha"])
) ?>

</td>


<td>

<?= htmlspecialchars(
    $m["codigo"]
) ?>

</td>


<td>

<?= htmlspecialchars(
    $m["producto"]
) ?>

</td>


<td>


<?php if ($m["tipo"] === "ENTRADA"): ?>


<span class="badge bg-success">


<i class="bi bi-arrow-down-circle"></i>


ENTRADA


</span>


<?php elseif ($m["tipo"] === "SALIDA"): ?>


<span class="badge bg-danger">


<i class="bi bi-arrow-up-circle"></i>


SALIDA


</span>


<?php else: ?>


<span class="badge bg-secondary">


<?= htmlspecialchars(
    $m["tipo"]
) ?>


</span>


<?php endif; ?>


</td>


<td>


<strong>

<?= (int)$m["cantidad"] ?>

</strong>


</td>


<td>

<?= htmlspecialchars(
    $m["observacion"] ?? ""
) ?>


</td>


<td>

<?= htmlspecialchars(
    $m["usuario"] ?? "Sistema"
) ?>


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


</div>


<!-- =========================================================
     MODAL STOCK BAJO
========================================================= -->

<div
class="modal fade modal-stock-bajo"
id="modalStockBajo"
tabindex="-1"
aria-labelledby="modalStockBajoLabel"
aria-hidden="true">


<div
class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">


<div class="modal-content">


<!-- =====================================================
     HEADER
====================================================== -->

<div class="modal-header">


<div>


<h5
class="modal-title mb-1"
id="modalStockBajoLabel">


<i class="bi bi-exclamation-triangle-fill me-2"></i>


Productos con Stock Bajo


</h5>


<small>

Productos que necesitan reposición

</small>


</div>


<button
type="button"
class="btn-close"
data-bs-dismiss="modal"
aria-label="Cerrar">
</button>


</div>


<!-- =====================================================
     BODY
====================================================== -->

<div class="modal-body">


<?php if (empty($productosStockBajo)): ?>


<div class="text-center py-5">


<i
class="bi bi-check-circle-fill text-success"
style="font-size:4rem;">
</i>


<h4 class="mt-3">

Stock en buen estado

</h4>


<p class="text-muted mb-0">

No hay productos con stock bajo.

</p>


</div>


<?php else: ?>


<div class="alert alert-warning d-flex align-items-center">


<i
class="bi bi-exclamation-triangle-fill fs-4 me-3">
</i>


<div>


<strong>

Atención:

</strong>


Hay

<strong>

<?= count($productosStockBajo) ?>

</strong>

productos que necesitan reposición.


</div>


</div>


<div class="table-responsive">


<table
class="table table-hover tabla-stock-bajo mb-0">


<thead class="table-dark">


<tr>

<th>#</th>

<th>Código</th>

<th>Producto</th>

<th>Categoría</th>

<th class="text-center">Stock actual</th>

<th class="text-center">Stock mínimo</th>

<th class="text-center">Estado</th>

</tr>


</thead>


<tbody>


<?php foreach (
    $productosStockBajo
    as
    $producto
):


    $sinStock =
        ((int)$producto["stock"] <= 0);

?>


<tr
class="<?= $sinStock ? 'fila-sin-stock' : '' ?>">


<td>

<?= (int)$producto["id"] ?>

</td>


<td>

<strong>

<?= htmlspecialchars(
    $producto["codigo"]
) ?>

</strong>

</td>


<td>

<?= htmlspecialchars(
    $producto["nombre"]
) ?>

</td>


<td>

<?= htmlspecialchars(
    $producto["categoria"]
    ?? "Sin categoría"
) ?>

</td>


<td class="text-center">


<?php if ($sinStock): ?>


<span class="badge bg-danger badge-stock">

<?= (int)$producto["stock"] ?>

</span>


<?php else: ?>


<span class="badge bg-warning text-dark badge-stock">

<?= (int)$producto["stock"] ?>

</span>


<?php endif; ?>


</td>


<td class="text-center">


<span class="badge bg-secondary badge-stock">

<?= (int)$producto["stock_minimo"] ?>

</span>


</td>


<td class="text-center">


<?php if ($sinStock): ?>


<span class="badge bg-danger">

<i class="bi bi-x-circle me-1"></i>

SIN STOCK

</span>


<?php else: ?>


<span class="badge bg-warning text-dark">

<i class="bi bi-exclamation-circle me-1"></i>

STOCK BAJO

</span>


<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php endif; ?>


</div>


<!-- =====================================================
     FOOTER
====================================================== -->

<div class="modal-footer">


<?php if (!empty($productosStockBajo)): ?>


<span class="text-muted me-auto">

Total:

<strong>

<?= count($productosStockBajo) ?>

</strong>

productos

</span>


<?php endif; ?>


<button
type="button"
class="btn btn-secondary"
data-bs-dismiss="modal">


<i class="bi bi-x-lg me-1"></i>


Cerrar


</button>


</div>


</div>


</div>


</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const btnCerrarSesion = document.getElementById("btnCerrarSesion");

    if (!btnCerrarSesion) {
        return;
    }

    btnCerrarSesion.addEventListener("click", function (e) {

        e.preventDefault();

        fetch("/stockpro-v2/caja/verificar_caja.php", {
            method: "GET",
            credentials: "same-origin"
        })
        .then(response => response.json())
        .then(data => {

            if (data.ok && data.caja_abierta) {

                Swal.fire({

                    icon: "warning",

                    title: "Caja abierta",

                    text: "Recordá cerrar la caja antes de cerrar sesión.",

                    showCancelButton: true,

                    confirmButtonText: "Ir a Caja",

                    cancelButtonText: "Cerrar sesión",

                    confirmButtonColor: "#0d6efd",

                    cancelButtonColor: "#6c757d"

                }).then((result) => {

                    if (result.isConfirmed) {

                        window.location.href =
                            "/stockpro-v2/caja/index.php";

                    } else if (result.dismiss === Swal.DismissReason.cancel) {

                        window.location.href =
                            "/stockpro-v2/logout.php";

                    }

                });

            } else {

                Swal.fire({

                    icon: "question",

                    title: "Cerrar sesión",

                    text: "¿Seguro que querés cerrar sesión?",

                    showCancelButton: true,

                    confirmButtonText: "Sí, cerrar sesión",

                    cancelButtonText: "Cancelar",

                    confirmButtonColor: "#dc3545"

                }).then((result) => {

                    if (result.isConfirmed) {

                        window.location.href =
                            "/stockpro-v2/logout.php";

                    }

                });

            }

        })
        .catch(error => {

            console.error(error);

            Swal.fire({

                icon: "error",

                title: "Error",

                text: "No se pudo verificar el estado de la caja."

            });

        });

    });

});

</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

const cajaAbierta = <?= !empty($cajaAbierta) ? 'true' : 'false' ?>;

document
    .getElementById("btnCerrarSesion")
    ?.addEventListener("click", function(e) {

        if (!cajaAbierta) {
            return;
        }

        e.preventDefault();

        Swal.fire({

            icon: "warning",

            title: "Caja abierta",

            html: `
                <p class="mb-2">
                    Tenés una caja abierta.
                </p>

                <strong>
                    Recordá cerrar la caja antes de cerrar sesión.
                </strong>
            `,

            showCancelButton: true,

            confirmButtonText:
                '<i class="bi bi-cash-stack"></i> Ir a Caja',

            cancelButtonText:
                'Cerrar sesión igualmente',

            reverseButtons: true

        }).then((result) => {

            if (result.isConfirmed) {

                window.location.href =
                    "/stockpro-v2/caja/index.php";

            } else if (result.dismiss === Swal.DismissReason.cancel) {

                window.location.href =
                    "/stockpro-v2/logout.php";

            }

        });

    });





</script>
<!-- =========================================================
     MODAL VALOR INVENTARIO
========================================================= -->

<div
    class="modal fade"
    id="modalValorInventario"
    tabindex="-1"
    aria-labelledby="modalValorInventarioLabel"
    aria-hidden="true"
>

    <div
        class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"
    >

        <div class="modal-content">

            <!-- HEADER -->

            <div class="modal-header bg-warning">

                <div>

                    <h5
                        class="modal-title mb-1"
                        id="modalValorInventarioLabel"
                    >

                        <i class="bi bi-cash-stack me-2"></i>

                        Detalle del Valor de Inventario

                    </h5>

                    <small>

                        Valor calculado según stock actual × precio de compra

                    </small>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>

            </div>


            <!-- BODY -->

            <div class="modal-body">

                <?php if (empty($productosInventario)): ?>

                    <div class="text-center py-5">

                        <i
                            class="bi bi-box-seam fs-1 text-muted"
                        ></i>

                        <h4 class="mt-3">

                            No hay productos

                        </h4>

                        <p class="text-muted">

                            No existen productos activos en el inventario.

                        </p>

                    </div>

                <?php else: ?>

                    <div
                        class="alert alert-info d-flex justify-content-between align-items-center"
                    >

                        <div>

                            <i
                                class="bi bi-calculator me-2"
                            ></i>

                            Total del inventario

                        </div>

                        <strong class="fs-5">

                            $

                            <?= number_format(
                                $valorInventario,
                                2,
                                ",",
                                "."
                            ) ?>

                        </strong>

                    </div>


                    <div class="table-responsive">

                        <table
                            class="table table-hover table-bordered align-middle"
                        >

                            <thead class="table-dark">

                                <tr>

                                    <th>ID</th>

                                    <th>Código</th>

                                    <th>Producto</th>

                                    <th class="text-center">
                                        Stock
                                    </th>

                                    <th class="text-end">
                                        Precio compra
                                    </th>

                                    <th class="text-end">
                                        Valor inventario
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $productosInventario
                                    as
                                    $productoInventario
                                ): ?>

                                    <tr>

                                        <td>

                                            <?= (int)$productoInventario["id"] ?>

                                        </td>


                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $productoInventario["codigo"]
                                                ) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $productoInventario["nombre"]
                                            ) ?>

                                        </td>


                                        <td class="text-center">

                                            <span class="badge bg-primary">

                                                <?= (int)$productoInventario["stock"] ?>

                                            </span>

                                        </td>


                                        <td class="text-end">

                                            $

                                            <?= number_format(
                                                (float)$productoInventario["precio_compra"],
                                                2,
                                                ",",
                                                "."
                                            ) ?>

                                        </td>


                                        <td class="text-end">

                                            <strong class="text-success">

                                                $

                                                <?= number_format(
                                                    (float)$productoInventario["valor_inventario"],
                                                    2,
                                                    ",",
                                                    "."
                                                ) ?>

                                            </strong>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>


                            <tfoot>

                                <tr class="table-warning">

                                    <th
                                        colspan="5"
                                        class="text-end"
                                    >

                                        VALOR TOTAL DEL INVENTARIO

                                    </th>

                                    <th class="text-end">

                                        $

                                        <?= number_format(
                                            $valorInventario,
                                            2,
                                            ",",
                                            "."
                                        ) ?>

                                    </th>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                <?php endif; ?>

            </div>


            <!-- FOOTER -->

            <div class="modal-footer">

                <span class="text-muted me-auto">

                    Productos activos:

                    <strong>

                        <?= count($productosInventario) ?>

                    </strong>

                </span>

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    <i class="bi bi-x-lg me-1"></i>

                    Cerrar

                </button>

            </div>

        </div>

    </div>

</div>
</body>



</html>