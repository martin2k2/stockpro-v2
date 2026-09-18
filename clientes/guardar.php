<?php

session_start();

require_once "../config/conexion.php";

$db = Conexion::conectar();


$dni = trim($_POST["dni"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$nombre = trim($_POST["nombre"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$direccion = trim($_POST["direccion"] ?? "");
$email = trim($_POST["email"] ?? "");

$password = $_POST["password"] ?? "";
$password2 = $_POST["password2"] ?? "";


/*
|--------------------------------------------------------------------------
| VALIDAR CONTRASEÑAS
|--------------------------------------------------------------------------
*/

if ($password !== $password2) {

    header("Location: registro.php?error=password");
    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR EMAIL
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT id
    FROM clientes
    WHERE email = ?
    LIMIT 1
");

$stmt->execute([$email]);

if ($stmt->fetch()) {

    header("Location: registro.php?error=email");
    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR DNI
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT id
    FROM clientes
    WHERE dni = ?
    LIMIT 1
");

$stmt->execute([$dni]);

if ($stmt->fetch()) {

    header("Location: registro.php?error=dni");
    exit;
}


/*
|--------------------------------------------------------------------------
| CREAR CONTRASEÑA
|--------------------------------------------------------------------------
*/

$hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


/*
|--------------------------------------------------------------------------
| CREAR CLIENTE
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    INSERT INTO clientes
    (
        dni,
        apellido,
        nombre,
        telefono,
        direccion,
        email,
        password,
        estado,
        fecha_alta
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        'ACTIVO',
        NOW()
    )
");

$stmt->execute([
    $dni,
    $apellido,
    $nombre,
    $telefono,
    $direccion,
    $email,
    $hash
]);


/*
|--------------------------------------------------------------------------
| IMPORTANTE
|--------------------------------------------------------------------------
|
| NO iniciar sesión automáticamente.
|
| El cliente deberá entrar con su email
| y contraseña cuando quiera comprar.
|
*/


/*
|--------------------------------------------------------------------------
| IR A LA TIENDA
|--------------------------------------------------------------------------
*/

header("Location: ../tienda/index.php");
exit;