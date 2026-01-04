<?php
/* =====================================================
   EXPORTAR CLIENTE A PDF - TECNOSUCRE
   Requiere: TCPDF (ya está en composer.json)
===================================================== */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Solo admin puede exportar
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso no autorizado");
}

$id_cliente = $_GET['id'] ?? 0;

if (!$id_cliente) {
    die("ID de cliente inválido");
}

// Obtener datos del cliente
$sql = "
    SELECT c.*, u.nombre AS instalador_nombre, u.apellido AS instalador_apellido, u.cedula
    FROM clientes c
    INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
    WHERE c.id_cliente = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Cliente no encontrado");
}

$cliente = $result->fetch_assoc();

// Decodificar JSON
$target = json_decode($cliente['target'], true) ?? [];
$productos = json_decode($cliente['productos'], true) ?? [];
$marcas = json_decode($cliente['marcas'], true) ?? [];
$diagnostico = json_decode($cliente['diagnostico'], true) ?? [];
$forecast = json_decode($cliente['forecast_pipeline'], true) ?? [];

// Crear PDF con TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Información del documento
$pdf->SetCreator('TecnoSucre CRM');
$pdf->SetAuthor('Soluciones Integrales TecnoSucre');
$pdf->SetTitle('Ficha de Cliente - ' . $cliente['nombre_apellido']);
$pdf->SetSubject('Información del Cliente');

// Configuración de página
$pdf->SetMargins(15, 20, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);

// Fuente
$pdf->SetFont('helvetica', '', 10);

// Agregar página
$pdf->AddPage();

// ==================== ENCABEZADO ====================
$pdf->Image(__DIR__ . '/../assets/dist/img/HIK.png', 15, 15, 30, 0, 'PNG');

$pdf->SetXY(50, 15);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(0, 8, 'SOLUCIONES INTEGRALES TECNOSUCRE', 0, 1);

$pdf->SetXY(50, 23);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'Distribuidor Autorizado Hikvision', 0, 1);

$pdf->Ln(5);

// Línea divisoria
$pdf->SetDrawColor(0, 51, 102);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// ==================== TÍTULO ====================
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(0, 8, 'FICHA DE CLIENTE', 0, 1, 'C');
$pdf->Ln(3);

// ==================== INFORMACIÓN GENERAL ====================
$pdf->SetFillColor(0, 51, 102);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, ' INFORMACIÓN GENERAL', 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);

// Tabla de información
$infoData = [
    ['Cliente:', $cliente['nombre_apellido']],
    ['Teléfono:', $cliente['telefono']],
    ['Ubicación:', $cliente['ubicacion']],
    ['Fecha de Registro:', date('d/m/Y H:i', strtotime($cliente['fecha_registro']))],
    ['Registrado por:', $cliente['instalador_nombre'] . ' ' . $cliente['instalador_apellido']],
    ['Cédula Instalador:', $cliente['cedula']]
];

foreach ($infoData as $row) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(45, 6, $row[0], 1, 0, 'L', false);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, $row[1], 1, 1, 'L', false);
}

$pdf->Ln(3);

// ==================== PERÍODO DE VISITA ====================
if ($cliente['fecha_visita_desde'] && $cliente['fecha_visita_hasta']) {
    $pdf->SetFillColor(0, 51, 102);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, ' PERÍODO DE VISITA', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(45, 6, 'Desde:', 1, 0, 'L');
    $pdf->Cell(0, 6, date('d/m/Y', strtotime($cliente['fecha_visita_desde'])), 1, 1, 'L');
    $pdf->Cell(45, 6, 'Hasta:', 1, 0, 'L');
    $pdf->Cell(0, 6, date('d/m/Y', strtotime($cliente['fecha_visita_hasta'])), 1, 1, 'L');
    
    $pdf->Ln(3);
}

// ==================== TARGET ====================
if (!empty($target)) {
    $pdf->SetFillColor(23, 162, 184);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, ' TARGET', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, implode(' • ', $target), 1, 1, 'L');
    $pdf->Ln(3);
}

// ==================== PRODUCTOS ====================
if (!empty($productos)) {
    $pdf->SetFillColor(40, 167, 69);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, ' PRODUCTOS DE INTERÉS', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, implode(' • ', $productos), 1, 1, 'L');
    $pdf->Ln(3);
}

// ==================== MARCAS ====================
if (!empty($marcas)) {
    $pdf->SetFillColor(255, 193, 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, ' MARCAS', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, implode(' • ', $marcas), 1, 1, 'L');
    
    if (!empty($cliente['marca_otra'])) {
        $pdf->Cell(0, 6, 'Otra marca: ' . $cliente['marca_otra'], 1, 1, 'L');
    }
    
    $pdf->Ln(3);
}

// ==================== DIAGNÓSTICO ====================
if (!empty($diagnostico)) {
    $pdf->SetFillColor(108, 117, 125);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, ' DIAGNÓSTICO', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, implode(' • ', $diagnostico), 1, 1, 'L');
    $pdf->Ln(3);
}

// ==================== FORECAST PIPELINE ====================
if (!empty($forecast)) {
    $pdf->SetFillColor(0, 51, 102);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, ' FORECAST PIPELINE (ESTADO DEL CLIENTE)', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 10);
    
    foreach ($forecast as $f) {
        // Colores según el forecast
        $color = [200, 200, 200];
        switch ($f) {
            case 'Curiosidad': $color = [108, 117, 125]; break;
            case 'Necesidad': $color = [23, 162, 184]; break;
            case 'Interesado': $color = [0, 123, 255]; break;
            case 'Muy Interesado': $color = [40, 167, 69]; break;
            case 'Declinado': $color = [220, 53, 69]; break;
        }
        
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, ' ' . $f, 1, 1, 'L', true);
    }
}

// ==================== PIE DE PÁGINA ====================
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'Documento generado el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
$pdf->Cell(0, 5, 'Soluciones Integrales TecnoSucre - Distribuidor Autorizado Hikvision', 0, 1, 'C');

// ==================== SALIDA ====================
$filename = 'cliente_' . $cliente['id_cliente'] . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D'); // D = descargar, I = ver en navegador
exit;