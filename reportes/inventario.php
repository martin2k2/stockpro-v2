<?php

require_once "../includes/auth.php";

requireAdmin();

require_once "../config/conexion.php";


$pdo = Conexion::conectar();


$sql = "

SELECT

    p.codigo,

    p.nombre,

    c.nombre AS categoria,

    pr.nombre AS proveedor,


    CASE

        WHEN EXISTS (

            SELECT 1

            FROM producto_variantes pv

            WHERE pv.producto_id = p.id
            AND pv.activo = 1

        )

        THEN (

            SELECT COALESCE(
                SUM(pv.stock),
                0
            )

            FROM producto_variantes pv

            WHERE pv.producto_id = p.id
            AND pv.activo = 1

        )

        ELSE p.stock

    END AS stock,


    p.stock_minimo,

    p.precio_compra,

    p.precio_venta,


    (

        CASE

            WHEN EXISTS (

                SELECT 1

                FROM producto_variantes pv

                WHERE pv.producto_id = p.id
                AND pv.activo = 1

            )

            THEN (

                SELECT COALESCE(
                    SUM(pv.stock),
                    0
                )

                FROM producto_variantes pv

                WHERE pv.producto_id = p.id
                AND pv.activo = 1

            )

            ELSE p.stock

        END

        * p.precio_compra

    ) AS valor


FROM productos p


LEFT JOIN categorias c

    ON c.id = p.categoria_id


LEFT JOIN proveedores pr

    ON pr.id = p.proveedor_id


ORDER BY p.nombre

";


$productos = $pdo
    ->query($sql)
    ->fetchAll();


require_once "../includes/header.php";

require_once "../includes/sidebar.php";

?>


<div class="main">

    <?php require_once "../includes/navbar.php"; ?>


    <br><br>


    <div class="container-fluid">


        <div class="card shadow">


            <div class="card-header bg-primary text-white">


                <h3 class="mb-0">

                    <i class="bi bi-box-seam"></i>

                    Inventario General

                </h3>


            </div>


            <div class="card-body">


                <div class="mb-3">


                    <a
                        href="pdf/inventario.php"
                        class="btn btn-danger"
                    >

                        <i class="bi bi-file-earmark-pdf"></i>

                        PDF

                    </a>


                    <a
                        href="excel/inventario.php"
                        class="btn btn-success"
                    >

                        <i class="bi bi-file-earmark-excel"></i>

                        Exportar a Excel

                    </a>
                    
                    <a href="../reportes/index.php" class="btn btn-secondary"> <i class="bi bi-arrow-left"></i> Volver</a>

                </div>


                <div class="table-responsive">


                    <table
                        class="table table-bordered table-striped table-hover datatable"
                    >


                        <thead class="table-dark">


                            <tr>

                                <th>Código</th>

                                <th>Producto</th>

                                <th>Categoría</th>

                                <th>Proveedor</th>

                                <th>Stock</th>

                                <th>Mínimo</th>

                                <th>Compra</th>

                                <th>Venta</th>

                                <th>Valor</th>

                            </tr>


                        </thead>


                        <tbody>


                        <?php

                        $totalInventario = 0;


                        foreach (
                            $productos
                            as $p
                        ):

                            $totalInventario +=
                                (float)$p["valor"];

                        ?>


                            <tr>


                                <td>

                                    <?= htmlspecialchars(
                                        $p["codigo"] ?? ""
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $p["nombre"] ?? ""
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $p["categoria"] ?? ""
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $p["proveedor"] ?? ""
                                    ) ?>

                                </td>


                                <td>

                                    <?= (int)$p["stock"] ?>

                                </td>


                                <td>

                                    <?= (int)$p["stock_minimo"] ?>

                                </td>


                                <td>

                                    $

                                    <?= number_format(
                                        (float)$p["precio_compra"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </td>


                                <td>

                                    $

                                    <?= number_format(
                                        (float)$p["precio_venta"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </td>


                                <td>

                                    $

                                    <?= number_format(
                                        (float)$p["valor"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                        <tfoot>


                            <tr class="table-success">


                                <th
                                    colspan="8"
                                    class="text-end"
                                >

                                    Valor Total del Inventario

                                </th>


                                <th>

                                    $

                                    <?= number_format(
                                        $totalInventario,
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                </th>


                            </tr>


                        </tfoot>


                    </table>


                </div>


            </div>


        </div>


    </div>


</div>


<?php require_once "../includes/footer.php"; ?>