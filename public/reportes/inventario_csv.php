<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo usuarios autenticados pueden acceder.
requerirAutenticacion();

// Solo administradores pueden descargar reportes.
requerirRol(['admin']);

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

try {
    $consulta = $pdo->query(
        "SELECT
            e.codigo,
            e.nombre,
            c.nombre AS categoria,
            e.descripcion,
            e.cantidad_total,
            e.cantidad_disponible,
            e.estado
         FROM equipos e
         INNER JOIN categorias c ON c.id = e.categoria_id
         ORDER BY e.nombre ASC"
    );

    $equipos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    // Nombre del archivo descargado.
    $nombreArchivo = 'inventario_equipos_' . date('Y-m-d') . '.csv';

    // Preparar la descarga.
    header('Content-Type: text/csv; charset=UTF-8');
    header(
        'Content-Disposition: attachment; filename="' .
        $nombreArchivo . '"'
    );

    // Abrir la salida para generar el CSV.
    $salida = fopen('php://output', 'w');

    if ($salida === false) {
        throw new RuntimeException('No se pudo generar el archivo CSV.');
    }

    // BOM para facilitar la lectura de tildes en Excel.
    fwrite($salida, "\xEF\xBB\xBF");

    // Encabezados.
    fputcsv($salida, [
        'Código',
        'Equipo',
        'Categoría',
        'Descripción',
        'Cantidad total',
        'Cantidad disponible',
        'Estado'
    ], ';');

    // Datos de los equipos.
    foreach ($equipos as $equipo) {
        fputcsv($salida, [
            $equipo['codigo'],
            $equipo['nombre'],
            $equipo['categoria'],
            $equipo['descripcion'],
            $equipo['cantidad_total'],
            $equipo['cantidad_disponible'],
            $equipo['estado']
        ], ';');
    }

    fclose($salida);
    exit;

} catch (Throwable $e) {
    error_log('Error al generar el reporte de inventario: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo generar el reporte de inventario.');
}