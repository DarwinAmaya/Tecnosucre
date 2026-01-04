<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pdi"; // tu base de datos

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
