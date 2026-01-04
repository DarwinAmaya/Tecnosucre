<?php
/* =====================================================
   DASHBOARD ADMINISTRADOR - TECNOSUCRE
   Panel de control completo con métricas y gráficos
===================================================== */

require_once __DIR__ . '/../config/db.php';

// MÉTRICAS GENERALES
$metricas = [
    'clientes_total' => 0,
    'clientes_hoy' => 0,
    'clientes_mes' => 0,
    'visitas_pendientes' => 0,
    'visitas_hoy' => 0,
    'visitas_completadas' => 0,
    'instaladores_activos' => 0,
    'muy_interesados' => 0
];

$metricas['clientes_total'] = $conn->query("SELECT COUNT(*) as c FROM clientes")->fetch_assoc()['c'];
$metricas['clientes_hoy'] = $conn->query("SELECT COUNT(*) as c FROM clientes WHERE DATE(fecha_registro) = CURDATE()")->fetch_assoc()['c'];
$metricas['clientes_mes'] = $conn->query("SELECT COUNT(*) as c FROM clientes WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())")->fetch_assoc()['c'];
$metricas['visitas_pendientes'] = $conn->query("SELECT COUNT(*) as c FROM visitas WHERE estado IN ('Pendiente','Confirmada')")->fetch_assoc()['c'];
$metricas['visitas_hoy'] = $conn->query("SELECT COUNT(*) as c FROM visitas WHERE DATE(fecha_visita) = CURDATE()")->fetch_assoc()['c'];
$metricas['visitas_completadas'] = $conn->query("SELECT COUNT(*) as c FROM visitas WHERE estado = 'Completada'")->fetch_assoc()['c'];
$metricas['instaladores_activos'] = $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE rol = 'instalador' AND estado = 1")->fetch_assoc()['c'];
$metricas['muy_interesados'] = $conn->query("SELECT COUNT(*) as c FROM clientes WHERE JSON_CONTAINS(forecast_pipeline, '\"Muy Interesado\"')")->fetch_assoc()['c'];

// TOP 5 INSTALADORES
$top_instaladores = $conn->query("
    SELECT u.nombre, u.apellido, COUNT(c.id_cliente) as total
    FROM usuarios u
    LEFT JOIN clientes c ON c.id_usuario = u.id_usuario
    WHERE u.rol = 'instalador'
    GROUP BY u.id_usuario
    ORDER BY total DESC
    LIMIT 5
");

// CLIENTES RECIENTES
$clientes_recientes = $conn->query("
    SELECT c.*, u.nombre AS instalador_nombre, u.apellido AS instalador_apellido
    FROM clientes c
    INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
    ORDER BY c.fecha_registro DESC
    LIMIT 5
");

// VISITAS PRÓXIMAS
$visitas_proximas = $conn->query("
    SELECT v.*, c.nombre_apellido, u.nombre AS inst_nombre, u.apellido AS inst_apellido
    FROM visitas v
    INNER JOIN clientes c ON c.id_cliente = v.id_cliente
    INNER JOIN usuarios u ON u.id_usuario = v.id_instalador
    WHERE v.estado IN ('Pendiente','Confirmada')
    AND v.fecha_visita >= CURDATE()
    ORDER BY v.fecha_visita, v.hora_visita
    LIMIT 5
");

// DISTRIBUCIÓN POR FORECAST
$forecast_stats = [];
$forecasts = ['Curiosidad', 'Necesidad', 'Interesado', 'Muy Interesado', 'Declinado'];
foreach ($forecasts as $f) {
    $sql = "SELECT COUNT(*) as c FROM clientes WHERE JSON_CONTAINS(forecast_pipeline, ?)";
    $stmt = $conn->prepare($sql);
    $val = json_encode($f);
    $stmt->bind_param("s", $val);
    $stmt->execute();
    $forecast_stats[$f] = $stmt->get_result()->fetch_assoc()['c'];
}
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-tachometer-alt text-primary"></i> Dashboard Administrador</h1>
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
                    <p>Total Clientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="index.php?p=clientes_lista" class="small-box-footer">
                    Ver más <i class="fas fa-arrow-circle-right"></i>
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
                    Ver más <i class="fas fa-arrow-circle-right"></i>
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
                    Ver más <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3><?= $metricas['instaladores_activos'] ?></h3>
                    <p>Instaladores Activos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <a href="index.php?p=usuarios_lista" class="small-box-footer">
                    Ver más <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- MÉTRICAS SECUNDARIAS -->
    <div class="row">
        
        <div class="col-lg-4 col-6">
            <div class="info-box bg-gradient-info">
                <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Clientes Hoy</span>
                    <span class="info-box-number"><?= $metricas['clientes_hoy'] ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="info-box bg-gradient-success">
                <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Clientes Este Mes</span>
                    <span class="info-box-number"><?= $metricas['clientes_mes'] ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="info-box bg-gradient-warning">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Visitas Completadas</span>
                    <span class="info-box-number"><?= $metricas['visitas_completadas'] ?></span>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <!-- COLUMNA IZQUIERDA -->
        <div class="col-md-8">

            <!-- TOP 5 INSTALADORES -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-trophy"></i> Top 5 Instaladores</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Instalador</th>
                                <th width="100">Clientes</th>
                                <th width="150">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $pos = 1;
                        while ($inst = $top_instaladores->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>
                                    <?php if ($pos == 1): ?>
                                        <span class="badge badge-warning"><i class="fas fa-trophy"></i></span>
                                    <?php elseif ($pos == 2): ?>
                                        <span class="badge badge-secondary"><i class="fas fa-medal"></i></span>
                                    <?php elseif ($pos == 3): ?>
                                        <span class="badge badge-info"><i class="fas fa-medal"></i></span>
                                    <?php else: ?>
                                        <?= $pos ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($inst['nombre'] . ' ' . $inst['apellido']) ?></td>
                                <td>
                                    <span class="badge badge-primary badge-lg"><?= $inst['total'] ?></span>
                                </td>
                                <td>
                                    <a href="index.php?p=clientes_lista" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Ver Clientes
                                    </a>
                                </td>
                            </tr>
                        <?php 
                        $pos++;
                        endwhile; 
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CLIENTES RECIENTES -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock"></i> Últimos Clientes Registrados</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Forecast</th>
                                <th>Instalador</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($cliente = $clientes_recientes->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($cliente['nombre_apellido']) ?></strong></td>
                                <td><?= htmlspecialchars($cliente['telefono']) ?></td>
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
                                <td><small><?= htmlspecialchars($cliente['instalador_nombre'] . ' ' . $cliente['instalador_apellido']) ?></small></td>
                                <td><small><?= date('d/m/Y H:i', strtotime($cliente['fecha_registro'])) ?></small></td>
                                <td>
                                    <a href="index.php?p=clientes_detalle&id=<?= $cliente['id_cliente'] ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-center">
                    <a href="index.php?p=clientes_lista" class="btn btn-sm btn-success">
                        Ver Todos los Clientes
                    </a>
                </div>
            </div>

        </div>

        <!-- COLUMNA DERECHA -->
        <div class="col-md-4">

            <!-- DISTRIBUCIÓN POR FORECAST -->
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie"></i> Forecast Pipeline</h3>
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
                                <span><strong><?= $count ?></strong> (<?= $percentage ?>%)</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-<?= $color ?>" 
                                     role="progressbar" 
                                     style="width: <?= $percentage ?>%"
                                     aria-valuenow="<?= $percentage ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- VISITAS PRÓXIMAS -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Próximas Visitas</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                    <?php if ($visitas_proximas->num_rows > 0): ?>
                        <?php while ($visita = $visitas_proximas->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($visita['nombre_apellido']) ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($visita['inst_nombre'] . ' ' . $visita['inst_apellido']) ?>
                                        </small>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-primary">
                                            <?= date('d/m', strtotime($visita['fecha_visita'])) ?>
                                        </span>
                                        <br>
                                        <small><?= date('H:i', strtotime($visita['hora_visita'])) ?></small>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted">
                            No hay visitas próximas
                        </li>
                    <?php endif; ?>
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <a href="index.php?p=visitas_lista" class="btn btn-sm btn-info">
                        Ver Todas las Visitas
                    </a>
                </div>
            </div>

            <!-- ACCIONES RÁPIDAS -->
            <div class="card card-primary">
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
                            <a href="index.php?p=usuarios_nuevo">
                                <i class="fas fa-user-shield text-danger"></i> Nuevo Instalador
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="index.php?p=configuraciones">
                                <i class="fas fa-file-excel text-success"></i> Exportar Datos
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

</div>
</section>

</div>

<style>
.badge-lg {
    font-size: 1rem;
    padding: 6px 10px;
}

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