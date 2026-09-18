<?php

session_start();

require_once "../config/conexion.php";


$db = Conexion::conectar();


$email = trim($_POST['email']);
$password = $_POST['password'];



$stmt = $db->prepare("
SELECT *
FROM clientes
WHERE email=?
AND estado=1
");


$stmt->execute([$email]);


$cliente = $stmt->fetch();



if(!$cliente){

header("Location: login.php?error=1");
exit;

}



if(!password_verify($password,$cliente['password'])){

header("Location: login.php?error=1");
exit;

}



$_SESSION['cliente_id']=$cliente['id'];

$_SESSION['cliente_nombre']=$cliente['nombre'];

$_SESSION['cliente_email']=$cliente['email'];



header("Location: perfil.php");

exit;