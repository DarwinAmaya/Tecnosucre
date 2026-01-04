<?php
/* =====================================================
   REGISTRO DE CLIENTE - TECNOSUCRE
   Formulario paso a paso con validaciones
===================================================== */
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-plus"></i> Registrar Nuevo Cliente</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php?p=clientes_lista">Clientes</a></li>
                    <li class="breadcrumb-item active">Nuevo Cliente</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <!-- INDICADOR DE PROGRESO -->
    <div class="card">
        <div class="card-body">
            <div class="progress mb-3" style="height: 25px;">
                <div class="progress-bar bg-primary" id="progressBar" role="progressbar" 
                     style="width: 11%;" aria-valuenow="11" aria-valuemin="0" aria-valuemax="100">
                    Paso 1 de 9
                </div>
            </div>
            <div class="text-center text-muted" id="progressText">
                <small><i class="fas fa-info-circle"></i> Complete todos los pasos para registrar el cliente</small>
            </div>
        </div>
    </div>

    <!-- FORMULARIO -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" id="stepTitle"><i class="fas fa-user"></i> Información del Cliente</h3>
        </div>

        <form id="formCliente" method="POST" action="app/actions/clientes_guardar.php">
            <div class="card-body">

                <!-- ==================== PASO 1: NOMBRE Y APELLIDO ==================== -->
                <div class="step" data-step="1">
                    <div class="form-group">
                        <label class="font-weight-bold">
                            <i class="fas fa-user text-primary"></i> Nombre y Apellido del Cliente *
                        </label>
                        <input type="text" 
                               name="nombre_apellido" 
                               id="nombre_apellido"
                               class="form-control form-control-lg" 
                               placeholder="Ejemplo: Juan Pérez"
                               required>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Ingrese el nombre completo del cliente
                        </small>
                    </div>
                </div>

                <!-- ==================== PASO 2: TELÉFONO ==================== -->
                <div class="step d-none" data-step="2">
                    <div class="form-group">
                        <label class="font-weight-bold">
                            <i class="fas fa-phone text-success"></i> Teléfono de Contacto *
                        </label>
                        <input type="text" 
                               name="telefono" 
                               id="telefono"
                               class="form-control form-control-lg" 
                               placeholder="Ejemplo: 04141234567"
                               maxlength="11"
                               required>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Solo números - 11 dígitos (0412, 0414, 0416, 0424, 0426)
                        </small>
                        <div id="telefono_error" class="text-danger d-none">
                            <i class="fas fa-exclamation-circle"></i> Teléfono venezolano inválido
                        </div>
                    </div>
                </div>

                <!-- ==================== PASO 3: UBICACIÓN ==================== -->
                <div class="step d-none" data-step="3">
                    <div class="form-group">
                        <label class="font-weight-bold">
                            <i class="fas fa-map-marker-alt text-danger"></i> Ubicación (Ciudad / Sector) *
                        </label>
                        <input type="text" 
                               name="ubicacion" 
                               class="form-control form-control-lg" 
                               placeholder="Ejemplo: Cumaná, Sector Centro"
                               required>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Ciudad y sector donde se encuentra el cliente
                        </small>
                    </div>
                </div>

                <!-- ==================== PASO 4: TARGET ==================== -->
                <div class="step d-none" data-step="4">
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-bullseye text-info"></i> Tipo de Target *
                    </label>
                    <div class="row">
                        <?php
                        $targets = [
                            'Residencial' => 'home',
                            'Comercial' => 'store',
                            'Empresarial' => 'building',
                            'Gubernamental' => 'landmark',
                            'Industrial' => 'industry'
                        ];
                        foreach ($targets as $target => $icon):
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="custom-control custom-checkbox custom-control-lg">
                                <input type="checkbox" 
                                       class="custom-control-input checkbox-target" 
                                       name="target[]" 
                                       value="<?= $target ?>" 
                                       id="target_<?= $target ?>">
                                <label class="custom-control-label" for="target_<?= $target ?>">
                                    <i class="fas fa-<?= $icon ?>"></i> <?= $target ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Seleccione al menos un tipo de target
                    </small>
                </div>

                <!-- ==================== PASO 5: PRODUCTOS ==================== -->
                <div class="step d-none" data-step="5">
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-box text-success"></i> Productos de Interés *
                    </label>
                    <div class="row">
                        <?php
                        $productos = [
                            'CCTV' => 'video',
                            'Alarmas' => 'bell',
                            'Video Portero' => 'door-closed',
                            'Control de Acceso' => 'key',
                            'Display' => 'tv'
                        ];
                        foreach ($productos as $producto => $icon):
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="custom-control custom-checkbox custom-control-lg">
                                <input type="checkbox" 
                                       class="custom-control-input checkbox-producto" 
                                       name="productos[]" 
                                       value="<?= $producto ?>" 
                                       id="producto_<?= str_replace(' ', '_', $producto) ?>">
                                <label class="custom-control-label" for="producto_<?= str_replace(' ', '_', $producto) ?>">
                                    <i class="fas fa-<?= $icon ?>"></i> <?= $producto ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Seleccione los productos que interesan al cliente
                    </small>
                </div>

                <!-- ==================== PASO 6: DIAGNÓSTICO ==================== -->
                <div class="step d-none" data-step="6">
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-tools text-warning"></i> Diagnóstico / Necesidad *
                    </label>
                    <div class="row">
                        <?php
                        $diagnosticos = [
                            'Instalación' => 'Instalación de sistema nuevo',
                            'Reparación' => 'Reparación de sistema existente',
                            'Ampliación' => 'Ampliación de sistema actual',
                            'Adecuación' => 'Adecuación o modernización'
                        ];
                        foreach ($diagnosticos as $diag => $desc):
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="custom-control custom-checkbox custom-control-lg">
                                <input type="checkbox" 
                                       class="custom-control-input checkbox-diagnostico" 
                                       name="diagnostico[]" 
                                       value="<?= $diag ?>" 
                                       id="diag_<?= $diag ?>">
                                <label class="custom-control-label" for="diag_<?= $diag ?>">
                                    <strong><?= $diag ?></strong>
                                    <br><small class="text-muted"><?= $desc ?></small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Seleccione el tipo de servicio requerido
                    </small>
                </div>

                <!-- ==================== PASO 7: MARCAS ==================== -->
                <div class="step d-none" data-step="7">
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-tags text-primary"></i> Marcas de Preferencia *
                    </label>
                    <div class="row">
                        <?php
                        $marcas = ['Hikvision', 'Huawei', 'Ezviz'];
                        foreach ($marcas as $marca):
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="custom-control custom-checkbox custom-control-lg">
                                <input type="checkbox" 
                                       class="custom-control-input checkbox-marca" 
                                       name="marcas[]" 
                                       value="<?= $marca ?>" 
                                       id="marca_<?= $marca ?>">
                                <label class="custom-control-label" for="marca_<?= $marca ?>">
                                    <i class="fas fa-tag"></i> <?= $marca ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group mt-3">
                        <label><i class="fas fa-plus-circle"></i> Otra Marca (Opcional)</label>
                        <input type="text" 
                               name="marca_otra" 
                               class="form-control" 
                               placeholder="Especifique otra marca">
                    </div>

                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Seleccione las marcas de interés del cliente
                    </small>
                </div>

                <!-- ==================== PASO 8: FORECAST PIPELINE ==================== -->
                <div class="step d-none" data-step="8">
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-chart-line text-info"></i> Forecast Pipeline (Estado del Cliente) *
                    </label>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Seleccione el nivel de interés del cliente:</strong>
                    </div>

                    <div class="row">
                        <?php
                        $forecasts = [
                            'Curiosidad' => ['color' => 'secondary', 'desc' => 'Solo preguntando, sin compromiso'],
                            'Necesidad' => ['color' => 'info', 'desc' => 'Tiene una necesidad identificada'],
                            'Interesado' => ['color' => 'primary', 'desc' => 'Muestra interés real en el servicio'],
                            'Muy Interesado' => ['color' => 'success', 'desc' => 'Alta probabilidad de cierre'],
                            'Declinado' => ['color' => 'danger', 'desc' => 'No está interesado por ahora']
                        ];
                        foreach ($forecasts as $forecast => $data):
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-<?= $data['color'] ?>">
                                <div class="card-body">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" 
                                               class="custom-control-input checkbox-forecast" 
                                               name="forecast[]" 
                                               value="<?= $forecast ?>" 
                                               id="forecast_<?= str_replace(' ', '_', $forecast) ?>">
                                        <label class="custom-control-label" for="forecast_<?= str_replace(' ', '_', $forecast) ?>">
                                            <span class="badge badge-<?= $data['color'] ?>"><?= $forecast ?></span>
                                            <br><small class="text-muted"><?= $data['desc'] ?></small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Puede seleccionar múltiples estados si aplican
                    </small>
                </div>

                <!-- ==================== PASO 9: PERÍODO DE VISITA ==================== -->
                <div class="step d-none" data-step="9">
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-calendar-alt text-danger"></i> Período Sugerido para Visita
                    </label>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Este período es opcional, puede programar la visita después desde el módulo de visitas.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Desde</label>
                                <input type="date" 
                                       name="fecha_desde" 
                                       class="form-control"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hasta</label>
                                <input type="date" 
                                       name="fecha_hasta" 
                                       class="form-control"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>

                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Rango de fechas en que el cliente prefiere ser visitado
                    </small>
                </div>

            </div>

            <!-- BOTONES DE NAVEGACIÓN -->
            <div class="card-footer">
                <div class="row">
                    <div class="col-6">
                        <button type="button" 
                                class="btn btn-secondary btn-lg btn-block" 
                                id="prevBtn"
                                style="display: none;">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" 
                                class="btn btn-primary btn-lg btn-block" 
                                id="nextBtn">
                            Siguiente <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="submit" 
                                class="btn btn-success btn-lg btn-block d-none" 
                                id="submitBtn">
                            <i class="fas fa-save"></i> Guardar Cliente
                        </button>
                    </div>
                </div>
            </div>

        </form>

    </div>

</div>
</section>

</div>

<!-- ESTILOS PERSONALIZADOS -->
<style>
.custom-control-lg .custom-control-label {
    font-size: 1.1rem;
    padding-left: 0.5rem;
}

.custom-control-lg .custom-control-input {
    width: 1.5rem;
    height: 1.5rem;
}

.card-body .step {
    min-height: 300px;
}

.progress-bar {
    transition: width 0.5s ease;
}
</style>

<!-- JAVASCRIPT DE NAVEGACIÓN Y VALIDACIÓN -->
<script>
let currentStep = 1;
const totalSteps = 9;

const steps = document.querySelectorAll('.step');
const nextBtn = document.getElementById('nextBtn');
const prevBtn = document.getElementById('prevBtn');
const submitBtn = document.getElementById('submitBtn');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');
const stepTitle = document.getElementById('stepTitle');

// Títulos de cada paso
const stepTitles = {
    1: '<i class="fas fa-user"></i> Información del Cliente',
    2: '<i class="fas fa-phone"></i> Datos de Contacto',
    3: '<i class="fas fa-map-marker-alt"></i> Ubicación',
    4: '<i class="fas fa-bullseye"></i> Tipo de Target',
    5: '<i class="fas fa-box"></i> Productos de Interés',
    6: '<i class="fas fa-tools"></i> Diagnóstico',
    7: '<i class="fas fa-tags"></i> Marcas Preferidas',
    8: '<i class="fas fa-chart-line"></i> Forecast Pipeline',
    9: '<i class="fas fa-calendar-alt"></i> Período de Visita'
};

function showStep(step) {
    // Ocultar todos los pasos
    steps.forEach(s => s.classList.add('d-none'));
    
    // Mostrar paso actual
    const currentStepElement = document.querySelector(`.step[data-step="${step}"]`);
    if (currentStepElement) {
        currentStepElement.classList.remove('d-none');
    }

    // Actualizar barra de progreso
    const progress = (step / totalSteps) * 100;
    progressBar.style.width = progress + '%';
    progressBar.setAttribute('aria-valuenow', progress);
    progressBar.textContent = `Paso ${step} de ${totalSteps}`;

    // Actualizar título
    stepTitle.innerHTML = stepTitles[step];

    // Actualizar texto de progreso
    const percentage = Math.round(progress);
    progressText.innerHTML = `<small><i class="fas fa-info-circle"></i> ${percentage}% completado</small>`;

    // Mostrar/ocultar botones
    prevBtn.style.display = step === 1 ? 'none' : 'block';
    nextBtn.classList.toggle('d-none', step === totalSteps);
    submitBtn.classList.toggle('d-none', step !== totalSteps);

    // Scroll al inicio del formulario
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validarPasoActual() {
    const currentStepElement = document.querySelector(`.step[data-step="${currentStep}"]`);
    
    // Validar inputs de texto requeridos
    const inputs = currentStepElement.querySelectorAll('input[required]:not([type="checkbox"])');
    for (let input of inputs) {
        if (input.value.trim() === '') {
            alert(`El campo "${input.previousElementSibling.textContent}" es obligatorio.`);
            input.focus();
            return false;
        }
    }

    // Validación especial: Teléfono (Paso 2)
    if (currentStep === 2) {
        const telefono = document.getElementById('telefono');
        const regexVenezuela = /^(0412|0414|0416|0424|0426)[0-9]{7}$/;
        
        if (!regexVenezuela.test(telefono.value.trim())) {
            document.getElementById('telefono_error').classList.remove('d-none');
            telefono.focus();
            return false;
        }
        document.getElementById('telefono_error').classList.add('d-none');
    }

    // Validar checkboxes (Pasos 4, 5, 6, 7, 8)
    const checkboxGroups = [
        { step: 4, class: 'checkbox-target', name: 'Target' },
        { step: 5, class: 'checkbox-producto', name: 'Productos' },
        { step: 6, class: 'checkbox-diagnostico', name: 'Diagnóstico' },
        { step: 7, class: 'checkbox-marca', name: 'Marcas' },
        { step: 8, class: 'checkbox-forecast', name: 'Forecast Pipeline' }
    ];

    for (let group of checkboxGroups) {
        if (currentStep === group.step) {
            const checkboxes = currentStepElement.querySelectorAll(`.${group.class}:checked`);
            if (checkboxes.length === 0) {
                alert(`Debe seleccionar al menos una opción en ${group.name}`);
                return false;
            }
        }
    }

    return true;
}

// Evento: Botón Siguiente
nextBtn.addEventListener('click', function() {
    if (!validarPasoActual()) return;
    
    if (currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
    }
});

// Evento: Botón Anterior
prevBtn.addEventListener('click', function() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
});

// Validación del formulario completo antes de enviar
document.getElementById('formCliente').addEventListener('submit', function(e) {
    if (!validarPasoActual()) {
        e.preventDefault();
        return false;
    }
    
    // Mostrar loader
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    submitBtn.disabled = true;
});

// Solo números en teléfono
document.getElementById('telefono').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Inicializar
showStep(currentStep);
</script>