<?php
/* =====================================================
   LISTA DE CLIENTES - TECNOSUCRE
   Con búsqueda, filtros y paginación
===================================================== */

require_once __DIR__ . '/../config/db.php';

$rol = $_SESSION['rol'];
$id_usuario = $_SESSION['id_usuario'];

// PARÁMETROS DE BÚSQUEDA Y FILTROS
$busqueda = $_GET['buscar'] ?? '';
$filtro_forecast = $_GET['forecast'] ?? '';
$filtro_producto = $_GET['producto'] ?? '';
$orden = $_GET['orden'] ?? 'fecha_desc';

// Paginación
$registros_por_pagina = 15;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $registros_por_pagina;

// ------------------------------
// CONSTRUCCIÓN DE CONSULTA
// ------------------------------
$where_conditions = [];
$params = [];
$types = '';

// Filtro por rol
if ($rol !== 'admin') {
    $where_conditions[] = "c.id_usuario = ?";
    $params[] = $id_usuario;
    $types .= 'i';
}

// Búsqueda por nombre o teléfono
if (!empty($busqueda)) {
    $where_conditions[] = "(c.nombre_apellido LIKE ? OR c.telefono LIKE ?)";
    $busqueda_param = "%{$busqueda}%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= 'ss';
}

// Filtro por forecast
if (!empty($filtro_forecast)) {
    $where_conditions[] = "JSON_CONTAINS(c.forecast_pipeline, ?)";
    $params[] = json_encode($filtro_forecast);
    $types .= 's';
}

// Filtro por producto
if (!empty($filtro_producto)) {
    $where_conditions[] = "JSON_CONTAINS(c.productos, ?)";
    $params[] = json_encode($filtro_producto);
    $types .= 's';
}

// Construir WHERE
$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Ordenamiento
$order_by = match($orden) {
    'fecha_asc' => 'c.fecha_registro ASC',
    'nombre_asc' => 'c.nombre_apellido ASC',
    'nombre_desc' => 'c.nombre_apellido DESC',
    default => 'c.fecha_registro DESC'
};

// Consulta principal
$sql = "
    SELECT 
        c.*,
        u.nombre AS nombre_usuario,
        u.apellido AS apellido_usuario
    FROM clientes c
    INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
    {$where_sql}
    ORDER BY {$order_by}
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

// Agregar parámetros de paginación
$params[] = $registros_por_pagina;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Contar total de registros para paginación
$sql_count = "
    SELECT COUNT(*) as total
    FROM clientes c
    {$where_sql}
";

$stmt_count = $conn->prepare($sql_count);
if (!empty($where_conditions)) {
    // Remover los últimos 2 parámetros (limit y offset)
    $params_count = array_slice($params, 0, -2);
    $types_count = substr($types, 0, -2);
    if (!empty($params_count)) {
        $stmt_count->bind_param($types_count, ...$params_count);
    }
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Función para generar badge de forecast
function getForecastBadgeClass($forecast_json) {
    $forecast = json_decode($forecast_json, true) ?? [];
    if (in_array('Muy Interesado', $forecast)) return 'success';
    if (in_array('Interesado', $forecast)) return 'primary';
    if (in_array('Necesidad', $forecast)) return 'info';
    if (in_array('Declinado', $forecast)) return 'danger';
    return 'secondary';
}

function getForecastText($forecast_json) {
    $forecast = json_decode($forecast_json, true) ?? [];
    return !empty($forecast) ? $forecast[0] : 'Sin definir';
}
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-users"></i> Lista de Clientes</h1>
            </div>
            <div class="col-sm-6">
                <a href="index.php?p=clientes_nuevo" class="btn btn-primary float-right">
                    <i class="fas fa-user-plus"></i> Registrar Cliente
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <!-- MENSAJE DE SESIÓN -->
    <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?? 'info' ?> alert-dismissible fade show">
        <?= $_SESSION['mensaje'] ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php 
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
    endif; 
    ?>

    <!-- BARRA DE BÚSQUEDA Y FILTROS -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-search"></i> Búsqueda y Filtros</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <input type="hidden" name="p" value="clientes_lista">
                <div class="row">
                    
                    <!-- Búsqueda por nombre/teléfono -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-search"></i> Buscar</label>
                            <input type="text" 
                                   name="buscar" 
                                   class="form-control" 
                                   placeholder="Nombre o teléfono"
                                   value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                    </div>

                    <!-- Filtro por Forecast -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-chart-line"></i> Forecast</label>
                            <select name="forecast" class="form-control">
                                <option value="">Todos</option>
                                <option value="Curiosidad" <?= $filtro_forecast === 'Curiosidad' ? 'selected' : '' ?>>Curiosidad</option>
                                <option value="Necesidad" <?= $filtro_forecast === 'Necesidad' ? 'selected' : '' ?>>Necesidad</option>
                                <option value="Interesado" <?= $filtro_forecast === 'Interesado' ? 'selected' : '' ?>>Interesado</option>
                                <option value="Muy Interesado" <?= $filtro_forecast === 'Muy Interesado' ? 'selected' : '' ?>>Muy Interesado</option>
                                <option value="Declinado" <?= $filtro_forecast === 'Declinado' ? 'selected' : '' ?>>Declinado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filtro por Producto -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-box"></i> Producto</label>
                            <select name="producto" class="form-control">
                                <option value="">Todos</option>
                                <option value="CCTV" <?= $filtro_producto === 'CCTV' ? 'selected' : '' ?>>CCTV</option>
                                <option value="Alarmas" <?= $filtro_producto === 'Alarmas' ? 'selected' : '' ?>>Alarmas</option>
                                <option value="Video Portero" <?= $filtro_producto === 'Video Portero' ? 'selected' : '' ?>>Video Portero</option>
                                <option value="Control de Acceso" <?= $filtro_producto === 'Control de Acceso' ? 'selected' : '' ?>>Control de Acceso</option>
                                <option value="Display" <?= $filtro_producto === 'Display' ? 'selected' : '' ?>>Display</option>
                            </select>
                        </div>
                    </div>

                    <!-- Ordenar por -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-sort"></i> Ordenar por</label>
                            <select name="orden" class="form-control">
                                <option value="fecha_desc" <?= $orden === 'fecha_desc' ? 'selected' : '' ?>>Más recientes</option>
                                <option value="fecha_asc" <?= $orden === 'fecha_asc' ? 'selected' : '' ?>>Más antiguos</option>
                                <option value="nombre_asc" <?= $orden === 'nombre_asc' ? 'selected' : '' ?>>Nombre A-Z</option>
                                <option value="nombre_desc" <?= $orden === 'nombre_desc' ? 'selected' : '' ?>>Nombre Z-A</option>
                            </select>
                        </div>
                    </div>

                </div>
                
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="index.php?p=clientes_lista" class="btn btn-secondary">
                            <i class="fas fa-eraser"></i> Limpiar Filtros
                        </a>
                        <?php if ($rol === 'admin'): ?>
                        <a href="index.php?p=configuraciones" class="btn btn-success float-right">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ESTADÍSTICAS RÁPIDAS -->
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?= $total_registros ?></h3>
                    <p>Total Clientes</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <?php if ($rol === 'admin'): ?>
        <div class="col-md-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <?php
                    $muy_interesados = $conn->query("SELECT COUNT(*) as c FROM clientes WHERE JSON_CONTAINS(forecast_pipeline, '\"Muy Interesado\"')")->fetch_assoc()['c'];
                    ?>
                    <h3><?= $muy_interesados ?></h3>
                    <p>Muy Interesados</p>
                </div>
                <div class="icon"><i class="fas fa-star"></i></div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <?php
                    $hoy = $conn->query("SELECT COUNT(*) as c FROM clientes WHERE DATE(fecha_registro) = CURDATE()")->fetch_assoc()['c'];
                    ?>
                    <h3><?= $hoy ?></h3>
                    <p>Registrados Hoy</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <?php
                    $mes = $conn->query("SELECT COUNT(*) as c FROM clientes WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())")->fetch_assoc()['c'];
                    ?>
                    <h3><?= $mes ?></h3>
                    <p>Este Mes</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- TABLA DE CLIENTES -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> 
                Listado de Clientes 
                <span class="badge badge-primary"><?= $total_registros ?></span>
            </h3>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th width="50">#</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Ubicación</th>
                        <th>Target</th>
                        <th>Productos</th>
                        <th>Forecast</th>
                        <?php if ($rol === 'admin'): ?>
                        <th>Instalador</th>
                        <?php endif; ?>
                        <th>Fecha Registro</th>
                        <th width="100">Acciones</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ($result->num_rows > 0): ?>
                    <?php 
                    $contador = $offset + 1;
                    while ($row = $result->fetch_assoc()): 
                    ?>

                    <tr>
                        <td><?= $contador++ ?></td>
                        
                        <td>
                            <strong><?= htmlspecialchars($row['nombre_apellido']) ?></strong>
                        </td>

                        <td>
                            <a href="tel:<?= htmlspecialchars($row['telefono']) ?>" class="text-success">
                                <i class="fas fa-phone"></i> <?= htmlspecialchars($row['telefono']) ?>
                            </a>
                        </td>

                        <td>
                            <small>
                                <i class="fas fa-map-marker-alt text-danger"></i>
                                <?= htmlspecialchars($row['ubicacion']) ?>
                            </small>
                        </td>

                        <td>
                            <small>
                                <?php
                                $targets = json_decode($row['target'], true) ?? [];
                                echo !empty($targets) ? htmlspecialchars($targets[0]) : '-';
                                if (count($targets) > 1) {
                                    echo " <span class='badge badge-secondary'>+" . (count($targets) - 1) . "</span>";
                                }
                                ?>
                            </small>
                        </td>

                        <td>
                            <small>
                                <?php
                                $productos = json_decode($row['productos'], true) ?? [];
                                echo !empty($productos) ? htmlspecialchars($productos[0]) : '-';
                                if (count($productos) > 1) {
                                    echo " <span class='badge badge-info'>+" . (count($productos) - 1) . "</span>";
                                }
                                ?>
                            </small>
                        </td>

                        <td>
                            <?php
                            $badgeClass = getForecastBadgeClass($row['forecast_pipeline']);
                            $forecastText = getForecastText($row['forecast_pipeline']);
                            ?>
                            <span class="badge badge-<?= $badgeClass ?>">
                                <?= htmlspecialchars($forecastText) ?>
                            </span>
                        </td>

                        <?php if ($rol === 'admin'): ?>
                        <td>
                            <small>
                                <?= htmlspecialchars($row['nombre_usuario'] . ' ' . $row['apellido_usuario']) ?>
                            </small>
                        </td>
                        <?php endif; ?>

                        <td>
                            <small>
                                <?= date('d/m/Y', strtotime($row['fecha_registro'])) ?>
                                <br>
                                <span class="text-muted"><?= date('H:i', strtotime($row['fecha_registro'])) ?></span>
                            </small>
                        </td>

                        <td>
                            <div class="btn-group">
                                <a href="index.php?p=clientes_detalle&id=<?= $row['id_cliente'] ?>" 
                                   class="btn btn-sm btn-info"
                                   title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?p=visitas_programar&cliente=<?= $row['id_cliente'] ?>" 
                                   class="btn btn-sm btn-primary"
                                   title="Programar visita">
                                    <i class="fas fa-calendar-plus"></i>
                                </a>
                            </div>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="<?= $rol === 'admin' ? '10' : '9' ?>" class="text-center py-5">
                            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                            <br>
                            <h5 class="text-muted">No hay clientes registrados</h5>
                            <?php if (!empty($busqueda) || !empty($filtro_forecast) || !empty($filtro_producto)): ?>
                                <p class="text-muted">Intenta cambiar los filtros de búsqueda</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($total_paginas > 1): ?>
        <div class="card-footer clearfix">
            <ul class="pagination pagination-sm m-0 float-right">
                
                <!-- Botón Anterior -->
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=clientes_lista&pagina=<?= $pagina - 1 ?>&buscar=<?= urlencode($busqueda) ?>&forecast=<?= urlencode($filtro_forecast) ?>&producto=<?= urlencode($filtro_producto) ?>&orden=<?= $orden ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>

                <!-- Números de página -->
                <?php
                $rango = 2;
                $inicio = max(1, $pagina - $rango);
                $fin = min($total_paginas, $pagina + $rango);

                if ($inicio > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?p=clientes_lista&pagina=1&buscar=<?= urlencode($busqueda) ?>&forecast=<?= urlencode($filtro_forecast) ?>&producto=<?= urlencode($filtro_producto) ?>&orden=<?= $orden ?>">1</a>
                    </li>
                    <?php if ($inicio > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                    <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?p=clientes_lista&pagina=<?= $i ?>&buscar=<?= urlencode($busqueda) ?>&forecast=<?= urlencode($filtro_forecast) ?>&producto=<?= urlencode($filtro_producto) ?>&orden=<?= $orden ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($fin < $total_paginas): ?>
                    <?php if ($fin < $total_paginas - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?p=clientes_lista&pagina=<?= $total_paginas ?>&buscar=<?= urlencode($busqueda) ?>&forecast=<?= urlencode($filtro_forecast) ?>&producto=<?= urlencode($filtro_producto) ?>&orden=<?= $orden ?>">
                            <?= $total_paginas ?>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Botón Siguiente -->
                <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=clientes_lista&pagina=<?= $pagina + 1 ?>&buscar=<?= urlencode($busqueda) ?>&forecast=<?= urlencode($filtro_forecast) ?>&producto=<?= urlencode($filtro_producto) ?>&orden=<?= $orden ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>

            </ul>

            <div class="float-left mt-2">
                <small class="text-muted">
                    Mostrando <?= $offset + 1 ?> - <?= min($offset + $registros_por_pagina, $total_registros) ?> 
                    de <?= $total_registros ?> clientes
                </small>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>
</section>

</div>