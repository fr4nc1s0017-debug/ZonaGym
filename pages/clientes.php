<?php
$pageTitle = 'Clientes';
$currentPage = 'clientes';
require_once '../includes/db.php';
requireAdmin();
require_once '../includes/header.php';

$conn = getConnection();
$msg = '';
$msgType = '';

// Agregar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {

        $nombres   = $conn->real_escape_string(trim($_POST['nombres']));
        $apellidos = $conn->real_escape_string(trim($_POST['apellidos']));
        $dui       = $conn->real_escape_string(trim($_POST['dui']));
        $direccion = $conn->real_escape_string(trim($_POST['direccion']));
        $email     = $conn->real_escape_string(trim($_POST['email']));
        $password  = trim($_POST['password'] ?? '');

        // Validaciones
        if (!$nombres || !$apellidos || !$dui || !$email || !$password) {

            $msg = 'Todos los campos obligatorios deben completarse.';
            $msgType = 'error';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {

            $msg = 'El correo electrónico no es válido.';
            $msgType = 'error';
        } elseif (strlen($password) < 6) {

            $msg = 'La contraseña debe tener al menos 6 caracteres.';
            $msgType = 'error';
        } else {

            // Verificar email único
            $chk = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $chk->bind_param('s', $_POST['email']);
            $chk->execute();

            $existe = $chk->get_result()->fetch_assoc();

            $chk->close();

            if ($existe) {

                $msg = 'Ya existe una cuenta con ese correo electrónico.';
                $msgType = 'error';
            } else {

                $hash = password_hash($password, PASSWORD_BCRYPT);

                // Crear usuario
                $stmt = $conn->prepare("
                INSERT INTO usuarios
                (nombre, apellidos, email, password_hash, rol)
                VALUES (?, ?, ?, ?, 'usuario')
            ");

                $stmt->bind_param(
                    'ssss',
                    $_POST['nombres'],
                    $_POST['apellidos'],
                    $_POST['email'],
                    $hash
                );

                $stmt->execute();

                $nuevoUserId = $conn->insert_id;

                $stmt->close();

                // Crear cliente vinculado
                $stmt2 = $conn->prepare("
                INSERT INTO clientes
                (nombres, apellidos, dui, direccion, usuario_id)
                VALUES (?, ?, ?, ?, ?)
            ");

                $stmt2->bind_param(
                    'ssssi',
                    $_POST['nombres'],
                    $_POST['apellidos'],
                    $_POST['dui'],
                    $_POST['direccion'],
                    $nuevoUserId
                );

                $stmt2->execute();

                $stmt2->close();

                $msg = 'Cliente y cuenta de usuario creados exitosamente.';
                $msgType = 'success';
            }
        }
    }

    if ($_POST['action'] === 'edit') {
        $id        = (int)$_POST['id'];
        $nombres   = escape($conn, $_POST['nombres']);
        $apellidos = escape($conn, $_POST['apellidos']);
        $dui       = escape($conn, $_POST['dui']);
        $direccion = escape($conn, $_POST['direccion']);

        // Actualizar cliente
        $conn->query("UPDATE clientes SET nombres='$nombres', apellidos='$apellidos', dui='$dui', direccion='$direccion' WHERE id=$id");

        // Actualizar nombre en usuario vinculado también
        $cliente = $conn->query("SELECT usuario_id FROM clientes WHERE id=$id")->fetch_assoc();
        if ($cliente['usuario_id']) {
            $uid = (int)$cliente['usuario_id'];
            $conn->query("UPDATE usuarios SET nombre='$nombres', apellidos='$apellidos' WHERE id=$uid");
        }

        $msg = 'Cliente actualizado.';
        $msgType = 'success';
    }
}

// Eliminar cliente
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Obtener usuario vinculado antes de eliminar
    $c = $conn->query("SELECT usuario_id FROM clientes WHERE id=$id")->fetch_assoc();
    $conn->query("DELETE FROM clientes WHERE id=$id");
    // Eliminar usuario vinculado si existe
    if ($c && $c['usuario_id']) {
        $uid = (int)$c['usuario_id'];
        $conn->query("DELETE FROM usuarios WHERE id=$uid");
    }
    header('Location: clientes.php?msg=eliminado');
    exit;
}

$clientes = $conn->query("
    SELECT c.*, u.email, u.activo as u_activo,
           cm.estado as mem_estado, m.nombre as mem_nombre
    FROM clientes c
    LEFT JOIN usuarios u ON u.id = c.usuario_id
    LEFT JOIN cliente_membresias cm ON cm.id = (
        SELECT id FROM cliente_membresias 
        WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1
    )
    LEFT JOIN membresias m ON m.id = cm.membresia_id
    ORDER BY c.id
");

?>

<div class="page-container">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><i class="fa-solid fa-circle-info"></i> <?= $msg ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check"></i> Cliente eliminado.</div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">Administración de Usuarios</h1>
        <button class="btn btn-primary" onclick="openModal('modal-add')">
            <i class="fa-solid fa-plus"></i> Agregar
        </button>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>DUI</th>
                    <th>Correo</th>
                    <th>Membresía</th>
                    <th>Estado</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($clientes->num_rows === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--text-muted); padding:32px">No hay clientes registrados.</td>
                    </tr>
                <?php endif; ?>
                <?php while ($c = $clientes->fetch_assoc()):
                    $badgeClass = $c['mem_estado'] === 'Activo'
                        ? 'badge-active'
                        : 'badge-expired';
                ?>

                    <tr>

                        <td><?= $c['id'] ?></td>

                        <td><?= htmlspecialchars($c['nombres']) ?></td>

                        <td><?= htmlspecialchars($c['apellidos']) ?></td>

                        <td><?= htmlspecialchars($c['dui']) ?></td>

                        <td style="font-size:13px; color:var(--text-secondary)">
                            <?= $c['email']
                                ? htmlspecialchars($c['email'])
                                : '<span style="color:var(--text-muted)">—</span>'
                            ?>
                        </td>

                        <td>

                            <?php if ($c['mem_nombre']): ?>

                                <?= htmlspecialchars($c['mem_nombre']) ?>

                            <?php else: ?>

                                <span style="color:var(--text-muted)">
                                    No asignada
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($c['mem_estado']): ?>

                                <span class="badge <?= $badgeClass ?>">
                                    <?= $c['mem_estado'] ?>
                                </span>

                            <?php else: ?>

                                <span style="color:var(--text-muted); font-size:13px">
                                    —
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <button
                                class="btn-icon btn-edit"
                                onclick="editCliente(
                <?= $c['id'] ?>,
                '<?= addslashes($c['nombres']) ?>',
                '<?= addslashes($c['apellidos']) ?>',
                '<?= $c['dui'] ?>',
                '<?= addslashes($c['direccion']) ?>'
            )">
                                <i class="fa-solid fa-pen"></i>
                            </button>

                            <button
                                class="btn-icon btn-delete"
                                onclick="confirmDelete(
                'clientes.php?delete=<?= $c['id'] ?>',
                '<?= addslashes($c['nombres'] . ' ' . $c['apellidos']) ?>'
            )">
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

            <h2 class="modal-title">
                Registrar Cliente
            </h2>

            <button
                class="modal-close"
                onclick="closeModal('modal-add')">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <form method="POST">

            <input type="hidden" name="action" value="add">

            <div class="form-row">

                <div class="form-group">

                    <label>Nombres *</label>

                    <input
                        type="text"
                        name="nombres"
                        class="form-control"
                        required>

                </div>

                <div class="form-group">

                    <label>Apellidos *</label>

                    <input
                        type="text"
                        name="apellidos"
                        class="form-control"
                        required>

                </div>

            </div>

            <div class="form-group">

                <label>DUI *</label>

                <input
                    type="text"
                    name="dui"
                    class="form-control"
                    placeholder="00000000-0"
                    maxlength="10"
                    required>

            </div>

            <div class="form-group">

                <label>
                    Correo electrónico *
                    <small style="color:var(--text-muted)">
                        (será usado para iniciar sesión)
                    </small>
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    required>

            </div>

            <div class="form-group">

                <label>
                    Contraseña *
                    <small style="color:var(--text-muted)">
                        (mín. 6 caracteres)
                    </small>
                </label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    required>

            </div>

            <div class="form-group">

                <label>Dirección</label>

                <textarea
                    name="direccion"
                    class="form-control"></textarea>

            </div>

            <div style="
                background:rgba(21,101,192,0.1);
                border:1px solid rgba(21,101,192,0.3);
                border-radius:8px;
                padding:12px;
                margin-bottom:16px;
                font-size:12px;
                color:var(--blue-light)
            ">

                <i class="fa-solid fa-circle-info"></i>

                Se creará automáticamente una cuenta de usuario
                con el correo y contraseña indicados.

                La membresía se asigna por separado desde la sección
                <strong>Membresías</strong>.

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Guardar
                </button>

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('modal-add')">
                    Cancelar
                </button>

            </div>

        </form>

    </div>

</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Editar Usuario</h2>
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
                <label>DUI</label>
                <input type="text" name="dui" id="edit-dui" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <textarea name="direccion" id="edit-direccion" class="form-control"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>