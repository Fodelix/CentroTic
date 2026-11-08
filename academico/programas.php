<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

$niveles = [
    'TECNICO' => 'Técnico',
    'TECNOLOGO' => 'Tecnólogo',
    'PREGRADO' => 'Pregrado',
    'ESPECIALIZACION' => 'Especialización',
    'MAESTRIA' => 'Maestría',
    'DOCTORADO' => 'Doctorado',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    exigirCsrf();

    $accion = $_POST['accion'] ?? '';
    $id = filter_input(INPUT_POST, 'id_programa', FILTER_VALIDATE_INT) ?: null;
    $nombre = trim($_POST['nombre'] ?? '');
    $nivel = $_POST['nivel_academico'] ?? '';
    $idFacultad = filter_input(INPUT_POST, 'id_facultad', FILTER_VALIDATE_INT) ?: null;

    try {
        if ($accion === 'eliminar') {

            if ($id === null) {
                throw new DomainException('El identificador no es válido.');
            }

            // programa_curso se elimina en cascada.
            $q = $conexion->prepare('DELETE FROM gestion.programa_academico WHERE id_programa = :id');
            $q->execute(['id' => $id]);
            flash('exito', 'El programa se eliminó correctamente.');

        } elseif ($accion === 'crear' || $accion === 'editar') {

            if ($nombre === '') {
                throw new DomainException('El nombre del programa es obligatorio.');
            }
            if (mb_strlen($nombre) > 100) {
                throw new DomainException('El nombre admite máximo 100 caracteres.');
            }
            if (!array_key_exists($nivel, $niveles)) {
                throw new DomainException('El nivel académico no es válido.');
            }
            if ($idFacultad === null) {
                throw new DomainException('Debes seleccionar una facultad.');
            }

            if ($accion === 'crear') {
                $q = $conexion->prepare(
                    'INSERT INTO gestion.programa_academico (nombre, nivel_academico, id_facultad)
                     VALUES (:nombre, :nivel, :facultad)'
                );
                $q->execute(['nombre' => $nombre, 'nivel' => $nivel, 'facultad' => $idFacultad]);
                flash('exito', 'El programa se registró correctamente.');
            } else {
                if ($id === null) {
                    throw new DomainException('El identificador no es válido.');
                }
                $q = $conexion->prepare(
                    'UPDATE gestion.programa_academico
                     SET nombre = :nombre, nivel_academico = :nivel, id_facultad = :facultad
                     WHERE id_programa = :id'
                );
                $q->execute([
                    'nombre' => $nombre, 'nivel' => $nivel,
                    'facultad' => $idFacultad, 'id' => $id,
                ]);
                flash('exito', 'El programa se actualizó correctamente.');
            }

        } else {
            throw new DomainException('Acción no reconocida.');
        }

    } catch (DomainException $e) {
        flash('error', $e->getMessage());
        if ($accion === 'editar' && $id !== null) {
            redirigir('programas.php?editar=' . $id);
        }
    } catch (PDOException $e) {
        $codigo = (string) ($e->errorInfo[0] ?? '');
        if ($codigo === '23505') {
            flash('error', 'Ya existe un programa con ese nombre en la facultad.');
        } elseif ($codigo === '23503') {
            flash('error', 'La facultad seleccionada no existe.');
        } else {
            flash('error', mensajeErrorBD($e, 'No se pudo completar la operación.'));
        }
    }

    redirigir('programas.php');
}

$editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: null;
$editando = null;
$programas = [];
$facultades = [];

try {
    $facultades = $conexion->query(
        'SELECT id_facultad, nombre FROM gestion.facultad ORDER BY nombre'
    )->fetchAll(PDO::FETCH_ASSOC);

    $programas = $conexion->query(
        'SELECT p.id_programa, p.nombre, p.nivel_academico, p.id_facultad,
                f.nombre AS facultad, COUNT(pc.id_curso) AS cursos
         FROM gestion.programa_academico p
         INNER JOIN gestion.facultad f ON f.id_facultad = p.id_facultad
         LEFT JOIN gestion.programa_curso pc ON pc.id_programa = p.id_programa
         GROUP BY p.id_programa, f.nombre
         ORDER BY f.nombre, p.nombre'
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($editarId !== null) {
        foreach ($programas as $p) {
            if ((int) $p['id_programa'] === $editarId) {
                $editando = $p;
                break;
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', 'No fue posible consultar los programas.');
}

cabecera('Programas académicos');
?>

<section class="tarjeta">

    <h2>Programas académicos</h2>
    <p class="descripcion">Administra los programas de cada facultad y su plan de estudios.</p>

    <?php mostrarFlash(); ?>

    <?php if (empty($facultades)): ?>

        <div class="vacio">
            Primero debes <a href="facultades.php">registrar una facultad</a>.
        </div>

    <?php else: ?>

        <form method="POST" action="programas.php" class="filtros">
            <?= csrfCampo() ?>
            <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'crear' ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id_programa" value="<?= (int) $editando['id_programa'] ?>">
            <?php endif; ?>

            <div class="campo" style="flex:2">
                <label for="nombre"><?= $editando ? 'Editar programa' : 'Nuevo programa' ?></label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required
                       value="<?= escapar($editando['nombre'] ?? '') ?>">
            </div>

            <div class="campo">
                <label for="nivel_academico">Nivel</label>
                <select id="nivel_academico" name="nivel_academico" required>
                    <?php foreach ($niveles as $valor => $etiqueta): ?>
                        <option value="<?= escapar($valor) ?>"
                            <?= ($editando['nivel_academico'] ?? 'PREGRADO') === $valor ? 'selected' : '' ?>>
                            <?= escapar($etiqueta) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="id_facultad">Facultad</label>
                <select id="id_facultad" name="id_facultad" required>
                    <option value="">Selecciona</option>
                    <?php foreach ($facultades as $f): ?>
                        <option value="<?= (int) $f['id_facultad'] ?>"
                            <?= (int) ($editando['id_facultad'] ?? 0) === (int) $f['id_facultad'] ? 'selected' : '' ?>>
                            <?= escapar($f['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit"><?= $editando ? 'Guardar cambios' : 'Registrar' ?></button>
            <?php if ($editando): ?>
                <a class="boton boton-sec" href="programas.php">Cancelar</a>
            <?php endif; ?>
        </form>

    <?php endif; ?>

    <?php if (empty($programas)): ?>
        <div class="vacio">No hay programas registrados.</div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Programa</th><th>Nivel</th><th>Facultad</th>
                        <th class="num">Cursos</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($programas as $p): ?>
                    <tr>
                        <td><?= (int) $p['id_programa'] ?></td>
                        <td><?= escapar($p['nombre']) ?></td>
                        <td><?= escapar($niveles[$p['nivel_academico']] ?? $p['nivel_academico']) ?></td>
                        <td><?= escapar($p['facultad']) ?></td>
                        <td class="num"><?= (int) $p['cursos'] ?></td>
                        <td>
                            <div class="acciones">
                                <a class="boton" href="programas.php?editar=<?= (int) $p['id_programa'] ?>">Editar</a>
                                <a class="boton boton-sec" href="plan_estudios.php?id=<?= (int) $p['id_programa'] ?>">Plan de estudios</a>
                                <form method="POST" action="programas.php"
                                      onsubmit="return confirm('¿Eliminar este programa y su plan de estudios?');">
                                    <?= csrfCampo() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_programa" value="<?= (int) $p['id_programa'] ?>">
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
