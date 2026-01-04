<!-- ==================== usuarios_toggle_estado.php ==================== -->
<?php
// app/actions/usuarios_toggle_estado.php

session_start();
require_once __DIR__ . '/../config/db.php';

// Solo admin puede cambiar estado
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso no autorizado");
}

$id_usuario = $_GET['id'] ?? 0;
$nuevo_estado = $_GET['estado'] ?? 1;

if (!$id_usuario || !is_numeric($id_usuario)) {
    $_SESSION['mensaje'] = "ID de usuario inválido";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=usuarios_lista");
    exit;
}

// No puede desactivar su propio usuario
if ($id_usuario == $_SESSION['id_usuario']) {
    $_SESSION['mensaje'] = "No puedes desactivar tu propio usuario";
    $_SESSION['tipo_mensaje'] = "warning";
    header("Location: ../../index.php?p=usuarios_lista");
    exit;
}

// Actualizar estado
$sql = "UPDATE usuarios SET estado = ? WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $nuevo_estado, $id_usuario);

if ($stmt->execute()) {
    $texto_estado = $nuevo_estado ? 'activado' : 'desactivado';
    $_SESSION['mensaje'] = "Usuario {$texto_estado} correctamente";
    $_SESSION['tipo_mensaje'] = "success";
    
    // Log de auditoría
    $accion = $nuevo_estado ? 'USUARIO_ACTIVADO' : 'USUARIO_DESACTIVADO';
    $log_sql = "INSERT INTO logs_actividad (accion, detalles, tabla_afectada, id_registro, id_usuario) 
                VALUES (?, ?, 'usuarios', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $detalles = "Estado cambiado a: " . ($nuevo_estado ? 'Activo' : 'Inactivo');
    $admin_id = $_SESSION['id_usuario'];
    $log_stmt->bind_param("ssii", $accion, $detalles, $id_usuario, $admin_id);
    @$log_stmt->execute();
    
} else {
    $_SESSION['mensaje'] = "Error al cambiar estado: " . $stmt->error;
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: ../../index.php?p=usuarios_lista");
exit;
?>