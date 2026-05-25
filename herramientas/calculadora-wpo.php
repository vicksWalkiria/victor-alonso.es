<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';
require_once dirname(__DIR__) . '/includes/wpo-psi-helper.php';

// ─── 1. INTERCEPTAR ACCIÓN AJAX DE ANÁLISIS WPO ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'wpo_analyze') {
    header('Content-Type: application/json');

    // Sanitizar y capturar parámetros
    $url = trim($_POST['url'] ?? '');
    $visits = isset($_POST['visits']) ? (int)$_POST['visits'] : 0;
    $ticket = isset($_POST['ticket']) ? (float)$_POST['ticket'] : 0.0;
    $conversion = isset($_POST['conversion']) ? (float)$_POST['conversion'] : 1.5;

    // Validación básica de entradas
    if (empty($url)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, introduce la URL de tu sitio web.']);
        exit;
    }

    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        echo json_encode(['success' => false, 'message' => 'El formato de la URL no es válido.']);
        exit;
    }

    if ($visits <= 0 || $ticket <= 0) {
        echo json_encode(['success' => false, 'message' => 'Las visitas y el ticket medio deben ser mayores que cero.']);
        exit;
    }

    $url = wpo_psi_normalize_url($url);

    // Caché por URL (12 h): evita agotar la cuota de Google (~60 req / 100 s por clave)
    $cached = wpo_psi_cache_get($url);
    if ($cached !== null) {
        echo json_encode(wpo_psi_build_response($cached, $visits, $ticket, $conversion, true));
        exit;
    }

    $ip = wpo_psi_client_ip();
    $wait = wpo_psi_rate_limit_remaining($ip);
    if ($wait > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Demasiados análisis seguidos. Espera {$wait} s antes de analizar otra URL nueva (Google limita la cuota por minuto).",
        ]);
        exit;
    }

    wpo_psi_rate_limit_touch($ip);
    $fetch = wpo_psi_fetch($url);

    if (!$fetch['ok']) {
        echo json_encode(['success' => false, 'message' => $fetch['message']]);
        exit;
    }

    wpo_psi_cache_set($url, $fetch['payload']);
    echo json_encode(wpo_psi_build_response($fetch['payload'], $visits, $ticket, $conversion));
    exit;
}

// ─── 2. INTERCEPTAR ACCIÓN AJAX DE VOTACIÓN ──────────────────────────────────
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

// ─── 3. CARGA DE LA PÁGINA (GET) ─────────────────────────────────────────────
$page = page_config([
    'title'        => 'Calculadora WPO: Pérdida de Dinero por Carga Lenta',
    'description'  => 'Simulador interactivo en vivo. Analiza tu web con Lighthouse de Google y calcula el dinero que pierdes por tiempos de carga lentos (LCP).',
    'canonical'    => '/herramientas/calculadora-wpo/',
    'body_class'   => 'page-calculadora-wpo',
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'calculadora-wpo',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Calculadora WPO', 'url' => ''],
    ],
    'faq_items' => [
        [
            'q' => '¿Qué precisión tienen los resultados de esta calculadora WPO?',
            'a' => 'La calculadora extrae métricas sintéticas (Lab Data) directamente desde la API oficial de Google Lighthouse en su entorno de emulación móvil (Moto G4). Las pérdidas económicas son simulaciones conservadoras basadas en estudios de conversión del sector retail, donde se documenta que cada segundo adicional de retardo en LCP merma la conversión en torno a un 7%. Úsala como mapa de referencia, no como un dato analítico absoluto.'
        ],
        [
            'q' => '¿Qué métrica de rendimiento (Web Vitals) es la más crítica para la conversión?',
            'a' => 'El LCP (Largest Contentful Paint) es la métrica de negocio más dura. Mide el tiempo que tarda en pintarse el bloque de contenido más grande de la zona visible. Si tu hero o la ficha de producto tardan más de 2.5 segundos en cargar, el cerebro del usuario desconecta por frustración y las probabilidades de rebote se disparan radicalmente.'
        ],
        [
            'q' => '¿Con instalar un plugin de caché en WordPress resuelvo mi WPO?',
            'a' => 'No. Un plugin de caché es una tirita, no una operación a corazón abierto. Optimizar la velocidad de verdad requiere auditar el código fuente, aligerar el árbol DOM, diferir peticiones de JavaScript de terceros, servir imágenes en formato Next-Gen y asegurarse de que el TTFB (latencia de respuesta pura del servidor) sea excelente.'
        ],
        [
            'q' => '¿Por qué Lighthouse penaliza siempre el tráfico móvil?',
            'a' => 'Porque Lighthouse simula una conexión de red móvil promedio (Throttle 4G lento) y CPU limitada. Google sabe que más del 70% del tráfico comercial proviene de terminales móviles en movimiento, no desde fibra óptica en un ordenador de sobremesa. Si tu web no es rápida en ese escenario de estrés real, estás perdiendo dinero.'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="calc-h1">
    <div class="container">
      <h1 id="calc-h1">Calculadora WPO: <span>cuánto dinero pierde tu web por cargar lenta</span></h1>
      <p class="page-hero-desc">Simulador de negocio en vivo. Conectamos el rendimiento técnico medido por Google PageSpeed Insights con las pérdidas estimadas de facturación de tu negocio por fricción en la tasa de conversión.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">

      <div class="tool-intro">
        <h2>Simulador de impacto financiero</h2>
        <p>Introduce los datos aproximados de tu negocio para estimar el dinero que dejas de ingresar por cada segundo de retraso en la carga.</p>
      </div>

      <!-- CALCULATOR COMPONENT -->
      <div class="wpo-calculator-wrapper">
        
        <div id="wpo-form-container" class="card wpo-form-card">
          <form id="wpo-calc-form">
            <div class="form-group">
              <label class="form-label" for="wpo-url">URL de tu web a analizar <span>*</span></label>
              <input type="url" class="form-input" id="wpo-url" name="url" required placeholder="https://tuweb.com">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="wpo-visits">Visitas mensuales <span>*</span></label>
                <input type="number" class="form-input" id="wpo-visits" name="visits" required min="1" placeholder="Ej. 15000">
              </div>

              <div class="form-group">
                <label class="form-label" for="wpo-ticket">Ticket medio / valor de cliente (€) <span>*</span></label>
                <input type="number" class="form-input" id="wpo-ticket" name="ticket" required min="1" step="0.01" placeholder="Ej. 65">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="wpo-conversion">Tasa de conversión estimada (%)</label>
              <input type="number" class="form-input" id="wpo-conversion" name="conversion" step="0.01" value="1.5">
              <p class="form-hint">¿No sabes este dato? Puedes usar 1,5% como referencia inicial, 1% para una estimación conservadora o ajustarlo si conoces tu tasa real.</p>
            </div>

            <div id="wpo-error" class="diag-alert diag-alert--danger" style="display: none; margin-bottom: 1rem;" role="alert"></div>

            <div class="wpo-form-actions">
              <button type="submit" class="btn btn--primary btn--lg">
                Analizar rendimiento e impacto económico
              </button>
            </div>
          </form>
        </div>

        <!-- LOADING STATE -->
        <div id="wpo-loading" class="card card--dark wpo-state-card" style="display: none; text-align: center;">
          <div class="wpo-spinner" style="display: inline-block; width: 50px; height: 50px; border: 4px solid rgba(255,255,255,0.1); border-radius: 50%; border-top-color: var(--orange); animation: spin 1s linear infinite;"></div>
          <h3 style="color: #fff; font-size: 1.4rem; margin-top: 1.5rem; margin-bottom: 0.5rem;">Conectando con Google PageSpeed...</h3>
          <p style="color: var(--muted); font-size: 0.95rem; max-width: 480px; margin: 0 auto; line-height: 1.5;">El análisis puede tardar unos segundos porque Google ejecuta una auditoría móvil completa con Lighthouse.</p>
        </div>

        <!-- RESULTS STATE -->
        <div id="wpo-results" class="card card--dark wpo-state-card" style="display: none;">
          
          <div style="text-align: center; margin-bottom: 2.5rem;">
            <span style="display: inline-block; background: rgba(231,76,60,0.1); border: 1px solid rgba(231,76,60,0.25); color: #f87171; font-weight: 700; font-size: 0.75rem; letter-spacing: 0.08em; padding: 0.35rem 0.8rem; border-radius: 20px; text-transform: uppercase; margin-bottom: 1rem;">
              Informe WPO y Pérdidas Proyectadas
            </span>
            <h2 style="color: #fff; font-size: 1.8rem; margin-bottom: 0.5rem;">Tu web está perdiendo aproximadamente:</h2>
            <div id="res-loss-container" style="font-size: 3.5rem; font-weight: 900; line-height: 1; margin: 1.5rem 0; text-shadow: 0 4px 12px rgba(0,0,0,0.3);">
              <span id="res-loss-val">0</span> € <span style="font-size: 1.3rem; color: var(--muted); font-weight: 500;">/ mes</span>
            </div>
            <p style="color: var(--muted); font-size: 0.95rem; max-width: 580px; margin: 0 auto 1.5rem; line-height: 1.5;">Esta cifra representa el impacto financiero estimado derivado de la reducción de la tasa de conversión móvil por un LCP superior a 2.5 segundos.</p>
            
            <!-- Detalle de la Fórmula de Simulación -->
            <div id="res-formula-box" style="margin: 1.5rem auto; max-width: 480px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 1.1rem 1.25rem; font-size: 0.88rem; text-align: left;">
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #cbd5e1; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 0.4rem;">
                <span>Facturación potencial estimada:</span>
                <strong id="res-form-pot">--</strong>
              </div>
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #cbd5e1; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 0.4rem;">
                <span>LCP móvil detectado:</span>
                <strong id="res-form-lcp">--</strong>
              </div>
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #cbd5e1; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 0.4rem;">
                <span>Exceso sobre umbral (2.5 s):</span>
                <strong id="res-form-excess">--</strong>
              </div>
              <div style="display: flex; justify-content: space-between; color: #cbd5e1; padding-top: 0.2rem;">
                <span>Pérdida de conversión aplicada:</span>
                <strong id="res-form-loss-pct" style="color: var(--orange);">--</strong>
              </div>
            </div>

            <!-- Prioridad Recomendada UX -->
            <div style="margin: 1rem auto 1.5rem; max-width: 480px; background: rgba(232,104,26,0.06); border: 1px dashed rgba(232,104,26,0.3); border-radius: 8px; padding: 0.9rem 1.1rem; font-size: 0.85rem; text-align: left; line-height: 1.45;">
              <span style="display: block; color: var(--orange); font-weight: 700; margin-bottom: 0.25rem; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">
                <i class="fa-solid fa-circle-exclamation"></i> Prioridad recomendada para mitigar pérdidas:
              </span>
              <span style="color: #cbd5e1;">Revisar tiempo LCP, eliminar recursos que bloquean el renderizado, optimizar la carga de la imagen hero principal, activar la caché del servidor y auditar scripts de JavaScript de terceros.</span>
            </div>

            <p style="color: #94a3b8; font-size: 0.8rem; max-width: 580px; margin: 1.25rem auto 0; line-height: 1.45; font-style: italic;">
              * Esta cifra es una simulación orientativa basada en tus datos introducidos y métricas sintéticas Lighthouse de Google. No sustituye datos reales de Analytics, ventas o CRM.
            </p>

            <p id="res-cache-note" class="wpo-cache-note" style="display: none; margin-top: 1.25rem;"></p>
          </div>

          <!-- METRICS GRID -->
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
            <!-- Puntuación -->
            <div style="background: #111; border: 1px solid #222; border-radius: 12px; padding: 1.25rem; text-align: center; position: relative;">
              <div id="res-score-bar" style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: #e74c3c; border-top-left-radius: 12px; border-top-right-radius: 12px;"></div>
              <span style="display: block; font-size: 0.75rem; color: var(--muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Performance Score</span>
              <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <span id="res-score-val" style="font-size: 1.8rem; font-weight: 800; color: #fff;">--</span><span style="color: var(--muted); font-weight: 600;">/100</span>
              </div>
            </div>
            
            <!-- LCP -->
            <div style="background: #111; border: 1px solid #222; border-radius: 12px; padding: 1.25rem; text-align: center; position: relative;">
              <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: #3498db; border-top-left-radius: 12px; border-top-right-radius: 12px;"></div>
              <span style="display: block; font-size: 0.75rem; color: var(--muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 0.5rem;" title="Largest Contentful Paint (Tiempo de Carga Principal)">Tiempo LCP</span>
              <span id="res-lcp-val" style="font-size: 1.8rem; font-weight: 800; color: #fff; display: block;">--</span>
            </div>

            <!-- CLS -->
            <div style="background: #111; border: 1px solid #222; border-radius: 12px; padding: 1.25rem; text-align: center; position: relative;">
              <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: #9b59b6; border-top-left-radius: 12px; border-top-right-radius: 12px;"></div>
              <span style="display: block; font-size: 0.75rem; color: var(--muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 0.5rem;" title="Cumulative Layout Shift (Estabilidad Visual)">CLS</span>
              <span id="res-cls-val" style="font-size: 1.8rem; font-weight: 800; color: #fff; display: block;">--</span>
            </div>
          </div>

          <!-- COMPARTE -->
          <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 1rem; padding: 1.25rem 0; border-top: 1px solid #2a2a2a; border-bottom: 1px solid #2a2a2a; margin-bottom: 2.5rem; font-size: 0.9rem;">
            <span style="color: var(--muted); font-weight: 600;"><i class="fa-solid fa-share-nodes" style="color: var(--orange); margin-right: 0.25rem;"></i> Compartir informe:</span>
            
            <button type="button" onclick="wpoCopyReport()" class="wpo-btn-share" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; background: #222; color: #fff; padding: 0.5rem 1rem; border-radius: 20px; transition: all 0.2s; cursor: pointer;">
              <i class="fa-solid fa-copy"></i> Copiar
            </button>

            <a id="share-tw" href="#" target="_blank" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; background: rgba(29,161,242,0.1); color: #1da1f2; padding: 0.5rem 1rem; border-radius: 20px; transition: all 0.2s;">
              <i class="fa-brands fa-twitter"></i> Twitter / X
            </a>

            <a id="share-li" href="#" target="_blank" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; background: rgba(10,102,194,0.1); color: #0a66c2; padding: 0.5rem 1rem; border-radius: 20px; transition: all 0.2s;">
              <i class="fa-brands fa-linkedin"></i> LinkedIn
            </a>
          </div>

          <!-- LILA ADAPTED CONVERSION BOX -->
          <div id="res-cta-box" style="background: #312e81; border: 1px solid #4338ca; border-radius: 16px; padding: 2.25rem; text-align: center; box-shadow: var(--shadow); position: relative; overflow: hidden;">
            <span id="res-cta-badge" style="display: inline-block; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #f87171; font-weight: 700; font-size: 0.72rem; letter-spacing: 0.08em; padding: 0.25rem 0.75rem; border-radius: 20px; text-transform: uppercase; margin-bottom: 1rem;">
              Diagnóstico técnico
            </span>
            <h3 id="res-cta-title" style="color: #fff; font-size: 1.45rem; font-weight: 800; margin-bottom: 0.75rem;">¿Quieres que resuelva los problemas que penalizan esta URL?</h3>
            <p id="res-cta-desc" style="color: #cbd5e1; font-size: 0.92rem; max-width: 580px; margin: 0 auto 1.5rem; line-height: 1.55;">Analizaré de manera manual el LCP y TBT de tu web para crear un plan de optimización WPO quirúrgico que detenga la fuga de conversión.</p>
            
            <div style="margin-top: 1.5rem;">
              <a id="res-cta-btn" href="/contacto/" class="btn btn--primary btn--lg" style="box-shadow: 0 4px 16px rgba(232,104,26,0.35); font-size: 1rem; font-weight: 700;">
                Optimizar mi velocidad web
              </a>
            </div>
            <p style="color: #94a3b8; font-size: 0.75rem; margin-top: 0.75rem; font-style: italic;">Sin rodeos comerciales ni auditorías automáticas inútiles. Trato directo con ingeniero.</p>
          </div>

          <div style="text-align: center; margin-top: 2rem;">
            <button type="button" id="wpo-reset-btn" style="color: var(--muted); font-weight: 600; font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='var(--orange)'" onmouseout="this.style.color='var(--muted)'">
              <i class="fa-solid fa-rotate-left" style="margin-right: 0.25rem;"></i> Analizar otra página web
            </button>
          </div>

        </div>

      </div>

      <!-- TEXTOS DE CRITERIO - NO COMMODITY -->
      <div class="criterio-section" style="margin-top: 4.5rem; border-top: 1px solid var(--border); padding-top: 4rem;">
        <span class="section-label">WPO de trinchera</span>
        <h2>¿Por qué 1 segundo de carga ralentiza tu facturación?</h2>
        
        <div class="criterio-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-top: 2rem;">
          <div class="criterio-card" style="background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem;">
            <h3 style="color: var(--black); margin-bottom: 0.75rem;"><span style="color:var(--orange)">01.</span> La paciencia móvil no perdona</h3>
            <p style="font-size: 0.9rem; color: var(--muted); line-height: 1.6;">El tráfico móvil representa más del 65% de las visitas comerciales actuales. En redes móviles (4G/5G con latencias inestables), un LCP superior a 2.5 segundos dispara el rebote. Un usuario frustrado no espera: vuelve al buscador y compra en la web de tu competidor.</p>
          </div>

          <div class="criterio-card" style="background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem;">
            <h3 style="color: var(--black); margin-bottom: 0.75rem;"><span style="color:var(--orange)">02.</span> El impacto de conversión estimado</h3>
            <p style="font-size: 0.9rem; color: var(--muted); line-height: 1.6;">Diversos estudios de rendimiento web han asociado los retrasos de carga con caídas relevantes en conversión. Para hacer una estimación conservadora, el simulador aplica una pérdida del 7% por cada segundo adicional sobre el umbral recomendado de LCP.</p>
          </div>

          <div class="criterio-card" style="background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem;">
            <h3 style="color: var(--black); margin-bottom: 0.75rem;"><span style="color:var(--orange)">03.</span> Experiencia de página y visibilidad</h3>
            <p style="font-size: 0.9rem; color: var(--muted); line-height: 1.6;">Las Core Web Vitals forman parte de las señales de experiencia de página. Una web lenta no suele hundirse solo por eso, pero en igualdad de condiciones puede perder competitividad frente a resultados más rápidos y cómodos.</p>
          </div>
        </div>
      </div>

      <!-- CÓMO FUNCIONA LA HERRAMIENTA -->
      <div class="criterio-section" style="margin-top: 4rem; border-top: 1px solid var(--border); padding-top: 4rem;">
        <span class="section-label">Especificaciones técnicas</span>
        <h2>Transparencia absoluta: cómo funciona este simulador</h2>
        <div class="criterio-grid" style="grid-template-columns: 1fr 1fr; gap: 2.5rem; margin-top: 2rem;">
          <div>
            <h3 style="color: var(--orange); font-size: 1.1rem; margin-bottom: 0.5rem;">Conexión directa con Google Lighthouse</h3>
            <p style="font-size: 0.92rem; color: var(--muted); line-height: 1.6;">Al pulsar el botón, nuestro servidor se conecta directamente a la API oficial de <strong>Google PageSpeed Insights v5</strong>. Solicitamos un escaneo en entorno móvil emulado (que simula una conexión de red móvil promedio con hardware de gama media, que es el escenario de mayor estrés y realismo comercial). Extraemos del JSON de respuesta el LCP, CLS y TBT del informe Lighthouse móvil.</p>
          </div>
          <div>
            <h3 style="color: var(--orange); font-size: 1.1rem; margin-bottom: 0.5rem;">La fórmula matemática</h3>
            <p style="font-size: 0.92rem; color: var(--muted); line-height: 1.6;">Calculamos la facturación base estimada mensual (`Visitas * Tasa de Conversión * Ticket`). Google define que un LCP excelente está por debajo de **2.5 segundos**. Por cada segundo que tu web supere esa frontera de confort, restamos un **7%** a tu facturación potencial por fricción. La pérdida máxima se acota por seguridad al 90% para reflejar la realidad operativa.</p>
          </div>
        </div>
      </div>

      <!-- WIDGET DE VOTACIÓN Y AGGREGATE RATING -->
      <?php render_rating_widget('calculadora-wpo', '¿Te ha sido útil esta calculadora WPO?'); ?>

    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Tu simulador arroja cifras rojas y lentitud?',
    'subtitle'  => 'No dejes dinero sobre la mesa por un hosting barato o código sin optimizar. Puedo meter tu web al quirófano WPO y arañar cada milisegundo.',
    'btn_label' => 'Optimizar mi velocidad web',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];

  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<script>
// Almacenar el último análisis globalmente para compartir
let wpoLastAnalysis = {};

function wpoCopyReport() {
    if (!wpoLastAnalysis.url) return;
    const text = `Simulación de Pérdidas WPO por Carga Lenta 🚀\n\n` +
        `URL: ${wpoLastAnalysis.url}\n` +
        `Performance Score: ${wpoLastAnalysis.score}/100\n` +
        `Tiempo LCP: ${wpoLastAnalysis.lcp}\n` +
        `Pérdidas mensuales estimadas: ${wpoLastAnalysis.loss} €/mes\n\n` +
        `Calcula tu impacto gratis en Víctor Alonso SEO:\nhttps://www.victor-alonso.es/herramientas/calculadora-wpo/`;
        
    navigator.clipboard.writeText(text).then(() => {
        alert('¡Informe copiado al portapapeles! Listo para enviar por Slack, WhatsApp o email.');
    }).catch(err => {
        console.error('Error al copiar el informe:', err);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wpo-calc-form');
    const formContainer = document.getElementById('wpo-form-container');
    const loadingBox = document.getElementById('wpo-loading');
    const resultsBox = document.getElementById('wpo-results');
    const errorBox = document.getElementById('wpo-error');
    const resetBtn = document.getElementById('wpo-reset-btn');

    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Limpiar estados de UI
        errorBox.style.display = 'none';
        formContainer.style.display = 'none';
        loadingBox.style.display = 'block';

        // Recopilar datos
        const formData = new FormData(form);
        formData.append('action', 'wpo_analyze');

        // Hacer petición AJAX a la misma URL de la página
        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            loadingBox.style.display = 'none';

            if (!res.success) {
                formContainer.style.display = 'block';
                errorBox.innerText = res.message || 'Ocurrió un error inesperado al analizar la URL.';
                errorBox.style.display = 'block';
                return;
            }

            const d = res.data;
            wpoLastAnalysis = {
                url: document.getElementById('wpo-url').value,
                score: d.score,
                lcp: d.metrics.lcp,
                loss: new Intl.NumberFormat('es-ES').format(d.financials.revenue_lost)
            };

            // Rellenar valores de resultados
            document.getElementById('res-loss-val').innerText = wpoLastAnalysis.loss;
            
            const lossContainer = document.getElementById('res-loss-container');
            if (d.financials.revenue_lost > 0) {
                lossContainer.style.color = '#ef4444'; // Rojo si hay pérdidas
            } else {
                lossContainer.style.color = '#10b981'; // Verde si carga excelente
            }

            document.getElementById('res-score-val').innerText = d.score;
            document.getElementById('res-lcp-val').innerText = d.metrics.lcp;
            document.getElementById('res-cls-val').innerText = d.metrics.cls;

            // Rellenar desglose de la fórmula de simulación
            const formatCurrency = (val) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(val);
            document.getElementById('res-form-pot').innerText = formatCurrency(d.financials.projected_revenue);
            document.getElementById('res-form-lcp').innerText = d.metrics.lcp;
            
            const lcpSecs = parseFloat(d.metrics.lcp);
            const excess = Math.max(0, lcpSecs - 2.5);
            document.getElementById('res-form-excess').innerText = excess.toFixed(2) + ' s';
            document.getElementById('res-form-loss-pct').innerText = d.financials.loss_percentage + ' %';

            const cacheNote = document.getElementById('res-cache-note');
            if (d.cache_note) {
                cacheNote.innerText = d.cache_note;
                cacheNote.style.display = 'block';
            } else {
                cacheNote.style.display = 'none';
            }

            // Configurar barra de color del score
            const scoreBar = document.getElementById('res-score-bar');
            if (d.tier === 'green') {
                scoreBar.style.background = '#10b981';
            } else if (d.tier === 'orange') {
                scoreBar.style.background = '#f59e0b';
            } else {
                scoreBar.style.background = '#ef4444';
            }

            // Adaptar Caja de Conversión de trinchera
            const ctaBadge = document.getElementById('res-cta-badge');
            const ctaTitle = document.getElementById('res-cta-title');
            const ctaDesc = document.getElementById('res-cta-desc');
            const ctaBtn = document.getElementById('res-cta-btn');

            if (d.tier === 'green') {
                ctaBadge.innerText = 'Rendimiento excelente';
                ctaBadge.style.background = 'rgba(16,185,129,0.15)';
                ctaBadge.style.borderColor = 'rgba(16,185,129,0.3)';
                ctaBadge.style.color = '#34d399';
                
                ctaTitle.innerText = '¿Quieres mantener este nivel de velocidad de élite?';
                ctaDesc.innerText = 'Tu web carga excepcionalmente rápido. Sin embargo, para maximizar las ventas, te aconsejo realizar revisiones frecuentes de indexación, arquitectura web y CRO para que sigas convirtiendo al 100%.';
                ctaBtn.innerText = 'Optimizar mi velocidad web';
            } else {
                ctaBadge.innerText = 'Diagnóstico técnico';
                ctaBadge.style.background = 'rgba(239,68,68,0.15)';
                ctaBadge.style.borderColor = 'rgba(239,68,68,0.3)';
                ctaBadge.style.color = '#f87171';

                ctaTitle.innerText = '¿Quieres que resuelva los problemas que penalizan esta URL?';
                ctaDesc.innerText = 'Analizaré de manera manual el LCP y TBT de tu web para crear un plan de optimización WPO quirúrgico que detenga la fuga de conversión.';
                ctaBtn.innerText = 'Optimizar mi velocidad web';
            }

            // Configurar enlaces para compartir
            const shareText = encodeURIComponent(`He analizado mi web en la calculadora WPO de Víctor Alonso 🚀\n\nScore: ${d.score}/100\nLCP: ${d.metrics.lcp}\nPérdida estimada: ${wpoLastAnalysis.loss} €/mes\n\n¡Comprueba gratis si tu web pierde ventas aquí!`);
            const shareUrl = encodeURIComponent('https://www.victor-alonso.es/herramientas/calculadora-wpo/');
            
            document.getElementById('share-tw').href = `https://twitter.com/intent/tweet?text=${shareText}&url=${shareUrl}`;
            document.getElementById('share-li').href = `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`;

            // Mostrar resultados
            resultsBox.style.display = 'block';
        })
        .catch(err => {
            loadingBox.style.display = 'none';
            formContainer.style.display = 'block';
            errorBox.innerText = 'Ocurrió un error al conectar con el servidor. Inténtalo de nuevo.';
            errorBox.style.display = 'block';
            console.error(err);
        });
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            resultsBox.style.display = 'none';
            formContainer.style.display = 'block';
            // No reseteamos los valores para facilitar pruebas iterativas al usuario
        });
    }
});
</script>

<style>
.wpo-calculator-wrapper { max-width: 860px; margin: 0 auto; }
.wpo-form-card { margin-bottom: 0; }
.wpo-form-card #wpo-calc-form { display: grid; gap: 0.25rem; }
.wpo-form-actions { text-align: center; margin-top: 1.25rem; }
.wpo-form-actions .btn { width: 100%; justify-content: center; font-size: 1.05rem; }
.form-hint { color: var(--muted); font-size: 0.82rem; margin-top: 0.4rem; line-height: 1.45; }
.wpo-state-card { margin-top: 0; }
.card--dark .wpo-muted { color: #9ca3af; }
.wpo-cache-note { font-size: 0.82rem; color: #9ca3af; margin-top: 0.75rem; font-style: italic; }
.wpo-btn-share:hover { background: #333 !important; }
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
