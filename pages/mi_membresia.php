<?php
$pageTitle   = 'Mi Membresía';
$currentPage = 'mi_membresia';
require_once '../includes/db.php';
requireLogin();

if (isAdmin()) { header('Location: /zonagym2/pages/membresias.php'); exit; }

$conn   = getConnection();
$userId = currentUser()['id'];

// Buscar cliente vinculado a este usuario
$cliente = $conn->query("SELECT * FROM clientes WHERE usuario_id = $userId")->fetch_assoc();

$membresia = null;
$historial = [];

if ($cliente) {
    $membresia = $conn->query("SELECT cm.*, m.nombre as tipo, m.precio, m.duracion_dias 
        FROM cliente_membresias cm 
        JOIN membresias m ON m.id = cm.membresia_id 
        WHERE cm.cliente_id = {$cliente['id']} AND cm.estado='Activo' 
        ORDER BY cm.id DESC LIMIT 1")->fetch_assoc();

    $res = $conn->query("SELECT cm.*, m.nombre as tipo FROM cliente_membresias cm 
        JOIN membresias m ON m.id = cm.membresia_id 
        WHERE cm.cliente_id = {$cliente['id']} 
        ORDER BY cm.id DESC LIMIT 5");
    while ($r = $res->fetch_assoc()) $historial[] = $r;
}

require_once '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Mi Membresía</h1>
    </div>

    <?php if (!$cliente): ?>
    <div class="empty-state">
        <i class="fa-solid fa-id-card"></i>
        <p>Tu cuenta no está vinculada a ningún cliente.</p>
        <p style="margin-top:8px; font-size:13px">Contacta al administrador para que registre tu perfil.</p>
    </div>

    <?php elseif (!$membresia): ?>
    <div style="background:rgba(183,28,28,0.1); border:1px solid rgba(183,28,28,0.3); border-radius:12px; padding:28px; margin-bottom:24px">
        <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning-light); font-size:28px"></i>
        <h3 style="font-family:'Rajdhani',sans-serif; font-size:20px; margin-top:12px">Sin membresía activa</h3>
        <p style="color:var(--text-muted); margin-top:8px">No tienes una membresía activa en este momento. Comunícate con el administrador.</p>
    </div>

    <?php else:
        // Calcular progreso
        $inicio   = strtotime($membresia['fecha_inicio']);
        $vence    = strtotime($membresia['fecha_vencimiento']);
        $hoy      = time();
        $total    = $vence - $inicio;
        $pasado   = $hoy - $inicio;
        $pct      = max(0, min(100, round($pasado / $total * 100)));
        $diasRest = max(0, ceil(($vence - $hoy) / 86400));
    ?>

    <div class="mi-plan-card" style="margin-bottom:24px">
        <div class="mi-plan-header">
            <div class="mi-plan-icon"><i class="fa-solid fa-id-card"></i></div>
            <div>
                <div style="font-family:'Rajdhani',sans-serif; font-weight:700; font-size:12px; letter-spacing:2px; text-transform:uppercase; color:rgba(229,57,53,0.8); margin-bottom:4px">Plan activo</div>
                <h2 style="font-family:'Bebas Neue',sans-serif; font-size:28px; letter-spacing:2px"><?= clean($membresia['tipo']) ?></h2>
                <span class="badge badge-active"><i class="fa-solid fa-circle" style="font-size:8px"></i> Activo</span>
            </div>
            <div style="margin-left:auto; text-align:right">
                <div style="font-family:'Bebas Neue',sans-serif; font-size:36px; color:var(--accent)">
                    $<?= number_format($membresia['precio'], 0) ?>
                </div>
                <div style="font-size:12px; color:var(--text-muted)">/ <?= $membresia['duracion_dias'] ?> días</div>
            </div>
        </div>
        <div class="mi-plan-body">
            <div class="plan-info-row">
                <div class="plan-info-item">
                    <label>Cliente</label>
                    <span><?= clean($cliente['nombres'].' '.$cliente['apellidos']) ?></span>
                </div>
                <div class="plan-info-item">
                    <label>DUI</label>
                    <span><?= clean($cliente['dui']) ?></span>
                </div>
                <div class="plan-info-item">
                    <label>Inicio</label>
                    <span><?= date('d/m/Y', strtotime($membresia['fecha_inicio'])) ?></span>
                </div>
                <div class="plan-info-item">
                    <label>Vencimiento</label>
                    <span style="color:<?= $diasRest < 7 ? 'var(--warning-light)' : 'inherit' ?>">
                        <?= date('d/m/Y', strtotime($membresia['fecha_vencimiento'])) ?>
                    </span>
                </div>
                <div class="plan-info-item">
                    <label>Días restantes</label>
                    <span style="font-family:'Bebas Neue',sans-serif; font-size:24px; color:<?= $diasRest < 7 ? 'var(--warning-light)' : 'var(--success-light)' ?>">
                        <?= $diasRest ?>
                    </span>
                </div>
            </div>

            <div class="progress-bar-wrap">
                <label>
                    <span>Tiempo transcurrido</span>
                    <span><?= $pct ?>%</span>
                </label>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                </div>
            </div>

            <?php if ($diasRest <= 7 && $diasRest > 0): ?>
            <div class="alert alert-error" style="margin-top:20px">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Tu membresía vence en <strong><?= $diasRest ?> días</strong>. Contacta al administrador para renovar.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>

    <!-- Historial -->
    <?php if (!empty($historial)): ?>
    <h2 class="section-title" style="font-size:20px; margin-bottom:14px">Historial de membresías</h2>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Plan</th><th>Inicio</th><th>Vencimiento</th><th>Estado</th></tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $h):
                    $bc = $h['estado'] === 'Activo' ? 'badge-active' : 'badge-expired';
                ?>
                <tr>
                    <td><?= clean($h['tipo']) ?></td>
                    <td><?= date('d/m/Y', strtotime($h['fecha_inicio'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($h['fecha_vencimiento'])) ?></td>
                    <td><span class="badge <?= $bc ?>"><?= $h['estado'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
