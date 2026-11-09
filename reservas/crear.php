
<?php
require_once __DIR__ . '/../auth/seguridad.php';
exigirRol('USUARIO');

require_once __DIR__ . '/../conexion.example.php';

function escapar($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$mensaje = '';
$tipoMensaje = '';
$salas = [];

$idPersona = $_SESSION['id_persona'] ?? null;

if (!$idPersona) {
    http_response_code(403);
    exit('No se encontró la persona asociada a la sesión.');
}

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

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idSala = filter_input(INPUT_POST, 'id_sala', FILTER_VALIDATE_INT);
    $fecha = $_POST['fecha_reserva'] ?? '';
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFin = $_POST['hora_fin'] ?? '';

    $fechaValida = DateTime::createFromFormat('!Y-m-d', $fecha);

    $horaInicioValida = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horaInicio);
    $horaFinValida = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horaFin);

    if (!$idSala || !$fechaValida ||
        $fechaValida->format('Y-m-d') !== $fecha ||
        !$horaInicioValida || !$horaFinValida) {

        $mensaje = 'Verifica los datos ingresados.';
        $tipoMensaje = 'error';

    } elseif ($horaInicio >= $horaFin) {

        $mensaje = 'La hora final debe ser posterior a la hora inicial.';
        $tipoMensaje = 'error';

    } else {

        try {
            $insertar = $conexion->prepare("
                INSERT INTO gestion.reserva (
                    fecha_reserva,
                    hora_inicio,
                    hora_fin,
                    id_sala,
                    id_persona
                )
                VALUES (
                    :fecha,
                    :hora_inicio,
                    :hora_fin,
                    :id_sala,
                    :id_persona
                )
                RETURNING id_reserva
            ");

            $insertar->execute([
                ':fecha' => $fecha,
                ':hora_inicio' => $horaInicio,
                ':hora_fin' => $horaFin,
                ':id_sala' => $idSala,
                ':id_persona' => $idPersona
            ]);

            $idReserva = $insertar->fetchColumn();

            // Consultar el estado definitivo después de ejecutar los triggers
            $consultaEstado = $conexion->prepare("
                SELECT estado
                FROM gestion.reserva
                WHERE id_reserva = :id
            ");

            $consultaEstado->execute([':id' => $idReserva]);
            $estado = $consultaEstado->fetchColumn();

            $mensaje = "Solicitud registrada. Número de reserva: "
                     . $idReserva . ". Estado: " . $estado . ".";
            $tipoMensaje = 'exito';

        } catch (PDOException $e) {

            if (($e->errorInfo[0] ?? '') === 'P0001') {
                // Regla de negocio de la base de datos: se muestra solo su mensaje,
                // sin el prefijo "ERROR:" ni la línea "CONTEXT:".
                $primeraLinea = (string) strtok($e->errorInfo[2] ?? '', "\n");
                $mensaje = trim(preg_replace('/^ERROR:\s*/', '', $primeraLinea));
            } else {
                error_log($e->getMessage());
                $mensaje = 'No fue posible registrar la solicitud. Inténtalo de nuevo.';
            }
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
    <title>Solicitar reserva - Centro TIC</title>

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
            max-width: 650px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.10);
        }

        h1 {
            color: #8B0000;
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

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button, .volver {
            display: block;
            width: 100%;
            padding: 13px;
            margin-top: 22px;
            border: none;
            border-radius: 6px;
            background: #8B0000;
            color: white;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        button:hover, .volver:hover {
            background: #650000;
        }

        .mensaje {
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
            overflow-wrap: anywhere;
        }

        .exito {
            background: #dff0d8;
            color: #285b2a;
        }

        .error {
            background: #f8d7da;
            color: #842029;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Solicitar reserva</h1>

    <p class="descripcion">
        Centro TIC - Préstamo y reserva de salas de cómputo
    </p>

    <?php if ($mensaje !== ''): ?>
        <div class="mensaje <?= escapar($tipoMensaje) ?>">
            <?= escapar($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if (count($salas) > 0): ?>

        <form method="POST">

            <label for="id_sala">Seleccionar sala</label>

            <select name="id_sala" id="id_sala" required>
                <option value="">Seleccione una sala</option>

                <?php foreach ($salas as $sala): ?>
                    <option value="<?= escapar($sala['id_sala']) ?>">
                        <?= escapar($sala['nombre']) ?>
                        - <?= escapar($sala['edificio']) ?>
                        (Capacidad: <?= escapar($sala['capacidad']) ?>)
                    </option>
                <?php endforeach; ?>

            </select>

            <label for="fecha_reserva">Fecha de reserva</label>
            <input
                type="date"
                name="fecha_reserva"
                id="fecha_reserva"
                min="<?= date('Y-m-d') ?>"
                required
            >

            <label for="hora_inicio">Hora de inicio</label>
            <input
                type="time"
                name="hora_inicio"
                id="hora_inicio"
                required
            >

            <label for="hora_fin">Hora de finalización</label>
            <input
                type="time"
                name="hora_fin"
                id="hora_fin"
                required
            >

            <button type="submit">Enviar solicitud de reserva</button>

        </form>

    <?php else: ?>

        <p>No hay salas disponibles para reservar actualmente.</p>

    <?php endif; ?>

    <a class="volver" href="../usuario/panel.php">
        Volver al panel
    </a>

</div>

</body>
</html>
