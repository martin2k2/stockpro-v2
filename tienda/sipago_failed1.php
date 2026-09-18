<?php
// /stockpro-v2/tienda/sipago_failed.php

session_start();

require_once "../config/conexion.php";

$pdo = Conexion::conectar();

$pedido_id = isset($_GET["pedido_id"])
    ? (int)$_GET["pedido_id"]
    : 0;

if ($pedido_id <= 0) {
    die("Pedido inválido.");
}

$stmt = $pdo->prepare("
    SELECT
        id,
        estado,
        total,
        metodo_pago
    FROM pedidos
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $pedido_id
]);

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("Pedido no encontrado.");
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Pago no realizado</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card shadow border-danger">

                <div class="card-header bg-danger text-white">

                    <h4 class="mb-0">

                        <i class="bi bi-x-circle-fill me-2"></i>

                        Pago no realizado

                    </h4>

                </div>

                <div class="card-body text-center py-5">

                    <i
                        class="bi bi-x-circle-fill text-danger"
                        style="font-size:80px;"
                    ></i>

                    <h3 class="mt-4">

                        No se pudo completar el pago

                    </h3>

                    <p class="text-muted">

                        El pago del pedido no pudo ser aprobado.

                    </p>

                    <div class="alert alert-warning mt-4">

                        <strong>

                            Pedido #<?= (int)$pedido["id"] ?>

                        </strong>

                        <br>

                        Total:

                        <strong>

                            $<?= number_format(
                                (float)$pedido["total"],
                                2,
                                ",",
                                "."
                            ) ?>

                        </strong>

                    </div>

                    <p class="small text-muted">

                        SiPago informará el resultado definitivo
                        mediante el sistema de notificaciones.

                    </p>

                    <div class="d-flex justify-content-center gap-2">

                        <a
                            href="index.php"
                            class="btn btn-primary"
                        >

                            <i class="bi bi-arrow-left me-1"></i>

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