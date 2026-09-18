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
        c.apellido
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
| VALIDAR MÉTODO DE PAGO
|--------------------------------------------------------------------------
*/

if ($pedido['metodo_pago'] !== 'Transferencia') {

    header("Location: gracias.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| VALIDAR ESTADO
|--------------------------------------------------------------------------
*/

if (strtoupper(trim($pedido['estado'])) !== 'PENDIENTE') {

    header("Location: gracias.php");
    exit;

}


$error = $_SESSION['comprobante_error'] ?? '';

unset($_SESSION['comprobante_error']);

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
        Enviar comprobante - Tienda Urqui
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

        .pedido {
            font-size: 22px;
            font-weight: 700;
        }

        .importe {
            font-size: 30px;
            font-weight: 700;
            color: #198754;
        }

        .zona-subida {
            border: 2px dashed #ced4da;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            background: #f8f9fa;
            transition: 0.2s;
        }

        .zona-subida:hover {
            border-color: #0d6efd;
            background: #f0f6ff;
        }

        .icono-upload {
            font-size: 50px;
            color: #0d6efd;
        }

        .nombre-archivo {
            margin-top: 10px;
            font-weight: 600;
            word-break: break-word;
        }

    </style>

</head>


<body>


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">


            <!-- TITULO -->

            <div class="text-center mb-4">

                <i
                    class="bi bi-receipt"
                    style="font-size:55px;color:#0d6efd;"
                ></i>

                <h1 class="titulo mt-2">
                    Enviar comprobante
                </h1>

                <p class="text-muted">
                    Adjuntá el comprobante de tu transferencia.
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

                            <div class="pedido">
                                #<?= $pedido_id ?>
                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted">
                                Importe
                            </div>

                            <div class="importe">

                                $ <?= number_format(
                                    (float)$pedido['total'],
                                    2,
                                    ",",
                                    "."
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ERROR -->

            <?php if (!empty($error)): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-exclamation-triangle"></i>

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- FORMULARIO -->

            <div class="card shadow-sm border-0">

                <div class="card-body p-4">


                    <form
                        action="guardar_comprobante.php"
                        method="POST"
                        enctype="multipart/form-data"
                        id="formComprobante"
                    >


                        <input
                            type="hidden"
                            name="pedido_id"
                            value="<?= $pedido_id ?>"
                        >


                        <!-- ARCHIVO -->

                        <div class="zona-subida mb-4">

                            <i
                                class="bi bi-cloud-arrow-up icono-upload"
                            ></i>


                            <h5 class="mt-3">
                                Seleccionar comprobante
                            </h5>


                            <p class="text-muted mb-3">

                                Formatos permitidos:

                                <strong>
                                    JPG, JPEG, PNG o PDF
                                </strong>

                            </p>


                            <input
                                type="file"
                                name="comprobante"
                                id="comprobante"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.pdf"
                                required
                            >


                            <div
                                id="nombreArchivo"
                                class="nombre-archivo text-primary"
                            ></div>

                        </div>


                        <!-- AVISO -->

                        <div class="alert alert-info">

                            <div class="d-flex align-items-start gap-2">

                                <i class="bi bi-info-circle fs-4"></i>

                                <div>

                                    <strong>
                                        Importante
                                    </strong>

                                    <div class="mt-1">

                                        Verificá que el comprobante
                                        corresponda al importe del pedido
                                        antes de enviarlo.

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- BOTONES -->

                        <div class="d-grid gap-2">


                            <button
                                type="submit"
                                class="btn btn-success btn-lg"
                                id="btnEnviar"
                            >

                                <i class="bi bi-upload"></i>

                                Enviar comprobante

                            </button>


                            <a
                                href="transferencia.php"
                                class="btn btn-outline-secondary"
                            >

                                <i class="bi bi-arrow-left"></i>

                                Volver

                            </a>


                        </div>


                    </form>

                </div>

            </div>


        </div>

    </div>

</div>


<script>

const archivo =
    document.getElementById("comprobante");

const nombreArchivo =
    document.getElementById("nombreArchivo");

const formulario =
    document.getElementById("formComprobante");

const boton =
    document.getElementById("btnEnviar");


/*
|--------------------------------------------------------------------------
| MOSTRAR NOMBRE DEL ARCHIVO
|--------------------------------------------------------------------------
*/

archivo.addEventListener("change", function () {

    if (this.files.length > 0) {

        nombreArchivo.innerHTML =
            '<i class="bi bi-file-earmark-check"></i> ' +
            this.files[0].name;

    } else {

        nombreArchivo.innerHTML = "";

    }

});


/*
|--------------------------------------------------------------------------
| ENVIAR
|--------------------------------------------------------------------------
*/

formulario.addEventListener("submit", function (event) {

    if (!archivo.files.length) {

        event.preventDefault();

        alert("Seleccione el comprobante de la transferencia.");

        return;

    }


    const archivoSeleccionado =
        archivo.files[0];

    const nombre =
        archivoSeleccionado.name.toLowerCase();

    const extensionesPermitidas = [
        ".jpg",
        ".jpeg",
        ".png",
        ".pdf"
    ];


    const extensionValida =
        extensionesPermitidas.some(
            extension => nombre.endsWith(extension)
        );


    if (!extensionValida) {

        event.preventDefault();

        alert(
            "El comprobante debe ser JPG, JPEG, PNG o PDF."
        );

        return;

    }


    boton.disabled = true;

    boton.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        Enviando comprobante...
    `;

});

</script>


</body>

</html>