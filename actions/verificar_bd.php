<?php
/* =====================================================
   VERIFICACIÓN DE BASE DE DATOS - TECNOSUCRE
   Ejecuta este script después de importar la BD
===================================================== */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Base de Datos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #003366; }
        h2 { color: #0066cc; margin-top: 30px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            margin: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th {
            background: #003366;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f0f0f0;
        }
        .status-box {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            background: white;
        }
        .ok { border-left: 5px solid green; }
        .fail { border-left: 5px solid red; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover { background: #45a049; }
        .btn-secondary { background: #007bff; }
        .btn-secondary:hover { background: #0056b3; }
    </style>
</head>
<body>

<h1>🔍 Verificación de Base de Datos PDI</h1>
<hr>

<?php
$errores = [];
$warnings = [];
$exitos = [];

// ==================== 1. VERIFICAR CONEXIÓN ====================
echo "<h2>1. Conexión a MySQL</h2>";
if ($conn) {
    echo "<div class='status-box ok'>";
    echo "<strong class='success'>✅ Conexión exitosa</strong><br>";
    echo "Servidor: " . $conn->server_info . "<br>";
    echo "Cliente: " . $conn->client_info . "<br>";
    echo "Base de datos seleccionada: " . $conn->query("SELECT DATABASE()")->fetch_row()[0];
    echo "</div>";
    $exitos[] = "Conexión a BD";
} else {
    echo "<div class='status-box fail'>";
    echo "<strong class='error'>❌ Error de conexión:</strong> " . $conn->connect_error;
    echo "</div>";
    $errores[] = "Conexión a BD";
    die();
}

// ==================== 2. VERIFICAR TABLAS ====================
echo "<h2>2. Tablas de la Base de Datos</h2>";
$tablas_requeridas = ['usuarios', 'clientes', 'visitas', 'logs_actividad'];
$result = $conn->query("SHOW TABLES");

echo "<table>";
echo "<tr><th>Tabla</th><th>Estado</th><th>Registros</th></tr>";

$tablas_existentes = [];
while ($row = $result->fetch_array()) {
    $tablas_existentes[] = $row[0];
}

foreach ($tablas_requeridas as $tabla) {
    echo "<tr>";
    echo "<td><strong>$tabla</strong></td>";
    
    if (in_array($tabla, $tablas_existentes)) {
        echo "<td class='success'>✅ Existe</td>";
        
        // Contar registros
        $count = $conn->query("SELECT COUNT(*) as c FROM $tabla")->fetch_assoc()['c'];
        echo "<td>$count registros</td>";
        
        $exitos[] = "Tabla $tabla";
    } else {
        echo "<td class='error'>❌ No existe</td>";
        echo "<td>-</td>";
        $errores[] = "Tabla $tabla";
    }
    echo "</tr>";
}
echo "</table>";

// ==================== 3. VERIFICAR ESTRUCTURA DE CLIENTES ====================
echo "<h2>3. Estructura de Tabla 'clientes'</h2>";
$result = $conn->query("DESCRIBE clientes");

if ($result) {
    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $campos_requeridos = [
        'id_cliente', 'nombre_apellido', 'telefono', 'ubicacion',
        'target', 'productos', 'marcas', 'diagnostico', 'forecast_pipeline',
        'marca_otra', 'fecha_visita_desde', 'fecha_visita_hasta',
        'fecha_registro', 'id_usuario'
    ];
    
    $campos_encontrados = [];
    
    while ($row = $result->fetch_assoc()) {
        $campos_encontrados[] = $row['Field'];
        
        $class = in_array($row['Field'], $campos_requeridos) ? 'success' : '';
        
        echo "<tr class='$class'>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar campos faltantes
    $faltantes = array_diff($campos_requeridos, $campos_encontrados);
    if (!empty($faltantes)) {
        echo "<div class='status-box fail'>";
        echo "<strong class='error'>❌ Campos faltantes:</strong> " . implode(', ', $faltantes);
        echo "</div>";
        $errores[] = "Campos de tabla clientes";
    } else {
        echo "<div class='status-box ok'>";
        echo "<strong class='success'>✅ Todos los campos requeridos existen</strong>";
        echo "</div>";
        $exitos[] = "Estructura tabla clientes";
    }
} else {
    echo "<div class='status-box fail'>";
    echo "<strong class='error'>❌ Error:</strong> No se pudo obtener estructura de clientes";
    echo "</div>";
    $errores[] = "Estructura tabla clientes";
}

// ==================== 4. VERIFICAR VISTAS ====================
echo "<h2>4. Vistas SQL</h2>";
$vistas_requeridas = [
    'v_clientes_completo',
    'v_visitas_pendientes',
    'v_productividad_instaladores',
    'v_metricas_generales'
];

$result = $conn->query("SHOW FULL TABLES WHERE table_type = 'VIEW'");

echo "<table>";
echo "<tr><th>Vista</th><th>Estado</th></tr>";

$vistas_existentes = [];
if ($result) {
    while ($row = $result->fetch_array()) {
        $vistas_existentes[] = $row[0];
    }
}

foreach ($vistas_requeridas as $vista) {
    echo "<tr>";
    echo "<td><strong>$vista</strong></td>";
    
    if (in_array($vista, $vistas_existentes)) {
        echo "<td class='success'>✅ Existe</td>";
        $exitos[] = "Vista $vista";
    } else {
        echo "<td class='warning'>⚠️ No existe</td>";
        $warnings[] = "Vista $vista";
    }
    echo "</tr>";
}
echo "</table>";

// ==================== 5. VERIFICAR PROCEDIMIENTOS ====================
echo "<h2>5. Procedimientos Almacenados</h2>";
$procedimientos = ['sp_visitas_semana', 'sp_clientes_por_forecast'];

$result = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'pdi'");

echo "<table>";
echo "<tr><th>Procedimiento</th><th>Estado</th></tr>";

$procs_existentes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $procs_existentes[] = $row['Name'];
    }
}

foreach ($procedimientos as $proc) {
    echo "<tr>";
    echo "<td><strong>$proc</strong></td>";
    
    if (in_array($proc, $procs_existentes)) {
        echo "<td class='success'>✅ Existe</td>";
        $exitos[] = "Procedimiento $proc";
    } else {
        echo "<td class='warning'>⚠️ No existe</td>";
        $warnings[] = "Procedimiento $proc";
    }
    echo "</tr>";
}
echo "</table>";

// ==================== 6. VERIFICAR TRIGGERS ====================
echo "<h2>6. Triggers</h2>";
$triggers = ['trg_cliente_creado', 'trg_visita_completada'];

$result = $conn->query("SHOW TRIGGERS FROM pdi");

echo "<table>";
echo "<tr><th>Trigger</th><th>Estado</th><th>Evento</th><th>Tabla</th></tr>";

$triggers_existentes = [];
$trigger_details = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $triggers_existentes[] = $row['Trigger'];
        $trigger_details[$row['Trigger']] = $row;
    }
}

foreach ($triggers as $trigger) {
    echo "<tr>";
    echo "<td><strong>$trigger</strong></td>";
    
    if (in_array($trigger, $triggers_existentes)) {
        $details = $trigger_details[$trigger];
        echo "<td class='success'>✅ Existe</td>";
        echo "<td>{$details['Event']}</td>";
        echo "<td>{$details['Table']}</td>";
        $exitos[] = "Trigger $trigger";
    } else {
        echo "<td class='warning'>⚠️ No existe</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
        $warnings[] = "Trigger $trigger";
    }
    echo "</tr>";
}
echo "</table>";

// ==================== 7. VERIFICAR USUARIO ADMIN ====================
echo "<h2>7. Usuario Administrador</h2>";
$result = $conn->query("SELECT * FROM usuarios WHERE usuario = 'admin'");

if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<div class='status-box ok'>";
    echo "<strong class='success'>✅ Usuario admin encontrado</strong><br>";
    echo "ID: {$admin['id_usuario']}<br>";
    echo "Nombre: {$admin['nombre']} {$admin['apellido']}<br>";
    echo "Cédula: {$admin['cedula']}<br>";
    echo "Rol: {$admin['rol']}<br>";
    echo "Estado: " . ($admin['estado'] ? 'Activo' : 'Inactivo');
    echo "</div>";
    $exitos[] = "Usuario admin";
} else {
    echo "<div class='status-box fail'>";
    echo "<strong class='error'>❌ Usuario admin NO encontrado</strong><br>";
    echo "Debes ejecutar el INSERT del usuario admin";
    echo "</div>";
    $errores[] = "Usuario admin";
}

// ==================== 8. VERIFICAR ÍNDICES ====================
echo "<h2>8. Índices de Tabla 'clientes'</h2>";
$result = $conn->query("SHOW INDEX FROM clientes");

if ($result) {
    echo "<table>";
    echo "<tr><th>Índice</th><th>Columna</th><th>Único</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Key_name']}</td>";
        echo "<td>{$row['Column_name']}</td>";
        echo "<td>" . ($row['Non_unique'] == 0 ? '✅ Sí' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='status-box fail'>";
    echo "<strong class='error'>❌ No se pudieron obtener índices</strong>";
    echo "</div>";
}

// ==================== 9. PRUEBA DE INSERCIÓN ====================
echo "<h2>9. Prueba de Inserción</h2>";

// Verificar si ya existe un cliente de prueba
$check = $conn->query("SELECT COUNT(*) as c FROM clientes WHERE telefono = '04140000000'");
$existe = $check->fetch_assoc()['c'] > 0;

if (!$existe) {
    $sql = "INSERT INTO clientes (
        nombre_apellido, telefono, ubicacion,
        target, productos, marcas, diagnostico, forecast_pipeline,
        id_usuario
    ) VALUES (
        'Cliente de Prueba Verificación',
        '04140000000',
        'Cumaná Test',
        '[\"Residencial\"]',
        '[\"CCTV\"]',
        '[\"Hikvision\"]',
        '[\"Instalación\"]',
        '[\"Interesado\"]',
        1
    )";
    
    if ($conn->query($sql)) {
        $id_nuevo = $conn->insert_id;
        echo "<div class='status-box ok'>";
        echo "<strong class='success'>✅ Inserción de prueba exitosa</strong><br>";
        echo "ID generado: $id_nuevo<br>";
        echo "<small>Puedes eliminar este registro: DELETE FROM clientes WHERE id_cliente = $id_nuevo;</small>";
        echo "</div>";
        $exitos[] = "Inserción de prueba";
    } else {
        echo "<div class='status-box fail'>";
        echo "<strong class='error'>❌ Error en inserción:</strong> " . $conn->error;
        echo "</div>";
        $errores[] = "Inserción de prueba";
    }
} else {
    echo "<div class='status-box ok'>";
    echo "<strong class='success'>✅ Ya existe un cliente de prueba</strong>";
    echo "</div>";
}

// ==================== RESUMEN FINAL ====================
echo "<hr>";
echo "<h2>📊 Resumen de Verificación</h2>";

echo "<div class='status-box'>";
echo "<h3>Exitoso: " . count($exitos) . "</h3>";
foreach ($exitos as $exito) {
    echo "✅ $exito<br>";
}
echo "</div>";

if (!empty($warnings)) {
    echo "<div class='status-box' style='border-left-color: orange;'>";
    echo "<h3 class='warning'>Advertencias: " . count($warnings) . "</h3>";
    foreach ($warnings as $warning) {
        echo "⚠️ $warning<br>";
    }
    echo "<p><small>Los warnings son funcionalidades opcionales que mejoran el sistema pero no son críticas.</small></p>";
    echo "</div>";
}

if (!empty($errores)) {
    echo "<div class='status-box fail'>";
    echo "<h3 class='error'>Errores: " . count($errores) . "</h3>";
    foreach ($errores as $error) {
        echo "❌ $error<br>";
    }
    echo "</div>";
}

// ==================== RESULTADO FINAL ====================
echo "<hr>";
if (empty($errores)) {
    echo "<div class='status-box ok' style='font-size: 18px; text-align: center;'>";
    echo "<h2 class='success'>🎉 ¡BASE DE DATOS LISTA PARA USAR!</h2>";
    echo "<p>Todos los componentes críticos están funcionando correctamente.</p>";
    echo "<a href='../../index.php' class='btn'>🏠 Ir al Sistema</a>";
    echo "<a href='../../login.php' class='btn btn-secondary'>🔐 Ir al Login</a>";
    echo "</div>";
} else {
    echo "<div class='status-box fail' style='font-size: 18px; text-align: center;'>";
    echo "<h2 class='error'>⚠️ HAY ERRORES QUE CORREGIR</h2>";
    echo "<p>Revisa los errores arriba y ejecuta nuevamente el script SQL.</p>";
    echo "<a href='javascript:location.reload()' class='btn'>🔄 Verificar Nuevamente</a>";
    echo "</div>";
}

$conn->close();
?>

</body>
</html>