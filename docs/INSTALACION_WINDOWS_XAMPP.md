# Instalación en Windows con XAMPP y pgAdmin

Guía para el equipo. Es lo que usamos para desarrollar y probar en nuestros computadores.

## 1. Poner los archivos en su sitio

1. Copia el proyecto a `C:\xampp\htdocs\PRESTAMO_Y_RESERVA_DE_SALAS_DE_COMPUTO_CENTRO_TIC\`. El nombre de la carpeta tiene que ser ese, porque el sistema redirige a esa ruta.
2. Crea la carpeta `C:\xampp\centro_tic_privado\` y guarda ahí `usuarios.php`. Es el archivo de cuentas y **no debe estar dentro de `htdocs`**.

## 2. Encender Apache

1. Abre el **XAMPP Control Panel** y pulsa **Start** en **Apache**. Cuando se ponga en verde, está listo. MySQL no se usa.
2. Para que PHP hable con PostgreSQL: **Config → PHP (php.ini)**, busca `pdo_pgsql` y `extension=pgsql`, quita el `;` del inicio de esas líneas, guarda y reinicia Apache (Stop / Start).

## 3. Preparar la base de datos en pgAdmin 4

1. Abre pgAdmin y conéctate a tu servidor PostgreSQL. La contraseña que pide al abrir el programa (la "maestra") **no** es la de PostgreSQL.
2. Crea la base `centro_tic` (clic derecho en **Databases → Create → Database**).
3. Clic derecho en `centro_tic` → **Query Tool**. Con el icono de carpeta abre y ejecuta, **en este orden**:
   - `config/centro_tic_script.sql`
   - `config/datos_prueba.sql`

   Los dos scripts borran datos: no ejecutes el primero otra vez cuando ya haya información que quieras conservar.

## 4. Datos de conexión

En `conexion.example.php` deben coincidir tres cosas con tu PostgreSQL:

| Dato | Cómo lo averiguas |
|---|---|
| Puerto | En pgAdmin, clic derecho en el servidor → **Properties → Connection**, o ejecuta `SHOW port;` en el Query Tool |
| Usuario | `postgres` |
| Contraseña | La del usuario `postgres`. Si no la recuerdas: `ALTER USER postgres PASSWORD 'nueva';` desde el Query Tool |

## 5. Entrar

Abre `http://localhost/PRESTAMO_Y_RESERVA_DE_SALAS_DE_COMPUTO_CENTRO_TIC/login.php`.

## Problemas que ya nos pasaron

| Mensaje | Causa y solución |
|---|---|
| *"No se pudo conectar con la base de datos"* | Una de las tres cosas anteriores no coincide. Un archivo de prueba con `new PDO(...)` mostrando `$e->getMessage()` dice la causa exacta (bórralo después) |
| *"could not find driver"* | Falta activar `pdo_pgsql` (paso 2) |
| *"Connection refused"* | El puerto está mal (paso 4) |
| *"password authentication failed"* | La contraseña está mal (paso 4) |
| *"Solo estudiantes y docentes pueden reservar (persona N)"* | La cuenta existe en `usuarios.php` pero su persona no está en la base. Tras recargar `datos_prueba.sql` hay que registrar la persona de nuevo, o crear la cuenta desde `registro.php` |
