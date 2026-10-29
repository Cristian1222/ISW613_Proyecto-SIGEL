<?php

declare(strict_types=1);

const MESSAGES = [
    'error' => [
        'invalid_input' => 'Complete los campos correctamente.',
        'invalid_credentials' => 'Usuario o contraseña incorrectos.',
        'login_required' => 'Debe iniciar sesión para acceder a esa página.',
        'session_expired' => 'La sesión expiró. Inicie sesión nuevamente.',
        'server_error' => 'Ocurrió un error. Inténtelo más tarde.',
        'invalid_request' => 'La solicitud no es válida.',
        'equipment_not_found' => 'No se encontró el equipo solicitado.',
        'duplicate_code' => 'Ese código de equipo ya está registrado.',
        'equipment_related' => 'No se puede eliminar el equipo porque tiene registros relacionados, posiblemente préstamos.',
        'forbidden' => 'No tiene permisos para realizar esta acción.',
        'duplicate_email' => 'Ese correo electrónico ya está registrado.',
    ],
    'success' => [
        'equipment_created' => 'El equipo se registró correctamente.',
        'equipment_updated' => 'El equipo se actualizó correctamente.',
        'equipment_deleted' => 'El equipo se eliminó correctamente.',
        'logged_out' => 'Sesión cerrada correctamente.',
        'user_created' => 'El usuario se registró correctamente.',
    ],
];

function renderAlert(): void
{
    foreach (['error' => 'danger', 'success' => 'success'] as $type => $style) {
        $code = $_GET[$type] ?? null;

        if (is_string($code) && isset(MESSAGES[$type][$code])) {
            echo '<div class="alert alert-' . $style . ' alert-dismissible fade show" role="alert">'
                . htmlspecialchars(
                    MESSAGES[$type][$code],
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>'
                . '</div>';
        }
    }
}