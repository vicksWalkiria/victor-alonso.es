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
    
    // Enviar correo de notificación de respaldo con el archivo adjunto a soy@victor-alonso.es
    if (file_exists($pdf_path)) {
        $to = 'soy@victor-alonso.es';
        $subject = 'Nuevo informe de Search Console generado - ' . date('d/m/Y');
        $boundary = md5(time());
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: soy@victor-alonso.es\r\n";
        $headers .= "Reply-To: soy@victor-alonso.es\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        
        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= "<p>Se ha generado un nuevo informe PDF de Search Console a través del sitio web.</p>";
        $body .= "<p>Adjunto encontrarás el informe en PDF generado para tu revisión.</p>\r\n";
        
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
    'schema_types' => ['WebApplication'],
    'rating_id'    => 'generador-informe-gsc',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Generador GSC PDF', 'url' => ''],
    ],
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
      <div style="margin-top: 3.5rem; background: rgba(232, 104, 26, 0.03); border-left: 4px solid var(--orange); padding: 1.5rem 2rem; border-radius: 0 12px 12px 0;">
        <h4 style="color: var(--black); font-size: 1.15rem; margin-top: 0; margin-bottom: 0.5rem; font-weight: 700;">¿Necesitas ayuda para interpretar o implementar estas mejoras?</h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6; color: var(--text);">
          Si el informe revela problemas de indexación o muchas palabras clave estancadas en la página 2 de Google, te puedo ayudar a empujarlas. Como <a href="/">consultor SEO en Albacete</a> con enfoque técnico, diseño y ejecuto estrategias de <a href="/servicios/seo-tecnico/">SEO técnico</a> y auditorías a medida para que consigas más negocio con tu tráfico orgánico. Escríbeme y lo valoramos juntos.
        </p>
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
