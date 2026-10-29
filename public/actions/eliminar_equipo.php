<?php

declare(strict_types=1);

session_start();

// Verificar que el usuario haya iniciado sesión.
if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?error=login_required');
    exit;
}

// Solo el administrador puede eliminar equipos.
if (($_SESSION['usuario']['rol'] ?? '') !== 'admin') {
    header('Location: ../pages/equipos.php?error=forbidden');
    exit;
}

// La eliminación solo se permite mediante POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/equipos.php?error=invalid_request');
    exit;
}

// Verificar el token de seguridad del formulario.
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header('Location: ../pages/equipos.php?error=invalid_request');
    exit;
}

// Validar el ID del equipo.
$id = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    header('Location: ../pages/equipos.php?error=invalid_request');
    exit;
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
        header('Location: ../pages/equipos.php?error=equipment_not_found');
        exit;
    }

    // Eliminación exitosa.
    header('Location: ../pages/equipos.php?success=equipment_deleted');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());

    // La base de datos puede impedir la eliminación
    // si existen préstamos relacionados con el equipo.
    if ($e->getCode() === '23000') {
        header('Location: ../pages/equipos.php?error=equipment_related');
        exit;
    }

    // Error inesperado del servidor.
    error_log('Error al eliminar un equipo en SIGEL.');
    header('Location: ../pages/equipos.php?error=server_error');
    exit;
}