<?php
/* =====================================================
   EDITAR CLIENTE - TECNOSUCRE
   Formulario de edición con datos pre-cargados
===================================================== */

require_once __DIR__ . '/../config/db.php';

$rol = $_SESSION['rol'];
$id_usuario = $_SESSION['id_usuario'];
$id_cliente = $_GET['id'] ?? 0;

// Validar ID
if (!$id_cliente || !is_numeric($id_cliente)) {
    $_SESSION['mensaje'] = "ID de cliente inválido";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: index.php?p=clientes_lista");
    exit;
}

// Obtener datos del cliente
if ($rol === 'admin') {
    $sql = "SELECT * FROM clientes WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
} else {
    // Instalador solo puede editar sus propios clientes
    $sql = "SELECT * FROM clientes WHERE id_cliente = ? AND id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_cliente, $id_usuario);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensaje'] = "Cliente no encontrado o sin permisos";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: index.php?p=clientes_lista");
    exit;
}

$cliente = $result->fetch_assoc();

// Decodificar JSON
$target = json_decode($cliente['target'], true) ?? [];
$productos = json_decode($cliente['productos'], true) ?? [];
$marcas = json_decode($cliente['marcas'], true) ?? [];
$diagnostico = json_decode($cliente['diagnostico'], true) ?? [];
$forecast = json_decode($cliente['forecast_pipeline'], true) ?? [];
?>

<div class="content-wrapper">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-edit text-warning"></i> Editar Cliente</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php?p=clientes_lista">Clientes</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <!-- BOTONES SUPERIORES -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="index.php?p=clientes_detalle&id=<?= $id_cliente ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Detalle
            </a>
        </div>
    </div>

    <!-- FORMULARIO -->
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-edit"></i> Editando: <?= htmlspecialchars($cliente['nombre_apellido']) ?>
            </h3>
        </div>

        <form method="POST" action="app/actions/clientes_actualizar.php" id="formEditarCliente">
            <input type="hidden" name="id_cliente" value="<?= $id_cliente ?>">
            
            <div class="card-body">

                <!-- INFORMACIÓN BÁSICA -->
                <h5 class="text-primary mb-3">
                    <i class="fas fa-info-circle"></i> Información Básica
                </h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nombre y Apellido *</label>
                            <input type="text" 
                                   name="nombre_apellido" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($cliente['nombre_apellido']) ?>"
                                   required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Teléfono *</label>
                            <input type="text" 
                                   name="telefono" 
                                   id="telefono"
                                   class="form-control" 
                                   value="<?= htmlspecialchars($cliente['telefono']) ?>"
                                   maxlength="11"
                                   required>
                            <small class="form-text text-muted">Solo números - 11 dígitos</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Ubicación *</label>
                    <input type="text" 
                           name="ubicacion" 
                           class="form-control" 
                           value="<?= htmlspecialchars($cliente['ubicacion']) ?>"
                           required>
                </div>

                <hr class="my-4">

                <!-- TARGET -->
                <h5 class="text-info mb-3">
                    <i class="fas fa-bullseye"></i> Target
                </h5>

                <div class="row">
                    <?php
                    $targets_opciones = ['Residencial', 'Comercial', 'Empresarial', 'Gubernamental', 'Industrial'];
                    foreach ($targets_opciones as $t):
                    ?>
                    <div class="col-md-6 col-lg-4 mb-2">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   name="target[]" 
                                   value="<?= $t ?>" 
                                   id="target_<?= $t ?>"
                                   <?= in_array($t, $target) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="target_<?= $t ?>">
                                <?= $t ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">

                <!-- PRODUCTOS -->
                <h5 class="text-success mb-3">
                    <i class="fas fa-box"></i> Productos de Interés
                </h5>

                <div class="row">
                    <?php
                    $productos_opciones = ['CCTV', 'Alarmas', 'Video Portero', 'Control de Acceso', 'Display'];
                    foreach ($productos_opciones as $p):
                    ?>
                    <div class="col-md-6 col-lg-4 mb-2">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   name="productos[]" 
                                   value="<?= $p ?>" 
                                   id="producto_<?= str_replace(' ', '_', $p) ?>"
                                   <?= in_array($p, $productos) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="producto_<?= str_replace(' ', '_', $p) ?>">
                                <?= $p ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">

                <!-- MARCAS -->
                <h5 class="text-warning mb-3">
                    <i class="fas fa-tags"></i> Marcas
                </h5>

                <div class="row">
                    <?php
                    $marcas_opciones = ['Hikvision', 'Huawei', 'Ezviz'];
                    foreach ($marcas_opciones as $m):
                    ?>
                    <div class="col-md-4 mb-2">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   name="marcas[]" 
                                   value="<?= $m ?>" 
                                   id="marca_<?= $m ?>"
                                   <?= in_array($m, $marcas) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="marca_<?= $m ?>">
                                <?= $m ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-group mt-3">
                    <label>Otra Marca</label>
                    <input type="text" 
                           name="marca_otra" 
                           class="form-control" 
                           value="<?= htmlspecialchars($cliente['marca_otra'] ?? '') ?>"
                           placeholder="Especificar otra marca">
                </div>

                <hr class="my-4">

                <!-- DIAGNÓSTICO -->
                <h5 class="text-secondary mb-3">
                    <i class="fas fa-tools"></i> Diagnóstico
                </h5>

                <div class="row">
                    <?php
                    $diagnosticos = ['Instalación', 'Reparación', 'Ampliación', 'Adecuación'];
                    foreach ($diagnosticos as $d):
                    ?>
                    <div class="col-md-6 mb-2">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   name="diagnostico[]" 
                                   value="<?= $d ?>" 
                                   id="diag_<?= $d ?>"
                                   <?= in_array($d, $diagnostico) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="diag_<?= $d ?>">
                                <?= $d ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">

                <!-- FORECAST PIPELINE -->
                <h5 class="text-primary mb-3">
                    <i class="fas fa-chart-line"></i> Forecast Pipeline
                </h5>

                <div class="row">
                    <?php
                    $forecasts = ['Curiosidad', 'Necesidad', 'Interesado', 'Muy Interesado', 'Declinado'];
                    foreach ($forecasts as $f):
                    ?>
                    <div class="col-md-6 col-lg-4 mb-2">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   name="forecast[]" 
                                   value="<?= $f ?>" 
                                   id="forecast_<?= str_replace(' ', '_', $f) ?>"
                                   <?= in_array($f, $forecast) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="forecast_<?= str_replace(' ', '_', $f) ?>">
                                <?= $f ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">

                <!-- PERÍODO DE VISITA -->
                <h5 class="text-danger mb-3">
                    <i class="fas fa-calendar-alt"></i> Período Sugerido para Visita
                </h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Desde</label>
                            <input type="date" 
                                   name="fecha_desde" 
                                   class="form-control"
                                   value="<?= $cliente['fecha_visita_desde'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Hasta</label>
                            <input type="date" 
                                   name="fecha_hasta" 
                                   class="form-control"
                                   value="<?= $cliente['fecha_visita_hasta'] ?? '' ?>">
                        </div>
                    </div>
                </div>

            </div>

            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <a href="index.php?p=clientes_detalle&id=<?= $id_cliente ?>" 
                           class="btn btn-secondary btn-block">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-success btn-block" id="btnGuardar">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>

        </form>

    </div>

</div>
</section>

</div>

<script>
// Solo números en teléfono
document.getElementById('telefono').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Prevenir doble submit
document.getElementById('formEditarCliente').addEventListener('submit', function() {
    const btnGuardar = document.getElementById('btnGuardar');
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
});
</script>