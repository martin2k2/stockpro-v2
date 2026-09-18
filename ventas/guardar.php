<?php
// ventas/guardar.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$conexion = (new Conexion())->conectar();

try {

    /* =========================================
       DATOS
    ========================================= */

    $usuario_id = (int)($_SESSION["usuario_id"] ?? 0);

    if ($usuario_id <= 0) {
        throw new Exception("Sesión de usuario inválida.");
    }

    // Consumidor Final = ID 6
    $cliente_id = !empty($_POST["cliente_id"])
        ? (int)$_POST["cliente_id"]
        : 6;

    $forma_pago = trim($_POST["forma_pago"] ?? "Efectivo");

    $observacion = trim($_POST["observacion"] ?? "");

    $detalle = json_decode($_POST["detalle"] ?? "", true);

    if (!is_array($detalle) || count($detalle) === 0) {
        throw new Exception("No hay productos en la venta.");
    }

    /* =========================================
       VERIFICAR CLIENTE
    ========================================= */

    $stmt = $conexion->prepare("
        SELECT id
        FROM clientes
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$cliente_id]);

    if (!$stmt->fetch()) {
        throw new Exception("El cliente seleccionado no existe.");
    }

    /* =========================================
       INICIAR TRANSACCIÓN
    ========================================= */

    $conexion->beginTransaction();

    /* =========================================
       CALCULAR TOTAL
    ========================================= */

    $total = 0;

    foreach ($detalle as $item) {

        $producto_id = (int)($item["id"] ?? 0);
        $cantidad = (int)($item["cantidad"] ?? 0);
        $precio = (float)($item["precio"] ?? 0);
        $descuento = (float)($item["descuento"] ?? 0);

        if ($producto_id <= 0) {
            throw new Exception("Producto inválido.");
        }

        if ($cantidad <= 0) {
            throw new Exception("Cantidad inválida.");
        }

        if ($precio < 0) {
            throw new Exception("Precio inválido.");
        }

        if ($descuento < 0) {
            $descuento = 0;
        }

        $subtotal = ($cantidad * $precio) - $descuento;

        if ($subtotal < 0) {
            $subtotal = 0;
        }

        $total += $subtotal;
    }

    /* =========================================
       VERIFICAR STOCK
    ========================================= */

    $stmtProducto = $conexion->prepare("
        SELECT
            id,
            nombre,
            stock,
            precio_venta
        FROM productos
        WHERE id = ?
        AND activo = 1
        FOR UPDATE
    ");

    foreach ($detalle as $item) {

        $producto_id = (int)$item["id"];
        $cantidad = (int)$item["cantidad"];

        $stmtProducto->execute([$producto_id]);

        $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception(
                "El producto ID ".$producto_id." no existe o está inactivo."
            );
        }

        if ($producto["stock"] < $cantidad) {
            throw new Exception(
                "Stock insuficiente para: ".$producto["nombre"].
                ". Stock disponible: ".$producto["stock"]
            );
        }
    }


    // PRODUCTOS

            $stmt = $conexion->query("
                SELECT
                  id,
                codigo,
                nombre,
                precio_venta,
                stock
            FROM productos
            WHERE activo = 1
            AND stock > 0
            ORDER BY nombre
            ");

    // CLIENTES

            $stmt = $conexion->query("
                SELECT
                id,
                apellido,
                nombre
                FROM clientes
                WHERE estado = 1
                ORDER BY apellido, nombre
                ");

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);        

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /* =========================================
       CREAR VENTA
    ========================================= */

    $sqlVenta = "

        INSERT INTO ventas
        (
            fecha,
            usuario_id,
            cliente_id,
            total,
            forma_pago,
            estado
        )
        VALUES
        (
            NOW(),
            ?,
            ?,
            ?,
            ?,
            'PAGADA'
        )

    ";

    $stmtVenta = $conexion->prepare($sqlVenta);

    $stmtVenta->execute([
        $usuario_id,
        $cliente_id,
        $total,
        $forma_pago
    ]);

    $venta_id = $conexion->lastInsertId();

    /* =========================================
       DETALLE
    ========================================= */

    $stmtDetalle = $conexion->prepare("

        INSERT INTO detalle_ventas
        (
            venta_id,
            producto_id,
            cantidad,
            precio,
            descuento,
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

    /* =========================================
       ACTUALIZAR STOCK
    ========================================= */

    $stmtStock = $conexion->prepare("

        UPDATE productos

        SET stock = stock - ?

        WHERE id = ?

    ");

    /* =========================================
       MOVIMIENTO
    ========================================= */

    $stmtMovimiento = $conexion->prepare("

        INSERT INTO movimientos
        (
            producto_id,
            tipo,
            cantidad,
            observacion,
            usuario_id,
            fecha
        )
        VALUES
        (
            ?,
            'SALIDA',
            ?,
            ?,
            ?,
            NOW()
        )

    ");

    /* =========================================
       GUARDAR PRODUCTOS
    ========================================= */

    foreach ($detalle as $item) {

        $producto_id = (int)$item["id"];

        $cantidad = (int)$item["cantidad"];

        $precio = (float)$item["precio"];

        $descuento = (float)($item["descuento"] ?? 0);

        $subtotal = ($cantidad * $precio) - $descuento;

        if ($subtotal < 0) {
            $subtotal = 0;
        }

        /* DETALLE */

        $stmtDetalle->execute([
            $venta_id,
            $producto_id,
            $cantidad,
            $precio,
            $descuento,
            $subtotal
        ]);

        /* STOCK */

        $stmtStock->execute([
            $cantidad,
            $producto_id
        ]);

        /* MOVIMIENTO */

        $stmtMovimiento->execute([
            $producto_id,
            $cantidad,
            "Venta Nº ".$venta_id,
            $usuario_id
        ]);
    }

    /* =========================================
       COMMIT
    ========================================= */

    $conexion->commit();

    /* =========================================
       IR AL TICKET
    ========================================= */

    header("Location: ticket.php?id=".$venta_id);
    exit;

} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    die(
        "<h3>Error al guardar la venta</h3>" .
        "<p>" . htmlspecialchars($e->getMessage()) . "</p>" .
        "<p><a href='nueva.php'>Volver</a></p>"
    );
}
?>