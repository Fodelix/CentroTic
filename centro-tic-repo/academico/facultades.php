<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirCsrf();

    $accion = $_POST['accion'] ?? '';
    $id = filter_input(INPUT_POST, 'id_facultad', FILTER_VALIDATE_INT) ?: null;
    $nombre = trim($_POST['nombre'] ?? '');

    try {
        if ($accion === 'eliminar') {

            if ($id === null) {
                throw new DomainException('El identificador no es válido.');
            }

            $q = $conexion->prepare('DELETE FROM gestion.facultad WHERE id_facultad = :id');
            $q->execute(['id' => $id]);
            flash('exito', 'La facultad se eliminó correctamente.');

        } elseif ($accion === 'crear' || $accion === 'editar') {

            if ($nombre === '') {
                throw new DomainException('El nombre de la facultad es obligatorio.');
            }
            if (mb_strlen($nombre) > 100) {
                throw new DomainException('El nombre admite máximo 100 caracteres.');
            }

            if ($accion === 'crear') {
                $q = $conexion->prepare('INSERT INTO gestion.facultad (nombre) VALUES (:nombre)');
                $q->execute(['nombre' => $nombre]);
                flash('exito', 'La facultad se registró correctamente.');
            } else {
                if ($id === null) {
                    throw new DomainException('El identificador no es válido.');
                }
                $q = $conexion->prepare(
                    'UPDATE gestion.facultad SET nombre = :nombre WHERE id_facultad = :id'
                );
                $q->execute(['nombre' => $nombre, 'id' => $id]);
                flash('exito', 'La facultad se actualizó correctamente.');
            }

        } else {
            throw new DomainException('Acción no reconocida.');
        }

    } catch (DomainException $e) {
        flash('error', $e->getMessage());
        if ($accion === 'editar' && $id !== null) {
            redirigir('facultades.php?editar=' . $id);
        }
    } catch (PDOException $e) {
        $codigo = (string) ($e->errorInfo[0] ?? '');
        if ($codigo === '23505') {
            flash('error', 'Ya existe una facultad con ese nombre.');
        } elseif ($codigo === '23503') {
            flash('error', 'No se puede eliminar: la facultad tiene programas académicos asociados.');
        } else {
            flash('error', mensajeErrorBD($e, 'No se pudo completar la operación.'));
        }
    }

    redirigir('facultades.php');
}

$editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: null;
$editando = null;
$facultades = [];

try {
    $facultades = $conexion->query(
        'SELECT f.id_facultad, f.nombre, COUNT(p.id_programa) AS programas
         FROM gestion.facultad f
         LEFT JOIN gestion.programa_academico p ON p.id_facultad = f.id_facultad
         GROUP BY f.id_facultad
         ORDER BY f.nombre'
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($editarId !== null) {
        foreach ($facultades as $f) {
            if ((int) $f['id_facultad'] === $editarId) {
                $editando = $f;
                break;
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', 'No fue posible consultar las facultades.');
}

cabecera('Facultades');
?>

<section class="tarjeta">

    <h2>Facultades</h2>
    <p class="descripcion">Registra y administra las facultades de la institución.</p>

    <?php mostrarFlash(); ?>

    <form method="POST" action="facultades.php" class="filtros">
        <?= csrfCampo() ?>
        <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'crear' ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id_facultad" value="<?= (int) $editando['id_facultad'] ?>">
        <?php endif; ?>

        <div class="campo" style="flex:1">
            <label for="nombre"><?= $editando ? 'Editar facultad' : 'Nueva facultad' ?></label>
            <input type="text" id="nombre" name="nombre" maxlength="100" required
                   value="<?= escapar($editando['nombre'] ?? '') ?>">
        </div>

        <button type="submit"><?= $editando ? 'Guardar cambios' : 'Registrar' ?></button>
        <?php if ($editando): ?>
            <a class="boton boton-sec" href="facultades.php">Cancelar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($facultades)): ?>
        <div class="vacio">No hay facultades registradas.</div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr><th>ID</th><th>Facultad</th><th class="num">Programas</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                <?php foreach ($facultades as $f): ?>
                    <tr>
                        <td><?= (int) $f['id_facultad'] ?></td>
                        <td><?= escapar($f['nombre']) ?></td>
                        <td class="num"><?= (int) $f['programas'] ?></td>
                        <td>
                            <div class="acciones">
                                <a class="boton" href="facultades.php?editar=<?= (int) $f['id_facultad'] ?>">Editar</a>
                                <form method="POST" action="facultades.php"
                                      onsubmit="return confirm('¿Eliminar esta facultad?');">
                                    <?= csrfCampo() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_facultad" value="<?= (int) $f['id_facultad'] ?>">
                                    <button type="submit" class="boton-peligro">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <p style="margin-top:22px">
        <a class="boton boton-sec" href="index.php">← Gestión académica</a>
    </p>

</section>

<?php pie(); ?>
