<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$sql = "
    SELECT
        m.id,
        m.fecha,
        m.tipo,
        m.cantidad,
        m.observacion,
        p.codigo,
        p.nombre AS producto,
        u.nombre AS usuario
    FROM movimientos m

    INNER JOIN productos p
        ON p.id = m.producto_id

    LEFT JOIN usuarios_stock u
        ON u.id = m.usuario_id

    ORDER BY m.id DESC
";

$stmt = $conexion->query($sql);

$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Movimientos</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<link
rel="stylesheet"
href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main">

<div class="content">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

<h3>
<i class="bi bi-arrow-left-right"></i>
Movimientos de Stock
</h3>


<a
    href="../dashboard/index.php"
    class="btn btn-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver

</a>
</div>

<div class="card shadow">

<div class="card-body">

<div class="table-responsive">

<table class="table table-striped table-hover align-middle">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>Fecha</th>

<th>Código</th>

<th>Producto</th>

<th>Tipo</th>

<th>Cantidad</th>

<th>Observación</th>

<th>Usuario</th>

</tr>

</thead>

<tbody>

<?php if (empty($movimientos)): ?>

<tr>

<td colspan="8" class="text-center">

No hay movimientos registrados.

</td>

</tr>

<?php else: ?>

<?php foreach ($movimientos as $m): ?>

<tr>

<td>
<?= (int)$m["id"] ?>
</td>

<td>
<?= date(
    "d/m/Y H:i",
    strtotime($m["fecha"])
) ?>
</td>

<td>
<?= htmlspecialchars($m["codigo"]) ?>
</td>

<td>
<?= htmlspecialchars($m["producto"]) ?>
</td>

<td>

<?php if ($m["tipo"] === "ENTRADA"): ?>

<span class="badge bg-success">

<i class="bi bi-arrow-down-circle"></i>

ENTRADA

</span>

<?php elseif ($m["tipo"] === "SALIDA"): ?>

<span class="badge bg-danger">

<i class="bi bi-arrow-up-circle"></i>

SALIDA

</span>

<?php else: ?>

<span class="badge bg-secondary">

<?= htmlspecialchars($m["tipo"]) ?>

</span>

<?php endif; ?>

</td>

<td>

<strong>

<?= (int)$m["cantidad"] ?>

</strong>

</td>

<td>

<?= htmlspecialchars($m["observacion"] ?? "") ?>

</td>

<td>

<?= htmlspecialchars($m["usuario"] ?? "Sistema") ?>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

</body>

</html>