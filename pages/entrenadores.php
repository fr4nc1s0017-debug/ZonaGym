<?php
$pageTitle = 'Entrenadores';
$currentPage = 'entrenadores';
require_once '../includes/db.php';
requireAdmin();
require_once '../includes/header.php';

$conn = getConnection();
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $nombres = $conn->real_escape_string(trim($_POST['nombres']));
        $apellidos = $conn->real_escape_string(trim($_POST['apellidos']));
        $especialidad = $conn->real_escape_string(trim($_POST['especialidad']));
        $turno = $conn->real_escape_string($_POST['turno']);
        $result = $conn->query("INSERT INTO entrenadores (nombres, apellidos, especialidad, turno) VALUES ('$nombres','$apellidos','$especialidad','$turno')");
        $msg = $result ? 'Entrenador agregado.' : 'Error: ' . $conn->error;
        $msgType = $result ? 'success' : 'error';
    }
    if ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $nombres = $conn->real_escape_string(trim($_POST['nombres']));
        $apellidos = $conn->real_escape_string(trim($_POST['apellidos']));
        $especialidad = $conn->real_escape_string(trim($_POST['especialidad']));
        $turno = $conn->real_escape_string($_POST['turno']);
        $result = $conn->query("UPDATE entrenadores SET nombres='$nombres', apellidos='$apellidos', especialidad='$especialidad', turno='$turno' WHERE id=$id");
        $msg = $result ? 'Entrenador actualizado.' : 'Error: ' . $conn->error;
        $msgType = $result ? 'success' : 'error';
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM entrenadores WHERE id=$id");
    header('Location: entrenadores.php?msg=eliminado');
    exit;
}

$entrenadores = $conn->query("SELECT * FROM entrenadores ORDER BY id");

$turnoClass = ['Mañana' => 'badge-morning', 'Tarde' => 'badge-afternoon', 'Noche' => 'badge-night'];
?>

<div class="page-container">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><i class="fa-solid fa-circle-info"></i> <?= $msg ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check"></i> Entrenador eliminado.</div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">Administración de Entrenadores</h1>
        <button class="btn btn-primary" onclick="openModal('modal-add')">
            <i class="fa-solid fa-plus"></i> Añadir
        </button>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Especialidad</th>
                    <th>Turno</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($entrenadores->num_rows === 0): ?>
                    <tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:32px">No hay entrenadores registrados.</td></tr>
                <?php endif; ?>
                <?php while ($e = $entrenadores->fetch_assoc()): 
                    $tc = $turnoClass[$e['turno']] ?? '';
                ?>
                <tr>
                    <td><?= $e['id'] ?></td>
                    <td><?= htmlspecialchars($e['nombres']) ?></td>
                    <td><?= htmlspecialchars($e['apellidos']) ?></td>
                    <td><?= htmlspecialchars($e['especialidad']) ?></td>
                    <td><span class="badge <?= $tc ?>"><?= $e['turno'] ?></span></td>
                    <td>
                        <button class="btn-icon btn-edit"
                            onclick="editEntrenador(<?= $e['id'] ?>, '<?= addslashes($e['nombres']) ?>', '<?= addslashes($e['apellidos']) ?>', '<?= addslashes($e['especialidad']) ?>', '<?= $e['turno'] ?>')">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn-icon btn-delete"
                            onclick="confirmDelete('entrenadores.php?delete=<?= $e['id'] ?>', '<?= addslashes($e['nombres'].' '.$e['apellidos']) ?>')">
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
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Registrar Entrenador</h2>
            <button class="modal-close" onclick="closeModal('modal-add')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombres" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Apellidos</label>
                <input type="text" name="apellidos" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Especialidad</label>
                <input type="text" name="especialidad" class="form-control" placeholder="Ej. Crossfit, Yoga, Pesas..." required>
            </div>
            <div class="form-group">
                <label>Turno</label>
                <select name="turno" class="form-control" required>
                    <option value="">-- Seleccionar --</option>
                    <option value="Mañana">Mañana</option>
                    <option value="Tarde">Tarde</option>
                    <option value="Noche">Noche</option>
                </select>
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
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Editar Entrenador</h2>
            <button class="modal-close" onclick="closeModal('modal-edit')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombres" id="edit-nombres" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Apellidos</label>
                <input type="text" name="apellidos" id="edit-apellidos" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Especialidad</label>
                <input type="text" name="especialidad" id="edit-especialidad" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Turno</label>
                <select name="turno" id="edit-turno" class="form-control" required>
                    <option value="Mañana">Mañana</option>
                    <option value="Tarde">Tarde</option>
                    <option value="Noche">Noche</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
