<?php

session_start();

require_once "../config/conexion.php";


if (!isset($_SESSION['usuario'])) {

    header("Location: ../login.php");

    exit;
}


$db = Conexion::conectar();


$stmt = $db->query("

    SELECT

        p.*,

        c.nombre AS categoria,

        pr.nombre AS proveedor,

        CASE

            WHEN EXISTS (

                SELECT 1

                FROM producto_variantes pv

                WHERE pv.producto_id = p.id
                AND pv.activo = 1

            )

            THEN 1

            ELSE 0

        END AS tiene_variantes,

        COALESCE(

            (

                SELECT SUM(pv.stock)

                FROM producto_variantes pv

                WHERE pv.producto_id = p.id
                AND pv.activo = 1

            ),

            0

        ) AS stock_variantes

    FROM productos p

    LEFT JOIN categorias c

        ON c.id = p.categoria_id

    LEFT JOIN proveedores pr

        ON pr.id = p.proveedor_id

    ORDER BY p.id DESC

");


$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Productos</title>


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
    background: #f8f9fa;
}

.card {
    border: 0;
}

.table th,
.table td {
    vertical-align: middle;
}

.imagen-producto {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

.sin-imagen {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    border-radius: 6px;
    color: #6c757d;
}

.badge-talles {
    font-size: 12px;
}

.stock-total {
    font-weight: 600;
}

</style>

</head>


<body>


<div class="container-fluid mt-4 px-3 px-md-4">


<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">


<div>

<h3 class="mb-1">

<i class="bi bi-box-seam"></i>

Productos

</h3>

<div class="text-muted small">

Gestión de productos y stock

</div>

</div>


<div class="d-flex gap-2 flex-wrap">


<a
    href="crear.php"
    class="btn btn-success"
>

<i class="bi bi-plus-circle"></i>

Nuevo Producto

</a>


<a
    href="../dashboard/index.php"
    class="btn btn-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver

</a>


</div>


</div>


<div class="card shadow-sm">


<div class="card-body">


<div class="table-responsive">


<table class="table table-bordered table-striped table-hover mb-0">


<thead class="table-dark">

<tr>

<th>Imagen</th>

<th>Código</th>

<th>Nombre</th>

<th>Categoría</th>

<th>Stock</th>

<th>Precio</th>

<th>Variantes</th>

<th>Acciones</th>

</tr>

</thead>


<tbody>


<?php if (empty($productos)) { ?>


<tr>

<td
    colspan="8"
    class="text-center text-muted py-4"
>

<i class="bi bi-box-seam fs-3"></i>

<div class="mt-2">

No hay productos registrados.

</div>

</td>

</tr>


<?php } ?>


<?php foreach ($productos as $p) { ?>


<?php

$tieneVariantes = (int)$p['tiene_variantes'] === 1;

$stockVariantes = (int)$p['stock_variantes'];

$stockMostrar = $tieneVariantes
    ? $stockVariantes
    : (int)$p['stock'];

?>


<tr>


<!-- IMAGEN -->

<td>


<?php if (!empty($p['imagen'])) { ?>


<img
    src="../uploads/productos/<?= htmlspecialchars($p['imagen'], ENT_QUOTES, 'UTF-8') ?>"
    class="imagen-producto"
    alt="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>"
>


<?php } else { ?>


<div class="sin-imagen">

<i class="bi bi-image"></i>

</div>


<?php } ?>


</td>


<!-- CODIGO -->

<td>

<?= htmlspecialchars(
    $p['codigo'] ?? '',
    ENT_QUOTES,
    'UTF-8'
) ?>

</td>


<!-- NOMBRE -->

<td>

<div class="fw-semibold">

<?= htmlspecialchars(
    $p['nombre'],
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>


<?php if ($tieneVariantes) { ?>

<span class="badge bg-info text-dark badge-talles mt-1">

<i class="bi bi-tags"></i>

Tiene talles/variantes

</span>

<?php } ?>


</td>


<!-- CATEGORIA -->

<td>

<?= htmlspecialchars(
    $p['categoria'] ?? 'Sin categoría',
    ENT_QUOTES,
    'UTF-8'
) ?>

</td>


<!-- STOCK -->

<td>


<?php if ($tieneVariantes) { ?>


<span class="stock-total">

<?= $stockMostrar ?>

</span>

<div>

<small class="text-muted">

Stock total por talles

</small>

</div>


<?php } else { ?>


<span class="stock-total">

<?= $stockMostrar ?>

</span>


<?php } ?>


</td>


<!-- PRECIO -->

<td>

$ <?= number_format(
    (float)$p['precio_venta'],
    2,
    ",",
    "."
) ?>

</td>


<!-- VARIANTES -->

<td>


<?php if ($tieneVariantes) { ?>


<a
    href="variantes.php?producto_id=<?= (int)$p['id'] ?>"
    class="btn btn-info btn-sm"
>

<i class="bi bi-tags"></i>

Ver talles

</a>


<?php } else { ?>


<span class="text-muted">

Sin talles

</span>


<?php } ?>


</td>


<!-- ACCIONES -->

<td>


<div class="d-flex gap-1 flex-wrap">


<a
    href="editar.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-warning btn-sm"
>

<i class="bi bi-pencil"></i>

Editar

</a>


<?php if ($tieneVariantes) { ?>


<a
    href="variantes.php?producto_id=<?= (int)$p['id'] ?>"
    class="btn btn-primary btn-sm"
>

<i class="bi bi-tags"></i>

Talles

</a>


<?php } else { ?>


<a
    href="variantes.php?producto_id=<?= (int)$p['id'] ?>"
    class="btn btn-outline-primary btn-sm"
>

<i class="bi bi-plus-circle"></i>

Agregar talles

</a>


<?php } ?>


<a
    href="eliminar.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-danger btn-sm"
    onclick="return confirm('¿Eliminar producto?')"
>

<i class="bi bi-trash"></i>

Eliminar

</a>


</div>


</td>


</tr>


<?php } ?>


</tbody>


</table>


</div>


</div>


</div>


</div>


</body>

</html>