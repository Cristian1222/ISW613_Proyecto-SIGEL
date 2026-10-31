<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo el administrador puede editar usuarios.
requerirRol(['admin']);

// Aceptar únicamente solicitudes POST.
if (!esPost()) {
    redirigir('../pages/usuarios.php?error=invalid_request');
}

// Verificar el token CSRF.
requerirTokenCSRF();

require_once __DIR__ . '/../../config/database.php';

// Obtener los datos enviados.
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$correo = trim((string) ($_POST['correo'] ?? ''));
$rol = (string) ($_POST['rol'] ?? '');
$activo = filter_var($_POST['activo'] ?? null, FILTER_VALIDATE_INT);

// Validar los datos.
if (
    !$id ||
    $id <= 0 ||
    mb_strlen($nombre) < 2 ||
    mb_strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 150 ||
    !in_array($rol, ['admin', 'operador', 'profesor'], true) ||
    !in_array($activo, [0, 1], true)
) {
    redirigir('../pages/usuarios.php?error=invalid_input');
}

try {

    // Comprobar que el usuario exista.
    $buscar = $pdo->prepare(
        'SELECT id FROM usuarios WHERE id = :id LIMIT 1'
    );

    $buscar->execute(['id' => $id]);

    if (!$buscar->fetch()) {
        redirigir('../pages/usuarios.php?error=invalid_input');
    }

    // Comprobar que el correo no pertenezca a otro usuario.
    $correoExiste = $pdo->prepare(
        'SELECT id
         FROM usuarios
         WHERE correo = :correo AND id <> :id
         LIMIT 1'
    );

    $correoExiste->execute([
        'correo' => $correo,
        'id' => $id
    ]);

    if ($correoExiste->fetch()) {
        redirigir('../pages/editar_usuario.php?id=' . $id . '&error=duplicate_email');
    }

    // Actualizar los datos del usuario.
    $actualizar = $pdo->prepare(
        'UPDATE usuarios
         SET nombre = :nombre,
             correo = :correo,
             rol = :rol,
             activo = :activo
         WHERE id = :id'
    );

    $actualizar->execute([
        'nombre' => $nombre,
        'correo' => $correo,
        'rol' => $rol,
        'activo' => $activo,
        'id' => $id
    ]);

    redirigir('../pages/usuarios.php?success=user_updated');

} catch (PDOException $e) {

    if ($e->getCode() === '23000') {
        redirigir('../pages/editar_usuario.php?id=' . $id . '&error=duplicate_email');
    }

    error_log($e->getMessage());

    redirigir('../pages/usuarios.php?error=server_error');

} catch (Throwable $e) {

    error_log($e->getMessage());

    redirigir('../pages/usuarios.php?error=server_error');
}