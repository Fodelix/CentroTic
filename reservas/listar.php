
<?php
require_once __DIR__ . '/../auth/seguridad.php';
require_once __DIR__ . '/../conexion.example.php';

exigirLogin();
exigirRol('ADMINISTRADOR', 'USUARIO');

$rol = $_SESSION['rol'] ?? '';
$idPersona = $_SESSION['id_persona'] ?? null;

$esAdmin = ($rol === 'ADMINISTRADOR');

$reservas = [];
$error = '';

try {
    if ($esAdmin) {
        $sql = "
            SELECT
                r.id_reserva,
                r.fecha_solicitud,
                r.fecha_reserva,
                r.hora_inicio,
                r.hora_fin,
                r.estado,
                r.id_sala,
                s.nombre AS nombre_sala,
                r.id_persona
            FROM gestion.reserva r
            INNER JOIN gestion.sala s
                ON r.id_sala = s.id_sala
            ORDER BY
                r.fecha_reserva DESC,
                r.hora_inicio DESC,
                r.id_reserva DESC
        ";

        $consulta = $conexion->query($sql);
    } else {
        $sql = "
            SELECT
                r.id_reserva,
                r.fecha_solicitud,
                r.fecha_reserva,
                r.hora_inicio,
                r.hora_fin,
                r.estado,
                r.id_sala,
                s.nombre AS nombre_sala,
                r.id_persona
            FROM gestion.reserva r
            INNER JOIN gestion.sala s
                ON r.id_sala = s.id_sala
            WHERE r.id_persona = :id_persona
            ORDER BY
                r.fecha_reserva DESC,
                r.hora_inicio DESC,
                r.id_reserva DESC
        ";

        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ':id_persona' => $idPersona
        ]);
    }

    $reservas = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'No fue posible consultar las reservas. Intenta nuevamente.';
}

function escapar($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatoFecha($fecha) {
    if (empty($fecha)) {
        return '';
    }

    return date('d/m/Y', strtotime($fecha));
}

function formatoHora($hora) {
    if (empty($hora)) {
        return '';
    }

    return date('H:i', strtotime($hora));
}

function claseEstado($estado) {
    switch ($estado) {
        case 'APROBADA':
            return 'aprobada';

        case 'PENDIENTE':
            return 'pendiente';

        case 'RECHAZADA':
            return 'rechazada';

        case 'CANCELADA':
            return 'cancelada';

        case 'VENCIDA':
            return 'vencida';

        default:
            return 'otro';
    }
}

$panel = $esAdmin
    ? '../admin/panel.php'
    : '../usuario/panel.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar reservas - Centro TIC</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            color: #222;
        }

        .contenedor {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.10);
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

        .acciones {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .boton {
            display: inline-block;
            padding: 11px 18px;
            background: #8B0000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .boton:hover {
            background: #650000;
        }

        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #8B0000;
            color: white;
        }

        tr:hover {
            background: #f7f7f7;
        }

        .estado {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .aprobada {
            background: #d4edda;
            color: #155724;
        }

        .pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .rechazada,
        .cancelada,
        .vencida {
            background: #f8d7da;
            color: #721c24;
        }

        .otro {
            background: #e2e3e5;
            color: #383d41;
        }

        .mensaje {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .vacio {
            background: #f2f2f2;
            color: #555;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .contenedor {
                padding: 18px;
            }

            .acciones {
                flex-direction: column;
            }

            .boton {
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>
        <?php echo $esAdmin ? 'Consultar todas las reservas' : 'Mis reservas'; ?>
    </h1>

    <p class="descripcion">
        Centro TIC - Préstamo y reserva de salas de cómputo
    </p>

    <div class="acciones">
        <a href="<?php echo escapar($panel); ?>" class="boton">
            Volver al panel
        </a>

        <?php if (!$esAdmin): ?>
            <a href="crear.php" class="boton">
                Solicitar reserva
            </a>
        <?php endif; ?>
    </div>

    <?php if ($error !== ''): ?>

        <div class="mensaje error">
            <?php echo escapar($error); ?>
        </div>

    <?php elseif (count($reservas) === 0): ?>

        <div class="mensaje vacio">
            No hay reservas registradas para mostrar.
        </div>

    <?php else: ?>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Fecha de solicitud</th>
                        <th>Fecha de reserva</th>
                        <th>Hora de inicio</th>
                        <th>Hora de fin</th>
                        <th>Sala</th>

                        <?php if ($esAdmin): ?>
                            <th>ID persona</th>
                        <?php endif; ?>

                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <td>
                                <?php echo escapar($reserva['id_reserva']); ?>
                            </td>

                            <td>
                                <?php
                                echo escapar(
                                    date(
                                        'd/m/Y H:i',
                                        strtotime($reserva['fecha_solicitud'])
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo escapar(formatoFecha($reserva['fecha_reserva'])); ?>
                            </td>

                            <td>
                                <?php echo escapar(formatoHora($reserva['hora_inicio'])); ?>
                            </td>

                            <td>
                                <?php echo escapar(formatoHora($reserva['hora_fin'])); ?>
                            </td>

                            <td>
                                <?php echo escapar($reserva['nombre_sala']); ?>
                            </td>

                            <?php if ($esAdmin): ?>
                                <td>
                                    <?php echo escapar($reserva['id_persona']); ?>
                                </td>
                            <?php endif; ?>

                            <td>
                                <span class="estado <?php echo escapar(claseEstado($reserva['estado'])); ?>">
                                    <?php echo escapar($reserva['estado']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
