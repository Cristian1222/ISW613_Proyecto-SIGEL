<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Solo administradores pueden consultar los reportes.
requerirAutenticacion();
requerirRol(['admin']);

$titulo = 'Reportes | SIGEL';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h1>Reportes de SIGEL</h1>

            <p class="text-muted">
                Descarga información del sistema en formato CSV.
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-outline-secondary">
            Volver al panel
        </a>

    </div>

    <div class="row g-4">

        <!-- Reporte de inventario -->
        <div class="col-md-4">

            <div class="card h-100 shadow-sm">

                <div class="card-body">

                    <h5 class="card-title">Inventario de equipos</h5>

                    <p class="card-text">
                        Consulta los equipos, categorías, cantidades
                        y estados del inventario.
                    </p>

                    <a
                        href="../reportes/inventario_csv.php"
                        class="btn btn-success mt-3"
                    >
                        Descargar CSV
                    </a>

                </div>

            </div>

        </div>

        <!-- Reporte de préstamos -->
        <div class="col-md-4">

            <div class="card h-100 shadow-sm">

                <div class="card-body">

                    <h5 class="card-title">Préstamos</h5>

                    <p class="card-text">
                        Consulta los operadores, profesores,
                        fechas y estados de los préstamos.
                    </p>

                    <a
                        href="../reportes/prestamos_csv.php"
                        class="btn btn-success mt-3"
                    >
                        Descargar CSV
                    </a>

                </div>

            </div>

        </div>

        <!-- Reporte de detalle de préstamos -->
        <div class="col-md-4">

            <div class="card h-100 shadow-sm">

                <div class="card-body">

                    <h5 class="card-title">Detalle de préstamos</h5>

                    <p class="card-text">
                        Consulta los equipos y las cantidades
                        asociadas a cada préstamo.
                    </p>

                    <a
                        href="../reportes/detalle_prestamos_csv.php"
                        class="btn btn-success mt-3"
                    >
                        Descargar CSV
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>