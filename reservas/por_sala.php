<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

// El administrador consulta las reservas hechas a las salas.
exigirRol('ADMINISTRADOR');

function fechaIsoValida(string $texto): bool
{
    $f = DateTime::createFromFormat('!Y-m-d', $texto);
    return $f !== false && $f->format('Y-m-d') === $texto;
}

$estados = ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA', 'VENCIDA'];

$idSala = filter_input(INPUT_GET, 'id_sala', FILTER_VALIDATE_INT);
$idSala = ($idSala && $idSala > 0) ? $idSala : null;

$estado = $_GET['estado'] ?? '';
if (!in_array($estado, $estados, true)) {
    $estado = '';
}

$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$error = '';

if ($desde !== '' && !fechaIsoValida($desde)) {
    $error = 'La fecha inicial no es válida.';
    $desde = '';
}
if ($hasta !== '' && !fechaIsoValida($hasta)) {
    $error = 'La fecha final no es válida.';
    $hasta = '';
}
if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
    $error = 'La fecha inicial no puede ser posterior a la final.';
    $desde = '';
    $hasta = '';
}

$limite = 500;
$salas = [];
$reservas = [];

try {
    $salas = $conexion->query(
        'SELECT id_sala, nombre, edificio FROM gestion.sala ORDER BY nombre'
    )->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT r.id_reserva, r.fecha_solicitud, r.fecha_reserva,
                   r.hora_inicio, r.hora_fin, r.estado_efectivo AS estado,
                   s.nombre AS sala, s.edificio,
                   p.nombres, p.apellidos, p.correo,
                   CASE
                       WHEN EXISTS (SELECT 1 FROM gestion.docente d WHERE d.id_persona = p.id_persona)
                        AND EXISTS (SELECT 1 FROM gestion.estudiante e WHERE e.id_persona = p.id_persona)
                           THEN 'Docente y estudiante'
                       WHEN EXISTS (SELECT 1 FROM gestion.docente d WHERE d.id_persona = p.id_persona)
                           THEN 'Docente'
                       WHEN EXISTS (SELECT 1 FROM gestion.estudiante e WHERE e.id_persona = p.id_persona)
                           THEN 'Estudiante'
                       ELSE '—'
                   END AS perfil
            FROM gestion.v_reserva_estado r
            INNER JOIN gestion.sala s ON s.id_sala = r.id_sala
            INNER JOIN gestion.persona p ON p.id_persona = r.id_persona
            WHERE TRUE";

    $parametros = [];

    if ($idSala !== null) {
        $sql .= ' AND r.id_sala = :id_sala';
        $parametros['id_sala'] = $idSala;
    }
    if ($estado !== '') {
        $sql .= ' AND r.estado_efectivo = :estado';
        $parametros['estado'] = $estado;
    }
    if ($desde !== '') {
        $sql .= ' AND r.fecha_reserva >= :desde';
        $parametros['desde'] = $desde;
    }
    if ($hasta !== '') {
        $sql .= ' AND r.fecha_reserva <= :hasta';
        $parametros['hasta'] = $hasta;
    }

    $sql .= ' ORDER BY r.fecha_reserva DESC, r.hora_inicio DESC, r.id_reserva DESC LIMIT ' . $limite;

    $q = $conexion->prepare($sql);
    $q->execute($parametros);
    $reservas = $q->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'No fue posible consultar las reservas.';
}

function claseBadge(string $estado): string
{
    switch ($estado) {
        case 'APROBADA': return 'ok';
        case 'PENDIENTE': return 'aviso';
        case 'RECHAZADA':
        case 'CANCELADA':
        case 'VENCIDA': return 'mal';
        default: return '';
    }
}

cabecera('Reservas por sala');
?>

<section class="tarjeta">

    <h2>Reservas realizadas a las salas</h2>
    <p class="descripcion">
        Consulta quién ha solicitado cada sala, en qué fecha y cuál es el estado de la solicitud.
    </p>

    <form method="GET" class="filtros">
        <div class="campo">
            <label for="id_sala">Sala</label>
            <select id="id_sala" name="id_sala">
                <option value="">Todas</option>
                <?php foreach ($salas as $sala): ?>
                    <option value="<?= (int) $sala['id_sala'] ?>"
                        <?= $idSala === (int) $sala['id_sala'] ? 'selected' : '' ?>>
                        <?= escapar($sala['nombre']) ?> - <?= escapar($sala['edificio']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach ($estados as $e): ?>
                    <option value="<?= escapar($e) ?>" <?= $estado === $e ? 'selected' : '' ?>>
                        <?= escapar($e) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="<?= escapar($desde) ?>">
        </div>

        <div class="campo">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="<?= escapar($hasta) ?>">
        </div>

        <button type="submit">Consultar</button>
        <a class="boton boton-sec" href="por_sala.php">Limpiar</a>
    </form>

    <?php if ($error !== ''): ?>

        <div class="mensaje error" role="alert"><?= escapar($error) ?></div>

    <?php elseif (empty($reservas)): ?>

        <div class="vacio">No hay reservas que coincidan con los filtros.</div>

    <?php else: ?>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Sala</th>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Solicitante</th>
                        <th>Perfil</th>
                        <th>Correo</th>
                        <th>Solicitada el</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reservas as $r): ?>
                    <tr>
                        <td><?= (int) $r['id_reserva'] ?></td>
                        <td><?= escapar($r['sala']) ?> <small>(<?= escapar($r['edificio']) ?>)</small></td>
                        <td><?= escapar(date('d/m/Y', strtotime($r['fecha_reserva']))) ?></td>
                        <td><?= escapar(substr($r['hora_inicio'], 0, 5)) ?> - <?= escapar(substr($r['hora_fin'], 0, 5)) ?></td>
                        <td><?= escapar($r['nombres'] . ' ' . $r['apellidos']) ?></td>
                        <td><?= escapar($r['perfil']) ?></td>
                        <td><?= escapar($r['correo']) ?></td>
                        <td><?= escapar(date('d/m/Y H:i', strtotime($r['fecha_solicitud']))) ?></td>
                        <td><span class="badge <?= escapar(claseBadge($r['estado'])) ?>"><?= escapar($r['estado']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="descripcion" style="margin-top:14px">
            Se muestran <?= count($reservas) ?> reserva(s)<?= count($reservas) === $limite ? ' (máximo ' . $limite . '; ajusta los filtros para acotar)' : '' ?>.
            El estado considera como VENCIDA toda solicitud pendiente cuya hora de inicio ya pasó.
        </p>

    <?php endif; ?>

    <p style="margin-top:22px">
        <a class="boton boton-sec" href="../admin/panel.php">← Volver al panel</a>
    </p>

</section>

<?php pie(); ?>
