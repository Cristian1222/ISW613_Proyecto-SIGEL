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
    $sql = "
        UPDATE prestamos
        SET estado = 'aprobado'
        WHERE id = :id
          AND estado = 'pendiente'
    ";

    $consulta = $pdo->prepare($sql);
    $consulta->execute(['id' => $prestamoId]);

    if ($consulta->rowCount() !== 1) {
        header('Location: ../pages/prestamos.php?error=invalid_request');
        exit;
    }

    header('Location: ../pages/prestamos.php?success=loan_approved');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());

    header('Location: ../pages/prestamos.php?error=server_error');
    exit;
}