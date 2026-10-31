<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo los administradores pueden gestionar usuarios.
requerirRol(['admin']);

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

$titulo = 'Gestión de usuarios | SIGEL';

$tokenCSRF = generarTokenCSRF();
$usuarioActual = obtenerUsuario();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/alerts.php';

$usuarios = [];
$errorConsulta = false;

try {

    $consulta = $pdo->query("
        SELECT id, nombre, correo, rol, activo, creado_en
        FROM usuarios
        ORDER BY id DESC
    ");

    $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log($e->getMessage());
    $errorConsulta = true;

}
?>

<div class="container py-4">

    <?php renderAlert(); ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h1 class="h3">Gestión de usuarios</h1>

            <p class="text-muted mb-0">
                Administra las cuentas y los roles de SIGEL.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">

            <a href="crear_usuario.php" class="btn btn-primary">
                Crear usuario
            </a>

            <a href="dashboard.php" class="btn btn-outline-secondary">
                Volver al panel
            </a>

        </div>

    </div>

    <?php if ($errorConsulta): ?>

        <div class="alert alert-danger">
            No fue posible consultar los usuarios.
        </div>

    <?php elseif (empty($usuarios)): ?>

        <div class="alert alert-info">
            No hay usuarios registrados.
        </div>

    <?php else: ?>

        <div class="card shadow-sm">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha de registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($usuarios as $fila): ?>

                                <tr>

                                    <td>
                                        <?= (int) $fila['id'] ?>
                                    </td>

                                    <td>
                                        <?= escapar($fila['nombre']) ?>
                                    </td>

                                    <td>
                                        <?= escapar($fila['correo']) ?>
                                    </td>

                                    <td>
                                        <?= escapar(ucfirst($fila['rol'])) ?>
                                    </td>

                                    <td>

                                        <?php if ((int) $fila['activo'] === 1): ?>

                                            <span class="badge bg-success">
                                                Activo
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary">
                                                Inactivo
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?= escapar($fila['creado_en']) ?>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-wrap gap-2">

                                            <a
                                                href="editar_usuario.php?id=<?= (int) $fila['id'] ?>"
                                                class="btn btn-sm btn-warning"
                                            >
                                                Editar
                                            </a>

                                            <?php if ((int) $fila['id'] !== (int) $usuarioActual['id']): ?>

                                                <form
                                                    action="../actions/cambiar_estado_usuario.php"
                                                    method="POST"
                                                    onsubmit="return confirm('¿Está seguro de cambiar el estado de esta cuenta?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= escapar($tokenCSRF) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int) $fila['id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="activo"
                                                        value="<?= (int) $fila['activo'] === 1 ? '0' : '1' ?>"
                                                    >

                                                    <?php if ((int) $fila['activo'] === 1): ?>

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-danger"
                                                        >
                                                            Desactivar
                                                        </button>

                                                    <?php else: ?>

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-success"
                                                        >
                                                            Activar
                                                        </button>

                                                    <?php endif; ?>

                                                </form>

                                            <?php endif; ?>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>