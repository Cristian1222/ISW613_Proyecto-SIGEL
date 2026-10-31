<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo usuarios autenticados pueden consultar el inventario.
requerirAutenticacion();

$rol = obtenerUsuario()['rol'] ?? '';
$esAdmin = tieneRol(['admin']);
$puedeGestionar = tieneRol(['admin', 'operador']);

// Generar token CSRF para las acciones del inventario.
$tokenCSRF = generarTokenCSRF();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/alerts.php';

$errorConsulta = false;

try {
    $sql = "SELECT
                e.id,
                e.codigo,
                e.nombre,
                e.descripcion,
                e.cantidad_total,
                e.cantidad_disponible,
                e.estado,
                c.nombre AS categoria
            FROM equipos e
            INNER JOIN categorias c ON c.id = e.categoria_id
            ORDER BY e.id DESC";

    $consulta = $pdo->query($sql);
    $equipos = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $equipos = [];
    $errorConsulta = true;
}
?>

<div class="container py-4">

    <?php renderAlert(); ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3">Consulta de equipos</h1>

            <p class="text-muted mb-0">
                Consulta el inventario y la disponibilidad de los equipos registrados en SIGEL.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">

            <?php if ($esAdmin): ?>
                <a href="crear_equipo.php" class="btn btn-primary">
                    + Registrar equipo
                </a>
            <?php endif; ?>

            <a href="dashboard.php" class="btn btn-outline-secondary">
                Volver al panel
            </a>

        </div>
    </div>

    <?php if ($errorConsulta): ?>

        <div class="alert alert-danger">
            No se pudo cargar el inventario. Intenta nuevamente.
        </div>

    <?php elseif (count($equipos) === 0): ?>

        <div class="alert alert-info">
            Todavía no hay equipos registrados.
        </div>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle">

                <thead class="table-dark">
                    <tr>
                        <th>Código</th>
                        <th>Equipo</th>
                        <th>Categoría</th>
                        <th>Total</th>
                        <th>Disponibles</th>
                        <th>Estado</th>

                        <?php if ($puedeGestionar): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($equipos as $equipo): ?>

                        <tr>

                            <td><?= escapar($equipo['codigo']) ?></td>

                            <td><?= escapar($equipo['nombre']) ?></td>

                            <td><?= escapar($equipo['categoria']) ?></td>

                            <td><?= (int) $equipo['cantidad_total'] ?></td>

                            <td><?= (int) $equipo['cantidad_disponible'] ?></td>

                            <td>
                                <?php
                                $claseEstado = match ($equipo['estado']) {
                                    'activo' => 'success',
                                    'mantenimiento' => 'warning',
                                    default => 'secondary',
                                };
                                ?>

                                <span class="badge text-bg-<?= $claseEstado ?>">
                                    <?= escapar(ucfirst($equipo['estado'])) ?>
                                </span>
                            </td>

                            <?php if ($puedeGestionar): ?>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">

                                        <?php if ($esAdmin): ?>

                                            <a
                                                href="editar_equipo.php?id=<?= (int) $equipo['id'] ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                Editar
                                            </a>

                                            <form
                                                action="../actions/eliminar_equipo.php"
                                                method="POST"
                                                onsubmit="return confirm('¿Estás seguro de que deseas eliminar este equipo? Esta acción no se puede deshacer.');">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $equipo['id'] ?>">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= escapar($tokenCSRF) ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger">
                                                    Eliminar
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>
                                </td>

                            <?php endif; ?>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>