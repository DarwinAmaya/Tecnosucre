<!-- ==================== visitas_detalle.php ==================== -->
<?php
// app/actions/visitas_detalle.php

session_start();
require_once __DIR__ . '/../config/db.php';

$id_visita = $_GET['id'] ?? 0;

if (!$id_visita) {
    echo '<div class="alert alert-danger">ID inválido</div>';
    exit;
}

// Obtener detalle completo
$sql = "
    SELECT 
        v.*,
        c.nombre_apellido AS cliente_nombre,
        c.telefono AS cliente_telefono,
        c.ubicacion AS cliente_ubicacion,
        c.target,
        c.productos,
        c.forecast_pipeline,
        u.nombre AS instalador_nombre,
        u.apellido AS instalador_apellido,
        u.cedula AS instalador_cedula
    FROM visitas v
    INNER JOIN clientes c ON c.id_cliente = v.id_cliente
    INNER JOIN usuarios u ON u.id_usuario = v.id_instalador
    WHERE v.id_visita = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_visita);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Visita no encontrada</div>';
    exit;
}

$v = $result->fetch_assoc();
?>

<div class="row">
    <div class="col-md-6">
        <h5 class="text-primary"><i class="fas fa-user-tie"></i> Información del Cliente</h5>
        <table class="table table-sm table-bordered">
            <tr>
                <th width="140">Cliente:</th>
                <td><strong><?= htmlspecialchars($v['cliente_nombre']) ?></strong></td>
            </tr>
            <tr>
                <th>Teléfono:</th>
                <td>
                    <a href="tel:<?= $v['cliente_telefono'] ?>">
                        <?= htmlspecialchars($v['cliente_telefono']) ?>
                    </a>
                </td>
            </tr>
            <tr>
                <th>Ubicación:</th>
                <td><?= htmlspecialchars($v['cliente_ubicacion']) ?></td>
            </tr>
            <tr>
                <th>Target:</th>
                <td><?= implode(', ', json_decode($v['target'], true) ?? []) ?></td>
            </tr>
            <tr>
                <th>Productos:</th>
                <td><?= implode(', ', json_decode($v['productos'], true) ?? []) ?></td>
            </tr>
            <tr>
                <th>Forecast:</th>
                <td>
                    <?php
                    $forecast = json_decode($v['forecast_pipeline'], true) ?? [];
                    foreach ($forecast as $f) {
                        echo "<span class='badge badge-info mr-1'>$f</span>";
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h5 class="text-success"><i class="fas fa-calendar-check"></i> Detalles de la Visita</h5>
        <table class="table table-sm table-bordered">
            <tr>
                <th width="140">ID Visita:</th>
                <td><strong>#<?= $v['id_visita'] ?></strong></td>
            </tr>
            <tr>
                <th>Fecha:</th>
                <td><?= date('d/m/Y', strtotime($v['fecha_visita'])) ?></td>
            </tr>
            <tr>
                <th>Hora:</th>
                <td><?= date('h:i A', strtotime($v['hora_visita'])) ?></td>
            </tr>
            <tr>
                <th>Tipo:</th>
                <td><?= htmlspecialchars($v['tipo_visita']) ?></td>
            </tr>
            <tr>
                <th>Prioridad:</th>
                <td>
                    <?php
                    $colors = ['Normal' => 'secondary', 'Alta' => 'warning', 'Urgente' => 'danger'];
                    $color = $colors[$v['prioridad']] ?? 'secondary';
                    echo "<span class='badge badge-{$color}'>{$v['prioridad']}</span>";
                    ?>
                </td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td>
                    <?php
                    $colors = [
                        'Pendiente' => 'warning',
                        'Confirmada' => 'info',
                        'En Proceso' => 'primary',
                        'Completada' => 'success',
                        'Cancelada' => 'danger'
                    ];
                    $color = $colors[$v['estado']] ?? 'secondary';
                    echo "<span class='badge badge-{$color}'>{$v['estado']}</span>";
                    ?>
                </td>
            </tr>
            <tr>
                <th>Instalador:</th>
                <td>
                    <?= htmlspecialchars($v['instalador_nombre'] . ' ' . $v['instalador_apellido']) ?>
                    <br><small class="text-muted">CI: <?= $v['instalador_cedula'] ?></small>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($v['notas'])): ?>
<div class="mt-3">
    <h5><i class="fas fa-sticky-note"></i> Notas / Observaciones</h5>
    <div class="alert alert-secondary">
        <?= nl2br(htmlspecialchars($v['notas'])) ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($v['observaciones'])): ?>
<div class="mt-3">
    <h5><i class="fas fa-clipboard"></i> Observaciones del Estado</h5>
    <div class="alert alert-info">
        <?= nl2br(htmlspecialchars($v['observaciones'])) ?>
    </div>
</div>
<?php endif; ?>

<div class="mt-3 text-muted">
    <small>
        <i class="fas fa-clock"></i> Creado: <?= date('d/m/Y H:i', strtotime($v['fecha_creacion'])) ?>
        <?php if ($v['fecha_actualizacion']): ?>
            | Actualizado: <?= date('d/m/Y H:i', strtotime($v['fecha_actualizacion'])) ?>
        <?php endif; ?>
    </small>
</div>