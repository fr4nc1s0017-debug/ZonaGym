<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'zonagym');

function getConnection(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Error de conexión: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ── Sesión segura ──────────────────────────────────────────
function iniciarSesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ── Verificar que el usuario esté logueado ─────────────────
function requireLogin(): void {
    iniciarSesion();
    if (empty($_SESSION['usuario_id'])) {
        header('Location: /zonagym2/auth/login.php');
        exit;
    }
}

// ── Verificar rol admin ────────────────────────────────────
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['rol'] !== 'admin') {
        header('Location: /zonagym2/index.php?error=acceso');
        exit;
    }
}

// ── Helpers de sesión ──────────────────────────────────────
function isAdmin(): bool {
    iniciarSesion();
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function isLoggedIn(): bool {
    iniciarSesion();
    return !empty($_SESSION['usuario_id']);
}

function currentUser(): array {
    iniciarSesion();
    return [
        'id'       => $_SESSION['usuario_id'] ?? null,
        'nombre'   => $_SESSION['nombre']     ?? '',
        'rol'      => $_SESSION['rol']        ?? '',
    ];
}

// ── Sanitizar entrada ──────────────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

// ── Escape para SQL (usa prepared statements mejor) ────────
function escape(mysqli $conn, string $val): string {
    return $conn->real_escape_string(trim($val));
}
?>



