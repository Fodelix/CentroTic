<?php

require_once __DIR__ . '/../includes/ui.php';

exigirRol('ADMINISTRADOR');

$nombre = $_SESSION['nombre_completo'] ?? 'Administrador';

cabecera('Panel de administración', 'Panel de administración');
?>

<section class="tarjeta" style="margin-bottom:25px">
    <span class="rol">ADMINISTRADOR</span>
    <h2>Bienvenido, <?= escapar($nombre) ?></h2>
    <p class="descripcion" style="margin:0">
        Desde aquí puedes administrar salas, equipos, horarios, reservas, aplicaciones y la información académica.
    </p>
</section>

<section class="tarjeta" style="margin-bottom:25px">
    <h2>Salas, equipos y horarios</h2>
    <div class="rejilla">

        <article class="servicio">
            <h4>Salas</h4>
            <p>Consulta las salas, sus edificios, capacidades y estados.</p>
            <div class="acciones">
                <a class="boton" href="../salas/listar.php">Ver salas</a>
                <a class="boton boton-sec" href="../salas/crear.php">Registrar</a>
            </div>
        </article>

        <article class="servicio">
            <h4>Equipos</h4>
            <p>Consulta los computadores, su código de inventario, estado y sala.</p>
            <div class="acciones">
                <a class="boton" href="../equipos/listar.php">Ver equipos</a>
                <a class="boton boton-sec" href="../equipos/crear.php">Registrar</a>
            </div>
        </article>

        <article class="servicio">
            <h4>Horarios</h4>
            <p>Administra los días y horas de funcionamiento de cada sala.</p>
            <div class="acciones">
                <a class="boton" href="../horarios/Listar.php">Ver horarios</a>
                <a class="boton boton-sec" href="../horarios/crear.php">Registrar</a>
            </div>
        </article>

        <article class="servicio">
            <h4>Aplicaciones instaladas</h4>
            <p>Administra el software y define en qué salas está instalado.</p>
            <div class="acciones">
                <a class="boton" href="../aplicaciones/listar.php">Ver aplicaciones</a>
                <a class="boton boton-sec" href="../aplicaciones/formulario.php">Registrar</a>
            </div>
        </article>

    </div>
</section>

<section class="tarjeta" style="margin-bottom:25px">
    <h2>Reservas y reportes</h2>
    <div class="rejilla">

        <article class="servicio">
            <h4>Reservas por sala</h4>
            <p>Consulta las reservas hechas a cada sala, con solicitante, fecha y estado.</p>
            <a class="boton" href="../reservas/por_sala.php">Consultar reservas</a>
        </article>

        <article class="servicio">
            <h4>Todas las reservas</h4>
            <p>Listado general de reservas ordenado por fecha.</p>
            <a class="boton" href="../reservas/listar.php">Ver listado</a>
        </article>

        <article class="servicio">
            <h4>Reportes</h4>
            <p>Reservas por sala, por estado y disponibilidad de salas, con exportación a CSV.</p>
            <a class="boton" href="../reportes/index.php">Ver reportes</a>
        </article>

    </div>
</section>

<section class="tarjeta">
    <h2>Gestión académica</h2>
    <div class="rejilla">

        <article class="servicio">
            <h4>Estructura académica</h4>
            <p>Facultades, programas con su plan de estudios y cursos.</p>
            <a class="boton" href="../academico/index.php">Administrar</a>
        </article>

        <article class="servicio">
            <h4>Estudiantes y docentes</h4>
            <p>Consulta las personas registradas y sus cursos.</p>
            <div class="acciones">
                <a class="boton" href="../academico/personas.php?tipo=estudiantes">Estudiantes</a>
                <a class="boton boton-sec" href="../academico/personas.php?tipo=docentes">Docentes</a>
            </div>
        </article>

    </div>
</section>

<?php pie(); ?>
