<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?error=login_required');
    exit;
}

$rol = $_SESSION['usuario']['rol'] ?? '';

if (!in_array($rol, ['admin', 'operador'], true)) {
    header('Location: ../pages/prestamos.php?error=forbidden');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/crear_prestamo.php?error=invalid_request');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

if (
    !isset($_SESSION['csrf_token'], $_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])
) {
    header('Location: ../pages/crear_prestamo.php?error=invalid_request');
    exit;
}

$profesorId = filter_var(
    $_POST['profesor_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$equipoId = filter_var(
    $_POST['equipo_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$cantidad = filter_var(
    $_POST['cantidad'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$fechaInicio = trim((string) ($_POST['fecha_inicio'] ?? ''));
$fechaDevolucion = trim((string) ($_POST['fecha_devolucion_prevista'] ?? ''));
$observaciones = trim((string) ($_POST['observaciones'] ?? ''));

$fechaInicioObj = DateTime::createFromFormat('!Y-m-d', $fechaInicio);
$fechaDevolucionObj = DateTime::createFromFormat('!Y-m-d', $fechaDevolucion);

$fechasValidas =
    $fechaInicioObj !== false &&
    $fechaInicioObj->format('Y-m-d') === $fechaInicio &&
    $fechaDevolucionObj !== false &&
    $fechaDevolucionObj->format('Y-m-d') === $fechaDevolucion;

if (
    $profesorId === false ||
    $equipoId === false ||
    $cantidad === false ||
    !$fechasValidas ||
    $fechaInicio < date('Y-m-d') ||
    $fechaDevolucion <= $fechaInicio ||
    mb_strlen($observaciones) > 2000
) {
    header('Location: ../pages/crear_prestamo.php?error=invalid_input');
    exit;
}

try {
    $pdo->beginTransaction();

    // Comprobar que el profesor exista y esté activo.
    $consultaProfesor = $pdo->prepare(
        "SELECT id
         FROM usuarios
         WHERE id = :id
           AND rol = 'profesor'
           AND activo = 1"
    );

    $consultaProfesor->execute(['id' => $profesorId]);

    if (!$consultaProfesor->fetch()) {
        $pdo->rollBack();
        header('Location: ../pages/crear_prestamo.php?error=invalid_input');
        exit;
    }

    // Bloquear el equipo durante la validación.
    $consultaEquipo = $pdo->prepare(
        "SELECT id, cantidad_disponible
         FROM equipos
         WHERE id = :id
           AND estado = 'activo'
         FOR UPDATE"
    );

    $consultaEquipo->execute(['id' => $equipoId]);
    $equipo = $consultaEquipo->fetch(PDO::FETCH_ASSOC);

    if (!$equipo || $cantidad > (int) $equipo['cantidad_disponible']) {
        $pdo->rollBack();
        header('Location: ../pages/crear_prestamo.php?error=invalid_input');
        exit;
    }

    // Registrar la solicitud como pendiente.
    $insertarPrestamo = $pdo->prepare(
        "INSERT INTO prestamos (
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
        )"
    );

    $insertarPrestamo->execute([
        'operador_id' => $_SESSION['usuario']['id'],
        'profesor_id' => $profesorId,
        'fecha_inicio' => $fechaInicio,
        'fecha_devolucion_prevista' => $fechaDevolucion,
        'observaciones' => $observaciones !== '' ? $observaciones : null
    ]);

    $prestamoId = (int) $pdo->lastInsertId();

    // Registrar el equipo solicitado.
    $insertarDetalle = $pdo->prepare(
        "INSERT INTO detalle_prestamo (
            prestamo_id,
            equipo_id,
            cantidad
        ) VALUES (
            :prestamo_id,
            :equipo_id,
            :cantidad
        )"
    );

    $insertarDetalle->execute([
        'prestamo_id' => $prestamoId,
        'equipo_id' => $equipoId,
        'cantidad' => $cantidad
    ]);

    $pdo->commit();

    header('Location: ../pages/prestamos.php?success=loan_created');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/crear_prestamo.php?error=server_error');
    exit;
}