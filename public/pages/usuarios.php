<?php
declare(strict_types=1);

session_start();

// Solo el administrador puede gestionar usuarios.
if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?error=login_required');
    exit;
}

if (($_SESSION['usuario']['rol'] ?? '') !== 'admin') {
    header('Location: dashboard.php?error=forbidden');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$titulo = 'Gestión de usuarios | SIGEL';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/alerts.php';

$usuarios = [];
$errorConsulta = false;

try {
    $consulta = $pdo->query(
        'SELECT id, nombre, correo, rol, activo, creado_en
         FROM usuarios
         ORDER BY id DESC'
    );

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
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($usuarios as $fila): ?>
                                <tr>
                                    <td>
                                        <?= (int) $fila['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $fila['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $fila['correo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            ucfirst($fila['rol']),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
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
                                        <?= htmlspecialchars(
                                            $fila['creado_en'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
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