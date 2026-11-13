<?php

declare(strict_types=1);

session_start();

// Cargar funciones auxiliares.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo operadores y profesores pueden cancelar préstamos.
requerirRol(['operador', 'profesor']);

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

// Obtener el usuario y sus permisos.
$usuarioId = (int) ($_SESSION['usuario']['id'] ?? 0);
$esOperador = tieneRol(['operador']);
$esProfesor = tieneRol(['profesor']);

if ($usuarioId <= 0) {
    redirigir('../pages/prestamos.php?error=login_required');
}

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

try {

    // Iniciar transacción para proteger el préstamo y el inventario.
    $pdo->beginTransaction();

    // Obtener y bloquear el préstamo.
    $sql = "
        SELECT
            id,
            profesor_id,
            fecha_inicio,
            hora_inicio,
            estado
        FROM prestamos
        WHERE id = :id
        FOR UPDATE
    ";

    $consulta = $pdo->prepare($sql);
    $consulta->execute(['id' => $prestamoId]);

    $prestamo = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo) {
        $pdo->rollBack();
        redirigir('../pages/prestamos.php?error=invalid_request');
    }

    // El profesor solamente puede cancelar sus propios préstamos.
    if (
        $esProfesor &&
        !$esOperador &&
        (int) $prestamo['profesor_id'] !== $usuarioId
    ) {
        $pdo->rollBack();
        redirigir('../pages/prestamos.php?error=forbidden');
    }

    // Verificar que el estado permita la cancelación.
    $estadosCancelables = ['pendiente', 'aprobado', 'entregado'];

    if (!in_array($prestamo['estado'], $estadosCancelables, true)) {
        $pdo->rollBack();
        redirigir('../pages/prestamos.php?error=loan_not_cancelable');
    }

    // Comprobar que exista una hora de inicio.
    if (empty($prestamo['fecha_inicio']) || empty($prestamo['hora_inicio'])) {
        $pdo->rollBack();
        redirigir('../pages/prestamos.php?error=loan_time_missing');
    }

    // Obtener el tiempo mínimo configurado por el administrador.
    $consultaConfig = $pdo->query("
        SELECT cancelacion_minutos
        FROM configuracion
        WHERE id = 1
    ");

    $minutosConfigurados = $consultaConfig->fetchColumn();

    if ($minutosConfigurados === false) {
        throw new RuntimeException(
            'No se encontró la configuración de cancelación.'
        );
    }

    $minutosConfigurados = (int) $minutosConfigurados;

    // Calcular los segundos que faltan para el inicio del préstamo.
    $sqlTiempo = "
        SELECT TIMESTAMPDIFF(
            SECOND,
            NOW(),
            CONCAT(:fecha_inicio, ' ', :hora_inicio)
        )
    ";

    $consultaTiempo = $pdo->prepare($sqlTiempo);

    $consultaTiempo->execute([
        'fecha_inicio' => $prestamo['fecha_inicio'],
        'hora_inicio' => $prestamo['hora_inicio']
    ]);

    $segundosRestantes = $consultaTiempo->fetchColumn();

    // Verificar el tiempo mínimo de anticipación.
    if (
        $segundosRestantes === false ||
        (int) $segundosRestantes < ($minutosConfigurados * 60)
    ) {
        $pdo->rollBack();
        redirigir('../pages/prestamos.php?error=cancellation_too_late');
    }

    // Si el préstamo ya fue entregado, devolver los equipos al inventario.
    if ($prestamo['estado'] === 'entregado') {

        $sqlDetalle = "
            SELECT equipo_id, cantidad
            FROM detalle_prestamo
            WHERE prestamo_id = :prestamo_id
        ";

        $consultaDetalle = $pdo->prepare($sqlDetalle);

        $consultaDetalle->execute([
            'prestamo_id' => $prestamoId
        ]);

        $detalles = $consultaDetalle->fetchAll(PDO::FETCH_ASSOC);

        if (empty($detalles)) {
            throw new RuntimeException(
                'El préstamo entregado no tiene detalles registrados.'
            );
        }

        foreach ($detalles as $detalle) {

            $sqlInventario = "
                UPDATE equipos
                SET cantidad_disponible = cantidad_disponible + :cantidad_sumar
                WHERE id = :equipo_id
                  AND cantidad_disponible + :cantidad_limite <= cantidad_total
            ";

            $consultaInventario = $pdo->prepare($sqlInventario);

            $consultaInventario->execute([
                'cantidad_sumar' => $detalle['cantidad'],
                'cantidad_limite' => $detalle['cantidad'],
                'equipo_id' => $detalle['equipo_id']
            ]);

            if ($consultaInventario->rowCount() !== 1) {
                throw new RuntimeException(
                    'No fue posible actualizar correctamente el inventario.'
                );
            }
        }
    }

    // Cambiar el estado del préstamo a cancelado.
    $sqlCancelar = "
        UPDATE prestamos
        SET estado = 'cancelado'
        WHERE id = :id
    ";

    $consultaCancelar = $pdo->prepare($sqlCancelar);

    $consultaCancelar->execute([
        'id' => $prestamoId
    ]);

    if ($consultaCancelar->rowCount() !== 1) {
        throw new RuntimeException(
            'No fue posible actualizar el estado del préstamo.'
        );
    }

    // Confirmar todos los cambios.
    $pdo->commit();

    redirigir('../pages/prestamos.php?success=loan_cancelled');

} catch (Throwable $e) {

    // Revertir cualquier modificación si ocurre un error.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    redirigir('../pages/prestamos.php?error=server_error');
}