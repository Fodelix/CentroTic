<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

// Contiene datos personales (correo, teléfono): solo administradores.
exigirRol('ADMINISTRADOR');

$tipo = ($_GET['tipo'] ?? 'estudiantes') === 'docentes' ? 'docentes' : 'estudiantes';
$busqueda = trim($_GET['q'] ?? '');

if (mb_strlen($busqueda) > 100) {
    $busqueda = mb_substr($busqueda, 0, 100);
}

$personas = [];
$error = '';

try {
    if ($tipo === 'estudiantes') {
        $sql = "SELECT p.id_persona, p.nombres, p.apellidos, p.correo,
                       e.codigo_estudiante AS codigo,
                       EXISTS (SELECT 1 FROM gestion.docente d WHERE d.id_persona = p.id_persona) AS es_otro,
                       COALESCE((SELECT string_agg(c.nombre, ', ' ORDER BY c.nombre)
                                 FROM gestion.matricula m
                                 INNER JOIN gestion.curso c ON c.id_curso = m.id_curso
                                 WHERE m.id_persona = p.id_persona), '') AS cursos
                FROM gestion.persona p
                INNER JOIN gestion.estudiante e ON e.id_persona = p.id_persona";
        $filtroCodigo = 'e.codigo_estudiante';
    } else {
        $sql = "SELECT p.id_persona, p.nombres, p.apellidos, p.correo,
                       d.codigo_docente AS codigo, d.telefono,
                       EXISTS (SELECT 1 FROM gestion.estudiante e WHERE e.id_persona = p.id_persona) AS es_otro,
                       COALESCE((SELECT string_agg(c.nombre, ', ' ORDER BY c.nombre)
                                 FROM gestion.docente_curso dc
                                 INNER JOIN gestion.curso c ON c.id_curso = dc.id_curso
                                 WHERE dc.id_persona = p.id_persona), '') AS cursos
                FROM gestion.persona p
                INNER JOIN gestion.docente d ON d.id_persona = p.id_persona";
        $filtroCodigo = 'd.codigo_docente';
    }

    $parametros = [];

    if ($busqueda !== '') {
        $sql .= " WHERE p.nombres ILIKE :b1 OR p.apellidos ILIKE :b2
                     OR p.correo ILIKE :b3 OR $filtroCodigo ILIKE :b4";
        // Se escapan los comodines de LIKE escritos por el usuario.
        $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
        $parametros = ['b1' => $patron, 'b2' => $patron, 'b3' => $patron, 'b4' => $patron];
    }

    $sql .= ' ORDER BY p.apellidos, p.nombres';

    $q = $conexion->prepare($sql);
    $q->execute($parametros);
    $personas = $q->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'No fue posible consultar la información.';
}

cabecera('Estudiantes y docentes');
?>

<section class="tarjeta">

    <h2><?= $tipo === 'estudiantes' ? 'Estudiantes' : 'Docentes' ?></h2>
    <p class="descripcion">
        Consulta de personas registradas. Las cuentas nuevas se crean desde el formulario de registro público.
    </p>

    <div class="acciones" style="margin-bottom:20px">
        <a class="boton <?= $tipo === 'estudiantes' ? '' : 'boton-sec' ?>" href="personas.php?tipo=estudiantes">Estudiantes</a>
        <a class="boton <?= $tipo === 'docentes' ? '' : 'boton-sec' ?>" href="personas.php?tipo=docentes">Docentes</a>
    </div>

    <form method="GET" class="filtros">
        <input type="hidden" name="tipo" value="<?= escapar($tipo) ?>">
        <div class="campo" style="flex:1">
            <label for="q">Buscar por nombre, correo o código</label>
            <input type="text" id="q" name="q" maxlength="100" value="<?= escapar($busqueda) ?>">
        </div>
        <button type="submit">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a class="boton boton-sec" href="personas.php?tipo=<?= escapar($tipo) ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if ($error !== ''): ?>
        <div class="mensaje error"><?= escapar($error) ?></div>
    <?php elseif (empty($personas)): ?>
        <div class="vacio">No se encontraron registros.</div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Código</th><th>Nombre</th><th>Correo</th>
                        <?php if ($tipo === 'docentes'): ?><th>Teléfono</th><?php endif; ?>
                        <th><?= $tipo === 'estudiantes' ? 'Cursos matriculados' : 'Cursos dictados' ?></th>
                        <th>También es</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($personas as $p): ?>
                    <tr>
                        <td><?= escapar($p['codigo']) ?></td>
                        <td><?= escapar($p['nombres'] . ' ' . $p['apellidos']) ?></td>
                        <td><?= escapar($p['correo']) ?></td>
                        <?php if ($tipo === 'docentes'): ?>
                            <td><?= escapar($p['telefono'] ?? '—') ?></td>
                        <?php endif; ?>
                        <td><?= $p['cursos'] !== '' ? escapar($p['cursos']) : '—' ?></td>
                        <td>
                            <?php if ($p['es_otro']): ?>
                                <span class="badge"><?= $tipo === 'estudiantes' ? 'Docente' : 'Estudiante' ?></span>
                            <?php else: ?>—<?php endif; ?>
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
