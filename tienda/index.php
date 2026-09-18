<?php

session_start();

require_once "../config/conexion.php";

$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| OBTENER PRODUCTOS
|--------------------------------------------------------------------------
*/

$stmt = $db->query("

    SELECT
        p.*,
        c.nombre AS categoria
    FROM productos p
    LEFT JOIN categorias c
        ON c.id = p.categoria_id
    WHERE p.activo = 1
    ORDER BY p.id DESC

");

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| OBTENER VARIANTES / TALLES
|--------------------------------------------------------------------------
*/

$stmtVariantes = $db->query("

    SELECT
        id,
        producto_id,
        talle,
        color,
        stock,
        codigo
    FROM producto_variantes
    WHERE activo = 1
    ORDER BY
        producto_id ASC,
        talle ASC,
        color ASC

");

$variantesDB = $stmtVariantes->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| AGRUPAR VARIANTES POR PRODUCTO
|--------------------------------------------------------------------------
*/

$variantesPorProducto = [];

foreach ($variantesDB as $variante) {

    $productoId = (int)$variante['producto_id'];

    if (!isset($variantesPorProducto[$productoId])) {

        $variantesPorProducto[$productoId] = [];

    }

    $variantesPorProducto[$productoId][] = $variante;
}


/*
|--------------------------------------------------------------------------
| STOCK TOTAL DE VARIANTES
|--------------------------------------------------------------------------
*/

$stockVariantes = [];

foreach ($variantesPorProducto as $productoId => $variantes) {

    $total = 0;

    foreach ($variantes as $variante) {

        $total += (int)$variante['stock'];

    }

    $stockVariantes[$productoId] = $total;
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

<title>Tienda Urqui</title>


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
    background: #f5f7f6;
    color: #1f2937;
}


/* =====================================================
   NAVBAR
===================================================== */

.navbar-tienda {
    background: #198754;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .12);
}

.navbar-brand {
    font-size: 1.25rem;
    font-weight: 600;
}

.btn-carrito {
    border-radius: 8px;
    font-weight: 600;
    padding: 9px 18px;
}


/* =====================================================
   CONTENEDOR
===================================================== */

.tienda-container {
    max-width: 1200px;
}


/* =====================================================
   TITULO
===================================================== */

.titulo-productos {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 30px;
    position: relative;
    padding-bottom: 12px;
}

.titulo-productos::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 0;
    width: 55px;
    height: 4px;
    background: #198754;
    border-radius: 10px;
}


/* =====================================================
   TARJETA PRODUCTO
===================================================== */

.producto-card {
    border: 0;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 4px 18px rgba(0, 0, 0, .08);
    transition: transform .2s ease, box-shadow .2s ease;
    height: 100%;
}

.producto-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(0, 0, 0, .13);
}


/* =====================================================
   IMAGEN
===================================================== */

.producto-imagen {
    width: 100%;
    height: 250px;
    background: #f8f9fa;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;

    border-bottom: 1px solid #eeeeee;
}

.producto-imagen img {
    width: auto;
    height: auto;

    max-width: 94%;
    max-height: 94%;

    object-fit: contain;

    display: block;

    transition: transform .25s ease;
}

.producto-card:hover .producto-imagen img {
    transform: scale(1.03);
}


/* =====================================================
   CUERPO
===================================================== */

.producto-body {
    padding: 18px;
}

.producto-nombre {
    font-size: 1.15rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.producto-categoria {
    color: #6c757d;
    font-size: .92rem;
    margin-bottom: 12px;
}

.producto-categoria i {
    color: #198754;
}


/* =====================================================
   PRECIO
===================================================== */

.producto-precio {
    color: #198754;
    font-size: 1.45rem;
    font-weight: 700;
    margin-bottom: 15px;
}


/* =====================================================
   TALLES
===================================================== */

.talles-label {
    font-size: .9rem;
    font-weight: 600;
    margin-bottom: 6px;
}

.talle-select {
    height: 42px;
    border-radius: 8px;
}

.talle-select:focus {
    border-color: #198754;
    box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .15);
}

.info-stock-talle {
    font-size: .82rem;
    color: #6c757d;
    margin-top: 5px;
}


/* =====================================================
   CANTIDAD
===================================================== */

.cantidad-input {
    height: 42px;
    border-radius: 8px;
    border: 1px solid #ced4da;
    text-align: center;
}

.cantidad-input:focus {
    border-color: #198754;
    box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .15);
}


/* =====================================================
   BOTONES
===================================================== */

.btn-agregar {
    height: 42px;
    border-radius: 8px;
    font-weight: 600;
    border: 0;
}

.btn-detalle {
    height: 42px;
    border-radius: 8px;
    font-weight: 600;
}

.btn-agregar:hover {
    background-color: #157347;
}

.btn-sin-stock {
    height: 42px;
    border-radius: 8px;
    font-weight: 600;
}


/* =====================================================
   INFORMACION INFERIOR
===================================================== */

.beneficios {
    margin-top: 45px;
    margin-bottom: 40px;
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
}

.beneficio {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
}

.beneficio i {
    font-size: 1.8rem;
    color: #198754;
}

.beneficio strong {
    display: block;
    font-size: .95rem;
}

.beneficio span {
    display: block;
    color: #6c757d;
    font-size: .82rem;
}


/* =========================================================
   LOGOS DE LA TIENDA
========================================================= */

.logo-tienda,
.logo-club {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;
}

.logo-tienda i {
    font-size: 30px;
    line-height: 1;
}

.logo-club img {
    width: 42px;
    height: 42px;

    object-fit: contain;

    display: block;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 767px) {

    .tienda-container {
        padding-left: 15px;
        padding-right: 15px;
    }

    .titulo-productos {
        font-size: 1.7rem;
    }

    .producto-imagen {
        height: 230px;
    }

    .producto-imagen img {
        max-width: 90%;
        max-height: 90%;
    }

    .producto-body {
        padding: 16px;
    }

    .beneficio {
        justify-content: flex-start;
    }

}


/* =====================================================
   TABLET
===================================================== */

@media (min-width: 768px) and (max-width: 991px) {

    .producto-imagen {
        height: 230px;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-dark navbar-tienda">

    <div class="container">


        <a
            class="navbar-brand d-flex align-items-center gap-2"
            href="index.php"
        >

            <!--<div class="logo-tienda">
                <i class="bi bi-shop"></i>
            </div>-->

            <div class="logo-club">

                <img
                    src="../assets/img/LOGO NUEVO.png"
                    alt="Logo del club"
                >

            </div>

            <span>
                Tienda Urqui
            </span>

        </a>


        <a
            href="../carrito/index.php"
            class="btn btn-light btn-carrito"
        >

            <i class="bi bi-cart3 me-1"></i>

            Carrito

        </a>


    </div>

</nav>


<!-- =========================================================
     CONTENIDO
========================================================= -->

<div class="container tienda-container py-5">


    <h1 class="titulo-productos">

        Productos

    </h1>


    <div class="row g-4">


        <?php foreach ($productos as $p): ?>


            <?php

            $productoId = (int)$p['id'];

            $tieneVariantes = isset(
                $variantesPorProducto[$productoId]
            ) && count(
                $variantesPorProducto[$productoId]
            ) > 0;


            if ($tieneVariantes) {

                $stockDisponible = (int)(
                    $stockVariantes[$productoId] ?? 0
                );

            } else {

                $stockDisponible = (int)$p['stock'];

            }

            ?>


            <div class="col-12 col-md-6 col-lg-4">


                <div class="producto-card">


                    <!-- =================================================
                         IMAGEN
                    ================================================== -->

                    <div class="producto-imagen">


                        <?php if (!empty($p['imagen'])): ?>


                            <img
                                src="../uploads/productos/<?= htmlspecialchars(
                                    $p['imagen'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $p['nombre'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                loading="lazy"
                            >


                        <?php else: ?>


                            <img
                                src="../assets/img/sin-imagen.png"
                                alt="Sin imagen"
                                loading="lazy"
                            >


                        <?php endif; ?>


                    </div>


                    <!-- =================================================
                         INFORMACION
                    ================================================== -->

                    <div class="producto-body">


                        <div class="producto-nombre">

                            <?= htmlspecialchars(
                                $p['nombre'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>


                        <div class="producto-categoria">

                            <i class="bi bi-tag-fill me-1"></i>

                            Categoría:

                            <?= htmlspecialchars(
                                $p['categoria'] ?? 'Sin categoría',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>


                        <div class="producto-precio">

                            $ <?= number_format(
                                (float)$p['precio_venta'],
                                2,
                                ",",
                                "."
                            ) ?>

                        </div>


                        <?php if ($tieneVariantes): ?>


                            <?php if ($stockDisponible > 0): ?>


                                <form
                                    action="../carrito/agregar.php"
                                    method="POST"
                                >


                                    <input
                                        type="hidden"
                                        name="producto_id"
                                        value="<?= $productoId ?>"
                                    >


                                    <!-- =================================================
                                         TALLE
                                    ================================================== -->

                                    <div class="mb-2">


                                        <label
                                            class="form-label talles-label"
                                            for="talle_<?= $productoId ?>"
                                        >

                                            <i class="bi bi-tags me-1"></i>

                                            Seleccioná el talle

                                        </label>


                                        <select
                                            name="variante_id"
                                            id="talle_<?= $productoId ?>"
                                            class="form-select talle-select variante-select"
                                            data-producto="<?= $productoId ?>"
                                            required
                                        >

                                            <option
                                                value=""
                                                data-stock="0"
                                            >

                                                Seleccionar talle...

                                            </option>


                                            <?php foreach (
                                                $variantesPorProducto[$productoId]
                                                as $variante
                                            ): ?>


                                                <?php

                                                $stockTalle =
                                                    (int)$variante['stock'];

                                                ?>


                                                <?php if ($stockTalle > 0): ?>


                                                    <option
                                                        value="<?= (int)$variante['id'] ?>"
                                                        data-stock="<?= $stockTalle ?>"
                                                    >

                                                        <?= htmlspecialchars(
                                                            $variante['talle'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>


                                                        <?php if (!empty($variante['color'])): ?>

                                                            -
                                                            <?= htmlspecialchars(
                                                                $variante['color'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>

                                                        <?php endif; ?>


                                                        - Stock:
                                                        <?= $stockTalle ?>

                                                    </option>


                                                <?php endif; ?>


                                            <?php endforeach; ?>


                                        </select>


                                        <div
                                            class="info-stock-talle"
                                            id="stock_info_<?= $productoId ?>"
                                        >

                                            Seleccioná un talle.

                                        </div>


                                    </div>


                                    <!-- =================================================
                                         CANTIDAD
                                    ================================================== -->

                                    <input
                                        type="number"
                                        name="cantidad"
                                        value="1"
                                        min="1"
                                        max="1"
                                        class="form-control cantidad-input mb-2 cantidad-variante"
                                        data-producto="<?= $productoId ?>"
                                        aria-label="Cantidad"
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-success btn-agregar w-100"
                                    >

                                        <i class="bi bi-cart-plus me-1"></i>

                                        Agregar al carrito

                                    </button>


                                </form>


                            <?php else: ?>


                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sin-stock w-100"
                                    disabled
                                >

                                    <i class="bi bi-x-circle me-1"></i>

                                    Sin stock

                                </button>


                            <?php endif; ?>


                        <?php else: ?>


                            <!-- =================================================
                                 PRODUCTO SIN TALLE
                            ================================================== -->


                            <?php if ($stockDisponible > 0): ?>


                                <form
                                    action="../carrito/agregar.php"
                                    method="POST"
                                >


                                    <input
                                        type="hidden"
                                        name="producto_id"
                                        value="<?= $productoId ?>"
                                    >


                                    <input
                                        type="number"
                                        name="cantidad"
                                        value="1"
                                        min="1"
                                        max="<?= $stockDisponible ?>"
                                        class="form-control cantidad-input mb-2"
                                        aria-label="Cantidad"
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-success btn-agregar w-100"
                                    >

                                        <i class="bi bi-cart-plus me-1"></i>

                                        Agregar al carrito

                                    </button>


                                </form>


                            <?php else: ?>


                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sin-stock w-100"
                                    disabled
                                >

                                    <i class="bi bi-x-circle me-1"></i>

                                    Sin stock

                                </button>


                            <?php endif; ?>


                        <?php endif; ?>


                        <!-- =================================================
                             DETALLE
                        ================================================== -->


                        <a
                            href="producto.php?id=<?= $productoId ?>"
                            class="btn btn-primary btn-detalle w-100 mt-2"
                        >

                            <i class="bi bi-eye me-1"></i>

                            Ver detalle

                        </a>


                    </div>


                </div>


            </div>


        <?php endforeach; ?>


    </div>


    <!-- =========================================================
         BENEFICIOS
    ========================================================== -->

    <div class="beneficios">


        <div class="row g-3">


            <div class="col-12 col-md-6 col-lg-3">

                <div class="beneficio">

                    <i class="bi bi-shield-check"></i>

                    <div>

                        <strong>
                            Compra segura
                        </strong>

                        <span>
                            Tus datos protegidos
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-12 col-md-6 col-lg-3">

                <div class="beneficio">

                    <i class="bi bi-patch-check"></i>

                    <div>

                        <strong>
                            Productos oficiales
                        </strong>

                        <span>
                            Calidad garantizada
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-12 col-md-6 col-lg-3">

                <div class="beneficio">

                    <i class="bi bi-headset"></i>

                    <div>

                        <strong>
                            Atención al cliente
                        </strong>

                        <span>
                            Estamos para ayudarte
                        </span>

                    </div>

                </div>

            </div>


        </div>


    </div>


</div>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const selects = document.querySelectorAll(
            ".variante-select"
        );


        selects.forEach(function (select) {


            select.addEventListener(
                "change",
                function () {


                    const productoId =
                        this.dataset.producto;


                    const option =
                        this.options[
                            this.selectedIndex
                        ];


                    const stock =
                        parseInt(
                            option.dataset.stock || 0
                        );


                    const cantidad =
                        document.querySelector(
                            '.cantidad-variante[data-producto="' +
                            productoId +
                            '"]'
                        );


                    const info =
                        document.getElementById(
                            "stock_info_" +
                            productoId
                        );


                    if (stock > 0) {


                        if (cantidad) {

                            cantidad.max = stock;

                            if (
                                parseInt(
                                    cantidad.value
                                ) > stock
                            ) {

                                cantidad.value = stock;

                            }

                        }


                        if (info) {

                            info.innerHTML =
                                "Stock disponible: <strong>" +
                                stock +
                                "</strong>";

                        }


                    } else {


                        if (cantidad) {

                            cantidad.max = 1;

                            cantidad.value = 1;

                        }


                        if (info) {

                            info.textContent =
                                "Seleccioná un talle.";

                        }

                    }


                }
            );


        });


    }
);

</script>


</body>

</html>