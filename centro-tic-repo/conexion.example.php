
<?php

$host = 'localhost';
$puerto = '5433';
$base_datos = 'centro_tic';
$usuario = 'postgres';
$contrasena = 'CAMBIAR_POR_TU_CONTRASENA';

try {
    $conexion = new PDO(
        "pgsql:host=$host;port=$puerto;dbname=$base_datos",
        $usuario,
        $contrasena
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
 
   

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit("No se pudo conectar con la base de datos.");
}
