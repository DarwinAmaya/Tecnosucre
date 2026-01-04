<?php
session_start();

if (isset($_SESSION['id_usuario'], $_SESSION['rol'])) {
    header("Location: index.php");
    exit;
}

require_once "app/config/db.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if ($usuario === "" || $password === "") {
        $mensaje = "Debe completar todos los campos";
    } else {

        $sql = "SELECT id_usuario, nombre, apellido, usuario, password, rol 
                FROM usuarios WHERE usuario = ? LIMIT 1";

        $stm = $conn->prepare($sql);
        $stm->bind_param("s", $usuario);
        $stm->execute();
        $res = $stm->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['usuario']    = $user['usuario'];
                $_SESSION['nombre']     = $user['nombre'];
                $_SESSION['apellido']   = $user['apellido'];
                $_SESSION['rol']        = $user['rol'];

                header("Location: index.php");
                exit;

            } else {
                $mensaje = "Contraseña incorrecta";
            }
        } else {
            $mensaje = "El usuario no existe";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Iniciar Sesión</title>

<!-- AdminLTE (desde tus propios archivos) -->
<link rel="stylesheet" href="app/assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="app/assets/plugins/fontawesome-free/css/all.min.css">

<style>
body{
    background:#f4f6f9;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.login-container{
    width:900px;
    max-width:95%;
    background:white;
    display:flex;
    box-shadow:0 0 18px rgba(0,0,0,0.12);
    border-radius:12px;
    overflow:hidden;
}

/* IZQUIERDA (LOGO) */
.login-left{
    width:45%;
    background:#003366;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    color:white;
    padding:40px 20px;
    text-align:center;
}

.login-left img{
    width:140px;
    margin-bottom:20px;
}

.login-left h2{
    font-weight:300;
}

/* DERECHA (FORMULARIO) */
.login-right{
    width:55%;
    padding:55px 40px;
}

.login-right h3{
    margin-bottom:25px;
    font-weight:600;
    color:#333;
}

.form-control{
    height:48px;
    font-size:16px;
    border-radius:8px;
}

.btn-primary{
    height:48px;
    font-size:16px;
    border-radius:8px;
}

.footer{
    text-align:center;
    margin-top:25px;
    color:#666;
    font-size:14px;
}
</style>
</head>

<body>

<div class="login-container">

    <!-- LADO IZQUIERDO -->
    <div class="login-left">
        <img src="app/assets/dist/img/HIK.png" alt="Logo">
        <h2>HIKVISION</h2>
        <p>Acceso al Sistema Interno</p>
    </div>

    <!-- LADO DERECHO -->
    <div class="login-right">
        <h3><i class="fas fa-user-lock"></i> Iniciar Sesión</h3>

        <?php if ($mensaje !== ""): ?>
            <div class="alert alert-danger"><?= $mensaje ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Usuario:</label>
                <input type="text" name="usuario" class="form-control" placeholder="Ingrese su usuario">
            </div>

            <div class="form-group mt-3">
                <label>Contraseña:</label>
                <input type="password" name="password" class="form-control" placeholder="Ingrese su contraseña">
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-4">
                Entrar al Sistema
            </button>
        </form>

        <div class="footer">
            Soluciones Integrales Tecnosucre
        </div>
    </div>

</div>

</body>
</html>
