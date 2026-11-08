
<?php

require_once __DIR__ . '/../auth/seguridad.php';
require_once __DIR__ . '/../conexion.example.php';

exigirRol('ADMINISTRADOR');

function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$error = '';
$mensaje = '';

$idSala = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idSala = filter_input(INPUT_POST, 'id_sala', FILTER_VALIDATE_INT);
}

$nombre = '';
$edificio = '';
$capacidad = '';
$estado = '';

$estadosPermitidos = [
    'DISPONIBLE',
    'MANTENIMIENTO',
    'INACTIVA'
];

if (!$idSala || $idSala <= 0) {
    exit('El identificador de la sala no es válido.');
}

try {
    $consulta = $conexion->prepare(
        'SELECT id_sala, nombre, edificio, capacidad, estado
         FROM gestion.sala
         WHERE id_sala = :id_sala'
    );

    $consulta->execute(['id_sala' => $idSala]);
    $sala = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$sala) {
        exit('La sala solicitada no existe.');
    }

    $nombre = $sala['nombre'];
    $edificio = $sala['edificio'];
    $capacidad = $sala['capacidad'];
    $estado = $sala['estado'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre'] ?? '');
        $edificio = trim($_POST['edificio'] ?? '');
        $capacidad = trim($_POST['capacidad'] ?? '');
        $estado = $_POST['estado'] ?? '';

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
            $actualizar = $conexion->prepare(
                'UPDATE gestion.sala
                 SET nombre = :nombre,
                     edificio = :edificio,
                     capacidad = :capacidad,
                     estado = :estado
                 WHERE id_sala = :id_sala'
            );

            $actualizar->execute([
                'nombre' => $nombre,
                'edificio' => $edificio,
                'capacidad' => (int) $capacidad,
                'estado' => $estado,
                'id_sala' => $idSala
            ]);

            $mensaje = 'La sala se actualizó correctamente.';
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'No se pudo consultar o actualizar la sala.';
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Actualizar sala - Centro TIC</title>

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

        h2 {
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
        }

        input, select {
            width: 100%;
            padding: 13px;
            border: 1px solid #cfcfcf;
            border-radius: 7px;
            font-size: 15px;
            background: white;
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

        <h2>Actualizar sala</h2>

        <p class="descripcion">
            Modifica la información de la sala seleccionada.
        </p>

        <?php if ($mensaje !== ''): ?>
            <div class="mensaje" role="status">
                <?= escapar($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error" role="alert">
                <?= escapar($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="id_sala"
                value="<?= (int) $idSala ?>"
            >

            <div class="campo">
                <label for="nombre">Nombre de la sala</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="100"
                    value="<?= escapar($nombre) ?>"
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
                    value="<?= escapar($edificio) ?>"
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
                    value="<?= escapar($capacidad) ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="estado">Estado de la sala</label>
                <select id="estado" name="estado" required>
                    <?php foreach ($estadosPermitidos as $opcion): ?>
                        <option
                            value="<?= escapar($opcion) ?>"
                            <?= $estado === $opcion ? 'selected' : '' ?>
                        >
                            <?= escapar($opcion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">
                Guardar cambios
            </button>

        </form>

        <div class="enlaces">
            <a href="listar.php">← Consultar salas</a>
            <a href="../admin/panel.php">Volver al panel →</a>
        </div>

    </section>

</main>

<footer>
    Centro TIC - Sistema de gestión de salas
</footer>

</body>
</html>
