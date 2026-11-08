<?php

require_once __DIR__ . '/../includes/ui.php';

exigirRol('USUARIO');

$nombre = $_SESSION['nombre_completo'] ?? '';

cabecera('Panel de usuario', 'Panel de usuario');
?>

<section class="tarjeta" style="margin-bottom:25px">
    <span class="rol">USUARIO</span>
    <h2>Bienvenido, <?= escapar($nombre) ?></h2>
    <p class="descripcion" style="margin:0">
        Consulta las salas, el software disponible y gestiona tus reservas.
    </p>
</section>

<section class="tarjeta" style="margin-bottom:25px">
    <h2>Reservas</h2>
    <div class="rejilla">

        <article class="servicio">
            <h4>Disponibilidad de salas</h4>
            <p>Revisa qué franjas de una sala están ocupadas en la fecha que te interesa.</p>
            <a class="boton" href="../reservas/disponibilidad.php">Consultar disponibilidad</a>
        </article>

        <article class="servicio">
            <h4>Solicitar reserva</h4>
            <p>Registra una solicitud indicando sala, fecha y horario.</p>
            <a class="boton" href="../reservas/crear.php">Solicitar reserva</a>
        </article>

        <article class="servicio">
            <h4>Mis reservas</h4>
            <p>Consulta el historial de tus reservas y el estado de cada solicitud.</p>
            <a class="boton" href="../reservas/listar.php">Ver mis reservas</a>
        </article>

    </div>
</section>

<section class="tarjeta">
    <h2>Consulta de información</h2>
    <div class="rejilla">

        <article class="servicio">
            <h4>Salas</h4>
            <p>Consulta las salas de cómputo registradas.</p>
            <a class="boton" href="../salas/listar.php">Ver salas</a>
        </article>

        <article class="servicio">
            <h4>Horarios</h4>
            <p>Consulta los horarios semanales de funcionamiento de las salas.</p>
            <a class="boton" href="../horarios/Listar.php">Ver horarios</a>
        </article>

        <article class="servicio">
            <h4>Equipos</h4>
            <p>Consulta los computadores de cada sala.</p>
            <a class="boton" href="../equipos/listar.php">Ver equipos</a>
        </article>

        <article class="servicio">
            <h4>Aplicaciones instaladas</h4>
            <p>Revisa qué software tiene cada sala antes de reservar.</p>
            <a class="boton" href="../aplicaciones/listar.php">Ver aplicaciones</a>
        </article>

    </div>
</section>

<?php pie(); ?>
