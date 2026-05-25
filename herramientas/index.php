<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
    'title'        => 'Herramientas SEO técnico gratuitas',
    'description'  => 'Échale un ojo a mis herramientas SEO gratuitas creadas a medida para auditoría web en vivo y generación de datos estructurados Schema JSON-LD.',
    'canonical'    => '/herramientas/',
    'body_class'   => 'page-herramientas-hub',
    'schema_types' => ['ItemList'],
    'item_list'    => [
        ['name' => 'Analizador Técnico de URLs', 'url' => '/herramientas/analizador-seo/'],
        ['name' => 'Generador Schema LocalBusiness JSON-LD', 'url' => '/herramientas/generador-schema-local/'],
        ['name' => 'Calculadora de Pérdidas WPO', 'url' => '/herramientas/calculadora-wpo/'],
        ['name' => 'Extractor y Auditor de Sitemaps XML', 'url' => '/herramientas/extractor-sitemap/'],
        ['name' => 'Extractor Semántico de Entidades', 'url' => '/herramientas/extractor-entidades/'],
        ['name' => 'Analizador de Logs Apache y Nginx', 'url' => '/herramientas/analizador-logs/'],
        ['name' => 'Auditor de Cookies y Privacidad RGPD', 'url' => '/herramientas/auditor-cookies/'],
        ['name' => 'Tester de .htaccess y Validador mod_rewrite', 'url' => '/herramientas/tester-htaccess/'],
    ],
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => ''],
    ],
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="hub-h1">
    <div class="container">
      <h1 id="hub-h1">Herramientas SEO con <span>criterio de trinchera</span></h1>
      <p class="page-hero-desc">Soluciones técnicas interactivas, libres de registros y de alto rendimiento. Herramientas creadas por un ingeniero para ayudarte a auditar, estructurar y optimizar el rendimiento técnico real de tu web.</p>
    </div>
  </section>

  <style>
  .tool-pill {
    background: rgba(232, 104, 26, 0.04) !important;
    border: 1px solid rgba(232, 104, 26, 0.3) !important;
    color: #e8681a !important;
    padding: 0.5rem 1.25rem;
    border-radius: 30px;
    font-size: 0.85rem;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    box-shadow: 0 2px 5px rgba(232, 104, 26, 0.03);
  }
  .tool-pill:hover {
    background: #e8681a !important;
    color: #ffffff !important;
    border-color: #e8681a !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(232, 104, 26, 0.25);
  }
  </style>

  <section class="section">
    <div class="container">
      
      <!-- Pastillas de Acceso Rápido (Autoscroll) -->
      <div class="tools-pills-container" style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:2.5rem;justify-content:center;">
        <span style="align-self:center;font-size:0.9rem;color:#334155;font-weight:600;margin-right:0.5rem">Accesos rápidos:</span>
        <a href="#analizador-seo" class="tool-pill">🔍 Analizador SEO</a>
        <a href="#generador-schema" class="tool-pill">📦 Schema Local</a>
        <a href="#calculadora-wpo" class="tool-pill">🧮 Calculadora WPO</a>
        <a href="#extractor-sitemap" class="tool-pill">🗺️ Auditor Sitemap</a>
        <a href="#extractor-entidades" class="tool-pill">🧠 Extractor Semántico</a>
        <a href="#analizador-logs" class="tool-pill">📊 Analizador de Logs</a>
        <a href="#auditor-cookies" class="tool-pill">🍪 Auditor de Cookies</a>
        <a href="#tester-htaccess" class="tool-pill">⚡ Tester .htaccess</a>
      </div>

      <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 2rem;">
        
        <!-- CARD 1: ANALIZADOR DE URL -->
        <article id="analizador-seo" class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="2" y1="12" x2="22" y2="12"></line>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Analizador Técnico de URLs</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Mi auditoría express en vivo. Introduce cualquier URL y analizaremos su código de respuesta HTTP, el tiempo de respuesta del servidor (TTFB real), etiquetas básicas de SEO On-Page, directivas de rastreo (`noindex`) y las cabeceras de seguridad indispensables.</p>
            <ul class="checklist" style="margin-bottom:2rem;color:rgba(255,255,255,.7)">
              <li class="checklist-item">Cálculo del TTFB exacto en milisegundos.</li>
              <li class="checklist-item">Validación de etiquetas Title, Meta Description y H1.</li>
              <li class="checklist-item">Comprobación de etiquetas canonical y directivas robots.</li>
              <li class="checklist-item">Análisis de cabeceras OWASP frente a vulnerabilidades.</li>
            </ul>
          </div>
          <a href="/herramientas/analizador-seo/" class="btn btn--primary" style="width:100%;justify-content:center">Acceder al Analizador SEO</a>
        </article>

        <!-- CARD 2: GENERADOR DE SCHEMA -->
        <article id="generador-schema" class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Generador Schema LocalBusiness</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Estructura la información de tu negocio local para que los buscadores la entiendan sin margen de error. Genera en tiempo real el marcado JSON-LD oficial según las directrices de Schema.org y Google.</p>
            <ul class="checklist" style="margin-bottom:2rem;color:rgba(255,255,255,.7)">
              <li class="checklist-item">Marcación para ProfessionalService, Store, Restaurant y más.</li>
              <li class="checklist-item">Formato de dirección estandarizado y geolocalización.</li>
              <li class="checklist-item">Integración de perfiles sociales y logotipos.</li>
              <li class="checklist-item">Código copiable con un clic y validación instantánea.</li>
            </ul>
          </div>
          <a href="/herramientas/generador-schema-local/" class="btn btn--primary" style="width:100%;justify-content:center">Acceder al Generador Schema</a>
        </article>

        <!-- CARD 3: CALCULADORA WPO -->
        <article id="calculadora-wpo" class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="2" ry="2"></rect>
                <line x1="12" y1="18" x2="12" y2="18"></line>
                <line x1="8" y1="14" x2="16" y2="14"></line>
                <line x1="8" y1="10" x2="16" y2="10"></line>
                <line x1="8" y1="6" x2="16" y2="6"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Calculadora de Pérdidas WPO</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Conecta el rendimiento técnico de tu web con tus ingresos. Analiza la URL en vivo con Google PageSpeed y calcula de forma científica cuánto dinero dejas de facturar debido a tiempos de carga móviles lentos.</p>
            <ul class="checklist" style="margin-bottom:2rem;color:rgba(255,255,255,.7)">
              <li class="checklist-item">Llamada en vivo a la API oficial de Google PageSpeed.</li>
              <li class="checklist-item">Cálculo de pérdidas según estudios oficiales de conversión.</li>
              <li class="checklist-item">Simulador interactivo adaptado a tu volumen y ticket medio.</li>
              <li class="checklist-item">Informe de rendimiento y sugerencias de WPO quirúrgico.</li>
            </ul>
          </div>
          <a href="/herramientas/calculadora-wpo/" class="btn btn--primary" style="width:100%;justify-content:center">Acceder a la Calculadora WPO</a>
        </article>

        <!-- CARD 4: EXTRACTOR DE SITEMAP -->
        <article id="extractor-sitemap" class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
                <path d="M10 6.5h4v11h-4M14 17.5h7"></path>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Extractor & Auditor de Sitemaps</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Mi herramienta de trinchera para indexabilidad. Extrae de forma recursiva todas las URLs de sitemaps XML e índices anidados, audita longitudes de enlace críticas para dispositivos móviles y expórtalas en un clic.</p>
            <ul class="checklist" style="margin-bottom:2rem;color:rgba(255,255,255,.7)">
              <li class="checklist-item">Rastreo recursivo de sub-sitemaps automático.</li>
              <li class="checklist-item">Auditoría visual de URLs críticas (>75 chars).</li>
              <li class="checklist-item">Extracción de imágenes, prioridad y lastmod.</li>
              <li class="checklist-item">Exportación profesional a CSV, JSON y TXT.</li>
            </ul>
          </div>
          <a href="/herramientas/extractor-sitemap/" class="btn btn--primary" style="width:100%;justify-content:center">Acceder al Extractor Sitemap</a>
        </article>

        <!-- CARD 5: EXTRACTOR SEMÁNTICO -->
        <article id="extractor-entidades" class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="18" cy="5" r="3"></circle>
                <circle cx="6" cy="12" r="3"></circle>
                <circle cx="18" cy="19" r="3"></circle>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Extractor Semántico & Grafos</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Mi herramienta de minería lingüística. Extrae entidades nombradas, mapea triples semánticos (Sujeto-Verbo-Objeto) y detecta brechas de cobertura semántica (Semantic Gap) frente a tus competidores.</p>
            <ul class="checklist" style="margin-bottom:2rem;color:rgba(255,255,255,.7)">
              <li class="checklist-item">Modelado de Grafo interactivo en HTML5 Canvas con físicas.</li>
              <li class="checklist-item">Minería NLP para Personas, Ubicaciones y Marcas.</li>
              <li class="checklist-item">Auditoría comparativa de solapamiento de entidades en vivo.</li>
              <li class="checklist-item">Detección automática de brechas semánticas clave de SEO.</li>
            </ul>
          </div>
          <a href="/herramientas/extractor-entidades/" class="btn btn--primary" style="width:100%;justify-content:center">Acceder al Extractor Semántico</a>
        </article>

        <!-- CARD 6: ANALIZADOR DE LOGS -->
        <article id="analizador-logs" class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="2" y1="20" x2="22" y2="20"></line>
                <line x1="12" y1="17" x2="12" y2="20"></line>
                <line x1="7" y1="8" x2="17" y2="8"></line>
                <line x1="7" y1="12" x2="13" y2="12"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Analizador de Logs Apache & Nginx</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Mi herramienta para auditar el presupuesto de rastreo (Crawl Budget) y la salud del servidor. Sube tu archivo log de accesos o pega fragmentos para visualizar de manera segura el rastreo de bots de búsqueda, errores 404 recurrentes y picos de tráfico.</p>
            <ul class="checklist" style="margin-bottom:2rem;color:rgba(255,255,255,.7)">
              <li class="checklist-item">Identificación visual de Googlebot y otros buscadores.</li>
              <li class="checklist-item">Reporte de errores 404 críticos para redireccionamiento 301.</li>
              <li class="checklist-item">Detección de IPs sospechosas y scrapers de contenido.</li>
              <li class="checklist-item">Procesamiento 100% privado y efímero en memoria (RGPD).</li>
            </ul>
          </div>
          <a href="/herramientas/analizador-logs/" class="btn btn--primary" style="width:100%;justify-content:center">Acceder al Analizador de Logs</a>
        </article>

        <!-- CARD 7: AUDITOR DE PRIVACIDAD Y COOKIES -->
        <article id="auditor-cookies" class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <circle cx="12" cy="13" r="3"></circle>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Auditor de Cookies y Privacidad</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Comprueba si tu web cumple estrictamente con el RGPD y la LOPDGDD. Rastrea en vivo las cabeceras HTTP y el código HTML para detectar cookies cargadas antes del consentimiento y scripts de terceros sin bloquear.</p>
            <ul class="checklist" style="margin-bottom:2rem;color:rgba(255,255,255,.7)">
              <li class="checklist-item">Detección de cookies de marketing y analítica en el primer impacto.</li>
              <li class="checklist-item">Análisis de scripts bloqueados (vanilla-cookieconsent, etc.).</li>
              <li class="checklist-item">Validación de enlaces obligatorios (Aviso Legal, Cookies, Privacidad).</li>
              <li class="checklist-item">Calificación visual rápida (Apto / No Apto) y plan de acción.</li>
            </ul>
          </div>
          <a href="/herramientas/auditor-cookies/" class="btn btn--primary" style="width:100%;justify-content:center">Acceder al Auditor de Cookies</a>
        </article>

        <!-- CARD 8: TESTER DE HTACCESS -->
        <article id="tester-htaccess" class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="4 17 10 11 4 5"></polyline>
                <line x1="12" y1="19" x2="20" y2="19"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Tester de .htaccess & Validador</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Mi simulador mod_rewrite interactivo. Prueba y depura tus reglas de redirección 301, condiciones complejas, expresiones regulares y configuraciones de Apache de forma 100% segura y visual.</p>
            <ul class="checklist" style="margin-bottom:2rem;color:rgba(255,255,255,.7)">
              <li class="checklist-item">Simulación instantánea sin llamadas externas ni latencia.</li>
              <li class="checklist-item">Traza detallada paso a paso explicada en español.</li>
              <li class="checklist-item">Presets listos de HTTPS, WWW, trailing slash y WordPress.</li>
              <li class="checklist-item">Detección interactiva de bucles de redirección infinitos.</li>
            </ul>
          </div>
          <a href="/herramientas/tester-htaccess/" class="btn btn--primary" style="width:100%;justify-content:center">Acceder al Tester .htaccess</a>
        </article>

      </div>

    </div>
  </section>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Necesitas un desarrollo de herramienta a medida?',
    'subtitle'  => 'Desde un estimador de presupuestos hasta integraciones con APIs externas de SEO o automatización de informes. Si puedes imaginarlo, lo puedo programar.',
    'btn_label' => 'Hablemos de tu idea técnica',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
