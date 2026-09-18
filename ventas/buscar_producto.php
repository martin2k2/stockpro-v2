<?php

session_start();

require_once "../config/conexion.php";

header("Content-Type: application/json; charset=UTF-8");


/*
|--------------------------------------------------------------------------
| VALIDAR SESIÓN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["usuario"])) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Sesión no válida."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER CÓDIGO
|--------------------------------------------------------------------------
*/

$codigo = trim($_GET["codigo"] ?? "");

if ($codigo === "") {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Ingrese un código."
    ]);

    exit;
}


try {

    $db = Conexion::conectar();


    /*
    |--------------------------------------------------------------------------
    | BUSCAR PRODUCTO NORMAL O VARIANTE
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("

        SELECT

            p.id,
            p.codigo,
            p.nombre,
            p.precio_venta,
            p.stock,
            p.imagen,

            NULL AS variante_id,
            NULL AS talle,
            NULL AS color,

            0 AS es_variante

        FROM productos p

        WHERE p.codigo = ?

        LIMIT 1

    ");

    $stmt->execute([
        $codigo
    ]);

    $producto = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | SI NO ENCUENTRA PRODUCTO, BUSCAR VARIANTE
    |--------------------------------------------------------------------------
    */

    if (!$producto) {

        $stmtVariante = $db->prepare("

            SELECT

                p.id,
                p.codigo,
                p.nombre,
                p.precio_venta,
                p.imagen,

                pv.id AS variante_id,
                pv.talle,
                pv.color,
                pv.codigo AS codigo_variante,
                pv.stock,

                1 AS es_variante

            FROM producto_variantes pv

            INNER JOIN productos p

                ON p.id = pv.producto_id

            WHERE pv.codigo = ?

            AND pv.activo = 1

            LIMIT 1

        ");

        $stmtVariante->execute([
            $codigo
        ]);

        $producto = $stmtVariante->fetch(PDO::FETCH_ASSOC);

    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCTO NO ENCONTRADO
    |--------------------------------------------------------------------------
    */

    if (!$producto) {

        echo json_encode([
            "ok" => false,
            "mensaje" => "Producto no encontrado."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR STOCK
    |--------------------------------------------------------------------------
    */

    if ((int)$producto["stock"] <= 0) {

        echo json_encode([
            "ok" => false,
            "mensaje" => "El producto no tiene stock disponible."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "ok" => true,

        "producto" => [

            "id" => (int)$producto["id"],

            "codigo" => $producto["codigo"],

            "nombre" => $producto["nombre"],

            "precio" => (float)$producto["precio_venta"],

            "stock" => (int)$producto["stock"],

            "imagen" => $producto["imagen"],

            "es_variante" =>
                (int)$producto["es_variante"],

            "variante_id" =>
                !empty($producto["variante_id"])
                    ? (int)$producto["variante_id"]
                    : null,

            "talle" =>
                $producto["talle"] ?? null,

            "color" =>
                $producto["color"] ?? null

        ]

    ]);



} catch (Throwable $e) {

    echo json_encode([

        "ok" => false,

        "mensaje" =>
            "Error al buscar el producto."

    ]);

}

?>