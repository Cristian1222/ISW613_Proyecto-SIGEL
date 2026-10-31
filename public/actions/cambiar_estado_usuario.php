<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo el administrador puede cambiar estados de usuarios.
requerirRol(['admin']);

// Aceptar únicamente solicitudes POST.
if (!esPost()) {
    redirigir('../pages/usuarios.php?error=invalid_request');
}

// Verificar el token CSRF.
requerirTokenCSRF();

// Obtener los datos enviados.
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$activo = filter_var($_POST['activo'] ?? null, FILTER_VALIDATE_INT);

$usuarioActual = obtenerUsuario();

// Validar los datos.
if (
    !$id ||
    $id <= 0 ||
    !in_array($activo, [0, 1], true)
) {
    redirigir('../pages/usuarios.php?error=invalid_input');
}

// Evitar que el administrador desactive su propia cuenta.
if ($id === (int) $usuarioActual['id']) {
    redirigir('../pages/usuarios.php?error=self_deactivation');
}

require_once __DIR__ . '/../../config/database.php';

try {

    // Comprobar que el usuario exista.
    $consulta = $pdo->prepare(
        'SELECT id FROM usuarios WHERE id = :id LIMIT 1'
    );

    $consulta->execute(['id' => $id]);

    if (!$consulta->fetch()) {
        redirigir('../pages/usuarios.php?error=invalid_input');
    }

    // Actualizar el estado de la cuenta.
    $actualizar = $pdo->prepare(
        'UPDATE usuarios
         SET activo = :activo
         WHERE id = :id'
    );

    $actualizar->execute([
        'activo' => $activo,
        'id' => $id
    ]);

    if ($activo === 1) {
        redirigir('../pages/usuarios.php?success=user_activated');
    }

    redirigir('../pages/usuarios.php?success=user_deactivated');

} catch (PDOException $e) {

    error_log($e->getMessage());

    redirigir('../pages/usuarios.php?error=server_error');

} catch (Throwable $e) {

    error_log($e->getMessage());

    redirigir('../pages/usuarios.php?error=server_error');
}