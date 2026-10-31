<?php

declare(strict_types=1);

session_start();

// Cargar funciones de seguridad y utilidades.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo el administrador puede eliminar equipos.
requerirRol(['admin']);

// La eliminación solo se permite mediante POST.
if (!esPost()) {
    redirigir('../pages/equipos.php?error=invalid_request');
}

// Verificar el token CSRF.
requerirTokenCSRF();

// Validar el ID del equipo.
$id = filter_var(
    obtenerPost('id'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    redirigir('../pages/equipos.php?error=invalid_request');
}

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

try {
    $consulta = $pdo->prepare(
        'DELETE FROM equipos WHERE id = :id'
    );

    $consulta->execute(['id' => $id]);

    // Comprobar si se eliminó algún registro.
    if ($consulta->rowCount() === 0) {
        redirigir('../pages/equipos.php?error=equipment_not_found');
    }

    // Eliminación exitosa.
    redirigir('../pages/equipos.php?success=equipment_deleted');

} catch (PDOException $e) {
    error_log($e->getMessage());

    // La base de datos puede impedir la eliminación
    // si existen préstamos relacionados con el equipo.
    if ($e->getCode() === '23000') {
        redirigir('../pages/equipos.php?error=equipment_related');
    }

    error_log('Error al eliminar un equipo en SIGEL.');
    redirigir('../pages/equipos.php?error=server_error');
}