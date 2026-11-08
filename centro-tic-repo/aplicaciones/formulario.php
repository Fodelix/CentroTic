<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

// Sin id se registra una aplicación nueva; con id se actualiza la existente.
$id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id_aplicacion', FILTER_VALIDATE_INT) ?: null;
} elseif (isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
    if ($id === null || $id < 1) {
        redirigir('listar.php');
    }
}

$nombre = '';
$version = '';
$descripcion = '';
$error = '';

try {
    if ($id !== null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $consulta = $conexion->prepare(
            'SELECT nombre, version, descripcion
             FROM gestion.aplicacion WHERE id_aplicacion = :id'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            flash('error', 'La aplicación solicitada no existe.');
            redirigir('listar.php');
        }

        $nombre = $fila['nombre'];
        $version = $fila['version'];
        $descripcion = $fila['descripcion'] ?? '';
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', 'No fue posible consultar la aplicación.');
    redirigir('listar.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirCsrf();

    $nombre = trim($_POST['nombre'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombre === '' || $version === '') {
        $error = 'El nombre y la versión son obligatorios.';
    } elseif (mb_strlen($nombre) > 100) {
        $error = 'El nombre admite máximo 100 caracteres.';
    } elseif (mb_strlen($version) > 30) {
        $error = 'La versión admite máximo 30 caracteres.';
    } elseif (mb_strlen($descripcion) > 1000) {
        $error = 'La descripción admite máximo 1000 caracteres.';
    } else {
        try {
            $datos = [
                'nombre' => $nombre,
                'version' => $version,
                'descripcion' => $descripcion === '' ? null : $descripcion,
            ];

            if ($id === null) {
                $consulta = $conexion->prepare(
                    'INSERT INTO gestion.aplicacion (nombre, version, descripcion)
                     VALUES (:nombre, :version, :descripcion)'
                );
                $consulta->execute($datos);
                flash('exito', 'La aplicación se registró correctamente.');
            } else {
                $consulta = $conexion->prepare(
                    'UPDATE gestion.aplicacion
                     SET nombre = :nombre, version = :version, descripcion = :descripcion
                     WHERE id_aplicacion = :id'
                );
                $datos['id'] = $id;
                $consulta->execute($datos);
                flash('exito', 'La aplicación se actualizó correctamente.');
            }

            redirigir('listar.php');

        } catch (PDOException $e) {
            $codigo = (string) ($e->errorInfo[0] ?? '');
            $error = $codigo === '23505'
                ? 'Ya existe una aplicación con ese nombre y versión.'
                : mensajeErrorBD($e, 'No se pudo guardar la aplicación.');
        }
    }
}

$titulo = $id === null ? 'Registrar aplicación' : 'Actualizar aplicación';

cabecera($titulo);
?>

<section class="tarjeta" style="max-width:750px;margin:auto">

    <h2><?= escapar($titulo) ?></h2>
    <p class="descripcion">
        Indica el nombre, la versión y una breve descripción del software.
    </p>

    <?php if ($error !== ''): ?>
        <div class="mensaje error" role="alert"><?= escapar($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrfCampo() ?>
        <?php if ($id !== null): ?>
            <input type="hidden" name="id_aplicacion" value="<?= (int) $id ?>">
        <?php endif; ?>

        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" maxlength="100"
                   value="<?= escapar($nombre) ?>" required>
        </div>

        <div class="campo">
            <label for="version">Versión</label>
            <input type="text" id="version" name="version" maxlength="30"
                   value="<?= escapar($version) ?>" required>
        </div>

        <div class="campo">
            <label for="descripcion">Descripción (opcional)</label>
            <textarea id="descripcion" name="descripcion" rows="3"
                      maxlength="1000"><?= escapar($descripcion) ?></textarea>
        </div>

        <div class="acciones">
            <button type="submit">Guardar</button>
            <a class="boton boton-sec" href="listar.php">Cancelar</a>
        </div>
    </form>

</section>

<?php pie(); ?>
