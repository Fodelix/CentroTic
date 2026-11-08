<?php
$hash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contrasena = $_POST['contrasena'] ?? '';

    if (strlen($contrasena) >= 10) {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
    } else {
        $error = 'La contraseña debe tener al menos 10 caracteres.';
    }
}
?>

<form method="POST">
    <label>Contraseña para Gabriela:</label>
    <input type="password" name="contrasena" required>
    <button type="submit">Generar hash</button>
</form>

<?php if (!empty($error)): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($hash !== ''): ?>
    <p>Hash generado:</p>
    <textarea rows="4" cols="80"><?= htmlspecialchars($hash) ?></textarea>
<?php endif; ?>