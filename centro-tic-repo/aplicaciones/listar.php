<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR', 'USUARIO');

$admin = esAdmin();

$idSala = filter_input(INPUT_GET, 'id_sala', FILTER_VALIDATE_INT);
if (!$idSala || $idSala < 1) {
    $idSala = null;
}

$aplicaciones = [];
$salas = [];
$error = '';

try {
    $salas = $conexion->query(
        'SELECT id_sala, nombre, edificio FROM gestion.sala ORDER BY nombre'
    )->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT a.id_aplicacion, a.nombre, a.version, a.descripcion,
                   COALESCE(string_agg(s.nombre, ', ' ORDER BY s.nombre), '') AS salas
            FROM gestion.aplicacion a
            LEFT JOIN gestion.sala_aplicacion sa ON sa.id_aplicacion = a.id_aplicacion
            LEFT JOIN gestion.sala s ON s.id_sala = sa.id_sala";

    $parametros = [];

    if ($idSala !== null) {
        $sql .= ' WHERE a.id_aplicacion IN (
                      SELECT id_aplicacion FROM gestion.sala_aplicacion WHERE id_sala = :id_sala
                  )';
        $parametros['id_sala'] = $idSala;
    }

    $sql .= ' GROUP BY a.id_aplicacion ORDER BY a.nombre, a.version';

    $consulta = $conexion->prepare($sql);
    $consulta->execute($parametros);
    $aplicaciones = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'No fue posible consultar las aplicaciones.';
}

cabecera('Aplicaciones instaladas');
?>

<section class="tarjeta">

    <h2>Aplicaciones instaladas</h2>
    <p class="descripcion">
        Consulta el software disponible y las salas donde está instalado.
    </p>

    <?php mostrarFlash(); ?>

    <?php if ($error !== ''): ?>
        <div class="mensaje error"><?= escapar($error) ?></div>
    <?php endif; ?>

    <div class="barra">
        <form method="GET" class="filtros">
            <div class="campo">
                <label for="id_sala">Filtrar por sala</label>
                <select name="id_sala" id="id_sala" onchange="this.form.submit()">
                    <option value="">Todas las salas</option>
                    <?php foreach ($salas as $sala): ?>
                        <option value="<?= (int) $sala['id_sala'] ?>"
                            <?= $idSala === (int) $sala['id_sala'] ? 'selected' : '' ?>>
                            <?= escapar($sala['nombre']) ?> - <?= escapar($sala['edificio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <noscript><button type="submit">Filtrar</button></noscript>
        </form>

        <?php if ($admin): ?>
            <div class="acciones">
                <a class="boton" href="formulario.php">+ Registrar aplicación</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($aplicaciones)): ?>

        <div class="vacio">No hay aplicaciones para mostrar.</div>

    <?php else: ?>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Aplicación</th>
                        <th>Versión</th>
                        <th>Descripción</th>
                        <th>Instalada en</th>
                        <?php if ($admin): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($aplicaciones as $app): ?>
                    <tr>
                        <td><strong><?= escapar($app['nombre']) ?></strong></td>
                        <td><?= escapar($app['version']) ?></td>
                        <td><?= escapar($app['descripcion'] ?? '—') ?></td>
                        <td><?= $app['salas'] !== '' ? escapar($app['salas']) : '<em>Sin salas asignadas</em>' ?></td>

                        <?php if ($admin): ?>
                            <td>
                                <div class="acciones">
                                    <a class="boton" href="formulario.php?id=<?= (int) $app['id_aplicacion'] ?>">Editar</a>
                                    <a class="boton boton-sec" href="asignar.php?id=<?= (int) $app['id_aplicacion'] ?>">Salas</a>

                                    <form method="POST" action="eliminar.php"
                                          onsubmit="return confirm('¿Eliminar esta aplicación y sus asignaciones a salas?');">
                                        <?= csrfCampo() ?>
                                        <input type="hidden" name="id_aplicacion" value="<?= (int) $app['id_aplicacion'] ?>">
                                        <button type="submit" class="boton-peligro">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

    <p style="margin-top:22px">
        <a class="boton boton-sec" href="<?= escapar(urlPanel()) ?>">← Volver al panel</a>
    </p>

</section>

<?php pie(); ?>
