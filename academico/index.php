<?php

require_once __DIR__ . '/../includes/ui.php';

exigirRol('ADMINISTRADOR');

cabecera('Gestión académica');
?>

<section class="tarjeta">

    <h2>Gestión académica</h2>
    <p class="descripcion">
        Administra la estructura académica: facultades, programas, cursos y las personas registradas.
    </p>

    <div class="rejilla">

        <article class="servicio">
            <h4>Facultades</h4>
            <p>Registra, edita y elimina facultades.</p>
            <a class="boton" href="facultades.php">Administrar facultades</a>
        </article>

        <article class="servicio">
            <h4>Programas académicos</h4>
            <p>Gestiona programas por nivel y facultad, y su plan de estudios por semestre.</p>
            <a class="boton" href="programas.php">Administrar programas</a>
        </article>

        <article class="servicio">
            <h4>Cursos</h4>
            <p>Administra el catálogo de cursos y consulta cuántos estudiantes y docentes tiene cada uno.</p>
            <a class="boton" href="cursos.php">Administrar cursos</a>
        </article>

        <article class="servicio">
            <h4>Estudiantes</h4>
            <p>Consulta estudiantes registrados y los cursos en que están matriculados.</p>
            <a class="boton" href="personas.php?tipo=estudiantes">Ver estudiantes</a>
        </article>

        <article class="servicio">
            <h4>Docentes</h4>
            <p>Consulta docentes registrados y los cursos que dictan.</p>
            <a class="boton" href="personas.php?tipo=docentes">Ver docentes</a>
        </article>

    </div>

    <p style="margin-top:22px">
        <a class="boton boton-sec" href="../admin/panel.php">← Volver al panel</a>
    </p>

</section>

<?php pie(); ?>
