
<?php
require_once __DIR__ . '/../auth/seguridad.php';
require_once __DIR__ . '/../conexion.example.php';

exigirLogin();
exigirRol('ADMINISTRADOR', 'USUARIO');

$esAdmin = ($_SESSION['rol'] ?? '') === 'ADMINISTRADOR';

// Determinar el panel al que debe regresar según el rol.
$panelRegreso = $esAdmin
    ? '../admin/panel.php'
    : '../usuario/panel.php';

try {
    $consulta = $conexion->query(
        "SELECT
            h.id_horario,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            h.id_sala,
            s.nombre AS nombre_sala,
            s.edificio
         FROM gestion.horario h
         INNER JOIN gestion.sala s
            ON h.id_sala = s.id_sala
         ORDER BY
            h.id_sala,
            CASE h.dia_semana
                WHEN 'LUNES' THEN 1
                WHEN 'MARTES' THEN 2
                WHEN 'MIERCOLES' THEN 3
                WHEN 'JUEVES' THEN 4
                WHEN 'VIERNES' THEN 5
                WHEN 'SABADO' THEN 6
                WHEN 'DOMINGO' THEN 7
            END,
            h.hora_inicio"
    );

    $horarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $horarios = [];
    $error = 'No fue posible consultar los horarios.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Consultar horarios - Centro TIC</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            color: #000;
        }

        .contenedor {
            max-width: 1150px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.12);
        }

        h1 {
            color: #8B0000;
            text-align: center;
            margin-bottom: 25px;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 13px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #8B0000;
            color: white;
        }

        tr:nth-child(even) {
            background: #f5f5f5;
        }

        .boton {
            display: inline-block;
            padding: 10px 15px;
            margin: 5px 3px;
            border: none;
            border-radius: 5px;
            background: #8B0000;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .boton:hover {
            background: #650000;
        }

        .sin-datos {
            padding: 18px;
            margin-top: 20px;
            text-align: center;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .acciones {
            text-align: center;
            margin-top: 25px;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Horarios de las salas</h1>

    <?php if (isset($error)): ?>

        <div class="sin-datos">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php elseif (count($horarios) > 0): ?>

        <div class="tabla-contenedor">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sala</th>
                        <th>Edificio</th>
                        <th>Día</th>
                        <th>Hora de inicio</th>
                        <th>Hora de finalización</th>

                        <?php if ($esAdmin): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($horarios as $horario): ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars($horario['id_horario'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($horario['nombre_sala'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($horario['edificio'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($horario['dia_semana'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(substr($horario['hora_inicio'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(substr($horario['hora_fin'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <?php if ($esAdmin): ?>
                                <td>
                                    <a
                                        class="boton"
                                        href="editar.php?id=<?= urlencode($horario['id_horario']) ?>"
                                    >
                                        Actualizar
                                    </a>
                                </td>
                            <?php endif; ?>

                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php else: ?>

        <div class="sin-datos">
            Todavía no hay horarios registrados.
        </div>

    <?php endif; ?>

    <div class="acciones">

        <?php if ($esAdmin): ?>
            <a class="boton" href="crear.php">
                Registrar horario
            </a>
        <?php endif; ?>

        <a class="boton" href="<?= htmlspecialchars($panelRegreso, ENT_QUOTES, 'UTF-8') ?>">
            Volver al panel
        </a>

    </div>

</div>

</body>
</html>
