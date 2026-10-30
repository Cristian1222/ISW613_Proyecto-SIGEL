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
        header('Location: ../pages/prestamos.php?error=invalid_request');
        exit;
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
        header('Location: ../pages/prestamos.php?error=invalid_request');
        exit;
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
            header('Location: ../pages/prestamos.php?error=stock_insufficient');
            exit;
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
        header('Location: ../pages/prestamos.php?error=invalid_request');
        exit;
    }

    $pdo->commit();

    header('Location: ../pages/prestamos.php?success=loan_delivered');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/prestamos.php?error=server_error');
    exit;
}