<?php
/* =====================================================
   HEADER - TECNOSUCRE
   Header con notificaciones y perfil de usuario
===================================================== */

// Seguridad: Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

// Variables globales
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';
$usuario_apellido = $_SESSION['apellido'] ?? '';
$usuario_completo = trim($usuario_nombre . ' ' . $usuario_apellido);
$rol = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id_usuario'];
$currentTitle = $currentTitle ?? 'Dashboard';

// Obtener notificaciones (visitas próximas)
require_once __DIR__ . '/../config/db.php';

$notificaciones = [];
$count_notificaciones = 0;

if ($rol === 'instalador') {
    // Visitas próximas del instalador (próximos 3 días)
    $sql = "SELECT v.*, c.nombre_apellido 
            FROM visitas v 
            INNER JOIN clientes c ON c.id_cliente = v.id_cliente
            WHERE v.id_instalador = ? 
            AND v.estado IN ('Pendiente', 'Confirmada')
            AND v.fecha_visita BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
            ORDER BY v.fecha_visita, v.hora_visita
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = $row;
    }
    $count_notificaciones = count($notificaciones);
} else {
    // Admin: Visitas pendientes del día + clientes nuevos
    $sql = "SELECT COUNT(*) as count FROM visitas 
            WHERE estado = 'Pendiente' 
            AND DATE(fecha_visita) = CURDATE()";
    $count_visitas = $conn->query($sql)->fetch_assoc()['count'];
    
    $sql = "SELECT COUNT(*) as count FROM clientes 
            WHERE DATE(fecha_registro) = CURDATE()";
    $count_clientes = $conn->query($sql)->fetch_assoc()['count'];
    
    $count_notificaciones = $count_visitas + $count_clientes;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= htmlspecialchars($currentTitle) ?> | TecnoSucre CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema CRM TecnoSucre - Distribuidor Autorizado Hikvision">
    <meta name="author" content="TecnoSucre">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="app/assets/dist/img/favicon.png">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="app/assets/plugins/fontawesome-free/css/all.min.css">

    <!-- AdminLTE -->
    <link rel="stylesheet" href="app/assets/dist/css/adminlte.min.css">

    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback" rel="stylesheet">

    <!-- Estilos personalizados -->
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #0066cc;
            --accent-color: #ff6600;
        }

        .main-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-bottom: 3px solid var(--accent-color);
        }

        .main-header .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
        }

        .main-header .navbar-nav .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }

        .dropdown-menu {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .navbar-badge {
            font-size: 0.6rem;
            padding: 2px 5px;
            position: absolute;
            right: 5px;
            top: 9px;
        }

        .user-panel {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .brand-link {
            background: var(--primary-color);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .brand-text {
            color: #fff !important;
        }

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #f4f4f4;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        @media print {
            .main-sidebar,
            .main-header,
            .content-header {
                display: none !important;
            }
            .content-wrapper {
                margin: 0 !important;
            }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

<!-- ================= NAVBAR SUPERIOR ================= -->
<nav class="main-header navbar navbar-expand navbar-dark">

    <!-- IZQUIERDA: Botón menú + Logo en móvil -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" title="Menú">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>
    </ul>

    <!-- CENTRO: Título de página (solo desktop) -->
    <div class="navbar-text ml-3 d-none d-md-block text-white">
        <strong><?= htmlspecialchars($currentTitle) ?></strong>
    </div>

    <!-- DERECHA: Notificaciones + Usuario -->
    <ul class="navbar-nav ml-auto">

        <!-- NOTIFICACIONES -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" title="Notificaciones">
                <i class="far fa-bell"></i>
                <?php if ($count_notificaciones > 0): ?>
                    <span class="badge badge-warning navbar-badge"><?= $count_notificaciones ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">
                    <?= $count_notificaciones ?> Notificación(es)
                </span>
                
                <?php if ($rol === 'instalador' && !empty($notificaciones)): ?>
                    <?php foreach ($notificaciones as $notif): ?>
                        <div class="dropdown-divider"></div>
                        <a href="index.php?p=visitas_lista" class="dropdown-item notification-item">
                            <div class="d-flex align-items-center">
                                <div class="notification-icon bg-info">
                                    <i class="fas fa-calendar text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars($notif['nombre_apellido']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?= date('d/m/Y', strtotime($notif['fecha_visita'])) ?> 
                                        <?= date('H:i', strtotime($notif['hora_visita'])) ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php elseif ($rol === 'admin'): ?>
                    <div class="dropdown-divider"></div>
                    <a href="index.php?p=visitas_lista" class="dropdown-item">
                        <i class="fas fa-calendar mr-2"></i> Visitas hoy: <?= $count_visitas ?? 0 ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="index.php?p=clientes_lista" class="dropdown-item">
                        <i class="fas fa-user-plus mr-2"></i> Clientes nuevos: <?= $count_clientes ?? 0 ?>
                    </a>
                <?php else: ?>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item text-center text-muted">
                        No hay notificaciones
                    </a>
                <?php endif; ?>
                
                <div class="dropdown-divider"></div>
                <a href="index.php?p=visitas_lista" class="dropdown-item dropdown-footer">
                    Ver todas las visitas
                </a>
            </div>
        </li>

        <!-- USUARIO -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" title="Mi Perfil">
                <i class="far fa-user-circle"></i>
                <span class="d-none d-md-inline ml-1"><?= htmlspecialchars($usuario_nombre) ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-item dropdown-header bg-light">
                    <strong><?= htmlspecialchars($usuario_completo) ?></strong>
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-user-tag"></i> 
                        <?= $rol === 'admin' ? 'Administrador' : 'Instalador' ?>
                    </small>
                </div>
                <div class="dropdown-divider"></div>
                <a href="index.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
                <?php if ($rol === 'admin'): ?>
                <a href="index.php?p=configuraciones" class="dropdown-item">
                    <i class="fas fa-cogs mr-2"></i> Configuraciones
                </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                </a>
            </div>
        </li>

        <!-- PANTALLA COMPLETA -->
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button" title="Pantalla Completa">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>

    </ul>
</nav>