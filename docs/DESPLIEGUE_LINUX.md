# Despliegue en Linux

La guía del proyecto exige que la aplicación se ejecute sobre Linux. Estos pasos son para Ubuntu/Debian con Apache, PHP y PostgreSQL.

## 1. Paquetes

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-pgsql postgresql
```

## 2. Base de datos

```bash
sudo -u postgres createdb centro_tic
sudo -u postgres psql -d centro_tic -f config/centro_tic_script.sql
sudo -u postgres psql -d centro_tic -f config/datos_prueba.sql   # solo en una base de pruebas
```

> Ambos scripts borran datos. No los ejecutes sobre una base con información que quieras conservar.

## 3. Aplicación

```bash
sudo cp -r PRESTAMO_Y_RESERVA_DE_SALAS_DE_COMPUTO_CENTRO_TIC /var/www/html/
```

Ajusta `conexion.example.php` (host, puerto, base, usuario y contraseña). En Linux el puerto por defecto de PostgreSQL es `5432`, no `5433`.

## 4. Archivo privado de usuarios (fuera de la carpeta pública)

```bash
sudo mkdir -p /var/lib/centro_tic_privado
sudo cp usuarios.php /var/lib/centro_tic_privado/usuarios.php
sudo chown -R www-data:www-data /var/lib/centro_tic_privado
sudo chmod 750 /var/lib/centro_tic_privado
sudo chmod 640 /var/lib/centro_tic_privado/usuarios.php
```

Apache necesita escribir en la carpeta (el registro crea archivos temporales y el `.lock`) y en `usuarios.php`:

```bash
sudo chmod 660 /var/lib/centro_tic_privado/usuarios.php
```

Indica la ruta mediante la variable de entorno `CENTRO_TIC_USUARIOS`. Con Apache, en `/etc/apache2/conf-available/centro_tic.conf`:

```apache
SetEnv CENTRO_TIC_USUARIOS /var/lib/centro_tic_privado/usuarios.php
```

```bash
sudo a2enconf centro_tic
sudo systemctl reload apache2
```

`config/rutas.php` devuelve esa ruta (o la de XAMPP si la variable no existe). Para que la aplicación la use, en `login.php` y `registro.php` reemplaza la ruta escrita a mano:

```php
// login.php
$archivoUsuarios = require __DIR__ . '/config/rutas.php';
$usuarios = require $archivoUsuarios;

// registro.php
$archivoUsuarios = require __DIR__ . '/config/rutas.php';
```

## 5. Mayúsculas y minúsculas

Linux distingue mayúsculas de minúsculas. El proyecto enlaza a `../horarios/Listar.php` (con L mayúscula) y el archivo se llama igual, así que funciona; mantén esa grafía en todos los enlaces o renombra el archivo a `listar.php` y actualiza los enlaces.

## 6. Ruta de redirección

`auth/seguridad.php` redirige a `/PRESTAMO_Y_RESERVA_DE_SALAS_DE_COMPUTO_CENTRO_TIC/login.php`. Si cambias el nombre de la carpeta en el servidor, actualiza esa ruta.

## 7. Vencimiento automático de reservas

La función `gestion.vencer_reservas()` debe ejecutarse periódicamente. Con cron del sistema:

```bash
*/15 * * * * sudo -u postgres psql -d centro_tic -c "SELECT gestion.vencer_reservas();"
```

La vista `v_reserva_estado` ya muestra el estado efectivo aunque la función no haya corrido, y los reportes la usan.
