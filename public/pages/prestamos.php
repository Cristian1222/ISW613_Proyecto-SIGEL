<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?error=login_required');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$rol = $_SESSION['usuario']['rol'] ?? '';
$puedeGestionar = in_array($rol, ['admin', 'operador'], true);

require_once __DIR__ . '/../../config/database.php';

$titulo = 'Préstamos | SIGEL';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/alerts.php';

$prestamos = [];
$errorConsulta = false;

try {
    $sql = "
        SELECT
            p.id,
            p.fecha_solicitud,
            p.fecha_inicio,
            p.fecha_devolucion_prevista,
            p.fecha_devolucion_real,
            p.estado,
            p.observaciones,
            operador.nombre AS operador,
            profesor.nombre AS profesor
        FROM prestamos p
        INNER JOIN usuarios operador
            ON operador.id = p.operador_id
        INNER JOIN usuarios profesor
            ON profesor.id = p.profesor_id
        ORDER BY p.id DESC
    ";

    $consulta = $pdo->query($sql);
    $prestamos = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorConsulta = true;
}

$estados = [
    'pendiente' => 'Pendiente',
    'aprobado' => 'Aprobado',
    'entregado' => 'Entregado',
    'devuelto' => 'Devuelto',
    'rechazado' => 'Rechazado',
    'cancelado' => 'Cancelado'
];

$clasesEstado = [
    'pendiente' => 'bg-warning text-dark',
    'aprobado' => 'bg-info text-dark',
    'entregado' => 'bg-primary',
    'devuelto' => 'bg-success',
    'rechazado' => 'bg-danger',
    'cancelado' => 'bg-secondary'
];
?>

<div class="container py-4">

    <?php renderAlert(); ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h1 class="h3 mb-1">Gestión de préstamos</h1>
            <p class="text-muted mb-0">
                Consulta y administra los préstamos de equipos.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">

            <?php if ($puedeGestionar): ?>
                <a href="crear_prestamo.php" class="btn btn-primary">
                    Registrar préstamo
                </a>
            <?php endif; ?>

            <a href="../index.php" class="btn btn-outline-secondary">
                Volver al panel
            </a>

        </div>
    </div>

    <?php if ($errorConsulta): ?>

        <div class="alert alert-danger" role="alert">
            No fue posible consultar los préstamos. Inténtalo más tarde.
        </div>

    <?php elseif (empty($prestamos)): ?>

        <div class="alert alert-info" role="alert">
            Todavía no hay préstamos registrados.
            Podés utilizar el botón «Registrar préstamo» para comenzar.
        </div>

    <?php else: ?>

        <div class="card shadow-sm">
            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>N.º</th>
                                <th>Profesor</th>
                                <th>Operador</th>
                                <th>Inicio</th>
                                <th>Devolución prevista</th>
                                <th>Devolución real</th>
                                <th>Estado</th>
                                <th>Observaciones</th>

                                <?php if ($puedeGestionar): ?>
                                    <th>Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($prestamos as $prestamo): ?>

                                <?php
                                $estado = $prestamo['estado'];

                                $clase = $clasesEstado[$estado]
                                    ?? 'bg-secondary';
                                ?>

                                <tr>

                                    <td>
                                        <?= (int) $prestamo['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $prestamo['profesor'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $prestamo['operador'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $prestamo['fecha_inicio'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $prestamo['fecha_devolucion_prevista'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $prestamo['fecha_devolucion_real']
                                            ? htmlspecialchars(
                                                $prestamo['fecha_devolucion_real'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : '—' ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= $clase ?>">
                                            <?= htmlspecialchars(
                                                $estados[$estado] ?? ucfirst($estado),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $prestamo['observaciones'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?: '—' ?>
                                    </td>

                                    <?php if ($puedeGestionar): ?>

                                        <td>

                                            <?php if ($estado === 'pendiente'): ?>

                                                <!-- Botón para aprobar -->
                                                <form
                                                    action="../actions/aprobar_prestamo.php"
                                                    method="POST"
                                                    class="mb-1"
                                                    onsubmit="return confirm('¿Deseas aprobar este préstamo?');">

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars(
                                                                    $_SESSION['csrf_token'],
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">

                                                    <input
                                                        type="hidden"
                                                        name="prestamo_id"
                                                        value="<?= (int) $prestamo['id'] ?>">

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-success">
                                                        Aprobar
                                                    </button>

                                                </form>

                                            <?php elseif ($estado === 'aprobado'): ?>

                                                <!-- Botón para entregar -->
                                                <form
                                                    action="../actions/entregar_prestamo.php"
                                                    method="POST"
                                                    onsubmit="return confirm('¿Deseas registrar la entrega de este préstamo?');">

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars(
                                                                    $_SESSION['csrf_token'],
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">

                                                    <input
                                                        type="hidden"
                                                        name="prestamo_id"
                                                        value="<?= (int) $prestamo['id'] ?>">

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-primary">
                                                        Entregar
                                                    </button>

                                                </form>
                                            <?php elseif ($estado === 'entregado'): ?>

                                                <!-- Botón para registrar la devolución -->
                                                <form
                                                    action="../actions/devolver_prestamo.php"
                                                    method="POST"
                                                    class="m-0"
                                                    onsubmit="return confirm('¿Confirmas que el equipo fue devuelto?');">
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars(
                                                                    $_SESSION['csrf_token'],
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">

                                                    <input
                                                        type="hidden"
                                                        name="prestamo_id"
                                                        value="<?= (int) $prestamo['id'] ?>">

                                                    <button type="submit" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-arrow-return-left"></i> Registrar devolución
                                                    </button>
                                                </form>

                                            <?php else: ?>

                                                <span class="text-muted">—</span>

                                            <?php endif; ?>

                                        </td>

                                    <?php endif; ?>

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