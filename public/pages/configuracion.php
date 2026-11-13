<?php

declare(strict_types=1);

session_start();

// Cargar funciones auxiliares.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo el administrador puede acceder.
requerirRol(['admin']);

// Generar token CSRF.
$tokenCSRF = generarTokenCSRF();

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

$titulo = 'Configuración | SIGEL';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/alerts.php';

$minutosCancelacion = 60;
$errorConsulta = false;

try {

    $consulta = $pdo->query("
        SELECT cancelacion_minutos
        FROM configuracion
        WHERE id = 1
    ");

    $resultado = $consulta->fetchColumn();

    if ($resultado !== false) {
        $minutosCancelacion = (int) $resultado;
    }

} catch (PDOException $e) {

    error_log($e->getMessage());
    $errorConsulta = true;
}

?>

<div class="container py-4">

    <?php renderAlert(); ?>

    <div class="mb-4">

        <h1 class="h3 mb-1">Configuración del sistema</h1>

        <p class="text-muted mb-0">
            Administra los parámetros generales de SIGEL.
        </p>

    </div>

    <?php if ($errorConsulta): ?>

        <div class="alert alert-danger" role="alert">
            No fue posible consultar la configuración del sistema.
        </div>

    <?php else: ?>

        <div class="card shadow-sm">

            <div class="card-header">
                <h2 class="h5 mb-0">Regla de cancelación de préstamos</h2>
            </div>

            <div class="card-body">

                <p>
                    Establece cuántos minutos antes de la hora de inicio
                    debe realizarse la cancelación de un préstamo.
                </p>

                <form
                    action="../actions/actualizar_configuracion.php"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escapar($tokenCSRF) ?>"
                    >

                    <div class="mb-3">

                        <label for="cancelacion_minutos" class="form-label">
                            Tiempo mínimo de anticipación (minutos)
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="cancelacion_minutos"
                            name="cancelacion_minutos"
                            min="0"
                            max="10080"
                            step="1"
                            value="<?= $minutosCancelacion ?>"
                            required
                        >

                        <div class="form-text">
                            Ejemplo: 60 minutos equivale a una hora
                            de anticipación. El valor 0 permite cancelar
                            hasta el momento de inicio.
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary">
                        Guardar configuración
                    </button>

                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        Volver al panel
                    </a>

                </form>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>