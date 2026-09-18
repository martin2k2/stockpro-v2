<?php
// clientes/index.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$sql = "
    SELECT
        id,
        dni,
        apellido,
        nombre,
        telefono,
        direccion,
        email,
        estado,
        fecha_alta
    FROM clientes
    ORDER BY apellido, nombre
";

$clientes = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>

<html lang="es">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1">

<title>Clientes</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet">

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet">

<link
    href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"
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

<!-- ENCABEZADO -->

<div class="d-flex justify-content-between align-items-center mb-4">

<h3>

<i class="bi bi-people-fill"></i>

Clientes

</h3>

<a
    href="nuevo.php"
    class="btn btn-success">

<i class="bi bi-plus-circle"></i>

Nuevo Cliente

</a>
<a
    href="../dashboard/index.php"
    class="btn btn-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver

</a>

</div>


<!-- TABLA -->

<div class="card shadow">

<div class="card-body">

<div class="table-responsive">

<table
    class="table table-bordered table-hover datatable align-middle">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>DNI</th>

<th>Apellido</th>

<th>Nombre</th>

<th>Teléfono</th>

<th>Email</th>

<th>Estado</th>

<th>Alta</th>

<th width="220">Acciones</th>

</tr>

</thead>


<tbody>

<?php foreach ($clientes as $c): ?>

<tr>

<!-- ID -->

<td>

<?= (int)$c["id"] ?>

</td>


<!-- DNI -->

<td>

<?= htmlspecialchars($c["dni"] ?? "") ?>

</td>


<!-- APELLIDO -->

<td>

<?= htmlspecialchars($c["apellido"] ?? "") ?>

</td>


<!-- NOMBRE -->

<td>

<?= htmlspecialchars($c["nombre"] ?? "") ?>

</td>


<!-- TELEFONO -->

<td>

<?= htmlspecialchars($c["telefono"] ?? "") ?>

</td>


<!-- EMAIL -->

<td>

<?= htmlspecialchars($c["email"] ?? "") ?>

</td>


<!-- ESTADO -->

<td>

<?php

$estado = strtoupper(
    trim((string)$c["estado"])
);

if (
    $estado === "1" ||
    $estado === "ACTIVO"
):

?>

<span class="badge bg-success">

Activo

</span>

<?php else: ?>

<span class="badge bg-danger">

Inactivo

</span>

<?php endif; ?>

</td>


<!-- FECHA -->

<td>

<?php

if (!empty($c["fecha_alta"])) {

    echo date(
        "d/m/Y",
        strtotime($c["fecha_alta"])
    );

}

?>

</td>


<!-- ACCIONES -->

<td>

<a
    href="detalle.php?id=<?= (int)$c["id"] ?>"
    class="btn btn-info btn-sm"
    title="Ver cliente">

<i class="bi bi-eye"></i>

</a>


<a
    href="editar.php?id=<?= (int)$c["id"] ?>"
    class="btn btn-warning btn-sm"
    title="Editar cliente">

<i class="bi bi-pencil"></i>

</a>


<a
    href="eliminar.php?id=<?= (int)$c["id"] ?>"
    class="btn btn-danger btn-sm"
    title="Eliminar cliente"
    onclick="return confirm('¿Eliminar cliente?');">

<i class="bi bi-trash"></i>

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>
</div>

</div>

<!-- JQUERY -->

<script
    src="https://code.jquery.com/jquery-3.7.1.min.js">
</script>


<!-- DATATABLES -->

<script
    src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js">
</script>

<script
    src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js">
</script>


<script>

$(function () {

    $('.datatable').DataTable({

        language: {

            url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"

        },

        pageLength: 10,

        order: [
            [2, "asc"],
            [3, "asc"]
        ]

    });

});

</script>

</body>

</html>