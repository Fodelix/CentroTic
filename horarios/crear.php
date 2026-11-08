<?php

require_once __DIR__ . '/../auth/seguridad.php';
require_once __DIR__ . '/../conexion.example.php';

exigirLogin();
exigirRol('ADMINISTRADOR');

$mensaje = '';
$error = '';

$dia_semana = '';
$hora_inicio = '';
$hora_fin = '';
$id_sala = '';

$dias = [
    'LUNES',
    'MARTES',
    'MIERCOLES',
    'JUEVES',
    'VIERNES',
    'SABADO',
    'DOMINGO'
];

// Consultar las salas registradas
try {

    $consultaSalas = $conexion->query(
        'SELECT id_sala, nombre
         FROM gestion.sala
         ORDER BY id_sala ASC'
    );

    $salas = $consultaSalas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log($e->getMessage());
    $salas = [];
    $error = 'No fue posible consultar las salas registradas.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {

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

            // Verificar que la sala exista
            $verificarSala = $conexion->prepare(
                'SELECT id_sala
                 FROM gestion.sala
                 WHERE id_sala = :id_sala'
            );

            $verificarSala->execute([
                'id_sala' => (int) $id_sala
            ]);

            if (!$verificarSala->fetch(PDO::FETCH_ASSOC)) {

                $error = 'La sala seleccionada no existe.';

            } else {

                // Registrar el horario
                $consulta = $conexion->prepare(
                    'INSERT INTO gestion.horario
                        (dia_semana, hora_inicio, hora_fin, id_sala)
                     VALUES
                        (:dia_semana, :hora_inicio, :hora_fin, :id_sala)'
                );

                $consulta->execute([
                    'dia_semana' => $dia_semana,
                    'hora_inicio' => $hora_inicio,
                    'hora_fin' => $hora_fin,
                    'id_sala' => (int) $id_sala
                ]);

                $mensaje = 'El horario se registró correctamente.';

                $dia_semana = '';
                $hora_inicio = '';
                $hora_fin = '';
                $id_sala = '';
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $codigoError = $e->errorInfo[0] ?? $e->getCode();

            if ($codigoError === '23505') {

                $error = 'Ya existe un horario con esa sala, día y hora de inicio.';

            } elseif ($codigoError === '23514') {

                $error = 'Los datos del horario no cumplen las condiciones permitidas.';

            } else {

                $error = 'No se pudo registrar el horario. Verifica que no se cruce con otro horario de la misma sala.';
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

    <title>Registrar horario - Centro TIC</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #000000;
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
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            border: 1px solid #e5e5e5;
        }

        .formulario h2 {
            color: #000000;
            font-size: 25px;
            margin-bottom: 10px;
        }

        .descripcion {
            color: #333333;
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
            color: #000000;
        }

        input, select {
            width: 100%;
            padding: 13px;
            border: 1px solid #cfcfcf;
            border-radius: 7px;
            font-size: 15px;
            background: white;
            color: #000000;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139,0,0,0.12);
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

        .mensaje {
            background: #f1f1f1;
            color: #000000;
            border-left: 5px solid #8B0000;
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .error {
            background: #fff0f0;
            color: #000000;
            border-left: 5px solid #8B0000;
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 20px;
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

        .enlaces a:hover {
            color: #650000;
            text-decoration: underline;
        }

        footer {
            text-align: center;
            color: #555555;
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

            <h2>Registrar nuevo horario</h2>

            <p class="descripcion">
                Ingresa los días y las horas de disponibilidad de una sala de cómputo.
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
                                <?= (string) $id_sala === (string) $sala['id_sala'] ? 'selected' : '' ?>
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

                <button type="submit">
                    Guardar horario
                </button>

            </form>

            <div class="enlaces">

                <a href="Listar.php">
                    ← Consultar horarios
                </a>

                <a href="../admin/panel.php">
                    Volver al panel →
                </a>

            </div>

        </section>

    </main>

    <footer>
        Centro TIC - Sistema de gestión académica y reservas
    </footer>

</body>

</html>