<?php

declare(strict_types=1);

session_start();

// Cargar funciones auxiliares.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Verificar autenticación y permisos.
requerirRol(['admin', 'operador']);

// Permitir únicamente solicitudes POST.
if (!esPost()) {
    redirigir('../pages/crear_prestamo.php?error=invalid_request');
}

// Validar el token CSRF.
requerirTokenCSRF();

// Obtener y validar los datos del formulario.
$profesorId = filter_var(
    obtenerPost('profesor_id'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$equipoId = filter_var(
    obtenerPost('equipo_id'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$cantidad = filter_var(
    obtenerPost('cantidad'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$fechaInicio = trim((string) obtenerPost('fecha_inicio', ''));
$fechaDevolucion = trim((string) obtenerPost('fecha_devolucion_prevista', ''));
$observaciones = trim((string) obtenerPost('observaciones', ''));

// Validar las fechas.
$fechaInicioObj = DateTime::createFromFormat('!Y-m-d', $fechaInicio);
$fechaDevolucionObj = DateTime::createFromFormat('!Y-m-d', $fechaDevolucion);

$fechasValidas =
    $fechaInicioObj !== false &&
    $fechaInicioObj->format('Y-m-d') === $fechaInicio &&
    $fechaDevolucionObj !== false &&
    $fechaDevolucionObj->format('Y-m-d') === $fechaDevolucion;

// Validar todos los campos.
if (
    $profesorId === false ||
    $equipoId === false ||
    $cantidad === false ||
    !$fechasValidas ||
    $fechaInicio < date('Y-m-d') ||
    $fechaDevolucion <= $fechaInicio ||
    mb_strlen($observaciones) > 2000
) {
    redirigir('../pages/crear_prestamo.php?error=invalid_input');
}

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

try {

    $pdo->beginTransaction();

    // Comprobar que el profesor exista y esté activo.
    $consultaProfesor = $pdo->prepare("
        SELECT id
        FROM usuarios
        WHERE id = :id
          AND rol = 'profesor'
          AND activo = 1
    ");

    $consultaProfesor->execute([
        'id' => $profesorId
    ]);

    if (!$consultaProfesor->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        redirigir('../pages/crear_prestamo.php?error=invalid_input');
    }

    // Bloquear el equipo mientras se verifica su inventario.
    $consultaEquipo = $pdo->prepare("
        SELECT id, cantidad_disponible
        FROM equipos
        WHERE id = :id
          AND estado = 'activo'
        FOR UPDATE
    ");

    $consultaEquipo->execute([
        'id' => $equipoId
    ]);

    $equipo = $consultaEquipo->fetch(PDO::FETCH_ASSOC);

    // Verificar que exista el equipo y tenga suficientes unidades.
    if (
        !$equipo ||
        $cantidad > (int) $equipo['cantidad_disponible']
    ) {
        $pdo->rollBack();
        redirigir('../pages/crear_prestamo.php?error=invalid_input');
    }

    // Registrar la solicitud como pendiente.
    $insertarPrestamo = $pdo->prepare("
        INSERT INTO prestamos (
            operador_id,
            profesor_id,
            fecha_solicitud,
            fecha_inicio,
            fecha_devolucion_prevista,
            estado,
            observaciones
        ) VALUES (
            :operador_id,
            :profesor_id,
            NOW(),
            :fecha_inicio,
            :fecha_devolucion_prevista,
            'pendiente',
            :observaciones
        )
    ");

    $insertarPrestamo->execute([
        'operador_id' => $_SESSION['usuario']['id'],
        'profesor_id' => $profesorId,
        'fecha_inicio' => $fechaInicio,
        'fecha_devolucion_prevista' => $fechaDevolucion,
        'observaciones' => $observaciones !== '' ? $observaciones : null
    ]);

    $prestamoId = (int) $pdo->lastInsertId();

    // Registrar el equipo solicitado.
    $insertarDetalle = $pdo->prepare("
        INSERT INTO detalle_prestamo (
            prestamo_id,
            equipo_id,
            cantidad
        ) VALUES (
            :prestamo_id,
            :equipo_id,
            :cantidad
        )
    ");

    $insertarDetalle->execute([
        'prestamo_id' => $prestamoId,
        'equipo_id' => $equipoId,
        'cantidad' => $cantidad
    ]);

    // Confirmar el registro.
    $pdo->commit();

    redirigir('../pages/prestamos.php?success=loan_created');

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    redirigir('../pages/crear_prestamo.php?error=server_error');
}