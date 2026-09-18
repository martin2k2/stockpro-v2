<?php
// cuentas_corrientes/cobrar.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

//-----------------------------------------------------
// Cuenta Corriente
//-----------------------------------------------------

$stmt = $conexion->prepare("

SELECT

cc.*,

c.apellido,
c.nombre

FROM cuentas_corrientes cc

INNER JOIN clientes c
ON c.id=cc.cliente_id

WHERE cc.id=?

");

$stmt->execute([$id]);

$cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$cuenta){

die("Cuenta inexistente.");

}

//-----------------------------------------------------
// Caja abierta
//-----------------------------------------------------

$stmt = $conexion->prepare("

SELECT id

FROM cajas

WHERE

usuario_id=?

AND estado='ABIERTA'

LIMIT 1

");

$stmt->execute([$_SESSION["usuario_id"]]);

$caja = $stmt->fetch(PDO::FETCH_ASSOC);

$caja_id = $caja["id"] ?? null;

//-----------------------------------------------------

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $importe = (float)$_POST["importe"];

    $forma_pago = $_POST["forma_pago"];

    $obs = trim($_POST["observaciones"]);

    if($importe<=0){

        die("Importe inválido.");

    }

    if($importe>$cuenta["saldo"]){

        die("El importe supera el saldo.");

    }

    $conexion->beginTransaction();

    try{

        //---------------------------------------------
        // Pago
        //---------------------------------------------

        $stmt = $conexion->prepare("

        INSERT INTO pagos_cuenta_corriente(

            cuenta_id,
            cliente_id,
            caja_id,
            usuario_id,
            importe,
            forma_pago,
            observaciones

        )

        VALUES(

            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?

        )

        ");

        $stmt->execute([

            $cuenta["id"],
            $cuenta["cliente_id"],
            $caja_id,
            $_SESSION["usuario_id"],
            $importe,
            $forma_pago,
            $obs

        ]);

        //---------------------------------------------
        // Actualizar cuenta
        //---------------------------------------------

        $nuevoEntregado =

        $cuenta["entregado"]

        +

        $importe;

        $nuevoSaldo =

        $cuenta["saldo"]

        -

        $importe;

        if($nuevoSaldo<=0){

            $estado="PAGADA";

            $nuevoSaldo=0;

        }else{

            $estado="PARCIAL";

        }

        $stmt = $conexion->prepare("

        UPDATE cuentas_corrientes

        SET

        entregado=?,
        saldo=?,
        estado=?

        WHERE id=?

        ");

        $stmt->execute([

            $nuevoEntregado,
            $nuevoSaldo,
            $estado,
            $cuenta["id"]

        ]);

        //---------------------------------------------
        // Movimiento Caja
        //---------------------------------------------

        if($caja_id){

            $stmt = $conexion->prepare("

            INSERT INTO movimientos_caja(

                caja_id,
                usuario_id,
                tipo,
                concepto,
                importe,
                fecha

            )

            VALUES(

                ?,
                ?,
                'INGRESO',
                ?,
                ?,
                NOW()

            )

            ");

            $stmt->execute([

                $caja_id,
                $_SESSION["usuario_id"],
                "Cobro Cuenta Corriente #".$cuenta["id"],
                $importe

            ]);

        }

        $conexion->commit();

        header("Location:detalle.php?id=".$cuenta["id"]);

        exit;

    }catch(Exception $e){

        $conexion->rollBack();

        die($e->getMessage());

    }

}

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Cobrar Cuenta Corriente</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="content">

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-6">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h4 class="mb-0">

<i class="bi bi-cash-stack"></i>

Cobrar Cuenta Corriente

</h4>

</div>

<div class="card-body">

<h5>

<?=htmlspecialchars($cuenta["apellido"])?>,
<?=htmlspecialchars($cuenta["nombre"])?>

</h5>

<hr>

<p>

Saldo Pendiente:

<strong class="text-danger">

$

<?=number_format($cuenta["saldo"],2,",",".")?>

</strong>

</p>

<form method="post">

<div class="mb-3">

<label>Importe</label>

<input
type="number"
step="0.01"
min="0.01"
max="<?=$cuenta["saldo"]?>"
name="importe"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Forma de Pago</label>

<select
name="forma_pago"
class="form-select">

<option>Efectivo</option>
<option>Transferencia</option>
<option>Débito</option>
<option>Crédito</option>
<option>Cheque</option>

</select>

</div>

<div class="mb-3">

<label>Observaciones</label>

<textarea
name="observaciones"
class="form-control"
rows="3"></textarea>

</div>

<div class="d-flex justify-content-between">

<a
href="detalle.php?id=<?=$cuenta["id"]?>"
class="btn btn-secondary">

Cancelar

</a>

<button
class="btn btn-success">

Registrar Cobro

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

</div>

</body>

</html>