<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";
require_once "../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;

requireAdmin();


if (!isset($_SESSION["usuario_id"])) {

    header("Location: ../login/index.php");
    exit;

}


if (
    strtoupper(
        trim($_SESSION["rol"] ?? "")
    ) !== "ADMIN"
) {

    http_response_code(403);
    exit("Acceso denegado.");

}


/*
|--------------------------------------------------------------------------
| ID DEL COMPROBANTE
|--------------------------------------------------------------------------
*/

$comprobante_id = isset($_POST["comprobante_id"])
    ? (int)$_POST["comprobante_id"]
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
    | BUSCAR COMPROBANTE, PEDIDO Y CLIENTE
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            cp.id AS comprobante_id,
            cp.pedido_id,
            cp.estado AS estado_comprobante,

            p.id AS pedido_id_real,
            p.estado AS estado_pedido,
            p.metodo_pago,
            p.total,

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
    | VALIDAR COMPROBANTE
    |--------------------------------------------------------------------------
    */

    if (
        strtoupper(
            trim(
                (string)$comprobante["estado_comprobante"]
            )
        ) !== "PENDIENTE"
    ) {

        throw new Exception(
            "Este comprobante ya fue procesado."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR TRANSFERENCIA
    |--------------------------------------------------------------------------
    */

    if (
        strtoupper(
            trim(
                (string)$comprobante["metodo_pago"]
            )
        ) !== "TRANSFERENCIA"
    ) {

        throw new Exception(
            "El pedido no corresponde a una transferencia bancaria."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RECHAZAR COMPROBANTE
    |--------------------------------------------------------------------------
    */

    $sql = "
        UPDATE comprobantes_pedidos
        SET estado = 'RECHAZADO'
        WHERE id = ?
    ";


    $stmt = $db->prepare($sql);

    $stmt->execute([
        $comprobante_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR PEDIDO
    |--------------------------------------------------------------------------
    */

    $sql = "
        UPDATE pedidos
        SET estado = 'Pago rechazado'
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
            | DATOS DEL PEDIDO
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
                "Pago rechazado - Pedido #" .
                $pedidoNumero;


            $mail->isHTML(true);


            /*
            |--------------------------------------------------------------------------
            | CUERPO DEL MAIL
            |--------------------------------------------------------------------------
            */

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
    color: #dc3545;
">

Tu comprobante de pago fue rechazado

</h2>


<p>

Hola

<strong>' .
$clienteNombre .
'</strong>

</p>


<p>

Lamentablemente no pudimos aprobar
el comprobante de pago correspondiente
a tu pedido.

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

Total del pedido:

</strong>

$' .
$totalPedido .
'

</p>


<hr>


<h3>

¿Qué significa esto?

</h3>


<p>

El pago informado no pudo ser validado.

</p>


<p>

Te recomendamos revisar los datos de la transferencia
o comunicarte con nosotros para poder resolver la situación.

</p>


<p>

Una vez solucionado el inconveniente,
podrás enviar nuevamente el comprobante correspondiente.

</p>


<br>


<p>

Ante cualquier consulta,
comunicate con nosotros.

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

                "Tu comprobante de pago correspondiente al pedido #" .

                $pedidoNumero .

                " no pudo ser aprobado.\n\n" .

                "Total del pedido: $" .

                $totalPedido .

                "\n\n" .

                "Te recomendamos revisar los datos de la transferencia o comunicarte con nosotros para resolver la situación.\n\n" .

                "URQUIZA";


            /*
            |--------------------------------------------------------------------------
            | ENVIAR MAIL
            |--------------------------------------------------------------------------
            */

            $mail->send();


        } catch (Throwable $mailError) {

            error_log(

                "Error enviando mail de rechazo del pedido #" .

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
        "Location: comprobantes.php?ok=rechazado"
    );

    exit;


} catch (Throwable $e) {


    if (

        isset($db) &&

        $db->inTransaction()

    ) {

        $db->rollBack();

    }


    header(

        "Location: comprobantes.php?error=" .

        urlencode(

            $e->getMessage()

        )

    );

    exit;

}