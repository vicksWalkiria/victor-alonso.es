<?php
/**
 * API Gateway para el Auditor de Cookies RGPD v2
 * Autor: Víctor Alonso
 */

require_once dirname(__DIR__) . '/includes/config.php';

header('Content-Type: application/json; charset=utf-8');

// Configuración de rutas y archivos
$base_dir = realpath(__DIR__ . '/../data/reports');
if (!$base_dir) {
    echo json_encode(['success' => false, 'error' => 'Directorio de datos no configurado.']);
    exit;
}
$lock_dir = $base_dir . '/locks';

if (!is_dir($lock_dir)) {
    mkdir($lock_dir, 0777, true);
}

// Helper para sanitizar strings (evitar problemas de XSS en logs)
function clean_output($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Helper para buscar el ejecutable de Node.js
function get_node_binary_path() {
    $path = trim(shell_exec('which node 2>/dev/null'));
    if (!empty($path) && file_exists($path) && is_executable($path)) {
        return $path;
    }
    
    // Buscar en NVM del usuario victor
    $nvm_glob = glob('/home/*/.nvm/versions/node/v*/bin/node');
    if (!empty($nvm_glob)) {
        rsort($nvm_glob);
        return $nvm_glob[0];
    }
    
    return 'node';
}

// Helper para validar SSRF y puertos estándar
function is_ssrf_safe($url) {
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['host'])) {
        return false;
    }
    
    $host = $parsed['host'];
    
    // Bloquear puertos no estándar (solo permitir 80 y 443)
    if (isset($parsed['port'])) {
        $port = intval($parsed['port']);
        if ($port !== 80 && $port !== 443) {
            return false;
        }
    }
    
    // Resolver IP
    $ip = gethostbyname($host);
    if (!$ip || $ip === $host) {
        return false;
    }
    
    // Convertir IP a entero largo
    $ip_long = ip2long($ip);
    if ($ip_long === false) {
        return false;
    }
    
    // Rangos privados y reservados
    $private_ranges = [
        ['0.0.0.0', '0.255.255.255'],       // Local network
        ['10.0.0.0', '10.255.255.255'],     // Class A private
        ['127.0.0.0', '127.255.255.255'],   // Loopback
        ['169.254.0.0', '169.254.255.255'], // Link-local
        ['172.16.0.0', '172.31.255.255'],   // Class B private
        ['192.168.0.0', '192.168.255.255'], // Class C private
        ['224.0.0.0', '239.255.255.255'],   // Multicast
        ['240.0.0.0', '255.255.255.255']    // Reserved
    ];
    
    foreach ($private_ranges as $range) {
        $start = ip2long($range[0]);
        $end = ip2long($range[1]);
        if ($ip_long >= $start && $ip_long <= $end) {
            return false;
        }
    }
    
    return true;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'start':
        $url = trim($_POST['url'] ?? '');
        if (empty($url)) {
            echo json_encode(['success' => false, 'error' => 'La URL es obligatoria.']);
            exit;
        }

        // Auto-añadir protocolo si falta
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        // Validar URL sintáctica
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'error' => 'Formato de URL no válido.']);
            exit;
        }

        // Validar protección SSRF
        if (!is_ssrf_safe($url)) {
            echo json_encode(['success' => false, 'error' => 'La URL apunta a una dirección no permitida (red privada o local).']);
            exit;
        }

        // Rate Limit por IP: 1 petición cada 10s
        $ip_hash = md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $ip_rate_file = $lock_dir . '/ip_rate_' . $ip_hash . '.txt';
        if (file_exists($ip_rate_file)) {
            $last_time = intval(file_get_contents($ip_rate_file));
            if (time() - $last_time < 10) {
                echo json_encode(['success' => false, 'error' => 'Por favor, espera al menos 10 segundos entre cada análisis.']);
                exit;
            }
        }
        file_put_contents($ip_rate_file, time());

        // Limpieza de locks antiguos (>120 segundos) y cálculo de concurrencia
        $now = time();
        $active_locks = 0;
        $lock_files = glob($lock_dir . '/*.lock');
        if ($lock_files) {
            foreach ($lock_files as $file) {
                if (filemtime($file) < $now - 120) {
                    @unlink($file);
                } else {
                    $active_locks++;
                }
            }
        }

        // Concurrencia máxima = 2 procesos paralelos
        if ($active_locks >= 2) {
            echo json_encode(['success' => false, 'error' => 'El servidor está ocupado analizando otras páginas. Por favor, espera unos segundos e inténtalo de nuevo.']);
            exit;
        }

        // Generar ID de auditoría criptográfico seguro
        $audit_id = 'aud_' . bin2hex(random_bytes(16));

        // Crear directorio de destino del reporte
        $report_dir = $base_dir . '/' . $audit_id;
        if (!is_dir($report_dir)) {
            mkdir($report_dir, 0777, true);
        }

        // Crear el archivo de lock
        $lock_file = $lock_dir . '/' . $audit_id . '.lock';
        file_put_contents($lock_file, time());

        // Inicializar status.json
        $initial_status = [
            'status' => 'running',
            'step' => 'Iniciando navegador real headless...',
            'progress' => 5
        ];
        file_put_contents($report_dir . '/status.json', json_encode($initial_status, JSON_PRETTY_PRINT));

        // Lanzar el script en background blindando las variables
        $node_bin = get_node_binary_path();
        $script_path = __DIR__ . '/auditor-v2/cookie-scraper.js';
        
        $cmd = sprintf(
            '%s %s --url=%s --id=%s > /dev/null 2>&1 &',
            escapeshellarg($node_bin),
            escapeshellarg($script_path),
            escapeshellarg($url),
            escapeshellarg($audit_id)
        );
        exec($cmd);

        echo json_encode([
            'success' => true,
            'id' => $audit_id
        ]);
        break;

    case 'poll':
        $audit_id = $_GET['id'] ?? '';
        if (!preg_match('/^aud_[a-f0-9]{32}$/', $audit_id)) {
            echo json_encode(['success' => false, 'error' => 'ID de auditoría no válido.']);
            exit;
        }

        $report_dir = $base_dir . '/' . $audit_id;
        $status_file = $report_dir . '/status.json';

        if (!file_exists($status_file)) {
            echo json_encode(['success' => false, 'error' => 'El análisis solicitado no existe o no ha comenzado.']);
            exit;
        }

        $status_data = json_decode(file_get_contents($status_file), true);
        if (!$status_data) {
            echo json_encode(['success' => false, 'error' => 'Error al leer el estado del análisis.']);
            exit;
        }

        // Si ha terminado con éxito, inyectamos el resultado limpio
        if ($status_data['status'] === 'done') {
            $result_file = $report_dir . '/result.json';
            if (file_exists($result_file)) {
                $result_data = json_decode(file_get_contents($result_file), true);
                if ($result_data) {
                    // Omitir cualquier ruta interna absoluta del servidor en la respuesta
                    unset($result_data['internal_path']);
                    $status_data['result'] = $result_data;
                }
            }
        }

        echo json_encode($status_data);
        break;

    case 'pdf':
        $audit_id = $_GET['id'] ?? '';
        $tool = $_GET['tool'] ?? 'cookies';
        if (!preg_match('/^aud_[a-f0-9]{32}$/', $audit_id) && !preg_match('/^log_[a-f0-9]{32}$/', $audit_id)) {
            http_response_code(403);
            exit('Acceso denegado: ID no válido.');
        }

        if ($tool === 'logs') {
            $report_dir = $base_dir . '/logs';
            $pdf_file = $report_dir . '/' . $audit_id . '.pdf';
            $filename = "auditoria-logs-victor-alonso.pdf";
        } else {
            $report_dir = $base_dir . '/' . $audit_id;
            $pdf_file = $report_dir . '/informe.pdf';
            $filename = "auditoria-cookies-victor-alonso.pdf";
        }

        // Si el PDF no existe, lo generamos en el momento
        if (!file_exists($pdf_file)) {
            $node_bin = get_node_binary_path();
            $script_path = __DIR__ . '/auditor-v2/generate-pdf.js';
            
            // Ejecutar el script síncronamente y esperar (tarda ~1-2s)
            $cmd = sprintf(
                '%s %s --id=%s --tool=%s 2>&1',
                escapeshellarg($node_bin),
                escapeshellarg($script_path),
                escapeshellarg($audit_id),
                escapeshellarg($tool)
            );
            $output = shell_exec($cmd);
            
            if (!file_exists($pdf_file)) {
                http_response_code(500);
                exit('Error al generar el PDF: ' . htmlspecialchars($output));
            }
        }

        // Servir el PDF para descarga
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=3600');
        readfile($pdf_file);
        exit;

    case 'image':
        $audit_id = $_GET['id'] ?? '';
        $phase = $_GET['phase'] ?? '';

        if (!preg_match('/^aud_[a-f0-9]{32}$/', $audit_id)) {
            http_response_code(403);
            exit('Acceso denegado: ID no válido.');
        }

        $allowed_images = [
            '1' => 'fase1_inicio.png',
            '2' => 'fase2_rechazado.png',
            '3' => 'fase3_aceptado.png'
        ];

        if (!isset($allowed_images[$phase])) {
            http_response_code(404);
            exit('Fase no válida.');
        }

        // Construir y validar ruta absoluta contra Path Traversal
        $file_path = $base_dir . '/' . $audit_id . '/' . $allowed_images[$phase];
        $real_file_path = realpath($file_path);

        if (!$real_file_path || strpos($real_file_path, $base_dir) !== 0) {
            http_response_code(403);
            exit('Acceso denegado.');
        }

        if (!file_exists($real_file_path)) {
            http_response_code(404);
            exit('Imagen no encontrada.');
        }

        // Servir la imagen con caché privada
        header('Content-Type: image/png');
        header('Cache-Control: private, max-age=3600');
        readfile($real_file_path);
        exit;

    case 'send_pdf':
        $audit_id = $_GET['id'] ?? '';
        $email = trim($_POST['email'] ?? '');
        
        if (!preg_match('/^aud_[a-f0-9]{32}$/', $audit_id)) {
            echo json_encode(['success' => false, 'error' => 'ID de auditoría no válido.']);
            exit;
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Por favor, introduce un correo electrónico válido.']);
            exit;
        }
        
        $report_dir = $base_dir . '/' . $audit_id;
        $pdf_file = $report_dir . '/informe.pdf';
        
        // Si el PDF no existe, lo generamos en el momento
        if (!file_exists($pdf_file)) {
            $node_bin = get_node_binary_path();
            $script_path = __DIR__ . '/auditor-v2/generate-pdf.js';
            
            $cmd = sprintf(
                '%s %s --id=%s --tool=cookies 2>&1',
                escapeshellarg($node_bin),
                escapeshellarg($script_path),
                escapeshellarg($audit_id)
            );
            $output = shell_exec($cmd);
            
            if (!file_exists($pdf_file)) {
                echo json_encode(['success' => false, 'error' => 'No se pudo compilar el PDF de la auditoría.']);
                exit;
            }
        }
        
        // 1. Guardar el lead en CSV local (cookie_leads.csv)
        $csv_file = dirname(__DIR__) . '/data/cookie_leads.csv';
        $csv_dir = dirname($csv_file);
        if (!is_dir($csv_dir)) {
            @mkdir($csv_dir, 0755, true);
        }
        
        // Obtener el dominio auditado del result.json (si existe)
        $domain = '';
        $result_file = $report_dir . '/result.json';
        if (file_exists($result_file)) {
            $res_data = json_decode(file_get_contents($result_file), true);
            $domain = $res_data['url'] ?? '';
        }
        
        $fp = fopen($csv_file, 'a');
        if ($fp) {
            fputcsv($fp, [date('Y-m-d H:i:s'), $email, $domain, $audit_id]);
            fclose($fp);
        }
        
        // 2. Enviar a Mailrelay (Grupo 7)
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
        $subject = 'Tu informe de Auditoría de Cookies y Privacidad RGPD';
        
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
        $body_html .= "<p>He procesado con éxito la auditoría de cookies y privacidad RGPD para el sitio: <strong>" . htmlspecialchars($domain) . "</strong>.</p>";
        $body_html .= "<p>Adjunto a este correo encontrarás el informe completo en PDF con el nivel de cumplimiento detectado, la puntuación obtenida, las evidencias por fase y las recomendaciones técnicas para subsanar cualquier posible infracción de la normativa de la AEPD.</p>";
        $body_html .= "<p>Si necesitas ayuda para configurar correctamente el consentimiento de cookies, bloquear scripts de seguimiento de forma técnica o implantar un CMP conforme a la ley, no dudes en responderme a este correo.</p>";
        $body_html .= "<p>Un saludo cordial,<br><strong>Víctor Alonso SEO</strong><br><a href=\"https://www.victor-alonso.es\">victor-alonso.es</a></p>\r\n";
        
        $body .= $body_html . "\r\n";
        
        // Adjuntar PDF
        $file_size = filesize($pdf_file);
        $handle = fopen($pdf_file, "r");
        $content = fread($handle, $file_size);
        fclose($handle);
        $encoded_content = chunk_split(base64_encode($content));
        
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: application/pdf; name=\"auditoria-cookies-rgpd.pdf\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"auditoria-cookies-rgpd.pdf\"\r\n\r\n";
        $body .= $encoded_content . "\r\n";
        $body .= "--$boundary--";
        
        if (mail($to, $subject, $body, $headers)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo enviar el email con la configuración del servidor.']);
        }
        exit;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
        break;
}
