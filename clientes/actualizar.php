<?php

session_start();

require_once "../config/conexion.php";


if(!isset($_SESSION['cliente_id'])){

header("Location: login.php");
exit;

}


$db=Conexion::conectar();



$id=$_SESSION['cliente_id'];


$dni=trim($_POST['dni']);
$apellido=trim($_POST['apellido']);
$nombre=trim($_POST['nombre']);
$telefono=trim($_POST['telefono']);
$direccion=trim($_POST['direccion']);
$email=trim($_POST['email']);



$stmt=$db->prepare("

SELECT id
FROM clientes
WHERE email=?
AND id<>?

");


$stmt->execute([

$email,
$id

]);



if($stmt->fetch()){


die("El email ya está registrado");

}



$stmt=$db->prepare("

UPDATE clientes SET

dni=?,
apellido=?,
nombre=?,
telefono=?,
direccion=?,
email=?

WHERE id=?

");



$stmt->execute([

$dni,
$apellido,
$nombre,
$telefono,
$direccion,
$email,
$id

]);



$_SESSION['cliente_nombre']=$nombre;

$_SESSION['cliente_email']=$email;



header("Location: perfil.php?ok=1");

exit;