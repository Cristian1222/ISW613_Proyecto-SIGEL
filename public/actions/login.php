<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/database.php';

// Solo se permite iniciar sesión mediante el formulario.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Obtener y validar los datos enviados.
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if (
    !is_string($correo) ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    !is_string($password) ||
    $password === ''
) {
    header('Location: ../index.php?error=1');
    exit;
}

try {
    // Buscar la cuenta por correo.
    $consulta = $pdo->prepare(
        'SELECT id, nombre, correo, password_hash, rol, activo
         FROM usuarios
         WHERE correo = :correo
         LIMIT 1'
    );

    $consulta->execute(['correo' => $correo]);
    $usuario = $consulta->fetch();

    // Verificar que exista, esté activo y la contraseña coincida.
    if (
        !$usuario ||
        (int) $usuario['activo'] !== 1 ||
        !password_verify($password, $usuario['password_hash'])
    ) {
        header('Location: ../index.php?error=1');
        exit;
    }

    // Evitar reutilizar el identificador anterior de sesión.
    session_regenerate_id(true);

    // Guardar solo los datos necesarios del usuario.
    $_SESSION['usuario'] = [
        'id' => (int) $usuario['id'],
        'nombre' => $usuario['nombre'],
        'correo' => $usuario['correo'],
        'rol' => $usuario['rol'],
    ];

    // Redirigir al panel del sistema.
    header('Location: ../pages/dashboard.php');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Ocurrió un error al procesar el inicio de sesión.');
}