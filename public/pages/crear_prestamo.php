<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?error=login_required');
    exit;
}
if (!in_array($_SESSION['usuario']['rol'] ?? '', ['admin', 'operador'], true)) {
    header('Location: prestamos.php?error=forbidden');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$titulo = 'Registrar préstamo | SIGEL';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/alerts.php';

$errores = [];

$profesores = [];
$equipos = [];

try {
    $consultaProfesores = $pdo->query(
        "SELECT id, nombre, correo
         FROM usuarios
         WHERE rol = 'profesor' AND activo = 1
         ORDER BY nombre"
    );

    $profesores = $consultaProfesores->fetchAll(PDO::FETCH_ASSOC);

    $consultaEquipos = $pdo->query(
        "SELECT id, codigo, nombre, cantidad_disponible
         FROM equipos
         WHERE estado = 'activo' AND cantidad_disponible > 0
         ORDER BY nombre"
    );

    $equipos = $consultaEquipos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $errores[] = 'No fue posible cargar los profesores y equipos.';
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="container py-4">

    <?php renderAlert(); ?>

    <div class="mb-4">
        <h1 class="h3">Registrar préstamo</h1>
        <p class="text-muted">
            Completá los datos para solicitar equipos.
        </p>
    </div>

    <?php foreach ($errores as $error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endforeach; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <form action="../actions/crear_prestamo.php" method="POST">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-3">
                    <label for="profesor_id" class="form-label">
                        Profesor solicitante
                    </label>

                    <select name="profesor_id"
                            id="profesor_id"
                            class="form-select"
                            required>

                        <option value="">Seleccioná un profesor</option>

                        <?php foreach ($profesores as $profesor): ?>
                            <option value="<?= (int) $profesor['id'] ?>">
                                <?= htmlspecialchars($profesor['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                (<?= htmlspecialchars($profesor['correo'], ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <?php if (empty($profesores)): ?>
                        <div class="form-text text-danger">
                            No hay profesores activos registrados.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="equipo_id" class="form-label">
                        Equipo
                    </label>

                    <select name="equipo_id"
                            id="equipo_id"
                            class="form-select"
                            required>

                        <option value="">Seleccioná un equipo</option>

                        <?php foreach ($equipos as $equipo): ?>
                            <option value="<?= (int) $equipo['id'] ?>">
                                <?= htmlspecialchars($equipo['codigo'], ENT_QUOTES, 'UTF-8') ?>
                                — <?= htmlspecialchars($equipo['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                (Disponibles: <?= (int) $equipo['cantidad_disponible'] ?>)
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <?php if (empty($equipos)): ?>
                        <div class="form-text text-danger">
                            No hay equipos activos con unidades disponibles.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="cantidad" class="form-label">
                        Cantidad solicitada
                    </label>

                    <input type="number"
                           name="cantidad"
                           id="cantidad"
                           class="form-control"
                           min="1"
                           value="1"
                           required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fecha_inicio" class="form-label">
                            Fecha de inicio
                        </label>

                        <input type="date"
                               name="fecha_inicio"
                               id="fecha_inicio"
                               class="form-control"
                               min="<?= date('Y-m-d') ?>"
                               value="<?= date('Y-m-d') ?>"
                               required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="fecha_devolucion_prevista" class="form-label">
                            Fecha prevista de devolución
                        </label>

                        <input type="date"
                               name="fecha_devolucion_prevista"
                               id="fecha_devolucion_prevista"
                               class="form-control"
                               min="<?= date('Y-m-d') ?>"
                               required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="observaciones" class="form-label">
                        Observaciones
                    </label>

                    <textarea name="observaciones"
                              id="observaciones"
                              class="form-control"
                              rows="3"
                              maxlength="2000"
                              placeholder="Detalles adicionales (opcional)"></textarea>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit"
                            class="btn btn-primary"
                        <?= empty($profesores) || empty($equipos) ? 'disabled' : '' ?>>
                        Registrar préstamo
                    </button>

                    <a href="prestamos.php"
                       class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>