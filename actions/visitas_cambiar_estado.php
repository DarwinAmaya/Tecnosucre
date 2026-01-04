!-- ==================== visitas_cambiar_estado.php ==================== -->
<?php
// app/actions/visitas_cambiar_estado.php

session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['id_usuario'])) {
    die("Sesión inválida");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.php?p=visitas_lista");
    exit;
}

$id_visita = $_POST['id_visita'] ?? 0;
$nuevo_estado = $_POST['estado'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';

if (!$id_visita || !$nuevo_estado) {
    die("Datos incompletos");
}

// Actualizar estado
$sql = "UPDATE visitas 
        SET estado = ?, 
            observaciones = ?,
            fecha_actualizacion = NOW()
        WHERE id_visita = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $nuevo_estado, $observaciones, $id_visita);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "Estado actualizado correctamente";
    $_SESSION['tipo_mensaje'] = "success";
} else {
    $_SESSION['mensaje'] = "Error al actualizar: " . $stmt->error;
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: ../../index.php?p=visitas_lista");
exit;
?>