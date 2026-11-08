
<?php
require_once __DIR__ . '/../auth/seguridad.php';
require_once __DIR__ . '/../conexion.example.php';

exigirLogin();
exigirRol('USUARIO');

function escapar($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$salas = [];
$horarios = [];
$mensaje = '';
$tipoMensaje = '';

$idSalaSeleccionada = filter_input(
    INPUT_GET,
    'id_sala',
    FILTER_VALIDATE_INT
);

$fechaSeleccionada = $_GET['fecha'] ?? '';

$diasSemana = [
    1 => 'LUNES',
    2 => 'MARTES',
    3 => 'MIERCOLES',
    4 => 'JUEVES',
    5 => 'VIERNES',
    6 => 'SABADO',
    7 => 'DOMINGO'
];

// Consultar salas disponibles
try {
    $consultaSalas = $conexion->query("
        SELECT id_sala, nombre, edificio, capacidad
        FROM gestion.sala
        WHERE estado = 'DISPONIBLE'
        ORDER BY nombre
    ");

    $salas = $consultaSalas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit('No fue posible consultar las salas.');
}

// Consultar disponibilidad cuando se selecciona sala y fecha
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['consultar'])) {

    $fechaValida = DateTime::createFromFormat(
        '!Y-m-d',
        $fechaSeleccionada
    );

    $salaExiste = false;

    foreach ($salas as $sala) {
        if ((int)$sala['id_sala'] === (int)$idSalaSeleccionada) {
            $salaExiste = true;
            break;
        }
    }

    if (
        !$idSalaSeleccionada ||
        !$salaExiste ||
        !$fechaValida ||
        $fechaValida->format('Y-m-d') !== $fechaSeleccionada
    ) {
        $mensaje = 'Selecciona una sala y una fecha válidas.';
        $tipoMensaje = 'error';

    } elseif ($fechaSeleccionada < date('Y-m-d')) {

        $mensaje = 'No puedes consultar fechas anteriores.';
        $tipoMensaje = 'error';

    } else {

        try {
            $numeroDia = (int)$fechaValida->format('N');
            $diaSemana = $diasSemana[$numeroDia];

            $consulta = $conexion->prepare("
                SELECT
                    h.id_horario,
                    h.hora_inicio AS horario_inicio,
                    h.hora_fin AS horario_fin,
                    r.id_reserva,
                    r.hora_inicio AS reserva_inicio,
                    r.hora_fin AS reserva_fin,
                    r.estado AS estado_reserva
                FROM gestion.horario h
                LEFT JOIN gestion.reserva r
                    ON r.id_sala = h.id_sala
                    AND r.fecha_reserva = :fecha
                    AND r.estado IN ('APROBADA', 'PENDIENTE')
                    AND r.hora_inicio < h.hora_fin
                    AND r.hora_fin > h.hora_inicio
                WHERE h.id_sala = :id_sala
                    AND h.dia_semana = :dia
                ORDER BY h.hora_inicio, r.hora_inicio
            ");

            $consulta->execute([
                ':fecha' => $fechaSeleccionada,
                ':id_sala' => $idSalaSeleccionada,
                ':dia' => $diaSemana
            ]);

            $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);

            foreach ($resultados as $fila) {

                $idHorario = $fila['id_horario'];

                if (!isset($horarios[$idHorario])) {
                    $horarios[$idHorario] = [
                        'inicio' => $fila['horario_inicio'],
                        'fin' => $fila['horario_fin'],
                        'reservas' => []
                    ];
                }

                if ($fila['id_reserva'] !== null) {
                    $horarios[$idHorario]['reservas'][] = [
                        'inicio' => $fila['reserva_inicio'],
                        'fin' => $fila['reserva_fin'],
                        'estado' => $fila['estado_reserva']
                    ];
                }
            }

            if (empty($horarios)) {
                $mensaje = 'Esta sala no tiene horarios configurados para ese día.';
                $tipoMensaje = 'informacion';
            }

        } catch (PDOException $e) {
            $mensaje = 'No fue posible consultar la disponibilidad.';
            $tipoMensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Disponibilidad de salas - Centro TIC</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            color: #222;
        }

        .contenedor {
            max-width: 850px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.10);
        }

        h1, h2 {
            color: #8B0000;
        }

        h1 {
            text-align: center;
            margin-top: 0;
        }

        .descripcion {
            text-align: center;
            color: #555;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 17px;
            margin-bottom: 7px;
        }

        select, input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button, .volver, .solicitar {
            display: block;
            width: 100%;
            padding: 13px;
            margin-top: 20px;
            border: none;
            border-radius: 6px;
            background: #8B0000;
            color: white;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        button:hover, .volver:hover, .solicitar:hover {
            background: #650000;
        }

        .mensaje {
            padding: 13px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .error {
            background: #f8d7da;
            color: #842029;
        }

        .informacion {
            background: #e8e8e8;
            color: #333;
        }

        .horario {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 18px;
            margin-top: 18px;
        }

        .ocupada {
            background: #f8d7da;
            border-left: 5px solid #8B0000;
            padding: 12px;
            margin-top: 10px;
            border-radius: 5px;
        }

        .libre {
            background: #e2f0e5;
            border-left: 5px solid #287a3d;
            padding: 12px;
            margin-top: 10px;
            border-radius: 5px;
        }

        .nota {
            color: #555;
            font-size: 13px;
            margin-top: 20px;
            line-height: 1.5;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Disponibilidad de salas</h1>

    <p class="descripcion">
        Centro TIC - Consulta los horarios y las reservas de las salas.
    </p>

    <?php if ($mensaje !== ''): ?>
        <div class="mensaje <?= escapar($tipoMensaje) ?>">
            <?= escapar($mensaje) ?>
        </div>
    <?php endif; ?>

    <form method="GET">

        <label for="id_sala">Seleccionar sala</label>

        <select name="id_sala" id="id_sala" required>
            <option value="">Seleccione una sala</option>

            <?php foreach ($salas as $sala): ?>
                <option
                    value="<?= escapar($sala['id_sala']) ?>"
                    <?= ((int)$idSalaSeleccionada === (int)$sala['id_sala']) ? 'selected' : '' ?>
                >
                    <?= escapar($sala['nombre']) ?>
                    - <?= escapar($sala['edificio']) ?>
                    (Capacidad: <?= escapar($sala['capacidad']) ?>)
                </option>
            <?php endforeach; ?>

        </select>

        <label for="fecha">Fecha que deseas consultar</label>

        <input
            type="date"
            name="fecha"
            id="fecha"
            min="<?= date('Y-m-d') ?>"
            value="<?= escapar($fechaSeleccionada) ?>"
            required
        >

        <button type="submit" name="consultar" value="1">
            Consultar disponibilidad
        </button>

    </form>

    <?php if (!empty($horarios)): ?>

        <h2>Resultado de la consulta</h2>

        <p>
            Fecha consultada:
            <strong><?= escapar($fechaSeleccionada) ?></strong>
        </p>

        <?php foreach ($horarios as $horario): ?>

            <div class="horario">

                <h3>
                    Horario de funcionamiento:
                    <?= escapar(substr($horario['inicio'], 0, 5)) ?>
                    -
                    <?= escapar(substr($horario['fin'], 0, 5)) ?>
                </h3>

                <?php if (empty($horario['reservas'])): ?>

                    <div class="libre">
                        No aparecen reservas pendientes ni aprobadas
                        en este horario.
                    </div>

                <?php else: ?>

                    <?php foreach ($horario['reservas'] as $reserva): ?>

                        <div class="ocupada">

                            <strong>
                                <?= escapar(substr($reserva['inicio'], 0, 5)) ?>
                                -
                                <?= escapar(substr($reserva['fin'], 0, 5)) ?>
                            </strong>

                            <br>

                            Estado:
                            <?= escapar($reserva['estado']) ?>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

        <p class="nota">
            Esta consulta es informativa. Las solicitudes pendientes
            y aprobadas se muestran como intervalos ocupados para
            orientar la consulta. La aceptación definitiva de una
            solicitud depende de las validaciones de PostgreSQL.
        </p>

        <a class="solicitar" href="crear.php">
            Solicitar una reserva
        </a>

    <?php endif; ?>

    <a class="volver" href="../usuario/panel.php">
        Volver al panel
    </a>

</div>

</body>
</html>
