<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requerirAutenticacion();
requerirRol(['admin']);

require_once __DIR__ . '/../../config/database.php';

try {
    $consulta = $pdo->query(
        "SELECT
            p.id AS prestamo_id,
            profesor.nombre AS profesor,
            operador.nombre AS operador,
            e.codigo,
            e.nombre AS equipo,
            c.nombre AS categoria,
            dp.cantidad,
            p.fecha_inicio,
            p.fecha_devolucion_prevista,
            p.fecha_devolucion_real,
            p.estado
        FROM detalle_prestamo dp
        INNER JOIN prestamos p
            ON p.id = dp.prestamo_id
        INNER JOIN equipos e
            ON e.id = dp.equipo_id
        INNER JOIN categorias c
            ON c.id = e.categoria_id
        INNER JOIN usuarios profesor
            ON profesor.id = p.profesor_id
        INNER JOIN usuarios operador
            ON operador.id = p.operador_id
        ORDER BY p.id DESC, e.nombre ASC"
    );

    $detalles = $consulta->fetchAll(PDO::FETCH_ASSOC);

    $nombreArchivo = 'detalle_prestamos_' . date('Y-m-d') . '.csv';

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

    fputcsv($salida, [
        'Número de préstamo',
        'Profesor',
        'Operador',
        'Código del equipo',
        'Equipo',
        'Categoría',
        'Cantidad',
        'Fecha de inicio',
        'Devolución prevista',
        'Devolución real',
        'Estado'
    ], ';');

    foreach ($detalles as $detalle) {
        fputcsv($salida, [
            $detalle['prestamo_id'],
            $detalle['profesor'],
            $detalle['operador'],
            $detalle['codigo'],
            $detalle['equipo'],
            $detalle['categoria'],
            $detalle['cantidad'],
            $detalle['fecha_inicio'],
            $detalle['fecha_devolucion_prevista'],
            $detalle['fecha_devolucion_real'],
            $detalle['estado']
        ], ';');
    }

    fclose($salida);
    exit;

} catch (Throwable $e) {
    error_log(
        'Error al generar el detalle de préstamos: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('No se pudo generar el detalle de préstamos.');
}