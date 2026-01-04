<?php
session_start();

/* =====================================================
   1. VALIDACIÓN DE SESIÓN
===================================================== */
if (!isset($_SESSION['id_usuario'], $_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];

/* =====================================================
   2. CONSTANTES DE RUTAS
===================================================== */
define('APP_DIR', __DIR__ . '/app/');
define('INCLUDES_DIR', APP_DIR . 'includes/');
define('PAGES_DIR', APP_DIR . 'pages/');

/* =====================================================
   3. RUTAS VÁLIDAS POR ROL
===================================================== */
$valid_pages = [

    /* DASHBOARDS */
    'dashboard_admin'      => ['title' => 'Dashboard Administrador', 'roles' => ['admin']],
    'dashboard_instalador' => ['title' => 'Dashboard Instalador', 'roles' => ['instalador']],

    /* CLIENTES */
    'clientes_nuevo'   => ['title' => 'Registrar Cliente', 'roles' => ['admin','instalador']],
    'clientes_lista'   => ['title' => 'Lista de Clientes', 'roles' => ['admin','instalador']],
    'clientes_detalle' => ['title' => 'Detalle del Cliente', 'roles' => ['admin','instalador']],
    'clientes_editar'  => ['title' => 'Editar Cliente', 'roles' => ['admin','instalador']], // NUEVA RUTA

    /* VISITAS / PIPELINE */
    'visitas_programar' => ['title' => 'Programar Visita', 'roles' => ['admin','instalador']],
    'visitas_lista'     => ['title' => 'Visitas Programadas', 'roles' => ['admin','instalador']],

    /* USUARIOS (ADMIN) */
    'usuarios_nuevo' => ['title' => 'Nuevo Usuario', 'roles' => ['admin']],
    'usuarios_lista' => ['title' => 'Usuarios del Sistema', 'roles' => ['admin']],

    /* CONFIGURACIÓN */
    'configuraciones' => ['title' => 'Configuraciones', 'roles' => ['admin']]
];

/* =====================================================
   4. RUTA POR DEFECTO SEGÚN ROL
===================================================== */
if (!isset($_GET['p'])) {
    $route = ($rol === 'admin') ? 'dashboard_admin' : 'dashboard_instalador';
} else {
    $route = $_GET['p'];
}

/* =====================================================
   5. VALIDACIÓN DE RUTA Y PERMISOS
===================================================== */
if (
    !isset($valid_pages[$route]) ||
    !in_array($rol, $valid_pages[$route]['roles'])
) {
    // redirigir a dashboard según rol
    header("Location: index.php");
    exit;
}

$currentRoute = $route;
$currentTitle = $valid_pages[$route]['title'];

/* =====================================================
   6. RENDER DE ESTRUCTURA
===================================================== */
require_once INCLUDES_DIR . 'header.php';
require_once INCLUDES_DIR . 'menu.php';

$pageFile = PAGES_DIR . "{$route}.php";

if (file_exists($pageFile)) {
    require $pageFile;
} else {
    echo "
    <div class='content-wrapper'>
        <section class='content p-4'>
            <div class='alert alert-danger'>
                Página no encontrada: <b>{$route}.php</b>
            </div>
        </section>
    </div>";
}

require_once INCLUDES_DIR . 'footer.php';