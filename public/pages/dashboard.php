<?php

declare(strict_types=1);

session_start();

// Solo usuarios autenticados pueden entrar.
if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';

$usuario = $_SESSION['usuario'];
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Panel principal de SIGEL</h1>
            <p class="text-muted mb-0">
                Bienvenido/a, <?= htmlspecialchars(
                    $usuario['nombre'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        </div>

        <form action="../actions/logout.php" method="POST">
    <button type="submit" class="btn btn-outline-danger">
        Cerrar sesión
    </button>
</form>
    </div>

    <div class="col-md-4">
    <div class="card h-100 shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Equipos</h5>
            <p class="card-text">
                Consulta y administra los equipos registrados.
            </p>

            <a href="equipos.php" class="btn btn-primary mt-3">
                Administrar equipos
            </a>
        </div>
    </div>
</div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Préstamos</h5>
                    <p class="card-text">
                        Gestiona las solicitudes y devoluciones de equipos.
                    </p>
                    <span class="badge text-bg-secondary">
                        Próximamente
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Usuarios</h5>
                    <p class="card-text">
                        Gestiona las cuentas y los roles del sistema.
                    </p>
                    <span class="badge text-bg-secondary">
                        Próximamente
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <strong>Tu rol:</strong>
        <?= htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>