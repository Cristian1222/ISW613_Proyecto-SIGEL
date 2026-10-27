-- 1. USUARIOS
CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'operador', 'profesor') NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. CATEGORIAS
CREATE TABLE categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- 3. EQUIPOS
CREATE TABLE equipos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    cantidad_total INT UNSIGNED NOT NULL DEFAULT 1,
    cantidad_disponible INT UNSIGNED NOT NULL DEFAULT 1,
    imagen VARCHAR(255) NULL,
    estado ENUM(
        'activo',
        'mantenimiento',
        'inactivo'
    ) NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_equipos_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_cantidades
        CHECK (cantidad_disponible <= cantidad_total)
) ENGINE=InnoDB;

-- 4. PRESTAMOS
CREATE TABLE prestamos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operador_id INT UNSIGNED NOT NULL,
    profesor_id INT UNSIGNED NOT NULL,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio DATE NOT NULL,
    fecha_devolucion_prevista DATE NOT NULL,
    fecha_devolucion_real DATE NULL,
    estado ENUM(
        'pendiente',
        'aprobado',
        'entregado',
        'devuelto',
        'rechazado',
        'cancelado'
    ) NOT NULL DEFAULT 'pendiente',
    observaciones TEXT NULL,

    CONSTRAINT fk_prestamos_operador
        FOREIGN KEY (operador_id)
        REFERENCES usuarios(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_prestamos_profesor
        FOREIGN KEY (profesor_id)
        REFERENCES usuarios(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_fechas_prestamo
        CHECK (
            fecha_devolucion_prevista >= fecha_inicio
        )
) ENGINE=InnoDB;

-- 5. DETALLE DE LOS EQUIPOS DE CADA PRESTAMO
CREATE TABLE detalle_prestamo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT UNSIGNED NOT NULL,
    equipo_id INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,

    CONSTRAINT fk_detalle_prestamo
        FOREIGN KEY (prestamo_id)
        REFERENCES prestamos(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_detalle_equipo
        FOREIGN KEY (equipo_id)
        REFERENCES equipos(id)
        ON DELETE RESTRICT,

    CONSTRAINT uq_prestamo_equipo
        UNIQUE (prestamo_id, equipo_id),

    CONSTRAINT chk_cantidad_prestamo
        CHECK (cantidad > 0)
) ENGINE=InnoDB;

-- CATEGORIAS INICIALES DE EJEMPLO
INSERT INTO categorias (nombre, descripcion)
VALUES
    ('Computadoras',
     'Computadoras portatiles y de escritorio'),
    ('Proyectores',
     'Equipos de proyeccion'),
    ('Accesorios',
     'Teclados, ratones y otros accesorios');