<?php
/* =====================================================
   LIMPIAR LOGS DEL SISTEMA - TECNOSUCRE
   Elimina registros antiguos de auditoría
===================================================== */

session_start();
require_once __DIR__ . '/../config/db.php';

// Solo admin puede limpiar logs
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso no autorizado");
}

// ==================== CONFIGURACIÓN ====================
// Días de retención (por defecto 90 días)
$dias_retencion = $_GET['dias'] ?? 90;

if (!is_numeric($dias_retencion) || $dias_retencion < 1) {
    $dias_retencion = 90;
}

// Límite máximo de retención (no se puede borrar logs más recientes de 30 días)
if ($dias_retencion < 30) {
    $_SESSION['mensaje'] = "Por seguridad, no se pueden eliminar logs de menos de 30 días";
    $_SESSION['tipo_mensaje'] = "warning";
    header("Location: ../../index.php?p=configuraciones");
    exit;
}

// ==================== VERIFICAR TABLA ====================
$check_table = $conn->query("SHOW TABLES LIKE 'logs_actividad'");

if (!$check_table || $check_table->num_rows === 0) {
    $_SESSION['mensaje'] = "La tabla logs_actividad no existe";
    $_SESSION['tipo_mensaje'] = "warning";
    header("Location: ../../index.php?p=configuraciones");
    exit;
}

// ==================== CONTAR REGISTROS A ELIMINAR ====================
$fecha_limite = date('Y-m-d H:i:s', strtotime("-{$dias_retencion} days"));

$count_sql = "SELECT COUNT(*) as total FROM logs_actividad WHERE fecha_hora < ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("s", $fecha_limite);
$count_stmt->execute();
$total_eliminar = $count_stmt->get_result()->fetch_assoc()['total'];

// Si no hay registros para eliminar
if ($total_eliminar === 0) {
    $_SESSION['mensaje'] = "No hay logs antiguos para eliminar (más de {$dias_retencion} días)";
    $_SESSION['tipo_mensaje'] = "info";
    header("Location: ../../index.php?p=configuraciones");
    exit;
}

// ==================== CREAR BACKUP ANTES DE ELIMINAR ====================
$backup_logs = [];
$backup_sql = "SELECT * FROM logs_actividad WHERE fecha_hora < ? ORDER BY fecha_hora DESC LIMIT 1000";
$backup_stmt = $conn->prepare($backup_sql);
$backup_stmt->bind_param("s", $fecha_limite);
$backup_stmt->execute();
$backup_result = $backup_stmt->get_result();

$backup_file = __DIR__ . '/../../backups/logs_backup_' . date('Y-m-d_His') . '.json';
$backup_dir = __DIR__ . '/../../backups';

// Crear carpeta de backups si no existe
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

while ($row = $backup_result->fetch_assoc()) {
    $backup_logs[] = $row;
}

// Guardar backup en JSON
if (!empty($backup_logs)) {
    file_put_contents($backup_file, json_encode($backup_logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ==================== ELIMINAR LOGS ANTIGUOS ====================
$delete_sql = "DELETE FROM logs_actividad WHERE fecha_hora < ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("s", $fecha_limite);

if ($delete_stmt->execute()) {
    $registros_eliminados = $delete_stmt->affected_rows;
    
    // ==================== OPTIMIZAR TABLA ====================
    $conn->query("OPTIMIZE TABLE logs_actividad");
    
    // ==================== REGISTRO DE LA LIMPIEZA ====================
    $log_limpieza = "INSERT INTO logs_actividad (accion, detalles, id_usuario) 
                     VALUES ('LIMPIEZA_LOGS', ?, ?)";
    $log_stmt = $conn->prepare($log_limpieza);
    $detalles = "Eliminados {$registros_eliminados} logs (más de {$dias_retencion} días). Backup: logs_backup_" . date('Y-m-d_His') . ".json";
    $admin_id = $_SESSION['id_usuario'];
    $log_stmt->bind_param("si", $detalles, $admin_id);
    $log_stmt->execute();
    
    // ==================== ESTADÍSTICAS FINALES ====================
    $total_restante = $conn->query("SELECT COUNT(*) as c FROM logs_actividad")->fetch_assoc()['c'];
    
    $_SESSION['mensaje'] = "
        <strong>¡Limpieza completada exitosamente!</strong><br>
        • Registros eliminados: <strong>{$registros_eliminados}</strong><br>
        • Registros conservados: <strong>{$total_restante}</strong><br>
        • Backup guardado en: <code>backups/logs_backup_" . date('Y-m-d_His') . ".json</code>
    ";
    $_SESSION['tipo_mensaje'] = "success";
    
} else {
    $_SESSION['mensaje'] = "Error al limpiar logs: " . $delete_stmt->error;
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: ../../index.php?p=configuraciones");
exit;