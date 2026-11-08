<?php

require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

function fechaIsoValida(string $texto): bool
{
    $f = DateTime::createFromFormat('!Y-m-d', $texto);
    return $f !== false && $f->format('Y-m-d') === $texto;
}

/**
 * Evita que Excel interprete una celda como fórmula al abrir el CSV.
 */
function celdaCsvSegura($valor): string
{
    $texto = (string) ($valor ?? '');
    return ($texto !== '' && strpos("=+-@\t\r", $texto[0]) !== false) ? "'" . $texto : $texto;
}

$tipo = $_GET['tipo'] ?? 'por_sala';
if (!in_array($tipo, ['por_sala', 'por_estado', 'disponibilidad'], true)) {
    $tipo = 'por_sala';
}

$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$fecha = trim($_GET['fecha'] ?? '');

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
if ($fecha === '' || !fechaIsoValida($fecha)) {
    if ($fecha !== '') {
        $error = 'La fecha consultada no es válida.';
    }
    $fecha = date('Y-m-d');
}

$titulos = [
    'por_sala' => 'Reservas por sala',
    'por_estado' => 'Reservas por estado',
    'disponibilidad' => 'Disponibilidad de salas',
];

$filas = [];

if ($error === '') {
    try {
        if ($tipo === 'por_sala') {

            $condicion = '';
            $parametros = [];
            if ($desde !== '') {
                $condicion .= ' AND r.fecha_reserva >= :desde';
                $parametros['desde'] = $desde;
            }
            if ($hasta !== '') {
                $condicion .= ' AND r.fecha_reserva <= :hasta';
                $parametros['hasta'] = $hasta;
            }

            $q = $conexion->prepare(
                "SELECT s.nombre AS \"Sala\",
                        s.edificio AS \"Edificio\",
                        s.estado AS \"Estado de la sala\",
                        COUNT(r.id_reserva) AS \"Total\",
                        COUNT(r.id_reserva) FILTER (WHERE r.estado_efectivo = 'APROBADA') AS \"Aprobadas\",
                        COUNT(r.id_reserva) FILTER (WHERE r.estado_efectivo = 'PENDIENTE') AS \"Pendientes\",
                        COUNT(r.id_reserva) FILTER (WHERE r.estado_efectivo = 'RECHAZADA') AS \"Rechazadas\",
                        COUNT(r.id_reserva) FILTER (WHERE r.estado_efectivo = 'CANCELADA') AS \"Canceladas\",
                        COUNT(r.id_reserva) FILTER (WHERE r.estado_efectivo = 'VENCIDA') AS \"Vencidas\"
                 FROM gestion.sala s
                 LEFT JOIN gestion.v_reserva_estado r
                        ON r.id_sala = s.id_sala $condicion
                 GROUP BY s.id_sala
                 ORDER BY s.nombre"
            );
            $q->execute($parametros);
            $filas = $q->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($tipo === 'por_estado') {

            $condicion = '';
            $parametros = [];
            if ($desde !== '') {
                $condicion .= ' AND r.fecha_reserva >= :desde';
                $parametros['desde'] = $desde;
            }
            if ($hasta !== '') {
                $condicion .= ' AND r.fecha_reserva <= :hasta';
                $parametros['hasta'] = $hasta;
            }

            $q = $conexion->prepare(
                "SELECT r.estado_efectivo AS \"Estado\", COUNT(*) AS \"Reservas\"
                 FROM gestion.v_reserva_estado r
                 WHERE TRUE $condicion
                 GROUP BY r.estado_efectivo
                 ORDER BY COUNT(*) DESC, r.estado_efectivo"
            );
            $q->execute($parametros);
            $filas = $q->fetchAll(PDO::FETCH_ASSOC);

        } else {

            $q = $conexion->prepare(
                "SELECT s.nombre AS \"Sala\",
                        s.edificio AS \"Edificio\",
                        s.estado AS \"Estado de la sala\",
                        COALESCE((
                            SELECT string_agg(
                                       to_char(h.hora_inicio, 'HH24:MI') || ' - ' || to_char(h.hora_fin, 'HH24:MI'),
                                       ', ' ORDER BY h.hora_inicio)
                            FROM gestion.horario h
                            WHERE h.id_sala = s.id_sala
                              AND h.dia_semana = gestion.dia_semana_es(CAST(:f1 AS date))
                        ), 'Sin horario') AS \"Horario del día\",
                        (SELECT COUNT(*) FROM gestion.reserva r
                          WHERE r.id_sala = s.id_sala
                            AND r.fecha_reserva = CAST(:f2 AS date)
                            AND r.estado = 'APROBADA') AS \"Aprobadas\",
                        (SELECT COUNT(*) FROM gestion.reserva r
                          WHERE r.id_sala = s.id_sala
                            AND r.fecha_reserva = CAST(:f3 AS date)
                            AND r.estado = 'PENDIENTE'
                            AND r.fecha_reserva + r.hora_inicio >= LOCALTIMESTAMP) AS \"Pendientes\"
                 FROM gestion.sala s
                 ORDER BY s.nombre"
            );
            $q->execute(['f1' => $fecha, 'f2' => $fecha, 'f3' => $fecha]);
            $filas = $q->fetchAll(PDO::FETCH_ASSOC);

            foreach ($filas as &$fila) {
                $fila['¿Se puede reservar?'] =
                    ($fila['Estado de la sala'] === 'DISPONIBLE' && $fila['Horario del día'] !== 'Sin horario')
                        ? 'Sí' : 'No';
            }
            unset($fila);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'No fue posible generar el reporte.';
        $filas = [];
    }
}

// Exportación a CSV (se atiende antes de enviar cualquier HTML).
if (($_GET['formato'] ?? '') === 'csv' && $error === '') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . date('Ymd') . '.csv"');

    $salida = fopen('php://output', 'w');
    fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel respete las tildes.

    if (!empty($filas)) {
        fputcsv($salida, array_keys($filas[0]), ';');
        foreach ($filas as $fila) {
            fputcsv($salida, array_map('celdaCsvSegura', $fila), ';');
        }
    }
    fclose($salida);
    exit;
}

$parametrosUrl = array_filter(
    ['tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta, 'fecha' => $tipo === 'disponibilidad' ? $fecha : ''],
    static fn($v) => $v !== ''
);
$urlCsv = 'index.php?' . http_build_query($parametrosUrl + [], '', '&') . '&formato=csv';

cabecera('Reportes');
?>

<section class="tarjeta">

    <h2>Reportes y consultas</h2>
    <p class="descripcion">Selecciona un reporte, ajusta los filtros y expórtalo si lo necesitas.</p>

    <div class="acciones" style="margin-bottom:22px">
        <?php foreach ($titulos as $clave => $etiqueta): ?>
            <a class="boton <?= $tipo === $clave ? '' : 'boton-sec' ?>"
               href="index.php?tipo=<?= escapar($clave) ?>"><?= escapar($etiqueta) ?></a>
        <?php endforeach; ?>
    </div>

    <form method="GET" class="filtros">
        <input type="hidden" name="tipo" value="<?= escapar($tipo) ?>">

        <?php if ($tipo === 'disponibilidad'): ?>
            <div class="campo">
                <label for="fecha">Fecha</label>
                <input type="date" id="fecha" name="fecha" value="<?= escapar($fecha) ?>">
            </div>
        <?php else: ?>
            <div class="campo">
                <label for="desde">Reservas desde</label>
                <input type="date" id="desde" name="desde" value="<?= escapar($desde) ?>">
            </div>
            <div class="campo">
                <label for="hasta">Reservas hasta</label>
                <input type="date" id="hasta" name="hasta" value="<?= escapar($hasta) ?>">
            </div>
        <?php endif; ?>

        <button type="submit">Generar</button>
    </form>

    <h3><?= escapar($titulos[$tipo]) ?></h3>

    <?php if ($error !== ''): ?>

        <div class="mensaje error" role="alert"><?= escapar($error) ?></div>

    <?php elseif (empty($filas)): ?>

        <div class="vacio">No hay datos para los filtros seleccionados.</div>

    <?php else: ?>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($filas[0]) as $columna): ?>
                            <th><?= escapar($columna) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <?php foreach ($fila as $valor): ?>
                            <td><?= escapar($valor) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($tipo === 'por_estado'): ?>
            <p class="descripcion" style="margin-top:14px">
                Total de reservas: <strong><?= array_sum(array_map('intval', array_column($filas, 'Reservas'))) ?></strong>
            </p>
        <?php endif; ?>

        <p style="margin-top:18px">
            <a class="boton boton-sec" href="<?= escapar($urlCsv) ?>">Exportar a CSV</a>
        </p>

    <?php endif; ?>

    <p style="margin-top:22px">
        <a class="boton boton-sec" href="../admin/panel.php">← Volver al panel</a>
    </p>

</section>

<?php pie(); ?>
