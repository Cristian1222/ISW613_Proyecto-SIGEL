<?php

declare(strict_types=1);

// Carpeta donde se almacenan las imágenes.
const EQUIPOS_IMAGENES_DIR = __DIR__ . '/../storage/equipos';

// Tamaño máximo permitido: 2 MB.
const EQUIPOS_IMAGENES_MAX_BYTES = 2 * 1024 * 1024;

// Dimensiones máximas permitidas.
const EQUIPOS_IMAGENES_MAX_WIDTH = 6000;
const EQUIPOS_IMAGENES_MAX_HEIGHT = 6000;
const EQUIPOS_IMAGENES_MAX_PIXELS = 20000000;

// Formatos de imagen permitidos.
const EQUIPOS_IMAGENES_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

/**
 * Guarda una imagen de equipo.
 *
 * @return array ['filename' => string] o ['error' => string]
 */
function guardarImagenEquipo(array $archivo): array
{
    if (
        !isset($archivo['error']) ||
        !is_int($archivo['error'])
    ) {
        return ['error' => 'invalid_image'];
    }

    if (
        $archivo['error'] === UPLOAD_ERR_INI_SIZE ||
        $archivo['error'] === UPLOAD_ERR_FORM_SIZE
    ) {
        return ['error' => 'image_too_large'];
    }

    if (
        $archivo['error'] !== UPLOAD_ERR_OK ||
        !isset($archivo['tmp_name']) ||
        !is_string($archivo['tmp_name']) ||
        !is_uploaded_file($archivo['tmp_name'])
    ) {
        return ['error' => 'image_upload_failed'];
    }

    if (
        !isset($archivo['size']) ||
        !is_int($archivo['size']) ||
        $archivo['size'] <= 0
    ) {
        return ['error' => 'invalid_image'];
    }

    if ($archivo['size'] > EQUIPOS_IMAGENES_MAX_BYTES) {
        return ['error' => 'image_too_large'];
    }

    // Detectar el tipo real del archivo.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($archivo['tmp_name']);

    if (
        !is_string($mimeType) ||
        !isset(EQUIPOS_IMAGENES_TYPES[$mimeType])
    ) {
        return ['error' => 'invalid_image'];
    }

    // Comprobar que sea una imagen válida.
    $informacionImagen = @getimagesize($archivo['tmp_name']);

    if ($informacionImagen === false) {
        return ['error' => 'invalid_image'];
    }

    $ancho = $informacionImagen[0];
    $alto = $informacionImagen[1];

    // Verificar las dimensiones de la imagen.
    if (
        $ancho <= 0 ||
        $alto <= 0 ||
        $ancho > EQUIPOS_IMAGENES_MAX_WIDTH ||
        $alto > EQUIPOS_IMAGENES_MAX_HEIGHT ||
        $ancho > intdiv(EQUIPOS_IMAGENES_MAX_PIXELS, $alto)
    ) {
        return ['error' => 'invalid_image'];
    }

    // Comprobar que el MIME detectado coincida con la imagen.
    if (
        !isset($informacionImagen['mime']) ||
        $informacionImagen['mime'] !== $mimeType
    ) {
        return ['error' => 'invalid_image'];
    }

    // Crear la carpeta si no existe.
    if (
        !is_dir(EQUIPOS_IMAGENES_DIR) &&
        !mkdir(EQUIPOS_IMAGENES_DIR, 0750, true) &&
        !is_dir(EQUIPOS_IMAGENES_DIR)
    ) {
        error_log('No se pudo crear la carpeta de imágenes de equipos.');
        return ['error' => 'image_upload_failed'];
    }

    // Generar un nombre aleatorio.
    try {
        $nombreArchivo = bin2hex(random_bytes(16))
            . '.'
            . EQUIPOS_IMAGENES_TYPES[$mimeType];
    } catch (Throwable $e) {
        error_log('No se pudo generar el nombre de la imagen.');
        return ['error' => 'image_upload_failed'];
    }

    $destino = EQUIPOS_IMAGENES_DIR . '/' . $nombreArchivo;

    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        error_log('No se pudo almacenar la imagen del equipo.');
        return ['error' => 'image_upload_failed'];
    }

    return ['filename' => $nombreArchivo];
}

/**
 * Elimina una imagen almacenada.
 */
function eliminarImagenEquipo(?string $nombreArchivo): void
{
    if ($nombreArchivo === null || $nombreArchivo === '') {
        return;
    }

    // Aceptar únicamente nombres generados por el sistema.
    if (
        !preg_match(
            '/\A[a-f0-9]{32}\.(jpg|png|webp)\z/',
            $nombreArchivo
        )
    ) {
        return;
    }

    $ruta = EQUIPOS_IMAGENES_DIR . '/' . $nombreArchivo;

    if (is_file($ruta)) {
        if (!@unlink($ruta)) {
            error_log('No se pudo eliminar una imagen de equipo.');
        }
    }
}

/**
 * Devuelve la ruta relativa para mostrar una imagen.
 */
function obtenerUrlImagenEquipo(?string $nombreArchivo): ?string
{
    if ($nombreArchivo === null || $nombreArchivo === '') {
        return null;
    }

    if (
        !preg_match(
            '/\A[a-f0-9]{32}\.(jpg|png|webp)\z/',
            $nombreArchivo
        )
    ) {
        return null;
    }

    return '../imagen_equipo.php?archivo=' . rawurlencode($nombreArchivo);
}