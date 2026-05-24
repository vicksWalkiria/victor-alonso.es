<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';

// Interceptar acción AJAX de votación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
    header('Content-Type: application/json');
    $tool_id = trim($_POST['tool_id'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    
    // Evitar que voten dos veces (Cookie por 1 año)
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

// Procesar el análisis de URL si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $headers = [];
            $info = [];
            $ttfb_total = 0;
            $bucle_detectado = false;
            $visitadas = [$current_url];
            $ssl_invalid_detected = false;

            while ($redirect_count <= $max_redirects) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $current_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_USERAGENT, 'VictorAlonsoSEOBot/1.0 (+https://www.victor-alonso.es/herramientas/analizador-seo)');

                $response = curl_exec($ch);
                
                // Si falla por problemas de certificado SSL, activamos la alerta y reintentamos desactivando la verificación estricta para poder auditar
                if (curl_errno($ch) && (curl_errno($ch) == 60 || curl_errno($ch) == 51 || stripos(curl_error($ch), 'ssl') !== false || stripos(curl_error($ch), 'certificate') !== false)) {
                    $ssl_invalid_detected = true;
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    $response = curl_exec($ch);
                }

                if (curl_errno($ch)) {
                    $error = 'No se ha podido conectar con el servidor: ' . curl_error($ch);
                    curl_close($ch);
                    break;
                }

                $info = curl_getinfo($ch);
                $step_ttfb = round($info['starttransfer_time'] * 1000);
                $ttfb_total += $step_ttfb;

                $header_size = $info['header_size'];
                $headers_raw = substr($response, 0, $header_size);
                $html_content = substr($response, $header_size);
                
                $step_headers = [];
                foreach (explode("\r\n", $headers_raw) as $line) {
                    if (strpos($line, ':') !== false) {
                        list($key, $val) = explode(':', $line, 2);
                        $step_headers[strtolower(trim($key))] = trim($val);
                    }
                }

                $status_code = $info['http_code'];

                // Si es redirección (3xx)
                if ($status_code >= 300 && $status_code < 400 && isset($step_headers['location'])) {
                    $location = $step_headers['location'];
                    
                    // Resolver URL relativa a absoluta si es necesario
                    if (!preg_match('~^https?://~i', $location)) {
                        $parsed_origin = parse_url($current_url);
                        $base = $parsed_origin['scheme'] . '://' . $parsed_origin['host'];
                        if (isset($parsed_origin['port'])) {
                            $base .= ':' . $parsed_origin['port'];
                        }
                        if (strpos($location, '/') === 0) {
                            $location = $base . $location;
                        } else {
                            $path = isset($parsed_origin['path']) ? dirname($parsed_origin['path']) : '';
                            $location = $base . '/' . ltrim($path . '/' . $location, '/');
                        }
                    }

                    $redirect_chain[] = [
                        'from' => $current_url,
                        'to' => $location,
                        'status' => $status_code,
                        'ttfb' => $step_ttfb
                    ];

                    // Evitar bucles infinitos comparando si ya la visitamos
                    if (in_array($location, $visitadas)) {
                        $bucle_detectado = true;
                        $error = '¡Bucle infinito de redirecciones detectado! La URL está atrapada en un ciclo sin fin.';
                        curl_close($ch);
                        break;
                    }

                    $visitadas[] = $location;
                    $current_url = $location;
                    $redirect_count++;
                    curl_close($ch);
                } else {
                    // Es 200, 404, 500, etc. Rompemos y procesamos el contenido
                    $headers = $step_headers;
                    curl_close($ch);
                    break;
                }
            }

            if ($redirect_count > $max_redirects && !$bucle_detectado) {
                $error = 'La URL supera el límite máximo de 5 redirecciones. Esto consume Crawl Budget en exceso y confunde a los rastreadores.';
            }

            if (empty($error)) {
                $ttfb = $ttfb_total; // TTFB acumulado de toda la cadena

                libxml_use_internal_errors(true);
                $dom = new DOMDocument();
                @$dom->loadHTML('<?xml encoding="UTF-8">' . $html_content);
                libxml_clear_errors();

                $title = '';
                $title_nodes = $dom->getElementsByTagName('title');
                if ($title_nodes->length > 0) {
                    $title = trim($title_nodes->item(0)->nodeValue);
                }

                $meta_desc = '';
                $meta_robots = '';
                $metas = $dom->getElementsByTagName('meta');
                for ($i = 0; $i < $metas->length; $i++) {
                    $meta = $metas->item($i);
                    $name = strtolower($meta->getAttribute('name'));
                    if ($name === 'description') {
                        $meta_desc = trim($meta->getAttribute('content'));
                    }
                    if ($name === 'robots') {
                        $meta_robots = trim($meta->getAttribute('content'));
                    }
                }

                $canonical = '';
                $links = $dom->getElementsByTagName('link');
                for ($i = 0; $i < $links->length; $i++) {
                    $link = $links->item($i);
                    if (strtolower($link->getAttribute('rel')) === 'canonical') {
                        $canonical = trim($link->getAttribute('href'));
                    }
                }

                $h1s = [];
                $h1_nodes = $dom->getElementsByTagName('h1');
                for ($i = 0; $i < $h1_nodes->length; $i++) {
                    $h1s[] = trim($h1_nodes->item($i)->nodeValue);
                }

                $security_headers = [
                    'x-frame-options' => $headers['x-frame-options'] ?? null,
                    'x-content-type-options' => $headers['x-content-type-options'] ?? null,
                    'referrer-policy' => $headers['referrer-policy'] ?? null,
                    'content-security-policy' => $headers['content-security-policy'] ?? null,
                ];

                $diagnostico = [];

                if ($info['http_code'] >= 400) {
                    $diagnostico[] = [
                        'type' => 'danger',
                        'title' => 'La URL devuelve un error HTTP ' . $info['http_code'],
                        'desc' => 'La página no está respondiendo correctamente. Si esta URL debe posicionar, corrige el estado HTTP antes de trabajar cualquier optimización SEO.'
                    ];
                } elseif ($info['http_code'] !== 200 && $info['http_code'] < 300) {
                    $diagnostico[] = [
                        'type' => 'warning',
                        'title' => 'Estado HTTP poco habitual',
                        'desc' => 'La URL no devuelve un 200 estándar. Conviene revisar si este comportamiento es intencionado.'
                    ];
                }

                if ($ttfb > 600) {
                    $diagnostico[] = [
                        'type' => 'warning',
                        'title' => 'Tiempo de respuesta del servidor (TTFB) elevado',
                        'desc' => "Tu servidor tardó {$ttfb}ms en responder. Por encima de 500ms, Googlebot empieza a ralentizar el rastreo y la experiencia del usuario se resiente de forma notable."
                    ];
                } else {
                    $diagnostico[] = [
                        'type' => 'success',
                        'title' => 'Excelente tiempo de respuesta (TTFB)',
                        'desc' => "Tu servidor respondió en {$ttfb}ms. Estupendo rendimiento del backend de alojamiento."
                    ];
                }

                if ($ssl_invalid_detected) {
                    $diagnostico[] = [
                        'type' => 'danger',
                        'title' => 'Certificado SSL inválido o no verificable',
                        'desc' => 'El bot de auditoría ha detectado problemas severos con la autenticidad o vigencia de tu certificado SSL. Hemos desactivado la comprobación estricta de firma de pares para poder leer los datos, pero los navegadores y Googlebot mostrarán advertencias graves de seguridad a tus usuarios.'
                    ];
                }

                if (empty($canonical)) {
                    $diagnostico[] = [
                        'type' => 'warning',
                        'title' => 'Falta etiqueta Canonical',
                        'desc' => 'Falta etiqueta Canonical. Añade una etiqueta canonical absoluta apuntando a la URL final indexable, sin parámetros ni redirecciones para evitar fragmentar o diluir la autoridad de tus enlaces.'
                    ];
                }

                if (empty($title)) {
                    $diagnostico[] = [
                        'type' => 'danger',
                        'title' => 'Falta etiqueta Title',
                        'desc' => 'El título de la página es el elemento SEO On-Page más importante. No tenerlo es un fallo técnico grave.'
                    ];
                } elseif (mb_strlen($title) > 65) {
                    $diagnostico[] = [
                        'type' => 'warning',
                        'title' => 'Título demasiado largo',
                        'desc' => "El título mide " . mb_strlen($title) . " caracteres. Google suele truncar los títulos que superan los 60-65 caracteres."
                    ];
                }

                if (empty($meta_desc)) {
                    $diagnostico[] = [
                        'type' => 'warning',
                        'title' => 'Falta Meta Description',
                        'desc' => 'Aunque no posiciona directamente, una meta description optimizada maximiza el CTR en los resultados de búsqueda.'
                    ];
                }

                if (count($h1s) === 0) {
                    $diagnostico[] = [
                        'type' => 'warning',
                        'title' => 'Sin cabecera H1',
                        'desc' => 'Tu página debe tener un único encabezado H1 que resuma la temática principal del contenido.'
                    ];
                } elseif (count($h1s) > 1) {
                    $diagnostico[] = [
                        'type' => 'warning',
                        'title' => 'Múltiples cabeceras H1',
                        'desc' => 'Hemos detectado ' . count($h1s) . ' etiquetas H1. Revisa plantilla, builder o bloque hero; normalmente este error viene de duplicar el título de página y el título visual. Aunque HTML5 lo permite, en SEO es mejor tener una única H1 clara para evitar diluir el foco semántico.'
                    ];
                }

                $is_noindex = false;
                if (!empty($meta_robots) && (stripos($meta_robots, 'noindex') !== false)) {
                    $is_noindex = true;
                }
                if (isset($headers['x-robots-tag']) && stripos($headers['x-robots-tag'], 'noindex') !== false) {
                    $is_noindex = true;
                }

                if ($is_noindex) {
                    $diagnostico[] = [
                        'type' => 'danger',
                        'title' => 'Página con directiva NOINDEX activa',
                        'desc' => 'Esta URL está bloqueada para los buscadores. Google tenderá a excluir esta URL del índice mientras la directiva siga activa.'
                    ];
                }

                if (count($redirect_chain) > 0) {
                    if (count($redirect_chain) === 1) {
                        $diagnostico[] = [
                            'type' => 'warning',
                            'title' => 'Redirección detectada (código ' . $redirect_chain[0]['status'] . ')',
                            'desc' => 'La URL consultada redirige a ' . $redirect_chain[0]['to'] . '. Hemos analizado de forma transparente la página de destino final.'
                        ];
                    } else {
                        $diagnostico[] = [
                            'type' => 'danger',
                            'title' => 'Cadena de redirecciones encadenada (' . count($redirect_chain) . ' saltos)',
                            'desc' => 'El rastreador ha tenido que dar ' . count($redirect_chain) . ' saltos antes de llegar al destino. Esto consume Crawl Budget de Google inútilmente y ralentiza al usuario. Enlaza siempre directamente al destino final.'
                        ];
                    }
                }

                $has_security_warnings = false;
                foreach ($security_headers as $key => $val) {
                    if (empty($val)) {
                        $has_security_warnings = true;
                        break;
                    }
                }
                if ($has_security_warnings) {
                    $diagnostico[] = [
                        'type' => 'warning',
                        'title' => 'Faltan cabeceras de seguridad recomendadas',
                        'desc' => 'Tu servidor no entrega algunas de las cabeceras HTTP de protección básica (como CSP, X-Frame-Options o Referrer-Policy). Esto no sustituye una auditoría de seguridad, pero sí detecta síntomas básicos de una configuración web descuidada.'
                    ];
                }

                // Capa de Scoring Avanzada y Ponderada Justamente
                $score = 100;
                
                if ($is_noindex) {
                    $score -= 30;
                }
                if ($ssl_invalid_detected) {
                    $score -= 25;
                }
                if ($info['http_code'] >= 400) {
                    $score -= 20;
                } elseif ($info['http_code'] !== 200 && $info['http_code'] < 300) {
                    $score -= 10;
                }
                if (count($redirect_chain) > 0) {
                    $score -= 15;
                }
                if ($ttfb > 600) {
                    $score -= 12;
                }
                if (empty($canonical)) {
                    $score -= 8;
                }
                if (empty($title)) {
                    $score -= 15;
                } elseif (mb_strlen($title) > 65) {
                    $score -= 5;
                }
                if (empty($meta_desc)) {
                    $score -= 4;
                }
                if (count($h1s) === 0) {
                    $score -= 8;
                } elseif (count($h1s) > 1) {
                    $score -= 5;
                }
                if ($has_security_warnings) {
                    $score -= 6;
                }
                
                $score = max(10, min(100, $score));
                
                // Determinar el riesgo principal y prioridad recomendada basado en los fallos
                $main_risk = 'Ninguno crítico detectado';
                $recommended_priority = 'Mantener buenas prácticas y monitorizar periódicamente la indexabilidad.';
                
                if ($is_noindex) {
                    $main_risk = 'Página oculta para buscadores (Directiva NOINDEX activa)';
                    $recommended_priority = 'Eliminar la directiva noindex en las metaetiquetas robots o en las cabeceras HTTP si deseas indexar esta URL.';
                } elseif ($ssl_invalid_detected) {
                    $main_risk = 'Certificado SSL inválido o no verificado';
                    $recommended_priority = 'Corregir la configuración del servidor web, instalar o renovar el certificado SSL (Let\'s Encrypt o similar) inmediatamente.';
                } elseif ($info['http_code'] >= 400) {
                    $main_risk = 'Error de estado HTTP (' . $info['http_code'] . ')';
                    $recommended_priority = 'Resolver el problema en el servidor, base de datos o CMS para que la URL devuelva un estado 200 OK.';
                } elseif (count($redirect_chain) > 1) {
                    $main_risk = 'Cadena de redirecciones encadenadas o saltos indirectos';
                    $recommended_priority = 'Apuntar todos los enlaces internos y canonicals directamente a la URL de destino final.';
                } elseif ($ttfb > 600) {
                    $main_risk = 'TTFB y latencia del servidor web elevados';
                    $recommended_priority = 'Optimizar el backend de base de datos, configurar caché de servidor o migrar a un alojamiento web optimizado para WPO/WordPress.';
                } elseif (empty($canonical)) {
                    $main_risk = 'Inconsistencia en la etiqueta canonical';
                    $recommended_priority = 'Añadir una etiqueta canonical absoluta apuntando a la URL final indexable, sin parámetros ni redirecciones.';
                } elseif (empty($title)) {
                    $main_risk = 'Ausencia de la etiqueta Title';
                    $recommended_priority = 'Escribir una etiqueta Title única y descriptiva de entre 50 y 65 caracteres.';
                } elseif (count($h1s) !== 1) {
                    $main_risk = 'Errores de estructura o jerarquía semántica H1';
                    $recommended_priority = 'Asegurar la existencia de un único encabezado H1 por página, eliminando duplicidades en bloques hero o builders.';
                }

                $result = [
                    'url' => $current_url,
                    'url_inicial' => $url,
                    'status' => $info['http_code'],
                    'ttfb' => $ttfb,
                    'title' => $title,
                    'description' => $meta_desc,
                    'canonical' => $canonical,
                    'h1s' => $h1s,
                    'robots' => $meta_robots ? $meta_robots : ($headers['x-robots-tag'] ?? 'index, follow'),
                    'security' => $security_headers,
                    'diagnostico' => $diagnostico,
                    'redirect_chain' => $redirect_chain,
                    'score' => $score,
                    'main_risk' => $main_risk,
                    'recommended_priority' => $recommended_priority
                ];
            }
            curl_close($ch);
        }
    }
}

$page = page_config([
    'title'        => 'Analizador SEO gratuito de TTFB y redirecciones',
    'description'  => 'Introduce cualquier dirección y analiza al instante la respuesta HTTP, velocidad TTFB, canonicals, robots y cabeceras de seguridad web.',
    'canonical'    => '/herramientas/analizador-seo/',
    'body_class'   => 'page-analizador-seo',
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'analizador-seo',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Analizador de URL', 'url' => ''],
    ],
    'faq_items' => [
        [
            'q' => '¿Por qué mi web obtiene una puntuación baja en la salud técnica?',
            'a' => 'La salud técnica SEO evalúa factores críticos como el código de estado HTTP, la ausencia de etiquetas fundamentales (Title o Canonical) y la velocidad de respuesta (TTFB). Una puntuación baja indica bloqueos severos que impiden a Google rastrear o interpretar correctamente tu contenido, limitando tu posicionamiento independientemente de lo buena que sea tu redacción.'
        ],
        [
            'q' => '¿Qué significa que falta la cabecera H1 o está duplicada?',
            'a' => 'La etiqueta H1 es el titular principal de tu página web. Solo debe haber uno por página para dar una señal clara y contundente a Google sobre la temática del contenido. Duplicarlo confunde semánticamente a los bots, y no tenerlo es perder una de las oportunidades de relevancia On-Page más fuertes que existen.'
        ],
        [
            'q' => '¿Por qué el TTFB (Time To First Byte) es tan importante para el SEO?',
            'a' => 'El TTFB mide el tiempo que tarda tu servidor en procesar la petición y enviar el primer byte de datos. Si tu TTFB supera los 600ms, tu servidor es lento. Google prioriza sitios rápidos; un TTFB alto agota el Crawl Budget, reduce la frecuencia de rastreo y lastra de raíz cualquier optimización web de rendimiento (Core Web Vitals).'
        ],
        [
            'q' => '¿Afectan las cabeceras de seguridad al posicionamiento orgánico?',
            'a' => 'Directamente no, pero de forma indirecta lo son todo. Cabeceras ausentes como CSP o X-Frame-Options dejan la puerta abierta a inyecciones de código malicioso o malware. Si Google detecta vulnerabilidades activas en tu sitio web, desplomará tu visibilidad y mostrará alertas rojas de "Sitio no seguro" a tus usuarios.'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<style>
@media (max-width: 768px) {
  .health-scoring-container {
    grid-template-columns: 1fr !important;
    text-align: center;
    justify-items: center;
  }
}
</style>

<main id="main">

  <section class="page-hero" aria-labelledby="analizador-h1">
    <div class="container">
      <span class="hero-eyebrow">Auditoría SEO Express</span>
      <h1 id="analizador-h1">Analizador <span>Técnico de URLs</span></h1>
      <p class="page-hero-desc">Petición HTTP directa en vivo para auditar los metadatos on-page, el tiempo de respuesta del servidor (TTFB) y la seguridad de cualquier página web.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      
      <div class="tool-intro">
        <h2>Cómo responde tu servidor a Googlebot</h2>
        <p style="margin-bottom:1.5rem">Analiza la velocidad de respuesta, directivas de indexación y cabeceras de seguridad técnica de cualquier URL en vivo.</p>
      </div>

      <form action="/herramientas/analizador-seo/" method="POST" class="tool-form" style="margin-bottom:2rem">
        <div class="form-group-row">
          <input 
            type="url" 
            name="url" 
            class="form-input" 
            value="<?= h($url ? $url : 'https://') ?>" 
            placeholder="https://tuweb.com/pagina-a-analizar" 
            required
            aria-label="URL de la página a analizar"
          >
          <button type="submit" class="btn btn--primary">Analizar URL ahora</button>
        </div>
        <?php if ($error): ?>
          <div class="alert alert--danger" style="margin-top:1rem"><?= h($error) ?></div>
        <?php endif; ?>
      </form>

      <?php if ($result): ?>
        <div class="audit-results" style="margin-top:2.5rem">
          <h3 style="margin-bottom:1.5rem;color:var(--orange)">Resultados del análisis para: <span style="color:#fff;font-weight:400"><?= h($result['url_inicial']) ?></span></h3>

          <?php if (!empty($result['redirect_chain'])): ?>
            <div class="redirect-chain-box card card--dark" style="margin-bottom:2.5rem; border-color:var(--orange)">
              <h4 style="margin-bottom:1rem; color:var(--orange); display:flex; align-items:center; gap:0.5rem">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 3h5v5M4 20L21 3M21 16v5h-5M4 4l5 5"/>
                </svg>
                Cadena de Redirecciones Detectada (<?= count($result['redirect_chain']) ?> saltos)
              </h4>
              <div style="display:flex; flex-direction:column; gap:0.75rem">
                <?php foreach ($result['redirect_chain'] as $idx => $step): ?>
                  <div style="display:flex; align-items:flex-start; gap:0.75rem; font-size:0.9rem;">
                    <span style="background:var(--orange); color:#fff; font-weight:700; border-radius:50%; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.8rem; margin-top: 2px;">
                      <?= $idx + 1 ?>
                    </span>
                    <div style="word-break:break-all; line-height:1.4">
                      <strong style="color:#fff"><?= h($step['from']) ?></strong>
                      <div style="font-size:0.8rem; margin-top:0.15rem;">
                        <span class="status-orange" style="font-weight:600">HTTP <?= h($step['status']) ?> Redirección</span> 
                        <span style="color:var(--muted)">en <?= h($step['ttfb']) ?>ms</span>
                      </div>
                    </div>
                  </div>
                  <div style="padding-left:7px; color:var(--orange); font-size:0.8rem; margin-top:-0.25rem;">↓</div>
                <?php endforeach; ?>
                <div style="display:flex; align-items:flex-start; gap:0.75rem; font-size:0.9rem;">
                  <span style="background:#2ecc71; color:#fff; font-weight:700; border-radius:50%; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.8rem; margin-top: 2px;">
                    ✓
                  </span>
                  <div style="word-break:break-all; line-height:1.4">
                    <span style="color:var(--muted)">Destino Final:</span> <strong style="color:#2ecc71"><?= h($result['url']) ?></strong>
                    <div style="font-size:0.8rem; margin-top:0.15rem">
                      <span class="status-green" style="font-weight:600">HTTP <?= h($result['status']) ?> OK</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- Cuadro de Salud Técnica de la URL -->
          <div class="health-scoring-container card card--dark" style="margin-bottom:2.5rem; display:grid; grid-template-columns: 240px 1fr; gap:2rem; align-items:center; border:1px solid rgba(255,255,255,0.08); background: #0b101c; padding: 2rem; border-radius: 12px;">
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; position:relative;">
              <svg width="130" height="130" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
                <!-- Círculo de fondo -->
                <circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8" />
                <!-- Círculo animado -->
                <?php 
                  $radius = 40;
                  $circumference = 2 * M_PI * $radius;
                  $offset = $circumference - ($result['score'] / 100) * $circumference;
                  $stroke_color = '#2ecc71';
                  if ($result['score'] < 50) {
                      $stroke_color = '#e74c3c';
                  } elseif ($result['score'] < 80) {
                      $stroke_color = 'var(--orange)';
                  }
                ?>
                <circle cx="50" cy="50" r="40" fill="none" stroke="<?= $stroke_color ?>" stroke-width="8" 
                        stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $offset ?>" 
                        stroke-linecap="round" style="transition: stroke-dashoffset 1s ease-out;" />
              </svg>
              <!-- Texto central -->
              <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); display:flex; flex-direction:column; align-items:center;">
                <span style="font-size:1.8rem; font-weight:800; color:#fff; line-height:1;"><?= $result['score'] ?></span>
                <span style="font-size:0.7rem; color:var(--muted); text-transform:uppercase; letter-spacing:1px; margin-top:3px;">/ 100</span>
              </div>
              <span style="font-size:0.95rem; font-weight:700; color:#fff; margin-top:0.75rem;">Salud Técnica</span>
            </div>
            
            <div style="display:flex; flex-direction:column; justify-content:center; gap:1.2rem;">
              <div>
                <span style="font-size:0.75rem; text-transform:uppercase; color:var(--muted); font-weight:700; letter-spacing:1px; display:block; margin-bottom:0.25rem;">Riesgo Principal Detectado</span>
                <span style="font-size:1.15rem; font-weight:700; color:#fff; display:block;">
                  <?= h($result['main_risk']) ?>
                </span>
              </div>
              
              <div>
                <span style="font-size:0.75rem; text-transform:uppercase; color:var(--muted); font-weight:700; letter-spacing:1px; display:block; margin-bottom:0.25rem;">Prioridad de Optimización</span>
                <p style="font-size:0.95rem; line-height:1.5; color:#cbd5e1; margin:0;">
                  <?= h($result['recommended_priority']) ?>
                </p>
              </div>
            </div>
          </div>

          <div class="results-summary-grid">
            <div class="summary-metric">
              <span class="metric-label">Estado HTTP</span>
              <span class="metric-value <?= $result['status'] === 200 ? 'status-green' : 'status-orange' ?>">
                <?= h($result['status']) ?>
              </span>
            </div>
            <div class="summary-metric">
              <span class="metric-label">Velocidad (TTFB)</span>
              <span class="metric-value <?= $result['ttfb'] < 400 ? 'status-green' : ($result['ttfb'] < 800 ? 'status-orange' : 'status-red') ?>">
                <?= h($result['ttfb']) ?>ms
              </span>
            </div>
            <div class="summary-metric" style="grid-column: span 2">
              <span class="metric-label">Indexabilidad (Robots)</span>
              <span class="metric-value" style="font-size:1.1rem;word-break:break-all">
                <?= h($result['robots']) ?>
              </span>
            </div>
          </div>

          <!-- Diagnósticos agrupados -->
          <div class="diagnostico-section" style="margin-top:2rem">
            <h4 style="margin-bottom:1rem">Alertas y comprobaciones</h4>
            <div style="display:grid;gap:1rem">
              <?php foreach ($result['diagnostico'] as $alert): ?>
                <div class="diag-alert diag-alert--<?= h($alert['type']) ?>">
                  <div class="diag-alert-title"><?= h($alert['title']) ?></div>
                  <div class="diag-alert-desc"><?= h($alert['desc']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Datos técnicos detallados -->
          <div class="tech-details-grid" style="margin-top:2rem">
            <div>
              <h4>Etiquetas SEO extraídas</h4>
              <table class="tech-table">
                <tr>
                  <th>Title</th>
                  <td><?= $result['title'] ? h($result['title']) : '<span style="color:var(--orange)">[Vacio]</span>' ?></td>
                </tr>
                <tr>
                  <th>Description</th>
                  <td><?= $result['description'] ? h($result['description']) : '<span style="color:var(--orange)">[Vacio]</span>' ?></td>
                </tr>
                <tr>
                  <th>Canonical</th>
                  <td><?= $result['canonical'] ? h($result['canonical']) : '<span style="color:var(--orange)">[No definido]</span>' ?></td>
                </tr>
                <tr>
                  <th>Cabecera H1</th>
                  <td>
                    <?php if (empty($result['h1s'])): ?>
                      <span style="color:var(--orange)">[No detectado]</span>
                    <?php else: ?>
                      <ul style="margin:0;padding-left:1.1rem">
                        <?php foreach ($result['h1s'] as $h1): ?>
                          <li><?= h($h1) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
            </div>

            <div>
              <h4>Cabeceras de Seguridad y Servidor</h4>
              <table class="tech-table">
                <tr>
                  <th>X-Frame-Options</th>
                  <td><?= $result['security']['x-frame-options'] ? h($result['security']['x-frame-options']) : '<span style="color:var(--muted)">[Inexistente]</span>' ?></td>
                </tr>
                <tr>
                  <th>X-Content-Type-Options</th>
                  <td><?= $result['security']['x-content-type-options'] ? h($result['security']['x-content-type-options']) : '<span style="color:var(--muted)">[Inexistente]</span>' ?></td>
                </tr>
                <tr>
                  <th>Referrer-Policy</th>
                  <td><?= $result['security']['referrer-policy'] ? h($result['security']['referrer-policy']) : '<span style="color:var(--muted)">[Inexistente]</span>' ?></td>
                </tr>
                <tr>
                  <th>Content-Security-Policy</th>
                  <td><?= $result['security']['content-security-policy'] ? '<span style="color:#2ecc71">Configurada (CSP activa)</span>' : '<span style="color:var(--muted)">[Inexistente]</span>' ?></td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Glosario Didáctico de Métricas y Cabeceras -->
          <div class="criterio-section" style="margin-top:3.5rem; border-top: 1px solid var(--border); padding-top:3rem;">
            <span class="section-label">Glosario de trinchera</span>
            <h2 style="margin-bottom:1.5rem">¿Qué significa cada métrica del análisis?</h2>
            
            <div class="criterio-grid" style="grid-template-columns: 1fr 1fr; gap:2.5rem;">
              <div>
                <h3 style="color:#fff; font-size:1.15rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Etiquetas SEO On-Page</h3>
                <ul style="list-style:none; padding:0; display:grid; gap:1.25rem;">
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Etiqueta Title</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Es el título oficial de la página web de cara a los buscadores. Aparece como el enlace principal azul en los resultados de búsqueda de Google. Debe ser persuasivo, descriptivo y contener la palabra clave principal de tu negocio.</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Meta Description</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">El extracto o resumen de texto que aparece debajo del título en los buscadores. Aunque no posiciona de forma directa, una buena meta descripción incita a hacer clic sobre tu enlace, incrementando el CTR (porcentaje de clics por impresiones).</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Etiqueta Canonical</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Le indica a Google cuál es la URL original y oficial de una página web. Es una directiva vital si tienes parámetros de rastreo o variaciones de página, ya que evita penalizaciones por duplicidad de contenido en buscadores.</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Cabecera H1</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">El título visual principal en tu contenido. Cada página web debe contar con un único y exclusivo encabezado H1 para estructurar semánticamente el contenido y dejar claro de qué trata tu página web de un solo vistazo.</span>
                  </li>
                </ul>
              </div>
              
              <div>
                <h3 style="color:#fff; font-size:1.15rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Cabeceras de Seguridad y Servidor</h3>
                <ul style="list-style:none; padding:0; display:grid; gap:1.25rem;">
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">X-Frame-Options</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Evita ataques de secuestro de clics (<em>Clickjacking</em>). Esta cabecera le prohíbe a navegadores ajenos incrustar tu sitio web en marcos (<code>&lt;iframe&gt;</code>) externos y maliciosos diseñados para engañar a tus usuarios.</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">X-Content-Type-Options</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Protege tu servidor de inyecciones de código bloqueando el <em>MIME Sniffing</em>. Fuerza al navegador a seguir rígidamente el tipo de archivo declarado (por ejemplo, impidiendo que un archivo de texto plano o una imagen se interprete como código JavaScript ejecutable).</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Referrer-Policy</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Regula el nivel de privacidad de tu sitio controlando la cantidad de datos que envías a terceras webs cuando un usuario pulsa un enlace externo (evitando la filtración de URLs internas o tokens de sesión en el referer).</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Content-Security-Policy (CSP)</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Es la directiva definitiva de seguridad web. Establece un listado estricto y de confianza de qué orígenes, dominios y recursos pueden cargar y ejecutar código en tu web, neutralizando por completo inyecciones de virus y scripts maliciosos (ataques XSS).</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          
          <!-- Cómo funciona la herramienta (Transparencia Técnica) -->
          <div class="criterio-section" style="margin-top:4rem; border-top: 1px solid var(--border); padding-top:4rem;">
            <span class="section-label">Especificaciones técnicas</span>
            <h2>Cómo funciona este analizador (y por qué es tan rápido)</h2>
            <div class="criterio-grid" style="grid-template-columns: 1fr 1fr; gap:2.5rem; margin-top:2rem;">
              <div>
                <h3 style="color:var(--orange); font-size:1.1rem; margin-bottom:.5rem;">1. Medición de TTFB real en Servidor</h3>
                <p style="font-size:.92rem; color:var(--text); line-height:1.6;">No es una simulación visual. Cuando envías el formulario, el servidor ejecuta una petición física en tiempo real a través de <strong>cURL</strong>. La herramienta monitoriza el parámetro <code>CURLINFO_STARTTRANSFER_TIME</code> para obtener el registro exacto del instante en que se recibe la respuesta inicial del servidor remoto. Con esta métrica obtenemos una estimación bastante precisa del tiempo hasta primer byte desde el servidor donde se ejecuta la herramienta.</p>
              </div>
              <div>
                <h3 style="color:var(--orange); font-size:1.1rem; margin-bottom:.5rem;">2. ¿Por qué se procesa de forma casi instantánea?</h3>
                <p style="font-size:.92rem; color:var(--text); line-height:1.6;">Esta herramienta está alojada en la infraestructura Cloud de alto rendimiento de <strong>Oracle Cloud</strong> con conexiones troncales de fibra óptica empresariales. A diferencia de un navegador web común o Lighthouse (que tiene que descargar imágenes, CSS, ejecutar scripts pesados de terceros y renderizar el árbol DOM), el bot de auditoría <strong>solo descarga el código HTML plano</strong> de la página, resolviendo la conexión de servidor a servidor en milisegundos.</p>
                <p style="font-size:.92rem; color:var(--muted); line-height:1.6; margin-top:0.75rem;"><em>Nota: Si analizas esta misma web (victor-alonso.es), el servidor se conectará a sí mismo mediante la interfaz local (loopback), eliminando la latencia externa de red y arrojando registros de respuesta de entre 10ms y 20ms.</em></p>
              </div>
            </div>
          </div>

        </div>
      <?php endif; ?>

      <!-- Textos de criterio - NO COMMODITY -->
      <div class="criterio-section" style="margin-top:3.5rem">
        <span class="section-label">Desde la trinchera técnica</span>
        <h2>Por qué estos datos determinan la salud de tu web</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>El mito del TTFB y los plugins mágicos</h3>
            <p>Muchos consultores te dirán que instales un plugin de caché en WordPress y des por solucionado el WPO. Mentira. El TTFB (Time to First Byte) mide la velocidad del servidor procesando PHP antes de enviar el primer bit.</p>
            <p>Si tu backend tarda más de 500ms en pensar, ningún plugin de JS o CSS va a camuflar esa latencia. El TTFB alto se arregla con hosting de calidad, optimización de queries a base de datos y eliminando código innecesario en el core de tu desarrollo.</p>
          </div>
          
          <div class="criterio-card">
            <h3>La pesadilla oculta del Canonical</h3>
            <p>Google suele ignorar etiquetas canonical que apuntan a páginas con errores 404, redirecciones o urls con etiquetas de noindex. Si tu canonical no es impecable, el algoritmo tomará el control, decidirá por su cuenta cuál es tu página principal y puede fragmentar o diluir la autoridad de tus enlaces.</p>
          </div>

          <div class="criterio-card">
            <h3>Cabeceras de seguridad y SEO: la conexión indirecta</h3>
            <p>¿Qué tiene que ver `X-Frame-Options` con posicionar? Directamente, nada. Indirectamente, todo. No tener estas directivas facilita ataques como el Clickjacking o inyecciones de Scripts maliciosos que insertan enlaces ocultos. Si Google detecta malware en tu web, puede mostrar advertencias de seguridad, reducir drásticamente la confianza del resultado y hundir el tráfico orgánico hasta que se resuelva el problema.</p>
          </div>
        </div>
      </div>

      <!-- Widget de Votación y Rich Snippet -->
      <?php render_rating_widget('analizador-seo'); ?>

    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Tu diagnóstico muestra alertas rojas o lentitud?',
    'subtitle'  => 'El SEO técnico de tu web es el cimiento de todo tu negocio. Si no carga rápido y limpio, tu competencia te sacará ventaja.',
    'btn_label' => 'Solicitar diagnóstico manual gratuito',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
