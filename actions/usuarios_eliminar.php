

<!-- ==================== usuarios_eliminar.php ==================== -->
<?php
// app/actions/usuarios_eliminar.php

session_start();
require_once __DIR__ . '/../config/db.php';

// Solo admin puede eliminar
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso no autorizado");
}

$id_usuario = $_GET['id'] ?? 0;

if (!$id_usuario || !is_numeric($id_usuario)) {
    $_SESSION['mensaje'] = "ID de usuario inválido";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=usuarios_lista");
    exit;
}

// No puede eliminar su propio usuario
if ($id_usuario == $_SESSION['id_usuario']) {
    $_SESSION['mensaje'] = "No puedes eliminar tu propio usuario";
    $_SESSION['tipo_mensaje'] = "warning";
    header("Location: ../../index.php?p=usuarios_lista");
    exit;
}

// Verificar si el usuario tiene clientes asociados
$check_sql = "SELECT COUNT(*) as c FROM clientes WHERE id_usuario = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $id_usuario);
$check_stmt->execute();
$total_clientes = $check_stmt->get_result()->fetch_assoc()['c'];

if ($total_clientes > 0) {
    $_SESSION['mensaje'] = "No se puede eliminar el usuario porque tiene {$total_clientes} cliente(s) asociado(s). Primero desactívalo.";
    $_SESSION['tipo_mensaje'] = "warning";
    header("Location: ../../index.php?p=usuarios_lista");
    exit;
}

// Obtener datos del usuario para el log
$usuario_data = $conn->query("SELECT usuario FROM usuarios WHERE id_usuario = {$id_usuario}")->fetch_assoc();

// Eliminar usuario
$sql = "DELETE FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "Usuario eliminado correctamente";
    $_SESSION['tipo_mensaje'] = "success";
    
    // Log de auditoría
    $log_sql = "INSERT INTO logs_actividad (accion, detalles, tabla_afectada, id_registro, id_usuario) 
                VALUES ('USUARIO_ELIMINADO', ?, 'usuarios', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $detalles = "Usuario eliminado: " . ($usuario_data['usuario'] ?? 'Desconocido');
    $admin_id = $_SESSION['id_usuario'];
    $log_stmt->bind_param("sii", $detalles, $id_usuario, $admin_id);
    @$log_stmt->execute();
    
} else {
    $_SESSION['mensaje'] = "Error al eliminar usuario: " . $stmt->error;
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: ../../index.php?p=usuarios_lista");
exit;
?>