<?php
/* =====================================================
   FOOTER - TECNOSUCRE
   Footer con información y scripts globales
===================================================== */
?>

<!-- FOOTER -->
<footer class="main-footer">
    <div class="float-right d-none d-sm-inline-block">
        <strong>Versión</strong> 2.0
    </div>
    <strong>&copy; <?= date('Y') ?> <a href="https://www.tecnosucre.com" target="_blank">Soluciones Integrales TecnoSucre</a></strong>
    <span class="ml-2">- Distribuidor Autorizado Hikvision</span>
</footer>

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <div class="p-3">
        <h5>Accesos Rápidos</h5>
        <div class="mb-2">
            <a href="index.php?p=clientes_nuevo" class="btn btn-block btn-primary btn-sm">
                <i class="fas fa-user-plus"></i> Nuevo Cliente
            </a>
        </div>
        <div class="mb-2">
            <a href="index.php?p=visitas_programar" class="btn btn-block btn-success btn-sm">
                <i class="fas fa-calendar-plus"></i> Programar Visita
            </a>
        </div>
        <div class="mb-2">
            <a href="index.php?p=clientes_lista" class="btn btn-block btn-info btn-sm">
                <i class="fas fa-list"></i> Ver Clientes
            </a>
        </div>
        <?php if ($_SESSION['rol'] === 'admin'): ?>
        <div class="mb-2">
            <a href="index.php?p=configuraciones" class="btn btn-block btn-warning btn-sm">
                <i class="fas fa-file-excel"></i> Exportar
            </a>
        </div>
        <?php endif; ?>
    </div>
</aside>

</div>
<!-- ./wrapper -->

<!-- =====================================================
     SCRIPTS GLOBALES
===================================================== -->

<!-- jQuery -->
<script src="app/assets/plugins/jquery/jquery.min.js"></script>

<!-- Bootstrap 4 -->
<script src="app/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE App -->
<script src="app/assets/dist/js/adminlte.min.js"></script>

<!-- SweetAlert2 para alertas bonitas -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Scripts personalizados globales -->
<script>
/* =====================================================
   CONFIGURACIÓN GLOBAL
===================================================== */

// Configuración de zona horaria
moment.locale('es');

// Confirmar antes de eliminar
function confirmarEliminacion(mensaje = '¿Está seguro de eliminar este registro?') {
    return Swal.fire({
        title: '¿Está seguro?',
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
}

// Toast de notificación
function mostrarToast(tipo, mensaje) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    Toast.fire({
        icon: tipo, // success, error, warning, info
        title: mensaje
    });
}

// Validar teléfono venezolano
function validarTelefonoVenezolano(telefono) {
    const regex = /^(0412|0414|0416|0424|0426)[0-9]{7}$/;
    return regex.test(telefono);
}

// Formatear número de teléfono
function formatearTelefono(telefono) {
    if (telefono.length === 11) {
        return telefono.replace(/(\d{4})(\d{3})(\d{4})/, '$1-$2-$3');
    }
    return telefono;
}

// Copiar al portapapeles
function copiarAlPortapapeles(texto) {
    navigator.clipboard.writeText(texto).then(function() {
        mostrarToast('success', 'Copiado al portapapeles');
    }, function() {
        mostrarToast('error', 'Error al copiar');
    });
}

// Loader global
function mostrarLoader(mensaje = 'Cargando...') {
    Swal.fire({
        title: mensaje,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function ocultarLoader() {
    Swal.close();
}

// Auto-cerrar alertas después de 5 segundos
$(document).ready(function() {
    $('.alert:not(.alert-important)').delay(5000).slideUp(300);
});

/* =====================================================
   EVENTOS GLOBALES
===================================================== */

// Prevenir doble clic en botones de submit
$('form').on('submit', function() {
    $(this).find('button[type="submit"]').prop('disabled', true);
});

// Confirmar antes de salir si hay cambios no guardados
let formModified = false;
$('form input, form textarea, form select').on('change', function() {
    formModified = true;
});

$(window).on('beforeunload', function() {
    if (formModified) {
        return '¿Está seguro de salir? Los cambios no guardados se perderán.';
    }
});

// Remover warning al enviar formulario
$('form').on('submit', function() {
    formModified = false;
});

// Tooltip de Bootstrap
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

// Activar popovers
$(function () {
    $('[data-toggle="popover"]').popover();
});

/* =====================================================
   FUNCIONES DE UTILIDAD
===================================================== */

// Formatear fecha a español
function formatearFecha(fecha) {
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fecha).toLocaleDateString('es-ES', opciones);
}

// Calcular días entre fechas
function diasEntreFechas(fecha1, fecha2) {
    const unDia = 24 * 60 * 60 * 1000;
    const diferencia = Math.abs(new Date(fecha2) - new Date(fecha1));
    return Math.round(diferencia / unDia);
}

// Validar email
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Scroll suave al top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Agregar botón de scroll to top
$(window).scroll(function() {
    if ($(this).scrollTop() > 100) {
        if ($('#btn-scroll-top').length === 0) {
            $('body').append(`
                <button id="btn-scroll-top" 
                        class="btn btn-primary" 
                        style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; border-radius: 50%; width: 50px; height: 50px;"
                        onclick="scrollToTop()">
                    <i class="fas fa-arrow-up"></i>
                </button>
            `);
        }
    } else {
        $('#btn-scroll-top').remove();
    }
});

/* =====================================================
   DEBUG (Solo en desarrollo)
===================================================== */
<?php if (isset($_GET['debug'])): ?>
console.log('===== TECNOSUCRE CRM DEBUG =====');
console.log('Usuario:', '<?= $_SESSION['nombre'] ?? 'N/A' ?>');
console.log('Rol:', '<?= $_SESSION['rol'] ?? 'N/A' ?>');
console.log('Página actual:', '<?= $_GET['p'] ?? 'dashboard' ?>');
console.log('================================');
<?php endif; ?>

</script>

<!-- Script adicional para DataTables (si se necesita) -->
<?php if (isset($usarDataTables) && $usarDataTables): ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $('.tabla-dinamica').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        "pageLength": 15
    });
});
</script>
<?php endif; ?>

<!-- Script para notificaciones en tiempo real (opcional) -->
<script>
// Revisar notificaciones cada 5 minutos
<?php if (isset($_SESSION['id_usuario'])): ?>
setInterval(function() {
    // Aquí puedes hacer una petición AJAX para verificar nuevas notificaciones
    // $.get('app/actions/check_notifications.php', function(data) { ... });
}, 300000); // 5 minutos
<?php endif; ?>
</script>

</body>
</html>