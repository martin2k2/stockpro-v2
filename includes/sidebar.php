<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = strtoupper(
    trim((string)($_SESSION["rol"] ?? ""))
);

$esAdmin = ($rol === "ADMIN");
$esOperador = ($rol === "OPERADOR");

?>

<div class="sidebar" id="sidebar">

    <div class="logo">

        <img
            src="../assets/img/LOGO NUEVO.png"
            alt="URQUI"
            class="logo-img"
        >

        <span>Sistema de ventas</span>

    </div>


    <nav class="menu">


        <!-- =====================================================
             INICIO
        ====================================================== -->

        <a
            href="/stockpro-v2/dashboard/index.php"
            class="<?= basename($_SERVER["PHP_SELF"]) === "index.php"
                && strpos($_SERVER["REQUEST_URI"], "/dashboard/") !== false
                ? "active"
                : "" ?>"
        >

            <i class="bi bi-speedometer2"></i>

            <span>Inicio</span>

        </a>


        <!-- =====================================================
             PUNTO DE VENTA
             ADMIN + OPERADOR
        ====================================================== -->

        <?php if ($esAdmin || $esOperador): ?>

            <a
                href="/stockpro-v2/ventas/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/ventas/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-cart-check"></i>

                <span>Punto de Venta</span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             PRODUCTOS ADMIN + OPERADOR
        ====================================================== -->

        <?php if ($esAdmin): ?>

            <a
                href="/stockpro-v2/productos/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/productos/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-box-seam"></i>

                <span>Productos</span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             CATEGORÍAS SOLO ADMIN
        ====================================================== -->

        <?php if ($esAdmin): ?>

            <a
                href="/stockpro-v2/categorias/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/categorias/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-tags"></i>

                <span>Categorías</span>

            </a>

        <?php endif; ?>

        <!-- =====================================================
            CLIENTES SOLO ADMIN
        ====================================================== -->

        <?php if ($esAdmin): ?>

            <a href="/stockpro-v2/clientes/index.php" class="<?= strpos($_SERVER["REQUEST_URI"], "/clientes/") !== false ? "active" : "" ?>" >
                <i class="bi bi-people-fill "></i>
                <span>Clientes</span>
            </a>
        <?php endif; ?>



        <!-- =====================================================
             PROVEEDORES SOLO ADMIN
        ====================================================== -->

        <?php if ($esAdmin): ?>

            <a
                href="/stockpro-v2/proveedores/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/proveedores/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-truck"></i>

                <span>Proveedores</span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             MOVIMIENTOS
             ADMIN + OPERADOR
        ====================================================== -->

        <?php if ($esAdmin || $esOperador): ?>

            <a
                href="/stockpro-v2/movimientos/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/movimientos/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-arrow-left-right"></i>

                <span>Movimientos</span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             TIENDA
             SOLO ADMIN
        ====================================================== -->

        <?php if ($esAdmin): ?>

            <a
                href="/stockpro-v2/tienda/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/tienda/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-cart"></i>

                <span>Tienda</span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             PEDIDOS
             SOLO ADMIN
        ====================================================== -->

        <?php if ($esAdmin): ?>

            <a
                href="/stockpro-v2/pedidos/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/pedidos/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-receipt"></i>

                <span>Pedidos</span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             CAJA
             ADMIN + OPERADOR
        ====================================================== -->

        <?php if ($esAdmin || $esOperador): ?>

            <a
                href="/stockpro-v2/caja/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/caja/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-cash-stack"></i>

                <span>Caja</span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             REPORTES
             SOLO ADMIN
        ====================================================== -->

        <?php if ($esAdmin): ?>

            <a
                href="/stockpro-v2/reportes/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/reportes/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-bar-chart-line"></i>

                <span>Reportes</span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             USUARIOS
             SOLO ADMIN
        ====================================================== -->

        <?php if ($esAdmin): ?>

            <a
                href="/stockpro-v2/usuarios/index.php"
                class="<?= strpos(
                    $_SERVER["REQUEST_URI"],
                    "/usuarios/"
                ) !== false ? "active" : "" ?>"
            >

                <i class="bi bi-people"></i>

                <span>Usuarios</span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             CERRAR SESIÓN
        ====================================================== -->

        <a
            href="/stockpro-v2/logout.php"
            id="btnCerrarSesion"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>Cerrar sesión</span>

        </a>


    </nav>

</div>


<!-- =========================================================
     SWEETALERT
========================================================= -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const btnCerrarSesion =
        document.getElementById("btnCerrarSesion");

    if (!btnCerrarSesion) {
        return;
    }


    btnCerrarSesion.addEventListener("click", function (e) {

        e.preventDefault();


        fetch("/stockpro-v2/caja/verificar_abierta.php", {
            method: "GET",
            credentials: "same-origin"
        })

        .then(response => response.json())

        .then(data => {

            if (data.abierta) {

                Swal.fire({

                    icon: "warning",

                    title: "Caja abierta",

                    html: `
                        <p class="mb-2">
                            Tenés una caja abierta.
                        </p>

                        <strong>
                            Recordá cerrar la caja antes de cerrar sesión.
                        </strong>
                    `,

                    showCancelButton: true,

                    confirmButtonText:
                        '<i class="bi bi-cash-stack"></i> Ir a Caja',

                    cancelButtonText:
                        'Cerrar sesión igualmente',

                    reverseButtons: true

                }).then((result) => {

                    if (result.isConfirmed) {

                        window.location.href =
                            "/stockpro-v2/caja/index.php";

                    } else if (
                        result.dismiss ===
                        Swal.DismissReason.cancel
                    ) {

                        window.location.href =
                            "/stockpro-v2/logout.php";

                    }

                });

            } else {

                window.location.href =
                    "/stockpro-v2/logout.php";

            }

        })

        .catch(error => {

            console.error(
                "Error verificando caja:",
                error
            );

            /*
             * Si falla la comprobación,
             * permitimos cerrar sesión.
             */

            window.location.href =
                "/stockpro-v2/logout.php";

        });

    });

});

</script>
