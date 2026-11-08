
<?php

require_once __DIR__ . '/../auth/seguridad.php';
require_once __DIR__ . '/../conexion.example.php';

exigirLogin();
exigirRol('ADMINISTRADOR');

$mensaje = '';
$error = '';

$dias = [
    'LUNES',
    'MARTES',
    'MIERCOLES',
    'JUEVES',
    'VIERNES',
    'SABADO',
    'DOMINGO'
];

$id_horario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_horario || $id_horario < 1) {
    header('Location: Listar.php');
    exit;
}

try {
    $consultaSalas = $conexion->query(
        'SELECT id_sala, nombre
         FROM gestion.sala
         ORDER BY id_sala'
    );

    $salas = $consultaSalas->fetchAll(PDO::FETCH_ASSOC);

    $consultaHorario = $conexion->prepare(
        'SELECT id_horario, dia_semana, hora_inicio,
                hora_fin, id_sala
         FROM gestion.horario
         WHERE id_horario = :id'
    );

    $consultaHorario->execute(['id' => $id_horario]);
    $horario = $consultaHorario->fetch(PDO::FETCH_ASSOC);

    if (!$horario) {
        header('Location: Listar.php');
        exit;
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    die('No fue posible consultar el horario.');
}

$dia_semana = $horario['dia_semana'];
$hora_inicio = substr($horario['hora_inicio'], 0, 5);
$hora_fin = substr($horario['hora_fin'], 0, 5);
$id_sala = (string) $horario['id_sala'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dia_semana = $_POST['dia_semana'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fin = $_POST['hora_fin'] ?? '';
    $id_sala = $_POST['id_sala'] ?? '';

    if (
        $dia_semana === '' ||
        $hora_inicio === '' ||
        $hora_fin === '' ||
        $id_sala === ''
    ) {
        $error = 'Todos los campos son obligatorios.';

    } elseif (!in_array($dia_semana, $dias, true)) {
        $error = 'El día seleccionado no es válido.';

    } elseif (
        !preg_match('/^\d{2}:\d{2}$/', $hora_inicio) ||
        !preg_match('/^\d{2}:\d{2}$/', $hora_fin)
    ) {
        $error = 'El formato de las horas no es válido.';

    } elseif ($hora_fin <= $hora_inicio) {
        $error = 'La hora de finalización debe ser posterior a la hora de inicio.';

    } elseif (
        filter_var(
            $id_sala,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        ) === false
    ) {
        $error = 'La sala seleccionada no es válida.';

    } else {

        try {

            $actualizar = $conexion->prepare(
                'UPDATE gestion.horario
                 SET dia_semana = :dia,
                     hora_inicio = :inicio,
                     hora_fin = :fin,
                     id_sala = :sala
                 WHERE id_horario = :id'
            );

            $actualizar->execute([
                'dia' => $dia_semana,
                'inicio' => $hora_inicio,
                'fin' => $hora_fin,
                'sala' => (int) $id_sala,
                'id' => $id_horario
            ]);

            $mensaje = 'El horario se actualizó correctamente.';

            $consultaHorario->execute(['id' => $id_horario]);
            $horario = $consultaHorario->fetch(PDO::FETCH_ASSOC);

            $dia_semana = $horario['dia_semana'];
            $hora_inicio = substr($horario['hora_inicio'], 0, 5);
            $hora_fin = substr($horario['hora_fin'], 0, 5);
            $id_sala = (string) $horario['id_sala'];

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $codigo = $e->errorInfo[0] ?? $e->getCode();

            if ($codigo === '23505') {
                $error = 'Ya existe un horario con esa sala, día y hora de inicio.';
            } elseif ($codigo === '23514') {
                $error = 'El horario no cumple las restricciones de la base de datos.';
            } else {
                $error = 'No se pudo actualizar. Comprueba que no se cruce con otro horario de la misma sala.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar horario - Centro TIC</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #000;
            min-height: 100vh;
        }

        header {
            background: #8B0000;
            color: white;
            text-align: center;
            padding: 30px 20px;
        }

        header h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        header p {
            color: #f5cccc;
        }

        main {
            max-width: 750px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .formulario {
            background: white;
            padding: 35px;
            border-radius: 13px;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
            border: 1px solid #e5e5e5;
        }

        h2 {
            font-size: 25px;
            margin-bottom: 10px;
        }

        .descripcion {
            color: #333;
            font-size: 14px;
            margin-bottom: 28px;
        }

        .campo {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }

        input, select {
            width: 100%;
            padding: 13px;
            border: 1px solid #cfcfcf;
            border-radius: 7px;
            font-size: 15px;
            background: white;
            color: black;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139,0,0,.12);
        }

        button {
            width: 100%;
            padding: 14px;
            background: #8B0000;
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 8px;
        }

        button:hover {
            background: #650000;
        }

        .mensaje, .error {
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .mensaje {
            background: #f1f1f1;
            border-left: 5px solid #8B0000;
        }

        .error {
            background: #fff0f0;
            border-left: 5px solid #8B0000;
        }

        .enlaces {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 25px;
        }

        .enlaces a {
            color: #8B0000;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        footer {
            text-align: center;
            color: #555;
            padding: 20px;
            font-size: 13px;
        }

        @media (max-width: 500px) {
            .formulario {
                padding: 23px;
            }

            .enlaces {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>CENTRO TIC</h1>
    <p>Sistema de préstamo y reserva de salas</p>
</header>

<main>
    <section class="formulario">

        <h2>Actualizar horario</h2>

        <p class="descripcion">
            Modifica el día, las horas o la sala del horario seleccionado.
        </p>

        <?php if ($mensaje !== ''): ?>
            <div class="mensaje" role="status">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="campo">
                <label for="id_sala">Sala de cómputo</label>

                <select id="id_sala" name="id_sala" required>
                    <option value="">Selecciona una sala</option>

                    <?php foreach ($salas as $sala): ?>
                        <option
                            value="<?= (int) $sala['id_sala'] ?>"
                            <?= $id_sala === (string) $sala['id_sala'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($sala['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="dia_semana">Día de la semana</label>

                <select id="dia_semana" name="dia_semana" required>
                    <option value="">Selecciona un día</option>

                    <?php foreach ($dias as $dia): ?>
                        <option
                            value="<?= $dia ?>"
                            <?= $dia_semana === $dia ? 'selected' : '' ?>
                        >
                            <?= $dia ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="hora_inicio">Hora de inicio</label>
                <input
                    type="time"
                    id="hora_inicio"
                    name="hora_inicio"
                    value="<?= htmlspecialchars($hora_inicio, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="hora_fin">Hora de finalización</label>
                <input
                    type="time"
                    id="hora_fin"
                    name="hora_fin"
                    value="<?= htmlspecialchars($hora_fin, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <button type="submit">Guardar cambios</button>

        </form>

        <div class="enlaces">
            <a href="Listar.php">← Consultar horarios</a>
            <a href="../admin/panel.php">Volver al panel →</a>
        </div>

    </section>
</main>

<footer>
    Centro TIC - Sistema de gestión académica y reservas
</footer>

</body>
</html>
