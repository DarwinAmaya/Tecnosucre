<?php
/* =====================================================
   GUARDAR CLIENTE - TECNOSUCRE
   Script mejorado con validaciones y mensajes
===================================================== */

session_start();
require_once __DIR__ . '/../config/db.php';

// ==================== VALIDAR SESIÓN ====================
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['mensaje'] = "Sesión inválida. Por favor inicie sesión nuevamente.";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$nombre_usuario = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];

// ==================== VALIDAR MÉTODO ====================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.php?p=clientes_nuevo");
    exit;
}

// ==================== FUNCIÓN DE VALIDACIÓN ====================
function validarCampo($nombre, $valor, $requerido = true) {
    if ($requerido && empty(trim($valor))) {
        return "El campo {$nombre} es obligatorio.";
    }
    return null;
}

// ==================== VALIDAR CAMPOS DE TEXTO ====================
$errores = [];

$nombre_apellido = trim($_POST['nombre_apellido'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');

if ($error = validarCampo('Nombre y Apellido', $nombre_apellido)) {
    $errores[] = $error;
}

if ($error = validarCampo('Teléfono', $telefono)) {
    $errores[] = $error;
}

if ($error = validarCampo('Ubicación', $ubicacion)) {
    $errores[] = $error;
}

// ==================== VALIDAR TELÉFONO VENEZOLANO ====================
if (!empty($telefono) && !preg_match('/^(0412|0414|0416|0424|0426)[0-9]{7}$/', $telefono)) {
    $errores[] = "El teléfono debe ser venezolano válido (0412, 0414, 0416, 0424, 0426 + 7 dígitos).";
}

// ==================== VALIDAR CHECKBOXES ====================
$checkboxes = [
    'target' => 'Target',
    'productos' => 'Productos',
    'marcas' => 'Marcas',
    'diagnostico' => 'Diagnóstico',
    'forecast' => 'Forecast Pipeline'
];

foreach ($checkboxes as $campo => $nombre) {
    if (empty($_POST[$campo]) || !is_array($_POST[$campo])) {
        $errores[] = "Debe seleccionar al menos una opción en {$nombre}.";
    }
}

// ==================== SI HAY ERRORES, RETORNAR ====================
if (!empty($errores)) {
    $_SESSION['mensaje'] = "Errores de validación:<br>• " . implode("<br>• ", $errores);
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=clientes_nuevo");
    exit;
}

// ==================== VALIDAR DUPLICADOS ====================
$check = $conn->prepare("SELECT id_cliente, nombre_apellido FROM clientes WHERE telefono = ? LIMIT 1");
$check->bind_param("s", $telefono);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $cliente_existente = $res->fetch_assoc();
    $_SESSION['mensaje'] = "Ya existe un cliente registrado con este teléfono: " . 
                           htmlspecialchars($cliente_existente['nombre_apellido']);
    $_SESSION['tipo_mensaje'] = "warning";
    header("Location: ../../index.php?p=clientes_lista");
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

// ==================== INSERTAR EN BASE DE DATOS ====================
$sql = "INSERT INTO clientes (
    nombre_apellido,
    telefono,
    ubicacion,
    target,
    productos,
    marcas,
    diagnostico,
    forecast_pipeline,
    marca_otra,
    fecha_visita_desde,
    fecha_visita_hasta,
    id_usuario,
    fecha_registro
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['mensaje'] = "Error al preparar consulta: " . $conn->error;
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=clientes_nuevo");
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
    $id_usuario
);

// ==================== EJECUTAR Y VALIDAR ====================
if ($stmt->execute()) {
    $id_cliente_nuevo = $stmt->insert_id;
    
    // Log de auditoría (opcional)
    $log_sql = "INSERT INTO logs_actividad (accion, detalles, id_usuario) 
                VALUES ('CLIENTE_CREADO', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $detalles = "Cliente: {$nombre_apellido} (ID: {$id_cliente_nuevo})";
    $log_stmt->bind_param("si", $detalles, $id_usuario);
    @$log_stmt->execute(); // @ para ignorar error si tabla no existe
    
    $_SESSION['mensaje'] = "¡Cliente registrado exitosamente! 
                           <br><strong>{$nombre_apellido}</strong> ha sido agregado al sistema.";
    $_SESSION['tipo_mensaje'] = "success";
    
    // Redirigir al detalle del cliente
    header("Location: ../../index.php?p=clientes_detalle&id={$id_cliente_nuevo}");
    
} else {
    $_SESSION['mensaje'] = "Error al guardar el cliente: " . $stmt->error;
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=clientes_nuevo");
}

exit;