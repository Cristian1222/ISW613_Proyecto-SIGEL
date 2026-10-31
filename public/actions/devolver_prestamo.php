<?php

declare(strict_types=1);

session_start();

// Cargar funciones auxiliares.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo administradores y operadores pueden registrar devoluciones.
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

    // Bloquear el préstamo y comprobar que esté entregado.
    $consulta = $pdo->prepare("
        SELECT id, estado
        FROM prestamos
        WHERE id = :id
        FOR UPDATE
    ");

    $consulta->execute(['id' => $prestamoId]);
    $prestamo = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo || $prestamo['estado'] !== 'entregado') {
        $pdo->rollBack();
        redirigir('../pages/prestamos.php?error=invalid_request');
    }

    // Consultar los equipos incluidos en el préstamo.
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

    // Devolver las unidades al inventario.
    $actualizarEquipo = $pdo->prepare("
        UPDATE equipos
        SET cantidad_disponible = cantidad_disponible + :cantidad
        WHERE id = :equipo_id
          AND cantidad_disponible + :cantidad_validar <= cantidad_total
    ");

    foreach ($detalles as $detalle) {
        $equipoId = (int) $detalle['equipo_id'];
        $cantidad = (int) $detalle['cantidad'];

        $actualizarEquipo->execute([
            'cantidad' => $cantidad,
            'equipo_id' => $equipoId,
            'cantidad_validar' => $cantidad
        ]);

        if ($actualizarEquipo->rowCount() !== 1) {
            throw new RuntimeException(
                'No fue posible actualizar el inventario del equipo ' . $equipoId
            );
        }
    }

    // Registrar el estado y la fecha real de devolución.
    $actualizarPrestamo = $pdo->prepare("
        UPDATE prestamos
        SET estado = 'devuelto',
            fecha_devolucion_real = CURDATE()
        WHERE id = :id
          AND estado = 'entregado'
    ");

    $actualizarPrestamo->execute(['id' => $prestamoId]);

    if ($actualizarPrestamo->rowCount() !== 1) {
        throw new RuntimeException('No fue posible actualizar el préstamo.');
    }

    // Confirmar todos los cambios.
    $pdo->commit();

    redirigir('../pages/prestamos.php?success=loan_returned');

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    redirigir('../pages/prestamos.php?error=server_error');
}