<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

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
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'VictorAlonsoSEOBot/1.0 (+https://www.victor-alonso.es/herramientas/analizador-seo)');

            $start_time = microtime(true);
            $response = curl_exec($ch);
            $ttfb = round((microtime(true) - $start_time) * 1000);

            if (curl_errno($ch)) {
                $error = 'No se ha podido conectar con el servidor: ' . curl_error($ch);
            } else {
                $info = curl_getinfo($ch);
                $header_size = $info['header_size'];
                $headers_raw = substr($response, 0, $header_size);
                $html_content = substr($response, $header_size);
                
                $headers = [];
                foreach (explode("\r\n", $headers_raw) as $line) {
                    if (strpos($line, ':') !== false) {
                        list($key, $val) = explode(':', $line, 2);
                        $headers[strtolower(trim($key))] = trim($val);
                    }
                }

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
                        'desc' => 'Hemos detectado ' . count($h1s) . ' etiquetas H1. Aunque HTML5 lo permite, en SEO es mejor tener una única H1 clara para evitar diluir el foco semántico.'
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
                        'desc' => 'Esta URL está bloqueada para los buscadores. Google no la indexará bajo ninguna circunstancia.'
                    ];
                }

                $result = [
                    'url' => $url,
                    'status' => $info['http_code'],
                    'ttfb' => $ttfb,
                    'title' => $title,
                    'description' => $meta_desc,
                    'canonical' => $canonical,
                    'h1s' => $h1s,
                    'robots' => $meta_robots ? $meta_robots : ($headers['x-robots-tag'] ?? 'index, follow'),
                    'security' => $security_headers,
                    'diagnostico' => $diagnostico
                ];
            }
            curl_close($ch);
        }
    }
}

$page = page_config([
    'title'        => 'Analizador Técnico de URLs en vivo gratis | Víctor Alonso',
    'description'  => 'Introduce cualquier dirección y analiza al instante la respuesta HTTP, velocidad TTFB, canonicals, robots y cabeceras de seguridad web.',
    'canonical'    => '/herramientas/analizador-seo',
    'body_class'   => 'page-analizador-seo',
    'schema_types' => [],
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas'],
        ['label' => 'Analizador de URL', 'url' => ''],
    ],
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

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
        <p>Introduce tu URL. Nuestro bot de auditoría simulará el comportamiento de un rastreador para diagnosticar problemas de indexabilidad, redirecciones o lentitud en tiempo real.</p>
      </div>

      <form action="/herramientas/analizador-seo" method="POST" class="tool-form" style="margin-bottom:2rem">
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
          <h3 style="margin-bottom:1.5rem;color:var(--orange)">Resultados del análisis para: <span style="color:#fff;font-weight:400"><?= h($result['url']) ?></span></h3>

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
            <p>¿Qué tiene que ver `X-Frame-Options` con posicionar? Directamente, nada. Indirectamente, todo. No tener estas directivas facilita ataques como el Clickjacking o inyecciones de Scripts maliciosos que insertan enlaces ocultos. Si Google detecta malware en tu web, te penalizará en horas mandando tu tráfico a cero.</p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Tu diagnóstico muestra alertas rojas o lentitud?',
    'subtitle'  => 'El SEO técnico de tu web es el cimiento de todo tu negocio. Si no carga rápido y limpio, tu competencia te sacará ventaja.',
    'btn_label' => 'Solicitar diagnóstico manual gratuito',
    'btn_href'  => '/contacto',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
