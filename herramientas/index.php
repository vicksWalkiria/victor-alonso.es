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
        ['name' => 'Tester de .htaccess y Validador mod_rewrite', 'url' => '/herramientas/tester-htaccess/'],
        ['name' => 'Analizador de Logs Apache y Nginx', 'url' => '/herramientas/analizador-logs/'],
        ['name' => 'Generador Schema LocalBusiness JSON-LD', 'url' => '/herramientas/generador-schema-local/'],
        ['name' => 'Analizador Técnico de URLs', 'url' => '/herramientas/analizador-seo/'],
        ['name' => 'Extractor y Auditor de Sitemaps XML', 'url' => '/herramientas/extractor-sitemap/'],
        ['name' => 'Analizador de Páginas Huérfanas', 'url' => '/herramientas/analizador-paginas-huerfanas/'],
        ['name' => 'Extractor Semántico de Entidades', 'url' => '/herramientas/extractor-entidades/'],
        ['name' => 'Calculadora de Pérdidas WPO', 'url' => '/herramientas/calculadora-wpo/'],
        ['name' => 'Editor Metadatos EXIF', 'url' => '/herramientas/editor-metadatos-imagenes/'],
        ['name' => 'Auditor de Coherencia SEO Local GMB', 'url' => '/herramientas/auditor-seo-local-gmb/'],
        ['name' => 'Auditor de Cookies y Privacidad RGPD', 'url' => '/herramientas/auditor-cookies/'],
        ['name' => 'Generador de Informes GSC PDF', 'url' => '/herramientas/generador-informe-gsc/'],
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
      <p class="page-hero-desc">Soluciones técnicas interactivas, libres de registros y de alto rendimiento. Herramientas creadas por un <a href="/" style="color:var(--orange)">consultor SEO en Albacete</a> para ayudarte a auditar, estructurar y optimizar el rendimiento técnico real de tu web.</p>
    </div>
  </section>

  <style>
  .filter-btn {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.1);
    color: var(--text);
    padding: 0.4rem 1.25rem;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s;
  }
  .filter-btn:hover, .filter-btn.active {
    background: var(--orange);
    color: #fff;
    border-color: var(--orange);
  }
  .tool-spec {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.4rem;
    font-size: 0.82rem;
    color: rgba(255,255,255,0.8);
    line-height: 1.4;
  }
  .tool-spec strong {
    color: var(--orange);
    min-width: 65px;
    font-weight: 700;
  }
  .tool-card-item {
    transition: opacity 0.3s ease, transform 0.3s ease;
  }
  .tool-card-item.hidden {
    display: none !important;
  }
  </style>

  <section class="section">
    <div class="container">
      
      <!-- Filtros Visuales por Categoría -->
      <div id="tool-filters" style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:3rem;justify-content:center;background:rgba(0,0,0,0.1);padding:1rem;border-radius:12px;">
        <button class="filter-btn active" data-filter="all">Todas</button>
        <button class="filter-btn" data-filter="redirecciones">Redirecciones</button>
        <button class="filter-btn" data-filter="rastreo">Rastreo</button>
        <button class="filter-btn" data-filter="seo-local">SEO Local</button>
        <button class="filter-btn" data-filter="rendimiento">Rendimiento</button>
        <button class="filter-btn" data-filter="semantica">Semántica</button>
        <button class="filter-btn" data-filter="privacidad">Privacidad</button>
      </div>

      <div class="cards-grid" id="tools-grid" style="grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 2rem;">
        
        <!-- TESTER DE HTACCESS -->
        <article class="card card--dark tool-card-item" data-category="redirecciones" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Tester de .htaccess & Validador</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Mi simulador mod_rewrite interactivo. Prueba y depura tus reglas de redirección 301, condiciones complejas, expresiones regulares y configuraciones de Apache.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>Reglas .htaccess y URL de prueba.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Traza lógica detallada paso a paso.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Servidor (PHP seguro, sin impacto real).</span></div>
            </div>
          </div>
          <a href="/herramientas/tester-htaccess/" class="btn btn--primary tool-link" data-tool="tester-htaccess" style="width:100%;justify-content:center">Acceder al Tester</a>
        </article>

        <!-- ANALIZADOR DE LOGS -->
        <article class="card card--dark tool-card-item" data-category="rastreo rendimiento" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="20" x2="22" y2="20"></line><line x1="12" y1="17" x2="12" y2="20"></line><line x1="7" y1="8" x2="17" y2="8"></line><line x1="7" y1="12" x2="13" y2="12"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Analizador de Logs Apache & Nginx</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Audita el Crawl Budget y la salud de tu servidor analizando archivos log para detectar errores 404 ocultos y frecuencia de bots.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>Archivo Log (.log, .txt, .gz).</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Dashboard de métricas y exportación CSV/PDF.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Servidor (Procesamiento efímero RAM).</span></div>
            </div>
          </div>
          <a href="/herramientas/analizador-logs/" class="btn btn--primary tool-link" data-tool="analizador-logs" style="width:100%;justify-content:center">Acceder al Analizador</a>
        </article>

        <!-- GENERADOR SCHEMA -->
        <article class="card card--dark tool-card-item" data-category="seo-local semantica" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Generador Schema LocalBusiness</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Genera código JSON-LD para dotar de semántica básica a tu negocio local ante los ojos de Google y directorios de búsqueda.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>Datos NAP del negocio (Nombre, Dirección, Tel).</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Script JSON-LD listo para el `<head>`.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Local (Navegador JavaScript).</span></div>
            </div>
          </div>
          <a href="/herramientas/generador-schema-local/" class="btn btn--primary tool-link" data-tool="generador-schema" style="width:100%;justify-content:center">Acceder al Generador</a>
        </article>

        <!-- ANALIZADOR SEO -->
        <article class="card card--dark tool-card-item" data-category="rastreo rendimiento" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Analizador Técnico de URLs</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Auditoría express de una sola URL para validar la respuesta HTTP, el TTFB en vivo, y las directivas on-page más críticas.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>Cualquier URL pública.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Puntuación técnica y prioridades de riesgo.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Servidor (Petición cURL directa).</span></div>
            </div>
          </div>
          <a href="/herramientas/analizador-seo/" class="btn btn--primary tool-link" data-tool="analizador-seo" style="width:100%;justify-content:center">Acceder al Analizador</a>
        </article>

        <!-- EXTRACTOR SITEMAP -->
        <article class="card card--dark tool-card-item" data-category="rastreo" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><path d="M10 6.5h4v11h-4M14 17.5h7"></path>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Extractor & Auditor de Sitemaps</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Extrae recursivamente URLs de sitemaps XML e índices anidados, y expórtalas rápidamente a CSV o TXT.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>URL de Sitemap.xml.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Listado de URLs y tabla exportable.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Servidor (Descarga XML recursiva).</span></div>
            </div>
          </div>
          <a href="/herramientas/extractor-sitemap/" class="btn btn--primary tool-link" data-tool="extractor-sitemap" style="width:100%;justify-content:center">Acceder al Extractor</a>
        </article>

        <!-- ANALIZADOR HUÉRFANAS -->
        <article class="card card--dark tool-card-item" data-category="rastreo" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Analizador de Páginas Huérfanas</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Cruza automáticamente tu Sitemap con el rastreo local (Screaming Frog) para detectar contenidos desconectados de tu enlazado interno.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>Sitemap XML + CSV/TXT de rastreo.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Dataset de URLs huérfanas o no indexadas.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Servidor (Fusión de datos rápida).</span></div>
            </div>
          </div>
          <a href="/herramientas/analizador-paginas-huerfanas/" class="btn btn--primary tool-link" data-tool="paginas-huerfanas" style="width:100%;justify-content:center">Acceder al Analizador</a>
        </article>

        <!-- EXTRACTOR SEMÁNTICO -->
        <article class="card card--dark tool-card-item" data-category="semantica" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Extractor Semántico & Grafos</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Minería lingüística para extraer entidades nombradas y construir grafos semánticos, ideal para evitar brechas de contenido.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>Bloque de texto completo.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Grafo HTML5 interactivo y listas NER.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Local (Navegador JavaScript NLP).</span></div>
            </div>
          </div>
          <a href="/herramientas/extractor-entidades/" class="btn btn--primary tool-link" data-tool="extractor-entidades" style="width:100%;justify-content:center">Acceder al Extractor</a>
        </article>

        <!-- CALCULADORA WPO -->
        <article class="card card--dark tool-card-item" data-category="rendimiento" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12" y2="18"></line><line x1="8" y1="14" x2="16" y2="14"></line><line x1="8" y1="10" x2="16" y2="10"></line><line x1="8" y1="6" x2="16" y2="6"></line>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Calculadora de Pérdidas WPO</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Estima cuánto dinero podría estar perdiendo tu eCommerce mensualmente debido a latencias elevadas en tiempos de carga móviles.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>URL pública, Visitas y Ticket Medio.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Estimación económica según Google.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Servidor (API de Google PageSpeed).</span></div>
            </div>
          </div>
          <a href="/herramientas/calculadora-wpo/" class="btn btn--primary tool-link" data-tool="calculadora-wpo" style="width:100%;justify-content:center">Acceder a la Calculadora</a>
        </article>

        <!-- EDITOR METADATOS -->
        <article class="card card--dark tool-card-item" data-category="seo-local privacidad" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Editor Metadatos de Imágenes EXIF</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Añade coordenadas GPS e información oculta a tus fotografías JPG para subir señales semánticas en Google Business Profile.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>Imagen JPG.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Imagen JPG re-etiquetada.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Local (Totalmente seguro y privado).</span></div>
            </div>
          </div>
          <a href="/herramientas/editor-metadatos-imagenes/" class="btn btn--primary tool-link" data-tool="editor-metadatos" style="width:100%;justify-content:center">Acceder al Editor EXIF</a>
        </article>

        <!-- AUDITOR GMB -->
        <article class="card card--dark tool-card-item" data-category="seo-local" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Auditor de Coherencia SEO Local GMB</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Extensión de Chrome para auditar la coherencia del NAP y detectar conflictos entre tu web y la ficha de mapas.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>Pestaña de Chrome activa.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Reporte en Markdown.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Local (Extensión in-browser).</span></div>
            </div>
          </div>
          <a href="/herramientas/auditor-seo-local-gmb/" class="btn btn--primary tool-link" data-tool="auditor-gmb" style="width:100%;justify-content:center">Descargar Extensión GMB</a>
        </article>

        <!-- AUDITOR COOKIES -->
        <article class="card card--dark tool-card-item" data-category="privacidad" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><circle cx="12" cy="13" r="3"></circle>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Auditor de Cookies y Privacidad</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Detecta problemas de cumplimiento RGPD básicos analizando si se inyectan cookies antes del consentimiento explícito.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>URL pública.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Listado de cookies y scripts bloqueados.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Servidor (Headless Browser básico).</span></div>
            </div>
          </div>
          <a href="/herramientas/auditor-cookies/" class="btn btn--primary tool-link" data-tool="auditor-cookies" style="width:100%;justify-content:center">Acceder al Auditor</a>
        </article>

        <!-- GENERADOR GSC -->
        <article class="card card--dark tool-card-item" data-category="rendimiento rastreo" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <div class="card-icon" style="background: rgba(232,104,26,.1)">
              <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>
              </svg>
            </div>
            <h2 style="font-size:1.5rem;margin-bottom:.75rem;color:#fff">Generador de Informes GSC PDF</h2>
            <p style="margin-bottom:1.5rem;font-size:.95rem">Sube un export de GSC y genera un informe en PDF con gráficas vectoriales ideal para entregar a clientes.</p>
            
            <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:1rem; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.05);">
              <div class="tool-spec"><strong>Entrada:</strong> <span>Archivo ZIP de GSC.</span></div>
              <div class="tool-spec"><strong>Salida:</strong> <span>Archivo PDF renderizado en LaTeX.</span></div>
              <div class="tool-spec"><strong>Proceso:</strong> <span>Servidor (Efímero. Genera PDF y borra datos).</span></div>
            </div>
          </div>
          <a href="/herramientas/generador-informe-gsc/" class="btn btn--primary tool-link" data-tool="generador-gsc" style="width:100%;justify-content:center">Acceder al Generador</a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Configuración de Analytics Event Tracking
  const trackEvent = (eventName, params) => {
    if (typeof window.gtag === 'function') {
      window.gtag('event', eventName, params);
    } else if (typeof window.dataLayer !== 'undefined') {
      window.dataLayer.push({ event: eventName, ...params });
    }
  };

  document.querySelectorAll('.tool-link').forEach(link => {
    link.addEventListener('click', function(e) {
      trackEvent('tool_hub_click', { tool_id: this.getAttribute('data-tool') });
    });
  });

  // Lógica de Filtros
  const filterBtns = document.querySelectorAll('.filter-btn');
  const toolCards = document.querySelectorAll('.tool-card-item');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      // Remover active
      filterBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      const filterValue = this.getAttribute('data-filter');

      toolCards.forEach(card => {
        if (filterValue === 'all') {
          card.classList.remove('hidden');
        } else {
          const categories = card.getAttribute('data-category').split(' ');
          if (categories.includes(filterValue)) {
            card.classList.remove('hidden');
          } else {
            card.classList.add('hidden');
          }
        }
      });
    });
  });
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
