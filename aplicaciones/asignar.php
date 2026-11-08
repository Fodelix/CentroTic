<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id_aplicacion', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    flash('error', 'El identificador de la aplicación no es válido.');
    redirigir('listar.php');
}

$error = '';

try {
    $consulta = $conexion->prepare(
        'SELECT id_aplicacion, nombre, version
         FROM gestion.aplicacion WHERE id_aplicacion = :id'
    );
    $consulta->execute(['id' => $id]);
    $aplicacion = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$aplicacion) {
        flash('error', 'La aplicación solicitada no existe.');
        redirigir('listar.php');
    }

    $salas = $conexion->query(
        'SELECT id_sala, nombre, edificio, estado
         FROM gestion.sala ORDER BY nombre'
    )->fetchAll(PDO::FETCH_ASSOC);

    $consulta = $conexion->prepare(
        'SELECT id_sala FROM gestion.sala_aplicacion WHERE id_aplicacion = :id'
    );
    $consulta->execute(['id' => $id]);
    $asignadas = array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));

} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', 'No fue posible consultar la información.');
    redirigir('listar.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirCsrf();

    $enviadas = $_POST['salas'] ?? [];
    $enviadas = is_array($enviadas) ? $enviadas : [];

    $idsValidos = array_map('intval', array_column($salas, 'id_sala'));
    $seleccion = [];

    foreach ($enviadas as $valor) {
        $sala = filter_var($valor, FILTER_VALIDATE_INT);
        // Solo se aceptan salas que existen realmente.
        if ($sala !== false && in_array($sala, $idsValidos, true)) {
            $seleccion[$sala] = $sala;
        }
    }

    try {
        $conexion->beginTransaction();

        $borrar = $conexion->prepare(
            'DELETE FROM gestion.sala_aplicacion WHERE id_aplicacion = :id'
        );
        $borrar->execute(['id' => $id]);

        $insertar = $conexion->prepare(
            'INSERT INTO gestion.sala_aplicacion (id_sala, id_aplicacion)
             VALUES (:sala, :app)'
        );

        foreach ($seleccion as $sala) {
            $insertar->execute(['sala' => $sala, 'app' => $id]);
        }

        $conexion->commit();

        flash('exito', 'Las salas de la aplicación se actualizaron correctamente.');
        redirigir('listar.php');

    } catch (PDOException $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $error = mensajeErrorBD($e, 'No se pudieron guardar las asignaciones.');
        $asignadas = array_values($seleccion);
    }
}

cabecera('Salas de la aplicación');
?>

<section class="tarjeta" style="max-width:850px;margin:auto">

    <h2>Salas de <?= escapar($aplicacion['nombre']) ?> <?= escapar($aplicacion['version']) ?></h2>
    <p class="descripcion">
        Marca las salas en las que está instalada esta aplicación.
    </p>

    <?php if ($error !== ''): ?>
        <div class="mensaje error" role="alert"><?= escapar($error) ?></div>
    <?php endif; ?>

    <?php if (empty($salas)): ?>

        <div class="vacio">Aún no hay salas registradas.</div>

    <?php else: ?>

        <form method="POST" action="">
            <?= csrfCampo() ?>
            <input type="hidden" name="id_aplicacion" value="<?= (int) $id ?>">

            <div class="opciones">
                <?php foreach ($salas as $sala): ?>
                    <label>
                        <input type="checkbox" name="salas[]"
                               value="<?= (int) $sala['id_sala'] ?>"
                               <?= in_array((int) $sala['id_sala'], $asignadas, true) ? 'checked' : '' ?>>
                        <?= escapar($sala['nombre']) ?>
                        <small>(<?= escapar($sala['edificio']) ?>)</small>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="acciones">
                <button type="submit">Guardar asignaciones</button>
                <a class="boton boton-sec" href="listar.php">Cancelar</a>
            </div>
        </form>

    <?php endif; ?>

</section>

<?php pie(); ?>
