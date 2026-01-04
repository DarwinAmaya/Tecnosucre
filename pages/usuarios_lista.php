<?php
/* =====================================================
   LISTA DE USUARIOS DEL SISTEMA - SOLO ADMIN
===================================================== */

if ($_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Filtros
$filtro_rol = $_GET['rol'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$busqueda = $_GET['buscar'] ?? '';

// Construcción de consulta
$where_conditions = [];
$params = [];
$types = '';

if (!empty($filtro_rol)) {
    $where_conditions[] = "rol = ?";
    $params[] = $filtro_rol;
    $types .= 's';
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "estado = ?";
    $params[] = $filtro_estado;
    $types .= 'i';
}

if (!empty($busqueda)) {
    $where_conditions[] = "(nombre LIKE ? OR apellido LIKE ? OR usuario LIKE ? OR cedula LIKE ?)";
    $busqueda_param = "%{$busqueda}%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= 'ssss';
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "
    SELECT 
        u.*,
        COUNT(DISTINCT c.id_cliente) as total_clientes,
        COUNT(DISTINCT CASE WHEN DATE(c.fecha_registro) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN c.id_cliente END) as clientes_mes
    FROM usuarios u
    LEFT JOIN clientes c ON c.id_usuario = u.id_usuario
    {$where_sql}
    GROUP BY u.id_usuario
    ORDER BY u.fecha_creacion DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-users-cog"></i> Usuarios del Sistema</h1>
            </div>
            <div class="col-sm-6">
                <a href="index.php?p=usuarios_nuevo" class="btn btn-success float-right">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
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

    <!-- ESTADÍSTICAS -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <?php
                    $total = $conn->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'];
                    ?>
                    <h3><?= $total ?></h3>
                    <p>Total Usuarios</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <?php
                    $activos = $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE estado = 1")->fetch_assoc()['c'];
                    ?>
                    <h3><?= $activos ?></h3>
                    <p>Usuarios Activos</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <?php
                    $instaladores = $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE rol = 'instalador'")->fetch_assoc()['c'];
                    ?>
                    <h3><?= $instaladores ?></h3>
                    <p>Instaladores</p>
                </div>
                <div class="icon"><i class="fas fa-user-hard-hat"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <?php
                    $admins = $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE rol = 'admin'")->fetch_assoc()['c'];
                    ?>
                    <h3><?= $admins ?></h3>
                    <p>Administradores</p>
                </div>
                <div class="icon"><i class="fas fa-user-shield"></i></div>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <input type="hidden" name="p" value="usuarios_lista">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-search"></i> Buscar</label>
                            <input type="text" 
                                   name="buscar" 
                                   class="form-control" 
                                   placeholder="Nombre, usuario o cédula"
                                   value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-user-tag"></i> Rol</label>
                            <select name="rol" class="form-control">
                                <option value="">Todos los roles</option>
                                <option value="admin" <?= $filtro_rol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                <option value="instalador" <?= $filtro_rol === 'instalador' ? 'selected' : '' ?>>Instalador</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Estado</label>
                            <select name="estado" class="form-control">
                                <option value="">Todos los estados</option>
                                <option value="1" <?= $filtro_estado === '1' ? 'selected' : '' ?>>Activos</option>
                                <option value="0" <?= $filtro_estado === '0' ? 'selected' : '' ?>>Inactivos</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <a href="index.php?p=usuarios_lista" class="btn btn-secondary btn-sm">
                            <i class="fas fa-eraser"></i> Limpiar Filtros
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA DE USUARIOS -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> 
                Listado de Usuarios 
                <span class="badge badge-primary"><?= $result->num_rows ?></span>
            </h3>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th width="50">#</th>
                        <th>Usuario</th>
                        <th>Cédula</th>
                        <th>Nombre Completo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Clientes</th>
                        <th>Último Acceso</th>
                        <th>Fecha Creación</th>
                        <th width="120">Acciones</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ($result->num_rows > 0): ?>
                    <?php 
                    $contador = 1;
                    while ($usuario = $result->fetch_assoc()): 
                    ?>

                    <tr>
                        <td><?= $contador++ ?></td>
                        
                        <td>
                            <strong><?= htmlspecialchars($usuario['usuario']) ?></strong>
                            <?php if ($usuario['id_usuario'] == $_SESSION['id_usuario']): ?>
                                <span class="badge badge-info badge-sm">Tú</span>
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($usuario['cedula']) ?></td>

                        <td><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></td>

                        <td>
                            <?php if ($usuario['rol'] === 'admin'): ?>
                                <span class="badge badge-danger">
                                    <i class="fas fa-user-shield"></i> Administrador
                                </span>
                            <?php else: ?>
                                <span class="badge badge-primary">
                                    <i class="fas fa-user-hard-hat"></i> Instalador
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($usuario['estado'] == 1): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Activo
                                </span>
                            <?php else: ?>
                                <span class="badge badge-danger">
                                    <i class="fas fa-times-circle"></i> Inactivo
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <?php if ($usuario['rol'] === 'instalador'): ?>
                                <span class="badge badge-info badge-lg">
                                    <?= $usuario['total_clientes'] ?>
                                </span>
                                <?php if ($usuario['clientes_mes'] > 0): ?>
                                    <br><small class="text-muted">
                                        +<?= $usuario['clientes_mes'] ?> este mes
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($usuario['ultimo_acceso']): ?>
                                <small>
                                    <?= date('d/m/Y', strtotime($usuario['ultimo_acceso'])) ?>
                                    <br>
                                    <span class="text-muted"><?= date('H:i', strtotime($usuario['ultimo_acceso'])) ?></span>
                                </small>
                            <?php else: ?>
                                <span class="text-muted">Nunca</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <small>
                                <?= date('d/m/Y', strtotime($usuario['fecha_creacion'])) ?>
                            </small>
                        </td>

                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if ($usuario['rol'] === 'instalador'): ?>
                                <a href="index.php?p=clientes_lista&instalador=<?= $usuario['id_usuario'] ?>" 
                                   class="btn btn-info"
                                   title="Ver clientes">
                                    <i class="fas fa-users"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($usuario['id_usuario'] != $_SESSION['id_usuario']): ?>
                                <button class="btn btn-warning" 
                                        onclick="toggleEstado(<?= $usuario['id_usuario'] ?>, <?= $usuario['estado'] ?>)"
                                        title="Cambiar estado">
                                    <i class="fas fa-toggle-<?= $usuario['estado'] ? 'on' : 'off' ?>"></i>
                                </button>
                                
                                <button class="btn btn-danger" 
                                        onclick="confirmarEliminar(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars($usuario['usuario']) ?>')"
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php else: ?>
                                <span class="badge badge-secondary">Mi usuario</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                            <br>
                            <h5 class="text-muted">No se encontraron usuarios</h5>
                            <?php if (!empty($busqueda) || !empty($filtro_rol) || !empty($filtro_estado)): ?>
                                <p class="text-muted">Intenta cambiar los filtros de búsqueda</p>
                            <?php endif; ?>
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

<script>
function toggleEstado(id, estadoActual) {
    const nuevoEstado = estadoActual ? 0 : 1;
    const texto = nuevoEstado ? 'activar' : 'desactivar';
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas ${texto} este usuario?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, ' + texto,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `app/actions/usuarios_toggle_estado.php?id=${id}&estado=${nuevoEstado}`;
        }
    });
}

function confirmarEliminar(id, usuario) {
    Swal.fire({
        title: '¿Estás seguro?',
        html: `¿Deseas eliminar al usuario <strong>${usuario}</strong>?<br><small class="text-danger">Esta acción no se puede deshacer</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `app/actions/usuarios_eliminar.php?id=${id}`;
        }
    });
}
</script>

<style>
.badge-lg {
    font-size: 1rem;
    padding: 4px 8px;
}

.table td {
    vertical-align: middle;
}
</style>