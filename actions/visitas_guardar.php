<!-- ==================== visitas_guardar.php ==================== -->
<?php
// app/actions/visitas_guardar.php

session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['id_usuario'])) {
    die("Sesión inválida");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.php?p=visitas_programar");
    exit;
}

// Validar campos obligatorios
$campos = ['id_cliente', 'id_instalador', 'fecha_visita', 'hora_visita', 'tipo_visita'];
foreach ($campos as $campo) {
    if (empty($_POST[$campo])) {
        die("Error: Todos los campos obligatorios deben completarse");
    }
}

$id_cliente = $_POST['id_cliente'];
$id_instalador = $_POST['id_instalador'];
$fecha_visita = $_POST['fecha_visita'];
$hora_visita = $_POST['hora_visita'];
$tipo_visita = $_POST['tipo_visita'];
$prioridad = $_POST['prioridad'] ?? 'Normal';
$notas = $_POST['notas'] ?? '';
$recordatorio = isset($_POST['recordatorio']) ? 1 : 0;

// Insertar visita
$sql = "INSERT INTO visitas (
    id_cliente, 
    id_instalador, 
    fecha_visita, 
    hora_visita, 
    tipo_visita, 
    prioridad, 
    notas, 
    estado,
    fecha_creacion
) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente', NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iisssss",
    $id_cliente,
    $id_instalador,
    $fecha_visita,
    $hora_visita,
    $tipo_visita,
    $prioridad,
    $notas
);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "Visita programada exitosamente";
    $_SESSION['tipo_mensaje'] = "success";
} else {
    $_SESSION['mensaje'] = "Error al programar visita: " . $stmt->error;
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: ../../index.php?p=visitas_lista");
exit;
?>