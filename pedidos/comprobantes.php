<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

requireAdmin();

/*
|--------------------------------------------------------------------------
| SOLO ADMINISTRADORES
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['usuario_id'])) {

    header("Location: ../login/index.php");
    exit;

}

if (
    !isset($_SESSION['rol']) ||
    strtoupper(trim($_SESSION['rol'])) !== 'ADMIN'
) {

    http_response_code(403);

    die("Acceso denegado.");

}


$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| OBTENER COMPROBANTES
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT
        cp.id AS comprobante_id,
        cp.pedido_id,
        cp.archivo,
        cp.nombre_original,
        cp.fecha AS fecha_comprobante,
        cp.estado AS estado_comprobante,

        p.fecha AS fecha_pedido,
        p.total,
        p.estado AS estado_pedido,
        p.metodo_pago,

        c.id AS cliente_id,
        c.nombre,
        c.apellido,
        c.dni,
        c.email

    FROM comprobantes_pedidos cp

    INNER JOIN pedidos p
        ON p.id = cp.pedido_id

    INNER JOIN clientes c
        ON c.id = p.cliente_id

    ORDER BY cp.id DESC
");

$comprobantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Comprobantes de transferencias - Stock PRO
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

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        .card {
            border: 0;
            border-radius: 12px;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        .comprobante {
            width: 120px;
            height: 80px;
            object-fit: contain;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background: #fff;
        }

        @media (max-width: 991px) {

            .main {
                margin-left: 0;
                padding: 20px;
            }

        }

    </style>

</head>


<body>


<?php

/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------


if (file_exists("../includes/sidebar.php")) {

    include "../includes/sidebar.php";

}*/

?>


<div class="main">


<?php if (isset($_GET["ok"]) && $_GET["ok"] === "aprobado"): ?>

<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle-fill"></i>
    <strong>Pago aprobado correctamente.</strong>
    La venta fue registrada y el stock fue descontado.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<?php endif; ?>


<?php if (isset($_GET["ok"]) && $_GET["ok"] === "rechazado"): ?>

<div class="alert alert-warning alert-dismissible fade show">
    <i class="bi bi-x-circle-fill"></i>
    <strong>Comprobante rechazado.</strong>
    El pedido fue marcado como pago rechazado.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<?php endif; ?>


<?php if (isset($_GET["error"])): ?>

<div class="alert alert-danger alert-dismissible fade show">

    <i class="bi bi-exclamation-triangle-fill"></i>

    <strong>Error:</strong>

    <?= htmlspecialchars(
        $_GET["error"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>

    <!-- ENCABEZADO -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">

                <i class="bi bi-receipt"></i>

                Comprobantes de transferencias

            </h2>

            <div class="text-muted">

                Revisión de pagos realizados por los clientes.

            </div>

            
            <a  href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i>
                    Volver a pedidos
            </a>

        </div>

    </div>


    <!-- TABLA -->

    <div class="card shadow-sm">

        <div class="card-body p-0">

            <div class="tabla-contenedor">

                <table
                    class="table table-hover align-middle mb-0"
                >

                    <thead class="table-dark">

                        <tr>

                            <th>
                                Pedido
                            </th>

                            <th>
                                Cliente
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Importe
                            </th>

                            <th>
                                Comprobante
                            </th>

                            <th>
                                Estado
                            </th>

                            <th class="text-center">
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($comprobantes)): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5 text-muted"
                            >

                                <i
                                    class="bi bi-inbox"
                                    style="font-size:45px;"
                                ></i>

                                <div class="mt-2">

                                    No hay comprobantes
                                    para revisar.

                                </div>

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($comprobantes as $c): ?>


                            <?php

                            $estado = strtoupper(
                                trim(
                                    (string)$c['estado_comprobante']
                                )
                            );


                            $claseEstado = 'bg-secondary';


                            if ($estado === 'PENDIENTE') {

                                $claseEstado =
                                    'bg-warning text-dark';

                            }


                            if ($estado === 'APROBADO') {

                                $claseEstado =
                                    'bg-success';

                            }


                            if ($estado === 'RECHAZADO') {

                                $claseEstado =
                                    'bg-danger';

                            }


                            $archivo =
                                "../uploads/comprobantes/" .
                                $c['archivo'];


                            $extension = strtolower(
                                pathinfo(
                                    $c['archivo'],
                                    PATHINFO_EXTENSION
                                )
                            );


                            $esImagen = in_array(

                                $extension,

                                [
                                    'jpg',
                                    'jpeg',
                                    'png'
                                ],

                                true

                            );

                            ?>


                            <tr>


                                <!-- PEDIDO -->

                                <td>

                                    <strong>
                                        #<?= (int)$c['pedido_id'] ?>
                                    </strong>

                                </td>


                                <!-- CLIENTE -->

                                <td>

                                    <div class="fw-semibold">

                                        <?= htmlspecialchars(

                                            trim(
                                                $c['apellido'] .
                                                ' ' .
                                                $c['nombre']
                                            ),

                                            ENT_QUOTES,
                                            'UTF-8'

                                        ) ?>

                                    </div>

                                    <small class="text-muted">

                                        DNI:
                                        <?= htmlspecialchars(

                                            $c['dni'],

                                            ENT_QUOTES,

                                            'UTF-8'

                                        ) ?>

                                    </small>

                                </td>


                                <!-- FECHA -->

                                <td>

                                    <?= date(

                                        'd/m/Y H:i',

                                        strtotime(
                                            $c['fecha_comprobante']
                                        )

                                    ) ?>

                                </td>


                                <!-- IMPORTE -->

                                <td>

                                    <strong class="text-success">

                                        $
                                        <?= number_format(

                                            (float)$c['total'],

                                            2,

                                            ',',

                                            '.'

                                        ) ?>

                                    </strong>

                                </td>


                                <!-- COMPROBANTE -->

                                <td>


                                    <?php if ($esImagen): ?>


                                        <a
                                            href="<?= htmlspecialchars(
                                                $archivo,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            target="_blank"
                                            title="Abrir comprobante"
                                        >

                                            <img
                                                src="<?= htmlspecialchars(
                                                    $archivo,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                class="comprobante"
                                                alt="Comprobante"
                                            >

                                        </a>


                                    <?php else: ?>


                                        <a
                                            href="<?= htmlspecialchars(
                                                $archivo,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-danger"
                                        >

                                            <i class="bi bi-file-pdf"></i>

                                            Ver PDF

                                        </a>


                                    <?php endif; ?>


                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <span
                                        class="badge <?= $claseEstado ?>"
                                    >

                                        <?= htmlspecialchars(

                                            $estado,

                                            ENT_QUOTES,

                                            'UTF-8'

                                        ) ?>

                                    </span>

                                </td>


                                <!-- ACCIONES -->

                                <td class="text-center">


                                    <?php if ($estado === 'PENDIENTE'): ?>


                                        <div
                                            class="d-flex justify-content-center gap-2 flex-wrap"
                                        >


                                            <!-- APROBAR -->

                                            <form
                                                action="aprobar_comprobante.php"
                                                method="POST"
                                                class="formAprobar"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="comprobante_id"
                                                    value="<?= (int)$c['comprobante_id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-success btn-sm"
                                                    title="Aprobar pago"
                                                >

                                                    <i class="bi bi-check-lg"></i>

                                                    Aprobar

                                                </button>

                                            </form>


                                            <!-- RECHAZAR -->

                                            <form
                                                action="rechazar_comprobante.php"
                                                method="POST"
                                                class="formRechazar"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="comprobante_id"
                                                    value="<?= (int)$c['comprobante_id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    title="Rechazar comprobante"
                                                >

                                                    <i class="bi bi-x-lg"></i>

                                                    Rechazar

                                                </button>

                                            </form>


                                        </div>


                                    <?php else: ?>


                                        <span class="text-muted">

                                            Sin acciones

                                        </span>


                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>


<script>


/*
|--------------------------------------------------------------------------
| CONFIRMAR APROBACIÓN
|--------------------------------------------------------------------------
*/

document.querySelectorAll(".formAprobar")
.forEach(function(form) {

    form.addEventListener("submit", function(event) {

        const confirmar = confirm(
            "¿Está seguro de aprobar este pago?\n\n" +
            "Al aprobarlo se registrará la venta y se descontará " +
            "el stock correspondiente."
        );

        if (!confirmar) {

            event.preventDefault();

        }

    });

});


/*
|--------------------------------------------------------------------------
| CONFIRMAR RECHAZO
|--------------------------------------------------------------------------
*/

document.querySelectorAll(".formRechazar")
.forEach(function(form) {

    form.addEventListener("submit", function(event) {

        const confirmar = confirm(
            "¿Está seguro de rechazar este comprobante?"
        );

        if (!confirmar) {

            event.preventDefault();

        }

    });

});


</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>