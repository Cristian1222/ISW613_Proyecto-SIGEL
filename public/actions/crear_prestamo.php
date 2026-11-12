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
$horaInicio = trim((string) obtenerPost('hora_inicio', ''));
$fechaDevolucion = trim((string) obtenerPost('fecha_devolucion_prevista', ''));
$observaciones = trim((string) obtenerPost('observaciones', ''));

// Validar las fechas.
$fechaInicioObj = DateTime::createFromFormat('!Y-m-d', $fechaInicio);
$fechaDevolucionObj = DateTime::createFromFormat('!Y-m-d', $fechaDevolucion);

// Validar la hora.
$horaInicioObj = DateTime::createFromFormat('!H:i', $horaInicio);

$fechasValidas =
    $fechaInicioObj !== false &&
    $fechaInicioObj->format('Y-m-d') === $fechaInicio &&
    $fechaDevolucionObj !== false &&
    $fechaDevolucionObj->format('Y-m-d') === $fechaDevolucion;

$horaValida =
    $horaInicioObj !== false &&
    $horaInicioObj->format('H:i') === $horaInicio;

// Comprobar que la fecha y hora de inicio no estén en el pasado.
$inicioPrestamo = false;

if ($fechasValidas && $horaValida) {
    $inicioPrestamo = DateTime::createFromFormat(
        '!Y-m-d H:i',
        $fechaInicio . ' ' . $horaInicio
    );
}

$ahora = new DateTime(date('Y-m-d H:i'));

$inicioValido =
    $inicioPrestamo !== false &&
    $inicioPrestamo >= $ahora;

// Validar todos los campos.
if (
    $profesorId === false ||
    $equipoId === false ||
    $cantidad === false ||
    !$fechasValidas ||
    !$horaValida ||
    !$inicioValido ||
    $fechaDevolucion < $fechaInicio ||
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
            hora_inicio,
            fecha_devolucion_prevista,
            estado,
            observaciones
        ) VALUES (
            :operador_id,
            :profesor_id,
            NOW(),
            :fecha_inicio,
            :hora_inicio,
            :fecha_devolucion_prevista,
            'pendiente',
            :observaciones
        )
    ");

    $insertarPrestamo->execute([
        'operador_id' => $_SESSION['usuario']['id'],
        'profesor_id' => $profesorId,
        'fecha_inicio' => $fechaInicio,
        'hora_inicio' => $horaInicio,
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

    // Registrar el error original.
    error_log('Error al crear préstamo: ' . $e->getMessage());

    // Revertir la transacción si sigue activa.
    try {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    } catch (Throwable $errorRollback) {
        error_log('Error al revertir la transacción: ' . $errorRollback->getMessage());
    }

    redirigir('../pages/crear_prestamo.php?error=server_error');
}