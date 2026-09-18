<?php

session_start();

if (!isset($_SESSION['pedido_id'])) {
    header("Location: ../tienda/index.php");
    exit;
}

$pedido_id = (int)$_SESSION['pedido_id'];

$enviado = $_SESSION['comprobante_enviado'] ?? false;

unset($_SESSION['comprobante_enviado']);

if (!$enviado) {
    header("Location: transferencia.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Comprobante recibido - Tienda Urqui
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
            border-radius: 18px;
        }

        .icono-exito {
            font-size: 80px;
            color: #198754;
        }

        .numero-pedido {
            font-size: 25px;
            font-weight: 700;
        }

    </style>

</head>

<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-6">

            <div class="card shadow border-0">

                <div class="card-body text-center p-5">

                    <i class="bi bi-check-circle-fill icono-exito"></i>

                    <h1 class="mt-4 fw-bold">
                        ¡Comprobante recibido!
                    </h1>

                    <p class="text-muted fs-5 mt-3">

                        Recibimos correctamente el comprobante
                        de tu transferencia.

                    </p>


                    <div class="alert alert-primary mt-4">

                        <div class="text-muted">
                            Número de pedido
                        </div>

                        <div class="numero-pedido">
                            #<?= $pedido_id ?>
                        </div>

                    </div>


                    <div class="alert alert-warning text-start mt-4">

                        <div class="d-flex align-items-start gap-2">

                            <i class="bi bi-clock-history fs-4"></i>

                            <div>

                                <strong>
                                    Pago en revisión
                                </strong>

                                <div class="mt-1">

                                    Nuestro equipo verificará la
                                    transferencia y confirmará el pedido
                                    una vez acreditado el pago.

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="mt-4">

                        <p class="text-muted mb-2">

                            No es necesario realizar ninguna otra acción
                            por el momento.

                        </p>

                        <p class="text-muted">

                            Conservá el número de pedido para futuras
                            consultas.

                        </p>

                    </div>


                    <div class="d-grid gap-2 mt-4">

                        <a
                            href="../tienda/index.php"
                            class="btn btn-primary btn-lg"
                        >

                            <i class="bi bi-shop"></i>

                            Volver a la tienda

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>