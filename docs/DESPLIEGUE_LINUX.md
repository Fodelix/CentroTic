# Cómo poner el proyecto a funcionar en Linux

Esta guía es para el equipo. La idea es que cualquiera de nosotros pueda repetirla sin ayuda y sin entender todo el detalle técnico. Cada paso dice **qué hacemos** y **por qué**.

La guía del proyecto exige que la entrega corra en Linux. Nosotros hemos desarrollado en Windows con XAMPP, así que esto hay que probarlo antes de sustentar, no el mismo día.

> **¿No tienes Linux?** Puedes usar Ubuntu dentro de Windows con **WSL 2** (se instala desde la Microsoft Store) o con una máquina virtual en VirtualBox. Todo lo de abajo se escribe en la terminal de Ubuntu.

Las instrucciones son para **Ubuntu / Debian**.

---

## Antes de empezar: qué vamos a montar

Tres piezas que se hablan entre sí:

1. **Apache + PHP**: el servidor que muestra las páginas.
2. **PostgreSQL**: la base de datos donde viven salas, reservas, etc.
3. **Un archivo privado de usuarios** (`usuarios.php`): donde están las cuentas y las contraseñas cifradas. Tiene que quedar **fuera** de la carpeta web, para que nadie pueda descargarlo desde el navegador.

---

## Paso 1. Instalar los programas

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-pgsql postgresql
```

**Por qué:** `php-pgsql` es el "puente" que permite a PHP hablar con PostgreSQL. Sin él aparece el mensaje *"No se pudo conectar con la base de datos"* (nos pasó en Windows hasta activar `pdo_pgsql`).

---

## Paso 2. Preparar la base de datos

Primero, ponle una contraseña conocida al usuario `postgres` (la usará la aplicación):

```bash
sudo -u postgres psql -c "ALTER USER postgres PASSWORD 'pon_aqui_tu_clave';"
```

Luego creamos la base y cargamos los scripts, **en este orden**:

```bash
sudo -u postgres createdb centro_tic
sudo -u postgres psql -d centro_tic -f config/centro_tic_script.sql
sudo -u postgres psql -d centro_tic -f config/datos_prueba.sql
```

- El primer script crea las tablas y las reglas.
- El segundo mete datos de ejemplo.

**Cuidado:** los dos scripts borran información. Solo se ejecutan en una base de pruebas, y `centro_tic_script.sql` nunca después de que ya haya datos que queramos conservar.

---

## Paso 3. Copiar la aplicación

```bash
sudo cp -r PRESTAMO_Y_RESERVA_DE_SALAS_DE_COMPUTO_CENTRO_TIC /var/www/html/
```

**Importante:** la carpeta debe llamarse exactamente así, porque `auth/seguridad.php` redirige a `/PRESTAMO_Y_RESERVA_DE_SALAS_DE_COMPUTO_CENTRO_TIC/login.php`. Si le ponemos otro nombre, hay que cambiar esa línea.

Después abrimos `conexion.example.php` y revisamos estas líneas:

```php
$puerto = '5432';              // en Linux casi siempre es 5432
$base_datos = 'centro_tic';
$usuario = 'postgres';
$contrasena = 'pon_aqui_tu_clave';   // la del paso 2
```

---

## Paso 4. Guardar las cuentas fuera de la carpeta web

```bash
sudo mkdir -p /var/lib/centro_tic_privado
sudo cp usuarios.php /var/lib/centro_tic_privado/usuarios.php
sudo chown -R www-data:www-data /var/lib/centro_tic_privado
sudo chmod 750 /var/lib/centro_tic_privado
sudo chmod 660 /var/lib/centro_tic_privado/usuarios.php
```

**Por qué `www-data`:** es el usuario con el que corre Apache. Cuando alguien se registra, la aplicación **escribe** en ese archivo (y crea un archivo temporal y un `.lock` en la misma carpeta), así que Apache necesita permiso de escritura en la carpeta y en el archivo.

Ahora le decimos a la aplicación dónde está ese archivo. Creamos el archivo de configuración de Apache:

```bash
sudo nano /etc/apache2/conf-available/centro_tic.conf
```

Dentro escribimos una sola línea:

```apache
SetEnv CENTRO_TIC_USUARIOS /var/lib/centro_tic_privado/usuarios.php
```

Guardamos (Ctrl+O, Enter) y salimos (Ctrl+X). Luego activamos y recargamos:

```bash
sudo a2enconf centro_tic
sudo systemctl reload apache2
```

`config/rutas.php` lee esa variable. Si no existe, usa la ruta de XAMPP de Windows, por eso en nuestros computadores no tuvimos que configurar nada.

> `login.php` y `registro.php` ya usan `config/rutas.php`, así que no hay nada que editar en ellos.

---

## Paso 5. Probar

Abrimos en el navegador:

```
http://localhost/PRESTAMO_Y_RESERVA_DE_SALAS_DE_COMPUTO_CENTRO_TIC/login.php
```

Entramos con el administrador y recorremos el panel. Para una revisión completa seguimos `docs/PLAN_DE_PRUEBAS.md`.

---

## Si algo falla

| Lo que vemos | Qué suele ser | Qué hacer |
|---|---|---|
| *"No se pudo conectar con la base de datos"* | Falta `php-pgsql`, o puerto/contraseña mal | Revisar paso 1 y las líneas de `conexion.example.php` |
| *"No se encuentra el archivo privado de usuarios o no tiene permisos"* al registrarse | Apache no puede escribir en la carpeta de usuarios | Repetir los `chown` y `chmod` del paso 4 |
| Al iniciar sesión dice usuario o contraseña incorrectos, y sí son correctos | La variable `CENTRO_TIC_USUARIOS` no llegó | Revisar paso 4 y recargar Apache |
| Una página no se encuentra (404) | Nombre de carpeta distinto, o mayúsculas distintas | Linux distingue mayúsculas: `Listar.php` no es `listar.php` |

Para ver el error real, el registro de Apache es el mejor aliado:

```bash
sudo tail -n 30 /var/log/apache2/error.log
```

---

## Extra: que las reservas venzan solas

La función `gestion.vencer_reservas()` marca como vencidas las solicitudes pendientes cuya hora ya pasó. Podemos programarla cada 15 minutos con cron:

```bash
sudo crontab -u postgres -e
```

y añadir al final:

```
*/15 * * * * psql -d centro_tic -c "SELECT gestion.vencer_reservas();"
```

Aunque no se programe, la aplicación muestra el estado correcto (los reportes usan la vista `v_reserva_estado`), así que esto es opcional.
