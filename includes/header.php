<?php
$basePath = $basePath ?? '';
iniciarSesion();
$user = currentUser();
$esAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZonaGym — <?= $pageTitle ?? 'Panel' ?></title>
    <link rel="stylesheet" href="/zonagym2/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">

    <!-- ── SIDEBAR ─────────────────────────────────────── -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <i class="fa-solid fa-dumbbell"></i>
        </div>

        <nav class="sidebar-nav">
            <!-- Inicio — ambos roles -->
            <a href="/zonagym2/index.php"
               class="nav-item <?= ($currentPage ?? '') === 'inicio' ? 'active' : '' ?>"
               title="Inicio">
                <i class="fa-solid fa-house"></i>
                <span>Inicio</span>
            </a>

            <?php if ($esAdmin): ?>
            <!-- ── Solo Admin ── -->
            <a href="/zonagym2/pages/clientes.php"
               class="nav-item <?= ($currentPage ?? '') === 'clientes' ? 'active' : '' ?>"
               title="Clientes">
                <i class="fa-solid fa-users"></i>
                <span>Clientes</span>
            </a>
            <a href="/zonagym2/pages/entrenadores.php"
               class="nav-item <?= ($currentPage ?? '') === 'entrenadores' ? 'active' : '' ?>"
               title="Entrenadores">
                <i class="fa-solid fa-person-running"></i>
                <span>Entrenadores</span>
            </a>
            <a href="/zonagym2/pages/membresias.php"
               class="nav-item <?= ($currentPage ?? '') === 'membresias' ? 'active' : '' ?>"
               title="Membresías">
                <i class="fa-solid fa-id-card"></i>
                <span>Membresías</span>
            </a>
            <a href="/zonagym2/pages/reportes.php"
               class="nav-item <?= ($currentPage ?? '') === 'reportes' ? 'active' : '' ?>"
               title="Reportes">
                <i class="fa-solid fa-chart-bar"></i>
                <span>Reportes</span>
            </a>
            <a href="/zonagym2/pages/usuarios.php"
               class="nav-item <?= ($currentPage ?? '') === 'usuarios' ? 'active' : '' ?>"
               title="Usuarios">
                <i class="fa-solid fa-user-gear"></i>
                <span>Usuarios</span>
            </a>

            <?php else: ?>
            <!-- ── Solo Usuario normal ── -->
            <a href="/zonagym2/pages/mi_membresia.php"
               class="nav-item <?= ($currentPage ?? '') === 'mi_membresia' ? 'active' : '' ?>"
               title="Mi Membresía">
                <i class="fa-solid fa-id-card"></i>
                <span>Mi Plan</span>
            </a>
            <a href="/zonagym2/pages/rutinas.php"
               class="nav-item <?= ($currentPage ?? '') === 'rutinas' ? 'active' : '' ?>"
               title="Rutinas">
                <i class="fa-solid fa-fire"></i>
                <span>Rutinas</span>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Avatar + logout al fondo -->
        <div class="sidebar-footer">
            <div class="sidebar-user" title="<?= clean($user['nombre']) ?> (<?= $user['rol'] ?>)">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= clean($user['nombre']) ?></span>
                    <span class="user-role"><?= $esAdmin ? 'Administrador' : 'Usuario' ?></span>
                </div>
            </div>
            <a href="/zonagym2/auth/logout.php" class="btn-logout" title="Cerrar sesión">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </aside>

    <!-- ── MAIN ─────────────────────────────────────────── -->
    <main class="main-content">
        <div class="banner">
            <div class="banner-text">
                <h2>Zona Gym — Cojutepeque</h2>
                <?php if ($esAdmin): ?>
                    <span class="banner-role admin-badge"><i class="fa-solid fa-shield-halved"></i> Administrador</span>
                <?php else: ?>
                    <span class="banner-role user-badge"><i class="fa-solid fa-user"></i> <?= clean($user['nombre']) ?></span>
                <?php endif; ?>
            </div>
        </div>
