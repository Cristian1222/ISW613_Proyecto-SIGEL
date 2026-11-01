<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/imagenes.php';

requerirAutenticacion();

$archivo = $_GET['archivo'] ?? '';

if (
    !is_string($archivo) ||
    !preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp)\z/', $archivo)
) {
    http_response_code(404);
    exit('Imagen no encontrada.');
}

$ruta = EQUIPOS_IMAGENES_DIR . '/' . $archivo;

if (!is_file($ruta) || !is_readable($ruta)) {
    http_response_code(404);
    exit('Imagen no encontrada.');
}

$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);

$tiposPermitidos = [
    'image/jpeg',
    'image/png',
    'image/webp'
];

if (
    !is_string($mimeType) ||
    !in_array($mimeType, $tiposPermitidos, true) ||
    getimagesize($ruta) === false
) {
    http_response_code(404);
    exit('Imagen no válida.');
}

header('Content-Type: ' . $mimeType);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

readfile($ruta);
exit;