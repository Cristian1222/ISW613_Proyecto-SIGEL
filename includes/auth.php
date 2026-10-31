<?php

declare(strict_types=1);

/**
 * Comprueba si existe una sesión iniciada.
 */
function estaAutenticado(): bool
{
    return isset($_SESSION['usuario'])
        && is_array($_SESSION['usuario'])
        && isset($_SESSION['usuario']['id']);
}

/**
 * Devuelve los datos del usuario autenticado.
 */
function obtenerUsuario(): ?array
{
    return estaAutenticado()
        ? $_SESSION['usuario']
        : null;
}

/**
 * Comprueba si el usuario tiene alguno de los roles indicados.
 */
function tieneRol(array $roles): bool
{
    $usuario = obtenerUsuario();

    return $usuario !== null
        && in_array($usuario['rol'] ?? '', $roles, true);
}

/**
 * Exige que el usuario haya iniciado sesión.
 */
function requerirAutenticacion(): void
{
    if (!estaAutenticado()) {
        header('Location: ../index.php?error=login_required');
        exit;
    }
}

/**
 * Exige que el usuario tenga uno de los roles permitidos.
 */
function requerirRol(array $roles): void
{
    requerirAutenticacion();

    if (!tieneRol($roles)) {
        header('Location: ../pages/dashboard.php?error=forbidden');
        exit;
    }
}