<?php
// ventas/procesar_devolucion.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

if ($_SESSION["rol"] != "Administrador") {
    die("Acceso denegado.");
}

$conexion = (new Conexion())->conectar();

$venta_id = (int)$_POST["venta_id"];
$cantidades = $_POST["cantidad"];
$observacion = trim($_POST["observacion"]);

try{

$conexion->beginTransaction();

$stmtStock = $conexion->prepare("
UPDATE productos
SET stock=stock+?
WHERE id=?
");

$stmtMovimiento = $conexion->prepare("
INSERT INTO movimientos(

producto_id,
tipo,
cantidad,
observacion,
usuario_id,
fecha

)

VALUES(

?,
'ENTRADA',
?,
?,
?,
NOW()

)
");

$stmtDetalle = $conexion->prepare("
SELECT cantidad
FROM detalle_ventas
WHERE venta_id=?
AND producto_id=?
");

$stmtUpdate = $conexion->prepare("
UPDATE detalle_ventas
SET cantidad=?,
subtotal=precio*?-descuento
WHERE venta_id=?
AND producto_id=?
");

$stmtDelete = $conexion->prepare("
DELETE FROM detalle_ventas
WHERE venta_id=?
AND producto_id=?
");

$total=0;

foreach($cantidades as $producto=>$dev){

$dev=(int)$dev;

if($dev<=0){
    continue;
}

$stmtDetalle->execute([$venta_id,$producto]);

$item=$stmtDetalle->fetch(PDO::FETCH_ASSOC);

if(!$item){
    continue;
}

$nuevaCantidad=$item["cantidad"]-$dev;

$stmtStock->execute([
$dev,
$producto
]);

$stmtMovimiento->execute([
$producto,
$dev,
"Devolución Venta #".$venta_id." - ".$observacion,
$_SESSION["usuario_id"]
]);

if($nuevaCantidad<=0){

$stmtDelete->execute([
$venta_id,
$producto
]);

}else{

$stmtUpdate->execute([
$nuevaCantidad,
$nuevaCantidad,
$venta_id,
$producto
]);

}

}

$stmt=$conexion->prepare("
SELECT IFNULL(SUM(subtotal),0)
FROM detalle_ventas
WHERE venta_id=?
");

$stmt->execute([$venta_id]);

$total=$stmt->fetchColumn();

$estado=$total==0 ? "DEVUELTA" : "PARCIAL";

$stmt=$conexion->prepare("
UPDATE ventas
SET

total=?,
estado=?

WHERE id=?
");

$stmt->execute([
$total,
$estado,
$venta_id
]);

$conexion->commit();

header("Location:detalle.php?id=".$venta_id);

}catch(Exception $e){

$conexion->rollBack();

die($e->getMessage());

}
?>