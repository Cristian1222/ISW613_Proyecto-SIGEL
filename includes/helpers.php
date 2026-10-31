<?php

declare(strict_types=1);

/**
 * Escapa texto para mostrarlo de forma segura en HTML.
 */
function escapar(mixed $valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Redirige al usuario a otra página.
 */
function redirigir(string $ruta): never
{
    header('Location: ' . $ruta);
    exit;
}

/**
 * Comprueba que una solicitud utilice el método POST.
 */
function esPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Obtiene un valor de forma segura desde GET.
 */
function obtenerGet(string $nombre, mixed $predeterminado = null): mixed
{
    return $_GET[$nombre] ?? $predeterminado;
}

/**
 * Obtiene un valor de forma segura desde POST.
 */
function obtenerPost(string $nombre, mixed $predeterminado = null): mixed
{
    return $_POST[$nombre] ?? $predeterminado;
}