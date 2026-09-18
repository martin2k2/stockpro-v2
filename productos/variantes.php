<?php

session_start();

require_once "../config/conexion.php";


/*
|--------------------------------------------------------------------------
| VALIDAR SESIÓN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['usuario'])) {

    header("Location: ../login.php");
    exit;
}


$db = Conexion::conectar();


/*
|--------------------------------------------------------------------------
| PRODUCTO
|--------------------------------------------------------------------------
*/

$producto_id = isset($_GET['producto_id'])
    ? (int)$_GET['producto_id']
    : 0;


if ($producto_id <= 0) {

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER PRODUCTO
|--------------------------------------------------------------------------
*/

$stmtProducto = $db->prepare("
    SELECT
        id,
        codigo,
        nombre,
        stock
    FROM productos
    WHERE id = ?
    LIMIT 1
");

$stmtProducto->execute([
    $producto_id
]);

$producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);


if (!$producto) {

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| AGREGAR VARIANTE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? '';


    try {

        /*
        |--------------------------------------------------------------------------
        | AGREGAR
        |--------------------------------------------------------------------------
        */

        if ($accion === 'agregar') {

            $talle = trim($_POST['talle'] ?? '');

            $color = trim($_POST['color'] ?? '');

            $stock = isset($_POST['stock'])
                ? (int)$_POST['stock']
                : 0;

            $codigo = trim($_POST['codigo'] ?? '');


            if ($talle === '') {

                throw new Exception(
                    "Debe ingresar un talle."
                );

            }


            if ($stock < 0) {

                throw new Exception(
                    "El stock no puede ser negativo."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | VERIFICAR DUPLICADO
            |--------------------------------------------------------------------------
            */

            $stmtExiste = $db->prepare("
                SELECT id
                FROM producto_variantes
                WHERE producto_id = ?
                  AND talle = ?
                  AND (
                        (color = ?)
                        OR (color IS NULL AND ? = '')
                  )
                LIMIT 1
            ");

            $stmtExiste->execute([

                $producto_id,

                $talle,

                $color !== ''
                    ? $color
                    : null,

                $color

            ]);


            if ($stmtExiste->fetch()) {

                throw new Exception(
                    "Ya existe esa variante para este producto."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | INSERTAR
            |--------------------------------------------------------------------------
            */

            $stmtInsert = $db->prepare("
                INSERT INTO producto_variantes
                (
                    producto_id,
                    talle,
                    color,
                    stock,
                    codigo,
                    activo
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    1
                )
            ");


            $stmtInsert->execute([

                $producto_id,

                $talle,

                $color !== ''
                    ? $color
                    : null,

                $stock,

                $codigo !== ''
                    ? $codigo
                    : null

            ]);


            header(
                "Location: variantes.php?producto_id=" .
                $producto_id .
                "&ok=1"
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | EDITAR
        |--------------------------------------------------------------------------
        */

        if ($accion === 'editar') {

            $variante_id = isset($_POST['variante_id'])
                ? (int)$_POST['variante_id']
                : 0;

            $talle = trim($_POST['talle'] ?? '');

            $color = trim($_POST['color'] ?? '');

            $stock = isset($_POST['stock'])
                ? (int)$_POST['stock']
                : 0;

            $codigo = trim($_POST['codigo'] ?? '');


            if ($variante_id <= 0) {

                throw new Exception(
                    "Variante inválida."
                );

            }


            if ($talle === '') {

                throw new Exception(
                    "Debe ingresar un talle."
                );

            }


            if ($stock < 0) {

                throw new Exception(
                    "El stock no puede ser negativo."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | VERIFICAR QUE PERTENEZCA AL PRODUCTO
            |--------------------------------------------------------------------------
            */

            $stmtVerificar = $db->prepare("
                SELECT id
                FROM producto_variantes
                WHERE id = ?
                  AND producto_id = ?
                LIMIT 1
            ");

            $stmtVerificar->execute([

                $variante_id,

                $producto_id

            ]);


            if (!$stmtVerificar->fetch()) {

                throw new Exception(
                    "La variante no pertenece a este producto."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | VERIFICAR DUPLICADO
            |--------------------------------------------------------------------------
            */

            $stmtExiste = $db->prepare("
                SELECT id
                FROM producto_variantes
                WHERE producto_id = ?
                  AND talle = ?
                  AND id <> ?
                  AND (
                        (color = ?)
                        OR (color IS NULL AND ? = '')
                  )
                LIMIT 1
            ");

            $stmtExiste->execute([

                $producto_id,

                $talle,

                $variante_id,

                $color !== ''
                    ? $color
                    : null,

                $color

            ]);


            if ($stmtExiste->fetch()) {

                throw new Exception(
                    "Ya existe otra variante con ese talle y color."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | ACTUALIZAR
            |--------------------------------------------------------------------------
            */

            $stmtUpdate = $db->prepare("
                UPDATE producto_variantes

                SET
                    talle = ?,
                    color = ?,
                    stock = ?,
                    codigo = ?

                WHERE id = ?
                  AND producto_id = ?
            ");


            $stmtUpdate->execute([

                $talle,

                $color !== ''
                    ? $color
                    : null,

                $stock,

                $codigo !== ''
                    ? $codigo
                    : null,

                $variante_id,

                $producto_id

            ]);


            header(
                "Location: variantes.php?producto_id=" .
                $producto_id .
                "&ok=2"
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | ELIMINAR
        |--------------------------------------------------------------------------
        */

        if ($accion === 'eliminar') {

            $variante_id = isset($_POST['variante_id'])
                ? (int)$_POST['variante_id']
                : 0;


            if ($variante_id <= 0) {

                throw new Exception(
                    "Variante inválida."
                );

            }


            $stmtDelete = $db->prepare("
                UPDATE producto_variantes

                SET activo = 0

                WHERE id = ?
                  AND producto_id = ?
            ");


            $stmtDelete->execute([

                $variante_id,

                $producto_id

            ]);


            header(
                "Location: variantes.php?producto_id=" .
                $producto_id .
                "&ok=3"
            );

            exit;
        }


        throw new Exception(
            "Acción no válida."
        );


    } catch (Throwable $e) {

        $error = $e->getMessage();

    }

}


/*
|--------------------------------------------------------------------------
| OBTENER VARIANTES
|--------------------------------------------------------------------------
*/

$stmtVariantes = $db->prepare("
    SELECT
        id,
        producto_id,
        talle,
        color,
        stock,
        codigo,
        activo
    FROM producto_variantes
    WHERE producto_id = ?
      AND activo = 1
    ORDER BY
        CASE
            WHEN talle REGEXP '^[0-9]+$'
            THEN CAST(talle AS UNSIGNED)
            ELSE 9999
        END,
        talle ASC,
        color ASC
");

$stmtVariantes->execute([
    $producto_id
]);

$variantes = $stmtVariantes->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| MENSAJES
|--------------------------------------------------------------------------
*/

$ok = isset($_GET['ok'])
    ? (int)$_GET['ok']
    : 0;

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>
Talles - <?= htmlspecialchars(
    $producto['nombre'],
    ENT_QUOTES,
    'UTF-8'
) ?>
</title>


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

.stock {
    font-weight: 700;
}

</style>

</head>


<body>


<div class="container py-4">


<!-- HEADER -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">


<div>

<h3 class="mb-1">

<i class="bi bi-tags"></i>

Talles y variantes

</h3>


<div class="text-muted">

<?= htmlspecialchars(
    $producto['nombre'],
    ENT_QUOTES,
    'UTF-8'
) ?>


<?php if (!empty($producto['codigo'])): ?>

<span class="ms-2">

Código:

<?= htmlspecialchars(
    $producto['codigo'],
    ENT_QUOTES,
    'UTF-8'
) ?>

</span>

<?php endif; ?>


</div>

</div>


<a
    href="index.php"
    class="btn btn-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver a productos

</a>


</div>


<!-- MENSAJES -->

<?php if ($ok === 1): ?>

<div class="alert alert-success">

<i class="bi bi-check-circle"></i>

Talle agregado correctamente.

</div>

<?php endif; ?>


<?php if ($ok === 2): ?>

<div class="alert alert-success">

<i class="bi bi-check-circle"></i>

Talle actualizado correctamente.

</div>

<?php endif; ?>


<?php if ($ok === 3): ?>

<div class="alert alert-success">

<i class="bi bi-check-circle"></i>

Talle eliminado correctamente.

</div>

<?php endif; ?>


<?php if (!empty($error)): ?>

<div class="alert alert-danger">

<i class="bi bi-exclamation-triangle"></i>

<?= htmlspecialchars(
    $error,
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>

<?php endif; ?>


<div class="row g-4">


<!-- AGREGAR -->

<div class="col-12 col-lg-4">


<div class="card shadow-sm">


<div class="card-header bg-primary text-white">

<strong>

<i class="bi bi-plus-circle"></i>

Agregar talle / variante

</strong>

</div>


<div class="card-body">


<form
    method="POST"
>


<input
    type="hidden"
    name="accion"
    value="agregar"
>


<div class="mb-3">

<label class="form-label fw-bold">

Talle

</label>


<input
    type="text"
    name="talle"
    class="form-control"
    placeholder="Ej: S, M, L, XL"
    maxlength="20"
    required
>

</div>


<div class="mb-3">

<label class="form-label">

Color

<span class="text-muted">
(opcional)
</span>

</label>


<input
    type="text"
    name="color"
    class="form-control"
    placeholder="Ej: Negro"
    maxlength="50"
>

</div>


<div class="mb-3">

<label class="form-label fw-bold">

Stock

</label>


<input
    type="number"
    name="stock"
    class="form-control"
    value="0"
    min="0"
    required
>

</div>


<div class="mb-3">

<label class="form-label">

Código

<span class="text-muted">
(opcional)
</span>

</label>


<input
    type="text"
    name="codigo"
    class="form-control"
    placeholder="Código específico"
    maxlength="50"
>

</div>


<button
    type="submit"
    class="btn btn-success w-100"
>

<i class="bi bi-plus-circle"></i>

Agregar variante

</button>


</form>


</div>

</div>


</div>


<!-- LISTADO -->

<div class="col-12 col-lg-8">


<div class="card shadow-sm">


<div class="card-header bg-dark text-white">


<div class="d-flex justify-content-between align-items-center">


<strong>

<i class="bi bi-list"></i>

Variantes disponibles

</strong>


<span class="badge bg-light text-dark">

<?= count($variantes) ?>

</span>


</div>


</div>


<div class="card-body p-0">


<div class="table-responsive">


<table class="table table-bordered table-striped mb-0">


<thead class="table-light">

<tr>

<th>Talle</th>

<th>Color</th>

<th>Stock</th>

<th>Código</th>

<th>Acciones</th>

</tr>

</thead>


<tbody>


<?php if (empty($variantes)): ?>


<tr>

<td
    colspan="5"
    class="text-center text-muted py-4"
>

<i class="bi bi-tags fs-3"></i>

<div class="mt-2">

Este producto todavía no tiene talles.

</div>

</td>

</tr>


<?php endif; ?>


<?php foreach ($variantes as $v): ?>


<tr>


<td>

<span class="badge bg-primary fs-6">

<?= htmlspecialchars(
    $v['talle'],
    ENT_QUOTES,
    'UTF-8'
) ?>

</span>

</td>


<td>

<?= !empty($v['color'])
    ? htmlspecialchars(
        $v['color'],
        ENT_QUOTES,
        'UTF-8'
    )
    : '<span class="text-muted">Sin color</span>'
?>

</td>


<td>

<span class="stock">

<?= (int)$v['stock'] ?>

</span>

</td>


<td>

<?= !empty($v['codigo'])
    ? htmlspecialchars(
        $v['codigo'],
        ENT_QUOTES,
        'UTF-8'
    )
    : '<span class="text-muted">—</span>'
?>

</td>


<td>


<button
    type="button"
    class="btn btn-warning btn-sm"
    data-bs-toggle="modal"
    data-bs-target="#editarModal<?= (int)$v['id'] ?>"
>

<i class="bi bi-pencil"></i>

Editar

</button>


<form
    method="POST"
    class="d-inline"
    onsubmit="return confirm('¿Eliminar este talle?');"
>

<input
    type="hidden"
    name="accion"
    value="eliminar"
>

<input
    type="hidden"
    name="variante_id"
    value="<?= (int)$v['id'] ?>"
>


<button
    type="submit"
    class="btn btn-danger btn-sm"
>

<i class="bi bi-trash"></i>

Eliminar

</button>


</form>


</td>


</tr>


<!-- MODAL EDITAR -->

<div
    class="modal fade"
    id="editarModal<?= (int)$v['id'] ?>"
    tabindex="-1"
    aria-hidden="true"
>


<div class="modal-dialog">


<div class="modal-content">


<div class="modal-header">


<h5 class="modal-title">

Editar variante

</h5>


<button
    type="button"
    class="btn-close"
    data-bs-dismiss="modal"
></button>


</div>


<form method="POST">


<div class="modal-body">


<input
    type="hidden"
    name="accion"
    value="editar"
>


<input
    type="hidden"
    name="variante_id"
    value="<?= (int)$v['id'] ?>"
>


<div class="mb-3">

<label class="form-label">

Talle

</label>


<input
    type="text"
    name="talle"
    class="form-control"
    value="<?= htmlspecialchars(
        $v['talle'],
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    maxlength="20"
    required
>

</div>


<div class="mb-3">

<label class="form-label">

Color

</label>


<input
    type="text"
    name="color"
    class="form-control"
    value="<?= htmlspecialchars(
        $v['color'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    maxlength="50"
>

</div>


<div class="mb-3">

<label class="form-label">

Stock

</label>


<input
    type="number"
    name="stock"
    class="form-control"
    value="<?= (int)$v['stock'] ?>"
    min="0"
    required
>

</div>


<div class="mb-3">

<label class="form-label">

Código

</label>


<input
    type="text"
    name="codigo"
    class="form-control"
    value="<?= htmlspecialchars(
        $v['codigo'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    maxlength="50"
>

</div>


</div>


<div class="modal-footer">


<button
    type="button"
    class="btn btn-secondary"
    data-bs-dismiss="modal"
>

Cancelar

</button>


<button
    type="submit"
    class="btn btn-primary"
>

<i class="bi bi-save"></i>

Guardar cambios

</button>


</div>


</form>


</div>

</div>

</div>


<?php endforeach; ?>


</tbody>


</table>


</div>


</div>


</div>


</div>


</div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>