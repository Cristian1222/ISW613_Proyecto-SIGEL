<?php

declare(strict_types=1);

session_start();

// Cargar archivos auxiliares.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo usuarios autenticados pueden entrar.
requerirAutenticacion();

// Obtener información del usuario.
$usuario = obtenerUsuario();
$esAdmin = tieneRol(['admin']);

// Generar token CSRF para cerrar sesión.
$tokenCSRF = generarTokenCSRF();

// Título de la página.
$titulo = 'Panel principal | SIGEL';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h1>Panel principal de SIGEL</h1>

            <p class="text-muted mb-0">
                Bienvenido/a,
                <?= escapar($usuario['nombre'] ?? '') ?>
            </p>
        </div>

        <!-- Formulario seguro para cerrar sesión -->
        <form action="../actions/logout.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapar($tokenCSRF) ?>"
            >

            <button type="submit" class="btn btn-outline-danger">
                Cerrar sesión
            </button>

        </form>

    </div>

    <div class="row g-4">

        <!-- Tarjeta de equipos -->
        <div class="col-md-4">

            <div class="card h-100 shadow-sm">

                <div class="card-body">

                    <h5 class="card-title">Equipos</h5>

                    <p class="card-text">
                        Consulta y administra los equipos registrados.
                    </p>

                    <a href="equipos.php" class="btn btn-primary mt-3">
                        Administrar equipos
                    </a>

                </div>

            </div>

        </div>

        <!-- Tarjeta de préstamos -->
        <div class="col-md-4">

            <div class="card h-100 shadow-sm">

                <div class="card-body">

                    <h5 class="card-title">Préstamos</h5>

                    <p class="card-text">
                        Consulta los préstamos y gestiona las devoluciones.
                    </p>

                    <a href="prestamos.php" class="btn btn-primary mt-3">
                        Gestionar préstamos
                    </a>

                </div>

            </div>

        </div>

        <!-- Solo el administrador puede gestionar usuarios -->
        <?php if ($esAdmin): ?>

            <div class="col-md-4">

                <div class="card h-100 shadow-sm">

                    <div class="card-body">

                        <h5 class="card-title">Usuarios</h5>

                        <p class="card-text">
                            Crea cuentas de profesores y operadores,
                            consulta usuarios y administra sus roles.
                        </p>

                        <a href="usuarios.php" class="btn btn-primary mt-3">
                            Gestionar usuarios
                        </a>

                    </div>

                </div>

            </div>

        <?php endif; ?>

    </div>

    <!-- Información del rol -->
    <div class="alert alert-info mt-4">

        <strong>Tu rol:</strong>
        <?= escapar($usuario['rol'] ?? '') ?>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>