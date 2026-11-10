# SIGEL – Sistema de Gestión de Equipos y Préstamos

## 1. Descripción del proyecto

SIGEL es un sistema web desarrollado para gestionar equipos tecnológicos y controlar sus préstamos dentro de una institución educativa.

Permite administrar usuarios, categorías, equipos, préstamos y devoluciones mediante una interfaz web.

## 2. Tecnologías utilizadas

- PHP 8.x
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- PDO para la conexión con la base de datos
- XAMPP como entorno de desarrollo
- Visual Studio Code como editor de código

## 3. Roles del sistema

El sistema contempla tres roles:

- **Administrador:** administra usuarios, categorías, equipos y otras funciones del sistema.
- **Operador:** registra y gestiona los préstamos de equipos.
- **Profesor:** usuario al que se le asignan los préstamos.

## 4. Requisitos de instalación

- XAMPP.
- PHP 8.x.
- MySQL o MariaDB.
- Navegador web.
- Visual Studio Code (recomendado).

## 5. Instalación y configuración

1. Instalar y ejecutar XAMPP.
2. Iniciar Apache y MySQL.
3. Copiar el proyecto en el entorno de trabajo configurado.
4. Abrir phpMyAdmin desde `http://localhost/phpmyadmin`.
5. Crear la base de datos `isw613_proyecto_sigel`.
6. Importar el script SQL que contiene la estructura de las tablas.
7. Ejecutar el archivo `seed.sql` para insertar las categorías iniciales.
8. Revisar la configuración de conexión en `config/database.php`.
9. Abrir el sistema desde la dirección local configurada.

## 6. Estructura de la base de datos

La base de datos contiene las siguientes tablas:

- `usuarios`: almacena la información de los usuarios y sus roles.
- `categorias`: registra las categorías de los equipos.
- `equipos`: almacena los equipos disponibles y sus características.
- `prestamos`: registra las operaciones de préstamo.
- `detalle_prestamo`: relaciona los equipos con cada préstamo.
- `intentos_login`: controla los intentos fallidos de inicio de sesión.

## 7. Funcionalidades principales

- Inicio y cierre de sesión.
- Gestión de usuarios.
- Gestión de categorías.
- Registro, consulta y edición de equipos.
- Registro y seguimiento de préstamos.
- Control de disponibilidad de equipos.
- Registro de devoluciones.
- Gestión de imágenes de equipos.
- Generación de reportes CSV.
- Control de acceso según el rol del usuario.

## 8. Seguridad

El sistema utiliza mecanismos de seguridad como:

- Consultas preparadas mediante PDO.
- Almacenamiento de contraseñas mediante hashes.
- Control de sesiones.
- Validación de permisos según el rol.
- Protección CSRF en formularios.
- Validación de datos recibidos.
- Control de intentos fallidos de inicio de sesión.

## 9. Consideraciones

El archivo `seed.sql` contiene datos iniciales de categorías. No reemplaza el script de creación de tablas ni crea usuarios administradores.

Los usuarios deben registrarse o crearse mediante el procedimiento seguro implementado en el sistema.

## 10. Proyecto académico

**Sistema:** SIGEL – Gestión de equipos y préstamos.

**Curso:** ISW-613.

**Tecnologías:** PHP, MySQL/MariaDB y XAMPP.