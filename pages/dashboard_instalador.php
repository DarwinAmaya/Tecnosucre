<?php
/* =====================================================
   DASHBOARD INSTALADOR - TECNOSUCRE
   Panel personal del instalador con sus métricas
===================================================== */

require_once __DIR__ . '/../config/db.php';

$id_usuario = $_SESSION['id_usuario'];
$nombre_completo = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];

// MÉTRICAS DEL INSTALADOR
$metricas = [
    'clientes_total' => 0,
    'clientes_hoy' => 0,
    'clientes_semana' => 0,
    'clientes_mes' => 0,
    'visitas_pendientes' => 0,
    'visitas_hoy' => 0,
    'visitas_completadas' => 0,
    'muy_interesados' => 0
];

// Consultas
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM clientes WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$metricas['clientes_total'] = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(*) as c FROM clientes WHERE id_usuario = ? AND DATE(fecha_registro) = CURDATE()");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$metricas['clientes_hoy'] = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(*) as c FROM clientes WHERE id_usuario = ? AND YEARWEEK(fecha_registro) = YEARWEEK(CURDATE())");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$metricas['clientes_semana'] = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(*) as c FROM clientes WHERE id_usuario = ? AND MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$metricas['clientes_mes'] = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(*) as c FROM visitas WHERE id_instalador = ? AND estado IN ('Pendiente','Confirmada')");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$metricas['visitas_pendientes'] = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(*) as c FROM visitas WHERE id_instalador = ? AND DATE(fecha_visita) = CURDATE()");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$metricas['visitas_hoy'] = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(*) as c FROM visitas WHERE id_instalador = ? AND estado = 'Completada'");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$metricas['visitas_completadas'] = $stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(*) as c FROM clientes WHERE id_usuario = ? AND JSON_CONTAINS(forecast_pipeline, '\"Muy Interesado\"')");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$metricas['muy_interesados'] = $stmt->get_result()->fetch_assoc()['c'];

// MIS CLIENTES RECIENTES
$stmt = $conn->prepare("
    SELECT * FROM clientes 
    WHERE id_usuario = ? 
    ORDER BY fecha_registro DESC 
    LIMIT 5
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$mis_clientes = $stmt->get_result();

// MIS VISITAS PRÓXIMAS
$stmt = $conn->prepare("
    SELECT v.*, c.nombre_apellido, c.telefono, c.ubicacion
    FROM visitas v
    INNER JOIN clientes c ON c.id_cliente = v.id_cliente
    WHERE v.id_instalador = ?
    AND v.estado IN ('Pendiente','Confirmada')
    AND v.fecha_visita >= CURDATE()
    ORDER BY v.fecha_visita, v.hora_visita
    LIMIT 5
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$mis_visitas = $stmt->get_result();

// DISTRIBUCIÓN POR FORECAST DEL INSTALADOR
$forecast_stats = [];
$forecasts = ['Curiosidad', 'Necesidad', 'Interesado', 'Muy Interesado', 'Declinado'];
foreach ($forecasts as $f) {
    $sql = "SELECT COUNT(*) as c FROM clientes WHERE id_usuario = ? AND JSON_CONTAINS(forecast_pipeline, ?)";
    $stmt = $conn->prepare($sql);
    $val = json_encode($f);
    $stmt->bind_param("is", $id_usuario, $val);
    $stmt->execute();
    $forecast_stats[$f] = $stmt->get_result()->fetch_assoc()['c'];
}
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-hard-hat text-primary"></i> Mi Dashboard</h1>
                <p class="text-muted">Bienvenido, <?= htmlspecialchars($nombre_completo) ?></p>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <!-- MÉTRICAS PRINCIPALES -->
    <div class="row">
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?= $metricas['clientes_total'] ?></h3>
                    <p>Mis Clientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="index.php?p=clientes_lista" class="small-box-footer">
                    Ver todos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3><?= $metricas['muy_interesados'] ?></h3>
                    <p>Muy Interesados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-star"></i>
                </div>
                <a href="index.php?p=clientes_lista&forecast=Muy Interesado" class="small-box-footer">
                    Ver lista <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3><?= $metricas['visitas_pendientes'] ?></h3>
                    <p>Visitas Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <a href="index.php?p=visitas_lista" class="small-box-footer">
                    Ver agenda <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3><?= $metricas['visitas_completadas'] ?></h3>
                    <p>Visitas Realizadas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="index.php?p=visitas_lista" class="small-box-footer">
                    Ver historial <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- MÉTRICAS TEMPORALES -->
    <div class="row">
        
        <div class="col-lg-4 col-6">
            <div class="info-box bg-gradient-info">
                <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Clientes Hoy</span>
                    <span class="info-box-number"><?= $metricas['clientes_hoy'] ?></span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">
                        <?= $metricas['visitas_hoy'] ?> visita(s) programada(s)
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="info-box bg-gradient-success">
                <span class="info-box-icon"><i class="fas fa-calendar-week"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Esta Semana</span>
                    <span class="info-box-number"><?= $metricas['clientes_semana'] ?></span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">
                        Clientes registrados
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="info-box bg-gradient-warning">
                <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Este Mes</span>
                    <span class="info-box-number"><?= $metricas['clientes_mes'] ?></span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">
                        Clientes registrados
                    </span>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <!-- COLUMNA IZQUIERDA -->
        <div class="col-md-8">

            <!-- MIS CLIENTES RECIENTES -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock"></i> Mis Últimos Clientes</h3>
                    <div class="card-tools">
                        <a href="index.php?p=clientes_nuevo" class="btn btn-sm btn-success">
                            <i class="fas fa-plus"></i> Nuevo Cliente
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ($mis_clientes->num_rows > 0): ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Ubicación</th>
                                <th>Forecast</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($cliente = $mis_clientes->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($cliente['nombre_apellido']) ?></strong></td>
                                <td>
                                    <a href="tel:<?= $cliente['telefono'] ?>">
                                        <?= htmlspecialchars($cliente['telefono']) ?>
                                    </a>
                                </td>
                                <td><small><?= htmlspecialchars($cliente['ubicacion']) ?></small></td>
                                <td>
                                    <?php
                                    $forecast = json_decode($cliente['forecast_pipeline'], true)[0] ?? 'N/A';
                                    $badges = [
                                        'Curiosidad' => 'secondary',
                                        'Necesidad' => 'info',
                                        'Interesado' => 'primary',
                                        'Muy Interesado' => 'success',
                                        'Declinado' => 'danger'
                                    ];
                                    $badge = $badges[$forecast] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?= $badge ?>"><?= $forecast ?></span>
                                </td>
                                <td><small><?= date('d/m/Y', strtotime($cliente['fecha_registro'])) ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?p=clientes_detalle&id=<?= $cliente['id_cliente'] ?>" 
                                           class="btn btn-info" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?p=visitas_programar&cliente=<?= $cliente['id_cliente'] ?>" 
                                           class="btn btn-primary" title="Programar visita">
                                            <i class="fas fa-calendar-plus"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <p>Aún no has registrado clientes</p>
                        <a href="index.php?p=clientes_nuevo" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Registrar Mi Primer Cliente
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="index.php?p=clientes_lista" class="btn btn-sm btn-primary">
                        Ver Todos Mis Clientes
                    </a>
                </div>
            </div>

            <!-- MIS PRÓXIMAS VISITAS -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Mis Próximas Visitas</h3>
                    <div class="card-tools">
                        <a href="index.php?p=visitas_programar" class="btn btn-sm btn-warning">
                            <i class="fas fa-plus"></i> Programar
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ($mis_visitas->num_rows > 0): ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Ubicación</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($visita = $mis_visitas->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($visita['nombre_apellido']) ?></strong></td>
                                <td>
                                    <a href="tel:<?= $visita['telefono'] ?>">
                                        <i class="fas fa-phone text-success"></i>
                                    </a>
                                </td>
                                <td><small><?= htmlspecialchars($visita['ubicacion']) ?></small></td>
                                <td>
                                    <?php
                                    $fecha = new DateTime($visita['fecha_visita']);
                                    $hoy = new DateTime();
                                    $diff = $hoy->diff($fecha)->days;
                                    
                                    if ($fecha < $hoy) {
                                        $color = 'text-danger';
                                    } elseif ($diff <= 1) {
                                        $color = 'text-warning';
                                    } else {
                                        $color = 'text-dark';
                                    }
                                    ?>
                                    <span class="<?= $color ?>">
                                        <?= $fecha->format('d/m/Y') ?>
                                    </span>
                                </td>
                                <td><?= date('H:i', strtotime($visita['hora_visita'])) ?></td>
                                <td><small><?= $visita['tipo_visita'] ?></small></td>
                                <td>
                                    <span class="badge badge-<?= $visita['estado'] === 'Confirmada' ? 'success' : 'warning' ?>">
                                        <?= $visita['estado'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                        <p>No tienes visitas programadas</p>
                        <a href="index.php?p=visitas_programar" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Programar Visita
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="index.php?p=visitas_lista" class="btn btn-sm btn-success">
                        Ver Todas Mis Visitas
                    </a>
                </div>
            </div>

        </div>

        <!-- COLUMNA DERECHA -->
        <div class="col-md-4">

            <!-- MI FORECAST PIPELINE -->
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie"></i> Mi Pipeline</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($forecast_stats as $forecast => $count): ?>
                        <?php
                        $colors = [
                            'Curiosidad' => 'secondary',
                            'Necesidad' => 'info',
                            'Interesado' => 'primary',
                            'Muy Interesado' => 'success',
                            'Declinado' => 'danger'
                        ];
                        $color = $colors[$forecast];
                        $percentage = $metricas['clientes_total'] > 0 
                            ? round(($count / $metricas['clientes_total']) * 100) 
                            : 0;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?= $forecast ?></span>
                                <span><strong><?= $count ?></strong></span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-<?= $color ?>" 
                                     role="progressbar" 
                                     style="width: <?= $percentage ?>%">
                                    <?= $percentage ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ACCIONES RÁPIDAS -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="index.php?p=clientes_nuevo">
                                <i class="fas fa-user-plus text-success"></i> Registrar Cliente
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="index.php?p=visitas_programar">
                                <i class="fas fa-calendar-plus text-primary"></i> Programar Visita
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="index.php?p=clientes_lista">
                                <i class="fas fa-list text-info"></i> Ver Mis Clientes
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="index.php?p=visitas_lista">
                                <i class="fas fa-calendar-check text-warning"></i> Mi Agenda
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- TIPS -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lightbulb"></i> Consejos</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Tip:</strong> Registra cada cliente potencial inmediatamente después del contacto inicial para no perder ninguna oportunidad.
                    </div>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Recuerda:</strong> Actualiza el forecast de tus clientes después de cada visita para mantener tu pipeline al día.
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
</section>

</div>

<style>
.small-box {
    border-radius: 8px;
    transition: transform 0.3s;
}

.small-box:hover {
    transform: translateY(-5px);
}

.info-box {
    border-radius: 8px;
}

.card {
    border-radius: 8px;
}
</style>