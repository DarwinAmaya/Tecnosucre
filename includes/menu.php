<?php
/* =====================================================
   MENU LATERAL - TECNOSUCRE
   Menú dinámico con control de acceso por rol
===================================================== */

$rol = $_SESSION['rol'] ?? '';
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';
$usuario_apellido = $_SESSION['apellido'] ?? '';

// Helpers para marcar menú activo
function isActive($route) {
    global $currentRoute;
    return (isset($currentRoute) && $currentRoute === $route) ? ' active' : '';
}

function isMenuOpen($routes) {
    global $currentRoute;
    return (isset($currentRoute) && in_array($currentRoute, $routes)) ? ' menu-open' : '';
}

function isMenuActive($routes) {
    global $currentRoute;
    return (isset($currentRoute) && in_array($currentRoute, $routes)) ? ' active' : '';
}

// Estadísticas para badges
require_once __DIR__ . '/../config/db.php';

$stats = [
    'clientes_total' => 0,
    'visitas_pendientes' => 0,
    'clientes_hoy' => 0
];

try {
    if ($rol === 'admin') {
        $stats['clientes_total'] = $conn->query("SELECT COUNT(*) as c FROM clientes")->fetch_assoc()['c'];
        $stats['visitas_pendientes'] = $conn->query("SELECT COUNT(*) as c FROM visitas WHERE estado IN ('Pendiente','Confirmada')")->fetch_assoc()['c'];
        $stats['clientes_hoy'] = $conn->query("SELECT COUNT(*) as c FROM clientes WHERE DATE(fecha_registro) = CURDATE()")->fetch_assoc()['c'];
    } else {
        $id_usuario = $_SESSION['id_usuario'];
        $stmt = $conn->prepare("SELECT COUNT(*) as c FROM clientes WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stats['clientes_total'] = $stmt->get_result()->fetch_assoc()['c'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as c FROM visitas WHERE id_instalador = ? AND estado IN ('Pendiente','Confirmada')");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stats['visitas_pendientes'] = $stmt->get_result()->fetch_assoc()['c'];
    }
} catch (Exception $e) {
    // Silenciar errores si hay problemas con BD
}
?>

<!-- ================= SIDEBAR ================= -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <!-- LOGO / MARCA -->
    <a href="index.php" class="brand-link text-center">
        <img src="app/assets/dist/img/HIK.png" 
             alt="TecnoSucre" 
             class="brand-image elevation-3"
             style="opacity: .8; max-height: 40px;">
        <span class="brand-text font-weight-bold">TECNOSUCRE</span>
    </a>

    <div class="sidebar">

        <!-- PANEL DE USUARIO -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <div class="rounded-circle bg-info d-flex align-items-center justify-content-center" 
                     style="width: 40px; height: 40px;">
                    <i class="fas fa-user text-white"></i>
                </div>
            </div>
            <div class="info">
                <a href="#" class="d-block">
                    <strong><?= htmlspecialchars($usuario_nombre) ?></strong>
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-circle text-success" style="font-size: 8px;"></i>
                        <?= $rol === 'admin' ? 'Administrador' : 'Instalador' ?>
                    </small>
                </a>
            </div>
        </div>

        <!-- MENÚ DE NAVEGACIÓN -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" 
                data-widget="treeview" 
                role="menu" 
                data-accordion="false">

                <!-- ========== DASHBOARD ========== -->
                <li class="nav-item">
                    <a href="index.php" class="nav-link<?= isActive('dashboard_' . $rol) ?>">
                        <i class="nav-icon fas fa-tachometer-alt text-info"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- SEPARADOR -->
                <li class="nav-header">GESTIÓN</li>

                <!-- ========== CLIENTES ========== -->
                <?php $routesClientes = ['clientes_nuevo', 'clientes_lista', 'clientes_detalle']; ?>
                <li class="nav-item has-treeview<?= isMenuOpen($routesClientes) ?>">
                    <a href="#" class="nav-link<?= isMenuActive($routesClientes) ?>">
                        <i class="nav-icon fas fa-users text-primary"></i>
                        <p>
                            Clientes
                            <?php if ($stats['clientes_total'] > 0): ?>
                                <span class="badge badge-info right"><?= $stats['clientes_total'] ?></span>
                            <?php endif; ?>
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="index.php?p=clientes_nuevo" class="nav-link<?= isActive('clientes_nuevo') ?>">
                                <i class="far fa-circle nav-icon text-success"></i>
                                <p>Registrar Cliente</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?p=clientes_lista" class="nav-link<?= isActive('clientes_lista') ?>">
                                <i class="far fa-circle nav-icon text-info"></i>
                                <p>Lista de Clientes</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ========== VISITAS ========== -->
                <?php $routesVisitas = ['visitas_programar', 'visitas_lista']; ?>
                <li class="nav-item has-treeview<?= isMenuOpen($routesVisitas) ?>">
                    <a href="#" class="nav-link<?= isMenuActive($routesVisitas) ?>">
                        <i class="nav-icon fas fa-calendar-alt text-warning"></i>
                        <p>
                            Visitas
                            <?php if ($stats['visitas_pendientes'] > 0): ?>
                                <span class="badge badge-warning right"><?= $stats['visitas_pendientes'] ?></span>
                            <?php endif; ?>
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="index.php?p=visitas_programar" class="nav-link<?= isActive('visitas_programar') ?>">
                                <i class="far fa-circle nav-icon text-success"></i>
                                <p>Programar Visita</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?p=visitas_lista" class="nav-link<?= isActive('visitas_lista') ?>">
                                <i class="far fa-circle nav-icon text-info"></i>
                                <p>Visitas Programadas</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ========== MÓDULOS ADMIN ========== -->
                <?php if ($rol === 'admin'): ?>

                <!-- SEPARADOR -->
                <li class="nav-header">ADMINISTRACIÓN</li>

                <!-- USUARIOS -->
                <?php $routesUsuarios = ['usuarios_nuevo', 'usuarios_lista']; ?>
                <li class="nav-item has-treeview<?= isMenuOpen($routesUsuarios) ?>">
                    <a href="#" class="nav-link<?= isMenuActive($routesUsuarios) ?>">
                        <i class="nav-icon fas fa-user-shield text-danger"></i>
                        <p>
                            Usuarios
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="index.php?p=usuarios_nuevo" class="nav-link<?= isActive('usuarios_nuevo') ?>">
                                <i class="far fa-circle nav-icon text-success"></i>
                                <p>Nuevo Usuario</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?p=usuarios_lista" class="nav-link<?= isActive('usuarios_lista') ?>">
                                <i class="far fa-circle nav-icon text-info"></i>
                                <p>Lista de Usuarios</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- CONFIGURACIONES -->
                <li class="nav-item">
                    <a href="index.php?p=configuraciones" class="nav-link<?= isActive('configuraciones') ?>">
                        <i class="nav-icon fas fa-cogs text-secondary"></i>
                        <p>Configuraciones</p>
                    </a>
                </li>

                <?php endif; ?>

                <!-- SEPARADOR -->
                <li class="nav-header">HERRAMIENTAS</li>

                <!-- REPORTES (ADMIN) -->
                <?php if ($rol === 'admin'): ?>
                <li class="nav-item">
                    <a href="index.php?p=configuraciones" class="nav-link">
                        <i class="nav-icon fas fa-file-excel text-success"></i>
                        <p>
                            Exportar Datos
                            <?php if ($stats['clientes_hoy'] > 0): ?>
                                <span class="badge badge-success right"><?= $stats['clientes_hoy'] ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                </li>
                <?php endif; ?>

                <!-- AYUDA -->
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="mostrarAyuda(); return false;">
                        <i class="nav-icon fas fa-question-circle text-info"></i>
                        <p>Ayuda</p>
                    </a>
                </li>

                <!-- SEPARADOR -->
                <li class="nav-header">SESIÓN</li>

                <!-- CERRAR SESIÓN -->
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Cerrar Sesión</p>
                    </a>
                </li>

            </ul>
        </nav>

        <!-- PIE DEL SIDEBAR -->
        <div class="mt-4 mb-3 px-3">
            <div class="card card-dark card-outline">
                <div class="card-body p-2 text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt"></i> 
                        Distribuidor Autorizado
                        <br>
                        <strong class="text-white">HIKVISION</strong>
                    </small>
                </div>
            </div>
        </div>

    </div>
</aside>

<!-- JavaScript para modal de ayuda -->
<script>
function mostrarAyuda() {
    const rol = '<?= $rol ?>';
    let mensaje = '';
    
    if (rol === 'admin') {
        mensaje = `
            <div class="text-left">
                <h5><i class="fas fa-info-circle text-info"></i> Guía Rápida - Administrador</h5>
                <hr>
                <ul>
                    <li><strong>Dashboard:</strong> Vista general del sistema</li>
                    <li><strong>Clientes:</strong> Gestión de clientes potenciales</li>
                    <li><strong>Visitas:</strong> Programación y seguimiento</li>
                    <li><strong>Usuarios:</strong> Crear y gestionar instaladores</li>
                    <li><strong>Configuraciones:</strong> Exportar datos y reportes</li>
                </ul>
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb"></i> Usa los filtros en cada lista para encontrar información específica.
                </div>
            </div>
        `;
    } else {
        mensaje = `
            <div class="text-left">
                <h5><i class="fas fa-info-circle text-info"></i> Guía Rápida - Instalador</h5>
                <hr>
                <ul>
                    <li><strong>Dashboard:</strong> Tus métricas personales</li>
                    <li><strong>Registrar Cliente:</strong> Completa los 9 pasos</li>
                    <li><strong>Lista de Clientes:</strong> Solo verás tus clientes</li>
                    <li><strong>Programar Visita:</strong> Agenda visitas técnicas</li>
                    <li><strong>Visitas Programadas:</strong> Revisa tu agenda</li>
                </ul>
                <div class="alert alert-warning">
                    <i class="fas fa-bell"></i> Revisa las notificaciones para visitas próximas.
                </div>
            </div>
        `;
    }
    
    Swal.fire({
        html: mensaje,
        icon: 'info',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#007bff',
        width: '600px'
    });
}
</script>

<!-- Estilos adicionales para el menú -->
<style>
.main-sidebar {
    background: linear-gradient(180deg, #1a1a1a 0%, #2c2c2c 100%);
}

.sidebar .nav-link {
    color: rgba(255,255,255,0.8);
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.1);
}

.sidebar .nav-link.active {
    background: rgba(0, 123, 255, 0.3);
    color: #fff;
    border-left: 3px solid #007bff;
}

.nav-treeview .nav-link {
    padding-left: 3rem;
}

.nav-header {
    color: rgba(255,255,255,0.5);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 1px;
    padding: 0.75rem 1rem;
    margin-top: 0.5rem;
}

.badge {
    font-size: 0.65rem;
    padding: 3px 6px;
}

.brand-link {
    transition: all 0.3s;
}

.brand-link:hover {
    opacity: 0.9;
}

.user-panel {
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

/* Animación al abrir submenús */
.nav-treeview {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>