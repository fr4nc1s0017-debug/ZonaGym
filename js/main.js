// ZonaGym v2 — Main JS

function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// Cerrar modal al hacer clic fuera
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

// Auto-dismiss alerts
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s';
        setTimeout(() => alert.remove(), 500);
    }, 4000);
});

// Confirmar eliminación
function confirmDelete(url, name) {
    if (confirm(`¿Seguro que deseas eliminar "${name}"?\nEsta acción no se puede deshacer.`)) {
        window.location.href = url;
    }
}

// Edit cliente
function editCliente(id, nombres, apellidos, dui, direccion) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nombres').value = nombres;
    document.getElementById('edit-apellidos').value = apellidos;
    document.getElementById('edit-dui').value = dui;
    document.getElementById('edit-direccion').value = direccion;
    openModal('modal-edit');
}

// Edit entrenador
function editEntrenador(id, nombres, apellidos, especialidad, turno) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nombres').value = nombres;
    document.getElementById('edit-apellidos').value = apellidos;
    document.getElementById('edit-especialidad').value = especialidad;
    document.getElementById('edit-turno').value = turno;
    openModal('modal-edit');
}

// Edit membresía
function editMembresia(id, clienteId, membresiaId, fechaInicio, fechaVencimiento) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-cliente-id').value = clienteId;
    document.getElementById('edit-membresia-id').value = membresiaId;
    document.getElementById('edit-fecha-inicio').value = fechaInicio;
    document.getElementById('edit-fecha-vencimiento').value = fechaVencimiento;
    openModal('modal-edit');
}

// Dashboard live refresh
function refreshDashboard() {
    fetch('/zonagym2/api/stats.php')
        .then(r => r.json())
        .then(data => {
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            set('stat-activos', data.activos);
            set('stat-vencidos', data.vencidos);
            set('stat-entrenadores', data.entrenadores);
            set('stat-ingresos', '$' + parseFloat(data.ingresos).toFixed(0));
        })
        .catch(err => console.error('Stats error:', err));
}

if (document.getElementById('stat-activos')) {
    setInterval(refreshDashboard, 30000);
}

// Toggle reporte sections
function showSection(id) {
    document.querySelectorAll('.report-section').forEach(s => {
        s.style.display = (s.id === id && s.style.display === 'none') ? 'block' : 'none';
    });
}
