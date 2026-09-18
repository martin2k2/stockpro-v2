<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../config/conexion.php";

$pdo = Conexion::conectar();

$sql = "SELECT
            p.codigo,
            p.nombre,
            c.nombre AS categoria,
            pr.nombre AS proveedor,
            p.stock,
            p.stock_minimo
        FROM productos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
        WHERE p.stock <= p.stock_minimo
        ORDER BY p.stock ASC";

$productos = $pdo->query($sql)->fetchAll();

require_once "../includes/header.php";
//require_once "../includes/navbar.php";
require_once "../includes/sidebar.php";
?>

<div class="main">

<?php require_once "../includes/navbar.php"; ?>
<br><br>
<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-danger text-white">

<h3>
<i class="bi bi-exclamation-triangle-fill"></i>
Productos con Stock Bajo
</h3>

</div>

<div class="card-body">

<?php if(count($productos)==0): ?>

<div class="alert alert-success">

<i class="bi bi-check-circle"></i>

No existen productos con stock bajo.

</div>

<?php else: ?>

<table class="table table-bordered table-striped table-hover datatable">

<thead class="table-dark">

<a href="pdf/inventario.php" class="btn btn-danger">
    <i class="bi bi-file-earmark-pdf"></i>
    PDF
</a>

<a href="excel/inventario.php" class="btn btn-success">
    <i class="bi bi-file-earmark-excel"></i>
    Excel
</a>

<a
    href="../reportes/index.php"
    class="btn btn-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver

</a>
<br>
<br>
<tr>

<th>Código</th>
<th>Producto</th>
<th>Categoría</th>
<th>Proveedor</th>
<th>Stock</th>
<th>Stock Mínimo</th>
<th>Estado</th>

</tr>

</thead>

<tbody>

<?php foreach($productos as $p): ?>

<tr>

<td><?= htmlspecialchars($p["codigo"]) ?></td>

<td><?= htmlspecialchars($p["nombre"]) ?></td>

<td><?= htmlspecialchars($p["categoria"]) ?></td>

<td><?= htmlspecialchars($p["proveedor"]) ?></td>

<td><?= $p["stock"] ?></td>

<td><?= $p["stock_minimo"] ?></td>

<td>

<span class="badge bg-danger">

REPOSICIÓN URGENTE

</span>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php endif; ?>

</div>

</div>

</div>

<?php require_once "../includes/footer.php"; ?>