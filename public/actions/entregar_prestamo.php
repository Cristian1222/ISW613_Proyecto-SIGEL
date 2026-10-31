<?php

declare(strict_types=1);

session_start();

// Cargar funciones auxiliares.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo administradores y operadores pueden entregar equipos.
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
    $pdo->beginTransaction();

    // Bloquear el préstamo para evitar entregas duplicadas.
    $consulta = $pdo->prepare("
        SELECT id, estado
        FROM prestamos
        WHERE id = :id
        FOR UPDATE
    ");

    $consulta->execute(['id' => $prestamoId]);
    $prestamo = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo || $prestamo['estado'] !== 'aprobado') {
        $pdo->rollBack();
        redirigir('../pages/prestamos.php?error=invalid_request');
    }

    // Obtener los equipos y cantidades del préstamo.
    $consulta = $pdo->prepare("
        SELECT equipo_id, cantidad
        FROM detalle_prestamo
        WHERE prestamo_id = :prestamo_id
        ORDER BY equipo_id
    ");

    $consulta->execute(['prestamo_id' => $prestamoId]);
    $detalles = $consulta->fetchAll(PDO::FETCH_ASSOC);

    if (empty($detalles)) {
        $pdo->rollBack();
        redirigir('../pages/prestamos.php?error=invalid_request');
    }

    // Descontar del inventario cada equipo entregado.
    $actualizarEquipo = $pdo->prepare("
        UPDATE equipos
        SET cantidad_disponible = cantidad_disponible - :cantidad
        WHERE id = :equipo_id
          AND cantidad_disponible >= :cantidad_validar
    ");

    foreach ($detalles as $detalle) {
        $cantidad = (int) $detalle['cantidad'];
        $equipoId = (int) $detalle['equipo_id'];

        $actualizarEquipo->execute([
            'cantidad' => $cantidad,
            'equipo_id' => $equipoId,
            'cantidad_validar' => $cantidad
        ]);

        if ($actualizarEquipo->rowCount() !== 1) {
            $pdo->rollBack();
            redirigir('../pages/prestamos.php?error=stock_insufficient');
        }
    }

    // Cambiar el estado del préstamo.
    $actualizarPrestamo = $pdo->prepare("
        UPDATE prestamos
        SET estado = 'entregado'
        WHERE id = :id
          AND estado = 'aprobado'
    ");

    $actualizarPrestamo->execute(['id' => $prestamoId]);

    if ($actualizarPrestamo->rowCount() !== 1) {
        $pdo->rollBack();
        redirigir('../pages/prestamos.php?error=invalid_request');
    }

    // Confirmar todos los cambios.
    $pdo->commit();

    redirigir('../pages/prestamos.php?success=loan_delivered');

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    redirigir('../pages/prestamos.php?error=server_error');
}