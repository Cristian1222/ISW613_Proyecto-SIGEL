<?php

declare(strict_types=1);

session_start();

// Cargar funciones auxiliares.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo administradores y operadores pueden aprobar préstamos.
requerirRol(['admin', 'operador']);

// La operación solo se permite mediante POST.
if (!esPost()) {
    redirigir('../pages/prestamos.php?error=invalid_request');
}

// Verificar el token CSRF.
requerirTokenCSRF();

// Validar el ID del préstamo.
$prestamoId = filter_var(
    obtenerPost('prestamo_id'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($prestamoId === false || $prestamoId === null) {
    redirigir('../pages/prestamos.php?error=invalid_request');
}

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

try {
    $sql = "
        UPDATE prestamos
        SET estado = 'aprobado'
        WHERE id = :id
          AND estado = 'pendiente'
    ";

    $consulta = $pdo->prepare($sql);
    $consulta->execute(['id' => $prestamoId]);

    // Comprobar que el préstamo se haya actualizado.
    if ($consulta->rowCount() !== 1) {
        redirigir('../pages/prestamos.php?error=invalid_request');
    }

    // Aprobación exitosa.
    redirigir('../pages/prestamos.php?success=loan_approved');

} catch (PDOException $e) {
    error_log($e->getMessage());

    redirigir('../pages/prestamos.php?error=server_error');
}