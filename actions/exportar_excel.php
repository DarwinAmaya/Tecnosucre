<?php
/* =====================================================
   EXPORTAR CLIENTES A EXCEL/CSV - TECNOSUCRE
   Genera archivo CSV compatible con Excel
===================================================== */

session_start();
require_once __DIR__ . '/../config/db.php';

// Solo admin puede exportar
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso no autorizado");
}

// ==================== OBTENER FILTROS ====================
$filtros = [];
$where_conditions = [];
$params = [];
$types = '';

// Filtro por instalador
if (!empty($_GET['instalador'])) {
    $where_conditions[] = "c.id_usuario = ?";
    $params[] = $_GET['instalador'];
    $types .= 'i';
}

// Filtro por target
if (!empty($_GET['target'])) {
    $where_conditions[] = "JSON_CONTAINS(c.target, ?)";
    $params[] = json_encode($_GET['target']);
    $types .= 's';
}

// Filtro por producto
if (!empty($_GET['producto'])) {
    $where_conditions[] = "JSON_CONTAINS(c.productos, ?)";
    $params[] = json_encode($_GET['producto']);
    $types .= 's';
}

// Filtro por forecast
if (!empty($_GET['forecast'])) {
    $where_conditions[] = "JSON_CONTAINS(c.forecast_pipeline, ?)";
    $params[] = json_encode($_GET['forecast']);
    $types .= 's';
}

// Filtro por rango de fechas
if (!empty($_GET['fecha_desde'])) {
    $where_conditions[] = "DATE(c.fecha_registro) >= ?";
    $params[] = $_GET['fecha_desde'];
    $types .= 's';
}

if (!empty($_GET['fecha_hasta'])) {
    $where_conditions[] = "DATE(c.fecha_registro) <= ?";
    $params[] = $_GET['fecha_hasta'];
    $types .= 's';
}

// Construir WHERE
$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// ==================== CONSULTA PRINCIPAL ====================
$sql = "
    SELECT 
        c.id_cliente,
        c.nombre_apellido,
        c.telefono,
        c.ubicacion,
        c.target,
        c.productos,
        c.marcas,
        c.marca_otra,
        c.diagnostico,
        c.forecast_pipeline,
        c.fecha_visita_desde,
        c.fecha_visita_hasta,
        c.fecha_registro,
        u.nombre AS instalador_nombre,
        u.apellido AS instalador_apellido,
        u.cedula AS instalador_cedula,
        u.usuario AS instalador_usuario
    FROM clientes c
    INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
    {$where_sql}
    ORDER BY c.fecha_registro DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// ==================== VERIFICAR SI HAY DATOS ====================
if ($result->num_rows === 0) {
    $_SESSION['mensaje'] = "No hay datos para exportar con los filtros seleccionados";
    $_SESSION['tipo_mensaje'] = "warning";
    header("Location: ../../index.php?p=configuraciones");
    exit;
}

// ==================== CONFIGURAR HEADERS PARA DESCARGA ====================
$filename = 'clientes_tecnosucre_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Abrir salida
$output = fopen('php://output', 'w');

// BOM para UTF-8 (para que Excel reconozca caracteres especiales)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// ==================== ENCABEZADOS ====================
$headers = [
    'ID',
    'Nombre y Apellido',
    'Teléfono',
    'Ubicación',
    'Target',
    'Productos',
    'Marcas',
    'Otra Marca',
    'Diagnóstico',
    'Forecast Pipeline',
    'Fecha Visita Desde',
    'Fecha Visita Hasta',
    'Instalador',
    'Cédula Instalador',
    'Usuario Instalador',
    'Fecha de Registro'
];

fputcsv($output, $headers, ';'); // Usamos ; para mejor compatibilidad con Excel

// ==================== DATOS ====================
while ($row = $result->fetch_assoc()) {
    
    // Procesar arrays JSON
    $target = json_decode($row['target'], true) ?? [];
    $productos = json_decode($row['productos'], true) ?? [];
    $marcas = json_decode($row['marcas'], true) ?? [];
    $diagnostico = json_decode($row['diagnostico'], true) ?? [];
    $forecast = json_decode($row['forecast_pipeline'], true) ?? [];
    
    $data = [
        $row['id_cliente'],
        $row['nombre_apellido'],
        $row['telefono'],
        $row['ubicacion'],
        implode(', ', $target),
        implode(', ', $productos),
        implode(', ', $marcas),
        $row['marca_otra'] ?? '',
        implode(', ', $diagnostico),
        implode(', ', $forecast),
        $row['fecha_visita_desde'] ? date('d/m/Y', strtotime($row['fecha_visita_desde'])) : '',
        $row['fecha_visita_hasta'] ? date('d/m/Y', strtotime($row['fecha_visita_hasta'])) : '',
        $row['instalador_nombre'] . ' ' . $row['instalador_apellido'],
        $row['instalador_cedula'],
        $row['instalador_usuario'],
        date('d/m/Y H:i', strtotime($row['fecha_registro']))
    ];
    
    fputcsv($output, $data, ';');
}

// ==================== CERRAR ARCHIVO ====================
fclose($output);

// Registrar en log de auditoría
$log_sql = "INSERT INTO logs_actividad (accion, detalles, id_usuario) 
            VALUES ('EXPORTACION_EXCEL', ?, ?)";
$log_stmt = $conn->prepare($log_sql);
$detalles = "Exportación de {$result->num_rows} clientes a Excel";
$admin_id = $_SESSION['id_usuario'];
$log_stmt->bind_param("si", $detalles, $admin_id);
@$log_stmt->execute();

exit;