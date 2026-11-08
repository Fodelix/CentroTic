<?php

require_once __DIR__ . '/../auth/seguridad.php';
require_once __DIR__ . '/../conexion.example.php';

// Permitir consultar salas a los roles autorizados.
exigirRol('ADMINISTRADOR', 'USUARIO');

$esAdministrador = ($_SESSION['rol'] === 'ADMINISTRADOR');

$urlPanel = $esAdministrador
    ? '../admin/panel.php'
    : '../usuario/panel.php';

try {

    $consulta = $conexion->query(
        'SELECT * FROM gestion.sala ORDER BY id_sala ASC'
    );

    $salas = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log($e->getMessage());
    exit('No se pudieron consultar las salas.');

}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Consultar salas - Centro TIC</title>

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
            color: white;
        }

        header p {
            color: #f5cccc;
            font-size: 15px;
        }

        main {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .contenedor-salas {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            border: 1px solid #e5e5e5;
        }

        h2 {
            color: #000000;
            font-size: 25px;
            margin-bottom: 12px;
        }

        .descripcion {
            color: #333333;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .volver {
            display: inline-block;
            color: #8B0000;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .volver:hover {
            color: #650000;
            text-decoration: underline;
        }

        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: #8B0000;
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-size: 14px;
            white-space: nowrap;
        }

        td {
            padding: 14px 15px;
            border-bottom: 1px solid #e5e5e5;
            color: #000000;
            font-size: 14px;
        }

        tbody tr:nth-child(even) {
            background: #f8f8f8;
        }

        tbody tr:hover {
            background: #fceeee;
        }

        .btn-actualizar {
            display: inline-block;
            background: #8B0000;
            color: white;
            text-decoration: none;
            padding: 9px 13px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        .btn-actualizar:hover {
            background: #650000;
        }

        .sin-registros {
            background: #f5f5f5;
            border-left: 5px solid #8B0000;
            padding: 18px;
            border-radius: 7px;
            color: #000000;
            margin-top: 15px;
        }

        footer {
            text-align: center;
            color: #555555;
            padding: 25px 15px;
            font-size: 13px;
        }

        @media (max-width: 600px) {

            main {
                margin: 25px auto;
            }

            .contenedor-salas {
                padding: 20px;
            }

            h2 {
                font-size: 21px;
            }

            th, td {
                padding: 11px;
                font-size: 13px;
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

        <section class="contenedor-salas">

            <h2>Salas registradas</h2>

            <p class="descripcion">
                Consulta la información de las salas de cómputo
                registradas en el sistema Centro TIC.
            </p>

            <a
                href="<?= htmlspecialchars($urlPanel, ENT_QUOTES, 'UTF-8') ?>"
                class="volver"
            >
                ← Volver al panel principal
            </a>

            <?php if (!empty($salas)): ?>

                <div class="tabla-contenedor">

                    <table>

                        <thead>

                            <tr>

                                <?php foreach (array_keys($salas[0]) as $columna): ?>

                                    <th>
                                        <?= htmlspecialchars((string) $columna, ENT_QUOTES, 'UTF-8') ?>
                                    </th>

                                <?php endforeach; ?>

                                <?php if ($esAdministrador): ?>
                                    <th>Acciones</th>
                                <?php endif; ?>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($salas as $sala): ?>

                                <tr>

                                    <?php foreach ($sala as $valor): ?>

                                        <td>
                                            <?= htmlspecialchars((string) ($valor ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>

                                    <?php endforeach; ?>

                                    <?php if ($esAdministrador): ?>

                                        <td>

                                            <a
                                                href="editar.php?id=<?= (int) $sala['id_sala'] ?>"
                                                class="btn-actualizar"
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

                <div class="sin-registros">
                    No hay salas registradas todavía.
                </div>

            <?php endif; ?>

        </section>

    </main>

    <footer>
        Centro TIC - Sistema de gestión académica y reservas
    </footer>

</body>

</html>
