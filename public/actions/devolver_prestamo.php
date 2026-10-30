<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?error=login_required');
    exit;
}

$rol = $_SESSION['usuario']['rol'] ?? '';

if (!in_array($rol, ['admin', 'operador'], true)) {
    header('Location: ../pages/prestamos.php?error=forbidden');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/prestamos.php?error=invalid_request');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    header('Location: ../pages/prestamos.php?error=invalid_request');
    exit;
}

$prestamoId = filter_input(INPUT_POST, 'prestamo_id', FILTER_VALIDATE_INT);

if (!$prestamoId || $prestamoId <= 0) {
    header('Location: ../pages/prestamos.php?error=invalid_request');
    exit;
}

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
        header('Location: ../pages/prestamos.php?error=invalid_request');
        exit;
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
        header('Location: ../pages/prestamos.php?error=invalid_request');
        exit;
    }

    // Devolver las unidades al inventario.
    foreach ($detalles as $detalle) {
        $equipoId = (int) $detalle['equipo_id'];
        $cantidad = (int) $detalle['cantidad'];

        $actualizarEquipo = $pdo->prepare("
            UPDATE equipos
            SET cantidad_disponible = cantidad_disponible + :cantidad
            WHERE id = :equipo_id
              AND cantidad_disponible + :cantidad_validar <= cantidad_total
        ");

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

    $pdo->commit();

    header('Location: ../pages/prestamos.php?success=loan_returned');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/prestamos.php?error=server_error');
    exit;
}