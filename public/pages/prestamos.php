<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?error=login_required');
    exit;
}

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
            <a href="crear_prestamo.php" class="btn btn-primary">
                Registrar préstamo
            </a>

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
                                            : 'Pendiente' ?>
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