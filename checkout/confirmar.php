<?php

session_start();

if (!isset($_SESSION["cliente_id"])) {
    header("Location: ../clientes/login.php");
    exit;
}

if (empty($_SESSION["carrito"])) {
    header("Location: ../carrito/index.php");
    exit;
}

$total = isset($_POST["total"])
    ? (float)$_POST["total"]
    : 0;

if ($total <= 0) {
    header("Location: ../carrito/index.php");
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

    <title>Confirmar compra - Tienda Urqui</title>

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
            overflow-x: hidden;
        }

        .metodo-pago-contenedor {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
        }

        .metodo-pago-dropdown {
            width: 100%;
            max-width: 100%;
        }

        .metodo-pago-btn {
            width: 100%;
            max-width: 100%;
            height: 44px;
            display: flex;
            align-items: center;
            text-align: left;
            padding: 8px 42px 8px 12px;
            position: relative;
            overflow: hidden;
        }

        .metodo-pago-btn .texto-metodo {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
            width: 100%;
        }

        .metodo-pago-btn::after {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
        }

        .metodo-pago-menu {
            width: 100%;
            max-width: 100%;
            min-width: 0 !important;
            max-height: 250px;
            overflow-y: auto;
            overflow-x: hidden;
            box-sizing: border-box;
        }

        .metodo-pago-menu .dropdown-item {
            width: 100%;
            padding: 12px 14px;
            white-space: normal;
            overflow-wrap: break-word;
            word-break: break-word;
            font-size: 15px;
        }

        .metodo-pago-menu .dropdown-item i {
            margin-right: 8px;
        }

        #infoPago {
            width: 100%;
            box-sizing: border-box;
        }

        @media (max-width: 576px) {

            .container {
                width: 100%;
                max-width: 100%;
                padding-left: 12px !important;
                padding-right: 12px !important;
            }

            .metodo-pago-contenedor {
                width: 100%;
                max-width: 100%;
            }

            .metodo-pago-dropdown {
                width: 100%;
                max-width: 100%;
            }

            .metodo-pago-btn {
                width: 100%;
                max-width: 100%;
                height: 44px;
                font-size: 14px;
            }

            .metodo-pago-menu {
                width: 100%;
                max-width: 100%;
            }

            .metodo-pago-menu .dropdown-item {
                font-size: 14px;
                padding: 12px;
            }

            .card-body {
                padding: 20px !important;
            }

            .display-5 {
                font-size: 2rem;
            }

        }

    </style>

</head>

<body class="bg-light">


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-12 col-md-7 col-lg-6">

            <div class="card shadow border-0">


                <!-- HEADER -->

                <div class="card-header bg-primary text-white">

                    <h3 class="mb-0">

                        <i class="bi bi-cart-check"></i>

                        Confirmar compra

                    </h3>

                </div>


                <!-- BODY -->

                <div class="card-body p-4">


                    <!-- TOTAL -->

                    <div class="text-center mb-4">

                        <div class="text-muted">

                            Total de la compra

                        </div>


                        <div class="display-5 fw-bold text-success">

                            $ <?= number_format($total, 2, ",", ".") ?>

                        </div>

                    </div>


                    <!-- FORMULARIO -->

                    <form
                        action="procesar.php"
                        method="POST"
                        id="formCompra"
                    >


                        <input
                            type="hidden"
                            name="total"
                            value="<?= htmlspecialchars((string)$total) ?>"
                        >


                        <!-- METODO DE PAGO -->

                        <div class="metodo-pago-contenedor">


                            <label class="form-label fw-bold">

                                Método de pago

                            </label>


                            <div class="dropdown metodo-pago-dropdown">


                                <button
                                    type="button"
                                    class="btn btn-outline-secondary dropdown-toggle metodo-pago-btn"
                                    id="btnMetodoPago"
                                    data-bs-toggle="dropdown"
                                    data-bs-boundary="viewport"
                                    data-bs-display="static"
                                    aria-expanded="false"
                                >

                                    <span class="texto-metodo">

                                        Seleccionar método de pago

                                    </span>

                                </button>


                                <ul
                                    class="dropdown-menu metodo-pago-menu"
                                    aria-labelledby="btnMetodoPago"
                                >


                                    <!-- TRANSFERENCIA -->

                                    <li>

                                        <button
                                            type="button"
                                            class="dropdown-item opcion-metodo"
                                            data-value="TRANSFERENCIA"
                                            data-texto="Transferencia bancaria"
                                        >

                                            <i class="bi bi-bank"></i>

                                            Transferencia bancaria

                                        </button>

                                    </li>


                                    <!-- EFECTIVO -->

                                    <li>

                                        <button
                                            type="button"
                                            class="dropdown-item opcion-metodo"
                                            data-value="EFECTIVO"
                                            data-texto="Efectivo - Pago presencial"
                                        >

                                            <i class="bi bi-cash-coin"></i>

                                            Efectivo - Pago presencial

                                        </button>

                                    </li>


                                </ul>

                            </div>


                            <input
                                type="hidden"
                                name="metodo_pago"
                                id="metodo_pago"
                                value=""
                            >

                        </div>


                        <!-- INFORMACION DEL PAGO -->

                        <div
                            id="infoPago"
                            class="alert alert-info d-none"
                        ></div>


                        <!-- BOTONES -->

                        <div class="d-grid gap-2">


                            <button
                                type="submit"
                                class="btn btn-success btn-lg"
                                id="btnConfirmar"
                            >

                                <i class="bi bi-check-circle"></i>

                                Confirmar pedido

                            </button>


                            <a
                                href="../carrito/index.php"
                                class="btn btn-outline-secondary"
                            >

                                <i class="bi bi-arrow-left"></i>

                                Volver al carrito

                            </a>


                        </div>


                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

document.addEventListener("DOMContentLoaded", function () {


    const formCompra =
        document.getElementById("formCompra");


    const boton =
        document.getElementById("btnConfirmar");


    const botonMetodo =
        document.getElementById("btnMetodoPago");


    const metodoPago =
        document.getElementById("metodo_pago");


    const infoPago =
        document.getElementById("infoPago");


    const opciones =
        document.querySelectorAll(".opcion-metodo");


    /*
    ==========================================
    SELECCIONAR METODO DE PAGO
    ==========================================
    */

    opciones.forEach(function (opcion) {

        opcion.addEventListener("click", function () {

            const valor =
                this.dataset.value;


            const texto =
                this.dataset.texto;


            metodoPago.value =
                valor;


            botonMetodo
                .querySelector(".texto-metodo")
                .textContent =
                texto;


            infoPago.classList.add("d-none");

            infoPago.innerHTML = "";


            /*
            ==========================================
            TRANSFERENCIA
            ==========================================
            */

            if (valor === "TRANSFERENCIA") {

                infoPago.innerHTML = `

                    <div class="d-flex align-items-start gap-2">

                        <i class="bi bi-bank fs-4"></i>

                        <div>

                            <strong>
                                Transferencia bancaria
                            </strong>

                            <div class="mt-1">

                                Al confirmar el pedido se mostrarán
                                los datos bancarios para realizar
                                la transferencia.

                            </div>

                            <div class="mt-2">

                                El pedido quedará pendiente hasta
                                que se verifique el pago.

                            </div>

                        </div>

                    </div>

                `;


                infoPago.classList.remove("d-none");

            }


            /*
            ==========================================
            EFECTIVO
            ==========================================
            */

            if (valor === "EFECTIVO") {

                infoPago.innerHTML = `

                    <div class="d-flex align-items-start gap-2">

                        <i class="bi bi-cash-coin fs-4"></i>

                        <div>

                            <strong>
                                Pago en efectivo
                            </strong>

                            <div class="mt-1">

                                El pago se realizará de manera
                                presencial al retirar o recibir
                                el pedido.

                            </div>

                            <div class="mt-2">

                                El pedido quedará pendiente hasta
                                confirmar el pago.

                            </div>

                        </div>

                    </div>

                `;


                infoPago.classList.remove("d-none");

            }

        });

    });


    /*
    ==========================================
    ENVIAR FORMULARIO
    ==========================================
    */

    formCompra.addEventListener("submit", function (event) {


        const metodo =
            metodoPago.value;


        if (!metodo) {

            event.preventDefault();


            alert(
                "Seleccione un método de pago."
            );


            botonMetodo.focus();


            return;

        }


        /*
        DESHABILITAR BOTON
        */

        boton.disabled = true;


        boton.innerHTML = `

            <span
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
            ></span>

            Generando pedido...

        `;

    });


});

</script>


</body>

</html>