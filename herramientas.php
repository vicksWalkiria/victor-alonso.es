<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

$error = null;
$result = null;
$url = '';

// Procesar el análisis de URL si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'analizar') {
    $url = trim($_POST['url'] ?? '');
    
    if (empty($url)) {
        $error = 'Por favor, introduce una URL válida.';
    } else {
        // Asegurar esquema HTTP/HTTPS
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        // Validar URL
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $error = 'El formato de la URL no es válido.';
        } else {
            // Configurar cURL para el análisis
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt_header: curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Capturamos redirecciones manualmente
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'VictorAlonsoSEOBot/1.0 (+https://www.victor-alonso.es/herramientas)');

            $start_time = microtime(true);
            $response = curl_exec($ch);
            $ttfb = round((microtime(true) - $start_time) * 1000); // Milisegundos aproximados

            if (curl_errno($ch)) {
                $error = 'No se ha podido conectar con el servidor: ' . curl_error($ch);
            } else {
                $info = curl_getinfo($ch);
                $header_size = $info['header_size'];
                $headers_raw = substr($response, 0, $header_size);
                $html_content = substr($response, $header_size);
                
                // Parsear cabeceras HTTP
                $headers = [];
                foreach (explode("\r\n", $headers_raw) as $line) {
                    if (strpos($line, ':') !== false) {
                        list($key, $val) = explode(':', $line, 2);
                        $headers[strtolower(trim($key))] = trim($val);
                    }
                }

                // Extracción de datos On-Page básicos mediante DOMDocument
                libxml_use_internal_errors(true);
                $dom = new DOMDocument();
                // Forzar codificación UTF-8 para evitar caracteres extraños
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

                // Analizar cabeceras de seguridad
                $security_headers = [
                    'x-frame-options' => $headers['x-frame-options'] ?? null,
                    'x-content-type-options' => $headers['x-content-type-options'] ?? null,
                    'referrer-policy' => $headers['referrer-policy'] ?? null,
                    'content-security-policy' => $headers['content-security-policy'] ?? null,
                ];

                // Diagnóstico y consejos
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
    'title'        => 'Herramientas SEO Técnicas gratuitas | Víctor Alonso',
    'description'  => 'Prueba nuestras herramientas SEO técnico: analizador en tiempo real de URLs (TTFB, headers, canonicals) y generador de Schema JSON-LD LocalBusiness.',
    'canonical'    => '/herramientas',
    'body_class'   => 'page-herramientas',
    'schema_types' => [],
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => ''],
    ],
]);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="herramientas-h1">
    <div class="container">
      <h1 id="herramientas-h1">Herramientas SEO con <span>criterio técnico</span></h1>
      <p class="page-hero-desc">Herramientas útiles, sin registro y de alto rendimiento. Diseñadas para auditar y estructurar los datos técnicos reales de cualquier proyecto web.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      
      <!-- Selector de herramientas -->
      <div class="tools-tabs" role="tablist" aria-label="Herramientas SEO">
        <button class="tool-tab active" id="tab-auditor" role="tab" aria-selected="true" aria-controls="panel-auditor">
          1. Analizador de URLs (Auditor SEO)
        </button>
        <button class="tool-tab" id="tab-schema" role="tab" aria-selected="false" aria-controls="panel-schema">
          2. Generador de Schema JSON-LD
        </button>
      </div>

      <!-- PANEL 1: AUDITOR DE URL -->
      <div class="tool-panel active" id="panel-auditor" role="tabpanel" aria-labelledby="tab-auditor">
        
        <div class="tool-intro">
          <h2>Analizador técnico de URLs en vivo</h2>
          <p>Introduce cualquier URL de tu sitio web. Haremos una petición HTTP directa para comprobar cómo responde tu servidor a Googlebot, midiendo el TTFB real, los metadatos on-page y la presencia de configuraciones de indexación o seguridad indispensables.</p>
        </div>

        <form action="/herramientas#panel-auditor" method="POST" class="tool-form" style="margin-bottom:2rem">
          <input type="hidden" name="action" value="analizar">
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

        <!-- Textos de criterio - NO COMMODITY - Auditor -->
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

      <!-- PANEL 2: SCHEMA GENERATOR -->
      <div class="tool-panel" id="panel-schema" role="tabpanel" aria-labelledby="tab-schema">
        
        <div class="tool-intro">
          <h2>Generador dinámico de Schema LocalBusiness JSON-LD</h2>
          <p>Rellena los datos de tu negocio a continuación. Generaremos en tiempo real el marcado de datos estructurados recomendado para SEO local. Solo copia el código e instálalo en el `<head>` de tu página.</p>
        </div>

        <div class="schema-tool-grid">
          
          <!-- Formulario interactivo -->
          <div class="schema-inputs card">
            <h3 style="margin-bottom:1.25rem;color:var(--orange)">1. Introduce los datos del negocio</h3>
            
            <div class="form-group">
              <label class="form-label" for="sc-type">Tipo de Negocio</label>
              <select class="form-select" id="sc-type">
                <option value="LocalBusiness">Negocio Local Genérico (LocalBusiness)</option>
                <option value="ProfessionalService" selected>Servicio Profesional (ProfessionalService)</option>
                <option value="LegalService">Servicio Legal / Abogados (LegalService)</option>
                <option value="MedicalBusiness">Clínica / Servicio Médico (MedicalBusiness)</option>
                <option value="AutomotiveBusiness">Taller / Negocio de Automoción</option>
                <option value="Restaurant">Restaurante / Hostelería</option>
                <option value="Store">Tienda Física / Ecommerce Local</option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="sc-name">Nombre del Negocio *</label>
                <input type="text" class="form-input" id="sc-name" value="Mi Negocio SEO" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="sc-url">URL Principal *</label>
                <input type="url" class="form-input" id="sc-url" value="https://minegocio.com" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="sc-phone">Teléfono *</label>
                <input type="tel" class="form-input" id="sc-phone" value="+34 600 000 000" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="sc-logo">URL del Logo (opcional)</label>
                <input type="url" class="form-input" id="sc-logo" placeholder="https://minegocio.com/logo.png">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" style="grid-column: span 2">
                <label class="form-label" for="sc-street">Calle y número *</label>
                <input type="text" class="form-input" id="sc-street" value="Calle Ancha 10" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="sc-postal">Código Postal *</label>
                <input type="text" class="form-input" id="sc-postal" value="02001" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="sc-locality">Ciudad / Municipio *</label>
                <input type="text" class="form-input" id="sc-locality" value="Albacete" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="sc-lat">Latitud (coordenada) *</label>
                <input type="text" class="form-input" id="sc-lat" value="38.9942">
              </div>
              <div class="form-group">
                <label class="form-label" for="sc-lng">Longitud (coordenada) *</label>
                <input type="text" class="form-input" id="sc-lng" value="-1.8585">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="sc-same">Redes sociales (una por línea)</label>
              <textarea class="form-textarea" id="sc-same" rows="3" placeholder="https://www.facebook.com/minegocio&#10;https://www.instagram.com/minegocio"></textarea>
            </div>
          </div>

          <!-- Código JSON-LD Generado -->
          <div class="schema-output card" style="display:flex;flex-direction:column;justify-content:space-between">
            <div>
              <h3 style="margin-bottom:1rem;color:var(--orange)">2. Código JSON-LD Generado</h3>
              <p style="font-size:.82rem;color:var(--muted);margin-bottom:1rem">Este código le dice a los algoritmos de Google exactamente qué eres, dónde estás y cómo contactar contigo de manera estructurada.</p>
              <pre class="schema-code-box"><code id="schema-code"></code></pre>
            </div>
            
            <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem">
              <button class="btn btn--primary" id="btn-copy-schema">Copiar código JSON-LD</button>
              <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener noreferrer" class="btn btn--ghost">Validar en Google</a>
            </div>
          </div>

        </div>

        <!-- Textos de criterio - NO COMMODITY - Schema -->
        <div class="criterio-section" style="margin-top:3.5rem">
          <span class="section-label">Verdades del SEO técnico</span>
          <h2>La realidad sobre los datos estructurados en SEO Local</h2>
          
          <div class="criterio-grid">
            <div class="criterio-card">
              <h3>Schema no te hará subir posiciones mágicamente</h3>
              <p>Mucha gente en el mundillo te dirá que inyectar Schema LocalBusiness te subirá directamente al top del Local Pack de Google Maps. No funciona así. El marcado estructurado es una capa de entendimiento semántico.</p>
              <p>No añade ranking; reduce fricción. Le ahorra recursos a Googlebot a la hora de relacionar los datos NAP de tu web con tu perfil de Google Business Profile. Si a Google le cuesta menos entender quién eres, catalogará tu negocio de manera correcta más rápido.</p>
            </div>

            <div class="criterio-card">
              <h3>La consistencia del NAP es innegociable</h3>
              <p>El nombre, dirección y teléfono (NAP) que definas en tu Schema JSON-LD debe ser EXACTAMENTE idéntico al que figura en tu ficha de Google Maps y en cualquier directorio relevante. Si en un sitio escribes "Calle Iris 25" y en tu schema pones "C/ Iris, Nº 25, Bajo", el algoritmo puede tener problemas para consolidar esas entidades como un único negocio sólido, restando confianza local.</p>
            </div>

            <div class="criterio-card">
              <h3>El riesgo de los marcados fraudulentos</h3>
              <p>Google monitoriza y penaliza el marcado fraudulento o inconsistente con el contenido visible de la página. Si marcas valoraciones de estrellas ficticias o utilizas un tipo de negocio que no se corresponde con tu actividad real, puedes recibir una acción manual en Search Console por datos estructurados engañosos.</p>
            </div>
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
  require __DIR__ . '/includes/cta.php';
  ?>

</main>

<!-- JS interactivo para cambiar pestañas y generar el schema en vivo -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Lógica de Pestañas
    const tabs = document.querySelectorAll('.tool-tab');
    const panels = document.querySelectorAll('.tool-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            panels.forEach(p => p.classList.remove('active'));

            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            const targetId = tab.getAttribute('aria-controls');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // Mantener la pestaña activa si hay un hash en la URL (ej: al enviar formulario POST)
    if (window.location.hash) {
        const hash = window.location.hash;
        if (hash === '#panel-auditor') {
            document.getElementById('tab-auditor').click();
        } else if (hash === '#panel-schema') {
            document.getElementById('tab-schema').click();
        }
    }

    // 2. Lógica del Generador de Schema JSON-LD
    const scType = document.getElementById('sc-type');
    const scName = document.getElementById('sc-name');
    const scUrl = document.getElementById('sc-url');
    const scPhone = document.getElementById('sc-phone');
    const scLogo = document.getElementById('sc-logo');
    const scStreet = document.getElementById('sc-street');
    const scPostal = document.getElementById('sc-postal');
    const scLocality = document.getElementById('sc-locality');
    const scLat = document.getElementById('sc-lat');
    const scLng = document.getElementById('sc-lng');
    const scSame = document.getElementById('sc-same');
    const schemaCode = document.getElementById('schema-code');
    const btnCopy = document.getElementById('btn-copy-schema');

    function generateSchema() {
        const logoVal = scLogo.value.trim();
        const sameAsVal = scSame.value.trim().split('\n').filter(url => url.trim() !== '');

        const schema = {
            "@context": "https://schema.org",
            "@type": scType.value,
            "name": scName.value.trim(),
            "url": scUrl.value.trim(),
            "telephone": scPhone.value.trim(),
            "address": {
                "@type": "PostalAddress",
                "streetAddress": scStreet.value.trim(),
                "postalCode": scPostal.value.trim(),
                "addressLocality": scLocality.value.trim(),
                "addressCountry": "ES"
            }
        };

        if (logoVal) {
            schema["logo"] = logoVal;
        }

        const lat = scLat.value.trim();
        const lng = scLng.value.trim();
        if (lat && lng) {
            schema["geo"] = {
                "@type": "GeoCoordinates",
                "latitude": lat,
                "longitude": lng
            };
        }

        if (sameAsVal.length > 0) {
            schema["sameAs"] = sameAsVal;
        }

        const jsonString = `<script type="application/ld+json">\n${JSON.stringify(schema, null, 4)}\n<\/script>`;
        schemaCode.textContent = jsonString;
    }

    // Escuchar cambios en todos los inputs
    const inputs = [scType, scName, scUrl, scPhone, scLogo, scStreet, scPostal, scLocality, scLat, scLng, scSame];
    inputs.forEach(input => {
        if(input) {
            input.addEventListener('input', generateSchema);
            input.addEventListener('change', generateSchema);
        }
    });

    // Generar por primera vez al cargar
    generateSchema();

    // Copiar código
    btnCopy.addEventListener('click', function() {
        navigator.clipboard.writeText(schemaCode.textContent).then(() => {
            const originalText = btnCopy.textContent;
            btnCopy.textContent = '¡Copiado con éxito!';
            btnCopy.style.background = '#2ecc71';
            setTimeout(() => {
                btnCopy.textContent = originalText;
                btnCopy.style.background = '';
            }, 2000);
        }).catch(err => {
            alert('No se pudo copiar de forma automática. Selecciona el código manualmente.');
        });
    });

});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
