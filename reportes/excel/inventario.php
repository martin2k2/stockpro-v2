<?php

require_once "../../includes/auth.php";
requireAdmin();

require_once "../../config/conexion.php";
require_once "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$pdo = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| INVENTARIO
|--------------------------------------------------------------------------
|
| Producto normal:
| productos.stock
|
| Producto con variantes:
| SUM(producto_variantes.stock)
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


/*
|--------------------------------------------------------------------------
| CREAR EXCEL
|--------------------------------------------------------------------------
*/

$excel = new Spreadsheet();

$hoja = $excel->getActiveSheet();

$hoja->setTitle("Inventario");


/*
|--------------------------------------------------------------------------
| TITULO
|--------------------------------------------------------------------------
*/

$hoja->setCellValue(
    "A1",
    "REPORTE DE INVENTARIO"
);

$hoja->mergeCells(
    "A1:H1"
);

$hoja
    ->getStyle("A1")
    ->getFont()
    ->setBold(true)
    ->setSize(16);

$hoja
    ->getStyle("A1")
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );


/*
|--------------------------------------------------------------------------
| ENCABEZADOS
|--------------------------------------------------------------------------
*/

$hoja->fromArray(
    [
        "Código",
        "Producto",
        "Categoría",
        "Proveedor",
        "Stock",
        "Compra",
        "Venta",
        "Valor"
    ],
    null,
    "A3"
);


$hoja
    ->getStyle("A3:H3")
    ->getFont()
    ->setBold(true);


$hoja
    ->getStyle("A3:H3")
    ->getFill()
    ->setFillType(
        Fill::FILL_SOLID
    )
    ->getStartColor()
    ->setARGB(
        "FF0D6EFD"
    );


$hoja
    ->getStyle("A3:H3")
    ->getFont()
    ->getColor()
    ->setARGB(
        "FFFFFFFF"
    );


/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

$fila = 4;

$total = 0;


foreach ($productos as $p) {

    $stock =
        (int)$p["stock"];

    $valor =
        (float)$p["valor"];


    $hoja->setCellValue(
        "A" . $fila,
        $p["codigo"]
    );


    $hoja->setCellValue(
        "B" . $fila,
        $p["nombre"]
    );


    $hoja->setCellValue(
        "C" . $fila,
        $p["categoria"]
    );


    $hoja->setCellValue(
        "D" . $fila,
        $p["proveedor"]
    );


    $hoja->setCellValue(
        "E" . $fila,
        $stock
    );


    $hoja->setCellValue(
        "F" . $fila,
        (float)$p["precio_compra"]
    );


    $hoja->setCellValue(
        "G" . $fila,
        (float)$p["precio_venta"]
    );


    $hoja->setCellValue(
        "H" . $fila,
        $valor
    );


    $total +=
        $valor;


    $fila++;

}


/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

$hoja->setCellValue(
    "G" . $fila,
    "TOTAL"
);


$hoja->setCellValue(
    "H" . $fila,
    $total
);


$hoja
    ->getStyle(
        "G" . $fila .
        ":H" . $fila
    )
    ->getFont()
    ->setBold(true);


$hoja
    ->getStyle(
        "G" . $fila .
        ":H" . $fila
    )
    ->getFill()
    ->setFillType(
        Fill::FILL_SOLID
    )
    ->getStartColor()
    ->setARGB(
        "FFD1E7DD"
    );


/*
|--------------------------------------------------------------------------
| FORMATO MONEDA
|--------------------------------------------------------------------------
*/

$hoja
    ->getStyle(
        "F4:H" . $fila
    )
    ->getNumberFormat()
    ->setFormatCode(
        '$ #,##0.00'
    );


/*
|--------------------------------------------------------------------------
| AJUSTAR COLUMNAS
|--------------------------------------------------------------------------
*/

foreach (
    range("A", "H")
    as $col
) {

    $hoja
        ->getColumnDimension(
            $col
        )
        ->setAutoSize(true);

}


/*
|--------------------------------------------------------------------------
| DESCARGAR
|--------------------------------------------------------------------------
*/

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=Inventario.xlsx"
);

header(
    "Cache-Control: max-age=0"
);


$writer = new Xlsx(
    $excel
);


$writer->save(
    "php://output"
);

exit;
?>