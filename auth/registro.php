<?php
require_once '../includes/db.php';

iniciarSesion();

if (isLoggedIn()) {
    header('Location: /zonagym2/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $confirm   = trim($_POST['confirm'] ?? '');

    // Validaciones
    if (
        !$nombre ||
        !$apellidos ||
        !$email ||
        !$password ||
        !$confirm
    ) {

        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($password) < 6) {

        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm) {

        $error = 'Las contraseñas no coinciden.';
    } else {

        $conn = getConnection();

        // Verificar email único
        $stmt = $conn->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
        ");

        $stmt->bind_param('s', $email);

        $stmt->execute();

        $exists = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if ($exists) {

            $error = 'Ya existe una cuenta con ese correo electrónico.';
        } else {

            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Crear usuario
            $stmt = $conn->prepare("
                INSERT INTO usuarios
                (nombre, apellidos, email, password_hash, rol)
                VALUES (?, ?, ?, ?, 'usuario')
            ");

            $stmt->bind_param(
                'ssss',
                $nombre,
                $apellidos,
                $email,
                $hash
            );

            if ($stmt->execute()) {

                $nuevoUserId = $conn->insert_id;

                // Crear cliente vinculado automáticamente
                $stmt2 = $conn->prepare("
                    INSERT INTO clientes
                    (nombres, apellidos, dui, direccion, usuario_id)
                    VALUES (?, ?, 'Sin DUI', '', ?)
                ");

                $stmt2->bind_param(
                    'ssi',
                    $nombre,
                    $apellidos,
                    $nuevoUserId
                );

                $stmt2->execute();

                $stmt2->close();

                header('Location: /zonagym2/auth/login.php?registered=1');

                exit;
            } else {

                $error = 'Error al crear la cuenta. Intenta de nuevo.';
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>ZonaGym — Registro</title>

    <link
        rel="stylesheet"
        href="/zonagym2/css/style.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body class="auth-body">

    <div class="auth-container">

        <div class="auth-card">

            <div class="auth-logo">
                <i class="fa-solid fa-dumbbell"></i>
            </div>

            <h1 class="auth-title">
                ZONA GYM
            </h1>

            <p class="auth-subtitle">
                Crear nueva cuenta
            </p>

            <?php if ($error): ?>

                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= clean($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST" novalidate>

                <div class="form-group">

                    <label>Nombre</label>

                    <input
                        type="text"
                        name="nombre"
                        class="form-control"
                        value="<?= clean($_POST['nombre'] ?? '') ?>"
                        required>

                </div>

                <div class="form-group">

                    <label>Apellidos</label>

                    <input
                        type="text"
                        name="apellidos"
                        class="form-control"
                        value="<?= clean($_POST['apellidos'] ?? '') ?>"
                        required>

                </div>

                <div class="form-group">

                    <label>Correo electrónico</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?= clean($_POST['email'] ?? '') ?>"
                        required>

                </div>

                <div class="form-group">

                    <label>Contraseña</label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Mínimo 6 caracteres"
                        required>

                </div>

                <div class="form-group">

                    <label>Confirmar contraseña</label>

                    <input
                        type="password"
                        name="confirm"
                        class="form-control"
                        required>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width:100%;justify-content:center;margin-top:8px">
                    <i class="fa-solid fa-user-plus"></i>
                    Crear cuenta
                </button>

            </form>

            <p style="
            text-align:center;
            margin-top:20px;
            font-size:13px;
            color:var(--text-muted)
        ">

                ¿Ya tienes cuenta?

                <a
                    href="/zonagym2/auth/login.php"
                    style="color:var(--accent)">
                    Inicia sesión
                </a>

            </p>

        </div>

    </div>

</body>

</html>