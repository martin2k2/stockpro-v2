<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

requireAdmin();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login/index.php");
    exit;
}

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

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
        c.id AS cliente_id,
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

?>
<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Etiqueta Pedido #<?= $pedido["id"] ?></title>

<style>

@page {
    size: 100mm 150mm;
    margin: 0;
}

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
    width: 100%;
    background: #fff;
    font-family: Arial, Helvetica, sans-serif;
}

.etiqueta {

    width: 100mm;
    height: 150mm;

    padding: 7mm;

    border: 1px solid #000;

    display: flex;
    flex-direction: column;
}

.encabezado {

    text-align: center;

    border-bottom: 2px solid #000;

    padding-bottom: 4mm;

    margin-bottom: 5mm;
}

.logo {

    font-size: 25px;

    font-weight: bold;

    letter-spacing: 1px;
}

.subtitulo {

    font-size: 12px;

    margin-top: 2mm;
}

.pedido {

    text-align: center;

    font-size: 22px;

    font-weight: bold;

    border: 2px solid #000;

    padding: 3mm;

    margin-bottom: 5mm;
}

.titulo {

    font-size: 12px;

    font-weight: bold;

    text-transform: uppercase;

    margin-bottom: 2mm;
}

.cliente {

    font-size: 19px;

    font-weight: bold;

    border-bottom: 1px solid #000;

    padding-bottom: 3mm;

    margin-bottom: 4mm;
}

.dato {

    font-size: 14px;

    margin-bottom: 3mm;

    line-height: 1.3;
}

.direccion {

    border: 2px solid #000;

    padding: 4mm;

    margin-top: 2mm;

    margin-bottom: 5mm;
}

.direccion .texto {

    font-size: 18px;

    font-weight: bold;

    line-height: 1.3;
}

.estado {

    margin-top: auto;

    text-align: center;

    border-top: 2px solid #000;

    padding-top: 4mm;

    font-size: 15px;

    font-weight: bold;
}

@media print {

    .no-print {
        display: none !important;
    }

    .etiqueta {
        border: none;
    }

}

</style>

</head>

<body>


<div class="etiqueta">


<div class="encabezado">

<div class="logo">

STOCK PRO

</div>

<div class="subtitulo">

ETIQUETA DE ENVÍO

</div>

</div>


<div class="pedido">

PEDIDO #<?= (int)$pedido["id"] ?>

</div>


<div class="titulo">

Cliente

</div>


<div class="cliente">

<?= htmlspecialchars(
    $pedido["apellido"] . " " . $pedido["nombre"]
) ?>

</div>


<div class="dato">

<strong>DNI:</strong>

<?= htmlspecialchars($pedido["dni"]) ?>

</div>


<div class="dato">

<strong>Teléfono:</strong>

<?= htmlspecialchars(
    $pedido["telefono"] ?: "No informado"
) ?>

</div>


<div class="dato">

<strong>Email:</strong>

<?= htmlspecialchars(
    $pedido["email"] ?: "No informado"
) ?>

</div>


<div class="direccion">


<div class="titulo">

DIRECCIÓN DE ENTREGA

</div>


<div class="texto">

<?= nl2br(
    htmlspecialchars(
        $pedido["direccion"] ?: "No informada"
    )
) ?>

</div>


</div>


<div class="dato">

<strong>Fecha:</strong>

<?= date(
    "d/m/Y H:i",
    strtotime($pedido["fecha"])
) ?>

</div>


<div class="estado">

PEDIDO PARA ENVÍO

</div>


</div>


<script>

window.onload = function () {

    window.print();

};

</script>


</body>

</html>