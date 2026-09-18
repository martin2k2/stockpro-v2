<?php

session_start();

require_once "../config/conexion.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit;
}

$db = Conexion::conectar();

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER PRODUCTO
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT *
    FROM productos
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CATEGORIAS
|--------------------------------------------------------------------------
*/

$categorias = $db->query("
    SELECT *
    FROM categorias
    ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PROVEEDORES
|--------------------------------------------------------------------------
*/

$proveedores = $db->query("
    SELECT *
    FROM proveedores
    ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| OBTENER VARIANTES
|--------------------------------------------------------------------------
*/

$stmtVariantes = $db->prepare("
    SELECT
        id,
        talle,
        color,
        codigo,
        stock,
        activo
    FROM producto_variantes
    WHERE producto_id = ?
    ORDER BY talle, color
");

$stmtVariantes->execute([$id]);

$variantes = $stmtVariantes->fetchAll(PDO::FETCH_ASSOC);

$tieneVariantes = !empty($variantes);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Editar Producto</title>

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
    background: #f4f6f9;
}

.card {
    border: none;
}

.campo {
    margin-bottom: 20px;
}

.imagen-actual {
    width: 180px;
    height: 180px;
    object-fit: contain;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px;
    background: white;
}

.variante-row {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 12px;
}

</style>

</head>

<body>

<div class="container mt-5 mb-5">

<div class="row justify-content-center">

<div class="col-lg-9">

<div class="card shadow">

<div class="card-header bg-warning">

<h4 class="mb-0">

<i class="bi bi-pencil-square"></i>

Editar Producto

</h4>

</div>


<div class="card-body p-4">

<form
    action="actualizar.php"
    method="POST"
    enctype="multipart/form-data"
>


<input
    type="hidden"
    name="id"
    value="<?= (int)$producto['id'] ?>"
>


<div class="campo">

<label class="form-label">

Código

</label>

<input
    type="text"
    name="codigo"
    class="form-control"
    value="<?= htmlspecialchars(
        $producto['codigo'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    required
>

</div>


<div class="campo">

<label class="form-label">

Nombre

</label>

<input
    type="text"
    name="nombre"
    class="form-control"
    value="<?= htmlspecialchars(
        $producto['nombre'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    required
>

</div>


<div class="campo">

<label class="form-label">

Categoría

</label>

<select
    name="categoria_id"
    class="form-select"
>

<option value="">

Seleccionar categoría

</option>

<?php foreach ($categorias as $c): ?>

<option
    value="<?= (int)$c['id'] ?>"
    <?= $c['id'] == $producto['categoria_id']
        ? 'selected'
        : '' ?>
>

<?= htmlspecialchars($c['nombre']) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="campo">

<label class="form-label">

Proveedor

</label>

<select
    name="proveedor_id"
    class="form-select"
>

<option value="">

Seleccionar proveedor

</option>

<?php foreach ($proveedores as $p): ?>

<option
    value="<?= (int)$p['id'] ?>"
    <?= $p['id'] == $producto['proveedor_id']
        ? 'selected'
        : '' ?>
>

<?= htmlspecialchars($p['nombre']) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="card border-primary mb-4">

<div class="card-body">

<div class="form-check form-switch">

<input
    class="form-check-input"
    type="checkbox"
    id="tieneVariantes"
    name="tiene_variantes"
    value="1"
    <?= $tieneVariantes ? 'checked' : '' ?>
>

<label
    class="form-check-label fw-bold"
    for="tieneVariantes"
>

<i class="bi bi-tags"></i>

Este producto tiene talles

</label>

</div>

</div>

</div>


<div
    class="campo"
    id="stockNormal"
    style="<?= $tieneVariantes ? 'display:none;' : '' ?>"
>

<label class="form-label">

Stock

</label>

<input
    type="number"
    id="stock"
    name="stock"
    class="form-control"
    min="0"
    value="<?= (int)$producto['stock'] ?>"
>

</div>


<div
    id="contenedorVariantes"
    class="card border-success mb-4"
    style="<?= $tieneVariantes ? '' : 'display:none;' ?>"
>

<div class="card-header">

<div class="d-flex justify-content-between align-items-center">

<strong>

Talles y stock

</strong>

<button
    type="button"
    class="btn btn-success btn-sm"
    id="btnAgregarVariante"
>

<i class="bi bi-plus-circle"></i>

Agregar

</button>

</div>

</div>


<div class="card-body">

<div id="listaVariantes">

<?php foreach ($variantes as $v): ?>

<div class="variante-row">

<input
    type="hidden"
    name="variante_id[]"
    value="<?= (int)$v['id'] ?>"
>

<div class="row g-2 align-items-end">

<div class="col-md-3">

<label class="form-label">

Talle

</label>

<select
    name="variante_talle[]"
    class="form-select"
    required
>

<option value="">Seleccionar</option>

<?php

$talles = [
    'XS',
    'S',
    'M',
    'L',
    'XL',
    'XXL',
    'XXXL',
    'UNICO'
];

foreach ($talles as $talle):

?>

<option
    value="<?= $talle ?>"
    <?= $v['talle'] === $talle
        ? 'selected'
        : '' ?>
>

<?= $talle === 'UNICO'
    ? 'Único'
    : $talle ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="col-md-3">

<label class="form-label">

Color

</label>

<input
    type="text"
    name="variante_color[]"
    class="form-control"
    value="<?= htmlspecialchars(
        $v['color'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

</div>


<div class="col-md-3">

<label class="form-label">

Código

</label>

<input
    type="text"
    name="variante_codigo[]"
    class="form-control"
    value="<?= htmlspecialchars(
        $v['codigo'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

</div>


<div class="col-md-2">

<label class="form-label">

Stock

</label>

<input
    type="number"
    name="variante_stock[]"
    class="form-control"
    min="0"
    value="<?= (int)$v['stock'] ?>"
    required
>

</div>


<div class="col-md-1 d-grid">

<button
    type="button"
    class="btn btn-danger btnEliminarVariante"
>

<i class="bi bi-trash"></i>

</button>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

</div>


<div class="campo">

<label class="form-label">

Stock mínimo

</label>

<input
    type="number"
    name="stock_minimo"
    class="form-control"
    min="0"
    value="<?= (int)$producto['stock_minimo'] ?>"
>

</div>


<div class="campo">

<label class="form-label">

Precio de compra

</label>

<input
    type="number"
    step="0.01"
    name="precio_compra"
    class="form-control"
    value="<?= htmlspecialchars(
        $producto['precio_compra'] ?? 0
    ) ?>"
>

</div>


<div class="campo">

<label class="form-label">

Precio de venta

</label>

<input
    type="number"
    step="0.01"
    name="precio_venta"
    class="form-control"
    value="<?= htmlspecialchars(
        $producto['precio_venta'] ?? 0
    ) ?>"
    required
>

</div>


<div class="campo">

<label class="form-label">

Descripción

</label>

<textarea
    name="descripcion"
    class="form-control"
    rows="4"
><?= htmlspecialchars(
    $producto['descripcion'] ?? ''
) ?></textarea>

</div>


<div class="campo">

<label class="form-label">

Imagen actual

</label>

<br>

<?php if (!empty($producto['imagen'])): ?>

<img
    src="../uploads/productos/<?= htmlspecialchars(
        $producto['imagen']
    ) ?>"
    class="imagen-actual"
>

<?php else: ?>

<div class="text-muted">

Sin imagen

</div>

<?php endif; ?>

</div>


<div class="campo">

<label class="form-label">

Cambiar imagen

</label>

<input
    type="file"
    name="imagen"
    class="form-control"
    accept=".jpg,.jpeg,.png,.webp"
>

</div>


<div class="d-flex gap-2">

<button
    type="submit"
    class="btn btn-warning"
>

<i class="bi bi-save"></i>

Actualizar producto

</button>


<a
    href="index.php"
    class="btn btn-secondary"
>

Volver

</a>

</div>

</form>

</div>

</div>

</div>

</div>

</div>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const tieneVariantes =
            document.getElementById(
                "tieneVariantes"
            );

        const contenedorVariantes =
            document.getElementById(
                "contenedorVariantes"
            );

        const stockNormal =
            document.getElementById(
                "stockNormal"
            );

        const inputStock =
            document.getElementById(
                "stock"
            );

        const listaVariantes =
            document.getElementById(
                "listaVariantes"
            );

        const btnAgregar =
            document.getElementById(
                "btnAgregarVariante"
            );


        function actualizarVista() {

            if (tieneVariantes.checked) {

                contenedorVariantes.style.display =
                    "block";

                stockNormal.style.display =
                    "none";

                inputStock.disabled =
                    true;

            } else {

                contenedorVariantes.style.display =
                    "none";

                stockNormal.style.display =
                    "block";

                inputStock.disabled =
                    false;

            }

        }


        tieneVariantes.addEventListener(
            "change",
            actualizarVista
        );


        btnAgregar.addEventListener(
            "click",
            function () {

                const div =
                    document.createElement("div");

                div.className =
                    "variante-row";

                div.innerHTML = `

<input
    type="hidden"
    name="variante_id[]"
    value=""
>

<div class="row g-2 align-items-end">

<div class="col-md-3">

<select
    name="variante_talle[]"
    class="form-select"
    required
>

<option value="">Seleccionar talle</option>

<option value="XS">XS</option>
<option value="S">S</option>
<option value="M">M</option>
<option value="L">L</option>
<option value="XL">XL</option>
<option value="XXL">XXL</option>
<option value="XXXL">XXXL</option>
<option value="UNICO">Único</option>

</select>

</div>

<div class="col-md-3">

<input
    type="text"
    name="variante_color[]"
    class="form-control"
    placeholder="Color"
>

</div>

<div class="col-md-3">

<input
    type="text"
    name="variante_codigo[]"
    class="form-control"
    placeholder="Código"
>

</div>

<div class="col-md-2">

<input
    type="number"
    name="variante_stock[]"
    class="form-control"
    min="0"
    value="0"
    required
>

</div>

<div class="col-md-1 d-grid">

<button
    type="button"
    class="btn btn-danger btnEliminarVariante"
>

<i class="bi bi-trash"></i>

</button>

</div>

</div>

`;

                listaVariantes.appendChild(div);

                asignarEliminar(div);

            }
        );


        function asignarEliminar(div) {

            const boton =
                div.querySelector(
                    ".btnEliminarVariante"
                );

            boton.addEventListener(
                "click",
                function () {

                    div.remove();

                }
            );

        }


        document
            .querySelectorAll(
                ".variante-row"
            )
            .forEach(
                function (div) {

                    asignarEliminar(div);

                }
            );


        actualizarVista();

    }
);

</script>

</body>

</html>