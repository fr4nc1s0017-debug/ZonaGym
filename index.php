<?php
$pageTitle   = 'Inicio';
$currentPage = 'inicio';
require_once 'includes/db.php';
requireLogin();
require_once 'includes/header.php';

$conn   = getConnection();
$esAdmin = isAdmin();
$rutinas = $conn->query("SELECT * FROM rutinas ORDER BY id");

$iconos = [
    'Pecho - Push'   => 'fa-arrow-up',
    'Espalda - Pull' => 'fa-arrow-down',
    'Hombro'         => 'fa-person',
    'Bicep'          => 'fa-hand-fist',
    'Tricep'         => 'fa-hands-clapping',
    'Abdomen'        => 'fa-fire',
];
?>

<div class="page-container">

<?php if ($esAdmin): ?>
    <!-- ── Vista Admin: accesos rápidos ── -->
    <h2 class="section-title">Panel de Administración</h2>
    <div class="dashboard-grid" style="margin-bottom:32px">
        <?php
        $conn->query("UPDATE cliente_membresias SET estado='Vencido' WHERE fecha_vencimiento < CURDATE() AND estado='Activo'");
        $activos     = $conn->query("SELECT COUNT(*) n FROM cliente_membresias WHERE estado='Activo'")->fetch_assoc()['n'];
        $vencidos    = $conn->query("SELECT COUNT(*) n FROM cliente_membresias WHERE estado='Vencido'")->fetch_assoc()['n'];
        $entTotal    = $conn->query("SELECT COUNT(*) n FROM entrenadores WHERE activo=1")->fetch_assoc()['n'];
        $clienteTotal= $conn->query("SELECT COUNT(*) n FROM clientes")->fetch_assoc()['n'];
        ?>
        <div class="stat-card active">
            <div class="stat-label">Membresías activas</div>
            <div class="stat-value"><?= $activos ?></div>
            <a href="/zonagym2/pages/membresias.php" class="btn btn-secondary btn-sm" style="margin-top:12px">Ver</a>
        </div>
        <div class="stat-card expired">
            <div class="stat-label">Membresías vencidas</div>
            <div class="stat-value"><?= $vencidos ?></div>
            <a href="/zonagym2/pages/reportes.php" class="btn btn-secondary btn-sm" style="margin-top:12px">Ver reporte</a>
        </div>
        <div class="stat-card trainers">
            <div class="stat-label">Total clientes</div>
            <div class="stat-value"><?= $clienteTotal ?></div>
            <a href="/zonagym2/pages/clientes.php" class="btn btn-secondary btn-sm" style="margin-top:12px">Gestionar</a>
        </div>
        <div class="stat-card income">
            <div class="stat-label">Entrenadores activos</div>
            <div class="stat-value" style="color:#ffd54f"><?= $entTotal ?></div>
            <a href="/zonagym2/pages/entrenadores.php" class="btn btn-secondary btn-sm" style="margin-top:12px">Gestionar</a>
        </div>
    </div>

    <h2 class="section-title">Rutinas del Gimnasio</h2>
    <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px">
        Rutinas disponibles para los miembros. Haz clic para ver el detalle de cada una.
    </p>

<?php else: ?>
    <!-- ── Vista Usuario: bienvenida ── -->
    <?php
    $userId = currentUser()['id'];
    $memActiva = $conn->query("SELECT cm.*, m.nombre as tipo FROM cliente_membresias cm 
        JOIN membresias m ON m.id = cm.membresia_id 
        JOIN clientes c ON c.id = cm.cliente_id
        WHERE c.usuario_id = $userId AND cm.estado='Activo' 
        ORDER BY cm.id DESC LIMIT 1")->fetch_assoc();
    ?>

    <?php if ($memActiva): ?>
    <div style="background:rgba(46,125,50,0.1); border:1px solid rgba(46,125,50,0.3); border-radius:12px; padding:16px 20px; margin-bottom:28px; display:flex; align-items:center; gap:14px">
        <i class="fa-solid fa-circle-check" style="color:var(--success-light); font-size:22px"></i>
        <div>
            <strong style="font-family:'Rajdhani',sans-serif; font-size:15px">Membresía activa: <?= clean($memActiva['tipo']) ?></strong>
            <p style="font-size:13px; color:var(--text-muted)">Vence el <?= date('d/m/Y', strtotime($memActiva['fecha_vencimiento'])) ?></p>
        </div>
        <a href="/zonagym2/pages/mi_membresia.php" class="btn btn-secondary btn-sm" style="margin-left:auto">Ver plan</a>
    </div>
    <?php else: ?>
    <div style="background:rgba(183,28,28,0.1); border:1px solid rgba(183,28,28,0.3); border-radius:12px; padding:16px 20px; margin-bottom:28px; display:flex; align-items:center; gap:14px">
        <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning-light); font-size:22px"></i>
        <div>
            <strong style="font-family:'Rajdhani',sans-serif; font-size:15px">No tienes una membresía activa</strong>
            <p style="font-size:13px; color:var(--text-muted)">Contacta al administrador para activar tu plan.</p>
        </div>
    </div>
    <?php endif; ?>

    <h2 class="section-title">Rutinas Recomendadas</h2>
    <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px">
        Explora nuestras rutinas y consigue tus objetivos. Haz clic en cualquier rutina para ver el brochure completo.
    </p>
<?php endif; ?>

    <!-- Tarjetas de rutinas (ambos roles) -->
    <div class="rutinas-grid">
        <?php
        $rutinas->data_seek(0);
        while ($r = $rutinas->fetch_assoc()):
            $icono = $iconos[$r['grupo_muscular']] ?? 'fa-dumbbell';
            $ejercicios = json_decode($r['ejercicios'] ?? '[]', true);
            $count = count($ejercicios);
        ?>
        <a href="/zonagym2/pages/rutina_detalle.php?id=<?= $r['id'] ?>" class="rutina-card">
            <div class="rutina-card-header">
                <div class="rutina-icon-big">
                    <i class="fa-solid <?= $icono ?>"></i>
                </div>
                <div>
                    <h3><?= clean($r['grupo_muscular']) ?></h3>
                    <p><?= clean($r['nombre']) ?></p>
                </div>
            </div>
            <div class="rutina-card-body">
                <ul>
                    <?php foreach(array_slice($ejercicios, 0, 3) as $ej): 
                        // Extraer solo nombre (sin series)
                        $nombre = preg_replace('/\d+x\d+/', '', $ej);
                        $nombre = trim($nombre);
                    ?>
                    <li><?= clean($nombre) ?></li>
                    <?php endforeach; ?>
                    <?php if ($count > 3): ?>
                        <li>+<?= $count - 3 ?> más...</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="rutina-card-footer">
                <span><i class="fa-solid fa-list-check"></i> <?= $count ?> ejercicios</span>
                <span style="color:var(--accent)">Ver brochure <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </a>
        <?php endwhile; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
