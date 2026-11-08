<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirCsrf();

    $accion = $_POST['accion'] ?? '';
    $id = filter_input(INPUT_POST, 'id_curso', FILTER_VALIDATE_INT) ?: null;
    $nombre = trim($_POST['nombre'] ?? '');

    try {
        if ($accion === 'eliminar') {

            if ($id === null) {
                throw new DomainException('El identificador no es válido.');
            }

            // Se eliminan en cascada plan de estudios, matrículas y asignaciones docentes.
            $q = $conexion->prepare('DELETE FROM gestion.curso WHERE id_curso = :id');
            $q->execute(['id' => $id]);
            flash('exito', 'El curso se eliminó correctamente.');

        } elseif ($accion === 'crear' || $accion === 'editar') {

            if ($nombre === '') {
                throw new DomainException('El nombre del curso es obligatorio.');
            }
            if (mb_strlen($nombre) > 100) {
                throw new DomainException('El nombre admite máximo 100 caracteres.');
            }

            if ($accion === 'crear') {
                $q = $conexion->prepare('INSERT INTO gestion.curso (nombre) VALUES (:nombre)');
                $q->execute(['nombre' => $nombre]);
                flash('exito', 'El curso se registró correctamente.');
            } else {
                if ($id === null) {
                    throw new DomainException('El identificador no es válido.');
                }
                $q = $conexion->prepare('UPDATE gestion.curso SET nombre = :nombre WHERE id_curso = :id');
                $q->execute(['nombre' => $nombre, 'id' => $id]);
                flash('exito', 'El curso se actualizó correctamente.');
            }

        } else {
            throw new DomainException('Acción no reconocida.');
        }

    } catch (DomainException $e) {
        flash('error', $e->getMessage());
        if ($accion === 'editar' && $id !== null) {
            redirigir('cursos.php?editar=' . $id);
        }
    } catch (PDOException $e) {
        flash('error', mensajeErrorBD($e, 'No se pudo completar la operación.'));
    }

    redirigir('cursos.php');
}

$editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: null;
$editando = null;
$cursos = [];

try {
    $cursos = $conexion->query(
        'SELECT c.id_curso, c.nombre,
                (SELECT COUNT(*) FROM gestion.programa_curso pc WHERE pc.id_curso = c.id_curso) AS programas,
                (SELECT COUNT(*) FROM gestion.matricula m WHERE m.id_curso = c.id_curso) AS estudiantes,
                (SELECT COUNT(*) FROM gestion.docente_curso dc WHERE dc.id_curso = c.id_curso) AS docentes
         FROM gestion.curso c
         ORDER BY c.nombre'
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($editarId !== null) {
        foreach ($cursos as $c) {
            if ((int) $c['id_curso'] === $editarId) {
                $editando = $c;
                break;
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', 'No fue posible consultar los cursos.');
}

cabecera('Cursos');
?>

<section class="tarjeta">

    <h2>Cursos</h2>
    <p class="descripcion">Administra el catálogo de cursos.</p>

    <?php mostrarFlash(); ?>

    <form method="POST" action="cursos.php" class="filtros">
        <?= csrfCampo() ?>
        <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'crear' ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id_curso" value="<?= (int) $editando['id_curso'] ?>">
        <?php endif; ?>

        <div class="campo" style="flex:1">
            <label for="nombre"><?= $editando ? 'Editar curso' : 'Nuevo curso' ?></label>
            <input type="text" id="nombre" name="nombre" maxlength="100" required
                   value="<?= escapar($editando['nombre'] ?? '') ?>">
        </div>

        <button type="submit"><?= $editando ? 'Guardar cambios' : 'Registrar' ?></button>
        <?php if ($editando): ?>
            <a class="boton boton-sec" href="cursos.php">Cancelar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($cursos)): ?>
        <div class="vacio">No hay cursos registrados.</div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Curso</th><th class="num">Programas</th>
                        <th class="num">Estudiantes</th><th class="num">Docentes</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cursos as $c): ?>
                    <tr>
                        <td><?= (int) $c['id_curso'] ?></td>
                        <td><?= escapar($c['nombre']) ?></td>
                        <td class="num"><?= (int) $c['programas'] ?></td>
                        <td class="num"><?= (int) $c['estudiantes'] ?></td>
                        <td class="num"><?= (int) $c['docentes'] ?></td>
                        <td>
                            <div class="acciones">
                                <a class="boton" href="cursos.php?editar=<?= (int) $c['id_curso'] ?>">Editar</a>
                                <form method="POST" action="cursos.php"
                                      onsubmit="return confirm('Al eliminar el curso también se eliminan sus matrículas, asignaciones docentes y su lugar en los planes de estudio. ¿Continuar?');">
                                    <?= csrfCampo() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_curso" value="<?= (int) $c['id_curso'] ?>">
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
