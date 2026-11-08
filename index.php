
<?php
session_start();

$nombre = $_SESSION['nombre_completo'] ?? '';
$rol = $_SESSION['rol'] ?? '';
$sesionActiva = isset($_SESSION['id_usuario']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro TIC | Plataforma Universitaria</title>

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

        /* BARRA SUPERIOR */
        .barra-superior {
            background: #650000;
            color: white;
            padding: 12px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .institucion {
            font-size: 13px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .acceso {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .acceso span {
            font-size: 13px;
            color: #f5dede;
        }

        .acceso a {
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            padding: 9px 15px;
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: 5px;
            transition: 0.3s;
        }

        .acceso a:hover {
            background: white;
            color: #8B0000;
        }

        .acceso .cerrar {
            background: white;
            color: #8B0000;
        }

        /* ENCABEZADO */
        header {
            background: linear-gradient(120deg, #8B0000, #a91d1d);
            color: white;
            padding: 65px 7% 75px;
        }

        .contenido-header {
            max-width: 1100px;
            margin: auto;
        }

        .etiqueta {
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.5);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            letter-spacing: 1px;
            margin-bottom: 22px;
        }

        header h1 {
            font-size: 48px;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }

        header p {
            font-size: 18px;
            color: #f5dddd;
            line-height: 1.6;
            max-width: 650px;
        }

        /* CONTENIDO PRINCIPAL */
        main {
            max-width: 1100px;
            margin: -35px auto 60px;
            padding: 0 25px;
            position: relative;
        }

        .bienvenida {
            background: white;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 5px 22px rgba(0,0,0,0.09);
            border-top: 5px solid #8B0000;
        }

        .bienvenida h2 {
            color: #8B0000;
            font-size: 27px;
            margin-bottom: 12px;
        }

        .bienvenida > p {
            color: #555;
            line-height: 1.7;
            font-size: 15px;
        }

        .datos-usuario {
            margin-top: 20px;
            padding: 14px 18px;
            background: #f8eeee;
            border-left: 4px solid #8B0000;
            border-radius: 5px;
            color: #333;
            line-height: 1.8;
        }

        /* TARJETAS */
        .seccion-servicios {
            margin-top: 38px;
        }

        .seccion-servicios h2 {
            font-size: 23px;
            color: #222;
            margin-bottom: 8px;
        }

        .descripcion {
            color: #666;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .tarjetas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 22px;
        }

        .tarjeta {
            background: white;
            padding: 27px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.06);
            border: 1px solid #e8e8e8;
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .tarjeta:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 22px rgba(0,0,0,0.11);
        }

        .icono {
            width: 48px;
            height: 48px;
            background: #f8eaea;
            color: #8B0000;
            border-radius: 9px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 23px;
            margin-bottom: 18px;
        }

        .tarjeta h3 {
            color: #222;
            font-size: 19px;
            margin-bottom: 10px;
        }

        .tarjeta p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .boton {
            display: inline-block;
            background: #8B0000;
            color: white;
            text-decoration: none;
            padding: 11px 18px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            transition: 0.3s;
        }

        .boton:hover {
            background: #650000;
        }

        /* PIE DE PÁGINA */
        footer {
            background: #1c1c1c;
            color: #ccc;
            text-align: center;
            padding: 24px 15px;
            font-size: 13px;
            line-height: 1.7;
        }

        footer strong {
            color: white;
        }

        @media (max-width: 600px) {
            .barra-superior {
                padding: 15px 5%;
            }

            header {
                padding: 45px 6% 65px;
            }

            header h1 {
                font-size: 36px;
            }

            header p {
                font-size: 16px;
            }

            main {
                padding: 0 15px;
            }

            .bienvenida {
                padding: 25px 20px;
            }

            .bienvenida h2 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

    <div class="barra-superior">
        <div class="institucion">
            CENTRO TIC | SERVICIOS UNIVERSITARIOS
        </div>

        <div class="acceso">
            <?php if ($sesionActiva): ?>

                <span>
                    Bienvenido, <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
                </span>

                <a class="cerrar" href="logout.php">Cerrar sesión</a>

            <?php else: ?>

                <span>No has iniciado sesión</span>

                <a href="login.php">Iniciar sesión</a>
                <a href="registro.php">Registrarse</a>

            <?php endif; ?>
        </div>
    </div>

    <header>
        <div class="contenido-header">
            <div class="etiqueta">PLATAFORMA DE SERVICIOS ACADÉMICOS</div>

            <h1>Centro TIC</h1>

            <p>
                Sistema de gestión académica y reservas de salas.
                Un espacio digital para consultar y gestionar los
                recursos tecnológicos de la comunidad universitaria.
            </p>
        </div>
    </header>

    <main>

        <section class="bienvenida">
            <h2>Panel principal</h2>

            <p>
                Bienvenido al sistema de gestión del Centro TIC.
                Desde esta plataforma podrás consultar las salas de
                cómputo y acceder a los servicios disponibles.
            </p>

            <?php if ($sesionActiva): ?>
                <div class="datos-usuario">
                    <strong>Usuario:</strong>
                    <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
                    <br>

                    <strong>Rol:</strong>
                    <?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="seccion-servicios">
            <h2>Servicios del Centro TIC</h2>

            <p class="descripcion">
                Explora las opciones disponibles en nuestra plataforma.
            </p>

            <div class="tarjetas">

                <article class="tarjeta">
                    <div class="icono">▦</div>

                    <h3>Consulta de salas</h3>

                    <p>
                        Consulta las salas de cómputo registradas
                        y revisa la información de los espacios
                        disponibles en el Centro TIC.
                    </p>

                    <a class="boton" href="salas/listar.php">
                        Consultar salas
                    </a>
                </article>

                <?php if (!$sesionActiva): ?>

                    <article class="tarjeta">
                        <div class="icono">♙</div>

                        <h3>Acceso a la plataforma</h3>

                        <p>
                            Ingresa con tu cuenta para acceder
                            a las funcionalidades correspondientes
                            a tu perfil de usuario.
                        </p>

                        <a class="boton" href="login.php">
                            Iniciar sesión
                        </a>
                    </article>

                <?php endif; ?>

            </div>
        </section>

    </main>

    <footer>
        <strong>Centro TIC</strong><br>
        Sistema de gestión académica y reservas de salas de cómputo.<br>
        Plataforma de servicios universitarios.
    </footer>

</body>
</html>
