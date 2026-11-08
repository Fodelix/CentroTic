
<?php
require_once __DIR__ . '/../auth/seguridad.php';

exigirLogin();
exigirRol('ADMINISTRADOR');

// Cargar la conexión.
if (file_exists(__DIR__ . '/../config/conexion.example.php')) {
    require_once __DIR__ . '/../config/conexion.example.php';
} else {
    require_once __DIR__ . '/../conexion.example.php';
}

// Estados permitidos por la restricción de PostgreSQL.
$estadosPermitidos = [
    'OPERATIVO',
    'EN_REPARACION',
    'BAJA'
];

$etiquetasEstados = [
    'OPERATIVO' => 'Operativo',
    'EN_REPARACION' => 'En reparación',
    'BAJA' => 'Baja'
];

$error = '';

$codigo = '';
$marca = '';
$modelo = '';
$estado = '';
$idSala = '';

try {
    // Consultar las salas registradas.
    $consultaSalas = $conexion->query(
        "SELECT id_sala, nombre
         FROM gestion.sala
         ORDER BY nombre ASC"
    );

    $salas = $consultaSalas->fetchAll(PDO::FETCH_ASSOC);

    // Procesar el formulario.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $codigo = trim($_POST['codigo_inventario'] ?? '');
        $marca = trim($_POST['marca'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $estado = $_POST['estado'] ?? '';
        $idSala = filter_var(
            $_POST['id_sala'] ?? '',
            FILTER_VALIDATE_INT
        );

        if ($codigo === '') {
            $error = 'El código de inventario es obligatorio.';

        } elseif (!in_array($estado, $estadosPermitidos, true)) {
            $error = 'Debes seleccionar un estado válido.';

        } elseif (!$idSala || $idSala <= 0) {
            $error = 'Debes seleccionar una sala válida.';

        } else {
            // Verificar que el código de inventario no exista.
            $consultaCodigo = $conexion->prepare(
                "SELECT COUNT(*)
                 FROM gestion.equipo
                 WHERE codigo_inventario = :codigo"
            );

            $consultaCodigo->execute([
                ':codigo' => $codigo
            ]);

            if ($consultaCodigo->fetchColumn() > 0) {
                $error = 'Ese código de inventario ya está registrado.';

            } else {
                // Verificar que la sala exista.
                $consultaSala = $conexion->prepare(
                    "SELECT COUNT(*)
                     FROM gestion.sala
                     WHERE id_sala = :id_sala"
                );

                $consultaSala->execute([
                    ':id_sala' => $idSala
                ]);

                if ($consultaSala->fetchColumn() == 0) {
                    $error = 'La sala seleccionada no existe.';

                } else {
                    // Registrar el equipo.
                    $insertar = $conexion->prepare(
                        "INSERT INTO gestion.equipo
                            (codigo_inventario, marca, modelo, estado, id_sala)
                         VALUES
                            (:codigo, :marca, :modelo, :estado, :id_sala)"
                    );

                    $insertar->execute([
                        ':codigo' => $codigo,
                        ':marca' => ($marca === '') ? null : $marca,
                        ':modelo' => ($modelo === '') ? null : $modelo,
                        ':estado' => $estado,
                        ':id_sala' => $idSala
                    ]);

                    header('Location: listar.php?creado=1');
                    exit;
                }
            }
        }
    }

} catch (PDOException $e) {
    $error = 'Error de base de datos: ' . $e->getMessage();
}

function escapar($valor) {
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrar equipo - Centro TIC</title>

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
            width: 100%;
            max-width: 650px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 25px;
            color: #8B0000;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 17px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
            background: white;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #8B0000;
        }

        .botones {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        button,
        .volver {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }

        button {
            background: #8B0000;
            color: white;
        }

        button:hover {
            background: #650000;
        }

        .volver {
            background: #ddd;
            color: #222;
        }

        .volver:hover {
            background: #ccc;
        }

        .error {
            padding: 12px;
            margin-bottom: 15px;
            color: #8B0000;
            background: #ffe5e5;
            border: 1px solid #cc9999;
            border-radius: 5px;
        }

        @media (max-width: 480px) {
            .contenedor {
                padding: 20px;
            }

            .botones {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Registrar equipo</h1>

    <?php if ($error !== ''): ?>
        <div class="error">
            <strong>No se pudo registrar el equipo:</strong>
            <?= escapar($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="crear.php">

        <label for="codigo_inventario">
            Código de inventario *
        </label>

        <input
            type="text"
            id="codigo_inventario"
            name="codigo_inventario"
            value="<?= escapar($codigo) ?>"
            required
        >

        <label for="marca">Marca</label>

        <input
            type="text"
            id="marca"
            name="marca"
            value="<?= escapar($marca) ?>"
        >

        <label for="modelo">Modelo</label>

        <input
            type="text"
            id="modelo"
            name="modelo"
            value="<?= escapar($modelo) ?>"
        >

        <label for="estado">Estado *</label>

        <select id="estado" name="estado" required>
            <option value="">Selecciona un estado</option>

            <?php foreach ($estadosPermitidos as $valorEstado): ?>
                <option
                    value="<?= escapar($valorEstado) ?>"
                    <?= ($estado === $valorEstado) ? 'selected' : '' ?>
                >
                    <?= escapar($etiquetasEstados[$valorEstado]) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label for="id_sala">Sala asignada *</label>

        <select id="id_sala" name="id_sala" required>

            <option value="">Selecciona una sala</option>

            <?php foreach ($salas as $sala): ?>
                <option
                    value="<?= (int)$sala['id_sala'] ?>"
                    <?= ((string)$idSala === (string)$sala['id_sala']) ? 'selected' : '' ?>
                >
                    <?= escapar($sala['nombre']) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <div class="botones">

            <button type="submit">
                Registrar equipo
            </button>

            <a class="volver" href="listar.php">
                Volver
            </a>

        </div>

    </form>

</div>

</body>
</html>
