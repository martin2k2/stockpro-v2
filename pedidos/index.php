<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

requireAdmin();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login/index.php");
    exit;
}

$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| PEDIDOS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.id,
        CONCAT(c.apellido, ' ', c.nombre) AS cliente,
        p.fecha,
        p.total,
        p.estado,
        p.metodo_pago,

        (
            SELECT cp.estado
            FROM comprobantes_pedidos cp
            WHERE cp.pedido_id = p.id
            ORDER BY cp.id DESC
            LIMIT 1
        ) AS estado_comprobante

    FROM pedidos p

    INNER JOIN clientes c
        ON c.id = p.cliente_id

    ORDER BY p.id DESC
");

$stmt->execute();

$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CANTIDAD DE PEDIDOS PAGADOS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM pedidos
    WHERE estado = 'Pagado'
");

$stmt->execute();

$totalPagados = (int)$stmt->fetchColumn();

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Pedidos - Stock PRO</title>


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

        .titulo {
            font-size: 32px;
            font-weight: 600;
        }

        .subtitulo {
            color: #6c757d;
        }

        .tabla-pedidos th {
            white-space: nowrap;
        }

        .tabla-pedidos td {
            vertical-align: middle;
        }

        .badge {
            font-size: 12px;
        }

    </style>

</head>


<body>


<div class="container-fluid py-4 px-3 px-md-4">


    <!-- CABECERA -->

    <div
        class="d-flex
        flex-column
        flex-md-row
        justify-content-between
        align-items-md-center
        mb-4"
    >

        <div>

            <h1 class="titulo mb-1">

                <i class="bi bi-cart-check"></i>

                Pedidos

            </h1>

            <div class="subtitulo">

                Pedidos realizados desde la tienda online

            </div>

        </div>
        <a
    href="historial_comprobantes.php"
    class="btn btn-secondary"
>
    <i class="bi bi-clock-history"></i>
    Historial de comprobantes
</a>

        <div class="mt-3 mt-md-0">

            <a
                href="../dashboard/index.php"
                class="btn btn-secondary"
            >

                <i class="bi bi-arrow-left"></i>

                Volver

            </a>

        </div>

    </div>


    <!-- RESUMEN -->

    <div class="row mb-4">

        <div class="col-md-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <div class="text-muted">
                        Pedidos pagados
                    </div>

                    <div class="fs-3 fw-bold">

                        <?= $totalPagados ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- TABLA -->

    <div class="card shadow-sm">

        <div class="card-body p-2 p-md-3">

            <div class="table-responsive">

                <table
                    class="table table-bordered table-hover tabla-pedidos mb-0"
                >

                    <thead class="table-dark">

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Cliente
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Total
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Forma de pago
                            </th>

                            <th>
                                Comprobante
                            </th>

                            <th>
                                Acción
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$pedidos): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-muted py-4"
                            >

                                No hay pedidos registrados.

                            </td>

                        </tr>

                    <?php endif; ?>


                    <?php foreach ($pedidos as $p): ?>


                        <?php

                        $estadoPedido = strtoupper(
                            trim((string)$p["estado"])
                        );

                        $estadoComprobante = strtoupper(
                            trim(
                                (string)(
                                    $p["estado_comprobante"] ?? ""
                                )
                            )
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | PEDIDO BLOQUEADO
                        |--------------------------------------------------------------------------
                        */

                        $pedidoBloqueado = (
                            $estadoPedido === "CANCELADO" ||
                            $estadoPedido === "PAGO RECHAZADO" ||
                            $estadoComprobante === "RECHAZADO"
                        );

                        ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <strong>

                                    #<?= (int)$p["id"] ?>

                                </strong>

                            </td>


                            <!-- CLIENTE -->

                            <td>

                                <?= htmlspecialchars(
                                    $p["cliente"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </td>


                            <!-- FECHA -->

                            <td>

                                <?= htmlspecialchars(
                                    $p["fecha"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </td>


                            <!-- TOTAL -->

                            <td>

                                <strong>

                                    $

                                    <?= number_format(
                                        (float)$p["total"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </strong>

                            </td>


                            <!-- ESTADO -->

                            <td>

                                <?php if ($pedidoBloqueado): ?>

                                    <span class="badge bg-danger">

                                        <i class="bi bi-lock-fill"></i>

                                        Cancelado

                                    </span>

                                <?php elseif (
                                    $estadoPedido === "PAGADO"
                                ): ?>

                                    <span
                                        class="badge bg-warning text-dark"
                                    >

                                        Pagado

                                    </span>

                                <?php elseif (
                                    $estadoPedido === "PREPARANDO"
                                ): ?>

                                    <span
                                        class="badge bg-info text-dark"
                                    >

                                        Preparando

                                    </span>

                                <?php elseif (
                                    $estadoPedido === "ENVIADO"
                                ): ?>

                                    <span class="badge bg-primary">

                                        Enviado

                                    </span>

                                <?php elseif (
                                    $estadoPedido === "ENTREGADO"
                                ): ?>

                                    <span class="badge bg-success">

                                        Entregado

                                    </span>

                                <?php elseif (
                                    $estadoPedido === "CANCELADO"
                                ): ?>

                                    <span class="badge bg-danger">

                                        Cancelado

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">

                                        <?= htmlspecialchars(
                                            $p["estado"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- FORMA DE PAGO -->

                            <td>

                                <?= htmlspecialchars(
                                    $p["metodo_pago"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </td>


                            <!-- COMPROBANTE -->

                            <td>

                                <?php if (
                                    $estadoComprobante === "RECHAZADO"
                                ): ?>

                                    <span class="badge bg-danger">

                                        <i class="bi bi-x-circle"></i>

                                        Rechazado

                                    </span>

                                <?php elseif (
                                    $estadoComprobante === "APROBADO"
                                ): ?>

                                    <span class="badge bg-success">

                                        <i class="bi bi-check-circle"></i>

                                        Aprobado

                                    </span>

                                <?php elseif (
                                    $estadoComprobante === "PENDIENTE"
                                ): ?>

                                    <a
                                        href="comprobantes.php"
                                        class="badge bg-warning text-dark text-decoration-none"
                                    >

                                        <i class="bi bi-clock"></i>

                                        Pendiente

                                    </a>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <!-- ACCIÓN -->

                            <td>

                                <a
                                    href="ver.php?id=<?= (int)$p["id"] ?>"
                                    class="btn btn-primary btn-sm"
                                >

                                    <i class="bi bi-eye"></i>

                                    Ver pedido

                                </a>

                            </td>


                        </tr>


                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>