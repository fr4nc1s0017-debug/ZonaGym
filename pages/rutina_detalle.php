<?php
$pageTitle   = 'Rutina';
$currentPage = 'rutinas';
require_once '../includes/db.php';
requireLogin();

$conn = getConnection();
$id   = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: /zonagym2/index.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM rutinas WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$rutina = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rutina) { header('Location: /zonagym2/index.php'); exit; }

$pageTitle   = $rutina['nombre'];
$ejercicios  = json_decode($rutina['ejercicios'] ?? '[]', true);

$iconos = [
    'Pecho - Push'   => 'fa-arrow-up',
    'Espalda - Pull' => 'fa-arrow-down',
    'Hombro'         => 'fa-person',
    'Bicep'          => 'fa-hand-fist',
    'Tricep'         => 'fa-hands-clapping',
    'Abdomen'        => 'fa-fire',
];
$icono = $iconos[$rutina['grupo_muscular']] ?? 'fa-dumbbell';

// Tips por grupo muscular
$tips = [
    'Pecho - Push'   => ['Calienta 10 min antes','Mantén la espalda apoyada en el banco','Controla la fase negativa','Descansa 60-90 seg entre series','Hidratación constante'],
    'Espalda - Pull' => ['Activa el core en cada ejercicio','No uses impulso en los jalones','Retrae las escápulas','Respira correctamente','Prioriza la conexión mente-músculo'],
    'Hombro'         => ['Evita el peso excesivo','Cuida la postura cervical','Trabaja los 3 deltoides por igual','Calienta el manguito rotador','Descansa 48h entre sesiones'],
    'Bicep'          => ['Supina la muñeca en el curl','Controla el movimiento completo','No balancees el cuerpo','Alterna agarre en cada sesión','Estira al finalizar'],
    'Tricep'         => ['Mantén los codos fijos','Completa el rango de movimiento','Trabaja las 3 cabezas','Usa cargas moderadas','Haz supersets para mayor intensidad'],
    'Abdomen'        => ['Activa el core antes de cada rep','Exhala en la contracción','No jales el cuello','Combina con cardio','Descansa 24h entre sesiones'],
];
$tipsActuales = $tips[$rutina['grupo_muscular']] ?? ['Calienta siempre antes','Mantén buena postura','Hidratación constante','Descansa entre series','Consulta a tu entrenador'];

require_once '../includes/header.php';
?>

<div class="page-container">
    <div style="margin-bottom:20px">
        <a href="/zonagym2/index.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Volver a rutinas
        </a>
        <?php if (isAdmin()): ?>
        <a href="/zonagym2/pages/rutinas.php" class="btn btn-secondary btn-sm" style="margin-left:8px">
            <i class="fa-solid fa-gear"></i> Gestionar rutinas
        </a>
        <?php endif; ?>
    </div>

    <!-- BROCHURE -->
    <div class="brochure">
        <!-- Header del brochure -->
        <div class="brochure-header">
            <div class="brochure-icon">
                <i class="fa-solid <?= $icono ?>"></i>
            </div>
            <div class="brochure-header-text">
                <div style="font-family:'Rajdhani',sans-serif; font-weight:700; font-size:12px; letter-spacing:2px; text-transform:uppercase; color:rgba(229,57,53,0.8); margin-bottom:4px">
                    Zona Gym — Cojutepeque
                </div>
                <h1><?= clean($rutina['grupo_muscular']) ?></h1>
                <p><?= clean($rutina['descripcion']) ?></p>
                <div style="margin-top:12px; display:flex; gap:12px; flex-wrap:wrap">
                    <span style="background:rgba(229,57,53,0.2); border:1px solid rgba(229,57,53,0.4); border-radius:20px; padding:4px 14px; font-size:12px; font-family:'Rajdhani',sans-serif; font-weight:700; color:var(--accent)">
                        <i class="fa-solid fa-list-check"></i> <?= count($ejercicios) ?> Ejercicios
                    </span>
                    <span style="background:rgba(21,101,192,0.2); border:1px solid rgba(21,101,192,0.4); border-radius:20px; padding:4px 14px; font-size:12px; font-family:'Rajdhani',sans-serif; font-weight:700; color:var(--blue-light)">
                        <i class="fa-solid fa-clock"></i> ~45-60 min
                    </span>
                    <span style="background:rgba(46,125,50,0.2); border:1px solid rgba(46,125,50,0.4); border-radius:20px; padding:4px 14px; font-size:12px; font-family:'Rajdhani',sans-serif; font-weight:700; color:var(--success-light)">
                        <i class="fa-solid fa-fire-flame-curved"></i> Hipertrofia
                    </span>
                </div>
            </div>
        </div>

        <!-- Ejercicios -->
        <div class="brochure-body">
            <h2 style="font-family:'Bebas Neue',sans-serif; font-size:24px; letter-spacing:2px; margin-bottom:4px">
                Plan de Ejercicios
            </h2>
            <p style="font-size:13px; color:var(--text-muted)">
                Realiza los ejercicios en el orden indicado para maximizar resultados.
            </p>

            <div class="ejercicios-grid">
                <?php foreach ($ejercicios as $i => $ej):
                    // Separar nombre y series/reps
                    preg_match('/^(.+?)\s+(\d+x\d+.*)$/', $ej, $m);
                    $nombre = $m[1] ?? $ej;
                    $series = $m[2] ?? '';
                ?>
                <div class="ejercicio-item">
                    <div class="ejercicio-num"><?= $i + 1 ?></div>
                    <div class="ejercicio-info">
                        <strong><?= clean($nombre) ?></strong>
                        <?php if ($series): ?>
                            <span><?= clean($series) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Tips -->
            <div class="brochure-tips">
                <h3><i class="fa-solid fa-lightbulb"></i> Consejos para esta rutina</h3>
                <ul class="tips-list">
                    <?php foreach ($tipsActuales as $tip): ?>
                        <li><?= clean($tip) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Footer del brochure -->
            <div style="margin-top:28px; padding-top:20px; border-top:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px">
                <div style="font-size:12px; color:var(--text-muted)">
                    <i class="fa-solid fa-dumbbell" style="color:var(--accent)"></i>
                    <strong style="color:var(--text-primary)"> ZONA GYM</strong> — Cojutepeque &nbsp;|&nbsp;
                    Equipos de Gimnasio de Alta Calidad
                </div>
                <div style="font-size:12px; color:var(--text-muted)">
                    Consulta siempre con tu entrenador para ajustar la rutina a tu nivel.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
