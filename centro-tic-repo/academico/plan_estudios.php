<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

$idPrograma = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPrograma = filter_input(INPUT_POST, 'id_programa', FILTER_VALIDATE_INT);
}

if (!$idPrograma || $idPrograma < 1) {
    flash('error', 'El identificador del programa no es válido.');
    redirigir('programas.php');
}

try {
    $q = $conexion->prepare(
        'SELECT id_programa, nombre FROM gestion.programa_academico WHERE id_programa = :id'
    );
    $q->execute(['id' => $idPrograma]);
    $programa = $q->fetch(PDO::FETCH_ASSOC);

    if (!$programa) {
        flash('error', 'El programa solicitado no existe.');
        redirigir('programas.php');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', 'No fue posible consultar el programa.');
    redirigir('programas.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirCsrf();

    $accion = $_POST['accion'] ?? '';
    $idCurso = filter_input(INPUT_POST, 'id_curso', FILTER_VALIDATE_INT) ?: null;
    $semestre = filter_input(
        INPUT_POST, 'semestre', FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 14]]
    );

    try {
        if ($idCurso === null) {
            throw new DomainException('Debes seleccionar un curso.');
        }

        if ($accion === 'agregar') {

            if ($semestre === false || $semestre === null) {
                throw new DomainException('El semestre debe ser un número entre 1 y 14.');
            }

            $q = $conexion->prepare(
                'INSERT INTO gestion.programa_curso (id_programa, id_curso, semestre)
                 VALUES (:programa, :curso, :semestre)'
            );
            $q->execute(['programa' => $idPrograma, 'curso' => $idCurso, 'semestre' => $semestre]);
            flash('exito', 'El curso se agregó al plan de estudios.');

        } elseif ($accion === 'quitar') {

            $q = $conexion->prepare(
                'DELETE FROM gestion.programa_curso
                 WHERE id_programa = :programa AND id_curso = :curso'
            );
            $q->execute(['programa' => $idPrograma, 'curso' => $idCurso]);
            flash('exito', 'El curso se quitó del plan de estudios.');

        } else {
            throw new DomainException('Acción no reconocida.');
        }

    } catch (DomainException $e) {
        flash('error', $e->getMessage());
    } catch (PDOException $e) {
        $codigo = (string) ($e->errorInfo[0] ?? '');
        if ($codigo === '23505') {
            flash('error', 'Ese curso ya está en el plan de estudios.');
        } else {
            flash('error', mensajeErrorBD($e, 'No se pudo completar la operación.'));
        }
    }

    redirigir('plan_estudios.php?id=' . $idPrograma);
}

$plan = [];
$disponibles = [];

try {
    $q = $conexion->prepare(
        'SELECT c.id_curso, c.nombre, pc.semestre
         FROM gestion.programa_curso pc
         INNER JOIN gestion.curso c ON c.id_curso = pc.id_curso
         WHERE pc.id_programa = :id
         ORDER BY pc.semestre, c.nombre'
    );
    $q->execute(['id' => $idPrograma]);
    $plan = $q->fetchAll(PDO::FETCH_ASSOC);

    $q = $conexion->prepare(
        'SELECT id_curso, nombre FROM gestion.curso
         WHERE id_curso NOT IN (
             SELECT id_curso FROM gestion.programa_curso WHERE id_programa = :id
         )
         ORDER BY nombre'
    );
    $q->execute(['id' => $idPrograma]);
    $disponibles = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', 'No fue posible consultar el plan de estudios.');
}

cabecera('Plan de estudios');
?>

<section class="tarjeta">

    <h2>Plan de estudios</h2>
    <p class="descripcion">Programa: <strong><?= escapar($programa['nombre']) ?></strong></p>

    <?php mostrarFlash(); ?>

    <?php if (empty($disponibles)): ?>
        <div class="vacio">
            No hay más cursos para agregar. Puedes <a href="cursos.php">registrar cursos nuevos</a>.
        </div>
    <?php else: ?>
        <form method="POST" action="plan_estudios.php?id=<?= (int) $idPrograma ?>" class="filtros">
            <?= csrfCampo() ?>
            <input type="hidden" name="accion" value="agregar">
            <input type="hidden" name="id_programa" value="<?= (int) $idPrograma ?>">

            <div class="campo" style="flex:2">
                <label for="id_curso">Curso</label>
                <select id="id_curso" name="id_curso" required>
                    <option value="">Selecciona un curso</option>
                    <?php foreach ($disponibles as $c): ?>
                        <option value="<?= (int) $c['id_curso'] ?>"><?= escapar($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="semestre">Semestre (1-14)</label>
                <input type="number" id="semestre" name="semestre" min="1" max="14" step="1" required>
            </div>

            <button type="submit">Agregar al plan</button>
        </form>
    <?php endif; ?>

    <?php if (empty($plan)): ?>
        <div class="vacio">Este programa todavía no tiene cursos.</div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th class="num">Semestre</th><th>Curso</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($plan as $fila): ?>
                    <tr>
                        <td class="num"><?= (int) $fila['semestre'] ?></td>
                        <td><?= escapar($fila['nombre']) ?></td>
                        <td>
                            <form method="POST" action="plan_estudios.php?id=<?= (int) $idPrograma ?>"
                                  onsubmit="return confirm('¿Quitar este curso del plan?');">
                                <?= csrfCampo() ?>
                                <input type="hidden" name="accion" value="quitar">
                                <input type="hidden" name="id_programa" value="<?= (int) $idPrograma ?>">
                                <input type="hidden" name="id_curso" value="<?= (int) $fila['id_curso'] ?>">
                                <button type="submit" class="boton-peligro">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <p style="margin-top:22px">
        <a class="boton boton-sec" href="programas.php">← Programas</a>
    </p>

</section>

<?php pie(); ?>
