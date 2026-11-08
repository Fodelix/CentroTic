
<?php

require_once __DIR__ . '/auth/seguridad.php';

// Cargar las cuentas almacenadas fuera de htdocs.
$archivoUsuarios = require __DIR__ . '/config/rutas.php';
$usuarios = require $archivoUsuarios;

// Si ya inició sesión, enviarlo al panel correspondiente.
if (isset($_SESSION['id_usuario'])) {

    if (($_SESSION['rol'] ?? '') === 'ADMINISTRADOR') {
        header('Location: admin/panel.php');
    } elseif (($_SESSION['rol'] ?? '') === 'USUARIO') {
        header('Location: usuario/panel.php');
    } else {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
    }

    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($nombre_usuario === '' || $contrasena === '') {

        $mensaje = 'Ingresa tu usuario y contraseña.';

    } else {

        $usuario = null;

        // Buscar la cuenta en el archivo privado.
        foreach ($usuarios as $cuenta) {

            if (
                strtolower($cuenta['nombre_usuario']) ===
                strtolower($nombre_usuario)
            ) {
                $usuario = $cuenta;
                break;
            }
        }

        // Validar cuenta, rol, estado y contraseña.
        if (
            $usuario !== null &&
            in_array(
                $usuario['rol'],
                ['ADMINISTRADOR', 'USUARIO'],
                true
            ) &&
            $usuario['activo'] === true &&
            password_verify(
                $contrasena,
                $usuario['contrasena_hash']
            )
        ) {

            session_regenerate_id(true);

            // id_usuario se conserva por compatibilidad
            // con las páginas que ya utilizan esta variable.
            $_SESSION['id_usuario'] = $usuario['id_persona'];
            $_SESSION['id_persona'] = $usuario['id_persona'];
            $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
            $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
            $_SESSION['rol'] = $usuario['rol'];

            if ($usuario['rol'] === 'ADMINISTRADOR') {
                header('Location: admin/panel.php');
            } else {
                header('Location: usuario/panel.php');
            }

            exit;

        } else {

            $mensaje = 'Usuario o contraseña incorrectos.';

        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión - Centro TIC</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #000000;
        }

        header {
            background: #8B0000;
            color: white;
            text-align: center;
            padding: 35px 20px;
        }

        header h1 {
            font-size: 34px;
            margin-bottom: 10px;
            color: white;
        }

        header p {
            color: #f5cccc;
            font-size: 15px;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 35px 20px;
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 430px;
            padding: 38px;
            border-radius: 14px;
            border: 1px solid #e5e5e5;
            box-shadow: 0 5px 22px rgba(0,0,0,0.07);
        }

        .login-card h2 {
            text-align: center;
            font-size: 26px;
            margin-bottom: 10px;
            color: #000000;
        }

        .descripcion {
            text-align: center;
            color: #444444;
            margin-bottom: 30px;
            font-size: 14px;
            line-height: 1.5;
        }

        .campo {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
            color: #000000;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #cccccc;
            border-radius: 7px;
            font-size: 15px;
            outline: none;
            color: #000000;
            background: white;
        }

        input:focus {
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
            background: #fff0f0;
            color: #8B0000;
            border: 1px solid #e5bcbc;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .volver {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: #8B0000;
            text-decoration: none;
            font-size: 14px;
        }

        .volver:hover {
            color: #650000;
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #555555;
            font-size: 13px;
        }

        @media (max-width: 480px) {

            .login-card {
                padding: 25px 20px;
            }

            header h1 {
                font-size: 28px;
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

        <section class="login-card">

            <h2>Iniciar sesión</h2>

            <p class="descripcion">
                Ingresa tus credenciales para acceder
                al sistema Centro TIC.
            </p>

            <?php if ($mensaje !== ''): ?>

                <div class="mensaje" role="alert">

                    <?= htmlspecialchars(
                        $mensaje,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>

            <form action="" method="POST">

                <div class="campo">

                    <label for="nombre_usuario">
                        Nombre de usuario
                    </label>

                    <input
                        type="text"
                        id="nombre_usuario"
                        name="nombre_usuario"
                        maxlength="50"
                        autocomplete="username"
                        placeholder="Ingresa tu usuario"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="contrasena">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="contrasena"
                        name="contrasena"
                        autocomplete="current-password"
                        placeholder="Ingresa tu contraseña"
                        required
                    >

                </div>

                <button type="submit">
                    Ingresar al sistema
                </button>

            </form>

            <a href="index.php" class="volver">
                Volver al inicio
            </a>

        </section>

    </main>

    <footer>
        Centro TIC - Sistema de gestión académica y reservas
    </footer>

</body>

</html>
