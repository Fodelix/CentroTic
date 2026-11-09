# Plan de pruebas

Ejecuta cada prueba con la base de datos recién cargada con `datos_prueba.sql`. Marca el resultado real en la última columna.

Cuentas necesarias: un administrador y un usuario (estudiante o docente) de `usuarios.php`.

## 1. Seguridad y roles

| # | Prueba | Resultado esperado | Real |
|---|--------|--------------------|------|
| 1.1 | Abrir `admin/panel.php` sin iniciar sesión | Redirige a `login.php` | |
| 1.2 | Con usuario general, abrir `admin/panel.php` | Error 403 | |
| 1.3 | Con usuario general, abrir `academico/index.php`, `reportes/index.php`, `reservas/por_sala.php`, `aplicaciones/formulario.php` | Error 403 en todas | |
| 1.4 | Con administrador, abrir `reservas/crear.php` | Error 403 (solo usuarios) | |
| 1.5 | Enviar un POST a `aplicaciones/eliminar.php` sin el campo `csrf` | Error 403 | |
| 1.6 | Escribir `<script>alert(1)</script>` como nombre de aplicación | Se muestra como texto, no se ejecuta | |

## 2. Aplicaciones (etapa 6)

| # | Prueba | Resultado esperado | Real |
|---|--------|--------------------|------|
| 2.1 | Registrar «Git» versión «2.45» | Aparece en el listado | |
| 2.2 | Registrar de nuevo «Git» «2.45» | «Ya existe una aplicación con ese nombre y versión» | |
| 2.3 | Dejar nombre o versión vacíos | Mensaje de campos obligatorios | |
| 2.4 | Nombre de más de 100 caracteres | Mensaje de longitud máxima | |
| 2.5 | Asignar la aplicación a dos salas y filtrar por una de ellas | Solo aparece donde está instalada | |
| 2.6 | Enviar por POST una sala inexistente (id 9999) en `asignar.php` | Se ignora, no hay error | |
| 2.7 | Eliminar la aplicación | Desaparece junto con sus asignaciones | |

## 3. Gestión académica (etapa 7)

| # | Prueba | Resultado esperado | Real |
|---|--------|--------------------|------|
| 3.1 | Crear facultad con nombre vacío | Mensaje de campo obligatorio | |
| 3.2 | Crear una facultad con nombre ya existente | «Ya existe una facultad con ese nombre» | |
| 3.3 | Eliminar una facultad con programas | «No se puede eliminar: la facultad tiene programas…» | |
| 3.4 | Crear programa con nivel no permitido (manipulando el formulario) | «El nivel académico no es válido» | |
| 3.5 | Agregar curso al plan con semestre 0, 15 o texto | «El semestre debe ser un número entre 1 y 14» | |
| 3.6 | Agregar dos veces el mismo curso al plan | «Ese curso ya está en el plan de estudios» | |
| 3.7 | Buscar `%` en estudiantes | Se trata como texto, no devuelve todo por comodín | |

## 4. Reportes (etapa 9)

| # | Prueba | Resultado esperado | Real |
|---|--------|--------------------|------|
| 4.1 | «Reservas por sala» con datos de prueba | Sala 101: 4 reservas (2 aprobadas, 2 pendientes) | |
| 4.2 | «Reservas por estado» | 4 aprobadas y 2 pendientes, total 6 | |
| 4.3 | Fecha inicial posterior a la final | Mensaje de error, sin consulta | |
| 4.4 | Fecha inválida (`2026-02-31`) | Mensaje de error | |
| 4.5 | «Disponibilidad» del próximo lunes | Sala 101 con 2 aprobadas y 2 pendientes; Auditorio «No» (mantenimiento) | |
| 4.6 | Exportar a CSV y abrir en Excel | Tildes correctas, columnas separadas | |
| 4.7 | `reservas/por_sala.php` filtrando por Sala 101 y estado PENDIENTE | Solo las reservas de Carlos y Laura | |

## 5. Casos inválidos de reservas y horarios (reglas en SQL)

Se prueban desde `reservas/crear.php` y `horarios/crear.php`.

| # | Prueba | Resultado esperado | Real |
|---|--------|--------------------|------|
| 5.1 | Campos vacíos | Validación del navegador / servidor | |
| 5.2 | Fecha pasada | «La fecha y hora de la reserva ya pasaron» | |
| 5.3 | Hora fin menor o igual a hora inicio | «La hora final debe ser posterior…» | |
| 5.4 | Fuera del horario de la sala | «Fuera del horario de funcionamiento» | |
| 5.5 | Sábado en una sala sin horario ese día | Misma restricción de horario | |
| 5.6 | Reserva que cruza una aprobada | Queda en estado PENDIENTE | |
| 5.7 | Cancelar la reserva aprobada (en SQL) | La pendiente se aprueba automáticamente | |
| 5.8 | Horario que se cruza con otro de la misma sala y día | «El horario se cruza con otro bloque…» | |
| 5.9 | Cualquier error de las reglas anteriores (5.2 a 5.4) | El mensaje se ve limpio, sin «ERROR:» ni «CONTEXT:» ni nombres de funciones | |

## 6. Capacidad de sala y equipos (datos numéricos)

| # | Prueba | Resultado esperado | Real |
|---|--------|--------------------|------|
| 6.1 | Crear sala con capacidad negativa | Rechazada | |
| 6.2 | Crear sala con capacidad 0 | Rechazada: «La capacidad debe ser un número entero mayor que cero» | |
| 6.3 | Crear un equipo con un código de inventario ya existente | «Ese código de inventario ya está registrado» | |

