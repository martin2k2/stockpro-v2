
<?php

session_start();

require_once "../config/conexion.php";


if (!isset($_SESSION['usuario'])) {

    header("Location: ../login.php");
    exit;

}


$db = Conexion::conectar();


$codigo = trim($_POST['codigo'] ?? '');

$nombre = trim($_POST['nombre'] ?? '');

$categoria_id = !empty($_POST['categoria_id'])
    ? (int)$_POST['categoria_id']
    : null;

$proveedor_id = !empty($_POST['proveedor_id'])
    ? (int)$_POST['proveedor_id']
    : null;

$stock = isset($_POST['stock'])
    ? (int)$_POST['stock']
    : 0;

$stock_minimo = isset($_POST['stock_minimo'])
    ? (int)$_POST['stock_minimo']
    : 0;

$precio_compra = isset($_POST['precio_compra'])
    ? (float)$_POST['precio_compra']
    : 0;

$precio_venta = isset($_POST['precio_venta'])
    ? (float)$_POST['precio_venta']
    : 0;

$descripcion = trim($_POST['descripcion'] ?? '');


/*
|--------------------------------------------------------------------------
| VARIANTES
|--------------------------------------------------------------------------
*/

$tiene_variantes = isset($_POST['tiene_variantes'])
    && $_POST['tiene_variantes'] == '1';

$variante_talle = $_POST['variante_talle'] ?? [];

$variante_color = $_POST['variante_color'] ?? [];

$variante_codigo = $_POST['variante_codigo'] ?? [];

$variante_stock = $_POST['variante_stock'] ?? [];


/*
|--------------------------------------------------------------------------
| VALIDAR CÓDIGO
|--------------------------------------------------------------------------
*/

if ($codigo === '') {

    header("Location: crear.php?error=codigo_vacio");
    exit;

}


if ($nombre === '') {

    header("Location: crear.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| VERIFICAR CÓDIGO PRINCIPAL
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT id, nombre
    FROM productos
    WHERE codigo = ?
    LIMIT 1
");

$stmt->execute([
    $codigo
]);

$productoExistente = $stmt->fetch(PDO::FETCH_ASSOC);


if ($productoExistente) {

    header(
        "Location: crear.php?error=codigo_existente&codigo=" .
        urlencode($codigo) .
        "&producto=" .
        urlencode($productoExistente['nombre'])
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| IMAGEN
|--------------------------------------------------------------------------
*/

$imagen = null;


if (
    isset($_FILES['imagen']) &&
    !empty($_FILES['imagen']['name'])
) {

    $carpeta = "../uploads/productos/";


    if (!is_dir($carpeta)) {

        mkdir(
            $carpeta,
            0777,
            true
        );

    }


    $nombreOriginal = basename(
        $_FILES['imagen']['name']
    );


    $extension = strtolower(
        pathinfo(
            $nombreOriginal,
            PATHINFO_EXTENSION
        )
    );


    $extensionesPermitidas = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif'
    ];


    if (
        !in_array(
            $extension,
            $extensionesPermitidas
        )
    ) {

        die("Formato de imagen no permitido.");

    }


    $nombreImagen =
        time() .
        "_" .
        uniqid() .
        "." .
        $extension;


    $ruta =
        $carpeta .
        $nombreImagen;


    if (
        move_uploaded_file(
            $_FILES['imagen']['tmp_name'],
            $ruta
        )
    ) {

        $imagen =
            $nombreImagen;

    }

}


/*
|--------------------------------------------------------------------------
| GUARDAR PRODUCTO Y VARIANTES
|--------------------------------------------------------------------------
*/

try {


    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | CALCULAR STOCK TOTAL
    |--------------------------------------------------------------------------
    */

    $stockTotal = $stock;


    if ($tiene_variantes) {

        $stockTotal = 0;


        foreach ($variante_stock as $cantidadVariante) {

            $cantidadVariante =
                (int)$cantidadVariante;


            if ($cantidadVariante > 0) {

                $stockTotal +=
                    $cantidadVariante;

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | GUARDAR PRODUCTO
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("

        INSERT INTO productos
        (
            codigo,
            nombre,
            categoria_id,
            proveedor_id,
            stock,
            stock_minimo,
            precio_compra,
            precio_venta,
            descripcion,
            imagen,
            activo
        )

        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1
        )

    ");


    $stmt->execute([

        $codigo,

        $nombre,

        $categoria_id,

        $proveedor_id,

        $stockTotal,

        $stock_minimo,

        $precio_compra,

        $precio_venta,

        $descripcion,

        $imagen

    ]);


    $producto_id =
        (int)$db->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | GUARDAR VARIANTES
    |--------------------------------------------------------------------------
    */

    if ($tiene_variantes) {


        $stmtVariante = $db->prepare("

            INSERT INTO producto_variantes
            (
                producto_id,
                talle,
                color,
                codigo,
                stock,
                activo
            )

            VALUES
            (
                ?, ?, ?, ?, ?, 1
            )

        ");


        foreach (
            $variante_talle
            as $index => $talle
        ) {


            $talle =
                trim($talle);


            $color =
                trim(
                    $variante_color[$index]
                    ?? ''
                );


            $codigoVariante =
                trim(
                    $variante_codigo[$index]
                    ?? ''
                );


            $stockVariante =
                isset(
                    $variante_stock[$index]
                )
                ? (int)$variante_stock[$index]
                : 0;


            /*
            |--------------------------------------------------------------------------
            | VALIDAR TALLE
            |--------------------------------------------------------------------------
            */

            if ($talle === '') {

                throw new Exception(
                    "Debe seleccionar un talle."
                );

            }


            if ($stockVariante < 0) {

                throw new Exception(
                    "El stock del talle " .
                    $talle .
                    " no puede ser negativo."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | VALIDAR CÓDIGO DE VARIANTE
            |--------------------------------------------------------------------------
            */

            if ($codigoVariante !== '') {


                $stmtCodigo = $db->prepare("

                    SELECT id
                    FROM producto_variantes
                    WHERE codigo = ?
                    LIMIT 1

                ");


                $stmtCodigo->execute([
                    $codigoVariante
                ]);


                if ($stmtCodigo->fetch()) {

                    throw new Exception(
                        "El código de variante " .
                        $codigoVariante .
                        " ya existe."
                    );

                }

            }


            /*
            |--------------------------------------------------------------------------
            | GUARDAR VARIANTE
            |--------------------------------------------------------------------------
            */

            $stmtVariante->execute([

                $producto_id,

                $talle,

                $color !== ''
                    ? $color
                    : null,

                $codigoVariante !== ''
                    ? $codigoVariante
                    : null,

                $stockVariante

            ]);

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $db->commit();


    header(
        "Location: index.php?ok=producto_guardado"
    );

    exit;


} catch (Throwable $e) {


    if ($db->inTransaction()) {

        $db->rollBack();

    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR IMAGEN SI FALLÓ EL GUARDADO
    |--------------------------------------------------------------------------
    */

    if (
        $imagen !== null &&
        file_exists(
            "../uploads/productos/" .
            $imagen
        )
    ) {

        unlink(
            "../uploads/productos/" .
            $imagen
        );

    }


    if (
        $e instanceof PDOException &&
        isset($e->errorInfo[1]) &&
        $e->errorInfo[1] == 1062
    ) {

        header(
            "Location: crear.php?error=codigo_existente&codigo=" .
            urlencode($codigo)
        );

        exit;

    }


    die(
        "Error al guardar el producto: " .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}

