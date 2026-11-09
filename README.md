# Sistema de Préstamo y Reserva de Salas - Centro TIC

Aplicación web en PHP (sin frameworks) y PostgreSQL para consultar y reservar salas de cómputo.

## Funcionalidades

| Módulo | Administrador | Usuario (estudiante/docente) |
|--------|:-------------:|:----------------------------:|
| Salas | Crear, consultar, editar | Consultar |
| Equipos | Crear, consultar, editar | Consultar |
| Horarios | Crear, consultar, editar | Consultar |
| Aplicaciones instaladas | Crear, editar, eliminar, asignar a salas | Consultar (filtro por sala) |
| Reservas | Consultar todas y por sala (con solicitante) | Consultar disponibilidad, solicitar, ver las propias |
| Gestión académica | Facultades, programas, plan de estudios, cursos, consulta de estudiantes y docentes | — |
| Reportes | Reservas por sala, por estado, disponibilidad; exportación CSV | — |

## Estructura

```
admin/          Panel del administrador
usuario/        Panel del usuario
auth/           Control de sesión y roles (seguridad.php)
includes/       ui.php: plantilla, CSRF, mensajes y manejo de errores
css/            centro_tic.css: estilos compartidos
salas/ equipos/ horarios/ reservas/ aplicaciones/ academico/ reportes/
config/         Scripts SQL y rutas.php
docs/           Despliegue en Linux y plan de pruebas
```

## Base de datos

El esquema `gestion` contiene las reglas de negocio en SQL (validaciones, cola de reservas y disparadores).
Scripts en `config/`:

1. `centro_tic_script.sql`: crea el esquema. **Elimina y recrea `gestion`.**
2. `datos_prueba.sql`: datos de ejemplo. **Vacía las tablas.**

## Cuentas y seguridad

- Las cuentas (administradores y usuarios) se guardan en `usuarios.php`, **fuera de la carpeta pública** (`C:/xampp/centro_tic_privado/` en XAMPP; ver `docs/DESPLIEGUE_LINUX.md` para Linux). Nunca debe subirse a GitHub.
- Las contraseñas se almacenan con `password_hash`.
- Todas las consultas usan sentencias preparadas (PDO), la salida se escapa con `htmlspecialchars` y los formularios que modifican datos llevan token CSRF.
- Cada página valida el rol en el servidor con `exigirRol()`.
- No subas credenciales reales de la base de datos: crea tu propio `conexion.example.php` local con tus datos.

## Documentación

- [Instalación en Windows con XAMPP](docs/INSTALACION_WINDOWS_XAMPP.md)
- [Despliegue en Linux](docs/DESPLIEGUE_LINUX.md)
- [Plan de pruebas](docs/PLAN_DE_PRUEBAS.md)

## Tecnologías

PHP 8, PostgreSQL 12+, Apache, Git/GitHub.
