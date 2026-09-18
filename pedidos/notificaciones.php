<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/conexion.php";
require_once "../includes/auth.php";

requireAdmin();


if (!isset($_SESSION["usuario_id"])) {

    echo json_encode([
        "ok" => false
    ]);

    exit;
}


try {

    $db = Conexion::conectar();


    /*
    |--------------------------------------------------------------------------
    | CANTIDAD DE PEDIDOS PAGADOS
    |--------------------------------------------------------------------------
    */

    $stmt = $db->query("
        SELECT COUNT(*) 
        FROM pedidos
        WHERE estado = 'Pagado'
    ");

    $cantidad = (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | ÚLTIMO PEDIDO PAGADO
    |--------------------------------------------------------------------------
    */

    $stmt = $db->query("
        SELECT
            p.id,
            p.fecha,
            p.total,
            CONCAT(c.apellido, ' ', c.nombre) AS cliente
        FROM pedidos p
        INNER JOIN clientes c
            ON c.id = p.cliente_id
        WHERE p.estado = 'Pagado'
        ORDER BY p.id DESC
        LIMIT 1
    ");

    $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);


    echo json_encode([
        "ok" => true,
        "cantidad" => $cantidad,
        "ultimo" => $ultimo ?: null
    ]);

} catch (Throwable $e) {

    echo json_encode([
        "ok" => false
    ]);
}