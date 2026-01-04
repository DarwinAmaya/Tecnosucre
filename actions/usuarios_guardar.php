<!-- ==================== usuarios_guardar.php (ACTUALIZADO) ==================== -->
<?php
// app/actions/usuarios_guardar.php (VERSIÓN MEJORADA)

session_start();
require_once __DIR__ . '/../config/db.php';

// Solo admin puede crear usuarios
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso no autorizado");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.php?p=usuarios_nuevo");
    exit;
}

// VALIDACIONES
$campos = ['cedula', 'nombre', 'apellido', 'usuario', 'password', 'rol'];
foreach ($campos as $c) {
    if (empty(trim($_POST[$c] ?? ''))) {
        $_SESSION['mensaje'] = "El campo {$c} es obligatorio";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: ../../index.php?p=usuarios_nuevo");
        exit;
    }
}

// Validar que las contraseñas coincidan
if (isset($_POST['password_confirm']) && $_POST['password'] !== $_POST['password_confirm']) {
    $_SESSION['mensaje'] = "Las contraseñas no coinciden";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=usuarios_nuevo");
    exit;
}

$cedula = trim($_POST['cedula']);
$nombre = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$usuario = trim($_POST['usuario']);
$rol = $_POST['rol'];
$estado = isset($_POST['estado']) ? 1 : 0;

// Validar longitud mínima de contraseña
if (strlen($_POST['password']) < 6) {
    $_SESSION['mensaje'] = "La contraseña debe tener al menos 6 caracteres";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=usuarios_nuevo");
    exit;
}

$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// VALIDAR DUPLICADOS
$check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ? OR cedula = ?");
$check->bind_param("ss", $usuario, $cedula);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $_SESSION['mensaje'] = "El usuario o cédula ya existe en el sistema";
    $_SESSION['tipo_mensaje'] = "warning";
    header("Location: ../../index.php?p=usuarios_nuevo");
    exit;
}

// INSERTAR
$sql = "INSERT INTO usuarios (cedula, nombre, apellido, usuario, password, rol, estado, fecha_creacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssi", $cedula, $nombre, $apellido, $usuario, $password, $rol, $estado);

if ($stmt->execute()) {
    $nuevo_id = $stmt->insert_id;
    
    $_SESSION['mensaje'] = "Usuario <strong>{$usuario}</strong> registrado exitosamente";
    $_SESSION['tipo_mensaje'] = "success";
    
    // Log de auditoría
    $log_sql = "INSERT INTO logs_actividad (accion, detalles, tabla_afectada, id_registro, id_usuario) 
                VALUES ('USUARIO_CREADO', ?, 'usuarios', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $detalles = "Usuario: {$usuario} ({$nombre} {$apellido}) - Rol: {$rol}";
    $admin_id = $_SESSION['id_usuario'];
    $log_stmt->bind_param("sii", $detalles, $nuevo_id, $admin_id);
    @$log_stmt->execute();
    
    header("Location: ../../index.php?p=usuarios_lista");
} else {
    $_SESSION['mensaje'] = "Error al guardar usuario: " . $stmt->error;
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: ../../index.php?p=usuarios_nuevo");
}

exit;