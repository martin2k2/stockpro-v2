<?php

session_start();

require_once "../config/conexion.php";


/*
|--------------------------------------------------------------------------
| VALIDAR PEDIDO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['pedido_id'])) {

    header("Location: ../tienda/index.php");
    exit;

}


$pedido_id = (int)$_SESSION['pedido_id'];

$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| OBTENER PEDIDO
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.id,
        p.fecha,
        p.total,
        p.estado,
        p.metodo_pago,
        c.nombre,
        c.apellido,
        c.dni,
        c.email
    FROM pedidos p
    INNER JOIN clientes c
        ON c.id = p.cliente_id
    WHERE p.id = ?
    LIMIT 1
");

$stmt->execute([$pedido_id]);

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$pedido) {

    unset($_SESSION['pedido_id']);
    unset($_SESSION['pedido_total']);

    header("Location: ../tienda/index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| VALIDAR MÉTODO
|--------------------------------------------------------------------------
*/

if ($pedido['metodo_pago'] !== 'Transferencia') {

    header("Location: gracias.php");
    exit;

}


$total = (float)$pedido['total'];

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Transferencia bancaria - Tienda Urqui
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        body {
            background: #f5f6f8;
        }

        .card {
            border-radius: 15px;
        }

        .titulo {
            font-weight: 700;
        }

        .dato-bancario {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
        }

        .dato-label {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 3px;
        }

        .dato-valor {
            font-size: 18px;
            font-weight: 600;
            word-break: break-word;
        }

        .importe {
            font-size: 34px;
            font-weight: 700;
            color: #198754;
        }

        .numero-pedido {
            font-size: 22px;
            font-weight: 700;
        }

        .pasos {
            background: #e9f7ef;
            border-radius: 10px;
            padding: 18px;
        }

    </style>

</head>


<body>


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">


            <!-- ENCABEZADO -->

            <div class="text-center mb-4">

                <i
                    class="bi bi-bank"
                    style="font-size:55px;color:#0d6efd;"
                ></i>

                <h1 class="titulo mt-2">
                    Transferencia bancaria
                </h1>

                <p class="text-muted">
                    Realizá la transferencia utilizando los siguientes datos.
                </p>

            </div>


            <!-- PEDIDO -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body p-4">


                    <div class="row text-center">


                        <div class="col-md-6 mb-3 mb-md-0">

                            <div class="text-muted">
                                Número de pedido
                            </div>

                            <div class="numero-pedido">
                                #<?= $pedido_id ?>
                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted">
                                Importe a transferir
                            </div>

                            <div class="importe">

                                $ <?= number_format(
                                    $total,
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- DATOS BANCARIOS -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-header bg-primary text-white">

                    <h4 class="mb-0">

                        <i class="bi bi-bank"></i>

                        Datos bancarios

                    </h4>

                </div>


                <div class="card-body p-4">


                    <!-- BANCO -->

                    <div class="dato-bancario">

                        <div class="dato-label">
                            Banco
                        </div>

                        <div class="dato-valor">
                            Banco Credicoop
                        </div>

                    </div>


                    <!-- TITULAR -->

                    <div class="dato-bancario">

                        <div class="dato-label">
                            Titular
                        </div>

                        <div class="dato-valor">
                            CLUB ATLÉTICO GRAL. URQUIZA
                        </div>

                    </div>


                    <!-- CUIT -->

                    <div class="dato-bancario">

                        <div class="dato-label">
                            CUIT
                        </div>

                        <div class="dato-valor">
                            30-66615710-5 
                        </div>

                    </div>


                    <!-- CBU -->

                    <div class="dato-bancario">

                        <div class="dato-label">
                            CBU
                        </div>

                        <div class="dato-valor">
                           1910118955011801071164
                        </div>

                    </div>


                    <!-- ALIAS -->

                    <div class="dato-bancario">

                        <div class="dato-label">
                            Alias
                        </div>

                        <div class="dato-valor">
                            URQUIZA.ROPA
                        </div>

                    </div>


                </div>

            </div>


            <!-- INSTRUCCIONES -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body p-4">

                    <h4 class="mb-3">

                        <i class="bi bi-list-check"></i>

                        ¿Cómo realizar el pago?

                    </h4>


                    <div class="pasos">

                        <p class="mb-2">

                            <strong>1.</strong>

                            Realizá una transferencia por el importe exacto:

                        </p>

                        <h4 class="text-success mb-3">

                            $ <?= number_format(
                                $total,
                                2,
                                ",",
                                "."
                            ) ?>

                        </h4>


                        <p class="mb-2">

                            <strong>2.</strong>

                            Utilizá los datos bancarios indicados arriba.

                        </p>


                        <p class="mb-2">

                            <strong>3.</strong>

                            Guardá el comprobante de la transferencia.

                        </p>


                        <p class="mb-0">

                            <strong>4.</strong>

                            En el próximo paso podrás enviar el comprobante
                            para que podamos verificar el pago.

                        </p>

                    </div>

                </div>

            </div>


            <!-- ESTADO -->

            <div class="alert alert-warning">

                <div class="d-flex align-items-start gap-2">

                    <i class="bi bi-clock-history fs-4"></i>

                    <div>

                        <strong>
                            Pedido pendiente de pago
                        </strong>

                        <div class="mt-1">

                            Tu pedido fue registrado correctamente,
                            pero todavía no está confirmado como pagado.

                            Una vez verificada la transferencia,
                            procederemos a confirmar el pedido.

                        </div>

                    </div>

                </div>

            </div>


            <!-- BOTONES -->

            <div class="d-grid gap-2">

                <a
                    href="comprobante.php"
                    class="btn btn-success btn-lg"
                >

                    <i class="bi bi-upload"></i>

                    Enviar comprobante

                </a>


                <a
                    href="../tienda/index.php"
                    class="btn btn-outline-secondary"
                >

                    <i class="bi bi-shop"></i>

                    Volver a la tienda

                </a>

            </div>


        </div>

    </div>

</div>


</body>

</html>