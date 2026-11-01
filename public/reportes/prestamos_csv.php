<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo administradores pueden descargar reportes.
requerirAutenticacion();
requerirRol(['admin']);

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

try {
    $consulta = $pdo->query(
        "SELECT
            p.id,
            operador.nombre AS operador,
            profesor.nombre AS profesor,
            p.fecha_solicitud,
            p.fecha_inicio,
            p.fecha_devolucion_prevista,
            p.fecha_devolucion_real,
            p.estado,
            p.observaciones
        FROM prestamos p
        INNER JOIN usuarios operador
            ON operador.id = p.operador_id
        INNER JOIN usuarios profesor
            ON profesor.id = p.profesor_id
        ORDER BY p.id DESC"
    );

    $prestamos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    $nombreArchivo = 'reporte_prestamos_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header(
        'Content-Disposition: attachment; filename="' .
        $nombreArchivo . '"'
    );

    $salida = fopen('php://output', 'w');

    if ($salida === false) {
        throw new RuntimeException('No se pudo generar el archivo CSV.');
    }

    // Compatibilidad con caracteres especiales en Excel.
    fwrite($salida, "\xEF\xBB\xBF");

    // Encabezados del reporte.
    fputcsv($salida, [
        'Número de préstamo',
        'Operador',
        'Profesor',
        'Fecha de solicitud',
        'Fecha de inicio',
        'Devolución prevista',
        'Devolución real',
        'Estado',
        'Observaciones'
    ], ';');

    // Información de los préstamos.
    foreach ($prestamos as $prestamo) {
        fputcsv($salida, [
            $prestamo['id'],
            $prestamo['operador'],
            $prestamo['profesor'],
            $prestamo['fecha_solicitud'],
            $prestamo['fecha_inicio'],
            $prestamo['fecha_devolucion_prevista'],
            $prestamo['fecha_devolucion_real'],
            $prestamo['estado'],
            $prestamo['observaciones']
        ], ';');
    }

    fclose($salida);
    exit;

} catch (Throwable $e) {
    error_log(
        'Error al generar el reporte de préstamos: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('No se pudo generar el reporte de préstamos.');
}