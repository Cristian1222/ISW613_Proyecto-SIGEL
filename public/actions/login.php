<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$correo = strtolower(trim($_POST['correo'] ?? ''));
$password = $_POST['password'] ?? '';

if (
    !is_string($correo) ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    !is_string($password) ||
    $password === ''
) {
    header('Location: ../index.php?error=invalid_input');
    exit;
}

try {
    $pdo->beginTransaction();

    // Crear el registro de intentos si todavía no existe.
    $insertar = $pdo->prepare(
        'INSERT INTO intentos_login (correo, intentos_fallidos, ultimo_intento)
         VALUES (:correo, 0, NOW())
         ON DUPLICATE KEY UPDATE correo = VALUES(correo)'
    );

    $insertar->execute(['correo' => $correo]);

    // Bloquear el registro mientras se procesa el intento.
    $consultaIntentos = $pdo->prepare(
        'SELECT intentos_fallidos, bloqueado_hasta
         FROM intentos_login
         WHERE correo = :correo
         FOR UPDATE'
    );

    $consultaIntentos->execute(['correo' => $correo]);
    $intentos = $consultaIntentos->fetch(PDO::FETCH_ASSOC);

    // Comprobar si el usuario todavía está bloqueado.
    if (
        !empty($intentos['bloqueado_hasta']) &&
        strtotime($intentos['bloqueado_hasta']) > time()
    ) {
        $pdo->rollBack();

        header('Location: ../index.php?error=login_locked');
        exit;
    }

    // Si el bloqueo ya terminó, reiniciar el contador.
    if (!empty($intentos['bloqueado_hasta'])) {
        $reiniciar = $pdo->prepare(
            'UPDATE intentos_login
             SET intentos_fallidos = 0,
                 bloqueado_hasta = NULL
             WHERE correo = :correo'
        );

        $reiniciar->execute(['correo' => $correo]);
        $intentos['intentos_fallidos'] = 0;
    }

    // Buscar al usuario.
    $consultaUsuario = $pdo->prepare(
        'SELECT id, nombre, correo, password_hash, rol, activo
         FROM usuarios
         WHERE correo = :correo
         LIMIT 1'
    );

    $consultaUsuario->execute(['correo' => $correo]);
    $usuario = $consultaUsuario->fetch(PDO::FETCH_ASSOC);

    // Verificar las credenciales.
    if (
        $usuario &&
        (int) $usuario['activo'] === 1 &&
        password_verify($password, $usuario['password_hash'])
    ) {
        // Inicio de sesión correcto: eliminar los intentos anteriores.
        $eliminarIntentos = $pdo->prepare(
            'DELETE FROM intentos_login WHERE correo = :correo'
        );

        $eliminarIntentos->execute(['correo' => $correo]);

        $pdo->commit();

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol'],
        ];

        header('Location: ../pages/dashboard.php');
        exit;
    }

    // Credenciales incorrectas: aumentar el contador.
    $cantidadIntentos = (int) $intentos['intentos_fallidos'] + 1;

    if ($cantidadIntentos >= 3) {
        $actualizar = $pdo->prepare(
            'UPDATE intentos_login
             SET intentos_fallidos = 3,
                 bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 5 MINUTE),
                 ultimo_intento = NOW()
             WHERE correo = :correo'
        );

        $actualizar->execute(['correo' => $correo]);

        $pdo->commit();

        header('Location: ../index.php?error=login_locked');
        exit;
    }

    $actualizar = $pdo->prepare(
        'UPDATE intentos_login
         SET intentos_fallidos = :intentos,
             ultimo_intento = NOW()
         WHERE correo = :correo'
    );

    $actualizar->execute([
        'intentos' => $cantidadIntentos,
        'correo' => $correo,
    ]);

    $pdo->commit();

    header('Location: ../index.php?error=invalid_credentials');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    exit('Ocurrió un error al procesar el inicio de sesión.');
}