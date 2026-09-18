<?php

require_once("../config/conexion.php");
require_once("../config/config.php");

$pdo = Conexion::conectar();

$usuario = $_POST['usuario'];
$password = $_POST['password'];

$sql = "SELECT * FROM usuarios_stock WHERE usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario]);

$usuarioBD = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuarioBD && md5($password) === $usuarioBD['password']) {

    $_SESSION['usuario'] = $usuarioBD['nombre'];
    $_SESSION['usuario_id'] = $usuarioBD['id'];
    $_SESSION['rol'] = $usuarioBD['rol'];
    $_SESSION['ultima_actividad'] = time();

    header("Location: " . URL . "dashboard/index.php");
    exit;

} else {

    header("Location: " . URL . "login/index.php?error=1");
    exit;
}




















//require_once "config/conexion.php";

//session_start();

//$pdo = Conexion::conectar();

//$usuario = $_POST['usuario'];
//$password = $_POST['password'];

//$sql = "SELECT * FROM usuarios WHERE usuario = ?";

//$stmt = $pdo->prepare($sql);
//$stmt->execute([$usuario]);

//$usuarioBD = $stmt->fetch();

//if ($usuarioBD && password_verify($password, $usuarioBD['password'])) {
//if ($usuarioBD && md5($password) === $usuarioBD['password']) {
//    $_SESSION['usuario'] = $usuarioBD['nombre'];
//    $_SESSION['usuario_id'] = $usuarioBD['id'];
//    $_SESSION['rol'] = $usuarioBD['rol'];
//
//    header("Location: dashboard/");
//    exit;
//
//} else {
//
//    header("Location: login.php?error=1");
//   exit;
//
//}