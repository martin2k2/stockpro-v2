<?php

// clientes/editar.php

session_start();

require_once "../config/conexion.php";
require_once "../includes/auth.php";

$conexion = (new Conexion())->conectar();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/* ==========================
   BUSCAR CLIENTE
========================== */

$stmt = $conexion->prepare("
    SELECT
        id,
        dni,
        apellido,
        nombre,
        telefono,
        direccion,
        email,
        password,
        observaciones,
        estado
    FROM clientes
    WHERE id = ?
");

$stmt->execute([$id]);

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {

    header("Location: index.php");
    exit;

}


/* ==========================
   ACTUALIZAR
========================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $dni           = trim($_POST["dni"] ?? "");
    $apellido      = trim($_POST["apellido"] ?? "");
    $nombre        = trim($_POST["nombre"] ?? "");
    $telefono      = trim($_POST["telefono"] ?? "");
    $direccion     = trim($_POST["direccion"] ?? "");
    $email         = trim($_POST["email"] ?? "");
    $password      = trim($_POST["password"] ?? "");
    $observaciones = trim($_POST["observaciones"] ?? "");
    $estado        = isset($_POST["estado"]) ? 1 : 0;


    if ($apellido == "") {

        $error = "El apellido es obligatorio.";

    } elseif ($nombre == "") {

        $error = "El nombre es obligatorio.";

    } else {

        try {

            /*
             * Verificar DNI duplicado
             */

            $stmt = $conexion->prepare("
                SELECT id
                FROM clientes
                WHERE dni = ?
                AND id <> ?
                LIMIT 1
            ");

            $stmt->execute([
                $dni,
                $id
            ]);

            if ($stmt->fetch()) {

                $error = "Ya existe otro cliente con ese DNI.";

            } else {

                /*
                 * Si escribió una contraseña nueva,
                 * se actualiza.
                 *
                 * Si queda vacía,
                 * se conserva la actual.
                 */

                if ($password != "") {

                    $passwordHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $conexion->prepare("
                        UPDATE clientes
                        SET
                            dni = ?,
                            apellido = ?,
                            nombre = ?,
                            telefono = ?,
                            direccion = ?,
                            email = ?,
                            password = ?,
                            observaciones = ?,
                            estado = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([

                        $dni,
                        $apellido,
                        $nombre,
                        $telefono,
                        $direccion,
                        $email,
                        $passwordHash,
                        $observaciones,
                        $estado,
                        $id

                    ]);

                } else {

                    $stmt = $conexion->prepare("
                        UPDATE clientes
                        SET
                            dni = ?,
                            apellido = ?,
                            nombre = ?,
                            telefono = ?,
                            direccion = ?,
                            email = ?,
                            observaciones = ?,
                            estado = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([

                        $dni,
                        $apellido,
                        $nombre,
                        $telefono,
                        $direccion,
                        $email,
                        $observaciones,
                        $estado,
                        $id

                    ]);

                }

                header("Location: index.php?editado=1");
                exit;

            }

        } catch (PDOException $e) {

            $error = "Error al actualizar cliente: "
                   . $e->getMessage();

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
content="width=device-width, initial-scale=1">

<title>Editar Cliente</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<link
rel="stylesheet"
href="../assets/css/estilos.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main">

<div class="content">

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-warning">

<h4 class="mb-0">

<i class="bi bi-pencil-square"></i>

Editar Cliente

</h4>

</div>

<div class="card-body">


<?php if (isset($error)): ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<form method="post">


<div class="row">


<div class="col-md-4 mb-3">

<label class="form-label">

DNI

</label>

<input
type="text"
name="dni"
class="form-control"
value="<?= htmlspecialchars($cliente["dni"]) ?>">

</div>


<div class="col-md-4 mb-3">

<label class="form-label">

Apellido

</label>

<input
type="text"
name="apellido"
class="form-control"
value="<?= htmlspecialchars($cliente["apellido"]) ?>"
required>

</div>


<div class="col-md-4 mb-3">

<label class="form-label">

Nombre

</label>

<input
type="text"
name="nombre"
class="form-control"
value="<?= htmlspecialchars($cliente["nombre"]) ?>"
required>

</div>


<div class="col-md-6 mb-3">

<label class="form-label">

Teléfono

</label>

<input
type="text"
name="telefono"
class="form-control"
value="<?= htmlspecialchars($cliente["telefono"] ?? "") ?>">

</div>


<div class="col-md-6 mb-3">

<label class="form-label">

Email

</label>

<input
type="email"
name="email"
class="form-control"
value="<?= htmlspecialchars($cliente["email"] ?? "") ?>">

</div>


<div class="col-md-12 mb-3">

<label class="form-label">

Dirección

</label>

<input
type="text"
name="direccion"
class="form-control"
value="<?= htmlspecialchars($cliente["direccion"] ?? "") ?>">

</div>


<div class="col-md-6 mb-3">

<label class="form-label">

Nueva contraseña

</label>

<input
type="password"
name="password"
class="form-control">

<small class="text-muted">

Dejar vacío para conservar la contraseña actual.

</small>

</div>


<div class="col-md-6 mb-3">

<label class="form-label">

Estado

</label>

<div class="form-check mt-2">

<input
class="form-check-input"
type="checkbox"
name="estado"
id="estado"
<?= $cliente["estado"] == 1 ? "checked" : "" ?>>

<label
class="form-check-label"
for="estado">

Cliente Activo

</label>

</div>

</div>


<div class="col-md-12 mb-3">

<label class="form-label">

Observaciones

</label>

<textarea
name="observaciones"
class="form-control"
rows="4"><?= htmlspecialchars($cliente["observaciones"] ?? "") ?></textarea>

</div>


</div>


<div class="d-flex justify-content-between">

<a
href="index.php"
class="btn btn-secondary">

<i class="bi bi-arrow-left"></i>

Volver

</a>

<button
type="submit"
class="btn btn-success">

<i class="bi bi-check-circle"></i>

Guardar Cambios

</button>

</div>


</form>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

</body>

</html>