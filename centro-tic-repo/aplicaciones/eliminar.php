<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('listar.php');
}

exigirCsrf();

$id = filter_input(INPUT_POST, 'id_aplicacion', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    flash('error', 'El identificador de la aplicación no es válido.');
    redirigir('listar.php');
}

try {
    // Las filas de sala_aplicacion se eliminan en cascada (ON DELETE CASCADE).
    $consulta = $conexion->prepare(
        'DELETE FROM gestion.aplicacion WHERE id_aplicacion = :id'
    );
    $consulta->execute(['id' => $id]);

    if ($consulta->rowCount() === 0) {
        flash('error', 'La aplicación no existe o ya fue eliminada.');
    } else {
        flash('exito', 'La aplicación se eliminó correctamente.');
    }

} catch (PDOException $e) {
    flash('error', mensajeErrorBD($e, 'No se pudo eliminar la aplicación.'));
}

redirigir('listar.php');
