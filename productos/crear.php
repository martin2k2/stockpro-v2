```php
<?php

session_start();

require_once "../config/conexion.php";


if (!isset($_SESSION['usuario'])) {

    header("Location: ../login.php");
    exit;

}


$db = Conexion::conectar();


$categorias = $db->query("
    SELECT * 
    FROM categorias 
    ORDER BY nombre
")->fetchAll();


$proveedores = $db->query("
    SELECT * 
    FROM proveedores 
    ORDER BY nombre
")->fetchAll();

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Nuevo Producto</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<style>

#contenedorVariantes {
    display: none;
}

.variante-row {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
}

</style>

</head>


<body class="bg-light">


<div class="container mt-5 mb-5">


<?php if (
    isset($_GET['error']) &&
    $_GET['error'] === 'codigo_existente'
): ?>

<div class="alert alert-danger alert-dismissible fade show" role="alert">

    <i class="bi bi-exclamation-triangle-fill me-2"></i>

    <strong>Código duplicado.</strong>

    El código
    <strong>
        <?= htmlspecialchars($_GET['codigo'] ?? '') ?>
    </strong>

    ya está utilizado por otro producto.

    <?php if (!empty($_GET['producto'])): ?>

        Producto:
        <strong>
            <?= htmlspecialchars($_GET['producto']) ?>
        </strong>.

    <?php endif; ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<div class="card shadow border-0">


<div class="card-header bg-success text-white">

<h4 class="mb-0">

<i class="bi bi-box-seam"></i>

Nuevo Producto

</h4>

</div>


<div class="card-body">


<form
    action="guardar.php"
    method="POST"
    enctype="multipart/form-data"
>


<!-- CODIGO -->

<div class="mb-3">

<label class="form-label">

Código

</label>

<input
    type="text"
    name="codigo"
    class="form-control"
    placeholder="Código"
    required
>

</div>


<!-- NOMBRE -->

<div class="mb-3">

<label class="form-label">

Nombre

</label>

<input
    type="text"
    name="nombre"
    class="form-control"
    placeholder="Nombre del producto"
    required
>

</div>


<!-- CATEGORIA -->

<div class="mb-3">

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

<option value="<?= (int)$c['id'] ?>">

<?= htmlspecialchars($c['nombre']) ?>

</option>

<?php endforeach; ?>


</select>

</div>


<!-- PROVEEDOR -->

<div class="mb-3">

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

<option value="<?= (int)$p['id'] ?>">

<?= htmlspecialchars($p['nombre']) ?>

</option>

<?php endforeach; ?>


</select>

</div>


<!-- PRODUCTO CON TALLES -->

<div class="card border-primary mb-4">


<div class="card-body">


<div class="form-check form-switch">

<input
    class="form-check-input"
    type="checkbox"
    id="tieneVariantes"
    name="tiene_variantes"
    value="1"
>


<label
    class="form-check-label fw-bold"
    for="tieneVariantes"
>

<i class="bi bi-tags"></i>

Este producto tiene talles

</label>

</div>


<div class="form-text">

Activá esta opción para productos como buzos, remeras,
camperas, pantalones, etc.

</div>


</div>


</div>


<!-- STOCK PRODUCTO NORMAL -->

<div
    id="stockNormal"
    class="mb-3"
>


<label class="form-label">

Stock

</label>


<input
    type="number"
    name="stock"
    id="stock"
    class="form-control"
    placeholder="Stock"
    min="0"
    value="0"
>


<div class="form-text">

Para productos sin talles.

</div>


</div>


<!-- VARIANTES -->

<div
    id="contenedorVariantes"
    class="card border-success mb-4"
>


<div class="card-header bg-light">

<div class="d-flex justify-content-between align-items-center">

<strong>

<i class="bi bi-rulers"></i>

Talles y stock

</strong>


<button
    type="button"
    class="btn btn-success btn-sm"
    id="btnAgregarVariante"
>

<i class="bi bi-plus-circle"></i>

Agregar talle

</button>


</div>

</div>


<div class="card-body">


<div id="listaVariantes">


<!-- LAS VARIANTES SE AGREGAN CON JAVASCRIPT -->


</div>


</div>


</div>


<!-- STOCK MINIMO -->

<div class="mb-3">

<label class="form-label">

Stock mínimo

</label>


<input
    type="number"
    name="stock_minimo"
    class="form-control"
    placeholder="Stock mínimo"
    min="0"
    value="0"
>


</div>


<!-- PRECIO COMPRA -->

<div class="mb-3">

<label class="form-label">

Precio compra

</label>


<input
    type="number"
    step="0.01"
    name="precio_compra"
    class="form-control"
    placeholder="Precio compra"
    min="0"
>


</div>


<!-- PRECIO VENTA -->

<div class="mb-3">

<label class="form-label">

Precio venta

</label>


<input
    type="number"
    step="0.01"
    name="precio_venta"
    class="form-control"
    placeholder="Precio venta"
    min="0"
    required
>


</div>


<!-- DESCRIPCION -->

<div class="mb-3">

<label class="form-label">

Descripción

</label>


<textarea
    name="descripcion"
    class="form-control"
    rows="4"
    placeholder="Descripción"
></textarea>


</div>


<!-- IMAGEN -->

<div class="mb-4">

<label class="form-label">

Imagen

</label>


<input
    type="file"
    name="imagen"
    class="form-control"
    accept="image/*"
>


</div>


<!-- BOTONES -->

<button
    type="submit"
    class="btn btn-success"
>

<i class="bi bi-check-circle"></i>

Guardar producto

</button>


<a
    href="../productos/index.php"
    class="btn btn-secondary"
>

<i class="bi bi-arrow-left"></i>

Volver

</a>


</form>


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


        const listaVariantes =
            document.getElementById(
                "listaVariantes"
            );


        const stockNormal =
            document.getElementById(
                "stockNormal"
            );


        const inputStock =
            document.getElementById(
                "stock"
            );


        const btnAgregarVariante =
            document.getElementById(
                "btnAgregarVariante"
            );


        /*
        =====================================================
        MOSTRAR / OCULTAR TALLES
        =====================================================
        */

        tieneVariantes.addEventListener(
            "change",
            function () {


                if (this.checked) {


                    contenedorVariantes.style.display =
                        "block";


                    stockNormal.style.display =
                        "none";


                    inputStock.value =
                        0;


                    inputStock.disabled =
                        true;


                    if (
                        listaVariantes.children.length === 0
                    ) {

                        agregarVariante();

                    }


                } else {


                    contenedorVariantes.style.display =
                        "none";


                    stockNormal.style.display =
                        "block";


                    inputStock.disabled =
                        false;

                }

            }
        );


        /*
        =====================================================
        AGREGAR TALLE
        =====================================================
        */

        btnAgregarVariante.addEventListener(
            "click",
            function () {

                agregarVariante();

            }
        );


        /*
        =====================================================
        FUNCION AGREGAR VARIANTE
        =====================================================
        */

        function agregarVariante() {


            const div =
                document.createElement(
                    "div"
                );


            div.className =
                "variante-row";


            div.innerHTML = `

                <div class="row g-2 align-items-end">


                    <div class="col-12 col-md-3">

                        <label class="form-label">

                            Talle

                        </label>


                        <select
                            name="variante_talle[]"
                            class="form-select"
                            required
                        >

                            <option value="">

                                Seleccionar

                            </option>

                            <option value="XS">

                                XS

                            </option>

                            <option value="S">

                                S

                            </option>

                            <option value="M">

                                M

                            </option>

                            <option value="L">

                                L

                            </option>

                            <option value="XL">

                                XL

                            </option>

                            <option value="XXL">

                                XXL

                            </option>

                            <option value="XXXL">

                                XXXL

                            </option>

                            <option value="UNICO">

                                Único

                            </option>

                        </select>

                    </div>


                    <div class="col-12 col-md-3">

                        <label class="form-label">

                            Color

                        </label>


                        <input
                            type="text"
                            name="variante_color[]"
                            class="form-control"
                            placeholder="Opcional"
                        >

                    </div>


                    <div class="col-12 col-md-3">

                        <label class="form-label">

                            Código

                        </label>


                        <input
                            type="text"
                            name="variante_codigo[]"
                            class="form-control"
                            placeholder="Opcional"
                        >

                    </div>


                    <div class="col-8 col-md-2">

                        <label class="form-label">

                            Stock

                        </label>


                        <input
                            type="number"
                            name="variante_stock[]"
                            class="form-control"
                            min="0"
                            value="0"
                            required
                        >

                    </div>


                    <div class="col-4 col-md-1 d-grid">

                        <button
                            type="button"
                            class="btn btn-danger btnEliminarVariante"
                            title="Eliminar talle"
                        >

                            <i class="bi bi-trash"></i>

                        </button>

                    </div>


                </div>

            `;


            listaVariantes.appendChild(
                div
            );


            const btnEliminar =
                div.querySelector(
                    ".btnEliminarVariante"
                );


            btnEliminar.addEventListener(
                "click",
                function () {


                    div.remove();


                    if (
                        listaVariantes.children.length === 0
                    ) {

                        agregarVariante();

                    }

                }
            );

        }


    }
);

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>
```
