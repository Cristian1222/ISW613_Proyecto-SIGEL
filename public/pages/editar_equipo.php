<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$id = filter_var(
    $_POST['id'] ?? $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    header('Location: equipos.php');
    exit;
}

$errores = [];
$guardado = false;

try {
    $consultaEquipo = $pdo->prepare(
        'SELECT id, categoria_id, codigo, nombre, descripcion,
                cantidad_total, cantidad_disponible, estado
         FROM equipos
         WHERE id = :id'
    );
    $consultaEquipo->execute(['id' => $id]);
    $equipo = $consultaEquipo->fetch();

    if (!$equipo) {
        http_response_code(404);
        exit('El equipo solicitado no existe.');
    }

    $consultaCategorias = $pdo->query(
        'SELECT id, nombre
         FROM categorias
         WHERE activo = 1
         ORDER BY nombre'
    );
    $categorias = $consultaCategorias->fetchAll();

    $codigo = $equipo['codigo'];
    $nombre = $equipo['nombre'];
    $categoriaId = (string) $equipo['categoria_id'];
    $descripcion = $equipo['descripcion'] ?? '';
    $cantidadTotal = (string) $equipo['cantidad_total'];
    $cantidadDisponible = (string) $equipo['cantidad_disponible'];
    $estado = $equipo['estado'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $categoriaId = $_POST['categoria_id'] ?? '';
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cantidadTotal = $_POST['cantidad_total'] ?? '';
        $cantidadDisponible = $_POST['cantidad_disponible'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if ($codigo === '' || strlen($codigo) > 50) {
            $errores[] = 'El código es obligatorio y no debe superar 50 caracteres.';
        }

        if ($nombre === '' || strlen($nombre) > 150) {
            $errores[] = 'El nombre es obligatorio y no debe superar 150 caracteres.';
        }

        $categoriaValida = filter_var($categoriaId, FILTER_VALIDATE_INT);
        $idsCategorias = array_map(
            'intval',
            array_column($categorias, 'id')
        );

        // Permitir conservar la categoría actual aunque se haya desactivado.
        if (!in_array((int) $equipo['categoria_id'], $idsCategorias, true)) {
            $idsCategorias[] = (int) $equipo['categoria_id'];
        }

        if (
            $categoriaValida === false ||
            !in_array((int) $categoriaId, $idsCategorias, true)
        ) {
            $errores[] = 'Selecciona una categoría válida.';
        }

        if (!in_array($estado, ['activo', 'mantenimiento', 'inactivo'], true)) {
            $errores[] = 'Selecciona un estado válido.';
        }

        $totalValido = filter_var(
            $cantidadTotal,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $disponibleValido = filter_var(
            $cantidadDisponible,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if ($totalValido === false) {
            $errores[] = 'La cantidad total debe ser al menos 1.';
        }

        if ($disponibleValido === false) {
            $errores[] = 'La cantidad disponible no puede ser negativa.';
        }

        if (
            $totalValido !== false &&
            $disponibleValido !== false &&
            $disponibleValido > $totalValido
        ) {
            $errores[] = 'La cantidad disponible no puede superar la cantidad total.';
        }

        if ($errores === []) {
            $sql = 'UPDATE equipos
                    SET categoria_id = :categoria_id,
                        codigo = :codigo,
                        nombre = :nombre,
                        descripcion = :descripcion,
                        cantidad_total = :cantidad_total,
                        cantidad_disponible = :cantidad_disponible,
                        estado = :estado
                    WHERE id = :id';

            try {
                $actualizar = $pdo->prepare($sql);
                $actualizar->execute([
                    'categoria_id' => (int) $categoriaId,
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'cantidad_total' => (int) $cantidadTotal,
                    'cantidad_disponible' => (int) $cantidadDisponible,
                    'estado' => $estado,
                    'id' => $id,
                ]);

                header('Location: equipos.php?actualizado=1');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errores[] = 'Ese código ya está registrado en otro equipo.';
                } else {
                    throw $e;
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Ocurrió un error al consultar o actualizar el equipo.');
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5">
    <h1 class="h3 mb-2">Editar equipo</h1>
    <p class="text-muted mb-4">
        Modifica los datos del equipo seleccionado.
    </p>

    <?php if ($errores !== []): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="editar_equipo.php">
                <input type="hidden" name="id" value="<?= (int) $id ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="codigo" class="form-label">Código *</label>
                        <input
                            type="text"
                            class="form-control"
                            id="codigo"
                            name="codigo"
                            maxlength="50"
                            required
                            value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre *</label>
                        <input
                            type="text"
                            class="form-control"
                            id="nombre"
                            name="nombre"
                            maxlength="150"
                            required
                            value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="categoria_id" class="form-label">Categoría *</label>
                        <select class="form-select" id="categoria_id" name="categoria_id" required>
                            <?php foreach ($categorias as $categoria): ?>
                                <option
                                    value="<?= (int) $categoria['id'] ?>"
                                    <?= (string) $categoriaId === (string) $categoria['id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="estado" class="form-label">Estado *</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="mantenimiento" <?= $estado === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                            <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="cantidad_total" class="form-label">Cantidad total *</label>
                        <input
                            type="number"
                            class="form-control"
                            id="cantidad_total"
                            name="cantidad_total"
                            min="1"
                            required
                            value="<?= htmlspecialchars((string) $cantidadTotal, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="cantidad_disponible" class="form-label">Cantidad disponible *</label>
                        <input
                            type="number"
                            class="form-control"
                            id="cantidad_disponible"
                            name="cantidad_disponible"
                            min="0"
                            required
                            value="<?= htmlspecialchars((string) $cantidadDisponible, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea
                            class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                        ><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>

                    <a href="equipos.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>