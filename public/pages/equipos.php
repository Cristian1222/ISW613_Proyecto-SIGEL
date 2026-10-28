<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

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
    $equipos = $consulta->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $equipos = [];
    $errorConsulta = true;
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3">Gestión de equipos</h1>
        <p class="text-muted mb-0">
            Inventario de equipos registrados en SIGEL.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="crear_equipo.php" class="btn btn-primary">
            + Registrar equipo
        </a>

        <a href="dashboard.php" class="btn btn-outline-secondary">
            Volver al panel
        </a>
    </div>
</div>

    <?php if (isset($errorConsulta)): ?>
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos as $equipo): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    $equipo['codigo'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $equipo['nombre'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $equipo['categoria'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>
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
                                    <?= htmlspecialchars(
                                        ucfirst($equipo['estado']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>
                            <td>
                         <a
                        href="editar_equipo.php?id=<?= (int) $equipo['id'] ?>"
                                    class="btn btn-sm btn-outline-primary">
                                Editar
                        </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>