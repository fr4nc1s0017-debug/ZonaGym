<?php
$pageTitle   = 'Usuarios';
$currentPage = 'usuarios';
require_once '../includes/db.php';
requireAdmin();
require_once '../includes/header.php';

$conn = getConnection();
$msg = ''; $msgType = '';

// Cambiar rol o estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_rol') {
        $id      = (int)$_POST['id'];
        $nuevoRol = $_POST['nuevo_rol'];
        if (in_array($nuevoRol, ['admin','usuario']) && $id !== (int)currentUser()['id']) {
            $conn->query("UPDATE usuarios SET rol='$nuevoRol' WHERE id=$id");
            $msg = 'Rol actualizado.'; $msgType = 'success';
        } else {
            $msg = 'No puedes cambiar tu propio rol.'; $msgType = 'error';
        }
    }
    if ($_POST['action'] === 'toggle_activo') {
        $id = (int)$_POST['id'];
        if ($id !== (int)currentUser()['id']) {
            $conn->query("UPDATE usuarios SET activo = NOT activo WHERE id=$id");
            $msg = 'Estado actualizado.'; $msgType = 'success';
        } else {
            $msg = 'No puedes desactivar tu propia cuenta.'; $msgType = 'error';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== (int)currentUser()['id']) {
        $conn->query("DELETE FROM usuarios WHERE id=$id");
        header('Location: usuarios.php?msg=eliminado'); exit;
    }
}

$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id");
?>

<div class="page-container">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><i class="fa-solid fa-circle-info"></i> <?= $msg ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check"></i> Usuario eliminado.</div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">Gestión de Usuarios</h1>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Nombre</th><th>Email</th>
                    <th>Rol</th><th>Estado</th><th>Registrado</th><th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($usuarios->num_rows === 0): ?>
                    <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-users"></i><p>No hay usuarios.</p></div></td></tr>
                <?php endif; ?>
                <?php while ($u = $usuarios->fetch_assoc()):
                    $esYo = ($u['id'] == currentUser()['id']);
                ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td>
                        <?= clean($u['nombre'].' '.$u['apellidos']) ?>
                        <?php if ($esYo): ?>
                            <span style="font-size:10px; color:var(--accent)"> (tú)</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px; color:var(--text-secondary)"><?= clean($u['email']) ?></td>
                    <td>
                        <span class="badge <?= $u['rol'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                            <?= $u['rol'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $u['activo'] ? 'badge-active' : 'badge-expired' ?>">
                            <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td style="font-size:12px; color:var(--text-muted)">
                        <?= date('d/m/Y', strtotime($u['creado_en'])) ?>
                    </td>
                    <td>
                        <?php if (!$esYo): ?>
                        <!-- Toggle rol -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle_rol">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="nuevo_rol" value="<?= $u['rol'] === 'admin' ? 'usuario' : 'admin' ?>">
                            <button type="submit" class="btn-icon btn-edit" title="Cambiar rol">
                                <i class="fa-solid fa-user-gear"></i>
                            </button>
                        </form>
                        <!-- Toggle activo -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle_activo">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn-icon" style="color:<?= $u['activo'] ? '#ffd54f' : 'var(--success-light)' ?>" title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>">
                                <i class="fa-solid <?= $u['activo'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                            </button>
                        </form>
                        <!-- Eliminar -->
                        <button class="btn-icon btn-delete" title="Eliminar"
                            onclick="confirmDelete('usuarios.php?delete=<?= $u['id'] ?>', '<?= addslashes($u['nombre']) ?>')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <?php else: ?>
                            <span style="font-size:11px; color:var(--text-muted)">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
