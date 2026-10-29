<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?error=login_required');
    exit;
}

if (($_SESSION['usuario']['rol'] ?? '') !== 'admin') {
    header('Location: ../pages/dashboard.php?error=forbidden');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/usuarios.php?error=invalid_request');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

if (
    !isset($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])
) {
    header('Location: ../pages/crear_usuario.php?error=invalid_request');
    exit;
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$correo = trim((string) ($_POST['correo'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$rol = (string) ($_POST['rol'] ?? '');

if (
    mb_strlen($nombre) < 2 ||
    mb_strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 150 ||
    strlen($password) < 8 ||
    !in_array($rol, ['profesor', 'operador'], true)
) {
    header('Location: ../pages/crear_usuario.php?error=invalid_input');
    exit;
}

try {
    $consulta = $pdo->prepare(
        'SELECT id FROM usuarios WHERE correo = :correo LIMIT 1'
    );
    $consulta->execute(['correo' => $correo]);

    if ($consulta->fetch()) {
        header('Location: ../pages/crear_usuario.php?error=duplicate_email');
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertar = $pdo->prepare(
        'INSERT INTO usuarios (nombre, correo, password_hash, rol, activo)
         VALUES (:nombre, :correo, :password_hash, :rol, 1)'
    );

    $insertar->execute([
        'nombre' => $nombre,
        'correo' => $correo,
        'password_hash' => $passwordHash,
        'rol' => $rol
    ]);

    header('Location: ../pages/usuarios.php?success=user_created');
    exit;

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        header('Location: ../pages/crear_usuario.php?error=duplicate_email');
        exit;
    }

    error_log($e->getMessage());
    header('Location: ../pages/crear_usuario.php?error=server_error');
    exit;
}