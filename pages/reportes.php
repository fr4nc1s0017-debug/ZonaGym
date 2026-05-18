<?php
$pageTitle = 'Reportes';
$currentPage = 'reportes';
require_once '../includes/db.php';
requireAdmin();
require_once '../includes/header.php';

$conn = getConnection();

// Actualizar estados
$conn->query("UPDATE cliente_membresias SET estado = 'Vencido' WHERE fecha_vencimiento < CURDATE() AND estado = 'Activo'");

// Stats
$activos   = $conn->query("SELECT COUNT(*) as n FROM cliente_membresias WHERE estado='Activo'")->fetch_assoc()['n'];
$vencidos  = $conn->query("SELECT COUNT(*) as n FROM cliente_membresias WHERE estado='Vencido'")->fetch_assoc()['n'];
$entrenadores = $conn->query("SELECT COUNT(*) as n FROM entrenadores WHERE activo=1")->fetch_assoc()['n'];
$ingresos  = $conn->query("SELECT SUM(m.precio) as total FROM cliente_membresias cm JOIN membresias m ON m.id = cm.membresia_id WHERE cm.estado='Activo'")->fetch_assoc()['total'] ?? 0;

// Membresías activas por tipo
$porTipo = $conn->query("SELECT m.nombre, COUNT(*) as total FROM cliente_membresias cm JOIN membresias m ON m.id = cm.membresia_id WHERE cm.estado='Activo' GROUP BY m.id ORDER BY total DESC");

// Clientes activos recientes
$recientes = $conn->query("SELECT c.nombres, c.apellidos, m.nombre as tipo, cm.fecha_vencimiento, cm.estado
    FROM cliente_membresias cm 
    JOIN clientes c ON c.id = cm.cliente_id 
    JOIN membresias m ON m.id = cm.membresia_id 
    ORDER BY cm.id DESC LIMIT 10");

// Entrenadores activos
$entList = $conn->query("SELECT * FROM entrenadores WHERE activo=1 ORDER BY id");
?>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Dashboard - Reportes</h1>
        <button class="btn btn-secondary btn-sm" onclick="refreshDashboard()">
            <i class="fa-solid fa-rotate-right"></i> Actualizar
        </button>
    </div>

    <!-- STATS CARDS -->
    <div class="dashboard-grid">
        <div class="stat-card active">
            <div class="stat-label">Usuarios activos</div>
            <div class="stat-value" id="stat-activos"><?= $activos ?></div>
            <button class="btn btn-secondary btn-sm" onclick="showSection('sec-activos')">
                <i class="fa-solid fa-eye"></i> Generar reporte
            </button>
        </div>
        <div class="stat-card expired">
            <div class="stat-label">Usuarios vencidos</div>
            <div class="stat-value" id="stat-vencidos"><?= $vencidos ?></div>
            <button class="btn btn-secondary btn-sm" onclick="showSection('sec-vencidos')">
                <i class="fa-solid fa-eye"></i> Generar reporte
            </button>
        </div>
        <div class="stat-card trainers">
            <div class="stat-label">Entrenadores activos</div>
            <div class="stat-value" id="stat-entrenadores"><?= $entrenadores ?></div>
            <button class="btn btn-secondary btn-sm" onclick="showSection('sec-entrenadores')">
                <i class="fa-solid fa-eye"></i> Ver
            </button>
        </div>
        <div class="stat-card income">
            <div class="stat-label">Ingresos proyectados</div>
            <div class="stat-value income-val" id="stat-ingresos">$<?= number_format($ingresos, 0) ?></div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:6px">membresías activas</div>
        </div>
    </div>

    <!-- Membresías por tipo -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:28px">
        <div>
            <h2 class="section-title" style="font-size:20px; margin-bottom:16px">Membresías activas por tipo</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Tipo</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php while ($t = $porTipo->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['nombre']) ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px">
                                    <div style="flex:1; height:6px; background:var(--border); border-radius:3px; overflow:hidden">
                                        <div style="width:<?= min(100, $t['total'] * 20) ?>%; height:100%; background:var(--accent); border-radius:3px"></div>
                                    </div>
                                    <span style="font-family:'Bebas Neue'; font-size:18px"><?= $t['total'] ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="section-title" style="font-size:20px; margin-bottom:16px">Últimas membresías</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Cliente</th><th>Plan</th><th>Vence</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php while ($r = $recientes->fetch_assoc()): 
                            $bc = $r['estado'] === 'Activo' ? 'badge-active' : 'badge-expired';
                        ?>
                        <tr>
                            <td style="font-size:13px"><?= htmlspecialchars($r['nombres'].' '.$r['apellidos']) ?></td>
                            <td style="font-size:12px; color:var(--text-secondary)"><?= htmlspecialchars($r['tipo']) ?></td>
                            <td style="font-size:12px"><?= date('d/m/Y', strtotime($r['fecha_vencimiento'])) ?></td>
                            <td><span class="badge <?= $bc ?>" style="font-size:9px"><?= $r['estado'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sección dinámica de reportes -->
    <div id="sec-activos" class="report-section" style="display:none">
        <h2 class="section-title" style="font-size:20px; margin-bottom:16px">
            <i class="fa-solid fa-users" style="color:var(--success-light)"></i> Usuarios con membresía Activa
        </h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>DUI</th><th>Plan</th><th>Vencimiento</th></tr></thead>
                <tbody>
                    <?php
                    $act = $conn->query("SELECT c.id, c.nombres, c.apellidos, c.dui, m.nombre as plan, cm.fecha_vencimiento FROM cliente_membresias cm JOIN clientes c ON c.id = cm.cliente_id JOIN membresias m ON m.id = cm.membresia_id WHERE cm.estado='Activo' ORDER BY cm.fecha_vencimiento ASC");
                    while ($r = $act->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['nombres'].' '.$r['apellidos']) ?></td>
                        <td><?= $r['dui'] ?></td>
                        <td><?= htmlspecialchars($r['plan']) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['fecha_vencimiento'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="sec-vencidos" class="report-section" style="display:none">
        <h2 class="section-title" style="font-size:20px; margin-bottom:16px">
            <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning-light)"></i> Usuarios con membresía Vencida
        </h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>DUI</th><th>Plan</th><th>Venció</th></tr></thead>
                <tbody>
                    <?php
                    $venc = $conn->query("SELECT c.id, c.nombres, c.apellidos, c.dui, m.nombre as plan, cm.fecha_vencimiento FROM cliente_membresias cm JOIN clientes c ON c.id = cm.cliente_id JOIN membresias m ON m.id = cm.membresia_id WHERE cm.estado='Vencido' ORDER BY cm.fecha_vencimiento DESC");
                    while ($r = $venc->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['nombres'].' '.$r['apellidos']) ?></td>
                        <td><?= $r['dui'] ?></td>
                        <td><?= htmlspecialchars($r['plan']) ?></td>
                        <td style="color:var(--warning-light)"><?= date('d/m/Y', strtotime($r['fecha_vencimiento'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="sec-entrenadores" class="report-section" style="display:none">
        <h2 class="section-title" style="font-size:20px; margin-bottom:16px">
            <i class="fa-solid fa-person-running" style="color:var(--blue-light)"></i> Entrenadores activos
        </h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>Especialidad</th><th>Turno</th></tr></thead>
                <tbody>
                    <?php
                    $turnoClass = ['Mañana' => 'badge-morning', 'Tarde' => 'badge-afternoon', 'Noche' => 'badge-night'];
                    $entList->data_seek(0);
                    while ($e = $entList->fetch_assoc()):
                        $tc = $turnoClass[$e['turno']] ?? '';
                    ?>
                    <tr>
                        <td><?= $e['id'] ?></td>
                        <td><?= htmlspecialchars($e['nombres'].' '.$e['apellidos']) ?></td>
                        <td><?= htmlspecialchars($e['especialidad']) ?></td>
                        <td><span class="badge <?= $tc ?>"><?= $e['turno'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showSection(id) {
    const sections = document.querySelectorAll('.report-section');
    sections.forEach(s => {
        if (s.id === id) {
            s.style.display = s.style.display === 'none' ? 'block' : 'none';
        } else {
            s.style.display = 'none';
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
