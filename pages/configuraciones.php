<?php
/* =====================================================
   CONFIGURACIONES Y EXPORTACIÓN - SOLO ADMIN
===================================================== */

if ($_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Obtener lista de instaladores para el filtro
$instaladores = $conn->query("
    SELECT id_usuario, nombre, apellido, cedula 
    FROM usuarios 
    WHERE rol = 'instalador' AND estado = 1
    ORDER BY nombre
");

// Estadísticas generales
$stats = [
    'total_clientes' => $conn->query("SELECT COUNT(*) as c FROM clientes")->fetch_assoc()['c'],
    'total_instaladores' => $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE rol='instalador'")->fetch_assoc()['c'],
    'clientes_hoy' => $conn->query("SELECT COUNT(*) as c FROM clientes WHERE DATE(fecha_registro) = CURDATE()")->fetch_assoc()['c'],
    'clientes_mes' => $conn->query("SELECT COUNT(*) as c FROM clientes WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())")->fetch_assoc()['c']
];
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <h1><i class="fas fa-cogs"></i> Configuraciones y Exportación</h1>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <!-- ESTADÍSTICAS RÁPIDAS -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?= $stats['total_clientes'] ?></h3>
                    <p>Total Clientes</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3><?= $stats['total_instaladores'] ?></h3>
                    <p>Instaladores Activos</p>
                </div>
                <div class="icon"><i class="fas fa-user-hard-hat"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3><?= $stats['clientes_hoy'] ?></h3>
                    <p>Registros Hoy</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3><?= $stats['clientes_mes'] ?></h3>
                    <p>Este Mes</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
            </div>
        </div>
    </div>

    <!-- EXPORTACIÓN DE DATOS -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-excel"></i> Exportar Datos a Excel
            </h3>
        </div>
        <div class="card-body">
            <form method="GET" action="app/actions/exportar_excel.php" target="_blank">
                
                <div class="row">
                    <!-- Filtro por Instalador -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Filtrar por Instalador</label>
                            <select name="instalador" class="form-control">
                                <option value="">Todos los instaladores</option>
                                <?php while ($inst = $instaladores->fetch_assoc()): ?>
                                    <option value="<?= $inst['id_usuario'] ?>">
                                        <?= htmlspecialchars($inst['nombre'] . ' ' . $inst['apellido']) ?>
                                        (<?= htmlspecialchars($inst['cedula']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Filtro por Target -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-bullseye"></i> Filtrar por Target</label>
                            <select name="target" class="form-control">
                                <option value="">Todos los targets</option>
                                <option value="Residencial">Residencial</option>
                                <option value="Comercial">Comercial</option>
                                <option value="Empresarial">Empresarial</option>
                                <option value="Gubernamental">Gubernamental</option>
                                <option value="Industrial">Industrial</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filtro por Producto -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-box"></i> Filtrar por Producto</label>
                            <select name="producto" class="form-control">
                                <option value="">Todos los productos</option>
                                <option value="CCTV">CCTV</option>
                                <option value="Alarmas">Alarmas</option>
                                <option value="Video Portero">Video Portero</option>
                                <option value="Control de Acceso">Control de Acceso</option>
                                <option value="Display">Display</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filtro por Forecast -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-chart-line"></i> Filtrar por Forecast Pipeline</label>
                            <select name="forecast" class="form-control">
                                <option value="">Todos los estados</option>
                                <option value="Curiosidad">Curiosidad</option>
                                <option value="Necesidad">Necesidad</option>
                                <option value="Interesado">Interesado</option>
                                <option value="Muy Interesado">Muy Interesado</option>
                                <option value="Declinado">Declinado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Rango de Fechas -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Fecha Desde</label>
                            <input type="date" name="fecha_desde" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Fecha Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Botones de Exportación -->
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </button>
                    <button type="reset" class="btn btn-secondary btn-lg">
                        <i class="fas fa-eraser"></i> Limpiar Filtros
                    </button>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nota:</strong> Si no instala PhpSpreadsheet, el sistema exportará en formato CSV compatible con Excel.
                    Para instalar: <code>composer require phpoffice/phpspreadsheet</code>
                </div>

            </form>
        </div>
    </div>

    <!-- REPORTES RÁPIDOS -->
    <div class="row">
        <div class="col-md-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Top 5 Instaladores</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Instalador</th>
                                <th>Clientes</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $top = $conn->query("
                            SELECT u.nombre, u.apellido, COUNT(c.id_cliente) as total
                            FROM usuarios u
                            LEFT JOIN clientes c ON c.id_usuario = u.id_usuario
                            WHERE u.rol = 'instalador'
                            GROUP BY u.id_usuario
                            ORDER BY total DESC
                            LIMIT 5
                        ");
                        while ($t = $top->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($t['nombre'] . ' ' . $t['apellido']) ?></td>
                                <td><span class="badge badge-primary"><?= $t['total'] ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie"></i> Distribución por Forecast</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $forecasts = ['Curiosidad', 'Necesidad', 'Interesado', 'Muy Interesado', 'Declinado'];
                        foreach ($forecasts as $f):
                            $sql = "SELECT COUNT(*) as c FROM clientes WHERE JSON_CONTAINS(forecast_pipeline, ?)";
                            $stmt = $conn->prepare($sql);
                            $val = json_encode($f);
                            $stmt->bind_param("s", $val);
                            $stmt->execute();
                            $count = $stmt->get_result()->fetch_assoc()['c'];
                        ?>
                            <tr>
                                <td><?= $f ?></td>
                                <td><span class="badge badge-success"><?= $count ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ACCIONES ADICIONALES -->
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tools"></i> Mantenimiento del Sistema</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <a href="app/actions/backup_db.php" class="btn btn-block btn-warning" target="_blank">
                        <i class="fas fa-database"></i> Respaldar Base de Datos
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="index.php?p=usuarios_lista" class="btn btn-block btn-info">
                        <i class="fas fa-users-cog"></i> Gestionar Usuarios
                    </a>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-block btn-danger" onclick="if(confirm('¿Limpiar logs del sistema?')) window.location='app/actions/limpiar_logs.php'">
                        <i class="fas fa-trash"></i> Limpiar Logs
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
</section>

</div>