<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';

// Interceptar acción AJAX de votación
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

/**
 * Comprueba si una URL es potencialmente insegura o privada (prevención de SSRF)
 */
function is_unsafe_url($url) {
    $parsed = parse_url($url);
    if (!$parsed || empty($parsed['host'])) {
        return true;
    }

    // Permitir exclusivamente esquemas http y https
    $scheme = strtolower($parsed['scheme'] ?? '');
    if ($scheme !== 'http' && $scheme !== 'https') {
        return true;
    }

    // Bloquear puertos no estándar (solo 80 y 443 permitidos)
    $port = $parsed['port'] ?? null;
    if ($port !== null && !in_array((int)$port, [80, 443], true)) {
        return true;
    }

    $host = trim($parsed['host']);
    
    // Eliminar corchetes si es una dirección IPv6 directa
    if (strpos($host, '[') === 0 && substr($host, -1) === ']') {
        $host = substr($host, 1, -1);
    }
    
    // Bloquear localhost y loopback explícitamente
    if (in_array(strtolower($host), ['localhost', 'localhost.localdomain'])) {
        return true;
    }

    $ips = [];
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $ips[] = $host;
    } else {
        // Resolver registros DNS A y AAAA
        $records_a = @dns_get_record($host, DNS_A);
        if (is_array($records_a)) {
            foreach ($records_a as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }
        $records_aaaa = @dns_get_record($host, DNS_AAAA);
        if (is_array($records_aaaa)) {
            foreach ($records_aaaa as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }
        
        // Fallback a gethostbyname para IPv4
        if (empty($ips)) {
            $ip_resolved = gethostbyname($host);
            if ($ip_resolved && $ip_resolved !== $host) {
                $ips[] = $ip_resolved;
            }
        }
    }

    if (empty($ips)) {
        return true; // Si no se puede resolver ninguna IP, bloquear por precaución
    }

    foreach ($ips as $ip) {
        // Verificar si la IP es pública (no privada ni reservada)
        $is_valid_public = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
        if (!$is_valid_public) {
            return true; // IP privada o de rango reservado detectada
        }
    }

    return false;
}

/**
 * Controla el límite de solicitudes por IP (10 solicitudes en 10 minutos) y guarda el log de peticiones.
 */
function check_rate_limit_and_log($url_audited) {
    $log_file = dirname(__DIR__) . '/data/auditor_logs.json';
    $dir = dirname($log_file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $parsed_log_url = parse_url($url_audited);
    $safe_logged_url = ($parsed_log_url['scheme'] ?? 'https') . '://' . ($parsed_log_url['host'] ?? '') . ($parsed_log_url['path'] ?? '/');

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    // Ofuscar IP con hash md5 y salt para cumplir con GDPR
    $ip_hash = md5($ip . 'cookie_audit_salt_55');
    $now = time();

    $logs = [];
    if (file_exists($log_file)) {
        $logs = json_decode(@file_get_contents($log_file), true) ?: [];
    }

    $cleaned_logs = [];
    $ip_count = 0;
    $domain_last_request_time = 0;

    $parsed_current = parse_url($safe_logged_url);
    $current_domain = strtolower($parsed_current['host'] ?? '');

    foreach ($logs as $entry) {
        // Conservar histórico de las últimas 24 horas en el log, pero usar 10 min para rate limit
        if ($now - $entry['timestamp'] < 86400) {
            $cleaned_logs[] = $entry;
        }
        
        if ($now - $entry['timestamp'] < 600) {
            if ($entry['ip_hash'] === $ip_hash) {
                $ip_count++;
            }
            
            $parsed_entry = parse_url($entry['url']);
            $entry_domain = strtolower($parsed_entry['host'] ?? '');
            if ($entry_domain === $current_domain && $entry['ip_hash'] === $ip_hash) {
                if ($entry['timestamp'] > $domain_last_request_time) {
                    $domain_last_request_time = $entry['timestamp'];
                }
            }
        }
    }

    // Validar límite de tasa
    if ($ip_count >= 10) {
        return 'Has superado el límite de análisis permitido (10 solicitudes cada 10 minutos). Por favor, espera un momento.';
    }

    // Validar anti-loop de análisis del mismo dominio
    if ($domain_last_request_time > 0 && ($now - $domain_last_request_time) < 10) {
        return 'Por favor, espera al menos 10 segundos antes de volver a analizar el mismo dominio.';
    }

    // Registrar solicitud
    $cleaned_logs[] = [
        'ip_hash'   => $ip_hash,
        'timestamp' => $now,
        'url'       => $safe_logged_url,
        'error'     => ''
    ];

    @file_put_contents($log_file, json_encode($cleaned_logs, JSON_PRETTY_PRINT), LOCK_EX);
    return true;
}

/**
 * Registra un error de conexión técnica en el log para diagnóstico interno
 */
function log_audit_error($url_audited, $error_msg) {
    $log_file = dirname(__DIR__) . '/data/auditor_logs.json';
    if (!file_exists($log_file)) return;

    $parsed_log_url = parse_url($url_audited);
    $safe_logged_url = ($parsed_log_url['scheme'] ?? 'https') . '://' . ($parsed_log_url['host'] ?? '') . ($parsed_log_url['path'] ?? '/');

    $logs = json_decode(@file_get_contents($log_file), true) ?: [];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ip_hash = md5($ip . 'cookie_audit_salt_55');

    // Buscar la última petición de esta IP y actualizar el mensaje de error
    for ($i = count($logs) - 1; $i >= 0; $i--) {
        if ($logs[$i]['ip_hash'] === $ip_hash && $logs[$i]['url'] === $safe_logged_url) {
            $logs[$i]['error'] = $error_msg;
            break;
        }
    }

    @file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT), LOCK_EX);
}

$error = null;
$result = null;
$url = '';

// Procesar el análisis si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = trim($_POST['url'] ?? '');
    
    if (empty($url)) {
        $error = 'Por favor, introduce una URL válida.';
    } else {
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $error = 'El formato de la URL no es válido.';
        } elseif (is_unsafe_url($url)) {
            $error = 'La dirección URL introducida está restringida o no es segura.';
        } else {
            // Verificar límite de tasa y registrar log
            $rate_limit_res = check_rate_limit_and_log($url);
            if ($rate_limit_res !== true) {
                $error = $rate_limit_res;
            } elseif (isset($_POST['audit_id']) && preg_match('/^aud_[a-f0-9]{32}$/', $_POST['audit_id'])) {
                // Modo Avanzado: Leer resultado de Puppeteer
                $audit_id = $_POST['audit_id'];
                $result_file = dirname(__DIR__) . '/data/reports/' . $audit_id . '/result.json';
                if (file_exists($result_file)) {
                    $result_v2 = json_decode(file_get_contents($result_file), true);
                    if (!$result_v2 || $result_v2['status'] !== 'done') {
                        $error = 'Hubo un problema procesando el informe avanzado. Por favor, inténtalo de nuevo.';
                    }
                } else {
                    $error = 'El informe avanzado solicitado no existe o ha expirado.';
                }
            } else {
                $error = 'El análisis requiere Javascript para ejecutar la simulación de navegador en vivo.';
            }
        } // Fin de is_unsafe_url
    } // Fin de empty($url)
} // Fin de REQUEST_METHOD == POST

$page = page_config([
    'title'        => 'Auditor de Cookies RGPD y Privacidad Online',
    'description'  => 'Audita online si tu web cumple con el RGPD y la AEPD. Detecta cookies de analítica, marketing e iframes cargados antes del consentimiento.',
    'canonical'    => '/herramientas/auditor-cookies/',
    'body_class'   => 'page-herramientas-auditor-cookies',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Auditor de Cookies', 'url' => ''],
    ],
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'auditor-cookies',
    'faq_items'    => [
        [
            'q' => '¿Cómo funciona el Auditor de Cookies?',
            'a' => 'El auditor realiza peticiones cURL simulando una primera visita sin consentimiento previo. Analiza las cabeceras HTTP Set-Cookie, las etiquetas script incrustadas, iframes de terceros y conexiones externas para diagnosticar qué recursos se cargan antes de que el usuario interactúe con el banner de consentimiento.'
        ],
        [
            'q' => '¿Qué cookies se consideran seguras para cargar antes del consentimiento?',
            'a' => 'Únicamente las cookies estrictamente necesarias para el funcionamiento técnico de la web (como guardar el estado de la sesión o las preferencias de idioma/consentimiento). Las cookies de analítica (Google Analytics, Hotjar) y marketing (Facebook Pixel, TikTok Pixel) requieren consentimiento explícito antes de ser depositadas.'
        ],
        [
            'q' => '¿Por qué mi web tiene un Score bajo si tengo banner de cookies?',
            'a' => 'Muchos banners o CMPs de cookies son meramente estéticos (visuales) pero no bloquean la ejecución técnica real de los scripts en segundo plano. Si las etiquetas de Google Tag Manager o GA4 se cargan directamente en el HTML de tu sitio sin tipo "text/plain" o sin control condicional, seguirán instalando cookies de seguimiento en la primera carga.'
        ],
        [
            'q' => '¿Es obligatorio ofrecer un botón de rechazar cookies?',
            'a' => 'Sí. Conforme a las últimas directrices de la AEPD y el Reglamento General de Protección de Datos (RGPD), las páginas web deben ofrecer la opción de rechazar todas las cookies no necesarias al mismo nivel de visibilidad y con la misma facilidad que la opción de aceptarlas. No se permiten botones escondidos o flujos complejos para el rechazo.'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">
  <section class="page-hero" aria-labelledby="tool-h1">
    <div class="container">
      <h1 id="tool-h1">Auditor de Cookies RGPD: <span>comprueba si tu web carga cookies antes de aceptar</span></h1>
      <p class="page-hero-desc">Audita tu web en vivo. Mi bot analiza si tu página respeta los estándares del RGPD/LOPDGDD bloqueando correctamente los scripts de seguimiento y las cookies de terceros antes de que el usuario haga clic en aceptar.</p>
    </div>
  </section>



  <section class="section">
    <div class="container">
      
      <!-- Explicación del Auditor de Cookies -->
      <div style="max-width:800px; margin:0 auto 2.5rem auto; color:#cbd5e1; font-size:0.95rem; line-height:1.6;">
        <p style="margin-bottom:1rem; font-weight:600; color:#fff; text-align:center;">¿Qué comprueba esta herramienta online?</p>
        <ul class="tool-intro-grid">
          <li class="tool-intro-grid__item">🔍 <strong>Inyección de Cookies:</strong> Analiza si se depositan cookies no técnicas en la primera carga sin consentimiento.</li>
          <li class="tool-intro-grid__item">🚀 <strong>Bloqueo de Scripts:</strong> Verifica si etiquetas como Google Tag Manager, GA4 o Facebook Pixel se ejecutan directamente.</li>
          <li class="tool-intro-grid__item">📺 <strong>Iframes de Terceros:</strong> Detecta la carga de reproductores de vídeo, mapas o widgets antes de su aceptación.</li>
          <li class="tool-intro-grid__item">🛡️ <strong>Estructura Legal:</strong> Comprueba la presencia de enlaces visibles a la Política de Cookies, Privacidad y Aviso Legal.</li>
        </ul>
      </div>

      <!-- Formulario de Entrada -->
      <div class="tool-card tool-card--accent">
        <form id="audit-form" method="POST" action="/herramientas/auditor-cookies/" class="tool-form">
          <div class="tool-form__group">
            <label for="url" class="tool-form__label" style="color:#111111;">Dirección URL a auditar</label>
            <input type="text" id="url" name="url" placeholder="https://miweb.com" value="<?= h($url) ?>" required class="tool-form__input" style="color:#111111; border-color:#111111;">
          </div>
          
          <p style="color:#64748b; font-size:0.85rem; line-height:1.5; margin: 0;">El análisis avanzado simula una visita interactiva con un navegador real: verifica el bloqueo inicial y la respuesta al pulsar "Rechazar todo" y "Aceptar todo". (Tarda entre 30 y 45 segundos).</p>

          <div id="form-error" style="display:none; background:rgba(231,76,60,0.1); border:1px solid rgba(231,76,60,0.3); color:#e74c3c; padding:0.75rem 1rem; border-radius:6px; font-size:0.9rem;"></div>

          <?php if ($error): ?>
            <div id="php-error" style="background:rgba(231,76,60,0.1); border:1px solid rgba(231,76,60,0.3); color:#e74c3c; padding:0.75rem 1rem; border-radius:6px; font-size:0.9rem;">
              ⚠️ <?= h($error) ?>
            </div>
          <?php endif; ?>
          
          <button type="submit" id="submit-btn" class="btn btn--primary" style="align-self:flex-start;">Iniciar Auditoría RGPD</button>

          <!-- Barra de progreso para análisis avanzado -->
          <div id="advanced-progress" style="display:none; margin-top:1rem; padding:1.5rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem; align-items:center;">
              <strong id="progress-step" style="color:#111111; font-size:0.95rem;">Iniciando análisis avanzado...</strong>
              <span id="progress-pct" style="color:var(--orange); font-weight:700; font-size:0.9rem;">0%</span>
            </div>
            <div style="background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden; position:relative;">
              <div id="progress-bar" style="background:var(--orange); width:0%; height:100%; transition:width 0.5s ease-out;"></div>
              <div class="progress-indeterminate" style="position:absolute; top:0; left:0; height:100%; width:30%; background:rgba(255,255,255,0.4); transform:translateX(-100%); animation:progress-slide 1.5s infinite;"></div>
            </div>
            <p style="font-size:0.8rem; color:#64748b; margin-top:0.75rem; text-align:center;">Por favor, no cierres esta ventana. El proceso puede tardar hasta 45 segundos.</p>
          </div>
        </form>

        <style>
          @keyframes progress-slide {
            100% { transform: translateX(350%); }
          }
        </style>

        <script>


          const form = document.getElementById('audit-form');
          const submitBtn = document.getElementById('submit-btn');
          const formError = document.getElementById('form-error');
          const phpError = document.getElementById('php-error');
          const advancedProgress = document.getElementById('advanced-progress');
          const progressStep = document.getElementById('progress-step');
          const progressPct = document.getElementById('progress-pct');
          const progressBar = document.getElementById('progress-bar');
          let pollInterval;

          form.addEventListener('submit', async (e) => {

            
            e.preventDefault();
            formError.style.display = 'none';
            if(phpError) phpError.style.display = 'none';
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Iniciando... <span style="display:inline-block; width:12px; height:12px; border:2px solid #fff; border-right-color:transparent; border-radius:50%; animation:spin 1s linear infinite; margin-left:8px; vertical-align:middle;"></span>';
            advancedProgress.style.display = 'block';
            
            const formData = new FormData(form);
            
            try {
              const res = await fetch('/herramientas/auditor-cookies-api/?action=start', {
                method: 'POST',
                body: formData
              });
              const data = await res.json();
              
              if (!data.success) {
                throw new Error(data.error || 'Error al iniciar el análisis avanzado.');
              }
              
              const auditId = data.id;
              
              // Polling loop
              let pollCount = 0;
              const maxPolls = 37; // ~75 segundos max con poll cada 2s
              
              pollInterval = setInterval(async () => {
                pollCount++;
                if (pollCount > maxPolls) {
                  clearInterval(pollInterval);
                  showError('La auditoría está tardando más de lo esperado. Puede que la web analizada bloquee navegadores automatizados o tarde demasiado en responder.');
                  return;
                }
                
                try {
                  const pollRes = await fetch(`/herramientas/auditor-cookies-api/?action=poll&id=${auditId}`);
                  const pollData = await pollRes.json();
                  
                  if (pollData.error) {
                    clearInterval(pollInterval);
                    showError(pollData.error);
                    return;
                  }
                  
                  if (pollData.step) progressStep.textContent = pollData.step;
                  if (pollData.progress !== undefined) {
                    progressPct.textContent = pollData.progress + '%';
                    progressBar.style.width = pollData.progress + '%';
                  }
                  
                  if (pollData.status === 'done') {
                    clearInterval(pollInterval);
                    submitBtn.innerHTML = '¡Completado! Renderizando...';
                    
                    // Enviar formulario post falso para renderizar los resultados con PHP
                    const hiddenForm = document.createElement('form');
                    hiddenForm.method = 'POST';
                    hiddenForm.action = '/herramientas/auditor-cookies/';
                    
                    const inputUrl = document.createElement('input');
                    inputUrl.type = 'hidden';
                    inputUrl.name = 'url';
                    inputUrl.value = form.querySelector('input[name="url"]').value;
                    
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'audit_id';
                    inputId.value = auditId;
                    
                    hiddenForm.appendChild(inputUrl);
                    hiddenForm.appendChild(inputId);
                    document.body.appendChild(hiddenForm);
                    hiddenForm.submit();
                    
                  } else if (pollData.status === 'failed') {
                    clearInterval(pollInterval);
                    showError('El análisis ha fallado en el servidor. Puede que la web no exista o haya bloqueado la conexión.');
                  }
                } catch(err) {
                  console.error('Error durante polling', err);
                }
              }, 2000);
              
            } catch (error) {
              showError(error.message);
            }
          });
          
          function showError(msg) {
            formError.innerHTML = '⚠️ ' + msg;
            formError.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Iniciar Auditoría RGPD';
            advancedProgress.style.display = 'none';
          }
        </script>
        <style>
          @keyframes spin { 100% { transform: rotate(360deg); } }
        </style>
      </div>

      <!-- Descargo de Responsabilidad (Disclaimer) -->
      <div style="background: #ffffff !important; border: 1px dashed #111111 !important; padding: 1.25rem; border-radius: 8px; font-size: 0.82rem; color: #111111 !important; margin-bottom: 3rem; line-height: 1.6; box-shadow: 0 4px 20px rgba(0,0,0,0.02) !important;">
        <strong style="color: #111111 !important; display: block; margin-bottom: 0.25rem;">⚖️ Descargo de responsabilidad (Disclaimer)</strong>
        Esta herramienta se ofrece de forma gratuita y con fines meramente orientativos, didácticos y de auditoría técnica. El análisis automatizado se basa en la simulación de carga y en la detección de cookies comunes en cabeceras HTTP y scripts en el HTML inicial. Puesto que existen inyecciones dinámicas complejas y cookies de comportamiento que escapan al análisis estático, la herramienta podría ofrecer falsos positivos o negativos. <strong>Este informe no constituye, en ningún caso, asesoramiento legal ni formal</strong>. El propietario de esta web queda eximido de cualquier responsabilidad ante reclamaciones, inspecciones o sanciones impuestas por la AEPD (Agencia Española de Protección de Datos) u otras autoridades de control relativas al estado de cumplimiento del sitio analizado. Para una auditoría legal vinculante, consulta con un profesional del derecho digital o una asesoría jurídica especializada.
      </div>

      <!-- Resultados del Análisis -->
      <?php if (isset($result_v2) && $result_v2['status'] === 'done'): ?>
        <?php
          $r2 = $result_v2;
          $p_init = $r2['phases']['initial'];
          $p_rej = $r2['phases']['reject'];
          $p_acc = $r2['phases']['accept'];
          $score = $r2['summary']['score'];
          
          if ($score >= 90) { $grade_class = 'tool-grade--a'; $grade_l = 'A'; $r_text = 'Bajo (Cumplimiento Alto)'; }
          elseif ($score >= 70) { $grade_class = 'tool-grade--b'; $grade_l = 'B'; $r_text = 'Medio (Revisar ajustes)'; }
          elseif ($score >= 50) { $grade_class = 'tool-grade--c'; $grade_l = 'C'; $r_text = 'Alto (Infracciones detectadas)'; }
          else { $grade_class = 'tool-grade--f'; $grade_l = 'F'; $r_text = 'Crítico (Incumplimiento RGPD)'; }
        ?>
        <div class="tool-layout-grid" style="grid-template-columns: 350px 1fr; margin-bottom:4rem;">
          
          <div class="tool-grade-box <?= $grade_class ?>" style="align-self: flex-start;">
            <span class="section-label" style="margin-bottom: 1rem;">Nivel de Cumplimiento V2</span>
            <div class="tool-grade-circle"><?= $grade_l ?></div>
            <h2 style="font-size:1.6rem; color:var(--black); margin-bottom:0.5rem;">Puntuación: <?= $score ?>/100</h2>
            <p style="font-size:0.9rem; color:var(--text); margin-bottom: 2rem;">Riesgo estimado: <strong><?= $r_text ?></strong></p>

            <div style="width:100%; text-align:left; border-top:1px solid var(--border); padding-top:1.5rem; display:flex; flex-direction:column; gap:0.75rem;">
              <span style="font-size:0.75rem; color:var(--orange); text-transform:uppercase; font-weight:700;">Evidencias (Capturas)</span>
              
              <a href="/herramientas/auditor-cookies-api/?action=image&id=<?= h($r2['id']) ?>&phase=1" target="_blank" style="display:flex; justify-content:space-between; color:var(--text); text-decoration:none; font-size:0.85rem; padding:0.5rem; background:var(--bg); border:1px solid var(--border); border-radius:4px;">
                <span>📸 Fase 1: Inicio</span> <span>Ver &rarr;</span>
              </a>
              <?php if ($p_rej['clicked']): ?>
                <a href="/herramientas/auditor-cookies-api/?action=image&id=<?= h($r2['id']) ?>&phase=2" target="_blank" style="display:flex; justify-content:space-between; color:var(--text); text-decoration:none; font-size:0.85rem; padding:0.5rem; background:var(--bg); border:1px solid var(--border); border-radius:4px;">
                  <span>📸 Fase 2: Rechazado</span> <span>Ver &rarr;</span>
                </a>
              <?php endif; ?>
              <?php if ($p_acc['clicked']): ?>
                <a href="/herramientas/auditor-cookies-api/?action=image&id=<?= h($r2['id']) ?>&phase=3" target="_blank" style="display:flex; justify-content:space-between; color:var(--text); text-decoration:none; font-size:0.85rem; padding:0.5rem; background:var(--bg); border:1px solid var(--border); border-radius:4px;">
                  <span>📸 Fase 3: Aceptado</span> <span>Ver &rarr;</span>
                </a>
              <?php endif; ?>
            </div>
          </div>
          
          <div style="display:flex; flex-direction:column; gap:2rem;">
            <!-- Botón de descarga PDF nativo -->
            <a href="/herramientas/auditor-cookies-api.php?action=pdf&id=<?= h($r2['id']) ?>" id="btn-download-pdf" class="btn btn--primary" style="align-self:flex-start; display:flex; align-items:center; gap:0.5rem; background:#E8681A; border:none; padding:0.75rem 1.25rem; font-weight:600; font-size:0.95rem; text-decoration:none;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
              Descargar Informe PDF
            </a>

            <div id="pdf-export-content" style="display:flex; flex-direction:column; gap:2rem;">
            
            <div class="tool-card">
              <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Infracciones y Advertencias (Simulación Dinámica)</h3>
              <?php if (empty($r2['summary']['findings'])): ?>
                <div style="background:rgba(21,128,61,0.1); border:1px solid rgba(21,128,61,0.3); color:#15803d; padding:0.85rem 1rem; border-radius:6px; font-size:0.92rem;">
                  ✓ Buen resultado: no se han detectado problemas RGPD durante la simulación de navegación.
                </div>
              <?php else: ?>
                <ul class="tool-violation-list">
                  <?php foreach ($r2['summary']['findings'] as $f): ?>
                    <li class="tool-violation-item" style="color:var(--text);">
                      <?php if($f['severity'] === 'alto'): ?> <span class="tool-status-badge tool-status-badge--danger" style="margin-right:0.5rem">Alto</span>
                      <?php elseif($f['severity'] === 'medio'): ?> <span class="tool-status-badge tool-status-badge--warning" style="margin-right:0.5rem">Medio</span>
                      <?php endif; ?>
                      <?= h($f['message']) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>

            <div class="tool-card">
              <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Auditoría por Fases</h3>
              
              <div style="margin-bottom:2rem;">
                <h4 style="color:var(--orange); margin-bottom:0.75rem; font-size:1.05rem;">Fase 1: Carga Inicial (Sin consentimiento)</h4>
                <div style="background:var(--bg); padding:1rem; border-radius:6px; font-size:0.9rem; color:var(--text); border:1px solid var(--border);">
                  <strong>Cookies detectadas:</strong> <?= count($p_init['cookies']) ?><br>
                  <?php if (count($p_init['cookies']) > 0): ?>
                    <div style="margin-top:0.5rem; font-family:monospace; font-size:0.8rem; background:var(--white); border:1px solid var(--border); padding:0.5rem; border-radius:4px; color:var(--text);">
                      <?= h(implode(', ', array_map(function($c){return $c['name'];}, $p_init['cookies']))) ?>
                    </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($p_init['consentMode'])): ?>
                    <div style="margin-top:1rem;">
                      <strong>Google Consent Mode:</strong> 
                      Detectado (<?= h(json_encode($p_init['consentMode'])) ?>)
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <div style="margin-bottom:2rem;">
                <h4 style="color:var(--orange); margin-bottom:0.75rem; font-size:1.05rem;">Fase 2: Tras pulsar "Rechazar todo"</h4>
                <div style="background:var(--bg); padding:1rem; border-radius:6px; font-size:0.9rem; color:var(--text); border:1px solid var(--border);">
                  <?php if (!$p_rej['clicked']): ?>
                    <span style="color:#e74c3c;">🔴 No se ha detectado un botón visible para rechazar cookies o denegar el consentimiento de forma directa.</span>
                  <?php else: ?>
                    <span style="color:#15803d; font-weight: 500;">🟢 Botón "<?= h($p_rej['buttonText']) ?>" localizado y pulsado correctamente.</span><br><br>
                    <strong>Cookies post-rechazo:</strong> <?= count($p_rej['cookies']) ?>
                    <?php if (count($p_rej['cookies']) > 0): ?>
                      <div style="margin-top:0.5rem; font-family:monospace; font-size:0.8rem; background:var(--white); border:1px solid var(--border); padding:0.5rem; border-radius:4px; color:var(--text);">
                        <?= h(implode(', ', array_map(function($c){return $c['name'];}, $p_rej['cookies']))) ?>
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>

              <div>
                <h4 style="color:var(--orange); margin-bottom:0.75rem; font-size:1.05rem;">Fase 3: Tras pulsar "Aceptar todo"</h4>
                <div style="background:var(--bg); padding:1rem; border-radius:6px; font-size:0.9rem; color:var(--text); border:1px solid var(--border);">
                  <?php if (!$p_acc['clicked']): ?>
                    <span style="color:#f1c40f;">⚠️ No se ha detectado el botón para aceptar cookies.</span>
                  <?php else: ?>
                    <span style="color:#15803d; font-weight: 500;">🟢 Botón "<?= h($p_acc['buttonText']) ?>" localizado y pulsado correctamente.</span><br><br>
                    <strong>Cookies comerciales cargadas:</strong> <?= count($p_acc['cookies']) ?>
                    <?php if (count($p_acc['cookies']) > 0): ?>
                      <div style="margin-top:0.5rem; font-family:monospace; font-size:0.8rem; background:var(--white); border:1px solid var(--border); padding:0.5rem; border-radius:4px; color:var(--text);">
                        <?= h(implode(', ', array_map(function($c){return $c['name'];}, $p_acc['cookies']))) ?>
                      </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($p_acc['consentMode'])): ?>
                      <div style="margin-top:1rem;">
                        <strong>Google Consent Mode:</strong> Actualizado (<?= h(json_encode($p_acc['consentMode'])) ?>)
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            </div> <!-- Cierre de pdf-export-content -->
          </div>
        </div>

      <?php elseif ($result): ?>
        <div class="tool-layout-grid" style="grid-template-columns: 350px 1fr; margin-bottom:4rem;">
          
          <!-- Columna Lateral: Nota Global -->
          <div class="tool-grade-box <?= $result['grade_class'] ?>" style="align-self: flex-start;">
            <span class="section-label" style="margin-bottom: 1rem;">Nivel de Cumplimiento</span>
            <div class="tool-grade-circle"><?= $result['grade'] ?></div>
            <h2 style="font-size:1.6rem; color:var(--black); margin-bottom:0.5rem;">Puntuación: <?= $result['score'] ?>/100</h2>
            <p style="font-size:0.9rem; color:var(--text); line-height:1.5;"><?= h($result['grade_desc']) ?></p>
            
            <!-- Subpuntuaciones / Desglose -->
            <div style="width:100%; margin-top:2rem; padding-top:1.5rem; border-top:1px solid var(--border); text-align:left; display:flex; flex-direction:column; gap:1rem;">
              <span style="font-size:0.75rem; color:#ff9f43; text-transform:uppercase; letter-spacing:0.05em; font-weight:700; display:block; margin-bottom:0.25rem;">Desglose de Puntuación</span>
              
              <div>
                <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem;">
                  <span style="color:var(--text);">🔒 Bloqueo Técnico</span>
                  <strong style="color:var(--black);"><?= $result['score_tecnico'] ?>/100</strong>
                </div>
                <div style="background:var(--border); height:6px; border-radius:3px; overflow:hidden;">
                  <div style="background:<?= $result['score_tecnico'] >= 90 ? '#2ecc71' : ($result['score_tecnico'] >= 50 ? '#f1c40f' : '#e74c3c') ?>; width:<?= $result['score_tecnico'] ?>%; height:100%;"></div>
                </div>
              </div>

              <div>
                <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem;">
                  <span style="color:var(--text);">💬 Consentimiento y Banner *</span>
                  <strong style="color:var(--black);"><?= $result['score_consentimiento'] ?>/100</strong>
                </div>
                <div style="background:var(--border); height:6px; border-radius:3px; overflow:hidden;">
                  <div style="background:<?= $result['score_consentimiento'] >= 90 ? '#2ecc71' : ($result['score_consentimiento'] >= 50 ? '#f1c40f' : '#e74c3c') ?>; width:<?= $result['score_consentimiento'] ?>%; height:100%;"></div>
                </div>
              </div>

              <div>
                <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem;">
                  <span style="color:var(--text);">📄 Páginas Legales</span>
                  <strong style="color:var(--black);"><?= $result['score_legal'] ?>/100</strong>
                </div>
                <div style="background:var(--border); height:6px; border-radius:3px; overflow:hidden;">
                  <div style="background:<?= $result['score_legal'] >= 90 ? '#2ecc71' : ($result['score_legal'] >= 50 ? '#f1c40f' : '#e74c3c') ?>; width:<?= $result['score_legal'] ?>%; height:100%;"></div>
                </div>
              </div>

              <div>
                <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem;">
                  <span style="color:var(--text);">🔌 Terceros Embebidos</span>
                  <strong style="color:var(--black);"><?= $result['score_embeds'] ?>/100</strong>
                </div>
                <div style="background:var(--border); height:6px; border-radius:3px; overflow:hidden;">
                  <div style="background:<?= $result['score_embeds'] >= 90 ? '#2ecc71' : ($result['score_embeds'] >= 50 ? '#f1c40f' : '#e74c3c') ?>; width:<?= $result['score_embeds'] ?>%; height:100%;"></div>
                </div>
              </div>

              <div style="font-size:0.72rem; color:var(--muted); margin-top:0.25rem; font-style:italic; line-height:1.3;">
                <span style="color:var(--orange);">*</span> Detección aproximada sobre HTML inicial.
              </div>
            </div>

            <?php if ($result['ssl_invalid']): ?>
              <div style="background:rgba(241,196,15,0.1); border:1px solid rgba(241,196,15,0.3); color:#f1c40f; padding:0.75rem; border-radius:6px; font-size:0.8rem; margin-top:1.5rem; text-align:left;">
                ⚠️ <strong>Aviso:</strong> El certificado SSL de esta web no es válido o ha expirado. He procedido con el análisis desactivando la comprobación estricta de SSL.
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Columna Principal: Informe Técnico -->
          <div style="display:flex; flex-direction:column; gap:2rem;">
            
            <!-- Listado de Violaciones Detectadas -->
            <div class="tool-card">
              <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Infracciones y Advertencias</h3>
              <?php if (empty($result['violations'])): ?>
                <div style="background:rgba(46,204,113,0.1); border:1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:0.85rem 1rem; border-radius:6px; font-size:0.92rem;">
                  ✓ Buen resultado: no se han detectado cookies, scripts o iframes problemáticos en el HTML inicial analizado.
                </div>
              <?php else: ?>
                <p style="font-size:0.92rem; color:var(--text); margin-bottom:0.5rem;">Se han detectado los siguientes riesgos legales que podrían ser objeto de sanción por la AEPD (Agencia Española de Protección de Datos):</p>
                <ul class="tool-violation-list">
                  <?php foreach ($result['violations'] as $violation): ?>
                    <li class="tool-violation-item"><?= strip_tags($violation, '<strong><code>') ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>

            <!-- Diagnóstico Técnico Interpretado -->
            <div class="tool-card" style="border-left:4px solid var(--orange);">
              <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Diagnóstico Técnico Interpretado</h3>
              
              <div style="font-size:0.95rem; line-height:1.6; color:var(--text); display:flex; flex-direction:column; gap:1.25rem;">
                <?php if (empty($result['violations'])): ?>
                  <p>🟢 <strong>Prioridad de arreglo: Baja.</strong> Tu sitio web cumple correctamente con las directrices de la AEPD en cuanto al bloqueo previo de cookies y scripts comerciales. No se requiere ninguna acción correctiva urgente.</p>
                <?php else: ?>
                  <div>
                    <strong style="color:var(--black);">Prioridad de arreglo:</strong> 
                    <?php if ($result['score'] < 50): ?>
                      <span class="tool-status-badge tool-status-badge--danger">Alta</span>
                      <p style="margin-top:0.5rem; color:var(--text); font-size:0.9rem;">Se están depositando cookies de terceros o cargando herramientas de seguimiento (como Analytics o píxeles) inmediatamente al entrar al sitio. Esto expone a la web a riesgos de cumplimiento normativo.</p>
                    <?php else: ?>
                      <span class="tool-status-badge tool-status-badge--warning">Media</span>
                      <p style="margin-top:0.5rem; color:var(--text); font-size:0.9rem;">El sitio web bloquea parte de los trackers, pero se han detectado deficiencias de configuración o la ausencia de páginas de políticas legales obligatorias.</p>
                    <?php endif; ?>
                  </div>

                  <?php 
                  $has_cookie_violation = false;
                  $has_script_violation = false;
                  $has_legal_violation = false;
                  foreach ($result['violations'] as $violation) {
                      if (stripos($violation, 'cookie') !== false) {
                          $has_cookie_violation = true;
                      }
                      if (stripos($violation, 'script') !== false) {
                          $has_script_violation = true;
                      }
                      if (stripos($violation, 'enlace') !== false || stripos($violation, 'página') !== false) {
                          $has_legal_violation = true;
                      }
                  }
                  ?>

                  <?php if ($has_cookie_violation || $has_script_violation || $has_legal_violation): ?>
                    <div style="background:var(--bg); padding:1rem; border-radius:6px; border:1px solid var(--border);">
                      <strong style="color:var(--black); display:block; margin-bottom:0.5rem;">📝 Recomendación del Auditor:</strong>
                      <ul style="margin:0; padding-left:1.25rem; font-size:0.9rem; color:var(--text); display:flex; flex-direction:column; gap:0.5rem;">
                        <?php if ($has_cookie_violation): ?>
                          <li><strong>Fuga de cookies antes del consentimiento:</strong> Esto suele ocurrir cuando Google Analytics 4 u otros trackers están pegados directamente en el código de la cabecera (<code>&lt;head&gt;</code>) de la página sin ningún tipo de condicionalidad, o si se utiliza un plugin de cookies visual (que muestra el aviso pero no realiza el bloqueo técnico).</li>
                        <?php endif; ?>
                        <?php if ($has_script_violation): ?>
                          <li><strong>Etiquetas de script no bloqueadas:</strong> Si utilizas WordPress, te recomendamos revisar si herramientas como Site Kit, Complianz, CookieYes o el propio tema están duplicando etiquetas o cargándolas directamente sin el filtrado obligatorio (como el tipo <code>type="text/plain"</code>).</li>
                        <?php endif; ?>
                        <?php if ($has_legal_violation): ?>
                          <li><strong>Enlaces legales ausentes:</strong> Es recomendable por la normativa aplicable que los enlaces permanentes al Aviso Legal, Política de Privacidad y Política de Cookies estén visibles y accesibles desde todas las páginas de tu web (habitualmente en el pie de página).</li>
                        <?php endif; ?>
                      </ul>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>

            <!-- Estado del Banner y CMP Detectado -->
            <div class="tool-card">
              <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Estado de Gestión de Consentimiento (Banner / CMP)</h3>
              <p style="font-size:0.92rem; color:var(--text); margin-bottom:1.5rem;">Análisis automatizado de la presencia de herramientas para la obtención del consentimiento y su configuración:</p>
              
              <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem;">
                <div style="background:var(--bg); padding:1rem; border-radius:6px; border:1px solid var(--border);">
                  <span style="font-size:0.8rem; color:#ff9f43; display:block; margin-bottom:0.25rem;">Gestor detectado (CMP)</span>
                  <strong style="color:var(--black); font-size:1.05rem;"><?= h($result['detected_cmp']) ?></strong>
                </div>

                <div style="background:var(--bg); padding:1rem; border-radius:6px; border:1px solid var(--border);">
                  <span style="font-size:0.8rem; color:#ff9f43; display:block; margin-bottom:0.25rem;">Presencia de Banner</span>
                  <strong style="color:var(--black); font-size:1.05rem;">
                    <?= $result['banner_detected'] ? '🟢 Detectado' : '🔴 No detectado en el HTML' ?>
                  </strong>
                </div>

                <div style="background:var(--bg); padding:1rem; border-radius:6px; border:1px solid var(--border);">
                  <span style="font-size:0.8rem; color:#ff9f43; display:block; margin-bottom:0.25rem;">Google Consent Mode</span>
                  <strong style="color:var(--black); font-size:1.05rem;">
                    <?= $result['consent_mode'] ? '🟢 Configurado' : '⚪ Sin indicios en el HTML' ?>
                  </strong>
                </div>
              </div>

              <div style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--border); font-size:0.88rem; color:var(--text); display:flex; flex-direction:column; gap:0.5rem;">
                <div>
                  <strong>¿Opción de aceptación explícita?:</strong> 
                  <?= $result['accept_btn'] ? '🟢 Detectada (Botón de aceptación aproximado encontrado)' : '⚠️ No se ha detectado claramente un botón con texto de aceptar. Revisa visualmente.' ?>
                </div>
                <div>
                  <strong>¿Opción de rechazo al mismo nivel?:</strong> 
                  <?= $result['reject_btn'] ? '🟢 Detectada (Botón de rechazo aproximado encontrado)' : '🔴 No se detecta un botón con texto claro para rechazar. La AEPD exige ofrecer el botón de rechazar al mismo nivel de visibilidad que el de aceptar.' ?>
                </div>
                <div style="font-size:0.8rem; color:var(--muted); margin-top:0.5rem; font-style:italic;">
                  <span style="color:var(--orange);">ℹ️</span> Esta detección se realiza sobre el HTML inicial. Si el banner se genera dinámicamente con JavaScript, puede requerir revisión manual.
                </div>
              </div>
            </div>

            <!-- Menú Legal / Enlaces obligatorios -->
            <div class="tool-card">
              <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Presencia de Páginas Legales Obligatorias</h3>
              <p style="font-size:0.92rem; color:var(--text); margin-bottom:1rem;">Toda web corporativa o que recopile analíticas debe enlazar de manera visible estas políticas:</p>
              <div class="tool-legal-grid">
                <?php foreach ($result['legal_pages'] as $name => $data): ?>
                  <div class="tool-legal-card">
                    <div style="font-size:1.25rem; margin-bottom:0.35rem;"><?= $data['found'] ? '🟢' : '🔴' ?></div>
                    <strong style="color:var(--black); font-size:0.9rem; display:block;"><?= h($name) ?></strong>
                    <span style="font-size:0.8rem; color:<?= $data['found'] ? '#15803d' : '#e74c3c' ?>;"><?= $data['found'] ? 'Enlace detectado' : 'No encontrado' ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <?php if (!empty($result['detected_iframes'])): ?>
              <!-- Iframes de Terceros Detectados -->
              <div class="tool-card">
                <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Incrustados (Iframes) de Terceros</h3>
                <p style="font-size:0.92rem; color:var(--text); margin-bottom:1rem;">Elementos embebidos en el HTML inicial que conectan con servicios externos. Deben estar bloqueados hasta obtener el consentimiento:</p>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:var(--black);">
                        <th style="padding:0.75rem 0.5rem;">Servicio</th>
                        <th style="padding:0.75rem 0.5rem;">Dirección URL del Iframe</th>
                        <th style="padding:0.75rem 0.5rem;">Riesgo RGPD</th>
                        <th style="padding:0.75rem 0.5rem;">Impacto en Privacidad</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['detected_iframes'] as $ifr): ?>
                        <tr style="border-bottom:1px solid var(--border); color:var(--text);">
                          <td style="padding:0.75rem 0.5rem;"><strong><?= h($ifr['name']) ?></strong></td>
                          <td style="padding:0.75rem 0.5rem; font-family:monospace; font-size:0.8rem; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= h($ifr['src']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <span class="tool-status-badge <?= $ifr['severity'] === 'Alto' ? 'tool-status-badge--danger' : 'tool-status-badge--warning' ?>">
                              <?= h($ifr['severity']) ?>
                            </span>
                          </td>
                          <td style="padding:0.75rem 0.5rem; font-size:0.82rem; color:var(--text);"><?= h($ifr['desc']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($result['external_requests'])): ?>
              <!-- Peticiones de Red a Dominios Externos -->
              <div class="tool-card">
                <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Conexiones a Dominios de Terceros</h3>
                <p style="font-size:0.92rem; color:var(--text); margin-bottom:1rem;">Dominios externos referenciados en el HTML inicial mediante scripts, CSS, imágenes, iframes o preconexiones:</p>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:var(--black);">
                        <th style="padding:0.75rem 0.5rem;">Dominio de Tercero</th>
                        <th style="padding:0.75rem 0.5rem;">Tipos de Recursos</th>
                        <th style="padding:0.75rem 0.5rem;">Riesgo de Privacidad</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['external_requests'] as $req): ?>
                        <tr style="border-bottom:1px solid var(--border); color:var(--text);">
                          <td style="padding:0.75rem 0.5rem; font-family:monospace;"><strong><?= h($req['domain']) ?></strong></td>
                          <td style="padding:0.75rem 0.5rem; font-size:0.82rem; color:var(--text);"><?= h($req['types']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <span class="tool-status-badge <?= $req['risk'] === 'Alto' ? 'tool-status-badge--danger' : 'tool-status-badge--warning' ?>">
                              <?= h($req['risk']) ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>

            <!-- Tabla de Cookies de 1er Impacto -->
            <div class="tool-card">
              <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Cookies detectadas en cabeceras HTTP durante la carga inicial</h3>
              <p style="font-size:0.92rem; color:var(--text); margin-bottom:1rem;">Las cookies que se envían en las cabeceras HTTP de respuesta inmediatamente al abrir la URL antes de pulsar ningún botón de banner:</p>
              
              <?php if (empty($result['cookies_set'])): ?>
                <div style="background:rgba(21,128,61,0.05); border:1px solid rgba(21,128,61,0.1); color:#15803d; padding:0.85rem 1rem; border-radius:6px; font-size:0.9rem;">
                  ✓ No se han detectado cookies en las cabeceras HTTP durante la carga inicial.
                </div>
              <?php else: ?>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:var(--black);">
                        <th style="padding:0.75rem 0.5rem;">Nombre</th>
                        <th style="padding:0.75rem 0.5rem;">Proveedor</th>
                        <th style="padding:0.75rem 0.5rem;">Propósito</th>
                        <th style="padding:0.75rem 0.5rem;">Tipo / Severidad</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['cookies_set'] as $name => $data): ?>
                        <tr style="border-bottom:1px solid var(--border); color:var(--text);">
                          <td style="padding:0.75rem 0.5rem; font-weight:700; color:var(--black);"><?= h($name) ?></td>
                          <td style="padding:0.75rem 0.5rem; color:var(--text);"><?= h($data['provider']) ?></td>
                          <td style="padding:0.75rem 0.5rem; color:var(--text);"><?= h($data['description']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <?php if ($data['category'] === 'necesaria'): ?>
                              <span class="tool-status-badge tool-status-badge--success">Necesaria / Técnica</span>
                            <?php else: ?>
                              <span class="tool-status-badge tool-status-badge--danger">No Consentida (Incumple)</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

            <!-- Tabla de Scripts Analizados -->
            <div class="tool-card">
              <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Scripts de Seguimiento en HTML</h3>
              <p style="font-size:0.92rem; color:var(--text); margin-bottom:1rem;">Scripts de terceros detectados en el código de la web y su estado de bloqueo:</p>
              
              <?php if (empty($result['detected_scripts'])): ?>
                <div style="background:rgba(21,128,61,0.05); border:1px solid rgba(21,128,61,0.1); color:#15803d; padding:0.85rem 1rem; border-radius:6px; font-size:0.9rem;">
                  ✓ No se han encontrado scripts de seguimiento conocidos cargados en el código HTML de esta página.
                </div>
              <?php else: ?>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:var(--black);">
                        <th style="padding:0.75rem 0.5rem;">Herramienta</th>
                        <th style="padding:0.75rem 0.5rem;">Origen (SRC)</th>
                        <th style="padding:0.75rem 0.5rem;">Estado</th>
                        <th style="padding:0.75rem 0.5rem;">Cumplimiento</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['detected_scripts'] as $name => $data): ?>
                        <tr style="border-bottom:1px solid var(--border); color:var(--text);">
                          <td style="padding:0.75rem 0.5rem; font-weight:700; color:var(--black);"><?= h($name) ?></td>
                          <td style="padding:0.75rem 0.5rem; font-family:monospace; font-size:0.8rem; color:var(--text);"><?= h($data['src']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <?= $data['blocked'] ? '<span style="color:#15803d;">Bloqueado (tipo text/plain)</span>' : '<span style="color:#e74c3c; font-weight:bold;">Ejecución directa</span>' ?>
                          </td>
                          <td style="padding:0.75rem 0.5rem;">
                            <?php if ($data['blocked']): ?>
                              <span class="tool-status-badge tool-status-badge--success">Correcto</span>
                            <?php else: ?>
                              <span class="tool-status-badge tool-status-badge--danger">Infracción</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

            <!-- Cadena de redirecciones si hubo -->
            <?php if (!empty($result['redirects'])): ?>
              <div class="tool-card">
                <h3 style="color:var(--black); font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Cadena de Redireccionamientos Auditados</h3>
                <ol style="margin:0; padding-left:1.25rem; font-size:0.9rem; color:var(--text); display:flex; flex-direction:column; gap:0.5rem;">
                  <?php foreach ($result['redirects'] as $index => $r): ?>
                    <li>
                      <strong>Redirección [<?= $r['code'] ?>]</strong>: 
                      <span style="font-family:monospace; font-size:0.8rem; color:var(--text); display:block; margin-top:0.15rem; word-break:break-all;"><?= h($r['from']) ?> → <?= h($r['to']) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ol>
              </div>
            <?php endif; ?>

          </div>
        </div>
      <?php endif; ?>

      <!-- Sección de Criterio Técnico y RGPD -->
      <div class="criterio-section" style="margin-top:4rem">
        <span class="section-label">Desde la trinchera legal</span>
        <h2>El criterio de la AEPD sobre el consentimiento de cookies</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>El falso banner de "Aceptar" y "Aceptar"</h3>
            <p>Muchos desarrolladores cometen el error de instalar un banner visual y creer que están en regla. Sin embargo, la normativa de cookies europea y española dicta que <strong>el botón de rechazar debe estar al mismo nivel de visibilidad</strong> que el de aceptar.</p>
            <p>Si tu banner obliga al usuario a navegar por 3 pantallas para denegar o carece de botón directo de rechazo en su pantalla inicial, tu web está incumpliendo con las directrices vigentes y se expone a posibles reclamaciones o sanciones administrativas.</p>
          </div>
          
          <div class="criterio-card">
            <h3>La trampa de Google Analytics 4 (GA4)</h3>
            <p>Para la ley europea, un identificador de usuario único (como el ID del parámetro <code>_ga</code> que usa Analytics) es considerado un <strong>dato personal</strong>. Por lo tanto, no se puede almacenar en el navegador ni transmitir a servidores de Google antes de que el usuario lo autorice de forma afirmativa.</p>
            <p>Muchas plantillas de WordPress inyectan GA4 de forma dura en el <code>&lt;head&gt;</code> bloqueando únicamente de manera visual la barra. Esto instala las cookies de Analytics de inmediato al primer milisegundo de entrada, pudiendo dar lugar a reclamaciones o sanciones administrativas.</p>
          </div>

          <div class="criterio-card">
            <h3>Cómo solucionar el bloqueo sin perder WPO</h3>
            <p>Para cumplir al 100%, debes modificar tus etiquetas <code>&lt;script&gt;</code> de marketing y analítica para que carguen con el tipo <code>type="text/plain"</code> y un identificador de categoría. De este modo, el navegador las ignorará por completo durante la carga inicial.</p>
            <p>Cuando el usuario interactúa y acepta tus cookies, un script de consentimiento ligero de código abierto (como <strong><code>vanilla-cookieconsent</code></strong>) reactiva esos scripts en vivo en memoria sin ralentizar la carga inicial de tu servidor.</p>
          </div>
        </div>
      </div>

      <!-- Brotip de rendimiento y RGPD -->
      <div style="background:#0b101c; border:1px solid var(--border); border-left:4px solid var(--orange); padding:1.5rem; border-radius:8px; margin:2rem 0 3rem 0; color:#cbd5e1; font-size:0.92rem; line-height:1.6;">
        <h4 style="color:#fff; font-size:1.1rem; margin-top:0; margin-bottom:0.75rem; display:flex; align-items:center; gap:0.5rem;">
          💡 Brotip para WordPress: ¿Cómo solucionarlo con plugins?
        </h4>
        <p style="margin-bottom:0.75rem;">Si usas WordPress y no quieres complicarte modificando el código de tus temas a mano (con <code>type="text/plain"</code>), existen plugins especializados que realizan este bloqueo automático de manera impecable:</p>
        <ul style="margin:0; padding-left:1.25rem; display:flex; flex-direction:column; gap:0.5rem; color:#cbd5e1;">
          <li><strong>Complianz (Mi recomendado):</strong> Es uno de los reyes del sector. Realiza un escaneo automático de cookies, genera los textos legales y bloquea los scripts de Google Analytics, Tag Manager o píxeles automáticamente antes de recibir el consentimiento.</li>
          <li><strong>CookieYes:</strong> Muy visual, ligero y configurable desde un panel en la nube. Gestiona los consentimientos de forma rápida y bloquea la inyección inicial con eficacia.</li>
          <li><strong>Real Cookie Banner / Borlabs Cookie:</strong> Dos opciones premium excelentes si buscas un control absoluto de scripts complejos, hojas de estilo o integraciones locales.</li>
        </ul>
        <p style="margin-top:0.75rem; margin-bottom:0; font-size:0.85rem; color:#ffffff; font-style:italic;">
          <span style="color:var(--orange);">*</span> Ojo: Si usas plugins de optimización WPO (como WP Rocket o LiteSpeed Cache), asegúrate de excluir el script del banner de cookies de la minificación y combinación de JS para que no tarde en cargarse o rompa su funcionalidad.
        </p>
      </div>

      <!-- Widget de Votación y Rich Snippet -->
      <?php render_rating_widget('auditor-cookies'); ?>

    </div>
  </section>

  <!-- Exportación PDF delegada a Backend (Puppeteer) -->


  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Quieres bloquear correctamente tus cookies y evitar sustos con la AEPD?',
    'subtitle'  => 'Corrige tu banner de cookies y tus scripts de tracking.',
    'btn_label' => 'Solucionar mi banner de cookies',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

  <?php if ((isset($result_v2) && $result_v2['status'] === 'done') || isset($result)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Small delay to ensure rendering is complete
      setTimeout(() => {
        const results = document.getElementById('pdf-export-content') || document.querySelector('.tool-layout-grid');
        if (results) {
          // Scroll with an offset to avoid sticking right to the top
          const y = results.getBoundingClientRect().top + window.scrollY - 100;
          window.scrollTo({ top: y, behavior: 'smooth' });
        }
      }, 300);
    });
  </script>
  <?php endif; ?>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
