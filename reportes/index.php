<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../includes/header.php";
//require_once "../includes/navbar.php";
require_once "../includes/sidebar.php";
?>

<div class="main">

<?php require_once "../includes/navbar.php"; ?>
<br><br>
<div class="container-fluid">
    

    <h2 class="mb-4">
        <i class="bi bi-file-earmark-bar-graph"></i>
        Reportes
    </h2>
    
    
    <div class="row">

        <div class="col-md-3 mb-4">

            <div class="card shadow text-center">

                <div class="card-body">

                    <i class="bi bi-box-seam display-3 text-primary"></i>

                    <h5 class="mt-3">Inventario</h5>

                    <a href="inventario.php" class="btn btn-primary w-100">
                        Ver Reporte
                    </a>

                </div>

            </div>

        </div>

        <div class="col-md-3 mb-4">

            <div class="card shadow text-center">

                <div class="card-body">

                    <i class="bi bi-exclamation-triangle display-3 text-danger"></i>

                    <h5 class="mt-3">Stock Bajo</h5>

                    <a href="stock_bajo.php" class="btn btn-danger w-100">
                        Ver Reporte
                    </a>

                </div>

            </div>

        </div>

        <div class="col-md-3 mb-4">

            <div class="card shadow text-center">

                <div class="card-body">

                    <i class="bi bi-arrow-left-right display-3 text-success"></i>

                    <h5 class="mt-3">Movimientos</h5>

                    <a href="movimientos.php" class="btn btn-success w-100">
                        Ver Reporte
                    </a>

                </div>

            </div>

        </div>
<!--
        <div class="col-md-3 mb-4">

            <div class="card shadow text-center">

                <div class="card-body">

                    <i class="bi bi-file-earmark-pdf display-3 text-secondary"></i>

                    <h5 class="mt-3">Exportaciones</h5>

                    <a href="inventario.php" class="btn btn-secondary w-100">
                    Ver Reporte
                    </a>

                </div>

            </div>

        </div>-->

    </div>
    <a
    href="../dashboard/index.php"
    class="btn btn-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver

</a>
</div>