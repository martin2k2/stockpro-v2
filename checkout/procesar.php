<?php

session_start();

require_once "../config/conexion.php";

$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| VALIDAR CLIENTE
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["cliente_id"])) {

    header("Location: ../clientes/login.php");
    exit;
}

$cliente_id = (int)$_SESSION["cliente_id"];


/*
|--------------------------------------------------------------------------
| VALIDAR CARRITO
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["carrito"])) {

    header("Location: ../carrito/index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| MÉTODO DE PAGO
|--------------------------------------------------------------------------
|
| Este archivo corresponde al pago online.
| Por ahora solamente transferencia bancaria.
|
*/

$metodo_pago = isset($_POST["metodo_pago"])
    ? strtoupper(trim($_POST["metodo_pago"]))
    : "";


if ($metodo_pago !== "TRANSFERENCIA") {

    die("Método de pago no válido.");

}


/*
|--------------------------------------------------------------------------
| OBTENER CLIENTE
|--------------------------------------------------------------------------
*/

$stmtCliente = $db->prepare("
    SELECT
        id,
        dni,
        apellido,
        nombre,
        telefono,
        direccion,
        email
    FROM clientes
    WHERE id = ?
    LIMIT 1
");

$stmtCliente->execute([
    $cliente_id
]);

$cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);


if (!$cliente) {

    die("Cliente no encontrado.");

}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$total = 0;

$productos = [];


try {


    /*
    |--------------------------------------------------------------------------
    | INICIAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | RECORRER CARRITO
    |--------------------------------------------------------------------------
    */

    foreach ($_SESSION["carrito"] as $clave => $item) {


        /*
        |--------------------------------------------------------------------------
        | DETECTAR FORMATO DEL CARRITO
        |--------------------------------------------------------------------------
        */

        if (is_array($item)) {

            $producto_id = (int)(
                $item["producto_id"] ?? 0
            );

            $variante_id = (int)(
                $item["variante_id"] ?? 0
            );

            $cantidad = (int)(
                $item["cantidad"] ?? 0
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | COMPATIBILIDAD CON CARRITO ANTIGUO
            |--------------------------------------------------------------------------
            */

            $producto_id = (int)$clave;

            $variante_id = 0;

            $cantidad = (int)$item;

        }


        /*
        |--------------------------------------------------------------------------
        | VALIDAR DATOS
        |--------------------------------------------------------------------------
        */

        if (
            $producto_id <= 0 ||
            $cantidad <= 0
        ) {

            throw new Exception(
                "Cantidad de producto inválida."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | BLOQUEAR PRODUCTO
        |--------------------------------------------------------------------------
        */

        $stmtProducto = $db->prepare("
            SELECT
                id,
                codigo,
                nombre,
                precio_venta,
                stock
            FROM productos
            WHERE id = ?
            FOR UPDATE
        ");

        $stmtProducto->execute([
            $producto_id
        ]);


        $p = $stmtProducto->fetch(
            PDO::FETCH_ASSOC
        );


        if (!$p) {

            throw new Exception(
                "Producto no encontrado."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | VARIABLES DE STOCK
        |--------------------------------------------------------------------------
        */

        $stockDisponible =
            (int)$p["stock"];

        $talle = null;

        $color = null;

        $codigoVariante = null;


        /*
        |--------------------------------------------------------------------------
        | PRODUCTO CON VARIANTE / TALLE
        |--------------------------------------------------------------------------
        */

        if ($variante_id > 0) {


            /*
            |--------------------------------------------------------------------------
            | BLOQUEAR VARIANTE
            |--------------------------------------------------------------------------
            */

            $stmtVariante = $db->prepare("
                SELECT
                    id,
                    producto_id,
                    talle,
                    color,
                    stock,
                    codigo,
                    activo
                FROM producto_variantes
                WHERE id = ?
                  AND producto_id = ?
                FOR UPDATE
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

                throw new Exception(
                    "La variante seleccionada no existe."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | VARIANTE ACTIVA
            |--------------------------------------------------------------------------
            */

            if ((int)$variante["activo"] !== 1) {

                throw new Exception(
                    "La variante seleccionada no está disponible."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | STOCK DEL TALLE
            |--------------------------------------------------------------------------
            */

            $stockDisponible =
                (int)$variante["stock"];


            $talle =
                $variante["talle"];


            $color =
                $variante["color"];


            $codigoVariante =
                $variante["codigo"];


            /*
            |--------------------------------------------------------------------------
            | VALIDAR STOCK DEL TALLE
            |--------------------------------------------------------------------------
            */

            if ($cantidad > $stockDisponible) {

                throw new Exception(

                    "Stock insuficiente para " .
                    $p["nombre"] .
                    " - Talle " .
                    $talle .
                    ". Stock disponible: " .
                    $stockDisponible

                );

            }


        } else {


            /*
            |--------------------------------------------------------------------------
            | PRODUCTO SIN TALLE
            |--------------------------------------------------------------------------
            */

            if ($cantidad > $stockDisponible) {

                throw new Exception(

                    "Stock insuficiente para: " .
                    $p["nombre"] .
                    ". Stock disponible: " .
                    $stockDisponible

                );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | PRECIO
        |--------------------------------------------------------------------------
        */

        $precio =
            (float)$p["precio_venta"];


        $subtotal =
            $precio * $cantidad;


        $total +=
            $subtotal;


        /*
        |--------------------------------------------------------------------------
        | GUARDAR PRODUCTO PARA DETALLE
        |--------------------------------------------------------------------------
        */

        $productos[] = [

            "id" =>
                (int)$p["id"],

            "codigo" =>
                $p["codigo"],

            "nombre" =>
                $p["nombre"],

            "variante_id" =>
                $variante_id,

            "talle" =>
                $talle,

            "color" =>
                $color,

            "codigo_variante" =>
                $codigoVariante,

            "cantidad" =>
                $cantidad,

            "precio" =>
                $precio,

            "subtotal" =>
                $subtotal

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR TOTAL
    |--------------------------------------------------------------------------
    */

    if ($total <= 0) {

        throw new Exception(
            "El total de la compra no es válido."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CREAR PEDIDO
    |--------------------------------------------------------------------------
    */

    $stmtPedido = $db->prepare("
        INSERT INTO pedidos
        (
            cliente_id,
            fecha,
            total,
            estado,
            metodo_pago
        )
        VALUES
        (
            ?,
            NOW(),
            ?,
            'Pendiente',
            ?
        )
    ");


    $stmtPedido->execute([

        $cliente_id,

        $total,

        "Transferencia"

    ]);


    $pedido_id =
        (int)$db->lastInsertId();


    if ($pedido_id <= 0) {

        throw new Exception(
            "No se pudo crear el pedido."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CREAR DETALLE DEL PEDIDO
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | pedido_detalle debe tener la columna variante_id.
    |
    */

    $stmtDetalle = $db->prepare("
        INSERT INTO pedido_detalle
        (
            pedido_id,
            producto_id,
            variante_id,
            cantidad,
            precio,
            subtotal
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");


    foreach ($productos as $p) {


        $stmtDetalle->execute([

            $pedido_id,

            $p["id"],

            $p["variante_id"] > 0
                ? $p["variante_id"]
                : null,

            $p["cantidad"],

            $p["precio"],

            $p["subtotal"]

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | DESCONTAR STOCK
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | El stock del talle se descuenta de producto_variantes.
    | El producto sin talle descuenta de productos.
    |
    */

    foreach ($productos as $p) {


        /*
        |--------------------------------------------------------------------------
        | VARIANTE
        |--------------------------------------------------------------------------
        */

        if ($p["variante_id"] > 0) {


            $stmtStockVariante = $db->prepare("
                UPDATE producto_variantes
                SET stock = stock - ?
                WHERE id = ?
                  AND stock >= ?
            ");


            $stmtStockVariante->execute([

                $p["cantidad"],

                $p["variante_id"],

                $p["cantidad"]

            ]);


            if (
                $stmtStockVariante->rowCount() !== 1
            ) {

                throw new Exception(

                    "No se pudo actualizar el stock del talle " .
                    $p["talle"] .
                    " de " .
                    $p["nombre"]

                );

            }


        } else {


            /*
            |--------------------------------------------------------------------------
            | PRODUCTO NORMAL
            |--------------------------------------------------------------------------
            */

            $stmtStockProducto = $db->prepare("
                UPDATE productos
                SET stock = stock - ?
                WHERE id = ?
                  AND stock >= ?
            ");


            $stmtStockProducto->execute([

                $p["cantidad"],

                $p["id"],

                $p["cantidad"]

            ]);


            if (
                $stmtStockProducto->rowCount() !== 1
            ) {

                throw new Exception(

                    "No se pudo actualizar el stock de " .
                    $p["nombre"]

                );

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $db->commit();


    /*
    |--------------------------------------------------------------------------
    | GUARDAR PEDIDO EN SESIÓN
    |--------------------------------------------------------------------------
    */

    $_SESSION["pedido_id"] =
        $pedido_id;

    $_SESSION["pedido_total"] =
        $total;


    /*
    |--------------------------------------------------------------------------
    | LIMPIAR CARRITO
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION["carrito"]
    );


    /*
    |--------------------------------------------------------------------------
    | IR A TRANSFERENCIA
    |--------------------------------------------------------------------------
    */

    header(
        "Location: transferencia.php"
    );

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if ($db->inTransaction()) {

        $db->rollBack();

    }


    /*
    |--------------------------------------------------------------------------
    | ERROR
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    echo "<!DOCTYPE html>";

    echo "<html lang='es'>";

    echo "<head>";

    echo "<meta charset='UTF-8'>";

    echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";

    echo "<title>Error al procesar pedido</title>";

    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";

    echo "</head>";

    echo "<body class='bg-light'>";

    echo "<div class='container py-5'>";

    echo "<div class='row justify-content-center'>";

    echo "<div class='col-12 col-md-8'>";

    echo "<div class='card shadow border-0'>";

    echo "<div class='card-header bg-danger text-white'>";

    echo "<h4 class='mb-0'>";

    echo "No se pudo procesar el pedido";

    echo "</h4>";

    echo "</div>";

    echo "<div class='card-body'>";

    echo "<div class='alert alert-danger'>";

    echo htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        "UTF-8"
    );

    echo "</div>";

    echo "<a href='../carrito/index.php' class='btn btn-secondary'>";

    echo "Volver al carrito";

    echo "</a>";

    echo "</div>";

    echo "</div>";

    echo "</div>";

    echo "</div>";

    echo "</div>";

    echo "</body>";

    echo "</html>";

}
