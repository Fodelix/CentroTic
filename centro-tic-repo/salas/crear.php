
<?php

require_once __DIR__ . '/../auth/seguridad.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

$mensaje = '';
$error = '';

$nombre = '';
$edificio = '';
$capacidad = '';
$estado = 'DISPONIBLE';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $edificio = trim($_POST['edificio'] ?? '');
    $capacidad = trim($_POST['capacidad'] ?? '');
    $estado = $_POST['estado'] ?? '';

    $estadosPermitidos = [
        'DISPONIBLE',
        'MANTENIMIENTO',
        'INACTIVA'
    ];

    if ($nombre === '' || $edificio === '' || $capacidad === '') {

        $error = 'Todos los campos son obligatorios.';

    } elseif (
        filter_var(
            $capacidad,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        ) === false
    ) {

        $error = 'La capacidad debe ser un número entero mayor que cero.';

    } elseif (!in_array($estado, $estadosPermitidos, true)) {

        $error = 'El estado seleccionado no es válido.';

    } else {

        try {

            $consulta = $conexion->prepare(
                'INSERT INTO gestion.sala
                    (nombre, edificio, capacidad, estado)
                 VALUES
                    (:nombre, :edificio, :capacidad, :estado)'
            );

            $consulta->execute([
                'nombre' => $nombre,
                'edificio' => $edificio,
                'capacidad' => (int) $capacidad,
                'estado' => $estado
            ]);

            $mensaje = 'La sala se registró correctamente.';

            $nombre = '';
            $edificio = '';
            $capacidad = '';
            $estado = 'DISPONIBLE';

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $error = 'No se pudo registrar la sala. Inténtalo nuevamente.';

        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrar sala - Centro TIC</title>

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

            <h2>Registrar nueva sala</h2>

            <p class="descripcion">
                Ingresa la información de la nueva sala de cómputo.
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

                    <label for="nombre">Nombre de la sala</label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        placeholder="Ej. Sala 201"
                        value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="edificio">Edificio</label>

                    <input
                        type="text"
                        id="edificio"
                        name="edificio"
                        maxlength="100"
                        placeholder="Ej. Edificio A"
                        value="<?= htmlspecialchars($edificio, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="capacidad">Capacidad</label>

                    <input
                        type="number"
                        id="capacidad"
                        name="capacidad"
                        min="1"
                        step="1"
                        placeholder="Número de personas"
                        value="<?= htmlspecialchars($capacidad, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="estado">Estado de la sala</label>

                    <select id="estado" name="estado" required>

                        <option value="DISPONIBLE"
                            <?= $estado === 'DISPONIBLE' ? 'selected' : '' ?>>
                            DISPONIBLE
                        </option>

                        <option value="MANTENIMIENTO"
                            <?= $estado === 'MANTENIMIENTO' ? 'selected' : '' ?>>
                            MANTENIMIENTO
                        </option>

                        <option value="INACTIVA"
                            <?= $estado === 'INACTIVA' ? 'selected' : '' ?>>
                            INACTIVA
                        </option>

                    </select>

                </div>

                <button type="submit">
                    Guardar sala
                </button>

            </form>

            <div class="enlaces">

                <a href="listar.php">
                    ← Consultar salas
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
