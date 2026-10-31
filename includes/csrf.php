<?php

declare(strict_types=1);

/**
 * Genera un token CSRF y lo guarda en la sesión.
 */
function generarTokenCSRF(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Comprueba que el token recibido sea válido.
 */
function verificarTokenCSRF(): bool
{
    $token = $_POST['csrf_token'] ?? '';

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Detiene la solicitud cuando el token no es válido.
 */
function requerirTokenCSRF(): void
{
    if (!verificarTokenCSRF()) {
        http_response_code(403);
        exit('Solicitud no válida. Recargue la página e inténtelo nuevamente.');
    }
}