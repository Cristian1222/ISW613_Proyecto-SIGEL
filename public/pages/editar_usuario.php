<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// Solo el administrador puede editar usuarios.
requerirRol(['admin']);

// Obtener y validar el ID del usuario.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    redirigir('usuarios.php?error=invalid_input');
}

// Buscar el usuario.
$consulta = $pdo->prepare(
    'SELECT id, nombre, correo, rol, activo
     FROM usuarios
     WHERE id = :id
     LIMIT 1'
);

$consulta->execute(['id' => $id]);

$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    redirigir('usuarios.php?error=invalid_input');
}

// Generar token CSRF.
$tokenCSRF = generarTokenCSRF();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Editar usuario</h2>

        <a href="usuarios.php" class="btn btn-secondary">
            Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="../actions/editar_usuario.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($tokenCSRF) ?>"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $usuario['id'] ?>"
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
                        value="<?= escapar($usuario['nombre']) ?>"
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
                        value="<?= escapar($usuario['correo']) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="rol" class="form-label">
                        Rol del usuario
                    </label>

                    <select
                        class="form-select"
                        id="rol"
                        name="rol"
                        required
                    >
                        <option
                            value="admin"
                            <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>
                        >
                            Administrador
                        </option>

                        <option
                            value="operador"
                            <?= $usuario['rol'] === 'operador' ? 'selected' : '' ?>
                        >
                            Operador
                        </option>

                        <option
                            value="profesor"
                            <?= $usuario['rol'] === 'profesor' ? 'selected' : '' ?>
                        >
                            Profesor
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="activo" class="form-label">
                        Estado de la cuenta
                    </label>

                    <select
                        class="form-select"
                        id="activo"
                        name="activo"
                        required
                    >
                        <option
                            value="1"
                            <?= (int) $usuario['activo'] === 1 ? 'selected' : '' ?>
                        >
                            Activo
                        </option>

                        <option
                            value="0"
                            <?= (int) $usuario['activo'] === 0 ? 'selected' : '' ?>
                        >
                            Inactivo
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Guardar cambios
                </button>

                <a href="usuarios.php" class="btn btn-secondary">
                    Cancelar
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>