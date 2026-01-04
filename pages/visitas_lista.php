<?php
/* =====================================================
   LISTA DE VISITAS PROGRAMADAS - TECNOSUCRE
===================================================== */

require_once __DIR__ . '/../config/db.php';

$rol = $_SESSION['rol'];
$id_usuario = $_SESSION['id_usuario'];

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todas';
$filtro_fecha = $_GET['fecha'] ?? '';

// Construcción de consulta según rol
if ($rol === 'admin') {
    $sql = "
        SELECT 
            v.*,
            c.nombre_apellido AS cliente_nombre,
            c.telefono AS cliente_telefono,
            c.ubicacion AS cliente_ubicacion,
            u.nombre AS instalador_nombre,
            u.apellido AS instalador_apellido
        FROM visitas v
        INNER JOIN clientes c ON c.id_cliente = v.id_cliente
        INNER JOIN usuarios u ON u.id_usuario = v.id_instalador
        WHERE 1=1
    ";
} else {
    $sql = "
        SELECT 
            v.*,
            c.nombre_apellido AS cliente_nombre,
            c.telefono AS cliente_telefono,
            c.ubicacion AS cliente_ubicacion
        FROM visitas v
        INNER JOIN clientes c ON c.id_cliente = v.id_cliente
        WHERE v.id_instalador = ?
    ";
}

// Aplicar filtros
$params = [];
$types = '';

if ($rol !== 'admin') {
    $params[] = $id_usuario;
    $types .= 'i';
}

if ($filtro_estado !== 'todas') {
    $sql .= " AND v.estado = ?";
    $params[] = $filtro_estado;
    $types .= 's';
}

if (!empty($filtro_fecha)) {
    $sql .= " AND DATE(v.fecha_visita) = ?";
    $params[] = $filtro_fecha;
    $types .= 's';
}

$sql .= " ORDER BY v.fecha_visita DESC, v.hora_visita DESC";

// Ejecutar consulta
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Función para badge de estado
function getEstadoBadge($estado) {
    $badges = [
        'Pendiente' => 'warning',
        'Confirmada' => 'info',
        'En Proceso' => 'primary',
        'Completada' => 'success',
        'Cancelada' => 'danger',
        'Reprogramada' => 'secondary'
    ];
    $class = $badges[$estado] ?? 'secondary';
    return "<span class='badge badge-{$class}'>{$estado}</span>";
}

function getPrioridadBadge($prioridad) {
    $badges = [
        'Normal' => 'secondary',
        'Alta' => 'warning',
        'Urgente' => 'danger'
    ];
    $class = $badges[$prioridad] ?? 'secondary';
    return "<span class='badge badge-{$class}'><i class='fas fa-flag'></i> {$prioridad}</span>";
}
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-calendar-check"></i> Visitas Programadas</h1>
            </div>
            <div class="col-sm-6">
                <a href="index.php?p=visitas_programar" class="btn btn-primary float-right">
                    <i class="fas fa-plus"></i> Nueva Visita
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <!-- FILTROS -->
    <div class="card card-info collapsed-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <input type="hidden" name="p" value="visitas_lista">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="estado" class="form-control">
                                <option value="todas" <?= $filtro_estado === 'todas' ? 'selected' : '' ?>>Todas</option>
                                <option value="Pendiente" <?= $filtro_estado === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="Confirmada" <?= $filtro_estado === 'Confirmada' ? 'selected' : '' ?>>Confirmada</option>
                                <option value="En Proceso" <?= $filtro_estado === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                                <option value="Completada" <?= $filtro_estado === 'Completada' ? 'selected' : '' ?>>Completada</option>
                                <option value="Cancelada" <?= $filtro_estado === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($filtro_fecha) ?>">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="index.php?p=visitas_lista" class="btn btn-secondary">
                            <i class="fas fa-eraser"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- LISTA DE VISITAS -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Todas las Visitas (<?= $result->num_rows ?>)</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Ubicación</th>
                        <?php if ($rol === 'admin'): ?>
                            <th>Instalador</th>
                        <?php endif; ?>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ($result->num_rows > 0): ?>
                    <?php while ($v = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $v['id_visita'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($v['cliente_nombre']) ?></strong>
                            </td>
                            <td>
                                <a href="tel:<?= $v['cliente_telefono'] ?>">
                                    <i class="fas fa-phone text-success"></i>
                                    <?= htmlspecialchars($v['cliente_telefono']) ?>
                                </a>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($v['cliente_ubicacion']) ?></small>
                            </td>
                            
                            <?php if ($rol === 'admin'): ?>
                                <td><?= htmlspecialchars($v['instalador_nombre'] . ' ' . $v['instalador_apellido']) ?></td>
                            <?php endif; ?>
                            
                            <td>
                                <?php
                                $fecha = new DateTime($v['fecha_visita']);
                                $hoy = new DateTime();
                                $diff = $hoy->diff($fecha)->days;
                                $color = 'text-dark';
                                
                                if ($fecha < $hoy) $color = 'text-danger';
                                elseif ($diff <= 1) $color = 'text-warning';
                                elseif ($diff <= 3) $color = 'text-info';
                                ?>
                                <span class="<?= $color ?>">
                                    <?= $fecha->format('d/m/Y') ?>
                                </span>
                            </td>
                            
                            <td><?= date('h:i A', strtotime($v['hora_visita'])) ?></td>
                            <td><small><?= htmlspecialchars($v['tipo_visita']) ?></small></td>
                            <td><?= getPrioridadBadge($v['prioridad']) ?></td>
                            <td><?= getEstadoBadge($v['estado']) ?></td>
                            
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="verDetalle(<?= $v['id_visita'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($v['estado'] !== 'Completada' && $v['estado'] !== 'Cancelada'): ?>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                onclick="cambiarEstado(<?= $v['id_visita'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $rol === 'admin' ? '11' : '10' ?>" class="text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i><br>
                            No hay visitas programadas con los filtros seleccionados
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>
</section>

</div>

<!-- Modal para Ver Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Detalle de Visita</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="detalleContent">
                <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Cambiar Estado -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Cambiar Estado</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="app/actions/visitas_cambiar_estado.php">
                <div class="modal-body">
                    <input type="hidden" name="id_visita" id="estado_id_visita">
                    
                    <div class="form-group">
                        <label>Nuevo Estado</label>
                        <select name="estado" class="form-control" required>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Confirmada">Confirmada</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Completada">Completada</option>
                            <option value="Cancelada">Cancelada</option>
                            <option value="Reprogramada">Reprogramada</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function verDetalle(id) {
    $('#modalDetalle').modal('show');
    $('#detalleContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
    
    fetch('app/actions/visitas_detalle.php?id=' + id)
        .then(r => r.text())
        .then(html => $('#detalleContent').html(html))
        .catch(() => $('#detalleContent').html('<div class="alert alert-danger">Error al cargar</div>'));
}

function cambiarEstado(id) {
    $('#estado_id_visita').val(id);
    $('#modalEstado').modal('show');
}
</script>