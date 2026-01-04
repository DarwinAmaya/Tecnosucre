<?php
/* =====================================================
   ACTUALIZAR CLIENTE - TECNOSUCRE
   Procesa la edición de cliente
===================================================== */

session_start();
require_once __DIR__ . '/../config/db.php';

// Validar sesión
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['mensaje'] = "Sesión inválida";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.php?p=clientes_lista");
    exit;
}

// Obtener ID del cliente
$id_cliente = $_POST['id_cliente'] ?? 0;

if (!$id_cliente || !is_numeric($id_cliente)) {
    $_SESSION['mensaje'] = "ID de cliente inválido";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=clientes_lista");
    exit;
}

// ==================== VALIDAR PERMISOS ====================
if ($rol !== 'admin') {
    // Instalador solo puede editar sus propios clientes
    $check = $conn->prepare("SELECT id_usuario FROM clientes WHERE id_cliente = ?");
    $check->bind_param("i", $id_cliente);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['mensaje'] = "Cliente no encontrado";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: ../../index.php?p=clientes_lista");
        exit;
    }
    
    $cliente_owner = $result->fetch_assoc()['id_usuario'];
    
    if ($cliente_owner != $id_usuario) {
        $_SESSION['mensaje'] = "No tienes permisos para editar este cliente";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: ../../index.php?p=clientes_lista");
        exit;
    }
}

// ==================== VALIDAR CAMPOS ====================
$errores = [];

$nombre_apellido = trim($_POST['nombre_apellido'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');

if (empty($nombre_apellido)) {
    $errores[] = "El nombre y apellido es obligatorio";
}

if (empty($telefono)) {
    $errores[] = "El teléfono es obligatorio";
} elseif (!preg_match('/^(0412|0414|0416|0424|0426)[0-9]{7}$/', $telefono)) {
    $errores[] = "Teléfono venezolano inválido";
}

if (empty($ubicacion)) {
    $errores[] = "La ubicación es obligatoria";
}

// Validar checkboxes
$checkboxes = [
    'target' => 'Target',
    'productos' => 'Productos',
    'marcas' => 'Marcas',
    'diagnostico' => 'Diagnóstico',
    'forecast' => 'Forecast Pipeline'
];

foreach ($checkboxes as $campo => $nombre) {
    if (empty($_POST[$campo]) || !is_array($_POST[$campo])) {
        $errores[] = "Debe seleccionar al menos una opción en {$nombre}";
    }
}

// Si hay errores, retornar
if (!empty($errores)) {
    $_SESSION['mensaje'] = "Errores de validación:<br>• " . implode("<br>• ", $errores);
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=clientes_editar&id={$id_cliente}");
    exit;
}

// ==================== VALIDAR TELÉFONO DUPLICADO ====================
$check_tel = $conn->prepare("SELECT id_cliente, nombre_apellido FROM clientes WHERE telefono = ? AND id_cliente != ?");
$check_tel->bind_param("si", $telefono, $id_cliente);
$check_tel->execute();
$res_tel = $check_tel->get_result();

if ($res_tel->num_rows > 0) {
    $duplicado = $res_tel->fetch_assoc();
    $_SESSION['mensaje'] = "El teléfono ya está registrado en otro cliente: " . 
                           htmlspecialchars($duplicado['nombre_apellido']);
    $_SESSION['tipo_mensaje'] = "warning";
    header("Location: ../../index.php?p=clientes_editar&id={$id_cliente}");
    exit;
}

// ==================== PREPARAR DATOS ====================
$target_json = json_encode($_POST['target'], JSON_UNESCAPED_UNICODE);
$productos_json = json_encode($_POST['productos'], JSON_UNESCAPED_UNICODE);
$marcas_json = json_encode($_POST['marcas'], JSON_UNESCAPED_UNICODE);
$diagnostico_json = json_encode($_POST['diagnostico'], JSON_UNESCAPED_UNICODE);
$forecast_json = json_encode($_POST['forecast'], JSON_UNESCAPED_UNICODE);

$marca_otra = !empty($_POST['marca_otra']) ? trim($_POST['marca_otra']) : null;
$fecha_desde = !empty($_POST['fecha_desde']) ? $_POST['fecha_desde'] : null;
$fecha_hasta = !empty($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : null;

// ==================== ACTUALIZAR EN BASE DE DATOS ====================
$sql = "UPDATE clientes SET
    nombre_apellido = ?,
    telefono = ?,
    ubicacion = ?,
    target = ?,
    productos = ?,
    marcas = ?,
    diagnostico = ?,
    forecast_pipeline = ?,
    marca_otra = ?,
    fecha_visita_desde = ?,
    fecha_visita_hasta = ?,
    fecha_actualizacion = NOW()
WHERE id_cliente = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['mensaje'] = "Error al preparar consulta: " . $conn->error;
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=clientes_editar&id={$id_cliente}");
    exit;
}

$stmt->bind_param(
    "sssssssssssi",
    $nombre_apellido,
    $telefono,
    $ubicacion,
    $target_json,
    $productos_json,
    $marcas_json,
    $diagnostico_json,
    $forecast_json,
    $marca_otra,
    $fecha_desde,
    $fecha_hasta,
    $id_cliente
);

// ==================== EJECUTAR Y VALIDAR ====================
if ($stmt->execute()) {
    
    // Log de auditoría
    $log_sql = "INSERT INTO logs_actividad (accion, detalles, tabla_afectada, id_registro, id_usuario) 
                VALUES ('CLIENTE_ACTUALIZADO', ?, 'clientes', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $detalles = "Cliente actualizado: {$nombre_apellido} (ID: {$id_cliente})";
    $log_stmt->bind_param("sii", $detalles, $id_cliente, $id_usuario);
    @$log_stmt->execute();
    
    $_SESSION['mensaje'] = "¡Cliente actualizado exitosamente!<br><strong>{$nombre_apellido}</strong> ha sido modificado.";
    $_SESSION['tipo_mensaje'] = "success";
    
    // Redirigir al detalle del cliente
    header("Location: ../../index.php?p=clientes_detalle&id={$id_cliente}");
    
} else {
    $_SESSION['mensaje'] = "Error al actualizar el cliente: " . $stmt->error;
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=clientes_editar&id={$id_cliente}");
}

exit;