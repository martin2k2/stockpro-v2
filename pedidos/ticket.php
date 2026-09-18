<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

requireAdmin();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login/index.php");
    exit;
}

$id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if ($id <= 0) {
    die("Pedido inválido.");
}

$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| PEDIDO + CLIENTE
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.id,
        p.fecha,
        p.total,
        p.estado,
        p.metodo_pago,
        c.dni,
        c.apellido,
        c.nombre,
        c.telefono,
        c.direccion,
        c.email
    FROM pedidos p
    INNER JOIN clientes c
        ON c.id = p.cliente_id
    WHERE p.id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("Pedido no encontrado.");
}


/*
|--------------------------------------------------------------------------
| DETALLE DEL PEDIDO + VARIANTE
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        pd.id,
        pd.producto_id,
        pd.variante_id,
        pd.cantidad,
        pd.precio,
        pd.subtotal,

        pr.codigo,
        pr.nombre,

        pv.talle,
        pv.color

    FROM pedido_detalle pd

    INNER JOIN productos pr
        ON pr.id = pd.producto_id

    LEFT JOIN producto_variantes pv
        ON pv.id = pd.variante_id

    WHERE pd.pedido_id = ?

    ORDER BY pd.id ASC
");

$stmt->execute([$id]);

$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Ticket Pedido #<?= $pedido["id"] ?></title>

<style>

@page {
    size: 80mm auto;
    margin: 0;
}

* {
    box-sizing: border-box;
}

html,
body {

    margin: 0;
    padding: 0;

    width: 80mm;

    background: #fff;

    font-family:
        "Courier New",
        Courier,
        monospace;

}

.ticket {

    width: 80mm;

    padding: 5mm;

    font-size: 12px;

}

.centro {
    text-align: center;
}

.logo {

    font-size: 22px;

    font-weight: bold;

}

.titulo {

    font-size: 15px;

    font-weight: bold;

}

.negrita {
    font-weight: bold;
}

.linea {

    border-top: 1px dashed #000;

    margin: 3mm 0;

}

.datos {

    line-height: 1.5;

}

.producto {

    margin-bottom: 3mm;

}

.producto-nombre {

    font-weight: bold;

    font-size: 12px;

}

.variante {

    margin-top: 1mm;

    margin-bottom: 1mm;

    line-height: 1.4;

}

.producto-linea {

    display: flex;

    justify-content: space-between;

}

.total {

    font-size: 18px;

    font-weight: bold;

    display: flex;

    justify-content: space-between;

}

.estado {

    margin-top: 5mm;

    text-align: center;

    font-weight: bold;

    font-size: 14px;

}

@media print {

    .no-print {
        display: none !important;
    }

}

</style>

</head>

<body>


<div class="ticket">


<div class="centro">

    <div class="logo">
        STOCK PRO
    </div>

    <div>
        DETALLE DE PEDIDO
    </div>

</div>


<div class="linea"></div>


<div class="datos">

<strong>PEDIDO:</strong>
#<?= (int)$pedido["id"] ?>

<br>

<strong>FECHA:</strong>
<?= date(
    "d/m/Y H:i",
    strtotime($pedido["fecha"])
) ?>

<br>

<strong>CLIENTE:</strong>
<?= htmlspecialchars(
    $pedido["apellido"] . " " . $pedido["nombre"]
) ?>

<br>

<strong>DNI:</strong>
<?= htmlspecialchars($pedido["dni"]) ?>

<br>

<strong>TEL:</strong>
<?= htmlspecialchars(
    $pedido["telefono"] ?: "-"
) ?>

</div>


<div class="linea"></div>


<div class="centro negrita">

PRODUCTOS

</div>


<div class="linea"></div>


<?php foreach ($detalles as $detalle): ?>


<div class="producto">


<div class="producto-nombre">

<?= htmlspecialchars($detalle["nombre"]) ?>

</div>


<?php if (!empty($detalle["codigo"])): ?>

<div>

Código:
<?= htmlspecialchars($detalle["codigo"]) ?>

</div>

<?php endif; ?>


<?php if (
    !empty($detalle["talle"]) ||
    !empty($detalle["color"])
): ?>

<div class="variante">

<?php if (!empty($detalle["talle"])): ?>

<div>

<strong>Talle:</strong>
<?= htmlspecialchars($detalle["talle"]) ?>

</div>

<?php endif; ?>


<?php if (!empty($detalle["color"])): ?>

<div>

<strong>Color:</strong>
<?= htmlspecialchars($detalle["color"]) ?>

</div>

<?php endif; ?>

</div>

<?php endif; ?>


<div class="producto-linea">

<span>

<?= number_format(
    (float)$detalle["cantidad"],
    0,
    ",",
    "."
) ?>

x

$

<?= number_format(
    (float)$detalle["precio"],
    2,
    ",",
    "."
) ?>

</span>


<span>

$

<?= number_format(
    (float)$detalle["subtotal"],
    2,
    ",",
    "."
) ?>

</span>

</div>


</div>


<?php endforeach; ?>


<div class="linea"></div>


<div class="total">

<span>
TOTAL
</span>

<span>

$

<?= number_format(
    (float)$pedido["total"],
    2,
    ",",
    "."
) ?>

</span>

</div>


<div class="linea"></div>


<div class="datos">

<strong>FORMA DE PAGO:</strong>

<?= htmlspecialchars(
    $pedido["metodo_pago"] ?: "-"
) ?>

</div>


<div class="estado">

PEDIDO PARA PREPARAR

</div>


<div class="centro" style="margin-top:8mm;">

Gracias por su compra

</div>


</div>


<script>

window.onload = function () {

    window.print();

};

</script>


</body>

</html>