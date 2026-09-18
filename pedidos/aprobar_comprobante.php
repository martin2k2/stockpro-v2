<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";
require_once "../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

requireAdmin();


if (!isset($_SESSION["usuario_id"])) {

    header("Location: ../login/index.php");
    exit;

}


if (strtoupper(trim($_SESSION["rol"] ?? "")) !== "ADMIN") {

    http_response_code(403);
    exit("Acceso denegado.");

}


/*
|--------------------------------------------------------------------------
| ID DEL COMPROBANTE
|--------------------------------------------------------------------------
*/

$comprobante_id = isset($_POST["comprobante_id"])
    ? (int) $_POST["comprobante_id"]
    : 0;


if ($comprobante_id <= 0) {

    header(
        "Location: comprobantes.php?error=comprobante_invalido"
    );

    exit;

}


try {

    $db = Conexion::conectar();

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | OBTENER COMPROBANTE, PEDIDO Y CLIENTE
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            cp.id AS comprobante_id,
            cp.pedido_id,
            cp.estado AS estado_comprobante,

            p.cliente_id,
            p.total,
            p.estado AS estado_pedido,
            p.metodo_pago,

            c.nombre,
            c.apellido,
            c.email

        FROM comprobantes_pedidos cp

        INNER JOIN pedidos p
            ON p.id = cp.pedido_id

        INNER JOIN clientes c
            ON c.id = p.cliente_id

        WHERE cp.id = ?

        LIMIT 1

        FOR UPDATE
    ";


    $stmt = $db->prepare($sql);

    $stmt->execute([
        $comprobante_id
    ]);


    $comprobante =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$comprobante) {

        throw new Exception(
            "El comprobante no existe."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR ESTADO DEL COMPROBANTE
    |--------------------------------------------------------------------------
    */

    $estadoComprobante = strtoupper(
        trim(
            (string)$comprobante["estado_comprobante"]
        )
    );


    if ($estadoComprobante !== "PENDIENTE") {

        throw new Exception(
            "Este comprobante ya fue procesado."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR MÉTODO DE PAGO
    |--------------------------------------------------------------------------
    */

    $metodoPago = strtoupper(
        trim(
            (string)$comprobante["metodo_pago"]
        )
    );


    if ($metodoPago !== "TRANSFERENCIA") {

        throw new Exception(
            "El pedido no corresponde a una transferencia bancaria."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER DETALLE DEL PEDIDO
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            pd.producto_id,
            pd.variante_id,
            pd.cantidad,
            pd.precio,
            pd.subtotal,

            p.nombre,
            p.stock,

            pv.talle,
            pv.color,
            pv.stock AS stock_variante

        FROM pedido_detalle pd

        INNER JOIN productos p
            ON p.id = pd.producto_id

        LEFT JOIN producto_variantes pv
            ON pv.id = pd.variante_id

        WHERE pd.pedido_id = ?

        FOR UPDATE
    ";


    $stmt = $db->prepare($sql);

    $stmt->execute([
        $comprobante["pedido_id"]
    ]);


    $detalles =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    if (empty($detalles)) {

        throw new Exception(
            "El pedido no tiene productos."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR STOCK
    |--------------------------------------------------------------------------
    */

    foreach ($detalles as $detalle) {

        $cantidad =
            (float)$detalle["cantidad"];

        $nombre =
            (string)$detalle["nombre"];


        if ($cantidad <= 0) {

            throw new Exception(
                "Cantidad inválida para el producto: " .
                $nombre
            );

        }


        /*
        |--------------------------------------------------------------------------
        | VALIDAR VARIANTE
        |--------------------------------------------------------------------------
        */

        if (!empty($detalle["variante_id"])) {

            if (
                (float)$detalle["stock_variante"]
                < $cantidad
            ) {

                throw new Exception(
                    "Stock insuficiente para la variante del producto: " .
                    $nombre .
                    " - Talle: " .
                    ($detalle["talle"] ?? "-") .
                    " - Color: " .
                    ($detalle["color"] ?? "-")
                );

            }

        } else {


            /*
            |--------------------------------------------------------------------------
            | VALIDAR PRODUCTO NORMAL
            |--------------------------------------------------------------------------
            */

            if (
                (float)$detalle["stock"]
                < $cantidad
            ) {

                throw new Exception(
                    "Stock insuficiente para el producto: " .
                    $nombre
                );

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DESCONTAR STOCK
    |--------------------------------------------------------------------------
    */

    foreach ($detalles as $detalle) {

        $cantidad =
            (float)$detalle["cantidad"];


        /*
        |--------------------------------------------------------------------------
        | DESCONTAR STOCK DE VARIANTE
        |--------------------------------------------------------------------------
        */

        if (!empty($detalle["variante_id"])) {

            $sql = "
                UPDATE producto_variantes

                SET stock = stock - ?

                WHERE id = ?

                AND stock >= ?
            ";


            $stmt = $db->prepare($sql);


            $stmt->execute([

                $cantidad,

                $detalle["variante_id"],

                $cantidad

            ]);


            if ($stmt->rowCount() !== 1) {

                throw new Exception(
                    "No se pudo actualizar el stock de la variante."
                );

            }


        } else {


            /*
            |--------------------------------------------------------------------------
            | DESCONTAR STOCK DE PRODUCTO NORMAL
            |--------------------------------------------------------------------------
            */

            $sql = "
                UPDATE productos

                SET stock = stock - ?

                WHERE id = ?

                AND stock >= ?
            ";


            $stmt = $db->prepare($sql);


            $stmt->execute([

                $cantidad,

                $detalle["producto_id"],

                $cantidad

            ]);


            if ($stmt->rowCount() !== 1) {

                throw new Exception(
                    "No se pudo actualizar el stock del producto."
                );

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | REGISTRAR MOVIMIENTOS
    |--------------------------------------------------------------------------
    */

    foreach ($detalles as $detalle) {

        $cantidad =
            (float)$detalle["cantidad"];


        $observacion =
            "Venta web - Pedido #" .
            $comprobante["pedido_id"] .
            " - Transferencia bancaria";


        if (!empty($detalle["variante_id"])) {

            $observacion .=
                " - Talle: " .
                ($detalle["talle"] ?? "-") .
                " - Color: " .
                ($detalle["color"] ?? "-");

        }


        $sql = "
            INSERT INTO movimientos
            (
                producto_id,
                tipo,
                cantidad,
                observacion,
                usuario_id,
                fecha
            )
            VALUES
            (
                ?,
                'SALIDA',
                ?,
                ?,
                ?,
                NOW()
            )
        ";


        $stmt = $db->prepare($sql);


        $stmt->execute([

            $detalle["producto_id"],

            $cantidad,

            $observacion,

            $_SESSION["usuario_id"]

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | CREAR VENTA
    |--------------------------------------------------------------------------
    */

    $sql = "
        INSERT INTO ventas
        (
            fecha,
            usuario_id,
            cliente_id,
            total,
            forma_pago,
            estado
        )
        VALUES
        (
            NOW(),
            ?,
            ?,
            ?,
            'TRANSFERENCIA',
            'PAGADA'
        )
    ";


    $stmt = $db->prepare($sql);


    $stmt->execute([

        $_SESSION["usuario_id"],

        $comprobante["cliente_id"],

        $comprobante["total"]

    ]);


    $venta_id =
        (int)$db->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | GUARDAR DETALLE DE VENTA
    |--------------------------------------------------------------------------
    */

    $sql = "
        INSERT INTO detalle_ventas
        (
            venta_id,
            producto_id,
            variante_id,
            cantidad,
            precio,
            descuento,
            subtotal
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            0,
            ?
        )
    ";


    $stmt = $db->prepare($sql);


    foreach ($detalles as $detalle) {

        $stmt->execute([

            $venta_id,

            $detalle["producto_id"],

            !empty($detalle["variante_id"])
                ? $detalle["variante_id"]
                : null,

            $detalle["cantidad"],

            $detalle["precio"],

            $detalle["subtotal"]

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | APROBAR COMPROBANTE
    |--------------------------------------------------------------------------
    */

    $sql = "
        UPDATE comprobantes_pedidos

        SET estado = 'APROBADO'

        WHERE id = ?
    ";


    $stmt = $db->prepare($sql);


    $stmt->execute([
        $comprobante_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | MARCAR PEDIDO COMO PAGADO
    |--------------------------------------------------------------------------
    */

    $sql = "
        UPDATE pedidos

        SET estado = 'Pagado'

        WHERE id = ?
    ";


    $stmt = $db->prepare($sql);


    $stmt->execute([
        $comprobante["pedido_id"]
    ]);


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $db->commit();


    /*
    |--------------------------------------------------------------------------
    | ENVIAR MAIL AL CLIENTE
    |--------------------------------------------------------------------------
    */

    if (!empty($comprobante["email"])) {

        try {

            $mail = new PHPMailer(true);


            /*
            |--------------------------------------------------------------------------
            | CONFIGURACIÓN SMTP
            |--------------------------------------------------------------------------
            */

            $mail->isSMTP();

            $mail->Host =
                "smtp.gmail.com";

            $mail->SMTPAuth =
                true;

            $mail->Username =
                "soporteurquiza@gmail.com";

            /*
            |--------------------------------------------------------------------------
            | CONTRASEÑA DE APLICACIÓN DE GMAIL
            |--------------------------------------------------------------------------
            */

            $mail->Password =
                "easz rukf yxsx dnka";

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port =
                587;


            /*
            |--------------------------------------------------------------------------
            | UTF-8
            |--------------------------------------------------------------------------
            */

            $mail->CharSet =
                "UTF-8";


            /*
            |--------------------------------------------------------------------------
            | REMITENTE
            |--------------------------------------------------------------------------
            */

            $mail->setFrom(

                "soporteurquiza@gmail.com",

                "Urquiza"

            );


            /*
            |--------------------------------------------------------------------------
            | DESTINATARIO
            |--------------------------------------------------------------------------
            */

            $mail->addAddress(

                $comprobante["email"],

                trim(

                    $comprobante["nombre"] .
                    " " .
                    $comprobante["apellido"]

                )

            );


            /*
            |--------------------------------------------------------------------------
            | DATOS DEL MAIL
            |--------------------------------------------------------------------------
            */

            $pedidoNumero =
                (int)$comprobante["pedido_id"];


            $clienteNombre =
                htmlspecialchars(

                    trim(

                        $comprobante["nombre"] .
                        " " .
                        $comprobante["apellido"]

                    )

                );


            $totalPedido =
                number_format(

                    (float)$comprobante["total"],

                    2,

                    ",",

                    "."

                );


            /*
            |--------------------------------------------------------------------------
            | ASUNTO
            |--------------------------------------------------------------------------
            */

            $mail->Subject =
                "Pago acreditado - Pedido #" .
                $pedidoNumero;


            /*
            |--------------------------------------------------------------------------
            | FORMATO HTML
            |--------------------------------------------------------------------------
            */

            $mail->isHTML(true);


            $mail->Body = '

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

</head>

<body style="
    font-family: Arial, sans-serif;
    color: #333;
    line-height: 1.6;
">

<div style="
    max-width: 600px;
    margin: auto;
    padding: 30px;
    border: 1px solid #ddd;
">

<h2 style="
    color: #198754;
">

¡Pago acreditado correctamente!

</h2>


<p>

Hola

<strong>' .
$clienteNombre .
'</strong>

</p>


<p>

Confirmamos que recibimos correctamente
el pago de tu pedido.

</p>


<hr>


<p>

<strong>

Pedido:

</strong>

#' .
$pedidoNumero .
'

</p>


<p>

<strong>

Total abonado:

</strong>

$' .
$totalPedido .
'

</p>


<hr>


<h3>

Tu pedido ya está confirmado

</h3>


<p>

Ya podés pasar a retirarlo por nuestro predio.

</p>


<p>

Te recomendamos presentar tu número de pedido
al momento del retiro.

</p>


<br>


<p>

Gracias por elegirnos.

</p>


<strong>

URQUIZA

</strong>


</div>

</body>

</html>

';


            /*
            |--------------------------------------------------------------------------
            | VERSIÓN TEXTO PLANO
            |--------------------------------------------------------------------------
            */

            $mail->AltBody =

                "Hola " .

                trim(

                    $comprobante["nombre"] .
                    " " .
                    $comprobante["apellido"]

                ) .

                ".\n\n" .

                "Confirmamos que el pago de tu pedido #" .

                $pedidoNumero .

                " fue acreditado correctamente.\n\n" .

                "Total abonado: $" .

                $totalPedido .

                "\n\n" .

                "Tu pedido ya está confirmado y podés pasar a retirarlo por nuestro predio.\n\n" .

                "Gracias por elegirnos.\n" .

                "URQUIZA";


            /*
            |--------------------------------------------------------------------------
            | ENVIAR MAIL
            |--------------------------------------------------------------------------
            */

            $mail->send();


        } catch (Throwable $mailError) {


            error_log(

                "Error enviando mail del pedido #" .

                $comprobante["pedido_id"] .

                ": " .

                $mailError->getMessage()

            );


        }

    }


    /*
    |--------------------------------------------------------------------------
    | VOLVER A COMPROBANTES
    |--------------------------------------------------------------------------
    */

    header(
        "Location: comprobantes.php?ok=aprobado"
    );

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | DESHACER TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    if (

        isset($db) &&

        $db->inTransaction()

    ) {

        $db->rollBack();

    }


    /*
    |--------------------------------------------------------------------------
    | VOLVER CON ERROR
    |--------------------------------------------------------------------------
    */

    header(

        "Location: comprobantes.php?error=" .

        urlencode(

            $e->getMessage()

        )

    );

    exit;

}