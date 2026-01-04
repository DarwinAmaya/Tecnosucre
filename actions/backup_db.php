<?php
/* =====================================================
   BACKUP DE BASE DE DATOS - TECNOSUCRE
   Genera un archivo SQL de respaldo completo
===================================================== */

session_start();
require_once __DIR__ . '/../config/db.php';

// Solo admin puede hacer backup
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso no autorizado");
}

// ==================== CONFIGURACIÓN ====================
$backup_file = 'backup_pdi_' . date('Y-m-d_His') . '.sql';
$tables = ['usuarios', 'clientes', 'visitas', 'logs_actividad'];

// ==================== FUNCIÓN DE BACKUP ====================
function export_table($conn, $table) {
    $sql = '';
    
    // Obtener estructura de tabla
    $sql .= "-- ========================================\n";
    $sql .= "-- Tabla: {$table}\n";
    $sql .= "-- ========================================\n\n";
    
    $create_result = $conn->query("SHOW CREATE TABLE `{$table}`");
    if ($create_result) {
        $create_row = $create_result->fetch_row();
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $create_row[1] . ";\n\n";
    }
    
    // Obtener datos
    $result = $conn->query("SELECT * FROM `{$table}`");
    
    if ($result && $result->num_rows > 0) {
        $sql .= "-- Datos de la tabla `{$table}`\n\n";
        
        while ($row = $result->fetch_assoc()) {
            $sql .= "INSERT INTO `{$table}` VALUES(";
            
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    // Escapar caracteres especiales
                    $value = addslashes($value);
                    $value = str_replace("\n", "\\n", $value);
                    $value = str_replace("\r", "\\r", $value);
                    $values[] = "'{$value}'";
                }
            }
            
            $sql .= implode(',', $values);
            $sql .= ");\n";
        }
        
        $sql .= "\n";
    }
    
    return $sql;
}

// ==================== GENERAR BACKUP ====================
$backup_content = "";

// Encabezado del archivo
$backup_content .= "-- =====================================================\n";
$backup_content .= "-- BACKUP DE BASE DE DATOS PDI - TECNOSUCRE\n";
$backup_content .= "-- Fecha: " . date('d/m/Y H:i:s') . "\n";
$backup_content .= "-- Usuario: " . $_SESSION['nombre'] . " " . $_SESSION['apellido'] . "\n";
$backup_content .= "-- =====================================================\n\n";

$backup_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$backup_content .= "SET time_zone = \"+00:00\";\n";
$backup_content .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

$backup_content .= "-- Crear base de datos si no existe\n";
$backup_content .= "CREATE DATABASE IF NOT EXISTS `pdi` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$backup_content .= "USE `pdi`;\n\n";

// Exportar cada tabla
foreach ($tables as $table) {
    // Verificar si la tabla existe
    $check = $conn->query("SHOW TABLES LIKE '{$table}'");
    
    if ($check && $check->num_rows > 0) {
        $backup_content .= export_table($conn, $table);
    } else {
        $backup_content .= "-- ADVERTENCIA: La tabla `{$table}` no existe\n\n";
    }
}

// Restaurar configuración
$backup_content .= "-- =====================================================\n";
$backup_content .= "-- Restaurar configuración\n";
$backup_content .= "-- =====================================================\n\n";
$backup_content .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$backup_content .= "COMMIT;\n\n";
$backup_content .= "-- Fin del backup\n";

// ==================== ESTADÍSTICAS DEL BACKUP ====================
$stats = [];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM `{$table}`");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        $stats[$table] = $count;
    }
}

// ==================== HEADERS PARA DESCARGA ====================
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $backup_file . '"');
header('Content-Length: ' . strlen($backup_content));
header('Pragma: no-cache');
header('Expires: 0');

// Enviar contenido
echo $backup_content;

// ==================== LOG DE AUDITORÍA ====================
$log_sql = "INSERT INTO logs_actividad (accion, detalles, id_usuario) 
            VALUES ('BACKUP_BD', ?, ?)";
$log_stmt = $conn->prepare($log_sql);

$total_registros = array_sum($stats);
$detalles = "Backup de BD con {$total_registros} registros totales";
$admin_id = $_SESSION['id_usuario'];

$log_stmt->bind_param("si", $detalles, $admin_id);
@$log_stmt->execute();

exit;