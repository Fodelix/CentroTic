<?php
/**
 * Utilidades compartidas del Centro TIC.
 * Se incluye desde las páginas de las carpetas de primer nivel
 * (aplicaciones/, academico/, reportes/, admin/, usuario/, reservas/).
 */

require_once __DIR__ . '/../auth/seguridad.php';

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function esAdmin(): bool
{
    return ($_SESSION['rol'] ?? '') === 'ADMINISTRADOR';
}

function urlPanel(): string
{
    return esAdmin() ? '../admin/panel.php' : '../usuario/panel.php';
}

/* ---------- CSRF ---------- */

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . escapar(csrfToken()) . '">';
}

function exigirCsrf(): void
{
    $enviado = $_POST['csrf'] ?? '';

    if (!is_string($enviado) || !hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        http_response_code(403);
        exit('403 - Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
}

/* ---------- Mensajes entre páginas (patrón Post/Redirect/Get) ---------- */

function flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

function mostrarFlash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $clase = $f['tipo'] === 'exito' ? 'exito' : 'error';
    echo '<div class="mensaje ' . $clase . '" role="status">'
        . escapar($f['texto']) . '</div>';
}

function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Errores de PostgreSQL ---------- */

/**
 * Traduce un error de PDO a un mensaje comprensible.
 * Los errores lanzados por los disparadores (RAISE EXCEPTION, SQLSTATE P0001)
 * ya traen un texto claro, así que se muestra tal cual.
 */
function mensajeErrorBD(PDOException $e, string $porDefecto): string
{
    $codigo = (string) ($e->errorInfo[0] ?? $e->getCode());

    switch ($codigo) {
        case '23505':
            return 'Ya existe un registro con esos datos.';
        case '23503':
            return 'No se puede completar la operación: el registro está relacionado con otros datos.';
        case '23514':
            return 'Los datos no cumplen las condiciones permitidas.';
        case 'P0001':
            // PostgreSQL entrega "ERROR: mensaje" y luego líneas "CONTEXT: ...".
            // Solo se conserva la primera línea, sin el prefijo.
            $primeraLinea = (string) strtok($e->errorInfo[2] ?? '', "\n");
            return trim(preg_replace('/^ERROR:\s*/', '', $primeraLinea)) ?: $porDefecto;
        default:
            error_log($e->getMessage());
            return $porDefecto;
    }
}

/* ---------- Plantilla ---------- */

function cabecera(string $titulo, string $descripcion = ''): void
{
    $panel = urlPanel();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapar($titulo) ?> - Centro TIC</title>
    <link rel="stylesheet" href="../css/centro_tic.css">
</head>
<body>

<header class="cabecera">
    <div>
        <h1>CENTRO TIC</h1>
        <p><?= escapar($descripcion !== '' ? $descripcion : 'Sistema de préstamo y reserva de salas') ?></p>
    </div>
    <nav>
        <a href="<?= escapar($panel) ?>">Panel</a>
        <a href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<main class="contenedor">
<?php
}

function pie(): void
{
    ?>
</main>

<footer>
    Centro TIC - Sistema de gestión académica y reservas
</footer>

</body>
</html>
<?php
}
