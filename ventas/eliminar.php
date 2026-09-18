<?php
// ventas/eliminar.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

if ($_SESSION["rol"] != "Administrador") {
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

    //---------------------------------------
    // Obtener detalle
    //---------------------------------------

    $stmt = $conexion->prepare("
        SELECT *
        FROM detalle_ventas
        WHERE venta_id=?
    ");

    $stmt->execute([$id]);

    $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //---------------------------------------
    // Devolver stock
    //---------------------------------------

    $stmtStock = $conexion->prepare("
        UPDATE productos
        SET stock = stock + ?
        WHERE id=?
    ");

    //---------------------------------------
    // Registrar movimiento
    //---------------------------------------

    $stmtMovimiento = $conexion->prepare("
        INSERT INTO movimientos(

            producto_id,
            tipo,
            cantidad,
            observacion,
            usuario_id,
            fecha

        ) VALUES(

            ?,
            'ENTRADA',
            ?,
            ?,
            ?,
            NOW()

        )
    ");

    foreach ($detalle as $item) {

        $stmtStock->execute([

            $item["cantidad"],
            $item["producto_id"]

        ]);

        $stmtMovimiento->execute([

            $item["producto_id"],
            $item["cantidad"],
            "Eliminación de venta #".$id,
            $_SESSION["usuario_id"]

        ]);

    }

    //---------------------------------------
    // Eliminar detalle
    //---------------------------------------

    $stmt = $conexion->prepare("
        DELETE FROM detalle_ventas
        WHERE venta_id=?
    ");

    $stmt->execute([$id]);

    //---------------------------------------
    // Eliminar cabecera
    //---------------------------------------

    $stmt = $conexion->prepare("
        DELETE FROM ventas
        WHERE id=?
    ");

    $stmt->execute([$id]);

    $conexion->commit();

    header("Location: index.php?ok=4");
    exit;

} catch (Exception $e) {

    $conexion->rollBack();

    die($e->getMessage());

}
?>