<?php
/* =====================================================
   REGISTRAR NUEVO USUARIO/INSTALADOR - SOLO ADMIN
===================================================== */

if ($_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-plus text-success"></i> Registrar Nuevo Usuario</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php?p=usuarios_lista">Usuarios</a></li>
                    <li class="breadcrumb-item active">Nuevo Usuario</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <div class="row">
        <div class="col-md-8 offset-md-2">

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-shield"></i> Datos del Nuevo Usuario</h3>
                </div>

                <form method="POST" action="app/actions/usuarios_guardar.php" id="formUsuario">
                    <div class="card-body">

                        <!-- INFORMACIÓN PERSONAL -->
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-id-card"></i> Información Personal
                        </h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cedula">
                                        Cédula de Identidad *
                                        <i class="fas fa-question-circle text-info" 
                                           data-toggle="tooltip" 
                                           title="Solo números, sin puntos ni guiones"></i>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                        <input type="text" 
                                               class="form-control" 
                                               id="cedula" 
                                               name="cedula" 
                                               placeholder="Ej: 12345678"
                                               maxlength="20"
                                               required>
                                    </div>
                                    <small class="form-text text-muted">Solo números, sin V-, E-, puntos ni guiones</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" 
                                               class="form-control" 
                                               id="nombre" 
                                               name="nombre" 
                                               placeholder="Ej: Juan"
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apellido">Apellido *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" 
                                               class="form-control" 
                                               id="apellido" 
                                               name="apellido" 
                                               placeholder="Ej: Pérez"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rol">Rol del Usuario *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        </div>
                                        <select class="form-control" id="rol" name="rol" required>
                                            <option value="">Seleccione un rol...</option>
                                            <option value="instalador" selected>Instalador / Técnico</option>
                                            <option value="admin">Administrador</option>
                                        </select>
                                    </div>
                                    <small class="form-text text-muted">
                                        <strong>Instalador:</strong> Puede registrar clientes y visitas<br>
                                        <strong>Admin:</strong> Control total del sistema
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- CREDENCIALES DE ACCESO -->
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-key"></i> Credenciales de Acceso
                        </h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="usuario">
                                        Nombre de Usuario *
                                        <i class="fas fa-question-circle text-info" 
                                           data-toggle="tooltip" 
                                           title="Debe ser único en el sistema"></i>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                                        </div>
                                        <input type="text" 
                                               class="form-control" 
                                               id="usuario" 
                                               name="usuario" 
                                               placeholder="Ej: jperez"
                                               pattern="[a-zA-Z0-9._-]+"
                                               title="Solo letras, números, punto, guión y guión bajo"
                                               required>
                                    </div>
                                    <small class="form-text text-muted">Solo letras, números y guiones</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Contraseña *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        </div>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               placeholder="Mínimo 6 caracteres"
                                               minlength="6"
                                               required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" 
                                                    type="button" 
                                                    id="togglePassword">
                                                <i class="fas fa-eye" id="eyeIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Mínimo 6 caracteres</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirm">Confirmar Contraseña *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        </div>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password_confirm" 
                                               name="password_confirm" 
                                               placeholder="Repita la contraseña"
                                               minlength="6"
                                               required>
                                    </div>
                                    <div id="password_match_error" class="text-danger d-none mt-1">
                                        <i class="fas fa-exclamation-circle"></i> Las contraseñas no coinciden
                                    </div>
                                    <div id="password_match_success" class="text-success d-none mt-1">
                                        <i class="fas fa-check-circle"></i> Las contraseñas coinciden
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estado del Usuario</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="estado" 
                                               name="estado" 
                                               value="1" 
                                               checked>
                                        <label class="custom-control-label" for="estado">
                                            <span class="badge badge-success">Activo</span>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Los usuarios inactivos no pueden acceder al sistema
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- ALERTA INFORMATIVA -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Información importante:</strong>
                            <ul class="mb-0 mt-2">
                                <li>El usuario podrá cambiar su contraseña después del primer acceso</li>
                                <li>La cédula y el nombre de usuario deben ser únicos</li>
                                <li>Todos los campos marcados con (*) son obligatorios</li>
                            </ul>
                        </div>

                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="index.php?p=usuarios_lista" class="btn btn-secondary btn-block">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-success btn-block" id="btnSubmit">
                                    <i class="fas fa-save"></i> Registrar Usuario
                                </button>
                            </div>
                        </div>
                    </div>

                </form>

            </div>

            <!-- CARD DE AYUDA -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-question-circle"></i> Ayuda</h3>
                </div>
                <div class="card-body">
                    <p><strong>Tipos de usuario:</strong></p>
                    <ul>
                        <li><strong>Instalador:</strong> Puede registrar clientes, programar y gestionar sus propias visitas</li>
                        <li><strong>Administrador:</strong> Control total del sistema, gestión de usuarios y exportación de datos</li>
                    </ul>
                    <p class="mb-0"><strong>Recomendaciones de seguridad:</strong></p>
                    <ul class="mb-0">
                        <li>Use contraseñas de al menos 8 caracteres</li>
                        <li>Combine letras, números y símbolos</li>
                        <li>No comparta las credenciales</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

</div>
</section>

</div>

<script>
// Validación en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    
    // Solo números en cédula
    document.getElementById('cedula').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Generar usuario automáticamente desde nombre y apellido
    const nombre = document.getElementById('nombre');
    const apellido = document.getElementById('apellido');
    const usuario = document.getElementById('usuario');
    
    function generarUsuario() {
        if (nombre.value && apellido.value) {
            const usuarioSugerido = (nombre.value.charAt(0) + apellido.value)
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9]/g, '');
            
            if (!usuario.value) {
                usuario.value = usuarioSugerido;
            }
        }
    }
    
    nombre.addEventListener('blur', generarUsuario);
    apellido.addEventListener('blur', generarUsuario);

    // Toggle mostrar contraseña
    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (password.type === 'password') {
            password.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });

    // Validar que las contraseñas coincidan
    const passwordConfirm = document.getElementById('password_confirm');
    const passwordInput = document.getElementById('password');
    const matchError = document.getElementById('password_match_error');
    const matchSuccess = document.getElementById('password_match_success');
    
    function validarPasswords() {
        if (passwordConfirm.value) {
            if (passwordInput.value === passwordConfirm.value) {
                matchError.classList.add('d-none');
                matchSuccess.classList.remove('d-none');
                passwordConfirm.setCustomValidity('');
            } else {
                matchError.classList.remove('d-none');
                matchSuccess.classList.add('d-none');
                passwordConfirm.setCustomValidity('Las contraseñas no coinciden');
            }
        } else {
            matchError.classList.add('d-none');
            matchSuccess.classList.add('d-none');
        }
    }
    
    passwordInput.addEventListener('input', validarPasswords);
    passwordConfirm.addEventListener('input', validarPasswords);

    // Validación del formulario antes de enviar
    document.getElementById('formUsuario').addEventListener('submit', function(e) {
        if (passwordInput.value !== passwordConfirm.value) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
            passwordConfirm.focus();
            return false;
        }
        
        // Deshabilitar botón para evitar doble envío
        const btnSubmit = document.getElementById('btnSubmit');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    });

    // Cambiar badge de estado
    document.getElementById('estado').addEventListener('change', function() {
        const label = this.nextElementSibling.querySelector('.badge');
        if (this.checked) {
            label.classList.remove('badge-danger');
            label.classList.add('badge-success');
            label.textContent = 'Activo';
        } else {
            label.classList.remove('badge-success');
            label.classList.add('badge-danger');
            label.textContent = 'Inactivo';
        }
    });

    // Activar tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<style>
.input-group-text {
    background: #f4f6f9;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.custom-control-label .badge {
    font-size: 0.9rem;
    padding: 4px 8px;
}
</style>