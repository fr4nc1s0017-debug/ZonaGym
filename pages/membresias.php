<?php
$pageTitle = 'Membresías';
$currentPage = 'membresias';
require_once '../includes/db.php';
requireAdmin();
require_once '../includes/header.php';

$conn = getConnection();
$msg = ''; $msgType = '';

// Actualizar estados vencidos
$conn->query("UPDATE cliente_membresias SET estado = 'Vencido' WHERE fecha_vencimiento < CURDATE() AND estado = 'Activo'");

// Asignar membresía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $clienteId = (int)$_POST['cliente_id'];
        $membresiaId = (int)$_POST['membresia_id'];
        $fechaInicio = $conn->real_escape_string($_POST['fecha_inicio']);

        // Get duracion
        $m = $conn->query("SELECT duracion_dias FROM membresias WHERE id=$membresiaId")->fetch_assoc();
        $fechaVenc = date('Y-m-d', strtotime($fechaInicio . ' +' . $m['duracion_dias'] . ' days'));

        // Desactivar membresías anteriores
        $conn->query("UPDATE cliente_membresias SET estado='Vencido' WHERE cliente_id=$clienteId");

        $result = $conn->query("INSERT INTO cliente_membresias (cliente_id, membresia_id, fecha_inicio, fecha_vencimiento) VALUES ($clienteId, $membresiaId, '$fechaInicio', '$fechaVenc')");
        $msg = $result ? 'Membresía asignada exitosamente.' : 'Error: ' . $conn->error;
        $msgType = $result ? 'success' : 'error';
    }

    if ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $membresiaId = (int)$_POST['membresia_id'];
        $fechaInicio = $conn->real_escape_string($_POST['fecha_inicio']);
        $m = $conn->query("SELECT duracion_dias FROM membresias WHERE id=$membresiaId")->fetch_assoc();
        $fechaVenc = date('Y-m-d', strtotime($fechaInicio . ' +' . $m['duracion_dias'] . ' days'));
        $estado = strtotime($fechaVenc) >= strtotime('today') ? 'Activo' : 'Vencido';

        $conn->query("UPDATE cliente_membresias SET membresia_id=$membresiaId, fecha_inicio='$fechaInicio', fecha_vencimiento='$fechaVenc', estado='$estado' WHERE id=$id");
        $msg = 'Membresía actualizada.'; $msgType = 'success';
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM cliente_membresias WHERE id=$id");
    header('Location: membresias.php?msg=eliminado');
    exit;
}

// Filter by membresia type
$filtro = isset($_GET['tipo']) ? (int)$_GET['tipo'] : 0;
$where = $filtro ? "AND cm.membresia_id = $filtro" : '';

$membresias = $conn->query("SELECT * FROM membresias ORDER BY precio");
$clientes = $conn->query("SELECT * FROM clientes ORDER BY nombres");

$lista = $conn->query("SELECT cm.*, c.nombres, c.apellidos, m.nombre as tipo_mem 
    FROM cliente_membresias cm 
    JOIN clientes c ON c.id = cm.cliente_id 
    JOIN membresias m ON m.id = cm.membresia_id 
    WHERE 1=1 $where 
    ORDER BY cm.id DESC");
?>

<div class="page-container">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><i class="fa-solid fa-circle-info"></i> <?= $msg ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check"></i> Membresía eliminada.</div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">Membresías</h1>
        <button class="btn btn-primary" onclick="openModal('modal-add')">
            <i class="fa-solid fa-plus"></i> Asignar Membresía
        </button>
    </div>

    <!-- Planes -->
    <div class="membresias-grid">
        <?php 
        $membresias->data_seek(0);
        while ($m = $membresias->fetch_assoc()): 
            $isSelected = $filtro == $m['id'];
        ?>
        <a href="membresias.php<?= $isSelected ? '' : '?tipo='.$m['id'] ?>" style="text-decoration:none">
            <div class="membresia-card <?= $isSelected ? 'selected' : '' ?>">
                <h3><?= htmlspecialchars($m['nombre']) ?></h3>
                <div class="price"><sup>$</sup><?= number_format($m['precio'], 0) ?></div>
                <div class="price-mo">/mes</div>
                <ul>
                    <?php foreach(explode(',', $m['descripcion']) as $b): ?>
                        <li><?= trim($b) ?></li>
                    <?php endforeach; ?>
                </ul>
                <span class="btn btn-sm <?= $isSelected ? 'btn-primary' : 'btn-secondary' ?>" style="width:100%;justify-content:center">
                    <?= $isSelected ? 'Viendo' : 'Ver' ?>
                </span>
            </div>
        </a>
        <?php endwhile; ?>
    </div>

    <!-- Lista de miembros -->
    <h2 class="section-title" style="font-size:22px; margin-bottom:16px">
        <?= $filtro ? 'Miembros con membresía filtrada' : 'Todos los miembros' ?>
        <?php if ($filtro): ?>
            <a href="membresias.php" style="font-size:13px; color:var(--accent); margin-left:12px; font-family:'Inter'">Ver todos</a>
        <?php endif; ?>
    </h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombres</th>
                    <th>Membresía</th>
                    <th>Vencimiento</th>
                    <th>Estado</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lista->num_rows === 0): ?>
                    <tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:32px">No hay membresías registradas.</td></tr>
                <?php endif; ?>
                <?php while ($r = $lista->fetch_assoc()): 
                    $badgeClass = $r['estado'] === 'Activo' ? 'badge-active' : 'badge-expired';
                ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['nombres'] . ' ' . $r['apellidos']) ?></td>
                    <td><?= htmlspecialchars($r['tipo_mem']) ?></td>
                    <td><?= date('d-m-Y', strtotime($r['fecha_vencimiento'])) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $r['estado'] ?></span></td>
                    <td>
                        <button class="btn-icon btn-edit"
                            onclick="editMembresia(<?= $r['id'] ?>, <?= $r['cliente_id'] ?>, <?= $r['membresia_id'] ?>, '<?= $r['fecha_inicio'] ?>', '<?= $r['fecha_vencimiento'] ?>')">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn-icon btn-delete"
                            onclick="confirmDelete('membresias.php?delete=<?= $r['id'] ?>', 'esta membresía')">
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
            <h2 class="modal-title">Asignar Membresía</h2>
            <button class="modal-close" onclick="closeModal('modal-add')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Cliente</label>
                <select name="cliente_id" class="form-control" required>
                    <option value="">-- Seleccionar cliente --</option>
                    <?php $clientes->data_seek(0); while ($c = $clientes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombres'].' '.$c['apellidos']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Membresía</label>
                <select name="membresia_id" class="form-control" required>
                    <option value="">-- Seleccionar membresía --</option>
                    <?php $membresias->data_seek(0); while ($m = $membresias->fetch_assoc()): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?> - $<?= $m['precio'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Asignar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Editar Membresía</h2>
            <button class="modal-close" onclick="closeModal('modal-edit')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <input type="hidden" name="cliente_id" id="edit-cliente-id">
            <div class="form-group">
                <label>Tipo de Membresía</label>
                <select name="membresia_id" id="edit-membresia-id" class="form-control" required>
                    <?php $membresias->data_seek(0); while ($m = $membresias->fetch_assoc()): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?> - $<?= $m['precio'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Inicio</label>
                <input type="date" name="fecha_inicio" id="edit-fecha-inicio" class="form-control" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
