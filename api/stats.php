<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
requireLogin();

$conn = getConnection();
$conn->query("UPDATE cliente_membresias SET estado='Vencido' WHERE fecha_vencimiento < CURDATE() AND estado='Activo'");

echo json_encode([
    'activos'      => (int)$conn->query("SELECT COUNT(*) n FROM cliente_membresias WHERE estado='Activo'")->fetch_assoc()['n'],
    'vencidos'     => (int)$conn->query("SELECT COUNT(*) n FROM cliente_membresias WHERE estado='Vencido'")->fetch_assoc()['n'],
    'entrenadores' => (int)$conn->query("SELECT COUNT(*) n FROM entrenadores WHERE activo=1")->fetch_assoc()['n'],
    'ingresos'     => (float)($conn->query("SELECT SUM(m.precio) t FROM cliente_membresias cm JOIN membresias m ON m.id=cm.membresia_id WHERE cm.estado='Activo'")->fetch_assoc()['t'] ?? 0),
]);
?>
