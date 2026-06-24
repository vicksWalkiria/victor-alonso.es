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

$email = isset($data['email']) ? trim($data['email']) : '';

if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // 1. Guardar lead en CSV
    $csv_file = dirname(__DIR__) . '/data/entities_leads.csv';
    $csv_dir = dirname($csv_file);
    if (!is_dir($csv_dir)) {
        @mkdir($csv_dir, 0755, true);
    }
    $fp = fopen($csv_file, 'a');
    if ($fp) {
        fputcsv($fp, [date('Y-m-d H:i:s'), $email, $data['url1'] ?? '', $data['url2'] ?? '', $audit_id]);
        fclose($fp);
    }

    // 2. Mailrelay (Grupo 7)
    $mailrelay_key = getenv('MAILRELAY_API_KEY') ?: ($_ENV['MAILRELAY_API_KEY'] ?? '');
    if ($mailrelay_key) {
        $post_data = json_encode([
            'status' => 'active',
            'email'  => $email,
            'group_ids' => [7]
        ]);
        
        $ch = curl_init('https://walkiriaapps.ipzmarketing.com/api/v1/subscribers');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Auth-Token: ' . $mailrelay_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_exec($ch);
        curl_close($ch);
    }

    // 3. Enviar email con PDF adjunto al usuario
    $to = $email;
    $subject = 'Tu informe de Grafo Semántico y Brecha SEO';
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "From: Víctor Alonso SEO <soy@victor-alonso.es>\r\n";
    $headers .= "Reply-To: soy@victor-alonso.es\r\n";
    $headers .= "Bcc: soy@victor-alonso.es\r\n";
    
    $boundary = md5(time());
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    
    $body_html = "<p>Hola,</p>";
    $body_html .= "<p>He procesado con éxito el informe de auditoría semántica y brecha SEO para el sitio: <strong>" . htmlspecialchars($data['url1'] ?? '') . "</strong>.</p>";
    if (!empty($data['url2'])) {
        $body_html .= "<p>El análisis incluye la brecha semántica respecto a tu competidor analizado: <strong>" . htmlspecialchars($data['url2']) . "</strong>.</p>";
    }
    $body_html .= "<p>Adjunto a este correo encontrarás el informe PDF compilado con el visualizador de tu Grafo Semántico, el listado de entidades semánticas y las recomendaciones editoriales prioritarias.</p>";
    $body_html .= "<p>Si necesitas ayuda para diseñar una arquitectura semántica a gran escala o estructurar tus contenidos para el Knowledge Graph de Google, no dudes en responderme a este correo.</p>";
    $body_html .= "<p>Un saludo cordial,<br><strong>Víctor Alonso SEO</strong><br><a href=\"https://www.victor-alonso.es\">victor-alonso.es</a></p>\r\n";
    
    $body .= $body_html . "\r\n";
    
    // Adjuntar PDF
    $file_size = filesize($pdf_path);
    $handle = fopen($pdf_path, "r");
    $content = fread($handle, $file_size);
    fclose($handle);
    $encoded_content = chunk_split(base64_encode($content));
    
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/pdf; name=\"informe-semantico-seo.pdf\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"informe-semantico-seo.pdf\"\r\n\r\n";
    $body .= $encoded_content . "\r\n";
    $body .= "--$boundary--";
    
    if (mail($to, $subject, $body, $headers)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al enviar el correo.']);
    }
    
    // Limpieza
    if (file_exists($json_path)) @unlink($json_path);
    if (file_exists($pdf_path)) @unlink($pdf_path);
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
