<?php
require_once '../includes/db.php';
iniciarSesion();

// Ya logueado → redirigir
if (isLoggedIn()) {
    header('Location: /zonagym2/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Por favor completa todos los campos.';
    } else {
        $conn  = getConnection();
        $stmt  = $conn->prepare("SELECT id, nombre, apellidos, password_hash, rol, activo FROM usuarios WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Correo o contraseña incorrectos.';
        } elseif (!$user['activo']) {
            $error = 'Tu cuenta está desactivada. Contacta al administrador.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = 'Correo o contraseña incorrectos.';
        } else {
            // Regenerar ID de sesión para evitar session fixation
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre']     = $user['nombre'];
            $_SESSION['apellidos']  = $user['apellidos'];
            $_SESSION['rol']        = $user['rol'];

            header('Location: /zonagym2/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZonaGym — Iniciar Sesión</title>
    <link rel="stylesheet" href="/zonagym2/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="fa-solid fa-dumbbell"></i>
        </div>
        <h1 class="auth-title">ZONA GYM</h1>
        <p class="auth-subtitle">Cojutepeque — Panel de Administración</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= clean($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check"></i> Cuenta creada. Inicia sesión.</div>
        <?php endif; ?>
        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check"></i> Sesión cerrada correctamente.</div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email" name="email" class="form-control"
                       value="<?= clean($_POST['email'] ?? '') ?>"
                       placeholder="correo@ejemplo.com" required autofocus>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <div style="position:relative">
                    <input type="password" name="password" id="pwd" class="form-control"
                           placeholder="••••••••" required>
                    <button type="button" onclick="togglePwd()" class="pwd-toggle">
                        <i class="fa-solid fa-eye" id="pwd-icon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px">
                <i class="fa-solid fa-right-to-bracket"></i> Ingresar
            </button>
        </form>

        <p style="text-align:center; margin-top:20px; font-size:13px; color:var(--text-muted)">
            ¿No tienes cuenta?
            <a href="/zonagym2/auth/registro.php" style="color:var(--accent)">Regístrate aquí</a>
        </p>
    </div>
</div>

<script>
function togglePwd() {
    const p = document.getElementById('pwd');
    const i = document.getElementById('pwd-icon');
    if (p.type === 'password') { p.type = 'text'; i.className = 'fa-solid fa-eye-slash'; }
    else { p.type = 'password'; i.className = 'fa-solid fa-eye'; }
}
</script>
</body>
</html>
