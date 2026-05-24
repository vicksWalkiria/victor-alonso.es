<?php
/**
 * extractor-entidades-pdf.php
 * Recibe datos del frontal y genera un PDF del Extractor Semántico usando Puppeteer.
 */

require_once dirname(__DIR__) . '/includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Crear ID único para este informe
$audit_id = uniqid('ent_');
$reports_dir = dirname(__DIR__) . '/data/reports/entidades';

if (!is_dir($reports_dir)) {
    if (!mkdir($reports_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio temporal para reportes.']);
        exit;
    }
}

$json_path = $reports_dir . '/' . $audit_id . '.json';
$pdf_path = $reports_dir . '/' . $audit_id . '.pdf';

// Añadir fecha actual
$data['date'] = date('Y-m-d H:i:s');

// Guardar datos temporalmente
file_put_contents($json_path, json_encode($data, JSON_UNESCAPED_UNICODE));

// Ejecutar script Puppeteer NodeJS
$node_script = dirname(__DIR__) . '/herramientas/auditor-v2/generate-pdf.js';
$cmd = escapeshellcmd("node " . escapeshellarg($node_script) . " --id=" . escapeshellarg($audit_id) . " --tool=entidades") . " 2>&1";

exec($cmd, $output, $return_var);

if ($return_var !== 0 || !file_exists($pdf_path)) {
    // Intentar limpiar
    if (file_exists($json_path)) @unlink($json_path);
    echo json_encode([
        'success' => false, 
        'message' => 'Error al generar el PDF. Node exit code: ' . $return_var,
        'logs' => $output
    ]);
    exit;
}

// Devolver el PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="informe_semantico_' . $audit_id . '.pdf"');
header('Content-Length: ' . filesize($pdf_path));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

readfile($pdf_path);

// Limpieza (opcional, borramos tras servir)
@unlink($json_path);
@unlink($pdf_path);

exit;
