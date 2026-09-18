<?php

session_start();

require_once "../config/conexion.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit;
}

$db = Conexion::conectar();

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATOS DEL PRODUCTO
|--------------------------------------------------------------------------
*/

$codigo = trim($_POST['codigo'] ?? '');

$nombre = trim($_POST['nombre'] ?? '');

$categoria_id = !empty($_POST['categoria_id'])
    ? (int)$_POST['categoria_id']
    : null;

$proveedor_id = !empty($_POST['proveedor_id'])
    ? (int)$_POST['proveedor_id']
    : null;

$stock = (int)($_POST['stock'] ?? 0);

$stock_minimo = (int)($_POST['stock_minimo'] ?? 0);

$precio_compra = (float)($_POST['precio_compra'] ?? 0);

$precio_venta = (float)($_POST['precio_venta'] ?? 0);

$descripcion = trim($_POST['descripcion'] ?? '');

$tieneVariantes = isset($_POST['tiene_variantes'])
    && $_POST['tiene_variantes'] == '1';


/*
|--------------------------------------------------------------------------
| VALIDAR DATOS
|--------------------------------------------------------------------------
*/

if ($codigo === '' || $nombre === '') {

    die("Código y nombre son obligatorios.");

}


/*
|--------------------------------------------------------------------------
| VERIFICAR PRODUCTO
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT imagen
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


$imagen = $producto['imagen'];


/*
|--------------------------------------------------------------------------
| VALIDAR CODIGO DUPLICADO
|--------------------------------------------------------------------------
*/

$stmtCodigo = $db->prepare("
    SELECT id
    FROM productos
    WHERE codigo = ?
    AND id != ?
    LIMIT 1
");

$stmtCodigo->execute([
    $codigo,
    $id
]);


if ($stmtCodigo->fetch()) {

    die("El código ya pertenece a otro producto.");

}


/*
|--------------------------------------------------------------------------
| SUBIR NUEVA IMAGEN
|--------------------------------------------------------------------------
*/

if (
    isset($_FILES['imagen']) &&
    $_FILES['imagen']['error'] === UPLOAD_ERR_OK
) {

    $carpeta = "../uploads/productos/";


    if (!is_dir($carpeta)) {

        mkdir(
            $carpeta,
            0777,
            true
        );

    }


    $extension = strtolower(
        pathinfo(
            $_FILES['imagen']['name'],
            PATHINFO_EXTENSION
        )
    );


    $extensionesPermitidas = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];


    if (
        !in_array(
            $extension,
            $extensionesPermitidas
        )
    ) {

        die("Formato de imagen no permitido.");

    }


    $nuevaImagen =
        uniqid("producto_") .
        "." .
        $extension;


    $rutaNueva =
        $carpeta .
        $nuevaImagen;


    if (
        move_uploaded_file(
            $_FILES['imagen']['tmp_name'],
            $rutaNueva
        )
    ) {

        if (
            !empty($imagen) &&
            file_exists(
                $carpeta . $imagen
            )
        ) {

            unlink(
                $carpeta . $imagen
            );

        }


        $imagen = $nuevaImagen;

    }

}


/*
|--------------------------------------------------------------------------
| INICIAR TRANSACCION
|--------------------------------------------------------------------------
*/

try {

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR PRODUCTO
    |--------------------------------------------------------------------------
    |
    | Si tiene variantes, el stock principal será la suma
    | de todos los talles.
    |
    */

    $stockProducto = $tieneVariantes
        ? 0
        : $stock;


    $stmtActualizar = $db->prepare("

        UPDATE productos SET

            codigo = ?,
            nombre = ?,
            categoria_id = ?,
            proveedor_id = ?,
            stock = ?,
            stock_minimo = ?,
            precio_compra = ?,
            precio_venta = ?,
            descripcion = ?,
            imagen = ?

        WHERE id = ?

    ");


    $stmtActualizar->execute([

        $codigo,
        $nombre,
        $categoria_id,
        $proveedor_id,
        $stockProducto,
        $stock_minimo,
        $precio_compra,
        $precio_venta,
        $descripcion,
        $imagen,
        $id

    ]);


    /*
    |--------------------------------------------------------------------------
    | PRODUCTO CON VARIANTES
    |--------------------------------------------------------------------------
    */

    if ($tieneVariantes) {


        $varianteIds =
            $_POST['variante_id'] ?? [];

        $varianteTalles =
            $_POST['variante_talle'] ?? [];

        $varianteColores =
            $_POST['variante_color'] ?? [];

        $varianteCodigos =
            $_POST['variante_codigo'] ?? [];

        $varianteStocks =
            $_POST['variante_stock'] ?? [];


        /*
        |--------------------------------------------------------------------------
        | OBTENER VARIANTES ACTUALES
        |--------------------------------------------------------------------------
        */

        $stmtActuales = $db->prepare("
            SELECT id
            FROM producto_variantes
            WHERE producto_id = ?
        ");

        $stmtActuales->execute([$id]);

        $actuales = $stmtActuales->fetchAll(
            PDO::FETCH_COLUMN
        );


        $idsRecibidos = [];


        /*
        |--------------------------------------------------------------------------
        | PREPARAR CONSULTAS
        |--------------------------------------------------------------------------
        */

        $stmtInsertar = $db->prepare("
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
                ?,
                ?,
                ?,
                ?,
                ?,
                1
            )
        ");


        $stmtActualizarVariante = $db->prepare("
            UPDATE producto_variantes
            SET
                talle = ?,
                color = ?,
                codigo = ?,
                stock = ?,
                activo = 1
            WHERE id = ?
            AND producto_id = ?
        ");


        $stockTotal = 0;


        /*
        |--------------------------------------------------------------------------
        | RECORRER VARIANTES
        |--------------------------------------------------------------------------
        */

        foreach ($varianteTalles as $index => $talle) {


            $talle = trim(
                $talle ?? ''
            );


            $color = trim(
                $varianteColores[$index] ?? ''
            );


            $codigoVariante = trim(
                $varianteCodigos[$index] ?? ''
            );


            $stockVariante = max(
                0,
                (int)(
                    $varianteStocks[$index] ?? 0
                )
            );


            $varianteId = !empty(
                $varianteIds[$index]
            )
                ? (int)$varianteIds[$index]
                : 0;


            /*
            |--------------------------------------------------------------------------
            | NO GUARDAR FILAS VACIAS
            |--------------------------------------------------------------------------
            */

            if (
                $talle === '' &&
                $color === '' &&
                $codigoVariante === ''
            ) {

                continue;

            }


            /*
            |--------------------------------------------------------------------------
            | EL TALLE ES OBLIGATORIO
            |--------------------------------------------------------------------------
            */

            if ($talle === '') {

                throw new Exception(
                    "Todas las variantes deben tener un talle."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | ACTUALIZAR VARIANTE EXISTENTE
            |--------------------------------------------------------------------------
            */

            if ($varianteId > 0) {


                $stmtActualizarVariante->execute([

                    $talle,
                    $color,
                    $codigoVariante,
                    $stockVariante,
                    $varianteId,
                    $id

                ]);


                $idsRecibidos[] =
                    $varianteId;


            } else {


                /*
                |--------------------------------------------------------------------------
                | CREAR NUEVA VARIANTE
                |--------------------------------------------------------------------------
                */

                $stmtInsertar->execute([

                    $id,
                    $talle,
                    $color,
                    $codigoVariante,
                    $stockVariante

                ]);


                $nuevoId =
                    (int)$db->lastInsertId();


                $idsRecibidos[] =
                    $nuevoId;

            }


            /*
            |--------------------------------------------------------------------------
            | SUMAR STOCK TOTAL
            |--------------------------------------------------------------------------
            */

            $stockTotal +=
                $stockVariante;

        }


        /*
        |--------------------------------------------------------------------------
        | ELIMINAR VARIANTES QUE FUERON BORRADAS
        |--------------------------------------------------------------------------
        */

        if (!empty($actuales)) {


            foreach ($actuales as $idActual) {


                if (
                    !in_array(
                        (int)$idActual,
                        $idsRecibidos
                    )
                ) {

                    $stmtEliminar =
                        $db->prepare("
                            DELETE FROM producto_variantes
                            WHERE id = ?
                            AND producto_id = ?
                        ");


                    $stmtEliminar->execute([

                        $idActual,
                        $id

                    ]);

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR STOCK TOTAL DEL PRODUCTO
        |--------------------------------------------------------------------------
        */

        $stmtStockTotal =
            $db->prepare("
                UPDATE productos
                SET stock = ?
                WHERE id = ?
            ");


        $stmtStockTotal->execute([

            $stockTotal,
            $id

        ]);


    } else {


        /*
        |--------------------------------------------------------------------------
        | PRODUCTO SIN VARIANTES
        |--------------------------------------------------------------------------
        |
        | Si se desmarca "Tiene talles", eliminamos
        | todas las variantes.
        |
        */

        $stmtEliminarVariantes =
            $db->prepare("
                DELETE FROM producto_variantes
                WHERE producto_id = ?
            ");


        $stmtEliminarVariantes->execute([

            $id

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR
    |--------------------------------------------------------------------------
    */

    $db->commit();


    header(
        "Location: index.php?actualizado=1"
    );

    exit;


} catch (Throwable $e) {


    if (
        $db->inTransaction()
    ) {

        $db->rollBack();

    }


    die(
        "Error al actualizar el producto: " .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}

?>