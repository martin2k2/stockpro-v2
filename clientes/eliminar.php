<?php
// clientes/eliminar.php

session_start();

require_once "../config/conexion.php";

$conexion = (new Conexion())->conectar();


/* =========================================================
   VERIFICAR SESIÓN
========================================================= */

if (!isset($_SESSION["usuario_id"])) {

    echo "<script>
        alert('Sesión no válida.');
        window.location.href='index.php';
    </script>";

    exit;
}

$usuario_id = (int)$_SESSION["usuario_id"];


/* =========================================================
   VERIFICAR USUARIO Y ROL
========================================================= */

$stmt = $conexion->prepare("
    SELECT id, usuario, nombre, rol
    FROM usuarios_stock
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$usuario_id]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$usuario) {

    echo "<script>
        alert('Usuario no encontrado.');
        window.location.href='index.php';
    </script>";

    exit;
}


$rol = strtoupper(trim($usuario["rol"]));


if ($rol !== "ADMIN") {

    echo "<script>
        alert('Acceso denegado. Solo los administradores pueden eliminar clientes.');
        window.location.href='index.php';
    </script>";

    exit;
}


/* =========================================================
   ID CLIENTE
========================================================= */

$id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


if ($id <= 0) {

    echo "<script>
        alert('Cliente inválido.');
        window.location.href='index.php';
    </script>";

    exit;
}


/* =========================================================
   BUSCAR CLIENTE
========================================================= */

$stmt = $conexion->prepare("
    SELECT *
    FROM clientes
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$cliente) {

    echo "<script>
        alert('El cliente no existe.');
        window.location.href='index.php';
    </script>";

    exit;
}


/* =========================================================
   VERIFICAR SI TIENE VENTAS
========================================================= */

$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM ventas
    WHERE cliente_id = ?
");

$stmt->execute([$id]);

$cantidadVentas = (int)$stmt->fetchColumn();


/* =========================================================
   SI TIENE VENTAS
   NO SE ELIMINA: SE DESACTIVA
========================================================= */

if ($cantidadVentas > 0) {

    try {

        $stmt = $conexion->prepare("
            UPDATE clientes
            SET estado = 'INACTIVO'
            WHERE id = ?
        ");

        $stmt->execute([$id]);


        echo "<script>

            alert(
                'El cliente tiene ventas registradas.\\n\\n' +
                'No se puede eliminar porque se debe conservar el historial.\\n\\n' +
                'El cliente fue marcado como INACTIVO.'
            );

            window.location.href='index.php';

        </script>";

        exit;

    } catch (PDOException $e) {

        echo "<script>

            alert(
                'Error al desactivar el cliente:\\n\\n" .
                addslashes($e->getMessage()) .
                "'
            );

            window.location.href='index.php';

        </script>";

        exit;
    }
}


/* =========================================================
   ELIMINAR SI NO TIENE VENTAS
========================================================= */

try {

    $stmt = $conexion->prepare("
        DELETE FROM clientes
        WHERE id = ?
    ");

    $stmt->execute([$id]);


    echo "<script>

        alert('Cliente eliminado correctamente.');

        window.location.href='index.php';

    </script>";

    exit;


} catch (PDOException $e) {

    echo "<script>

        alert(
            'No se pudo eliminar el cliente:\\n\\n" .
            addslashes($e->getMessage()) .
            "'
        );

        window.location.href='index.php';

    </script>";

    exit;
}
?>