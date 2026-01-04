<?php
/* =====================================================
   DETALLE COMPLETO DE CLIENTE - TECNOSUCRE
   Con timeline de visitas e información detallada
===================================================== */

require_once __DIR__ . '/../config/db.php';

$id_cliente = $_GET['id'] ?? 0;
$rol = $_SESSION['rol'];
$id_usuario = $_SESSION['id_usuario'];

// Validar que el ID sea válido
if (!$id_cliente || !is_numeric($id_cliente)) {
    header("Location: index.php?p=clientes_lista");
    exit;
}

// Consulta según rol
if ($rol === 'admin') {
    $sql = "
        SELECT c.*, 
               u.nombre AS nombre_usuario, 
               u.apellido AS apellido_usuario, 
               u.cedula,
               u.id_usuario as uid
        FROM clientes c
        INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
        WHERE c.id_cliente = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
} else {
    // Instalador solo ve sus clientes
    $sql = "
        SELECT c.*, 
               u.nombre AS nombre_usuario, 
               u.apellido AS apellido_usuario, 
               u.cedula,
               u.id_usuario as uid
        FROM clientes c
        INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
        WHERE c.id_cliente = ? AND c.id_usuario = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_cliente, $id_usuario);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='content-wrapper'><section class='content'><div class='alert alert-danger m-4'>
          <i class='fas fa-exclamation-triangle'></i> Cliente no encontrado o no tiene permisos para verlo.
          <br><a href='index.php?p=clientes_lista' class='btn btn-secondary mt-2'>
          <i class='fas fa-arrow-left'></i> Volver</a></div></section></div>";
    exit;
}

$cliente = $result->fetch_assoc();

// Decodificar JSON
$target = json_decode($cliente['target'], true) ?? [];
$productos = json_decode($cliente['productos'], true) ?? [];
$marcas = json_decode($cliente['marcas'], true) ?? [];
$diagnostico = json_decode($cliente['diagnostico'], true) ?? [];
$forecast = json_decode($cliente['forecast_pipeline'], true) ?? [];

// Obtener visitas del cliente
$sql_visitas = "
    SELECT v.*, u.nombre, u.apellido
    FROM visitas v
    LEFT JOIN usuarios u ON u.id_usuario = v.id_instalador
    WHERE v.id_cliente = ?
    ORDER BY v.fecha_visita DESC, v.hora_visita DESC
    LIMIT 10
";
$stmt_v = $conn->prepare($sql_visitas);
$stmt_v->bind_param("i", $id_cliente);
$stmt_v->execute();
$visitas = $stmt_v->get_result();

// Función para badge de forecast
function getForecastBadge($forecast) {
    $badges = [
        'Curiosidad' => 'secondary',
        'Necesidad' => 'info',
        'Interesado' => 'primary',
        'Muy Interesado' => 'success',
        'Declinado' => 'danger'
    ];
    
    $html = '';
    foreach ($forecast as $f) {
        $class = $badges[$f] ?? 'secondary';
        $html .= "<span class='badge badge-{$class} badge-lg mr-1 mb-1'><i class='fas fa-flag'></i> {$f}</span>";
    }
    return $html;
}
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    <i class="fas fa-user-tie text-primary"></i> 
                    <?= htmlspecialchars($cliente['nombre_apellido']) ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php?p=clientes_lista">Clientes</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <!-- BOTONES DE ACCIÓN SUPERIORES -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="index.php?p=clientes_lista" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            
            <a href="index.php?p=visitas_programar&cliente=<?= $id_cliente ?>" class="btn btn-primary">
                <i class="fas fa-calendar-plus"></i> Programar Visita
            </a>
            
            <?php if ($rol === 'admin'): ?>
            <a href="app/actions/exportar_cliente_pdf.php?id=<?= $id_cliente ?>" 
               class="btn btn-danger" target="_blank">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </a>
            <?php endif; ?>
            
            <!-- BOTÓN EDITAR (AHORA FUNCIONAL) -->
            <a href="index.php?p=clientes_editar&id=<?= $id_cliente ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Editar
            </a>

            <a href="https://wa.me/58<?= substr($cliente['telefono'], 1) ?>" 
               class="btn btn-success float-right" target="_blank">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
        </div>
    </div>

    <div class="row">
        <!-- COLUMNA IZQUIERDA -->
        <div class="col-md-8">

            <!-- INFORMACIÓN GENERAL -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Información General
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl>
                                <dt><i class="fas fa-user text-primary"></i> Cliente</dt>
                                <dd class="h5"><?= htmlspecialchars($cliente['nombre_apellido']) ?></dd>

                                <dt><i class="fas fa-phone text-success"></i> Teléfono</dt>
                                <dd>
                                    <a href="tel:<?= htmlspecialchars($cliente['telefono']) ?>" class="h5">
                                        <?= htmlspecialchars($cliente['telefono']) ?>
                                    </a>
                                </dd>

                                <dt><i class="fas fa-map-marker-alt text-danger"></i> Ubicación</dt>
                                <dd><?= htmlspecialchars($cliente['ubicacion']) ?></dd>
                            </dl>
                        </div>

                        <div class="col-md-6">
                            <dl>
                                <dt><i class="fas fa-user-hard-hat text-info"></i> Registrado por</dt>
                                <dd>
                                    <?= htmlspecialchars($cliente['nombre_usuario'] . ' ' . $cliente['apellido_usuario']) ?>
                                    <br><small class="text-muted">CI: <?= htmlspecialchars($cliente['cedula']) ?></small>
                                </dd>

                                <dt><i class="fas fa-calendar text-secondary"></i> Fecha Registro</dt>
                                <dd><?= date('d/m/Y H:i', strtotime($cliente['fecha_registro'])) ?></dd>

                                <?php if ($cliente['fecha_visita_desde'] && $cliente['fecha_visita_hasta']): ?>
                                <dt><i class="fas fa-calendar-alt text-warning"></i> Período Sugerido</dt>
                                <dd>
                                    <?= date('d/m/Y', strtotime($cliente['fecha_visita_desde'])) ?>
                                    <strong>→</strong>
                                    <?= date('d/m/Y', strtotime($cliente['fecha_visita_hasta'])) ?>
                                </dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TARGET Y PRODUCTOS -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-bullseye"></i> Target</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($target)): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($target as $t): ?>
                                        <li class="mb-2">
                                            <span class="badge badge-info badge-lg">
                                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($t) ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">No especificado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-box"></i> Productos</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($productos)): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($productos as $p): ?>
                                        <li class="mb-2">
                                            <span class="badge badge-success badge-lg">
                                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($p) ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">No especificado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MARCAS Y DIAGNÓSTICO -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tags"></i> Marcas</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($marcas)): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($marcas as $m): ?>
                                        <li class="mb-2">
                                            <span class="badge badge-warning badge-lg">
                                                <i class="fas fa-tag"></i> <?= htmlspecialchars($m) ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (!empty($cliente['marca_otra'])): ?>
                                    <div class="alert alert-light mt-2">
                                        <strong>Otra:</strong> <?= htmlspecialchars($cliente['marca_otra']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No especificado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tools"></i> Diagnóstico</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($diagnostico)): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($diagnostico as $d): ?>
                                        <li class="mb-2">
                                            <span class="badge badge-secondary badge-lg">
                                                <i class="fas fa-wrench"></i> <?= htmlspecialchars($d) ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">No especificado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HISTORIAL DE VISITAS -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Historial de Visitas
                        <span class="badge badge-light"><?= $visitas->num_rows ?></span>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($visitas->num_rows > 0): ?>
                        <div class="timeline">
                            <?php while ($v = $visitas->fetch_assoc()): ?>
                                <?php
                                $color_class = match($v['estado']) {
                                    'Completada' => 'success',
                                    'Cancelada' => 'danger',
                                    'En Proceso' => 'primary',
                                    default => 'warning'
                                };
                                ?>
                                <div>
                                    <i class="fas fa-calendar bg-<?= $color_class ?>"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            <i class="fas fa-clock"></i> 
                                            <?= date('d/m/Y H:i', strtotime($v['fecha_visita'] . ' ' . $v['hora_visita'])) ?>
                                        </span>
                                        <h3 class="timeline-header">
                                            <?= htmlspecialchars($v['tipo_visita']) ?>
                                            <span class="badge badge-<?= $color_class ?> float-right">
                                                <?= $v['estado'] ?>
                                            </span>
                                        </h3>
                                        <div class="timeline-body">
                                            <strong>Instalador:</strong> 
                                            <?= htmlspecialchars($v['nombre'] . ' ' . $v['apellido']) ?>
                                            <br>
                                            <strong>Prioridad:</strong> 
                                            <span class="badge badge-secondary"><?= $v['prioridad'] ?></span>
                                            <?php if ($v['notas']): ?>
                                                <br><strong>Notas:</strong> <?= nl2br(htmlspecialchars($v['notas'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <div><i class="fas fa-clock bg-gray"></i></div>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <p>No hay visitas registradas para este cliente</p>
                            <a href="index.php?p=visitas_programar&cliente=<?= $id_cliente ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i> Programar Primera Visita
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- COLUMNA DERECHA -->
        <div class="col-md-4">

            <!-- FORECAST PIPELINE -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i> Forecast Pipeline
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div style="font-size: 16px;">
                        <?= getForecastBadge($forecast) ?>
                    </div>
                </div>
            </div>

            <!-- ACCIONES RÁPIDAS -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt"></i> Acciones Rápidas
                    </h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="tel:<?= $cliente['telefono'] ?>">
                                <i class="fas fa-phone text-success"></i> Llamar Cliente
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="https://wa.me/58<?= substr($cliente['telefono'], 1) ?>" target="_blank">
                                <i class="fab fa-whatsapp text-success"></i> Enviar WhatsApp
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="index.php?p=visitas_programar&cliente=<?= $id_cliente ?>">
                                <i class="fas fa-calendar-plus text-primary"></i> Programar Visita
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="index.php?p=clientes_editar&id=<?= $id_cliente ?>">
                                <i class="fas fa-edit text-warning"></i> Editar Cliente
                            </a>
                        </li>
                        <?php if ($rol === 'admin'): ?>
                        <li class="list-group-item">
                            <a href="app/actions/exportar_cliente_pdf.php?id=<?= $id_cliente ?>" target="_blank">
                                <i class="fas fa-file-pdf text-danger"></i> Exportar PDF
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- INFORMACIÓN ADICIONAL -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Información
                    </h3>
                </div>
                <div class="card-body">
                    <p><strong>ID Cliente:</strong> #<?= $id_cliente ?></p>
                    <p><strong>Registrado:</strong> 
                        <?= date('d/m/Y', strtotime($cliente['fecha_registro'])) ?>
                    </p>
                    <p><strong>Días desde registro:</strong>
                        <?php
                        $fecha_reg = new DateTime($cliente['fecha_registro']);
                        $hoy = new DateTime();
                        $diff = $hoy->diff($fecha_reg)->days;
                        echo $diff . ' días';
                        ?>
                    </p>
                </div>
            </div>

        </div>
    </div>

</div>
</section>

</div>

<style>
.badge-lg {
    padding: 8px 12px;
    font-size: 14px;
}

dl dt {
    margin-top: 10px;
}

.timeline {
    position: relative;
    margin: 0 0 30px 0;
    padding: 0;
    list-style: none;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #ddd;
    left: 31px;
    margin: 0;
    border-radius: 2px;
}

.timeline > div > .timeline-item {
    margin-right: 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
    margin-left: 60px;
    margin-bottom: 10px;
    padding: 10px;
}

.timeline > div > .fa,
.timeline > div > .fas {
    width: 30px;
    height: 30px;
    font-size: 15px;
    line-height: 30px;
    position: absolute;
    color: #fff;
    background: #999;
    border-radius: 50%;
    text-align: center;
    left: 18px;
    top: 0;
}

.timeline > div > .timeline-item > .time {
    color: #999;
    float: right;
    font-size: 12px;
}

.timeline > div > .timeline-item > .timeline-header {
    margin: 0;
    color: #555;
    border-bottom: 1px solid #f4f4f4;
    padding-bottom: 10px;
    font-size: 14px;
    font-weight: 600;
}

.timeline > div > .timeline-item > .timeline-body {
    padding-top: 10px;
}
</style>