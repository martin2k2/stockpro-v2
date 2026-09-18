<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "../../includes/auth.php";
requireAdmin();

require_once "../../config/conexion.php";
require_once "../../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$pdo = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| INVENTARIO
|--------------------------------------------------------------------------
|
| Productos normales:
| stock = productos.stock
|
| Productos con variantes:
| stock = SUM(producto_variantes.stock)
|
*/

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
                SELECT COALESCE(SUM(pv.stock), 0)
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
                    SELECT COALESCE(SUM(pv.stock), 0)
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

    ORDER BY p.nombre ASC
";


$productos = $pdo
    ->query($sql)
    ->fetchAll(PDO::FETCH_ASSOC);


$total = 0;


$html = '

<style>

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #0d6efd;
    color: white;
}

th,
td {
    border: 1px solid #ccc;
    padding: 6px;
    text-align: left;
}

.total {
    background: #eeeeee;
    font-weight: bold;
}

</style>


<h2>REPORTE DE INVENTARIO</h2>


<table>

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
';


foreach ($productos as $p) {

    $stock = (int)$p["stock"];

    $valor = (float)$p["valor"];

    $total += $valor;


    $html .= "

    <tr>

        <td>" .
            htmlspecialchars($p["codigo"] ?? "") .
        "</td>

        <td>" .
            htmlspecialchars($p["nombre"] ?? "") .
        "</td>

        <td>" .
            htmlspecialchars($p["categoria"] ?? "") .
        "</td>

        <td>" .
            htmlspecialchars($p["proveedor"] ?? "") .
        "</td>

        <td>" .
            $stock .
        "</td>

        <td>" .
            (int)$p["stock_minimo"] .
        "</td>

        <td>$ " .
            number_format(
                (float)$p["precio_compra"],
                2,
                ",",
                "."
            ) .
        "</td>

        <td>$ " .
            number_format(
                (float)$p["precio_venta"],
                2,
                ",",
                "."
            ) .
        "</td>

        <td>$ " .
            number_format(
                $valor,
                2,
                ",",
                "."
            ) .
        "</td>

    </tr>

    ";

}


$html .= "

<tr class='total'>

    <td
        colspan='8'
        style='text-align:right'
    >

        TOTAL INVENTARIO

    </td>

    <td>

        $ " .
        number_format(
            $total,
            2,
            ",",
            "."
        ) .
    "

    </td>

</tr>


</table>
";


$options = new Options();

$options->set(
    'defaultFont',
    'DejaVu Sans'
);


$dompdf = new Dompdf(
    $options
);


$dompdf->loadHtml(
    $html
);


$dompdf->setPaper(
    'A4',
    'landscape'
);


$dompdf->render();


$dompdf->stream(
    "Inventario.pdf",
    [
        "Attachment" => false
    ]
);

?>