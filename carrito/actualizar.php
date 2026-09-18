<?php

session_start();

require_once "../config/conexion.php";

$db = Conexion::conectar();


$producto_id = isset($_POST['producto_id'])
    ? (int)$_POST['producto_id']
    : 0;

$variante_id = isset($_POST['variante_id'])
    ? (int)$_POST['variante_id']
    : 0;

$cantidad = isset($_POST['cantidad'])
    ? (int)$_POST['cantidad']
    : 0;

$clave = $_POST['clave'] ?? '';


/*
|--------------------------------------------------------------------------
| PRODUCTO CON VARIANTE
|--------------------------------------------------------------------------
*/

if ($variante_id > 0) {


    $stmt = $db->prepare("
        SELECT
            id,
            producto_id,
            stock,
            activo
        FROM producto_variantes
        WHERE id = ?
          AND producto_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $variante_id,
        $producto_id
    ]);

    $variante = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$variante || (int)$variante['activo'] !== 1) {

        header("Location: index.php");
        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | CLAVE CORRECTA
    |--------------------------------------------------------------------------
    */

    if ($clave === '') {

        $clave = 'v_' . $variante_id;

    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR
    |--------------------------------------------------------------------------
    */

    if ($cantidad < 1) {

        unset($_SESSION['carrito'][$clave]);

        header("Location: index.php");

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR STOCK DEL TALLE
    |--------------------------------------------------------------------------
    */

    $stock = (int)$variante['stock'];


    if ($cantidad > $stock) {

        $cantidad = $stock;

    }


    if ($cantidad <= 0) {

        unset($_SESSION['carrito'][$clave]);

        header("Location: index.php");

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR
    |--------------------------------------------------------------------------
    */

    if (isset($_SESSION['carrito'][$clave])
        && is_array($_SESSION['carrito'][$clave])) {

        $_SESSION['carrito'][$clave]['cantidad'] = $cantidad;

    } else {

        $_SESSION['carrito'][$clave] = [

            'producto_id' => $producto_id,

            'variante_id' => $variante_id,

            'cantidad' => $cantidad

        ];

    }


    header("Location: index.php");

    exit;

}


/*
|--------------------------------------------------------------------------
| PRODUCTO SIN VARIANTE
|--------------------------------------------------------------------------
*/

$claveProducto = $clave !== ''
    ? $clave
    : 'p_' . $producto_id;


/*
|--------------------------------------------------------------------------
| PRODUCTO
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        id,
        stock
    FROM productos
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $producto_id
]);

$producto = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$producto) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| ELIMINAR
|--------------------------------------------------------------------------
*/

if ($cantidad < 1) {

    unset($_SESSION['carrito'][$claveProducto]);

    header("Location: index.php");

    exit;

}


/*
|--------------------------------------------------------------------------
| VALIDAR STOCK
|--------------------------------------------------------------------------
*/

$stock = (int)$producto['stock'];


if ($cantidad > $stock) {

    $cantidad = $stock;

}


if ($cantidad <= 0) {

    unset($_SESSION['carrito'][$claveProducto]);

    header("Location: index.php");

    exit;

}


/*
|--------------------------------------------------------------------------
| ACTUALIZAR PRODUCTO NORMAL
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['carrito'][$claveProducto])
    && is_array($_SESSION['carrito'][$claveProducto])
) {

    $_SESSION['carrito'][$claveProducto]['cantidad'] =
        $cantidad;

} else {

    $_SESSION['carrito'][$claveProducto] = [

        'producto_id' => $producto_id,

        'variante_id' => 0,

        'cantidad' => $cantidad,

        'talle' => null,

        'color' => null

    ];

}


header("Location: index.php");

exit;