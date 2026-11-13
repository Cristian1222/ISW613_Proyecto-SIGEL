<?php

declare(strict_types=1);

session_start();

// Cargar funciones auxiliares.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo el administrador puede modificar la configuración.
requerirRol(['admin']);

// La operación solo se permite mediante POST.
if (!esPost()) {
    redirigir('../pages/configuracion.php?error=invalid_request');
}

// Verificar el token CSRF.
requerirTokenCSRF();

// Validar los minutos recibidos.
$minutos = filter_var(
    obtenerPost('cancelacion_minutos'),
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 0,
            'max_range' => 10080
        ]
    ]
);

if ($minutos === false || $minutos === null) {
    redirigir('../pages/configuracion.php?error=invalid_input');
}

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

try {

    // Actualizar el tiempo mínimo de anticipación.
    $sql = "
        UPDATE configuracion
        SET cancelacion_minutos = :minutos
        WHERE id = 1
    ";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        'minutos' => $minutos
    ]);

    // Verificar que exista el registro de configuración.
    $verificar = $pdo->query("
        SELECT COUNT(*)
        FROM configuracion
        WHERE id = 1
    ");

    if ((int) $verificar->fetchColumn() !== 1) {
        throw new RuntimeException(
            'No se encontró el registro de configuración.'
        );
    }

    // Redirigir después de guardar.
    redirigir('../pages/configuracion.php?success=configuration_updated');

} catch (Throwable $e) {

    error_log($e->getMessage());

    redirigir('../pages/configuracion.php?error=server_error');
}