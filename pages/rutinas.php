<?php
$pageTitle   = 'Rutinas';
$currentPage = 'rutinas';
require_once '../includes/db.php';
requireAdmin();
require_once '../includes/header.php';

$conn = getConnection();
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $grupo      = escape($conn, $_POST['grupo_muscular']);
        $nombre     = escape($conn, $_POST['nombre']);
        $descripcion= escape($conn, $_POST['descripcion']);
        // Ejercicios: uno por línea → JSON
        $ejerciciosRaw = array_filter(array_map('trim', explode("\n", $_POST['ejercicios'] ?? '')));
        $ejerciciosJson = $conn->real_escape_string(json_encode(array_values($ejerciciosRaw)));

        if ($_POST['action'] === 'add') {
            $conn->query("INSERT INTO rutinas (grupo_muscular,nombre,descripcion,ejercicios) VALUES ('$grupo','$nombre','$descripcion','$ejerciciosJson')");
            $msg = 'Rutina agregada.';
        } else {
            $id = (int)$_POST['id'];
            $conn->query("UPDATE rutinas SET grupo_muscular='$grupo',nombre='$nombre',descripcion='$descripcion',ejercicios='$ejerciciosJson' WHERE id=$id");
            $msg = 'Rutina actualizada.';
        }
        $msgType = 'success';
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM rutinas WHERE id=$id");
    header('Location: rutinas.php?msg=eliminado'); exit;
}

$rutinas = $conn->query("SELECT * FROM rutinas ORDER BY id");

$iconos = [
    'Pecho - Push'=>'fa-arrow-up','Espalda - Pull'=>'fa-arrow-down',
    'Hombro'=>'fa-person','Bicep'=>'fa-hand-fist',
    'Tricep'=>'fa-hands-clapping','Abdomen'=>'fa-fire',
];
?>

<div class="page-container">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><i class="fa-solid fa-circle-info"></i> <?= $msg ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check"></i> Rutina eliminada.</div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">Gestión de Rutinas</h1>
        <button class="btn btn-primary" onclick="openModal('modal-add')">
            <i class="fa-solid fa-plus"></i> Agregar Rutina
        </button>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>ID</th><th>Grupo Muscular</th><th>Nombre</th><th>Ejercicios</th><th>Opciones</th></tr>
            </thead>
            <tbody>
                <?php if ($rutinas->num_rows === 0): ?>
                    <tr><td colspan="5"><div class="empty-state"><i class="fa-solid fa-dumbbell"></i><p>No hay rutinas.</p></div></td></tr>
                <?php endif; ?>
                <?php while ($r = $rutinas->fetch_assoc()):
                    $ejs = count(json_decode($r['ejercicios'] ?? '[]', true));
                    $icono = $iconos[$r['grupo_muscular']] ?? 'fa-dumbbell';
                ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <span style="width:30px;height:30px;background:var(--accent);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff">
                                <i class="fa-solid <?= $icono ?>"></i>
                            </span>
                            <?= clean($r['grupo_muscular']) ?>
                        </div>
                    </td>
                    <td><?= clean($r['nombre']) ?></td>
                    <td><span class="badge badge-active"><?= $ejs ?> ejercicios</span></td>
                    <td>
                        <a href="/zonagym2/pages/rutina_detalle.php?id=<?= $r['id'] ?>" class="btn-icon btn-edit" title="Ver brochure">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <button class="btn-icon btn-edit" title="Editar"
                            onclick="editRutina(<?= $r['id'] ?>, '<?= addslashes($r['grupo_muscular']) ?>', '<?= addslashes($r['nombre']) ?>', '<?= addslashes($r['descripcion']) ?>', <?= htmlspecialchars($r['ejercicios'] ?? '[]', ENT_QUOTES) ?>)">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn-icon btn-delete" title="Eliminar"
                            onclick="confirmDelete('rutinas.php?delete=<?= $r['id'] ?>', '<?= addslashes($r['nombre']) ?>')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Agregar -->
<div class="modal-overlay" id="modal-add">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h2 class="modal-title">Nueva Rutina</h2>
            <button class="modal-close" onclick="closeModal('modal-add')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Grupo muscular</label>
                <select name="grupo_muscular" class="form-control" required>
                    <option value="">-- Seleccionar --</option>
                    <option>Pecho - Push</option><option>Espalda - Pull</option>
                    <option>Hombro</option><option>Bicep</option>
                    <option>Tricep</option><option>Abdomen</option>
                    <option>Piernas</option><option>Cuerpo Completo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nombre de la rutina</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Ejercicios (uno por línea, ej: Press de banca 4x10)</label>
                <textarea name="ejercicios" class="form-control" rows="6" placeholder="Press de banca 4x10&#10;Aperturas 3x15&#10;Fondos 3x12"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h2 class="modal-title">Editar Rutina</h2>
            <button class="modal-close" onclick="closeModal('modal-edit')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label>Grupo muscular</label>
                <select name="grupo_muscular" id="edit-grupo" class="form-control" required>
                    <option>Pecho - Push</option><option>Espalda - Pull</option>
                    <option>Hombro</option><option>Bicep</option>
                    <option>Tricep</option><option>Abdomen</option>
                    <option>Piernas</option><option>Cuerpo Completo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" id="edit-nombre" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" id="edit-descripcion" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Ejercicios (uno por línea)</label>
                <textarea name="ejercicios" id="edit-ejercicios" class="form-control" rows="6"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRutina(id, grupo, nombre, desc, ejerciciosJson) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-grupo').value = grupo;
    document.getElementById('edit-nombre').value = nombre;
    document.getElementById('edit-descripcion').value = desc;
    // Convertir array JSON a texto uno por línea
    const arr = Array.isArray(ejerciciosJson) ? ejerciciosJson : JSON.parse(ejerciciosJson);
    document.getElementById('edit-ejercicios').value = arr.join('\n');
    openModal('modal-edit');
}
</script>

<?php require_once '../includes/footer.php'; ?>
