<?php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$usuarioId = (int)($_SESSION["usuario_id"] ?? 0);

if ($usuarioId <= 0) {
    header("Location: ../login.php");
    exit;
}


/*
==================================================
VERIFICAR SI EL USUARIO YA TIENE UNA CAJA ABIERTA
==================================================
*/

$stmt = $conexion->prepare("
    SELECT id
    FROM cajas
    WHERE usuario_id = ?
      AND estado = 'ABIERTA'
    LIMIT 1
");

$stmt->execute([$usuarioId]);

$cajaAbierta = $stmt->fetch(PDO::FETCH_ASSOC);


/*
==================================================
PROCESAR APERTURA
==================================================
*/

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $saldoInicial = isset($_POST["saldo_inicial"])
        ? (float)str_replace(",", ".", $_POST["saldo_inicial"])
        : 0;


    /*
    VALIDAR CAJA ABIERTA
    */

    if ($cajaAbierta) {

        $error = "Ya tenés una caja abierta.";

    }


    /*
    VALIDAR SALDO
    */

    elseif ($saldoInicial < 0) {

        $error = "El saldo inicial no puede ser negativo.";

    }


    /*
    CREAR CAJA
    */

    else {

        try {

            $conexion->beginTransaction();


            /*
            VOLVER A VERIFICAR DENTRO DE LA TRANSACCION
            */

            $stmt = $conexion->prepare("
                SELECT id
                FROM cajas
                WHERE usuario_id = ?
                  AND estado = 'ABIERTA'
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->execute([$usuarioId]);

            $cajaExistente = $stmt->fetch(PDO::FETCH_ASSOC);


            if ($cajaExistente) {

                throw new Exception(
                    "Ya tenés una caja abierta."
                );

            }


            /*
            INSERTAR CAJA
            */

            $stmt = $conexion->prepare("
                INSERT INTO cajas
                (
                    fecha_apertura,
                    usuario_id,
                    saldo_inicial,
                    ingresos,
                    egresos,
                    ventas,
                    saldo_final,
                    saldo_real,
                    diferencia,
                    estado
                )
                VALUES
                (
                    NOW(),
                    ?,
                    ?,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    'ABIERTA'
                )
            ");

            $stmt->execute([
                $usuarioId,
                $saldoInicial
            ]);


            $conexion->commit();


            /*
            VOLVER AL LISTADO
            */

            header(
                "Location: index.php?ok=abierta"
            );

            exit;

        } catch (Throwable $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            $error = $e->getMessage();

        }

    }

}

?>

<!doctype html>

<html lang="es">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Abrir Caja - Stock PRO</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="../assets/css/estilos.css"
    >

</head>


<body>


<?php include "../includes/sidebar.php"; ?>


<div class="content">

    <div class="container-fluid">


        <!-- ENCABEZADO -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h3 class="mb-0">

                <i class="bi bi-cash-stack"></i>

                Abrir Caja

            </h3>


            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >

                <i class="bi bi-arrow-left"></i>

                Volver

            </a>

        </div>


        <div class="row justify-content-center">

            <div class="col-md-7 col-lg-5">


                <div class="card shadow border-0">


                    <!-- HEADER -->

                    <div class="card-header bg-success text-white">

                        <h5 class="mb-0">

                            <i class="bi bi-unlock"></i>

                            Apertura de Caja

                        </h5>

                    </div>


                    <!-- BODY -->

                    <div class="card-body p-4">


                        <?php if ($error): ?>

                            <div class="alert alert-danger">

                                <i class="bi bi-exclamation-triangle"></i>

                                <?= htmlspecialchars($error) ?>

                            </div>

                        <?php endif; ?>


                        <?php if ($cajaAbierta): ?>


                            <div class="alert alert-warning">

                                <i class="bi bi-exclamation-circle"></i>

                                Ya tenés una caja abierta.

                            </div>


                            <div class="d-grid">

                                <a
                                    href="detalle.php?id=<?= (int)$cajaAbierta["id"] ?>"
                                    class="btn btn-primary"
                                >

                                    <i class="bi bi-eye"></i>

                                    Ver Caja Abierta

                                </a>

                            </div>


                        <?php else: ?>


                            <form
                                method="POST"
                                id="formAbrirCaja"
                            >


                                <!-- SALDO INICIAL -->

                                <div class="mb-4">

                                    <label
                                        for="saldo_inicial"
                                        class="form-label fw-bold"
                                    >

                                        Saldo inicial

                                    </label>


                                    <div class="input-group input-group-lg">

                                        <span class="input-group-text">

                                            $

                                        </span>


                                        <input
                                            type="number"
                                            name="saldo_inicial"
                                            id="saldo_inicial"
                                            class="form-control"
                                            min="0"
                                            step="0.01"
                                            value="0.00"
                                            required
                                            autofocus
                                        >

                                    </div>


                                    <div class="form-text">

                                        Ingresá el dinero en efectivo
                                        disponible al comenzar la jornada.

                                    </div>

                                </div>


                                <!-- INFORMACION -->

                                <div class="alert alert-info">

                                    <div class="d-flex align-items-start gap-2">

                                        <i class="bi bi-info-circle fs-4"></i>

                                        <div>

                                            <strong>
                                                Importante
                                            </strong>

                                            <div class="mt-1">

                                                Una vez abierta la caja,
                                                las ventas presenciales
                                                podrán registrarse en ella.

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <!-- BOTON -->

                                <div class="d-grid">

                                    <button
                                        type="submit"
                                        class="btn btn-success btn-lg"
                                        id="btnAbrir"
                                    >

                                        <i class="bi bi-unlock"></i>

                                        Abrir Caja

                                    </button>

                                </div>


                            </form>


                        <?php endif; ?>


                    </div>

                </div>


            </div>

        </div>


    </div>

</div>


<script>

document.getElementById("formAbrirCaja")?.addEventListener(
    "submit",
    function () {

        const boton = document.getElementById("btnAbrir");

        if (boton) {

            boton.disabled = true;

            boton.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Abriendo caja...
            `;

        }

    }
);

</script>


</body>

</html>