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
        } else {
            $redirect_chain = [];
            $max_redirects = 5;
            $redirect_count = 0;
            $current_url = $url;
            $html_content = '';
            $cookies_set = [];
            $ssl_invalid_detected = false;
            
            // Guardar cookies a lo largo de la cadena de redirecciones
            while ($redirect_count <= $max_redirects) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $current_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_USERAGENT, 'VictorAlonsoCookieBot/1.0 (+https://www.victor-alonso.es/herramientas/auditor-cookies)');

                $response = curl_exec($ch);
                
                // Reintentar si falla SSL para poder auditar de todas formas
                if (curl_errno($ch) && (curl_errno($ch) == 60 || curl_errno($ch) == 51 || stripos(curl_error($ch), 'ssl') !== false || stripos(curl_error($ch), 'certificate') !== false)) {
                    $ssl_invalid_detected = true;
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    $response = curl_exec($ch);
                }

                if (curl_errno($ch)) {
                    $error = 'No se ha podido establecer conexión con la web: ' . curl_error($ch);
                    curl_close($ch);
                    break;
                }

                $info = curl_getinfo($ch);
                $header_size = $info['header_size'];
                $headers_raw = substr($response, 0, $header_size);
                $html_content = substr($response, $header_size);
                curl_close($ch);

                // Capturar cookies de las cabeceras Set-Cookie
                if (preg_match_all('/^Set-Cookie:\s*([^;=]+)=([^;]*)/mi', $headers_raw, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $c_name = trim($match[1]);
                        $c_value = trim($match[2]);
                        
                        // Clasificar la cookie
                        $category = 'necesaria';
                        $provider = 'Propio (First-party)';
                        $description = 'Cookie técnica de servidor o sesión.';
                        
                        $name_lower = strtolower($c_name);
                        if (strpos($name_lower, '_ga') === 0) {
                            $category = 'analitica';
                            $provider = 'Google Analytics';
                            $description = 'Identificador único de usuario para Google Analytics.';
                        } elseif (strpos($name_lower, '_gid') === 0) {
                            $category = 'analitica';
                            $provider = 'Google Analytics';
                            $description = 'Identificador temporal de usuario para Google Analytics.';
                        } elseif (strpos($name_lower, '_fbp') === 0 || strpos($name_lower, '_fbc') === 0) {
                            $category = 'marketing';
                            $provider = 'Facebook Pixel';
                            $description = 'Seguimiento de conversiones y perfiles de publicidad de Facebook.';
                        } elseif (strpos($name_lower, '_hj') === 0) {
                            $category = 'analitica';
                            $provider = 'Hotjar';
                            $description = 'Persiste el ID del usuario único de Hotjar en el navegador.';
                        } elseif (strpos($name_lower, '_clck') === 0 || strpos($name_lower, '_clsk') === 0) {
                            $category = 'analitica';
                            $provider = 'Microsoft Clarity';
                            $description = 'Estadísticas y grabaciones de navegación de Microsoft.';
                        } elseif (strpos($name_lower, '_uet') === 0) {
                            $category = 'marketing';
                            $provider = 'Bing Ads';
                            $description = 'Seguimiento de publicidad y conversiones de Microsoft Bing.';
                        } elseif (strpos($name_lower, '_tt_enable') === 0) {
                            $category = 'marketing';
                            $provider = 'TikTok Pixel';
                            $description = 'Seguimiento de conversiones de anuncios de TikTok.';
                        }
                        
                        $cookies_set[$c_name] = [
                            'value'       => substr($c_value, 0, 30) . (strlen($c_value) > 30 ? '...' : ''),
                            'category'    => $category,
                            'provider'    => $provider,
                            'description' => $description,
                            'url'         => $current_url
                        ];
                    }
                }

                // Seguir redirecciones 3xx manualmente
                if ($info['http_code'] >= 300 && $info['http_code'] < 400 && preg_match('/^Location:\s*([^\r\n]+)/mi', $headers_raw, $loc_matches)) {
                    $redirect_url = trim($loc_matches[1]);
                    
                    // Resolver rutas relativas si es necesario
                    if (strpos($redirect_url, '/') === 0) {
                        $parsed = parse_url($current_url);
                        $redirect_url = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $redirect_url;
                    }
                    
                    $redirect_chain[] = [
                        'code' => $info['http_code'],
                        'from' => $current_url,
                        'to'   => $redirect_url
                    ];
                    
                    $current_url = $redirect_url;
                    $redirect_count++;
                } else {
                    break;
                }
            }

            if (!$error) {
                // Analizar el HTML en busca de scripts de seguimiento
                $detected_scripts = [];
                
                // Mapeo de patrones de script de seguimiento
                $script_patterns = [
                    'Google Tag Manager' => [
                        'pattern' => '/googletagmanager\.com\/gtm\.js/i',
                        'desc'    => 'Gestor de etiquetas de Google.'
                    ],
                    'Google Analytics (gtag.js)' => [
                        'pattern' => '/googletagmanager\.com\/gtag\/js/i',
                        'desc'    => 'Script principal de medición de Google Analytics 4.'
                    ],
                    'Google Analytics (analytics.js legacy)' => [
                        'pattern' => '/google-analytics\.com\/analytics\.js/i',
                        'desc'    => 'Versión antigua y obsoleta de Google Analytics.'
                    ],
                    'Facebook Pixel' => [
                        'pattern' => '/connect\.facebook\.net\/[a-z_]+\/fbevents\.js/i',
                        'desc'    => 'Seguimiento publicitario y conversiones de Facebook.'
                    ],
                    'Hotjar' => [
                        'pattern' => '/static\.hotjar\.com\/c\/hotjar-/i',
                        'desc'    => 'Mapas de calor y grabación de sesiones de usuario.'
                    ],
                    'Microsoft Clarity' => [
                        'pattern' => '/clarity\.ms\/tag/i',
                        'desc'    => 'Herramienta de análisis del comportamiento del usuario de Microsoft.'
                    ],
                    'TikTok Pixel' => [
                        'pattern' => '/analytics\.tiktok\.com\/i18n\/pixel/i',
                        'desc'    => 'Píxel de seguimiento publicitario para anuncios en TikTok.'
                    ],
                    'Google AdSense' => [
                        'pattern' => '/pagead2\.googlesyndication\.com\/pagead\/js\/adsbygoogle\.js/i',
                        'desc'    => 'Red de anuncios contextuales de Google.'
                    ],
                    'Matomo / Piwik' => [
                        'pattern' => '/(piwik|matomo)\.js/i',
                        'desc'    => 'Plataforma auto-alojada de analítica de privacidad.'
                    ]
                ];

                // Extraer todos los tags <script>
                if (preg_match_all('/<script\b([^>]*)>(.*?)<\/script>/is', $html_content, $script_tags, PREG_SET_ORDER)) {
                    foreach ($script_tags as $tag) {
                        $attrs = $tag[1];
                        $inner = $tag[2];
                        
                        // Buscar src en los atributos
                        $src = '';
                        if (preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $src_match)) {
                            $src = $src_match[1];
                        }
                        
                        // Verificar patrones
                        foreach ($script_patterns as $name => $info) {
                            $matches_pattern = false;
                            if ($src && preg_match($info['pattern'], $src)) {
                                $matches_pattern = true;
                            } elseif (preg_match($info['pattern'], $inner)) {
                                $matches_pattern = true;
                            }

                            if ($matches_pattern) {
                                // Determinar si está bloqueado/configurado con tipo text/plain (para consentimiento dinámico)
                                $is_blocked = false;
                                if (preg_match('/type=["\']text\/plain["\']/i', $attrs)) {
                                    $is_blocked = true;
                                }
                                
                                $detected_scripts[$name] = [
                                    'name'       => $name,
                                    'src'        => $src ? substr($src, 0, 60) . (strlen($src) > 60 ? '...' : '') : 'Código en línea (Inline)',
                                    'blocked'    => $is_blocked,
                                    'desc'       => $info['desc']
                                ];
                            }
                        }
                    }
                }

                // Analizar la presencia de enlaces a las páginas legales en el HTML
                $legal_pages = [
                    'Política de Cookies' => [
                        'pattern' => '/href=["\'][^"\']*(cookie)[^"\']*["\']/i',
                        'found'   => false
                    ],
                    'Política de Privacidad' => [
                        'pattern' => '/href=["\'][^"\']*(privac|privacy|proteccion-datos|proteccion-de-datos)[^"\']*["\']/i',
                        'found'   => false
                    ],
                    'Aviso Legal' => [
                        'pattern' => '/href=["\'][^"\']*(legal|condiciones|terminos)[^"\']*["\']/i',
                        'found'   => false
                    ]
                ];

                foreach ($legal_pages as $page_name => &$p_info) {
                    if (preg_match($p_info['pattern'], $html_content)) {
                        $p_info['found'] = true;
                    }
                }
                unset($p_info);

                // Calcular la puntuación de cumplimiento
                $score = 100;
                $violations = [];
                
                // 1. Cookies no funcionales seteadas en el primer impacto
                $unsafe_cookies_count = 0;
                foreach ($cookies_set as $c_name => $c_data) {
                    if ($c_data['category'] !== 'necesaria') {
                        $unsafe_cookies_count++;
                        $violations[] = "Se ha depositado la cookie de terceros `{$c_name}` ({$c_data['provider']}) antes de que el usuario acepte el banner.";
                    }
                }
                if ($unsafe_cookies_count > 0) {
                    $score -= 35;
                }

                // 2. Scripts de tracking no bloqueados
                $unblocked_scripts_count = 0;
                foreach ($detected_scripts as $s_name => $s_data) {
                    if (!$s_data['blocked']) {
                        $unblocked_scripts_count++;
                        $violations[] = "El script de `{$s_name}` está cargado de forma agresiva y directa en el HTML (no bloqueado en espera de consentimiento).";
                    }
                }
                if ($unblocked_scripts_count > 0) {
                    $score -= 35;
                }

                // 3. Páginas legales faltantes
                foreach ($legal_pages as $p_name => $p_data) {
                    if (!$p_data['found']) {
                        $score -= 10;
                        $violations[] = "No se detecta enlace a la página de {$p_name} en el menú o pie de página del HTML analizado.";
                    }
                }

                // Asegurar rango de score
                $score = max(0, $score);

                // Clasificar por nota
                if ($score >= 90) {
                    $grade = 'A';
                    $grade_class = 'grade-a';
                    $grade_desc = '¡Apto! Excelente nivel de cumplimiento. No se han detectado cookies ni scripts no consentidos en el primer hit.';
                } elseif ($score >= 70) {
                    $grade = 'B';
                    $grade_class = 'grade-b';
                    $grade_desc = 'Aceptable. Sin scripts de analítica activos por defecto, pero se detecta alguna deficiencia leve.';
                } elseif ($score >= 50) {
                    $grade = 'C';
                    $grade_class = 'grade-c';
                    $grade_desc = 'Alerta de Riesgo. La web carga trackers de forma descuidada o carece de los enlaces obligatorios.';
                } else {
                    $grade = 'F';
                    $grade_class = 'grade-f';
                    $grade_desc = 'Incumplimiento Crítico. La web recopila y procesa datos personales inmediatamente al entrar, vulnerando el RGPD.';
                }

                $result = [
                    'final_url'        => $current_url,
                    'ssl_invalid'      => $ssl_invalid_detected,
                    'score'            => $score,
                    'grade'            => $grade,
                    'grade_class'      => $grade_class,
                    'grade_desc'       => $grade_desc,
                    'cookies_set'      => $cookies_set,
                    'detected_scripts' => $detected_scripts,
                    'legal_pages'      => $legal_pages,
                    'violations'       => $violations,
                    'redirects'        => $redirect_chain
                ];
            }
        }
    }
}

$page = page_config([
    'title'        => 'Auditor de Cookies y Privacidad RGPD',
    'description'  => 'Introduce una URL para auditar en tiempo real su nivel de cumplimiento legal con el RGPD. Analiza cookies y bloqueo de scripts de terceros.',
    'canonical'    => '/herramientas/auditor-cookies/',
    'body_class'   => 'page-herramientas-auditor-cookies',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Auditor de Cookies', 'url' => ''],
    ],
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">
  <section class="page-hero" aria-labelledby="tool-h1">
    <div class="container">
      <h1 id="tool-h1">Auditor de <span>Cookies y Privacidad RGPD</span></h1>
      <p class="page-hero-desc">Audita tu web en vivo. Nuestro bot analiza si tu página respeta los estándares del RGPD/LOPDGDD bloqueando correctamente los scripts de seguimiento y las cookies de terceros antes de que el usuario haga clic en aceptar.</p>
    </div>
  </section>

  <style>
    /* Estilos Premium para el Auditor de Cookies */
    .compliance-grade-box {
      background: #0b101c;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2.5rem;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    .grade-circle {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      font-weight: 900;
      margin-bottom: 1.5rem;
      box-shadow: 0 0 25px rgba(255, 255, 255, 0.05);
      position: relative;
    }
    .grade-circle::after {
      content: '';
      position: absolute;
      inset: -4px;
      border-radius: 50%;
      border: 4px solid transparent;
    }
    
    .grade-a .grade-circle {
      color: #2ecc71;
      background: rgba(46, 204, 113, 0.05);
    }
    .grade-a .grade-circle::after { border-color: #2ecc71; }
    
    .grade-b .grade-circle {
      color: #3498db;
      background: rgba(52, 152, 219, 0.05);
    }
    .grade-b .grade-circle::after { border-color: #3498db; }
    
    .grade-c .grade-circle {
      color: #f1c40f;
      background: rgba(241, 196, 15, 0.05);
    }
    .grade-c .grade-circle::after { border-color: #f1c40f; }
    
    .grade-f .grade-circle {
      color: #e74c3c;
      background: rgba(231, 76, 60, 0.05);
    }
    .grade-f .grade-circle::after { border-color: #e74c3c; }

    .result-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 700;
      display: inline-block;
    }
    .badge-success { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
    .badge-warning { background: rgba(241, 196, 15, 0.15); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); }
    .badge-danger { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }

    .violation-list {
      list-style: none;
      padding: 0;
      margin: 1.5rem 0 0 0;
      text-align: left;
      width: 100%;
    }
    .violation-item {
      padding: 0.75rem 1rem 0.75rem 2.25rem;
      border-bottom: 1px solid var(--border);
      font-size: 0.92rem;
      color: #e2e8f0;
      position: relative;
    }
    .violation-item::before {
      content: '✗';
      color: #e74c3c;
      font-weight: bold;
      position: absolute;
      left: 0.75rem;
      top: 0.75rem;
    }
    
    .legal-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      margin: 1.5rem 0;
      width: 100%;
    }
    .legal-card {
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1rem;
      text-align: center;
    }
    
    @media (max-width: 768px) {
      .legal-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <section class="section">
    <div class="container">
      
      <!-- Formulario de Entrada -->
      <div class="card card--dark" style="margin-bottom:3rem;">
        <form method="POST" action="" style="display:flex; flex-direction:column; gap:1.25rem;">
          <div>
            <label for="url" style="display:block; margin-bottom:0.5rem; font-weight:600; color:#fff;">Dirección URL a auditar</label>
            <input type="text" id="url" name="url" placeholder="https://miweb.com" value="<?= h($url) ?>" required style="width:100%; padding:0.85rem 1rem; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:6px; color:#fff; font-size:1rem; outline:none; transition:border-color 0.3s;" onfocus="this.style.borderColor='var(--orange)'" onblur="this.style.borderColor='var(--border)'">
          </div>
          
          <?php if ($error): ?>
            <div style="background:rgba(231,76,60,0.1); border:1px solid rgba(231,76,60,0.3); color:#e74c3c; padding:0.75rem 1rem; border-radius:6px; font-size:0.9rem;">
              ⚠️ <?= h($error) ?>
            </div>
          <?php endif; ?>
          
          <button type="submit" class="btn btn--primary" style="align-self:flex-start; padding:0.85rem 2rem;">Iniciar Auditoría RGPD</button>
        </form>
      </div>

      <!-- Resultados del Análisis -->
      <?php if ($result): ?>
        <div class="result-container" style="display:grid; grid-template-columns: 350px 1fr; gap:2.5rem; margin-bottom:4rem;">
          
          <!-- Columna Lateral: Nota Global -->
          <div class="compliance-grade-box <?= $result['grade_class'] ?>">
            <span class="section-label" style="margin-bottom: 1rem;">Nivel de Cumplimiento</span>
            <div class="grade-circle"><?= $result['grade'] ?></div>
            <h2 style="font-size:1.6rem; color:#fff; margin-bottom:0.5rem;">Puntuación: <?= $result['score'] ?>/100</h2>
            <p style="font-size:0.9rem; color:#94a3b8; line-height:1.5;"><?= h($result['grade_desc']) ?></p>
            
            <?php if ($result['ssl_invalid']): ?>
              <div style="background:rgba(241,196,15,0.1); border:1px solid rgba(241,196,15,0.3); color:#f1c40f; padding:0.75rem; border-radius:6px; font-size:0.8rem; margin-top:1.5rem; text-align:left;">
                ⚠️ <strong>Aviso:</strong> El certificado SSL de esta web no es válido o ha expirado. Hemos procedido con el análisis desactivando la comprobación estricta de SSL.
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Columna Principal: Informe Técnico -->
          <div style="display:flex; flex-direction:column; gap:2rem;">
            
            <!-- Listado de Violaciones Detectadas -->
            <div class="card card--dark" style="padding:2rem;">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Infracciones y Advertencias</h3>
              <?php if (empty($result['violations'])): ?>
                <div style="background:rgba(46,204,113,0.1); border:1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:0.85rem 1rem; border-radius:6px; font-size:0.92rem;">
                  ✓ ¡Impecable! No se han detectado infracciones de cookies ni scripts cargados directamente. Tu web está bien configurada.
                </div>
              <?php else: ?>
                <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:0.5rem;">Se han detectado los siguientes riesgos legales que podrían ser objeto de sanción por la AEPD (Agencia Española de Protección de Datos):</p>
                <ul class="violation-list">
                  <?php foreach ($result['violations'] as $violation): ?>
                    <li class="violation-item"><?= h($violation) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>

            <!-- Menú Legal / Enlaces obligatorios -->
            <div class="card card--dark" style="padding:2rem;">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Presencia de Páginas Legales Obligatorias</h3>
              <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:1rem;">Toda web corporativa o que recopile analíticas debe enlazar de manera visible estas políticas:</p>
              <div class="legal-grid">
                <?php foreach ($result['legal_pages'] as $name => $data): ?>
                  <div class="legal-card">
                    <div style="font-size:1.25rem; margin-bottom:0.35rem;"><?= $data['found'] ? '🟢' : '🔴' ?></div>
                    <strong style="color:#fff; font-size:0.9rem; display:block;"><?= h($name) ?></strong>
                    <span style="font-size:0.8rem; color:#64748b;"><?= $data['found'] ? 'Enlace detectado' : 'No encontrado' ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Tabla de Cookies de 1er Impacto -->
            <div class="card card--dark" style="padding:2rem;">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Cookies Creadas en el Primer Impacto (Sin Consentir)</h3>
              <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:1rem;">Las cookies que se depositan inmediatamente en el navegador al abrir la URL antes de pulsar ningún botón de banner:</p>
              
              <?php if (empty($result['cookies_set'])): ?>
                <div style="background:rgba(46,204,113,0.05); border:1px solid rgba(46,204,113,0.1); color:#2ecc71; padding:0.85rem 1rem; border-radius:6px; font-size:0.9rem;">
                  ✓ No se ha instalado ninguna cookie en el navegador durante la carga inicial.
                </div>
              <?php else: ?>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:#fff;">
                        <th style="padding:0.75rem 0.5rem;">Nombre</th>
                        <th style="padding:0.75rem 0.5rem;">Proveedor</th>
                        <th style="padding:0.75rem 0.5rem;">Propósito</th>
                        <th style="padding:0.75rem 0.5rem;">Tipo / Severidad</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['cookies_set'] as $name => $data): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                          <td style="padding:0.75rem 0.5rem; font-weight:700; color:#fff;"><?= h($name) ?></td>
                          <td style="padding:0.75rem 0.5rem;"><?= h($data['provider']) ?></td>
                          <td style="padding:0.75rem 0.5rem; color:#94a3b8;"><?= h($data['description']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <?php if ($data['category'] === 'necesaria'): ?>
                              <span class="result-badge badge-success">Necesaria / Técnica</span>
                            <?php else: ?>
                              <span class="result-badge badge-danger">No Consentida (Incumple)</span>
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
            <div class="card card--dark" style="padding:2rem;">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Scripts de Seguimiento en HTML</h3>
              <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:1rem;">Scripts de terceros detectados en el código de la web y su estado de bloqueo:</p>
              
              <?php if (empty($result['detected_scripts'])): ?>
                <div style="background:rgba(46,204,113,0.05); border:1px solid rgba(46,204,113,0.1); color:#2ecc71; padding:0.85rem 1rem; border-radius:6px; font-size:0.9rem;">
                  ✓ No se han encontrado scripts de seguimiento conocidos cargados en el código HTML de esta página.
                </div>
              <?php else: ?>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:#fff;">
                        <th style="padding:0.75rem 0.5rem;">Herramienta</th>
                        <th style="padding:0.75rem 0.5rem;">Origen (SRC)</th>
                        <th style="padding:0.75rem 0.5rem;">Estado</th>
                        <th style="padding:0.75rem 0.5rem;">Cumplimiento</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['detected_scripts'] as $name => $data): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                          <td style="padding:0.75rem 0.5rem; font-weight:700; color:#fff;"><?= h($name) ?></td>
                          <td style="padding:0.75rem 0.5rem; font-family:monospace; font-size:0.8rem; color:#94a3b8;"><?= h($data['src']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <?= $data['blocked'] ? 'Bloqueado (tipo text/plain)' : 'Ejecución directa' ?>
                          </td>
                          <td style="padding:0.75rem 0.5rem;">
                            <?php if ($data['blocked']): ?>
                              <span class="result-badge badge-success">Correcto</span>
                            <?php else: ?>
                              <span class="result-badge badge-danger">Infracción</span>
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
              <div class="card card--dark" style="padding:2rem;">
                <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Cadena de Redireccionamientos Auditados</h3>
                <ol style="margin:0; padding-left:1.25rem; font-size:0.9rem; color:#e2e8f0; display:flex; flex-direction:column; gap:0.5rem;">
                  <?php foreach ($result['redirects'] as $index => $r): ?>
                    <li>
                      <strong>Redirección [<?= $r['code'] ?>]</strong>: 
                      <span style="font-family:monospace; font-size:0.8rem; color:#94a3b8; display:block; margin-top:0.15rem; word-break:break-all;"><?= h($r['from']) ?> → <?= h($r['to']) ?></span>
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
        <h2>Por qué la AEPD está multando a las webs con el consentimiento de cookies</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>El falso banner de "Aceptar" y "Aceptar"</h3>
            <p>Muchos desarrolladores cometen el error de instalar un banner visual y creer que están en regla. Sin embargo, la normativa de cookies europea y española dicta que **el botón de rechazar debe estar al mismo nivel de visibilidad** que el de aceptar.</p>
            <p>Si tu banner obliga al usuario a navegar por 3 pantallas para denegar o carece de botón directo de rechazo en su pantalla inicial, tu web está infringiendo la ley de forma flagrante y puede recibir denuncias.</p>
          </div>
          
          <div class="criterio-card">
            <h3>La trampa de Google Analytics 4 (GA4)</h3>
            <p>Para la ley europea, un identificador de usuario único (como el ID del parámetro `_ga` que usa Analytics) es considerado un **dato personal**. Por lo tanto, no se puede almacenar en el navegador ni transmitir a servidores de Google antes de que el usuario lo autorice de forma afirmativa.</p>
            <p>Muchas plantillas de WordPress inyectan GA4 de forma dura en el `head` bloqueando únicamente de manera visual la barra. Esto instala las cookies de Analytics de inmediato al primer milisegundo de entrada, resultando en una multa económica.</p>
          </div>

          <div class="criterio-card">
            <h3>Cómo solucionar el bloqueo sin perder WPO</h3>
            <p>Para cumplir al 100%, debes modificar tus etiquetas `<script>` de marketing y analítica para que carguen con el tipo `type="text/plain"` y un identificador de categoría. De este modo, el navegador las ignorará por completo durante la carga inicial.</p>
            <p>Cuando el usuario interactúa y acepta tus cookies, un script de consentimiento ligero de código abierto (como **`vanilla-cookieconsent`**) reactiva esos scripts en vivo en memoria sin ralentizar la carga inicial de tu servidor.</p>
          </div>
        </div>
      </div>

      <!-- Widget de Votación y Rich Snippet -->
      <?php render_rating_widget('auditor-privacidad'); ?>

    </div>
  </section>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Tu diagnóstico muestra infracciones de privacidad?',
    'subtitle'  => 'Cumplir con el RGPD no consiste solo en no recibir multas, sino en garantizar la seguridad y privacidad de tus propios clientes.',
    'btn_label' => 'Quiero hacer mi web 100% legal',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
