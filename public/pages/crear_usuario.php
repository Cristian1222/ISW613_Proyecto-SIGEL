<?php

declare(strict_types=1);

session_start();

// Cargar archivos auxiliares.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo los administradores pueden crear usuarios.
requerirRol(['admin']);

// Generar token CSRF.
$tokenCSRF = generarTokenCSRF();

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

$titulo = 'Crear usuario | SIGEL';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/alerts.php';

$errores = [];
$nombre = '';
$correo = '';
$rol = 'profesor';

?>

<div class="container py-4">

    <?php renderAlert(); ?>

    <div class="mb-4">
        <h1 class="h3">Crear usuario</h1>

        <p class="text-muted">
            Registra profesores y operadores en SIGEL.
        </p>
    </div>

    <?php foreach ($errores as $error): ?>

        <div class="alert alert-danger">
            <?= escapar($error) ?>
        </div>

    <?php endforeach; ?>

    <div class="card shadow-sm">

        <div class="card-body p-4">

            <form action="../actions/crear_usuario.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($tokenCSRF) ?>"
                >

                <div class="mb-3">

                    <label for="nombre" class="form-label">
                        Nombre completo
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        autocomplete="name"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label for="correo" class="form-label">
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        id="correo"
                        name="correo"
                        maxlength="150"
                        autocomplete="email"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label for="password" class="form-label">
                        Contraseña inicial
                    </label>

                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <div class="form-text">
                        Utiliza una contraseña de al menos 8 caracteres.
                    </div>

                </div>

                <div class="mb-4">

                    <label for="rol" class="form-label">
                        Rol del usuario
                    </label>

                    <select
                        class="form-select"
                        id="rol"
                        name="rol"
                        required
                    >

                        <option value="profesor">
                            Profesor
                        </option>

                        <option value="operador">
                            Operador
                        </option>

                    </select>

                    <div class="form-text">
                        Desde este formulario solo se pueden crear profesores y operadores.
                    </div>

                </div>

                <div class="d-flex flex-wrap gap-2">

                    <button type="submit" class="btn btn-primary">
                        Crear usuario
                    </button>

                    <a
                        href="usuarios.php"
                        class="btn btn-outline-secondary"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>