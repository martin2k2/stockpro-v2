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

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    die("Pedido inválido.");
}


/*
|--------------------------------------------------------------------------
| PEDIDO
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.*,
        CONCAT(c.apellido, ' ', c.nombre) AS cliente,
        c.telefono,
        c.direccion,
        c.email
    FROM pedidos p
    INNER JOIN clientes c
        ON c.id = p.cliente_id
    WHERE p.id = ?
");

$stmt->execute([$id]);

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("Pedido inexistente.");
}


/*
|--------------------------------------------------------------------------
| ÚLTIMO COMPROBANTE
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        id,
        estado,
        archivo,
        nombre_original,
        fecha
    FROM comprobantes_pedidos
    WHERE pedido_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$stmt->execute([$id]);

$comprobante = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ESTADOS
|--------------------------------------------------------------------------
*/

$estadoPedido = strtoupper(
    trim((string)$pedido["estado"])
);

$estadoComprobante = strtoupper(
    trim((string)($comprobante["estado"] ?? ""))
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


/*
|--------------------------------------------------------------------------
| DETALLE DEL PEDIDO CON TALLE Y COLOR
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        pd.*,

        pr.nombre AS nombre_producto,

        pv.id AS variante_id_real,
        pv.talle,
        pv.color,
        pv.codigo AS codigo_variante

    FROM pedido_detalle pd

    INNER JOIN productos pr
        ON pr.id = pd.producto_id

    LEFT JOIN producto_variantes pv
        ON pv.id = pd.variante_id
        AND pv.producto_id = pd.producto_id

    WHERE pd.pedido_id = ?

    ORDER BY pd.id ASC
");

$stmt->execute([$id]);

$detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Pedido #<?= (int)$pedido["id"] ?>
    </title>

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

<div class="container mt-5 mb-5">

    <div class="card shadow">


        <!-- CABECERA -->

        <div
            class="card-header text-white
            <?= $pedidoBloqueado ? "bg-danger" : "bg-success" ?>"
        >

            <div class="d-flex justify-content-between align-items-center">

                <h3 class="mb-0">

                    <i
                        class="bi
                        <?= $pedidoBloqueado
                            ? "bi-x-circle-fill"
                            : "bi-bag-check-fill"
                        ?>"
                    ></i>

                    Pedido #<?= (int)$pedido["id"] ?>

                </h3>


                <?php if ($pedidoBloqueado): ?>

                    <span class="badge bg-light text-danger fs-6">

                        <i class="bi bi-lock-fill"></i>

                        Pedido bloqueado

                    </span>

                <?php else: ?>

                    <span class="badge bg-light text-dark fs-6">

                        <?= htmlspecialchars(
                            $pedido["estado"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                <?php endif; ?>

            </div>

        </div>


        <!-- CUERPO -->

        <div class="card-body">


            <!-- ALERTA DE PAGO RECHAZADO -->

            <?php if ($estadoComprobante === "RECHAZADO"): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-x-circle-fill"></i>

                    <strong>Pago rechazado.</strong>

                    El comprobante de transferencia fue rechazado.

                    El pedido está cancelado y no se puede modificar.

                </div>

            <?php endif; ?>


            <!-- ALERTA PEDIDO CANCELADO -->

            <?php if (
                $estadoPedido === "CANCELADO" &&
                $estadoComprobante !== "RECHAZADO"
            ): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-lock-fill"></i>

                    <strong>Pedido cancelado.</strong>

                    Este pedido no puede volver a cambiar de estado.

                </div>

            <?php endif; ?>


            <!-- CLIENTE -->

            <div class="row mb-4">

                <div class="col-md-6">

                    <p class="mb-2">

                        <strong>Cliente:</strong>

                        <?= htmlspecialchars(
                            $pedido["cliente"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </p>


                    <p class="mb-2">

                        <strong>Teléfono:</strong>

                        <?= htmlspecialchars(
                            $pedido["telefono"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </p>

                </div>


                <div class="col-md-6">

                    <p class="mb-2">

                        <strong>Dirección:</strong>

                        <?= htmlspecialchars(
                            $pedido["direccion"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </p>


                    <?php if (!empty($pedido["email"])): ?>

                        <p class="mb-2">

                            <strong>Email:</strong>

                            <?= htmlspecialchars(
                                $pedido["email"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </p>

                    <?php endif; ?>

                </div>

            </div>


            <hr>


            <!-- ESTADO -->

            <div class="mb-4">

                <strong>
                    Estado actual:
                </strong>


                <?php if ($estadoComprobante === "RECHAZADO"): ?>

                    <span class="badge bg-danger ms-2">

                        <i class="bi bi-x-circle-fill"></i>

                        Cancelado - Pago rechazado

                    </span>

                <?php else: ?>

                    <span
                        class="badge
                        <?= $estadoPedido === "CANCELADO"
                            ? "bg-danger"
                            : "bg-primary"
                        ?>
                        ms-2"
                    >

                        <?= htmlspecialchars(
                            $pedido["estado"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                <?php endif; ?>

            </div>


            <!-- COMPROBANTE -->

            <?php if ($comprobante): ?>

                <div class="mb-4">

                    <strong>
                        Comprobante:
                    </strong>


                    <?php if ($estadoComprobante === "RECHAZADO"): ?>

                        <span class="badge bg-danger ms-2">

                            <i class="bi bi-x-circle"></i>

                            Rechazado

                        </span>


                    <?php elseif ($estadoComprobante === "APROBADO"): ?>

                        <span class="badge bg-success ms-2">

                            <i class="bi bi-check-circle"></i>

                            Aprobado

                        </span>


                    <?php elseif ($estadoComprobante === "PENDIENTE"): ?>

                        <span class="badge bg-warning text-dark ms-2">

                            <i class="bi bi-clock"></i>

                            Pendiente

                        </span>


                    <?php else: ?>

                        <span class="badge bg-secondary ms-2">

                            <?= htmlspecialchars(
                                $comprobante["estado"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <!-- PRODUCTOS -->

            <div class="table-responsive">

                <table
                    class="table table-bordered table-striped align-middle"
                >

                    <thead class="table-dark">

                        <tr>

                            <th>
                                Producto
                            </th>

                            <th class="text-center">
                                Talle
                            </th>

                            <th class="text-center">
                                Color
                            </th>

                            <th class="text-center">
                                Cantidad
                            </th>

                            <th class="text-end">
                                Precio
                            </th>

                            <th class="text-end">
                                Subtotal
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if ($detalle): ?>

                        <?php foreach ($detalle as $d): ?>

                            <tr>


                                <!-- PRODUCTO -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $d["nombre_producto"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        !empty($d["codigo_variante"])
                                    ): ?>

                                        <br>

                                        <small class="text-muted">

                                            Código:

                                            <?= htmlspecialchars(
                                                $d["codigo_variante"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- TALLE -->

                                <td class="text-center">

                                    <?php if (!empty($d["talle"])): ?>

                                        <span class="badge bg-primary">

                                            <?= htmlspecialchars(
                                                $d["talle"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">

                                            -

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- COLOR -->

                                <td class="text-center">

                                    <?php if (!empty($d["color"])): ?>

                                        <span class="badge bg-secondary">

                                            <?= htmlspecialchars(
                                                $d["color"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">

                                            -

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CANTIDAD -->

                                <td class="text-center">

                                    <?= htmlspecialchars(
                                        $d["cantidad"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </td>


                                <!-- PRECIO -->

                                <td class="text-end">

                                    $

                                    <?= number_format(
                                        (float)$d["precio"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </td>


                                <!-- SUBTOTAL -->

                                <td class="text-end">

                                    $

                                    <?= number_format(
                                        (float)$d["subtotal"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </td>


                            </tr>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center text-muted"
                            >

                                No hay productos en este pedido.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- TOTAL -->

            <h4 class="text-end mt-4">

                Total:

                <strong>

                    $

                    <?= number_format(
                        (float)$pedido["total"],
                        2,
                        ",",
                        "."
                    ) ?>

                </strong>

            </h4>


            <hr class="my-4">


            <!-- BOTONES -->

            <div class="d-flex flex-wrap gap-2">

            <?php

            $estadoPedidoNormalizado = strtoupper(
                trim((string)$pedido["estado"])
            );

            $bloquearTodo = (
                $pedidoBloqueado ||
                $estadoPedidoNormalizado === "ENTREGADO"
            );

            $bloquearImpresion = (
                $bloquearTodo ||
                $estadoPedidoNormalizado === "ENVIADO"
            );

            ?>


            <!-- CAMBIAR ESTADO -->

            <?php if (!$bloquearTodo): ?>

                <a
                    href="cambiar_estado.php?id=<?= (int)$pedido["id"] ?>"
                    class="btn btn-primary"
                >

                    <i class="bi bi-arrow-repeat"></i>

                    Cambiar estado

                </a>

            <?php else: ?>

                <button
                    type="button"
                    class="btn btn-danger"
                    disabled
                >

                    <i class="bi bi-lock-fill"></i>

                    Cambiar estado bloqueado

                </button>

            <?php endif; ?>


            <!-- IMPRIMIR ETIQUETA -->

            <?php if (!$bloquearImpresion): ?>

                <a
                    href="etiqueta.php?id=<?= (int)$pedido["id"] ?>"
                    target="_blank"
                    class="btn btn-dark"
                >

                    <i class="bi bi-tag"></i>

                    Imprimir etiqueta

                </a>

            <?php else: ?>

                <button
                    type="button"
                    class="btn btn-secondary"
                    disabled
                >

                    <i class="bi bi-lock-fill"></i>

                    Etiqueta bloqueada

                </button>

            <?php endif; ?>


            <!-- IMPRIMIR TICKET -->

            <?php if (!$bloquearImpresion): ?>

                <a
                    href="ticket.php?id=<?= (int)$pedido["id"] ?>"
                    target="_blank"
                    class="btn btn-secondary"
                >

                    <i class="bi bi-receipt"></i>

                    Imprimir ticket

                </a>

            <?php else: ?>

                <button
                    type="button"
                    class="btn btn-secondary"
                    disabled
                >

                    <i class="bi bi-lock-fill"></i>

                    Ticket bloqueado

                </button>

            <?php endif; ?>


            <!-- VOLVER -->

            <a
                href="index.php"
                class="btn btn-secondary"
            >

                <i class="bi bi-arrow-left"></i>

                Volver

            </a>

            </div>

        </div>

    </div>

</div>

</body>

</html>