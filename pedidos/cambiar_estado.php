<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

requireAdmin();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login/index.php");
    exit;
}

$pdo = Conexion::conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    die("Pedido inválido.");
}


/*
|--------------------------------------------------------------------------
| OBTENER PEDIDO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.cliente_id,
        p.estado,
        p.total,
        p.metodo_pago,
        CONCAT(c.apellido, ' ', c.nombre) AS cliente
    FROM pedidos p
    INNER JOIN clientes c
        ON c.id = p.cliente_id
    WHERE p.id = ?
");

$stmt->execute([$id]);

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("Pedido no encontrado.");
}


/*
|--------------------------------------------------------------------------
| NORMALIZAR ESTADO
|--------------------------------------------------------------------------
*/

$estadoActual = strtoupper(
    trim((string)$pedido["estado"])
);


/*
|--------------------------------------------------------------------------
| BUSCAR ÚLTIMO COMPROBANTE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        estado
    FROM comprobantes_pedidos
    WHERE pedido_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$stmt->execute([$id]);

$comprobante = $stmt->fetch(PDO::FETCH_ASSOC);

$estadoComprobante = strtoupper(
    trim((string)($comprobante["estado"] ?? ""))
);


/*
|--------------------------------------------------------------------------
| PEDIDOS BLOQUEADOS
|--------------------------------------------------------------------------
|
| CANCELADO
| PAGO RECHAZADO
| COMPROBANTE RECHAZADO
|
*/

$pedidoBloqueado = (
    $estadoActual === "CANCELADO" ||
    $estadoActual === "PAGO RECHAZADO" ||
    $estadoComprobante === "RECHAZADO"
);

if ($pedidoBloqueado) {

    $mensajeBloqueo = "El pedido está bloqueado y no se puede modificar.";

    if ($estadoActual === "PAGO RECHAZADO") {
        $mensajeBloqueo = "El pago de este pedido fue rechazado.";
    } elseif ($estadoComprobante === "RECHAZADO") {
        $mensajeBloqueo = "El comprobante de pago fue rechazado.";
    } elseif ($estadoActual === "CANCELADO") {
        $mensajeBloqueo = "El pedido fue cancelado.";
    }

    die(
        "<!DOCTYPE html>
        <html lang='es'>
        <head>

            <meta charset='UTF-8'>

            <meta
                name='viewport'
                content='width=device-width, initial-scale=1'
            >

            <title>Pedido bloqueado</title>

            <link
                href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
                rel='stylesheet'
            >

            <link
                rel='stylesheet'
                href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'
            >

        </head>

        <body class='bg-light'>

            <div class='container py-5'>

                <div class='row justify-content-center'>

                    <div class='col-md-7'>

                        <div class='card shadow-sm border-danger'>

                            <div class='card-header bg-danger text-white'>

                                <h4 class='mb-0'>
                                    <i class='bi bi-lock-fill'></i>
                                    Pedido bloqueado
                                </h4>

                            </div>

                            <div class='card-body text-center py-5'>

                                <div class='mb-3'>

                                    <i
                                        class='bi bi-x-circle-fill text-danger'
                                        style='font-size:60px;'
                                    ></i>

                                </div>

                                <h4 class='text-danger'>
                                    Pedido bloqueado
                                </h4>

                                <p class='text-muted mb-4'>
                                    "
        . htmlspecialchars(
            $mensajeBloqueo,
            ENT_QUOTES,
            "UTF-8"
        )
        . "
                                </p>

                                <div class='mb-4'>

                                    <strong>
                                        Pedido #"
        . (int)$pedido["id"]
        . "
                                    </strong>

                                </div>

                                <a
                                    href='ver.php?id="
        . (int)$pedido["id"]
        . "'
                                    class='btn btn-secondary'
                                >

                                    <i class='bi bi-arrow-left'></i>

                                    Volver al pedido

                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </body>
        </html>"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| PROCESAR CAMBIO DE ESTADO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nuevoEstado = trim(
        $_POST["estado"] ?? ""
    );

    $estadosValidos = [
        "Pendiente",
        "Pagado",
        "Preparando",
        "Enviado",
        "Entregado",
        "Cancelado"
    ];

    if (!in_array(
        $nuevoEstado,
        $estadosValidos,
        true
    )) {
        die("Estado inválido.");
    }


    try {

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | BLOQUEAR PEDIDO
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                cliente_id,
                total,
                estado,
                metodo_pago
            FROM pedidos
            WHERE id = ?
            FOR UPDATE
        ");

        $stmt->execute([$id]);

        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            throw new Exception(
                "El pedido no existe."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ESTADO ACTUAL
        |--------------------------------------------------------------------------
        */

        $estadoActual = strtoupper(
            trim((string)$pedido["estado"])
        );


        /*
        |--------------------------------------------------------------------------
        | COMPROBANTE ACTUAL
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                estado
            FROM comprobantes_pedidos
            WHERE pedido_id = ?
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->execute([$id]);

        $comprobante = $stmt->fetch(PDO::FETCH_ASSOC);

        $estadoComprobante = strtoupper(
            trim((string)($comprobante["estado"] ?? ""))
        );


        /*
        |--------------------------------------------------------------------------
        | BLOQUEAR CANCELADO
        |--------------------------------------------------------------------------
        */

        if ($estadoActual === "CANCELADO") {

            throw new Exception(
                "El pedido está cancelado y no se puede modificar."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | BLOQUEAR PAGO RECHAZADO
        |--------------------------------------------------------------------------
        */

        if ($estadoActual === "PAGO RECHAZADO") {

            throw new Exception(
                "El pago de este pedido fue rechazado. " .
                "El pedido está bloqueado."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | BLOQUEAR COMPROBANTE RECHAZADO
        |--------------------------------------------------------------------------
        */

        if ($estadoComprobante === "RECHAZADO") {

            throw new Exception(
                "El comprobante de pago fue rechazado. " .
                "El pedido está bloqueado."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PEDIDO ENTREGADO
        |--------------------------------------------------------------------------
        |
        | Un pedido entregado queda cerrado.
        |
        */

        if (
            $estadoActual === "ENTREGADO" &&
            $nuevoEstado !== "Entregado"
        ) {

            throw new Exception(
                "El pedido ya fue entregado y no se puede modificar."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SI YA ESTÁ PAGADO
        |--------------------------------------------------------------------------
        |
        | No volver a crear la venta.
        |
        */

        if (
            $estadoActual === "PAGADO" &&
            $nuevoEstado === "Pagado"
        ) {

            $pdo->commit();

            header(
                "Location: ver.php?id=" . $id
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | NO PERMITIR VOLVER ATRÁS DESDE PAGADO
        |--------------------------------------------------------------------------
        */

        if ($estadoActual === "PAGADO") {

            $estadosPosteriores = [
                "Preparando",
                "Enviado",
                "Entregado"
            ];

            if (
                !in_array(
                    $nuevoEstado,
                    $estadosPosteriores,
                    true
                )
            ) {

                throw new Exception(
                    "Un pedido pagado solamente puede avanzar a " .
                    "Preparando, Enviado o Entregado."
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | NO PERMITIR VOLVER ATRÁS DESDE PREPARANDO
        |--------------------------------------------------------------------------
        */

        if ($estadoActual === "PREPARANDO") {

            $estadosPermitidos = [
                "Preparando",
                "Enviado",
                "Entregado"
            ];

            if (
                !in_array(
                    $nuevoEstado,
                    $estadosPermitidos,
                    true
                )
            ) {

                throw new Exception(
                    "Un pedido en preparación solamente puede " .
                    "avanzar a Enviado o Entregado."
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | NO PERMITIR VOLVER ATRÁS DESDE ENVIADO
        |--------------------------------------------------------------------------
        */

        if ($estadoActual === "ENVIADO") {

            $estadosPermitidos = [
                "Enviado",
                "Entregado"
            ];

            if (
                !in_array(
                    $nuevoEstado,
                    $estadosPermitidos,
                    true
                )
            ) {

                throw new Exception(
                    "Un pedido enviado solamente puede pasar a Entregado."
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CUANDO PASA A PAGADO
        |--------------------------------------------------------------------------
        */

        if (
            $nuevoEstado === "Pagado" &&
            $estadoActual !== "PAGADO"
        ) {


            /*
            |--------------------------------------------------------------------------
            | VALIDAR MÉTODO DE PAGO
            |--------------------------------------------------------------------------
            */

            $metodoPago = strtoupper(
                trim((string)$pedido["metodo_pago"])
            );

            if ($metodoPago !== "TRANSFERENCIA") {

                throw new Exception(
                    "Los pedidos web solamente pueden pagarse " .
                    "mediante transferencia bancaria."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDAR COMPROBANTE APROBADO
            |--------------------------------------------------------------------------
            */

            if (!$comprobante) {

                throw new Exception(
                    "El pedido no tiene comprobante de pago."
                );
            }

            if ($estadoComprobante !== "APROBADO") {

                throw new Exception(
                    "El comprobante de pago todavía no fue aprobado."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | BUSCAR DETALLE
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    pd.producto_id,
                    pd.cantidad,
                    pd.precio,
                    pd.subtotal,
                    p.nombre,
                    p.stock
                FROM pedido_detalle pd
                INNER JOIN productos p
                    ON p.id = pd.producto_id
                WHERE pd.pedido_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$id]);

            $detalles = $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

            if (!$detalles) {

                throw new Exception(
                    "El pedido no tiene productos."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | VERIFICAR STOCK
            |--------------------------------------------------------------------------
            */

            foreach ($detalles as $detalle) {

                $cantidad = (float)$detalle["cantidad"];
                $stock = (float)$detalle["stock"];

                if ($cantidad <= 0) {

                    throw new Exception(
                        "Cantidad inválida para el producto: " .
                        $detalle["nombre"]
                    );
                }

                if ($stock < $cantidad) {

                    throw new Exception(
                        "Stock insuficiente para: " .
                        $detalle["nombre"] .
                        ". Stock disponible: " .
                        $stock .
                        ". Cantidad solicitada: " .
                        $cantidad
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | CREAR VENTA
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO ventas
                (
                    fecha,
                    usuario_id,
                    caja_id,
                    cliente_id,
                    total,
                    forma_pago,
                    estado
                )
                VALUES
                (
                    NOW(),
                    ?,
                    NULL,
                    ?,
                    ?,
                    'TRANSFERENCIA',
                    'PAGADA'
                )
            ");

            $stmt->execute([
                $_SESSION["usuario_id"],
                $pedido["cliente_id"],
                $pedido["total"]
            ]);

            $ventaId = (int)$pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | PREPARAR DETALLE
            |--------------------------------------------------------------------------
            */

            $stmtDetalle = $pdo->prepare("
                INSERT INTO detalle_ventas
                (
                    venta_id,
                    producto_id,
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
                    ?
                )
            ");


            /*
            |--------------------------------------------------------------------------
            | PREPARAR STOCK
            |--------------------------------------------------------------------------
            */

            $stmtStock = $pdo->prepare("
                UPDATE productos
                SET stock = stock - ?
                WHERE id = ?
                  AND stock >= ?
            ");


            /*
            |--------------------------------------------------------------------------
            | PREPARAR MOVIMIENTO
            |--------------------------------------------------------------------------
            */

            $stmtMovimiento = $pdo->prepare("
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
            ");


            /*
            |--------------------------------------------------------------------------
            | PROCESAR PRODUCTOS
            |--------------------------------------------------------------------------
            */

            foreach ($detalles as $detalle) {

                $productoId = (int)$detalle["producto_id"];

                $cantidad = (float)$detalle["cantidad"];

                $precio = (float)$detalle["precio"];

                $subtotal = (float)$detalle["subtotal"];

                $descuento = 0;


                /*
                |--------------------------------------------------------------------------
                | DETALLE DE VENTA
                |--------------------------------------------------------------------------
                */

                $stmtDetalle->execute([
                    $ventaId,
                    $productoId,
                    $cantidad,
                    $precio,
                    $descuento,
                    $subtotal
                ]);


                /*
                |--------------------------------------------------------------------------
                | DESCONTAR STOCK
                |--------------------------------------------------------------------------
                */

                $stmtStock->execute([
                    $cantidad,
                    $productoId,
                    $cantidad
                ]);

                if ($stmtStock->rowCount() !== 1) {

                    throw new Exception(
                        "No se pudo descontar el stock del producto: " .
                        $detalle["nombre"]
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | MOVIMIENTO DE STOCK
                |--------------------------------------------------------------------------
                */

                $stmtMovimiento->execute([
                    $productoId,
                    $cantidad,
                    "Venta web - Pedido #" . $id .
                    " - Transferencia bancaria",
                    $_SESSION["usuario_id"]
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | IMPORTANTE:
            |--------------------------------------------------------------------------
            |
            | Venta web por transferencia:
            |
            | caja_id = NULL
            | ingresos de caja = NO SE MODIFICAN
            | ventas de caja = NO SE MODIFICAN
            |
            |--------------------------------------------------------------------------
            */
        }


        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR ESTADO DEL PEDIDO
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE pedidos
            SET estado = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $nuevoEstado,
            $id
        ]);


        /*
        |--------------------------------------------------------------------------
        | CONFIRMAR
        |--------------------------------------------------------------------------
        */

        $pdo->commit();

        header(
            "Location: ver.php?id=" . $id
        );

        exit;


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die(
            "<!DOCTYPE html>

            <html lang='es'>

            <head>

                <meta charset='UTF-8'>

                <meta
                    name='viewport'
                    content='width=device-width, initial-scale=1'
                >

                <title>Error</title>

                <link
                    href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
                    rel='stylesheet'
                >

                <link
                    rel='stylesheet'
                    href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'
                >

            </head>

            <body class='bg-light'>

                <div class='container py-5'>

                    <div class='row justify-content-center'>

                        <div class='col-md-7'>

                            <div class='card shadow-sm border-danger'>

                                <div class='card-header bg-danger text-white'>

                                    <h4 class='mb-0'>

                                        <i class='bi bi-exclamation-triangle-fill'></i>

                                        No se pudo cambiar el estado

                                    </h4>

                                </div>

                                <div class='card-body'>

                                    <div class='alert alert-danger'>

                                        "
            . htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES,
                "UTF-8"
            )
            . "

                                    </div>

                                    <a
                                        href='ver.php?id="
            . (int)$id
            . "'
                                        class='btn btn-secondary'
                                    >

                                        <i class='bi bi-arrow-left'></i>

                                        Volver al pedido

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </body>

            </html>"
        );
    }
}


/*
|--------------------------------------------------------------------------
| ESTADOS DISPONIBLES
|--------------------------------------------------------------------------
*/

$estados = [
    "Pendiente",
    "Pagado",
    "Preparando",
    "Enviado",
    "Entregado",
    "Cancelado"
];

?>
<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Cambiar estado - Pedido #<?= (int)$pedido["id"] ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

</head>

<body class="bg-light">

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card shadow-sm">

                <div class="card-header">

                    <h4 class="mb-0">

                        <i class="bi bi-arrow-repeat"></i>

                        Cambiar estado

                    </h4>

                </div>

                <div class="card-body">

                    <div class="mb-3">

                        <strong>Pedido:</strong>

                        #<?= (int)$pedido["id"] ?>

                    </div>


                    <div class="mb-3">

                        <strong>Cliente:</strong>

                        <?= htmlspecialchars(
                            $pedido["cliente"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>


                    <div class="mb-3">

                        <strong>Total:</strong>

                        $

                        <?= number_format(
                            (float)$pedido["total"],
                            2,
                            ",",
                            "."
                        ) ?>

                    </div>


                    <div class="mb-3">

                        <strong>Método de pago:</strong>

                        <span class="badge bg-info text-dark">

                            <?= htmlspecialchars(
                                $pedido["metodo_pago"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </span>

                    </div>


                    <div class="mb-4">

                        <strong>Estado actual:</strong>

                        <span class="badge bg-primary">

                            <?= htmlspecialchars(
                                $pedido["estado"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </span>

                    </div>


                    <?php if ($comprobante): ?>

                        <div class="mb-4">

                            <strong>Comprobante:</strong>

                            <?php if ($estadoComprobante === "APROBADO"): ?>

                                <span class="badge bg-success">

                                    <i class="bi bi-check-circle"></i>

                                    Aprobado

                                </span>

                            <?php elseif ($estadoComprobante === "PENDIENTE"): ?>

                                <span class="badge bg-warning text-dark">

                                    <i class="bi bi-clock"></i>

                                    Pendiente

                                </span>

                            <?php elseif ($estadoComprobante === "RECHAZADO"): ?>

                                <span class="badge bg-danger">

                                    <i class="bi bi-x-circle"></i>

                                    Rechazado

                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">

                                    <?= htmlspecialchars(
                                        $comprobante["estado"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">

                                Nuevo estado

                            </label>

                            <select
                                name="estado"
                                class="form-select"
                                required
                            >

                                <?php foreach ($estados as $estado): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $estado,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        <?= $pedido["estado"] === $estado
                                            ? "selected"
                                            : "" ?>
                                    >

                                        <?= htmlspecialchars(
                                            $estado,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="alert alert-info">

                            <i class="bi bi-info-circle"></i>

                            Para marcar un pedido como
                            <strong>Pagado</strong>,
                            primero debe existir un comprobante
                            de transferencia aprobado.

                        </div>


                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-check-lg"></i>

                                Guardar estado

                            </button>


                            <a
                                href="ver.php?id=<?= (int)$pedido["id"] ?>"
                                class="btn btn-secondary"
                            >

                                <i class="bi bi-arrow-left"></i>

                                Cancelar

                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>