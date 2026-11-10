-- ==========================================
-- SIGEL - Datos iniciales
-- Base de datos: isw613_proyecto_sigel
-- ==========================================

USE isw613_proyecto_sigel;

-- Categorías iniciales
INSERT IGNORE INTO categorias (nombre, descripcion)
VALUES
    ('Computadoras',
     'Computadoras portátiles y de escritorio'),
    ('Proyectores',
     'Equipos de proyección'),
    ('Accesorios',
     'Teclados, ratones y otros accesorios');

-- Verificar categorías registradas
SELECT id, nombre, descripcion
FROM categorias
ORDER BY nombre;