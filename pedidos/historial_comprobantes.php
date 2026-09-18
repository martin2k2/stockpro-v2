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

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login/index.php");
    exit;
}

if (strtoupper(trim($_SESSION["rol"] ?? "")) !== "ADMIN") {
    http_response_code(403);
    exit("Acceso denegado.");
}


try {

    $db = Conexion::conectar();


    /*
    |--------------------------------------------------------------------------
    | OBTENER HISTORIAL DE COMPROBANTES
    |--------------------------------------------------------------------------
    */

    $sql = "
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
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $comprobantes = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (Throwable $e) {

    $error = $e->getMessage();
    $comprobantes = [];

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Historial de comprobantes</title>

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
            background: #f4f6f9;
        }

        .contenedor {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .titulo {
            font-size: 30px;
            font-weight: 700;
            color: #212529;
        }

        .subtitulo {
            color: #6c757d;
            margin-bottom: 25px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.08);
        }

        .tabla {
            vertical-align: middle;
        }

        .comprobante-preview {
            width: 100px;
            height: 75px;
            object-fit: contain;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background: #fff;
        }

        .pdf-preview {
            width: 100px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            color: #dc3545;
            font-size: 35px;
        }

        .estado {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }

        .estado-pendiente {
            background: #ffc107;
            color: #212529;
        }

        .estado-aprobado {
            background: #198754;
            color: white;
        }

        .estado-rechazado {
            background: #dc3545;
            color: white;
        }

        .cliente {
            font-weight: 600;
        }

        .dni {
            font-size: 13px;
            color: #6c757d;
        }

    </style>

</head>

<body>

<div class="contenedor">

    <div class="mb-4">

        <div class="titulo">
            <i class="bi bi-clock-history"></i>
            Historial de comprobantes
        </div>

        <div class="subtitulo">
            Consulta de comprobantes de pago enviados por los clientes.
        </div>

        <a
            href="comprobantes.php"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Volver a comprobantes
        </a>

    </div>


    <?php if (isset($error)): ?>

        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            Error: <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <div class="card">

        <div class="card-body p-0">

            <?php if (empty($comprobantes)): ?>

                <div class="text-center p-5">

                    <i
                        class="bi bi-file-earmark-x"
                        style="font-size:50px;color:#adb5bd;"
                    ></i>

                    <h5 class="mt-3">
                        No hay comprobantes registrados
                    </h5>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover mb-0 tabla">

                        <thead class="table-dark">

                            <tr>

                                <th>Pedido</th>

                                <th>Cliente</th>

                                <th>Fecha</th>

                                <th>Importe</th>

                                <th>Comprobante</th>

                                <th>Estado</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($comprobantes as $c): ?>

                            <?php

                            $estado = strtoupper(
                                trim(
                                    (string)$c["estado_comprobante"]
                                )
                            );

                            $claseEstado = "estado-pendiente";

                            if ($estado === "APROBADO") {
                                $claseEstado = "estado-aprobado";
                            }

                            if ($estado === "RECHAZADO") {
                                $claseEstado = "estado-rechazado";
                            }

                            $archivo = "../uploads/comprobantes/" .
                                $c["archivo"];

                            $extension = strtolower(
                                pathinfo(
                                    $c["archivo"],
                                    PATHINFO_EXTENSION
                                )
                            );

                            ?>

                            <tr>

                                <td>

                                    <strong>
                                        #<?= (int)$c["pedido_id"] ?>
                                    </strong>

                                </td>


                                <td>

                                    <div class="cliente">

                                        <?= htmlspecialchars(
                                            trim(
                                                $c["apellido"] .
                                                " " .
                                                $c["nombre"]
                                            )
                                        ) ?>

                                    </div>

                                    <div class="dni">

                                        DNI:
                                        <?= htmlspecialchars(
                                            $c["dni"]
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <?= date(
                                        "d/m/Y H:i",
                                        strtotime(
                                            $c["fecha_comprobante"]
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <strong class="text-success">

                                        $ <?= number_format(
                                            (float)$c["total"],
                                            2,
                                            ",",
                                            "."
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                            

                                        <?php if (
                                            in_array(
                                                    $extension,
                                                    ["jpg", "jpeg", "png"]
                                                        )
                                                ): ?>

    <img
        src="<?= htmlspecialchars($archivo) ?>"
        class="comprobante-preview"
        alt="Comprobante"
        data-bs-toggle="modal"
        data-bs-target="#modalComprobante"
        data-imagen="<?= htmlspecialchars($archivo) ?>"
        style="cursor:pointer;"
    >

<?php elseif ($extension === "pdf"): ?>



                                        <div class="pdf-preview">

                                            <i class="bi bi-file-earmark-pdf"></i>

                                        </div>

                                    <?php else: ?>

                                        <div class="pdf-preview">

                                            <i class="bi bi-file-earmark"></i>

                                        </div>

                                    <?php endif; ?>

                                    <div class="small text-muted mt-1">

                                        <?= htmlspecialchars(
                                            $c["nombre_original"] ??
                                            $c["archivo"]
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <span class="estado <?= $claseEstado ?>">

                                        <?= htmlspecialchars(
                                            $estado
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>
<!-- MODAL COMPROBANTE -->

<div
    class="modal fade"
    id="modalComprobante"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    <i class="bi bi-image"></i>
                    Comprobante de pago
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>

            </div>

            <div class="modal-body text-center">

                <img
                    id="imagenComprobanteGrande"
                    src=""
                    alt="Comprobante ampliado"
                    style="
                        max-width:100%;
                        max-height:75vh;
                        object-fit:contain;
                    "
                >

            </div>

        </div>

    </div>
</div>


<!-- BOOTSTRAP -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalComprobante");

    const imagenGrande =
        document.getElementById("imagenComprobanteGrande");


    modal.addEventListener("show.bs.modal", function (event) {

        const imagen = event.relatedTarget;

        const ruta = imagen.getAttribute("data-imagen");

        imagenGrande.src = ruta;

    });


    modal.addEventListener("hidden.bs.modal", function () {

        imagenGrande.src = "";

    });

});

</script>
</body>

</html>