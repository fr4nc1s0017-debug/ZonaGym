<?php
require_once '../includes/db.php';
iniciarSesion();
$_SESSION = [];
session_destroy();
header('Location: /zonagym2/auth/login.php?logout=1');
exit;
?>
