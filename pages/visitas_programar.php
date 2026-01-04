<?php
/* =====================================================
   PROGRAMAR VISITAS - TECNOSUCRE
===================================================== */

require_once __DIR__ . '/../config/db.php';

$rol = $_SESSION['rol'];
$id_usuario = $_SESSION['id_usuario'];

// Si viene desde detalle de cliente, pre-cargar
$cliente_preseleccionado = $_GET['cliente'] ?? '';

// Obtener clientes según rol
if ($rol === 'admin') {
    $sql_clientes = "
        SELECT c.id_cliente, c.nombre_apellido, c.telefono, c.ubicacion,
               u.nombre AS instalador_nombre, u.apellido AS instalador_apellido
        FROM clientes c
        INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
        ORDER BY c.nombre_apellido
    ";
    $result_clientes = $conn->query($sql_clientes);
} else {
    $sql_clientes = "
        SELECT id_cliente, nombre_apellido, telefono, ubicacion
        FROM clientes
        WHERE id_usuario = ?
        ORDER BY nombre_apellido
    ";
    $stmt = $conn->prepare($sql_clientes);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result_clientes = $stmt->get_result();
}

// Obtener instaladores (solo admin)
$instaladores = [];
if ($rol === 'admin') {
    $result_inst = $conn->query("
        SELECT id_usuario, nombre, apellido 
        FROM usuarios 
        WHERE rol = 'instalador' AND estado = 1
        ORDER BY nombre
    ");
    while ($inst = $result_inst->fetch_assoc()) {
        $instaladores[] = $inst;
    }
}
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <h1><i class="fas fa-calendar-plus"></i> Programar Visita Técnica</h1>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Formulario de Visita</h3>
        </div>

        <form method="POST" action="app/actions/visitas_guardar.php">
            <div class="card-body">

                <!-- CLIENTE -->
                <div class="form-group">
                    <label><i class="fas fa-user-tie"></i> Cliente *</label>
                    <select name="id_cliente" id="cliente_select" class="form-control" required>
                        <option value="">Seleccione un cliente...</option>
                        <?php while ($c = $result_clientes->fetch_assoc()): ?>
                            <option value="<?= $c['id_cliente'] ?>" 
                                    data-telefono="<?= htmlspecialchars($c['telefono']) ?>"
                                    data-ubicacion="<?= htmlspecialchars($c['ubicacion']) ?>"
                                    <?= ($cliente_preseleccionado == $c['id_cliente']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre_apellido']) ?>
                                <?php if ($rol === 'admin'): ?>
                                    - <?= htmlspecialchars($c['instalador_nombre'] . ' ' . $c['instalador_apellido']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- INFO DEL CLIENTE (AUTO-COMPLETAR) -->
                <div class="row" id="cliente_info" style="display: none;">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <strong>Teléfono:</strong> <span id="info_telefono"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <strong>Ubicación:</strong> <span id="info_ubicacion"></span>
                        </div>
                    </div>
                </div>

                <!-- INSTALADOR (SOLO ADMIN) -->
                <?php if ($rol === 'admin'): ?>
                <div class="form-group">
                    <label><i class="fas fa-user-hard-hat"></i> Instalador Asignado *</label>
                    <select name="id_instalador" class="form-control" required>
                        <option value="">Seleccione instalador...</option>
                        <?php foreach ($instaladores as $inst): ?>
                            <option value="<?= $inst['id_usuario'] ?>">
                                <?= htmlspecialchars($inst['nombre'] . ' ' . $inst['apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="id_instalador" value="<?= $id_usuario ?>">
                <?php endif; ?>

                <!-- FECHA Y HORA -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Fecha de Visita *</label>
                            <input type="date" name="fecha_visita" class="form-control" 
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Hora de Visita *</label>
                            <input type="time" name="hora_visita" class="form-control" required>
                        </div>
                    </div>
                </div>

                <!-- TIPO DE VISITA -->
                <div class="form-group">
                    <label><i class="fas fa-clipboard-list"></i> Tipo de Visita *</label>
                    <select name="tipo_visita" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <option value="Evaluación">Evaluación / Cotización</option>
                        <option value="Instalación">Instalación</option>
                        <option value="Reparación">Reparación</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Seguimiento">Seguimiento</option>
                        <option value="Ampliación">Ampliación del Sistema</option>
                    </select>
                </div>

                <!-- PRIORIDAD -->
                <div class="form-group">
                    <label><i class="fas fa-flag"></i> Prioridad</label>
                    <select name="prioridad" class="form-control">
                        <option value="Normal">Normal</option>
                        <option value="Alta">Alta</option>
                        <option value="Urgente">Urgente</option>
                    </select>
                </div>

                <!-- NOTAS -->
                <div class="form-group">
                    <label><i class="fas fa-sticky-note"></i> Notas / Observaciones</label>
                    <textarea name="notas" class="form-control" rows="4" 
                              placeholder="Detalles adicionales sobre la visita..."></textarea>
                </div>

                <!-- RECORDATORIO -->
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="recordatorio" 
                               name="recordatorio" value="1" checked>
                        <label class="custom-control-label" for="recordatorio">
                            Enviar recordatorio al instalador
                        </label>
                    </div>
                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-save"></i> Programar Visita
                </button>
                <a href="index.php?p=visitas_lista" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>

    </div>

</div>
</section>

</div>

<script>
// Auto-completar info del cliente
document.getElementById('cliente_select').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const infoDiv = document.getElementById('cliente_info');
    
    if (this.value) {
        document.getElementById('info_telefono').textContent = option.dataset.telefono;
        document.getElementById('info_ubicacion').textContent = option.dataset.ubicacion;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
});

// Si viene pre-seleccionado, mostrar info
if (document.getElementById('cliente_select').value) {
    document.getElementById('cliente_select').dispatchEvent(new Event('change'));
}
</script>