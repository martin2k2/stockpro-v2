<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| TIEMPO MÁXIMO DE INACTIVIDAD
|--------------------------------------------------------------------------
*/

$tiempo_maximo_inactividad = 1800; // 30 minutos


if (isset($_SESSION["ultima_actividad"])) {

    $tiempo_inactivo =
        time() - (int)$_SESSION["ultima_actividad"];

    if ($tiempo_inactivo >= $tiempo_maximo_inactividad) {

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header(
            "Location: /stockpro-v2/login/index.php?sesion=expirada"
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| ACTUALIZAR ACTIVIDAD
|--------------------------------------------------------------------------
*/

$_SESSION["ultima_actividad"] = time();


/*
|--------------------------------------------------------------------------
| VERIFICAR LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["usuario_id"])) {

    header(
        "Location: /stockpro-v2/login/index.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER ROL
|--------------------------------------------------------------------------
*/

$rolUsuario = strtoupper(
    trim((string)($_SESSION["rol"] ?? ""))
);


/*
|--------------------------------------------------------------------------
| FUNCIÓN: REQUERIR ROL
|--------------------------------------------------------------------------
*/

function requireRole(...$rolesPermitidos)
{
    global $rolUsuario;

    $rolesPermitidos = array_map(
        function ($rol) {
            return strtoupper(trim((string)$rol));
        },
        $rolesPermitidos
    );

    if (
        !in_array(
            $rolUsuario,
            $rolesPermitidos,
            true
        )
    ) {

        http_response_code(403);

        die(
            '<!DOCTYPE html>
            <html lang="es">
            <head>

                <meta charset="UTF-8">

                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1"
                >

                <title>Acceso denegado</title>

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

                <div class="container py-5">

                    <div class="row justify-content-center">

                        <div class="col-md-6">

                            <div class="card shadow-sm border-danger">

                                <div class="card-header bg-danger text-white">

                                    <h4 class="mb-0">

                                        <i class="bi bi-shield-lock-fill"></i>

                                        Acceso denegado

                                    </h4>

                                </div>

                                <div class="card-body text-center py-5">

                                    <i
                                        class="bi bi-person-lock text-danger"
                                        style="font-size:65px;"
                                    ></i>

                                    <h4 class="mt-3">

                                        No tiene permisos

                                    </h4>

                                    <p class="text-muted">

                                        Su usuario no tiene autorización
                                        para acceder a esta sección.

                                    </p>

                                    <a
                                        href="/stockpro-v2/dashboard/index.php"
                                        class="btn btn-primary"
                                    >

                                        <i class="bi bi-house"></i>

                                        Volver al inicio

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </body>
            </html>'
        );
    }
}


/*
|--------------------------------------------------------------------------
| FUNCIÓN: SOLO ADMINISTRADOR
|--------------------------------------------------------------------------
*/

function requireAdmin()
{
    requireRole("ADMIN");
}


/*
|--------------------------------------------------------------------------
| FUNCIÓN: ADMINISTRADOR + OPERADOR
|--------------------------------------------------------------------------
*/

function requireStaff()
{
    requireRole(
        "ADMIN",
        "OPERADOR"
    );
}