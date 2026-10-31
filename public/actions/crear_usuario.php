<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo el administrador puede crear usuarios.
requerirRol(['admin']);

// La solicitud debe utilizar POST.
if (!esPost()) {
    redirigir('../pages/usuarios.php?error=invalid_request');
}

// Verificar el token de seguridad CSRF.
requerirTokenCSRF();

require_once __DIR__ . '/../../config/database.php';

// Obtener los datos del formulario.
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$correo = trim((string) ($_POST['correo'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$rol = (string) ($_POST['rol'] ?? '');

// Validar los datos recibidos.
if (
    mb_strlen($nombre) < 2 ||
    mb_strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 150 ||
    strlen($password) < 8 ||
    !in_array($rol, ['profesor', 'operador'], true)
) {
    redirigir('../pages/crear_usuario.php?error=invalid_input');
}

try {

    // Comprobar si el correo ya está registrado.
    $consulta = $pdo->prepare(
        'SELECT id FROM usuarios WHERE correo = :correo LIMIT 1'
    );

    $consulta->execute([
        'correo' => $correo
    ]);

    if ($consulta->fetch()) {
        redirigir('../pages/crear_usuario.php?error=duplicate_email');
    }

    // Generar el hash seguro de la contraseña.
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if ($passwordHash === false) {
        throw new RuntimeException('No fue posible generar el hash de la contraseña.');
    }

    // Registrar el nuevo usuario.
    $insertar = $pdo->prepare(
        'INSERT INTO usuarios
        (nombre, correo, password_hash, rol, activo)
        VALUES
        (:nombre, :correo, :password_hash, :rol, 1)'
    );

    $insertar->execute([
        'nombre' => $nombre,
        'correo' => $correo,
        'password_hash' => $passwordHash,
        'rol' => $rol
    ]);

    redirigir('../pages/usuarios.php?success=user_created');

} catch (PDOException $e) {

    // Controlar posibles correos duplicados.
    if ($e->getCode() === '23000') {
        redirigir('../pages/crear_usuario.php?error=duplicate_email');
    }

    error_log($e->getMessage());

    redirigir('../pages/crear_usuario.php?error=server_error');

} catch (Throwable $e) {

    error_log($e->getMessage());

    redirigir('../pages/crear_usuario.php?error=server_error');
}