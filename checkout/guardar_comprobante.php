<?php

session_start();

require_once "../config/conexion.php";


/*
|--------------------------------------------------------------------------
| VALIDAR CLIENTE / PEDIDO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['pedido_id'])) {

    header("Location: ../tienda/index.php");
    exit;

}


$pedido_id = (int)$_SESSION['pedido_id'];


/*
|--------------------------------------------------------------------------
| VALIDAR ARCHIVO
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['comprobante']) ||
    $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK
) {

    $_SESSION['comprobante_error'] =
        "Debe seleccionar un comprobante válido.";

    header("Location: comprobante.php");
    exit;

}


$archivo = $_FILES['comprobante'];


/*
|--------------------------------------------------------------------------
| VALIDAR TAMAÑO
|--------------------------------------------------------------------------
|
| Máximo: 5 MB
|
*/

$maximo = 5 * 1024 * 1024;

if ($archivo['size'] > $maximo) {

    $_SESSION['comprobante_error'] =
        "El comprobante no puede superar los 5 MB.";

    header("Location: comprobante.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| VALIDAR EXTENSIÓN
|--------------------------------------------------------------------------
*/

$nombreOriginal = $archivo['name'];

$extension = strtolower(
    pathinfo($nombreOriginal, PATHINFO_EXTENSION)
);


$extensionesPermitidas = [
    'jpg',
    'jpeg',
    'png',
    'pdf'
];


if (!in_array($extension, $extensionesPermitidas, true)) {

    $_SESSION['comprobante_error'] =
        "Formato no permitido. Solo JPG, JPEG, PNG o PDF.";

    header("Location: comprobante.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| VALIDAR MIME REAL
|--------------------------------------------------------------------------
*/

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($archivo['tmp_name']);


$mimePermitidos = [

    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'pdf'  => 'application/pdf'

];


if (
    !isset($mimePermitidos[$extension]) ||
    $mime !== $mimePermitidos[$extension]
) {

    $_SESSION['comprobante_error'] =
        "El archivo seleccionado no es válido.";

    header("Location: comprobante.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| CONEXIÓN
|--------------------------------------------------------------------------
*/

$db = Conexion::conectar();


try {

    /*
    |--------------------------------------------------------------------------
    | BUSCAR PEDIDO
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT
            id,
            total,
            estado,
            metodo_pago
        FROM pedidos
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $pedido_id
    ]);

    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$pedido) {

        throw new Exception(
            "El pedido no existe."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR MÉTODO
    |--------------------------------------------------------------------------
    */

    if ($pedido['metodo_pago'] !== 'Transferencia') {

        throw new Exception(
            "Este pedido no corresponde a una transferencia bancaria."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR ESTADO
    |--------------------------------------------------------------------------
    */

    if (
        strtoupper(trim($pedido['estado'])) !== 'PENDIENTE'
    ) {

        throw new Exception(
            "Este pedido ya no se encuentra pendiente de pago."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CREAR CARPETA
    |--------------------------------------------------------------------------
    */

    $carpeta = "../uploads/comprobantes/";


    if (!is_dir($carpeta)) {

        if (!mkdir($carpeta, 0755, true)) {

            throw new Exception(
                "No se pudo crear la carpeta de comprobantes."
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | GENERAR NOMBRE SEGURO
    |--------------------------------------------------------------------------
    */

    $nombreArchivo =
        "pedido_" .
        $pedido_id .
        "_" .
        uniqid('', true) .
        "." .
        $extension;


    $rutaCompleta =
        $carpeta .
        $nombreArchivo;


    /*
    |--------------------------------------------------------------------------
    | MOVER ARCHIVO
    |--------------------------------------------------------------------------
    */

    if (
        !move_uploaded_file(
            $archivo['tmp_name'],
            $rutaCompleta
        )
    ) {

        throw new Exception(
            "No se pudo guardar el comprobante."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR PEDIDO
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | La ruta se guarda dentro del pedido.
    |
    */

    $stmtUpdate = $db->prepare("
        UPDATE pedidos
        SET
            estado = 'PAGO EN REVISION'
        WHERE id = ?
    ");

    $stmtUpdate->execute([
        $pedido_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | GUARDAR INFORMACIÓN DEL COMPROBANTE
    |--------------------------------------------------------------------------
    |
    | Si la tabla pedidos todavía no tiene una columna
    | para almacenar el archivo, por ahora guardamos
    | el comprobante mediante una tabla independiente.
    |
    */

    $stmtExiste = $db->query("
        SHOW TABLES LIKE 'comprobantes_pedidos'
    ");


    $tablaExiste = $stmtExiste->fetchColumn();


    if (!$tablaExiste) {

        $db->exec("
            CREATE TABLE comprobantes_pedidos (

                id INT(11) NOT NULL AUTO_INCREMENT,

                pedido_id INT(11) NOT NULL,

                archivo VARCHAR(255) NOT NULL,

                nombre_original VARCHAR(255) NULL,

                fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                estado VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE',

                PRIMARY KEY (id),

                INDEX (pedido_id)

            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_general_ci
        ");

    }


    /*
    |--------------------------------------------------------------------------
    | INSERTAR COMPROBANTE
    |--------------------------------------------------------------------------
    */

    $stmtComprobante = $db->prepare("
        INSERT INTO comprobantes_pedidos
        (
            pedido_id,
            archivo,
            nombre_original,
            fecha,
            estado
        )
        VALUES
        (
            ?,
            ?,
            ?,
            NOW(),
            'PENDIENTE'
        )
    ");


    $stmtComprobante->execute([

        $pedido_id,

        $nombreArchivo,

        $nombreOriginal

    ]);


    /*
    |--------------------------------------------------------------------------
    | SESIÓN
    |--------------------------------------------------------------------------
    */

    $_SESSION['comprobante_enviado'] = true;


    /*
    |--------------------------------------------------------------------------
    | REDIRECCIÓN
    |--------------------------------------------------------------------------
    */

    header("Location: comprobante_exito.php");
    exit;


} catch (Exception $e) {


    /*
    |--------------------------------------------------------------------------
    | BORRAR ARCHIVO SI HUBO ERROR
    |--------------------------------------------------------------------------
    */

    if (
        isset($rutaCompleta) &&
        file_exists($rutaCompleta)
    ) {

        unlink($rutaCompleta);

    }


    /*
    |--------------------------------------------------------------------------
    | GUARDAR ERROR
    |--------------------------------------------------------------------------
    */

    $_SESSION['comprobante_error'] =
        $e->getMessage();


    header("Location: comprobante.php");
    exit;

}