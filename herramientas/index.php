<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
    'title'        => 'Herramientas SEO técnico gratuitas',
    'description'  => 'Échale un ojo a mis herramientas SEO gratuitas creadas a medida para auditoría web en vivo y generación de datos estructurados Schema JSON-LD.',
    'canonical'    => '/herramientas/',
    'body_class'   => 'page-herramientas-hub',
    'schema_types' => [],
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

  <section class="section">
    <div class="container">
      
      <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 2rem;">
        
        <!-- CARD 1: ANALIZADOR DE URL -->
        <article class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
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
        <article class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
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
        <article class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
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
        <article class="card card--dark" style="display:flex;flex-direction:column;justify-content:space-between">
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
