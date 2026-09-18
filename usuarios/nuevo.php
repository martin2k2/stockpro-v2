<?php
require_once "../includes/auth.php";
requireAdmin();
require_once "../includes/header.php";
//require_once "../includes/navbar.php";
require_once "../includes/sidebar.php";
?>

<?php if(isset($_GET["error"]) && $_GET["error"]=="existe"): ?>

<div class="alert alert-danger">
    Ya existe un usuario con ese nombre de usuario.
</div>

<?php endif; ?>


<div class="main">

<?php require_once "../includes/navbar.php"; ?>
<div class="container-fluid">
<br><br>
    <div class="card shadow">

        <div class="card-header bg-success text-white">

            <h3>
                <i class="bi bi-person-plus"></i>
                Nuevo Usuario
            </h3>

        </div>

        <div class="card-body">

            <form action="guardar.php" method="POST">

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">Nombre</label>

                        <input
                            type="text"
                            name="nombre"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">Usuario</label>

                        <input
                            type="text"
                            name="usuario"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">Contraseña</label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">Rol</label>

                        <select
                            name="rol"
                            class="form-select"
                            required>

                            <option value="ADMIN">Administrador</option>
                            <option value="OPERADOR">Operador</option>

                        </select>

                    </div>

                </div>

                <button class="btn btn-success">
                    <i class="bi bi-save"></i>
                    Guardar
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Cancelar
                </a>

            </form>

        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>