<?php

session_start();

require_once "../config/conexion.php";

$db = Conexion::conectar();

$carrito = $_SESSION['carrito'] ?? [];

$total = 0;

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Carrito - Tienda Urqui</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<style>

/* =========================================================
   GENERAL
========================================================= */

body {
    background: #f4f6f9;
    min-height: 100vh;
}

.contenedor-carrito {
    max-width: 1100px;
    margin: 0 auto;
    padding: 30px 15px 50px;
}

.titulo-carrito {
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 25px;
    color: #212529;
}


/* =========================================================
   CARD
========================================================= */

.card-carrito {
    border: none;
    border-radius: 14px;
    overflow: hidden;
}

.card-carrito .card-body {
    padding: 25px;
}


/* =========================================================
   TABLA
========================================================= */

.tabla-carrito {
    vertical-align: middle;
    margin-bottom: 25px;
}

.tabla-carrito th {
    white-space: nowrap;
}

.tabla-carrito td {
    vertical-align: middle;
}

.precio {
    white-space: nowrap;
    font-weight: 600;
}

.subtotal {
    white-space: nowrap;
    font-weight: 700;
}

.form-cantidad {
    min-width: 120px;
}

.input-cantidad {
    width: 100px;
}

.info-variante {
    margin-top: 5px;
    font-size: 13px;
}

.badge-talle {
    font-size: 12px;
}

.stock-info {
    font-size: 12px;
    color: #6c757d;
}


/* =========================================================
   TOTAL
========================================================= */

.total-label {
    font-size: 18px;
    font-weight: 700;
}

.total-importe {
    font-size: 21px;
    font-weight: 700;
    color: #198754;
    white-space: nowrap;
}


/* =========================================================
   BOTONES
========================================================= */

.botones-carrito {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.botones-carrito .btn {
    white-space: nowrap;
}


/* =========================================================
   CARRITO VACÍO
========================================================= */

.carrito-vacio {
    text-align: center;
    padding: 45px 20px;
}

.carrito-vacio i {
    font-size: 55px;
    color: #adb5bd;
}

.carrito-vacio h4 {
    margin-top: 15px;
    margin-bottom: 10px;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 768px) {

    .contenedor-carrito {
        padding: 20px 12px 40px;
    }

    .titulo-carrito {
        font-size: 25px;
    }

    .card-carrito .card-body {
        padding: 15px;
    }

    .tabla-carrito {
        font-size: 14px;
    }

    .input-cantidad {
        width: 85px;
    }

}


/* =========================================================
   CELULAR
========================================================= */

@media (max-width: 576px) {

    .contenedor-carrito {
        padding: 15px 10px 35px;
    }

    .titulo-carrito {
        font-size: 23px;
        margin-bottom: 18px;
    }

    .card-carrito {
        border-radius: 10px;
    }

    .card-carrito .card-body {
        padding: 10px;
    }

    .tabla-carrito {
        border: 0;
        margin-bottom: 20px;
    }

    .tabla-carrito thead {
        display: none;
    }

    .tabla-carrito tbody,
    .tabla-carrito tr,
    .tabla-carrito td {
        display: block;
        width: 100%;
    }

    .tabla-carrito tbody tr {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 15px;
        padding: 12px;
        box-shadow: 0 2px 7px rgba(0,0,0,.05);
    }

    .tabla-carrito tbody td {
        border: 0;
        border-bottom: 1px solid #f0f0f0;
        padding: 10px 5px;
        text-align: left;
    }

    .tabla-carrito tbody td:last-child {
        border-bottom: 0;
    }

    .tabla-carrito tbody td::before {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #6c757d;
        margin-bottom: 4px;
        text-transform: uppercase;
    }

    .tabla-carrito tbody td:nth-child(1)::before {
        content: "Producto";
    }

    .tabla-carrito tbody td:nth-child(2)::before {
        content: "Precio";
    }

    .tabla-carrito tbody td:nth-child(3)::before {
        content: "Cantidad";
    }

    .tabla-carrito tbody td:nth-child(4)::before {
        content: "Subtotal";
    }

    .tabla-carrito tbody td:nth-child(5)::before {
        content: "Acción";
    }

    .form-cantidad {
        width: 100%;
        min-width: 0;
    }

    .input-cantidad {
        width: 100%;
        max-width: 140px;
    }

    .form-cantidad .btn {
        margin-top: 8px;
    }

    .tabla-carrito tbody td:nth-child(5) .btn {
        width: 100%;
    }

    .tabla-carrito tfoot {
        display: block;
        width: 100%;
        background: #fff;
        border-radius: 10px;
        margin-top: 10px;
    }

    .tabla-carrito tfoot tr {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 10px;
    }

    .tabla-carrito tfoot th {
        border: 0;
        padding: 0;
        width: auto;
    }

    .total-label {
        font-size: 16px;
    }

    .total-importe {
        font-size: 19px;
    }

    .botones-carrito {
        display: flex;
        flex-direction: column;
        gap: 9px;
        margin-top: 15px;
    }

    .botones-carrito .btn {
        width: 100%;
        padding: 11px;
    }

}

</style>

</head>


<body>


<div class="contenedor-carrito">


    <h2 class="titulo-carrito">

        <i class="bi bi-cart3"></i>

        Mi carrito

    </h2>


    <div class="card shadow-sm card-carrito">

        <div class="card-body">


            <?php if (empty($carrito)): ?>


                <div class="carrito-vacio">

                    <i class="bi bi-cart-x"></i>

                    <h4>
                        Tu carrito está vacío
                    </h4>

                    <p class="text-muted">
                        Todavía no agregaste productos.
                    </p>

                    <a
                        href="../tienda/index.php"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-shop"></i>

                        Ir a la tienda

                    </a>

                </div>


            <?php else: ?>


                <div class="table-responsive">

                    <table class="table table-bordered tabla-carrito">


                        <thead class="table-dark">

                            <tr>

                                <th>
                                    Producto
                                </th>

                                <th>
                                    Precio
                                </th>

                                <th>
                                    Cantidad
                                </th>

                                <th>
                                    Subtotal
                                </th>

                                <th>
                                    Acción
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($carrito as $clave => $item): ?>


                            <?php

                            /*
                            |--------------------------------------------------------------------------
                            | COMPATIBILIDAD CON EL CARRITO ANTERIOR
                            |--------------------------------------------------------------------------
                            */

                            if (is_numeric($clave) && is_numeric($item)) {

                                $producto_id = (int)$clave;

                                $variante_id = 0;

                                $cantidad = (int)$item;

                            } else {

                                $producto_id = (int)(
                                    $item['producto_id'] ?? 0
                                );

                                $variante_id = (int)(
                                    $item['variante_id'] ?? 0
                                );

                                $cantidad = (int)(
                                    $item['cantidad'] ?? 1
                                );

                            }


                            if ($producto_id <= 0) {
                                continue;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | PRODUCTO
                            |--------------------------------------------------------------------------
                            */

                            $stmt = $db->prepare("
                                SELECT
                                    id,
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
                            | STOCK Y VARIANTE
                            |--------------------------------------------------------------------------
                            */

                            $stockDisponible = (int)$p['stock'];

                            $talle = null;
                            $color = null;


                            if ($variante_id > 0) {


                                $stmtVariante = $db->prepare("
                                    SELECT
                                        id,
                                        talle,
                                        color,
                                        stock,
                                        codigo
                                    FROM producto_variantes
                                    WHERE id = ?
                                      AND producto_id = ?
                                      AND activo = 1
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


                                $stockDisponible =
                                    (int)$variante['stock'];

                                $talle =
                                    $variante['talle'];

                                $color =
                                    $variante['color'];

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | AJUSTAR CANTIDAD
                            |--------------------------------------------------------------------------
                            */

                            if ($cantidad < 1) {
                                $cantidad = 1;
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
                            | SUBTOTAL
                            |--------------------------------------------------------------------------
                            */

                            $subtotal =
                                $cantidad *
                                (float)$p['precio_venta'];


                            $total += $subtotal;

                            ?>


                            <tr>


                                <!-- PRODUCTO -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $p['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </strong>


                                    <?php if ($variante_id > 0): ?>


                                        <div class="info-variante">


                                            <span class="badge bg-primary badge-talle">

                                                <i class="bi bi-tags"></i>

                                                Talle:

                                                <?= htmlspecialchars(
                                                    $talle,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </span>


                                            <?php if (!empty($color)): ?>


                                                <span class="badge bg-secondary badge-talle">

                                                    Color:

                                                    <?= htmlspecialchars(
                                                        $color,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                </span>


                                            <?php endif; ?>


                                        </div>


                                        <div class="stock-info">

                                            Stock disponible:

                                            <?= $stockDisponible ?>

                                        </div>


                                    <?php endif; ?>


                                </td>


                                <!-- PRECIO -->

                                <td class="precio">

                                    $

                                    <?= number_format(
                                        (float)$p['precio_venta'],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </td>


                                <!-- CANTIDAD -->

                                <td>

                                    <form
                                        action="actualizar.php"
                                        method="POST"
                                        class="form-cantidad"
                                    >


                                        <input
                                            type="hidden"
                                            name="producto_id"
                                            value="<?= $producto_id ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="variante_id"
                                            value="<?= $variante_id ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="clave"
                                            value="<?= htmlspecialchars(
                                                $clave,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >


                                        <input
                                            type="number"
                                            name="cantidad"
                                            value="<?= $cantidad ?>"
                                            min="1"
                                            max="<?= $stockDisponible ?>"
                                            class="form-control input-cantidad"
                                            required
                                        >


                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-primary"
                                        >

                                            <i class="bi bi-arrow-repeat"></i>

                                            Actualizar

                                        </button>


                                    </form>

                                </td>


                                <!-- SUBTOTAL -->

                                <td class="subtotal">

                                    $

                                    <?= number_format(
                                        $subtotal,
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </td>


                                <!-- ELIMINAR -->

                                <td>

                                    <a
                                        href="eliminar.php?clave=<?= urlencode($clave) ?>"
                                        class="btn btn-danger btn-sm"
                                    >

                                        <i class="bi bi-trash"></i>

                                        Eliminar

                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                        <tfoot>

                            <tr>

                                <th
                                    colspan="3"
                                    class="total-label"
                                >

                                    TOTAL

                                </th>


                                <th
                                    colspan="2"
                                    class="total-importe"
                                >

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


                <!-- BOTONES -->

                <div class="botones-carrito">


                    <a
                        href="../tienda/index.php"
                        class="btn btn-secondary"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Seguir comprando

                    </a>


                    <a
                        href="../checkout/index.php"
                        class="btn btn-success"
                    >

                        <i class="bi bi-credit-card"></i>

                        Finalizar compra

                    </a>


                    <a
                        href="vaciar.php"
                        class="btn btn-danger"
                    >

                        <i class="bi bi-trash"></i>

                        Vaciar carrito

                    </a>


                </div>


            <?php endif; ?>


        </div>

    </div>

</div>


</body>

</html>