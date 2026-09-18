<?php

session_start();

require_once "../config/conexion.php";

$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

$producto_id = isset($_POST['producto_id'])
    ? (int)$_POST['producto_id']
    : 0;

$variante_id = isset($_POST['variante_id'])
    ? (int)$_POST['variante_id']
    : 0;

$cantidad = isset($_POST['cantidad'])
    ? (int)$_POST['cantidad']
    : 1;


if ($producto_id <= 0) {

    header("Location: ../tienda/index.php");
    exit;

}


if ($cantidad < 1) {

    $cantidad = 1;

}


/*
|--------------------------------------------------------------------------
| OBTENER PRODUCTO
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

$producto = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$producto) {

    header("Location: ../tienda/index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| CARRITO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['carrito'])) {

    $_SESSION['carrito'] = [];

}


/*
|--------------------------------------------------------------------------
| PRODUCTO CON VARIANTE / TALLE
|--------------------------------------------------------------------------
*/

if ($variante_id > 0) {


    /*
    |--------------------------------------------------------------------------
    | OBTENER VARIANTE
    |--------------------------------------------------------------------------
    */

    $stmtVariante = $db->prepare("
        SELECT
            id,
            producto_id,
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

    $variante = $stmtVariante->fetch(PDO::FETCH_ASSOC);


    if (!$variante) {

        header(
            "Location: ../tienda/index.php?error=variante"
        );

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | CLAVE DEL CARRITO
    |--------------------------------------------------------------------------
    |
    | Producto + talle/color son una línea independiente.
    |
    */

    $clave = 'v_' . $variante_id;


    /*
    |--------------------------------------------------------------------------
    | CANTIDAD YA EXISTENTE
    |--------------------------------------------------------------------------
    */

    $cantidadActual = 0;


    if (isset($_SESSION['carrito'][$clave])) {

        $cantidadActual = (int)(
            $_SESSION['carrito'][$clave]['cantidad'] ?? 0
        );

    }


    $nuevaCantidad =
        $cantidadActual + $cantidad;


    /*
    |--------------------------------------------------------------------------
    | VALIDAR STOCK DEL TALLE
    |--------------------------------------------------------------------------
    */

    $stockDisponible = (int)$variante['stock'];


    if ($nuevaCantidad > $stockDisponible) {

        $_SESSION['carrito_error'] =
            "No hay suficiente stock para el talle " .
            $variante['talle'] .
            ".";

        header("Location: ../tienda/index.php");

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | GUARDAR EN CARRITO
    |--------------------------------------------------------------------------
    */

    $_SESSION['carrito'][$clave] = [

        'producto_id' => $producto_id,

        'variante_id' => $variante_id,

        'cantidad' => $nuevaCantidad,

        'talle' => $variante['talle'],

        'color' => $variante['color'],

        'codigo' => $variante['codigo'],

        'nombre' => $producto['nombre'],

        'precio' => (float)$producto['precio_venta']

    ];


    header("Location: ../carrito/index.php");

    exit;

}


/*
|--------------------------------------------------------------------------
| PRODUCTO SIN VARIANTE
|--------------------------------------------------------------------------
*/

$clave = 'p_' . $producto_id;


$cantidadActual = 0;


if (isset($_SESSION['carrito'][$clave])) {

    $cantidadActual = (int)(
        $_SESSION['carrito'][$clave]['cantidad'] ?? 0
    );

}


$nuevaCantidad =
    $cantidadActual + $cantidad;


/*
|--------------------------------------------------------------------------
| VALIDAR STOCK NORMAL
|--------------------------------------------------------------------------
*/

$stockDisponible = (int)$producto['stock'];


if ($nuevaCantidad > $stockDisponible) {

    $_SESSION['carrito_error'] =
        "No hay suficiente stock disponible.";

    header("Location: ../tienda/index.php");

    exit;

}


/*
|--------------------------------------------------------------------------
| GUARDAR PRODUCTO NORMAL
|--------------------------------------------------------------------------
*/

$_SESSION['carrito'][$clave] = [

    'producto_id' => $producto_id,

    'variante_id' => 0,

    'cantidad' => $nuevaCantidad,

    'talle' => null,

    'color' => null,

    'codigo' => null,

    'nombre' => $producto['nombre'],

    'precio' => (float)$producto['precio_venta']

];


header("Location: ../carrito/index.php");

exit;