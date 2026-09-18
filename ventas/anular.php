<?php
// ventas/anular.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

if ($_SESSION["rol"] != "ADMIN") {
    die("Acceso denegado.");
}

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

try {

    $conexion->beginTransaction();

    //----------------------------------------
    // Obtener venta
    //----------------------------------------

    $stmt = $conexion->prepare("
        SELECT *
        FROM ventas
        WHERE id=?
    ");

    $stmt->execute([$id]);

    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        throw new Exception("La venta no existe.");
    }

    if ($venta["estado"] == "ANULADA") {
        throw new Exception("La venta ya fue anulada.");
    }

    //----------------------------------------
    // Obtener detalle
    //----------------------------------------

    $stmt = $conexion->prepare("
        SELECT *
        FROM detalle_ventas
        WHERE venta_id=?
    ");

    $stmt->execute([$id]);

    $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //----------------------------------------
    // Devolver stock
    //----------------------------------------

    $sqlStock = "
        UPDATE productos
        SET stock = stock + ?
        WHERE id = ?
    ";

    $updStock = $conexion->prepare($sqlStock);

    //----------------------------------------
    // Registrar movimiento
    //----------------------------------------

    $sqlMov = "
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
            'ENTRADA',
            ?,
            ?,
            ?,
            NOW()
        )
    ";

    $mov = $conexion->prepare($sqlMov);

    foreach ($detalle as $item) {

        $updStock->execute([
            $item["cantidad"],
            $item["producto_id"]
        ]);

        $mov->execute([
            $item["producto_id"],
            $item["cantidad"],
            "Anulación Venta Nº ".$venta["id"],
            $_SESSION["usuario_id"]
        ]);

    }

    //----------------------------------------
    // Marcar venta anulada
    //----------------------------------------

    $stmt = $conexion->prepare("
        UPDATE ventas
        SET
            estado='ANULADA',
            fecha_anulacion=NOW(),
            usuario_anulacion=?
        WHERE id=?
    ");

    $stmt->execute([
        $_SESSION["usuario_id"],
        $id
    ]);

    $conexion->commit();

    header("Location: index.php");
    exit;

} catch (Exception $e) {

    $conexion->rollBack();

    die($e->getMessage());

}
?>