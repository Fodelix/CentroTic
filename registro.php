
<?php
session_start();

require_once __DIR__ . '/conexion.example.php';

$archivoUsuarios = require __DIR__ . '/config/rutas.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo = $_POST['tipo'] ?? '';
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $codigo_estudiante = trim($_POST['codigo_estudiante'] ?? '');
    $codigo_docente = trim($_POST['codigo_docente'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar = $_POST['confirmar_contrasena'] ?? '';

    if (!in_array($tipo, ['ESTUDIANTE', 'DOCENTE'], true)) {
        $mensaje = 'Selecciona un perfil válido.';

    } elseif (
        $nombres === '' ||
        $apellidos === '' ||
        $correo === '' ||
        $nombre_usuario === '' ||
        $contrasena === ''
    ) {
        $mensaje = 'Completa todos los campos obligatorios.';

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El correo electrónico no es válido.';

    } elseif (strlen($contrasena) < 8) {
        $mensaje = 'La contraseña debe tener al menos 8 caracteres.';

    } elseif ($contrasena !== $confirmar) {
        $mensaje = 'Las contraseñas no coinciden.';

    } elseif (strlen($nombre_usuario) > 50) {
        $mensaje = 'El nombre de usuario admite máximo 50 caracteres.';

    } elseif (
        ($tipo === 'ESTUDIANTE' && $codigo_estudiante === '') ||
        ($tipo === 'DOCENTE' && $codigo_docente === '')
    ) {
        $mensaje = 'Debes ingresar el código correspondiente a tu perfil.';

    } elseif (!is_file($archivoUsuarios) || !is_writable($archivoUsuarios)) {
        $mensaje = 'No se encuentra el archivo privado de usuarios o no tiene permisos de escritura.';

    } else {

        $bloqueo = null;
        $transaccionIniciada = false;
        $archivoActualizado = false;
        $contenidoAnterior = null;
        $temporal = null;

        try {

            // 1. Bloquear el archivo para evitar registros simultáneos.
            $bloqueo = fopen($archivoUsuarios . '.lock', 'c');

            if ($bloqueo === false || !flock($bloqueo, LOCK_EX)) {
                throw new RuntimeException(
                    'No fue posible acceder al archivo de usuarios.'
                );
            }

            // 2. Leer las cuentas existentes.
            $usuarios = require $archivoUsuarios;

            if (!is_array($usuarios)) {
                throw new RuntimeException(
                    'El archivo privado de usuarios no tiene un formato válido.'
                );
            }

            // 3. Comprobar que el nombre de usuario no exista.
            foreach ($usuarios as $usuarioExistente) {
                if (
                    isset($usuarioExistente['nombre_usuario']) &&
                    strtolower($usuarioExistente['nombre_usuario']) === strtolower($nombre_usuario)
                ) {
                    throw new DomainException(
                        'Ese nombre de usuario ya está ocupado.'
                    );
                }
            }

            // 4. Iniciar la transacción de PostgreSQL.
            $conexion->beginTransaction();
            $transaccionIniciada = true;

            // 5. Comprobar que el correo no exista.
            $consulta = $conexion->prepare(
                'SELECT 1
                 FROM gestion.persona
                 WHERE LOWER(correo) = LOWER(:correo)'
            );

            $consulta->execute([
                'correo' => $correo
            ]);

            if ($consulta->fetchColumn()) {
                throw new DomainException(
                    'Ya existe una persona registrada con ese correo.'
                );
            }

            // 6. Comprobar que el código institucional no esté repetido.
            if ($tipo === 'ESTUDIANTE') {

                $consulta = $conexion->prepare(
                    'SELECT 1
                     FROM gestion.estudiante
                     WHERE codigo_estudiante = :codigo'
                );

                $consulta->execute([
                    'codigo' => $codigo_estudiante
                ]);

            } else {

                $consulta = $conexion->prepare(
                    'SELECT 1
                     FROM gestion.docente
                     WHERE codigo_docente = :codigo'
                );

                $consulta->execute([
                    'codigo' => $codigo_docente
                ]);
            }

            if ($consulta->fetchColumn()) {
                throw new DomainException(
                    'El código institucional ya está registrado.'
                );
            }

            // 7. Registrar la persona.
            $consulta = $conexion->prepare(
                'INSERT INTO gestion.persona
                    (nombres, apellidos, correo)
                 VALUES
                    (:nombres, :apellidos, :correo)
                 RETURNING id_persona'
            );

            $consulta->execute([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'correo' => $correo
            ]);

            $id_persona = $consulta->fetchColumn();

            // 8. Registrar el perfil académico.
            if ($tipo === 'ESTUDIANTE') {

                $consulta = $conexion->prepare(
                    'INSERT INTO gestion.estudiante
                        (id_persona, codigo_estudiante)
                     VALUES
                        (:id_persona, :codigo)'
                );

                $consulta->execute([
                    'id_persona' => $id_persona,
                    'codigo' => $codigo_estudiante
                ]);

            } else {

                $consulta = $conexion->prepare(
                    'INSERT INTO gestion.docente
                        (id_persona, codigo_docente, telefono)
                     VALUES
                        (:id_persona, :codigo, :telefono)'
                );

                $consulta->execute([
                    'id_persona' => $id_persona,
                    'codigo' => $codigo_docente,
                    'telefono' => $telefono !== '' ? $telefono : null
                ]);
            }

            // 9. Preparar las credenciales.
            $nuevoUsuario = [
                'id_persona' => (int) $id_persona,
                'nombre_usuario' => $nombre_usuario,
                'nombre_completo' => $nombres . ' ' . $apellidos,
                'contrasena_hash' => password_hash(
                    $contrasena,
                    PASSWORD_DEFAULT
                ),
                'rol' => 'USUARIO',
                'activo' => true
            ];

            // 10. Actualizar el archivo privado.
            $contenidoAnterior = file_get_contents($archivoUsuarios);

            if ($contenidoAnterior === false) {
                throw new RuntimeException(
                    'No fue posible leer el archivo privado.'
                );
            }

            $nuevosUsuarios = $usuarios;
            $nuevosUsuarios[] = $nuevoUsuario;

            $nuevoContenido = "<?php\nreturn " .
                var_export($nuevosUsuarios, true) .
                ";\n";

            $temporal = tempnam(
                dirname($archivoUsuarios),
                'usuarios_'
            );

            if ($temporal === false) {
                throw new RuntimeException(
                    'No fue posible preparar el archivo de usuarios.'
                );
            }

            if (file_put_contents($temporal, $nuevoContenido) === false) {
                throw new RuntimeException(
                    'No fue posible guardar las nuevas credenciales.'
                );
            }

            if (!rename($temporal, $archivoUsuarios)) {
                throw new RuntimeException(
                    'No fue posible actualizar el archivo privado de usuarios.'
                );
            }

            $temporal = null;
            $archivoActualizado = true;

            // 11. Confirmar la transacción.
            $conexion->commit();
            $transaccionIniciada = false;

            $mensaje = '¡Registro exitoso! Ya puedes iniciar sesión.';
            $tipo_mensaje = 'exito';

            $_POST = [];

        } catch (DomainException $e) {

            $mensaje = $e->getMessage();

        } catch (Throwable $e) {

            error_log($e->getMessage());

            $mensaje = 'No fue posible completar el registro. Revisa la conexión y los permisos del archivo privado.';

        } finally {

            // Si algo falla, deshacer los cambios de PostgreSQL.
            if ($transaccionIniciada && $conexion->inTransaction()) {
                $conexion->rollBack();
            }

            // Si falló la transacción después de actualizar el archivo,
            // restaurar las credenciales anteriores.
            if (
                $archivoActualizado &&
                $transaccionIniciada &&
                $contenidoAnterior !== null
            ) {
                file_put_contents(
                    $archivoUsuarios,
                    $contenidoAnterior,
                    LOCK_EX
                );
            }

            if ($temporal !== null && is_file($temporal)) {
                unlink($temporal);
            }

            if (is_resource($bloqueo)) {
                flock($bloqueo, LOCK_UN);
                fclose($bloqueo);
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

    <title>Registro | Centro TIC</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f4f4;
            color: #222;
            min-height: 100vh;
        }

        header {
            background: linear-gradient(120deg, #650000, #8B0000);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        header p {
            color: #f3dede;
            font-size: 15px;
        }

        .contenedor {
            width: 92%;
            max-width: 650px;
            margin: 35px auto;
        }

        .tarjeta {
            background: white;
            padding: 35px;
            border-radius: 12px;
            border-top: 5px solid #8B0000;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .tarjeta h2 {
            color: #8B0000;
            margin-bottom: 10px;
            font-size: 25px;
        }

        .descripcion {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 2px rgba(139,0,0,0.1);
        }

        .separador {
            border: none;
            border-top: 1px solid #e5e5e5;
            margin: 25px 0;
        }

        button {
            width: 100%;
            padding: 14px;
            margin-top: 25px;
            background: #8B0000;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #650000;
        }

        .mensaje {
            padding: 14px;
            margin-bottom: 22px;
            border-radius: 6px;
            line-height: 1.5;
            font-size: 14px;
        }

        .mensaje.error {
            background: #fceaea;
            color: #800000;
            border-left: 4px solid #8B0000;
        }

        .mensaje.exito {
            background: #e8f5e9;
            color: #24632b;
            border-left: 4px solid #398443;
        }

        .enlaces {
            text-align: center;
            margin-top: 25px;
            line-height: 2;
            font-size: 14px;
        }

        .enlaces a {
            color: #8B0000;
            font-weight: bold;
            text-decoration: none;
        }

        .enlaces a:hover {
            text-decoration: underline;
        }

        footer {
            background: #1c1c1c;
            color: #ccc;
            text-align: center;
            padding: 20px;
            font-size: 13px;
            margin-top: 40px;
        }

        @media (max-width: 600px) {
            .tarjeta {
                padding: 23px 18px;
            }

            header h1 {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>Centro TIC</h1>
    <p>Plataforma de servicios académicos y tecnológicos</p>
</header>

<main class="contenedor">

    <section class="tarjeta">

        <h2>Crear una cuenta</h2>

        <p class="descripcion">
            Registra tus datos académicos para acceder a los servicios
            del Centro TIC.
        </p>

        <?php if ($mensaje !== ''): ?>
            <div class="mensaje <?= $tipo_mensaje === 'exito' ? 'exito' : 'error' ?>"
                 role="status">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">

            <label for="tipo">Tipo de perfil</label>

            <select name="tipo" id="tipo" required>
                <option value="">Selecciona una opción</option>

                <option value="ESTUDIANTE"
                    <?= (($_POST['tipo'] ?? '') === 'ESTUDIANTE') ? 'selected' : '' ?>>
                    Estudiante
                </option>

                <option value="DOCENTE"
                    <?= (($_POST['tipo'] ?? '') === 'DOCENTE') ? 'selected' : '' ?>>
                    Docente
                </option>
            </select>

            <label for="nombres">Nombres</label>

            <input type="text" id="nombres" name="nombres"
                   maxlength="100"
                   value="<?= htmlspecialchars($_POST['nombres'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   required>

            <label for="apellidos">Apellidos</label>

            <input type="text" id="apellidos" name="apellidos"
                   maxlength="100"
                   value="<?= htmlspecialchars($_POST['apellidos'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   required>

            <label for="correo">Correo electrónico</label>

            <input type="email" id="correo" name="correo"
                   maxlength="150"
                   value="<?= htmlspecialchars($_POST['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   required>

            <div id="campo-estudiante" hidden>

                <label for="codigo_estudiante">Código de estudiante</label>

                <input type="text" id="codigo_estudiante"
                       name="codigo_estudiante"
                       value="<?= htmlspecialchars($_POST['codigo_estudiante'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            </div>

            <div id="campo-docente" hidden>

                <label for="codigo_docente">Código de docente</label>

                <input type="text" id="codigo_docente"
                       name="codigo_docente"
                       value="<?= htmlspecialchars($_POST['codigo_docente'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <label for="telefono">Teléfono (opcional)</label>

                <input type="tel" id="telefono" name="telefono"
                       value="<?= htmlspecialchars($_POST['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            </div>

            <hr class="separador">

            <label for="nombre_usuario">Nombre de usuario</label>

            <input type="text" id="nombre_usuario"
                   name="nombre_usuario" maxlength="50"
                   value="<?= htmlspecialchars($_POST['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   required>

            <label for="contrasena">Contraseña</label>

            <input type="password" id="contrasena"
                   name="contrasena" minlength="8" required>

            <label for="confirmar_contrasena">Confirmar contraseña</label>

            <input type="password" id="confirmar_contrasena"
                   name="confirmar_contrasena" minlength="8" required>

            <button type="submit">Crear cuenta</button>

        </form>

        <div class="enlaces">
            ¿Ya tienes una cuenta?
            <a href="login.php">Inicia sesión</a>
            <br>
            <a href="index.php">Volver al inicio</a>
        </div>

    </section>

</main>

<footer>
    <strong>Centro TIC</strong><br>
    Sistema de gestión académica y reservas de salas de cómputo.
</footer>

<script>
    const tipo = document.getElementById('tipo');

    const campoEstudiante = document.getElementById('campo-estudiante');
    const campoDocente = document.getElementById('campo-docente');

    const codigoEstudiante = document.getElementById('codigo_estudiante');
    const codigoDocente = document.getElementById('codigo_docente');

    function actualizarFormulario() {
        const esEstudiante = tipo.value === 'ESTUDIANTE';
        const esDocente = tipo.value === 'DOCENTE';

        campoEstudiante.hidden = !esEstudiante;
        campoDocente.hidden = !esDocente;

        codigoEstudiante.required = esEstudiante;
        codigoDocente.required = esDocente;
    }

    tipo.addEventListener('change', actualizarFormulario);

    actualizarFormulario();
</script>

</body>
</html>
