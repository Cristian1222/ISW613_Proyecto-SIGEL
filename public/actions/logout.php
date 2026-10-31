<?php

declare(strict_types=1);

session_start();

// Cargar funciones auxiliares.
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Permitir únicamente solicitudes POST.
if (!esPost()) {
    redirigir('../index.php?error=invalid_request');
}

// Validar el token CSRF.
requerirTokenCSRF();

// Vaciar los datos de la sesión.
$_SESSION = [];

// Invalidar la cookie de sesión, si está habilitada.
if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $parametros['path'],
        'domain' => $parametros['domain'],
        'secure' => $parametros['secure'],
        'httponly' => $parametros['httponly'],
        'samesite' => $parametros['samesite'] ?? 'Lax'
    ]);
}

// Destruir la sesión.
session_destroy();

// Regresar al inicio de sesión.
redirigir('../index.php?success=logged_out');