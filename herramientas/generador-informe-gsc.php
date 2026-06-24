<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/schema.php';
require_once __DIR__ . '/../includes/ratings-helper.php';

// ─── 1. Limpieza periódica automática de archivos temporales (antigüedad > 1 hora) ───
$gsc_reports_dir = BASE_DIR . "/data/reports/gsc";
if (is_dir($gsc_reports_dir)) {
    $dirs = glob("$gsc_reports_dir/tmp_*");
    $now = time();
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            if ($now - filemtime($dir) > 3600) {
                // Eliminar archivos internos
                $files = glob("$dir/*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($dir);
            }
        }
    }
}

// ─── 1.5. Controlador AJAX de Valoraciones (Ratings) ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
    header('Content-Type: application/json');
    $tool_id = trim($_POST['tool_id'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    
    if (isset($_COOKIE['voted_' . $tool_id])) {
        echo json_encode(['success' => false, 'message' => 'Ya has valorado esta herramienta.']);
        exit;
    }
    
    $res = save_vote($tool_id, $rating);
    if ($res) {
        setcookie('voted_' . $tool_id, '1', time() + (365 * 24 * 60 * 60), '/');
        echo json_encode([
            'success' => true,
            'count' => $res['count'],
            'average' => $res['average']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar valoración.']);
    }
    exit;
}

// ─── 2. Acción de descarga directa del PDF ───
if (isset($_GET['download'])) {
    $hash = preg_replace('/[^a-f0-9]/', '', $_GET['download']);
    if (strlen($hash) === 32) {
        $pdf_path = BASE_DIR . "/data/reports/gsc/tmp_$hash/report.pdf";
        $dir_path = BASE_DIR . "/data/reports/gsc/tmp_$hash";
        
        if (file_exists($pdf_path)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="informe-gsc-auditoria-' . date('Y-m-d') . '.pdf"');
            header('Content-Length: ' . filesize($pdf_path));
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            readfile($pdf_path);
            
            // Borrado inmediato tras descarga exitosa para máxima privacidad
            if (is_dir($dir_path)) {
                $files = glob("$dir_path/*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($dir_path);
            }
            exit;
        } else {
            http_response_code(404);
            $error_msg = "El informe solicitado no existe, ya expiró o ya ha sido descargado. Por motivos de privacidad, los archivos se eliminan permanentemente tras la descarga.";
        }
    } else {
        http_response_code(400);
        $error_msg = "Identificador de informe inválido.";
    }
}

// ─── 3. Controlador AJAX de Subida y Procesamiento ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process') {
    header('Content-Type: application/json');
    
    if (!isset($_FILES['gsc_zip']) || $_FILES['gsc_zip']['error'] !== UPLOAD_ERR_OK) {
        $upload_err = isset($_FILES['gsc_zip']) ? $_FILES['gsc_zip']['error'] : -1;
        echo json_encode(['success' => false, 'error' => "Error en la subida del archivo (Código: $upload_err). Asegúrate de que el archivo no excede el límite permitido del servidor."]);
        exit;
    }
    
    $file_tmp = $_FILES['gsc_zip']['tmp_name'];
    $file_name = $_FILES['gsc_zip']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if ($file_ext !== 'zip') {
        echo json_encode(['success' => false, 'error' => 'Formato no soportado. Debes subir un archivo ZIP exportado de Google Search Console.']);
        exit;
    }
    
    // Crear directorio temporal único y seguro
    $hash = bin2hex(random_bytes(16));
    $temp_user_dir = BASE_DIR . "/data/reports/gsc/tmp_$hash";
    if (!is_dir($temp_user_dir)) {
        mkdir($temp_user_dir, 0755, true);
    }
    
    $zip_path = "$temp_user_dir/upload.zip";
    $pdf_path = "$temp_user_dir/report.pdf";
    
    if (!move_uploaded_file($file_tmp, $zip_path)) {
        echo json_encode(['success' => false, 'error' => 'No se pudo guardar el archivo ZIP temporal en el servidor.']);
        exit;
    }
    
    // Ejecutar el motor de Python
    $engine_path = __DIR__ . '/gsc-report-engine/engine.py';
    $cmd = "python3 " . escapeshellarg($engine_path) . " " . escapeshellarg($zip_path) . " " . escapeshellarg($pdf_path) . " 2>&1";
    
    exec($cmd, $cmd_output, $return_var);
    
    if ($return_var !== 0) {
        // Limpiar directorio temporal ante fallos
        $files = glob("$temp_user_dir/*");
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        rmdir($temp_user_dir);
        
        $error_detail = implode("\n", $cmd_output);
        error_log("GSC Generator Error: " . $error_detail);
        echo json_encode(['success' => false, 'error' => 'Fallo al procesar el archivo o compilar el informe LaTeX. Asegúrate de que el ZIP contiene los CSV de GSC correctos.', 'debug' => $error_detail]);
        exit;
    }
    
    // Procesar email de contacto si fue suministrado
    $user_email = isset($_POST['user_email']) ? trim($_POST['user_email']) : '';
    $user_email = filter_var($user_email, FILTER_VALIDATE_EMAIL);

    if ($user_email) {
        // Guardar email en base de datos CSV local gratis y segura
        $leads_file = BASE_DIR . "/data/gsc_leads.csv";
        $dir = dirname($leads_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file_exists = file_exists($leads_file);
        $fp = fopen($leads_file, 'a');
        if ($fp) {
            if (!$file_exists) {
                fputcsv($fp, ['Fecha', 'Email', 'Archivo ZIP']);
            }
            fputcsv($fp, [date('Y-m-d H:i:s'), $user_email, $file_name]);
            fclose($fp);
        }

        // --- Conexión con Mailrelay API ---
        $mailrelay_key = $_ENV['MAILRELAY_API_KEY'] ?? getenv('MAILRELAY_API_KEY') ?? '';
        if (!empty($mailrelay_key)) {
            $url = 'https://walkiriaapps.ipzmarketing.com/api/v1/subscribers';
            $data = [
                'email' => $user_email,
                'status' => 'active',
                'group_ids' => [7]
            ];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-AUTH-TOKEN: ' . $mailrelay_key
            ]);
            // Ejecutar con un timeout bajo para no bloquear el flujo del usuario
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            curl_close($ch);
        }
    }
    
    // Enviar correo de notificación de respaldo con el archivo adjunto
    if (file_exists($pdf_path)) {
        $to = 'soy@victor-alonso.es';
        $subject = 'Nuevo informe de Search Console generado - ' . date('d/m/Y');
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: Víctor Alonso SEO <soy@victor-alonso.es>\r\n";
        $headers .= "Reply-To: soy@victor-alonso.es\r\n";
        
        if ($user_email) {
            $to = $user_email;
            $subject = 'Tu informe de rendimiento SEO de Google Search Console está listo';
            $headers .= "Bcc: soy@victor-alonso.es\r\n";
        }
        
        $boundary = md5(time());
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        
        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        
        if ($user_email) {
            $body .= "<p>Hola,</p>";
            $body .= "<p>He preparado y compilado con éxito el informe SEO solicitado a partir de los datos de Google Search Console.</p>";
            $body .= "<p>Adjunto a este correo encontrarás el documento PDF con todos tus indicadores clave (KPIs), tendencias de rendimiento y el listado de tus palabras clave oportunidad en la página 2 de Google.</p>";
            $body .= "<p>Espero que te resulte de gran valor para optimizar la visibilidad orgánica de tu proyecto.</p>";
            $body .= "<p>Un saludo cordial,<br><strong>Víctor Alonso SEO</strong><br><a href=\"https://www.victor-alonso.es\">victor-alonso.es</a></p>\r\n";
        } else {
            $body .= "<p>Se ha generado un nuevo informe PDF de Search Console a través del sitio web.</p>";
            $body .= "<p>Adjunto encontrarás el informe en PDF generado para tu revisión.</p>\r\n";
        }
        
        $file_size = filesize($pdf_path);
        $handle = fopen($pdf_path, "r");
        $content = fread($handle, $file_size);
        fclose($handle);
        $encoded_content = chunk_split(base64_encode($content));
        
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: application/pdf; name=\"informe-gsc-auditoria.pdf\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"informe-gsc-auditoria.pdf\"\r\n\r\n";
        $body .= $encoded_content . "\r\n";
        $body .= "--$boundary--";
        
        mail($to, $subject, $body, $headers);
    }
    
    // Retornar éxito con el hash de descarga
    echo json_encode(['success' => true, 'download_url' => "?download=$hash"]);
    exit;
}

// Configuración de metadatos de la página
$page = page_config([
    'title'        => 'Generador de Informes GSC PDF Profesional | Herramientas SEO',
    'description'  => 'Sube el ZIP exportado de Google Search Console y obtén un informe de rendimiento y auditoría SEO en PDF maquetado profesionalmente con LaTeX y gráficas vectoriales.',
    'canonical'    => '/herramientas/generador-informe-gsc/',
    'body_class'   => 'page-tool-gsc',
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'generador-informe-gsc',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Generador GSC PDF', 'url' => ''],
    ],
    'faq_items'    => [
        [
            'q' => '¿Qué tipo de análisis realiza este generador de informes?',
            'a' => 'Esta herramienta ofrece una auditoría básica del rendimiento orgánico de tu sitio a partir del archivo ZIP exportado de Google Search Console. Analiza métricas globales, CTR promedio, tendencias de clics frente a impresiones y palabras clave oportunidad posicionadas en la página 2 de Google.'
        ],
        [
            'q' => '¿Este informe sustituye a una auditoría SEO profesional?',
            'a' => 'No. Este informe automatizado sirve como un diagnóstico inicial y orientación rápida del estado de tu web. Una auditoría SEO profesional realizada por un consultor experto implica un análisis exhaustivo de intenciones de búsqueda, arquitectura web detallada, enlazado interno estratégico, rastreabilidad, optimización de velocidad de carga (WPO) y un plan de acción a medida adaptado al negocio.'
        ],
        [
            'q' => '¿Es seguro subir mis datos de Search Console aquí?',
            'a' => 'Totalmente. El archivo ZIP y el PDF resultante se procesan de forma efímera. Los archivos se eliminan de forma permanente e inmediata en cuanto finaliza tu descarga, y se destruyen por completo en 60 minutos si no se descargan, garantizando la privacidad de tus datos.'
        ]
    ]
]);

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/breadcrumbs.php';
?>

<main id="main">
  <section class="page-hero" aria-labelledby="gsc-h1">
    <div class="container">
      <span class="page-hero-eyebrow">Herramientas SEO Gratuitas</span>
      <h1 id="gsc-h1">Generador de Informes PDF de Google Search Console</h1>
      <p class="page-hero-desc">Sube el archivo ZIP completo exportado de Google Search Console y te generaré una auditoría en PDF maquetada profesionalmente con LaTeX, gráficas de tendencia vectoriales y listados de palabras clave oportunidad.</p>
    </div>
  </section>

  <section class="section">
    <div class="container" style="max-width: 800px;">
      
      <!-- Cartel de Privacidad -->
      <div style="background: rgba(232, 104, 26, 0.05); border: 1px solid rgba(232, 104, 26, 0.2); padding: 1.5rem; border-radius: 12px; margin-bottom: 2.5rem; display: flex; gap: 1.25rem; align-items: flex-start; box-shadow: 0 4px 12px rgba(232, 104, 26, 0.02);">
        <i class="fa-solid fa-shield-halved" style="color: var(--orange); font-size: 1.75rem; margin-top: 0.2rem;"></i>
        <div>
          <h3 style="color: var(--black); font-size: 1.1rem; margin-top: 0; margin-bottom: 0.5rem; font-weight: 700;">🔒 No me quedo con tus datos</h3>
          <p style="margin: 0; font-size: 0.95rem; line-height: 1.6; color: var(--text);">
            No almaceno tus datos de búsqueda ni me quedo con tus archivos. El ZIP que subas, las tablas de datos procesadas y el documento PDF resultante <strong>se eliminarán de manera inmediata y permanente</strong> del servidor en cuanto finalice tu descarga. Si por algún motivo no descargas el informe, los datos temporales se autodestruirán automáticamente pasados 60 minutos.
          </p>
        </div>
      </div>

      <?php if (isset($error_msg)): ?>
        <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 1.25rem; border-radius: 8px; margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.5;">
          <i class="fa-solid fa-circle-exclamation" style="margin-right: 0.5rem;"></i> <?= h($error_msg) ?>
        </div>
      <?php endif; ?>

      <!-- Área del Formulario -->
      <div class="card" style="padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);">
        <h2 style="font-size: 1.4rem; margin-bottom: 1rem; color: var(--black);">Sube tu exportación de GSC</h2>
        <p style="font-size: 0.95rem; line-height: 1.6; color: var(--muted); margin-bottom: 2rem;">
          Para que pueda generar tu informe, ve a tu panel de Google Search Console, selecciona el periodo de tiempo deseado y haz clic en el botón <strong>"Exportar"</strong> (arriba a la derecha), eligiendo la opción <strong>"Descargar archivo ZIP"</strong>. Sube ese mismo archivo aquí sin descomprimir.
        </p>

        <form id="gsc-upload-form" method="POST" enctype="multipart/form-data">
          <!-- Dropzone -->
          <div id="dropzone" style="border: 2px dashed rgba(34, 49, 63, 0.2); border-radius: 12px; padding: 3rem 1.5rem; text-align: center; background: var(--lightgray); cursor: pointer; transition: all 0.25s ease; position: relative;">
            <input type="file" id="gsc_zip" name="gsc_zip" accept=".zip" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
            <div id="dropzone-prompt">
              <i class="fa-solid fa-file-zipper" style="font-size: 3rem; color: var(--orange); margin-bottom: 1.25rem; display: block;"></i>
              <span style="font-size: 1.1rem; font-weight: 700; color: var(--black); display: block; margin-bottom: 0.5rem;">Arrastra tu archivo ZIP aquí</span>
              <span style="font-size: 0.9rem; color: var(--muted);">o haz clic para explorar en tu equipo</span>
            </div>
            <div id="file-info" style="display: none;">
              <i class="fa-solid fa-circle-check" style="font-size: 3rem; color: #10b981; margin-bottom: 1.25rem; display: block;"></i>
              <span id="selected-file-name" style="font-size: 1.1rem; font-weight: 700; color: var(--black); display: block; margin-bottom: 0.5rem;">archivo.zip</span>
              <span style="font-size: 0.9rem; color: var(--muted);">Listo para procesar. Haz clic para cambiarlo.</span>
            </div>
          </div>

          <!-- Campo de Email (Opcional) -->
          <div style="margin-top: 1.75rem; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 1.5rem;">
            <label for="user_email" style="display: block; font-weight: 700; color: var(--black); margin-bottom: 0.5rem; font-size: 0.95rem;">
              📧 ¿Quieres recibir el PDF en tu correo? (Opcional)
            </label>
            <input type="email" id="user_email" name="user_email" placeholder="ejemplo@tuweb.com" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(34, 49, 63, 0.2); border-radius: 8px; font-size: 0.95rem; background: #fff; color: var(--black); transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--orange)'" onblur="this.style.borderColor='rgba(34,49,63,0.2)'">
            <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: var(--muted); line-height: 1.4;">
              Si indicas tu email, te enviaré una copia del informe PDF directamente a tu bandeja de entrada en cuanto finalice el procesamiento.
            </p>
          </div>

          <div style="margin-top: 2rem; display: flex; justify-content: center;">
            <button type="submit" id="btn-submit" class="btn btn--primary btn--lg" style="min-width: 250px; justify-content: center;" disabled>
              <i class="fa-solid fa-gears" style="margin-right: 0.5rem;"></i> Generar Informe PDF
            </button>
          </div>
        </form>

        <!-- Bloque de Carga / Spinner -->
        <div id="loading-overlay" style="display: none; margin-top: 2rem; text-align: center; padding: 2rem 0; border-top: 1px solid var(--bordergray);">
          <div style="display: inline-block; width: 3rem; height: 3rem; border: 4px solid rgba(232, 104, 26, 0.1); border-radius: 50%; border-top-color: var(--orange); animation: spin 1s linear infinite; margin-bottom: 1.5rem;"></div>
          <h3 id="loading-status" style="font-size: 1.2rem; color: var(--black); margin-bottom: 0.5rem; font-weight: 700;">Subiendo archivo ZIP...</h3>
          <p id="loading-desc" style="font-size: 0.95rem; color: var(--muted); margin: 0; max-width: 450px; margin-left: auto; margin-right: auto;">Este proceso suele tardar de 5 a 15 segundos debido a la compilación en tiempo real del documento LaTeX.</p>
        </div>
      </div>

      <!-- Guía Explicativa del Funcionamiento -->
      <div style="margin-top: 4rem;">
        <h2 style="font-size: 1.5rem; color: var(--black); margin-bottom: 1.5rem;">¿Qué analiza este informe de rendimiento?</h2>
        <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
          <div class="card" style="padding: 1.5rem; border-radius: 8px;">
            <h3 style="font-size: 1.1rem; color: var(--black); margin-top: 0; margin-bottom: 0.75rem;"><i class="fa-solid fa-chart-line" style="color: var(--orange); margin-right: 0.5rem;"></i> KPIs y Tendencias Mensuales</h3>
            <p style="margin: 0; font-size: 0.9rem; line-height: 1.6; color: var(--text);">Agrupo las métricas del periodo para calcular tu CTR ponderado y la evolución mensual de clics frente a impresiones con gráficas vectoriales.</p>
          </div>
          <div class="card" style="padding: 1.5rem; border-radius: 8px;">
            <h3 style="font-size: 1.1rem; color: var(--black); margin-top: 0; margin-bottom: 0.75rem;"><i class="fa-solid fa-arrow-trend-up" style="color: var(--orange); margin-right: 0.5rem;"></i> Oportunidades en Página 2</h3>
            <p style="margin: 0; font-size: 0.9rem; line-height: 1.6; color: var(--text);">Extraigo las consultas con alto volumen de impresiones situadas entre los puestos 11 y 20, listas para subir a primera página con pequeños cambios On-Page.</p>
          </div>
          <div class="card" style="padding: 1.5rem; border-radius: 8px;">
            <h3 style="font-size: 1.1rem; color: var(--black); margin-top: 0; margin-bottom: 0.75rem;"><i class="fa-solid fa-mobile-screen" style="color: var(--orange); margin-right: 0.5rem;"></i> Distribución de Dispositivos</h3>
            <p style="margin: 0; font-size: 0.9rem; line-height: 1.6; color: var(--text);">Comparo la visibilidad y tasa de clics entre ordenadores, móviles y tablets para guiar la optimización de fragmentos y el WPO móvil.</p>
          </div>
        </div>
      </div>

      <!-- Bloque de Conversión / CTA de rigor -->
      <div style="margin-top: 3.5rem; background: rgba(232, 104, 26, 0.03); border-left: 4px solid var(--orange); padding: 2rem; border-radius: 0 12px 12px 0;">
        <h4 style="color: var(--black); font-size: 1.25rem; margin-top: 0; margin-bottom: 0.75rem; font-weight: 700;">¿Necesitas ayuda para interpretar o implementar estas mejoras?</h4>
        <p style="margin: 0 0 1.5rem 0; font-size: 0.95rem; line-height: 1.6; color: var(--text);">
          Si el informe revela problemas de indexación o muchas palabras clave estancadas en la página 2 de Google, te puedo ayudar a empujarlas. Como <a href="/">consultor SEO en Albacete</a> con enfoque técnico, diseño y ejecuto estrategias de <a href="/servicios/seo-tecnico/">SEO técnico</a> y auditorías a medida para que consigas más negocio con tu tráfico orgánico. Escríbeme y lo valoramos juntos.
        </p>
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
          <a href="/contacto/" class="btn btn--primary btn--lg" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; text-decoration: none; min-width: 220px; font-weight: 700;">
            <i class="fa-solid fa-envelope"></i> Contactar ahora
          </a>
          <a href="https://wa.me/<?= SITE_PHONE_RAW ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp btn--lg" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; text-decoration: none; min-width: 220px; font-weight: 700;">
            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.2rem;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg> Hablar por WhatsApp
          </a>
        </div>
      </div>

      <!-- Bloque de Preguntas Frecuentes (FAQ) -->
      <div style="margin-top: 4rem; border-top: 1px solid var(--bordergray); padding-top: 3rem;">
        <h2 style="font-size: 1.6rem; color: var(--black); margin-bottom: 2.5rem; text-align: center; font-weight: 700;">Preguntas Frecuentes sobre el Generador de Informes GSC</h2>
        
        <div style="display: flex; flex-direction: column; gap: 1.5rem; max-width: 800px; margin: 0 auto 3rem auto;">
          <?php foreach ($page['faq_items'] as $item): ?>
            <div class="card" style="padding: 1.75rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid var(--bordergray); background: var(--white);">
              <h3 style="font-size: 1.15rem; color: var(--black); margin-top: 0; margin-bottom: 0.75rem; font-weight: 700; display: flex; gap: 0.5rem; align-items: flex-start; line-height: 1.4;">
                <span style="color: var(--orange); font-weight: 800;">¿</span><?= h($item['q']) ?>
              </h3>
              <p style="margin: 0; font-size: 0.95rem; line-height: 1.65; color: var(--text);">
                <?= h($item['a']) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Sistema de Valoraciones (Reviews) -->
      <?php render_rating_widget('generador-informe-gsc', '¿Te ha sido útil este generador de informes GSC PDF?'); ?>

    </div>
  </section>
</main>

<style>
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
#dropzone.dragover {
  border-color: var(--orange) !important;
  background: rgba(232, 104, 26, 0.03) !important;
  box-shadow: 0 0 15px rgba(232, 104, 26, 0.1) inset;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('gsc_zip');
    const dropzonePrompt = document.getElementById('dropzone-prompt');
    const fileInfo = document.getElementById('file-info');
    const selectedFileName = document.getElementById('selected-file-name');
    const btnSubmit = document.getElementById('btn-submit');
    const form = document.getElementById('gsc-upload-form');
    const loadingOverlay = document.getElementById('loading-overlay');
    const loadingStatus = document.getElementById('loading-status');
    
    const statusPhases = [
        "Subiendo archivo ZIP...",
        "Extrayendo datos de Search Console...",
        "Analizando clics e impresiones...",
        "Identificando oportunidades de página 2...",
        "Maquetando documento LaTeX...",
        "Compilando reporte PDF..."
    ];
    
    let statusInterval;

    // Manejar Drag & Drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            dropzone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            dropzone.classList.remove('dragover');
        }, false);
    });

    // Detectar selección de archivo
    fileInput.addEventListener('change', function() {
        handleFileSelect(fileInput.files[0]);
    });

    function handleFileSelect(file) {
        if (file) {
            if (file.name.toLowerCase().endsWith('.zip')) {
                selectedFileName.textContent = file.name + ' (' + formatBytes(file.size) + ')';
                dropzonePrompt.style.display = 'none';
                fileInfo.style.display = 'block';
                btnSubmit.disabled = false;
            } else {
                alert('Por favor, selecciona un archivo comprimido .zip válido.');
                resetForm();
            }
        }
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = 2;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    function resetForm() {
        fileInput.value = '';
        const emailInput = document.getElementById('user_email');
        if (emailInput) emailInput.value = '';
        dropzonePrompt.style.display = 'block';
        fileInfo.style.display = 'none';
        btnSubmit.disabled = true;
    }

    // Envío del formulario vía AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const file = fileInput.files[0];
        if (!file) return;

        // Desactivar UI y mostrar overlay de carga
        btnSubmit.disabled = true;
        dropzone.style.pointerEvents = 'none';
        loadingOverlay.style.display = 'block';
        
        let phaseIdx = 0;
        loadingStatus.textContent = statusPhases[phaseIdx];
        
        // Simular fases de procesamiento para amenizar la espera técnica
        statusInterval = setInterval(() => {
            if (phaseIdx < statusPhases.length - 1) {
                phaseIdx++;
                loadingStatus.textContent = statusPhases[phaseIdx];
            }
        }, 2200);

        const formData = new FormData();
        formData.append('gsc_zip', file);
        formData.append('action', 'process');
        
        const emailInput = document.getElementById('user_email');
        if (emailInput && emailInput.value.trim() !== '') {
            formData.append('user_email', emailInput.value.trim());
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.pathname, true);
        
        xhr.onload = function() {
            clearInterval(statusInterval);
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success && response.download_url) {
                        loadingStatus.innerHTML = '<span style="color:#10b981;"><i class="fa-solid fa-circle-check"></i> ¡Informe compilado con éxito!</span>';
                        loadingStatus.nextElementSibling.textContent = "Iniciando descarga del PDF. Los archivos temporales se han borrado del servidor de forma segura.";
                        
                        // Redirigir para forzar la descarga del PDF
                        window.location.href = response.download_url;
                        
                        setTimeout(() => {
                            resetForm();
                            dropzone.style.pointerEvents = 'auto';
                            loadingOverlay.style.display = 'none';
                        }, 5000);
                    } else {
                        alert(response.error || 'Ocurrió un error inesperado al procesar los datos de Search Console.');
                        enableFormUI();
                    }
                } catch(err) {
                    console.error("JSON Parse Error:", xhr.responseText);
                    alert('Error en la respuesta del servidor. Inténtalo de nuevo más tarde.');
                    enableFormUI();
                }
            } else {
                alert('Error de conexión con el servidor (Código de estado: ' + xhr.status + ').');
                enableFormUI();
            }
        };

        xhr.onerror = function() {
            clearInterval(statusInterval);
            alert('Error de red. Asegúrate de tener conexión y de que el archivo ZIP no excede las limitaciones de subida.');
            enableFormUI();
        };

        xhr.send(formData);
    });

    function enableFormUI() {
        btnSubmit.disabled = false;
        dropzone.style.pointerEvents = 'auto';
        loadingOverlay.style.display = 'none';
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
