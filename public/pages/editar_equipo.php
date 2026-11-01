<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/imagenes.php';

// Solo el administrador puede editar equipos.
requerirRol(['admin']);

// Obtener y validar el ID del equipo.
$id = filter_var(
    obtenerPost('id', obtenerGet('id')),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    redirigir('equipos.php?error=invalid_request');
}

// Si se envió el formulario, verificar el token CSRF.
if (esPost()) {
    requerirTokenCSRF();
}

// Conectar con la base de datos.
require_once __DIR__ . '/../../config/database.php';

$errores = [];

try {
    $consultaEquipo = $pdo->prepare(
        'SELECT id, categoria_id, codigo, nombre, descripcion,
                cantidad_total, cantidad_disponible, estado, imagen
         FROM equipos
         WHERE id = :id'
    );

    $consultaEquipo->execute(['id' => $id]);
    $equipo = $consultaEquipo->fetch();

    if (!$equipo) {
        redirigir('equipos.php?error=equipment_not_found');
    }

    $consultaCategorias = $pdo->query(
        'SELECT id, nombre
         FROM categorias
         WHERE activo = 1
         ORDER BY nombre'
    );

    $categorias = $consultaCategorias->fetchAll();

    // Cargar los datos actuales del equipo.
    $codigo = $equipo['codigo'];
    $nombre = $equipo['nombre'];
    $categoriaId = (string) $equipo['categoria_id'];
    $descripcion = $equipo['descripcion'] ?? '';
    $cantidadTotal = (string) $equipo['cantidad_total'];
    $cantidadDisponible = (string) $equipo['cantidad_disponible'];
    $estado = $equipo['estado'];

    // Procesar el formulario.
    if (esPost()) {
        $codigo = trim((string) obtenerPost('codigo', ''));
        $nombre = trim((string) obtenerPost('nombre', ''));
        $categoriaId = (string) obtenerPost('categoria_id', '');
        $descripcion = trim((string) obtenerPost('descripcion', ''));
        $cantidadTotal = (string) obtenerPost('cantidad_total', '');
        $cantidadDisponible = (string) obtenerPost('cantidad_disponible', '');
        $estado = (string) obtenerPost('estado', '');

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

        // Mantener la imagen anterior si no se selecciona una nueva.
        $nombreImagenNueva = null;
        $archivoImagen = $_FILES['imagen'] ?? null;

        if (
            $archivoImagen !== null &&
            $archivoImagen['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $resultadoImagen = guardarImagenEquipo($archivoImagen);

            if (isset($resultadoImagen['error'])) {
                $mensajesImagen = [
                    'invalid_image' => 'El archivo seleccionado no es una imagen válida. Usa JPG, PNG o WebP.',
                    'image_too_large' => 'La imagen supera el tamaño máximo permitido de 2 MB.',
                    'image_upload_failed' => 'No se pudo subir la imagen. Inténtalo nuevamente.',
                ];

                $errores[] = $mensajesImagen[$resultadoImagen['error']]
                    ?? 'Ocurrió un problema al subir la imagen.';
            } else {
                $nombreImagenNueva = $resultadoImagen['filename'];
            }
        }

        // Actualizar si no existen errores.
        if ($errores === []) {
            $imagenFinal = $nombreImagenNueva ?? $equipo['imagen'];

            $sql = 'UPDATE equipos
                    SET categoria_id = :categoria_id,
                        codigo = :codigo,
                        nombre = :nombre,
                        descripcion = :descripcion,
                        cantidad_total = :cantidad_total,
                        cantidad_disponible = :cantidad_disponible,
                        estado = :estado,
                        imagen = :imagen
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
                    'imagen' => $imagenFinal,
                    'id' => $id,
                ]);

                // Eliminar la imagen anterior solo después de actualizar la BD.
                if (
                    $nombreImagenNueva !== null &&
                    $equipo['imagen'] !== null
                ) {
                    eliminarImagenEquipo($equipo['imagen']);
                }

                redirigir('equipos.php?success=equipment_updated');

            } catch (PDOException $e) {
                // Si falla la actualización, conservar la imagen anterior.
                if ($nombreImagenNueva !== null) {
                    eliminarImagenEquipo($nombreImagenNueva);
                }

                if ($e->getCode() === '23000') {
                    $errores[] = 'Ese código ya está registrado en otro equipo.';
                } else {
                    throw $e;
                }
            }
        } elseif ($nombreImagenNueva !== null) {
            // Si hay errores de validación, eliminar la imagen recién subida.
            eliminarImagenEquipo($nombreImagenNueva);
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Ocurrió un error al consultar o actualizar el equipo.');
}

$titulo = 'Editar equipo | SIGEL';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/alerts.php';

$tokenCSRF = generarTokenCSRF();
$urlImagenActual = obtenerUrlImagenEquipo($equipo['imagen']);
?>

<div class="container py-5">
    <h1 class="h3 mb-2">Editar equipo</h1>

    <p class="text-muted mb-4">
        Modifica los datos del equipo seleccionado.
    </p>

    <?php renderAlert(); ?>

    <?php if ($errores !== []): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= escapar($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <form
                method="POST"
                action="editar_equipo.php"
                enctype="multipart/form-data"
            >

                <input type="hidden" name="id" value="<?= (int) $id ?>">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($tokenCSRF) ?>"
                >

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
                            value="<?= escapar($codigo) ?>"
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
                            value="<?= escapar($nombre) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="categoria_id" class="form-label">Categoría *</label>

                        <select
                            class="form-select"
                            id="categoria_id"
                            name="categoria_id"
                            required
                        >
                            <?php foreach ($categorias as $categoria): ?>
                                <option
                                    value="<?= (int) $categoria['id'] ?>"
                                    <?= (string) $categoriaId === (string) $categoria['id'] ? 'selected' : '' ?>
                                >
                                    <?= escapar($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="estado" class="form-label">Estado *</label>

                        <select
                            class="form-select"
                            id="estado"
                            name="estado"
                            required
                        >
                            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>
                                Activo
                            </option>

                            <option value="mantenimiento" <?= $estado === 'mantenimiento' ? 'selected' : '' ?>>
                                Mantenimiento
                            </option>

                            <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>
                                Inactivo
                            </option>
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
                            value="<?= escapar($cantidadTotal) ?>"
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
                            value="<?= escapar($cantidadDisponible) ?>"
                        >
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>

                        <textarea
                            class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                        ><?= escapar($descripcion) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Imagen actual</label>

                        <div class="mb-3">
                            <?php if ($urlImagenActual !== null): ?>
                                <img
                                    src="<?= escapar($urlImagenActual) ?>"
                                    alt="Imagen actual de <?= escapar($nombre) ?>"
                                    style="width: 150px; height: 120px; object-fit: cover; border-radius: 6px;"
                                >
                            <?php else: ?>
                                <p class="text-muted">Este equipo no tiene una imagen registrada.</p>
                            <?php endif; ?>
                        </div>

                        <label for="imagen" class="form-label">Cambiar imagen</label>

                        <input
                            type="file"
                            class="form-control"
                            id="imagen"
                            name="imagen"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <div class="form-text">
                            Formatos permitidos: JPG, PNG y WebP. Tamaño máximo: 2 MB.
                            Si no seleccionas otra imagen, se conservará la actual.
                        </div>
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