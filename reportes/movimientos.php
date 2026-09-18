<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../config/conexion.php";

$pdo = Conexion::conectar();

$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";

$sql = "SELECT
            m.fecha,
            p.codigo,
            p.nombre AS producto,
            m.tipo,
            m.cantidad,
            u.nombre AS usuario,
            m.observacion
        FROM movimientos m
        INNER JOIN productos p ON p.id = m.producto_id
        INNER JOIN usuarios_stock u ON u.id = m.usuario_id
        WHERE 1=1";

$params = [];

if ($desde != "") {
    $sql .= " AND DATE(m.fecha)>=?";
    $params[] = $desde;
}

if ($hasta != "") {
    $sql .= " AND DATE(m.fecha)<=?";
    $params[] = $hasta;
}

$sql .= " ORDER BY m.fecha DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="main">

<?php require_once "../includes/navbar.php"; ?>

<br><br>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h3 class="mb-0">
<i  class="bi bi-arrow-left-right"></i>
Reporte de Movimientos
</h3>



</div>

<div class="card-body">

<form method="GET" class="row g-3 mb-3">

    <div class="col-md-3">
        <label class="form-label">Desde</label>
        <input
            type="date"
            name="desde"
            class="form-control"
            value="<?= htmlspecialchars($desde) ?>">
    </div>

    <div class="col-md-3">
        <label class="form-label">Hasta</label>
        <input
            type="date"
            name="hasta"
            class="form-control"
            value="<?= htmlspecialchars($hasta) ?>">
    </div>

    <div class="col-md-3 d-flex align-items-end">

        <button class="btn btn-success me-2">
            <i class="bi bi-search"></i>
            Buscar
        </button>

        <a href="movimientos.php" class="btn btn-secondary">
            Limpiar
        </a>

    </div>
    

</form>

<div class="mb-3">

<!--<a href="pdf/inventario.php" class="btn btn-danger">
    <i class="bi bi-file-earmark-pdf"></i>
    PDF
</a>

<a href="excel/inventario.php" class="btn btn-success">
    <i class="bi bi-file-earmark-excel"></i>
    Excel
</a>
-->
</div>

<div class="table-responsive">

<table class="table table-bordered table-striped table-hover datatable">

<thead class="table-dark">

<tr>

<th>Fecha</th>
<th>Código</th>
<th>Producto</th>
<th>Tipo</th>
<th>Cantidad</th>
<th>Usuario</th>
<th>Observación</th>

</tr>

</thead>

<tbody>

<?php foreach($movimientos as $m): ?>

<tr>

<td><?= date("d/m/Y H:i",strtotime($m["fecha"])) ?></td>

<td><?= htmlspecialchars($m["codigo"]) ?></td>

<td><?= htmlspecialchars($m["producto"]) ?></td>

<td>

<?php if($m["tipo"]=="ENTRADA"){ ?>

<span class="badge bg-success">ENTRADA</span>

<?php }else{ ?>

<span class="badge bg-danger">SALIDA</span>

<?php } ?>

</td>

<td><?= $m["cantidad"] ?></td>

<td><?= htmlspecialchars($m["usuario"]) ?></td>

<td><?= htmlspecialchars($m["observacion"]) ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

<?php require_once "../includes/footer.php"; ?>