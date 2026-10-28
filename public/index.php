<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../includes/header.php';

// Si ya inició sesión, ir al panel principal.
if (isset($_SESSION['usuario'])) {
    header('Location: pages/dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="text-center mb-4">
        <h1>SIGEL</h1>
        <p>Sistema de Gestión de Equipos y Préstamos</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h4>Iniciar sesión</h4>
                </div>

                <div class="card-body">

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            No se pudo iniciar sesión.
                            Verifica tu correo y contraseña.
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['inactive'])): ?>
                        <div class="alert alert-warning">
                            Esta cuenta está desactivada.
                            Contacta al administrador.
                        </div>
                    <?php endif; ?>

                    <form action="actions/login.php" method="POST">
                        <div class="mb-3">
                            <label for="correo" class="form-label">
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                id="correo"
                                name="correo"
                                maxlength="150"
                                autocomplete="username"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Contraseña
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Iniciar sesión
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>