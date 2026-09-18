<?php
// cuentas_corrientes/nueva.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

//-----------------------------------------
// Clientes
//-----------------------------------------

$stmt = $conexion->query("
SELECT
id,
apellido,
nombre
FROM clientes
WHERE estado=1
ORDER BY apellido,nombre
");

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

//-----------------------------------------
// Ventas sin cuenta corriente
//-----------------------------------------

$stmt = $conexion->query("

SELECT

v.id,
v.total,
v.fecha,

c.apellido,
c.nombre

FROM ventas v

INNER JOIN clientes c
ON c.id=v.cliente_id

LEFT JOIN cuentas_corrientes cc
ON cc.venta_id=v.id

WHERE

cc.id IS NULL

AND v.estado='PAGADA'

ORDER BY v.id DESC

");

$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

//-----------------------------------------

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $venta_id      = (int)$_POST["venta_id"];
    $cliente_id    = (int)$_POST["cliente_id"];
    $vencimiento   = $_POST["vencimiento"] ?: null;
    $total         = (float)$_POST["total"];
    $entregado     = (float)$_POST["entregado"];
    $observaciones = trim($_POST["observaciones"]);

    $saldo = $total - $entregado;

    if($saldo < 0){

        $saldo = 0;

    }

    if($saldo == 0){

        $estado = "PAGADA";

    }elseif($entregado > 0){

        $estado = "PARCIAL";

    }else{

        $estado = "PENDIENTE";

    }

    $stmt = $conexion->prepare("

    INSERT INTO cuentas_corrientes(

        venta_id,
        cliente_id,
        fecha,
        vencimiento,
        total,
        entregado,
        saldo,
        estado,
        observaciones

    )

    VALUES(

        ?,
        ?,
        NOW(),
        ?,
        ?,
        ?,
        ?,
        ?,
        ?

    )

    ");

    $stmt->execute([

        $venta_id,
        $cliente_id,
        $vencimiento,
        $total,
        $entregado,
        $saldo,
        $estado,
        $observaciones

    ]);

    header("Location:index.php?ok=1");

    exit;

}

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Nueva Cuenta Corriente</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="content">

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h4 class="mb-0">

<i class="bi bi-wallet2"></i>

Nueva Cuenta Corriente

</h4>

</div>

<div class="card-body">

<form method="post">

<div class="mb-3">

<label>Venta</label>

<select
name="venta_id"
class="form-select"
required>

<option value="">

Seleccionar...

</option>

<?php foreach($ventas as $v): ?>

<option
value="<?=$v["id"]?>"
data-total="<?=$v["total"]?>">

Venta #<?=$v["id"]?>

-

<?=htmlspecialchars($v["apellido"])?>

<?=htmlspecialchars($v["nombre"])?>

-

$

<?=number_format($v["total"],2,",",".")?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Cliente</label>

<select
name="cliente_id"
class="form-select"
required>

<option value="">

Seleccionar...

</option>

<?php foreach($clientes as $c): ?>

<option value="<?=$c["id"]?>">

<?=htmlspecialchars($c["apellido"])?>,
<?=htmlspecialchars($c["nombre"])?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="row">

<div class="col-md-4 mb-3">

<label>Total</label>

<input
type="number"
step="0.01"
name="total"
id="total"
class="form-control"
required>

</div>

<div class="col-md-4 mb-3">

<label>Entrega Inicial</label>

<input
type="number"
step="0.01"
value="0"
name="entregado"
class="form-control">

</div>

<div class="col-md-4 mb-3">

<label>Vencimiento</label>

<input
type="date"
name="vencimiento"
class="form-control">

</div>

</div>

<div class="mb-3">

<label>Observaciones</label>

<textarea
name="observaciones"
rows="4"
class="form-control"></textarea>

</div>

<div class="d-flex justify-content-between">

<a
href="index.php"
class="btn btn-secondary">

Cancelar

</a>

<button
class="btn btn-success">

Guardar

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

</div>

<script>

document.querySelector('[name="venta_id"]').addEventListener("change",function(){

let op=this.options[this.selectedIndex];

document.getElementById("total").value=op.dataset.total||"";

});

</script>

</body>

</html>