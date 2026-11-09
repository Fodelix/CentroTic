<?php
/**
 * Ruta del archivo privado de usuarios (administradores y cuentas).
 *
 * Debe estar FUERA de la carpeta pública del servidor web (htdocs / DocumentRoot).
 *
 * Prioridad:
 *   1. Variable CENTRO_TIC_USUARIOS (recomendada en Linux; ver docs/DESPLIEGUE_LINUX.md).
 *   2. Ruta por defecto de XAMPP en Windows.
 *
 * Uso en login.php y registro.php:
 *   $archivoUsuarios = require __DIR__ . '/config/rutas.php';
 */

$ruta = getenv('CENTRO_TIC_USUARIOS');

// Con Apache, SetEnv puede llegar solo a $_SERVER; se revisa también ahí.
if ($ruta === false || $ruta === '') {
    $ruta = $_SERVER['CENTRO_TIC_USUARIOS'] ?? '';
}

return $ruta !== '' ? $ruta : 'C:/xampp/centro_tic_privado/usuarios.php';
