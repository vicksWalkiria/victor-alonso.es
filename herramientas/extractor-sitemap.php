<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';

// Desactivar límite de tiempo para sitemaps masivos recursivos
@set_time_limit(120);

// Función auxiliar: descargar contenido simulando Googlebot y admitiendo gzip
function fetch_sitemap_content($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, ''); // cURL gestiona descompresión automáticamente
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Simular Googlebot Móvil exacto
    $googlebot = 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
    curl_setopt($ch, CURLOPT_USERAGENT, $googlebot);
    
    $content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || !$content) {
        return false;
    }
    
    // Decodificación gzip de seguridad redundante
    if (substr($content, 0, 2) === "\x1f\x8b") {
        $decoded = @gzdecode($content);
        if ($decoded) {
            $content = $decoded;
        }
    }
    
    return $content;
}

// Función recursiva para procesar sitemaps e índices de sitemaps
function parse_sitemap_xml($xml_content, &$all_urls, &$processed_sitemaps, $depth = 0, $parent_url = '') {
    if ($depth > 3) return; // Protección frente a recursividad infinita
    
    // Protección XXE (XML External Entity)
    $disableEntities = libxml_disable_entity_loader(true);
    $internalErrors = libxml_use_internal_errors(true);
    
    // Cargar con namespaces de forma flexible
    $xml = @simplexml_load_string($xml_content, 'SimpleXMLElement', LIBXML_NOCDATA);
    
    if (!$xml) {
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);
        return;
    }
    
    $root_name = $xml->getName();
    
    if ($root_name === 'sitemapindex') {
        // Es un Sitemap Index: recorrer sub-sitemaps
        foreach ($xml->sitemap as $sitemap) {
            $loc = trim((string)$sitemap->loc);
            if ($loc && !in_array($loc, $processed_sitemaps)) {
                $processed_sitemaps[] = $loc;
                $sub_content = fetch_sitemap_content($loc);
                if ($sub_content) {
                    parse_sitemap_xml($sub_content, $all_urls, $processed_sitemaps, $depth + 1, $loc);
                }
            }
        }
    } elseif ($root_name === 'urlset') {
        // Es un sitemap de URLs final
        $sitemap_name = basename($parent_url ? $parent_url : 'sitemap.xml');
        
        foreach ($xml->url as $url_node) {
            $loc = trim((string)$url_node->loc);
            if (!$loc) continue;
            
            $lastmod = trim((string)$url_node->lastmod);
            $changefreq = trim((string)$url_node->changefreq);
            $priority = trim((string)$url_node->priority);
            
            // Extraer cantidad de imágenes asociadas
            $image_count = 0;
            $namespaces = $url_node->getNamespaces(true);
            if (isset($namespaces['image'])) {
                $image_nodes = $url_node->children($namespaces['image']);
                $image_count = count($image_nodes->image);
            }
            
            $all_urls[] = [
                'url' => $loc,
                'lastmod' => $lastmod ? $lastmod : '',
                'changefreq' => $changefreq ? $changefreq : '',
                'priority' => $priority ? $priority : '',
                'images' => $image_count,
                'sitemap' => $sitemap_name
            ];
        }
    }
    
    libxml_clear_errors();
    libxml_use_internal_errors($internalErrors);
    libxml_disable_entity_loader($disableEntities);
}

// Interceptar API de Extracción Ajax
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'rate') {
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
            echo json_encode(['success' => true, 'count' => $res['count'], 'average' => $res['average']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar valoración.']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'parse_sitemap') {
        header('Content-Type: application/json');
        $sitemap_url = trim($_POST['url'] ?? '');
        
        if (filter_var($sitemap_url, FILTER_VALIDATE_URL) === false) {
            echo json_encode(['success' => false, 'message' => 'La URL proporcionada no tiene un formato válido.']);
            exit;
        }
        
        $initial_content = fetch_sitemap_content($sitemap_url);
        if (!$initial_content) {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo descargar el sitemap. Verifica que la URL esté online y permita peticiones desde servidores externos.'
            ]);
            exit;
        }
        
        $all_urls = [];
        $processed_sitemaps = [$sitemap_url];
        
        parse_sitemap_xml($initial_content, $all_urls, $processed_sitemaps, 0, $sitemap_url);
        
        if (empty($all_urls)) {
            echo json_encode([
                'success' => false,
                'message' => 'El XML se descargó correctamente pero no se encontraron etiquetas <url> ni <sitemap> válidas. Comprueba el formato.'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'sitemaps_processed' => $processed_sitemaps,
            'urls' => $all_urls
        ]);
        exit;
    }
}

// Configuración de Página
$page = page_config([
    'title'        => 'Extractor de Sitemaps XML y Auditor de URLs Profesional',
    'description'  => 'Extrae gratis todas las URLs de cualquier Sitemap XML o Índice de Sitemaps de forma recursiva. Filtra, audita longitudes y exporta a CSV/TXT.',
    'canonical'    => '/herramientas/extractor-sitemap/',
    'body_class'   => 'page-extractor-sitemap',
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'extractor-sitemap',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Extractor Sitemap XML', 'url' => ''],
    ],
    'faq_items' => [
        [
            'q' => '¿Por qué mi sitemap dice tener 500 URLs pero Google solo indexa 100?',
            'a' => 'Existen multitud de factores: desde que el sitemap contenga URLs bloqueadas por robots.txt, canonicalizadas hacia otra variante, marcadas como noindex, o simplemente que tu web no tenga la autoridad suficiente (Crawl Demand) para que Google procese todas tus páginas profundas.'
        ],
        [
            'q' => '¿Cuántas URLs o Megabytes puede tener un sitemap como máximo?',
            'a' => 'El protocolo oficial establece un límite estricto de 50.000 URLs (etiquetas <loc>) y un peso máximo de 50 MB por archivo. Si superas estos umbrales, debes dividir tu mapa web mediante un Sitemap Index.'
        ],
        [
            'q' => '¿Es necesario enviar el Sitemap todos los días a Search Console?',
            'a' => 'No. Solo debes enviarlo la primera vez. A partir de ahí, Googlebot leerá automáticamente la etiqueta <lastmod> para descubrir nuevo contenido. Puedes acelerar el proceso declarando la ruta de tu sitemap dentro de tu archivo robots.txt.'
        ],
        [
            'q' => '¿Debería incluir en el Sitemap páginas de políticas, avisos legales y paginaciones?',
            'a' => 'Generalmente no. El Sitemap XML es tu escaparate "VIP" para Google. Solo debe contener URLs canónicas, con código de estado 200 y que aporten valor comercial o de captación. Evita malgastar Crawl Budget en páginas huérfanas de utilidad orgánica.'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="sitemap-h1">
    <div class="container">
      <span class="hero-eyebrow">Auditoría de Rastreo y Arquitectura</span>
      <h1 id="sitemap-h1">Extractor de <span>Sitemaps XML</span></h1>
      <p class="page-hero-desc">Bypassea restricciones CORS y cortafuegos. Extrae, unifica y audita sitemaps anidados de forma recursiva simulando Googlebot.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      
      <div class="tool-intro">
        <h2>Audita la indexabilidad de tu arquitectura web</h2>
        <p>Introduce la URL de tu sitemap XML o sitemap index. Mi servidor descargará recursivamente la estructura, clasificará los tipos de URLs, auditará métricas técnicas y te permitirá exportarlas listas para Screaming Frog, GSC o Excel.</p>
      </div>

      <div class="extractor-grid">
        
        <!-- Bloque de Entrada / Configuración -->
        <div class="card card--dark border-orange" style="padding: 2.5rem; border-radius: 1.5rem; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
                
                <div>
                    <h3 style="color: var(--orange); margin-bottom: 1.25rem; font-size: 1.3rem; font-weight: 800; text-transform: uppercase;">1. Indicar origen del Sitemap</h3>
                    
                    <!-- Pestañas de entrada -->
                    <div class="tab-container" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                        <button class="tab-btn active" onclick="switchTab('tab-url')">URL de Sitemap</button>
                        <button class="tab-btn" onclick="switchTab('tab-upload')">Carga de Fichero / XML</button>
                    </div>

                    <!-- Entrada URL -->
                    <div id="tab-url" class="tab-content active">
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label" for="sm-url">URL de Sitemap XML (.xml o .xml.gz)</label>
                            <input type="url" class="form-input" id="sm-url" value="https://www.victor-alonso.es/sitemap.xml" placeholder="https://tuweb.com/sitemap.xml" style="font-family: monospace;">
                        </div>
                        <button class="btn btn--primary" id="btn-parse-sitemap" style="width: 100%; justify-content: center; padding: 1.1rem;">
                            <span id="btn-text">Extraer URLs recursivamente</span>
                            <span id="btn-loader" class="loader-spinner" style="display: none;"></span>
                        </button>
                    </div>

                    <!-- Entrada Fichero / Código -->
                    <div id="tab-upload" class="tab-content" style="display: none;">
                        <div id="drop-zone" style="border: 2px dashed rgba(232,104,26,.3); border-radius: 1rem; padding: 2rem; text-align: center; background: rgba(0,0,0,.2); cursor: pointer; transition: all 0.3s ease; margin-bottom: 1.5rem;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            <p style="font-weight: 700; margin-bottom: .25rem; font-size: .95rem;">Arrastra aquí tu fichero XML</p>
                            <p style="font-size: .8rem; color: var(--muted);">O haz clic para seleccionar archivo de tu ordenador</p>
                            <input type="file" id="file-input" accept=".xml,.txt" style="display: none;">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="raw-xml">O pega el código plano del Sitemap XML</label>
                            <textarea class="form-textarea" id="raw-xml" rows="5" placeholder="&lt;?xml version='1.0' encoding='UTF-8'?&gt;&#10;&lt;urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'&gt;&#10;  &lt;url&gt;&#10;    &lt;loc&gt;https://tuweb.com/pagina&lt;/loc&gt;&#10;  &lt;/url&gt;&#10;&lt;/urlset&gt;" style="font-family: monospace; font-size: .82rem;"></textarea>
                        </div>
                        <button class="btn btn--primary" id="btn-parse-local" style="width: 100%; justify-content: center; padding: 1.1rem; margin-top: 1rem;">
                            Procesar XML pegado
                        </button>
                    </div>

                </div>

            </div>
        </div>

        <!-- Pantalla de carga animada -->
        <div id="loading-overlay" class="card card--dark" style="display: none; padding: 3rem; text-align: center; border-radius: 1.5rem; margin-bottom: 2rem;">
            <div class="loader-spinner" style="width: 50px; height: 50px; border-width: 5px; border-top-color: var(--orange); margin: 0 auto 1.5rem;"></div>
            <h3 style="font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;" id="loading-title">Extrayendo información...</h3>
            <p style="color: var(--muted); font-size: .9rem;" id="loading-status">Conectando con el servidor remoto bajo agente Googlebot...</p>
        </div>

        <!-- Panel de control de Resultados (Inicialmente oculto) -->
        <div id="results-panel" style="display: none;">
            
            <!-- Dashboard de Estadísticas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
                
                <!-- Stat 1: Total URLs -->
                <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid var(--orange);">
                    <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em;">Total URLs Únicas</span>
                    <span id="stat-total" style="display: block; font-size: 2.25rem; font-weight: 900; color: #fff; margin-top: .25rem;">0</span>
                    <span id="stat-processed-sitemaps" style="display: block; font-size: .78rem; color: #2ecc71; margin-top: .25rem;">1 sitemap procesado</span>
                </div>

                <!-- Stat 2: Auditoría de Longitud -->
                <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid #e74c3c;">
                    <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em;">URLs Críticas (>75 chars)</span>
                    <span id="stat-long-urls" style="display: block; font-size: 2.25rem; font-weight: 900; color: #e74c3c; margin-top: .25rem;">0</span>
                    <span style="display: block; font-size: .78rem; color: var(--muted); margin-top: .25rem;">Riesgo de truncado en Google SERPs</span>
                </div>

                <!-- Stat 3: Frescura de Contenidos -->
                <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid #2ecc71;">
                    <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em;">Actualizado en 30 días</span>
                    <span id="stat-fresh" style="display: block; font-size: 2.25rem; font-weight: 900; color: #2ecc71; margin-top: .25rem;">0</span>
                    <span id="stat-fresh-pct" style="display: block; font-size: .78rem; color: var(--muted); margin-top: .25rem;">0% del total de contenidos</span>
                </div>

                <!-- Stat 4: Longitud Media -->
                <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid #3498db;">
                    <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em;">Longitud Media URL</span>
                    <span id="stat-avg-len" style="display: block; font-size: 2.25rem; font-weight: 900; color: #3498db; margin-top: .25rem;">0</span>
                    <span style="display: block; font-size: .78rem; color: var(--muted); margin-top: .25rem;">Caracteres de media</span>
                </div>

            </div>

            <!-- Panel de control de datos -->
            <div class="card card--dark" style="padding: 2rem; border-radius: 1.5rem; margin-bottom: 2rem;">
                <h3 style="color: #fff; margin-bottom: 1.25rem; font-size: 1.2rem; font-weight: 800;">2. Filtrar, Inspeccionar y Exportar</h3>
                
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                    <!-- Buscador dinámico -->
                    <div style="flex-grow: 1; min-width: 260px;">
                        <input type="text" id="search-input" class="form-input" placeholder="Buscar por palabra clave o extensión de URL (ej: /blog, .pdf, android)..." style="width: 100%; padding: .85rem 1.2rem;">
                    </div>
                    <!-- Selector de tipo de URLs -->
                    <div style="width: 200px; flex-shrink: 0;">
                        <select id="filter-type" class="form-select" style="padding: .85rem 1.2rem; height: 100%;">
                            <option value="all">Todos los formatos</option>
                            <option value="html">Páginas Web (HTML)</option>
                            <option value="pdf">Ficheros PDF (.pdf)</option>
                            <option value="images">Con imágenes (>0)</option>
                            <option value="critical">Críticas (>75 chars)</option>
                        </select>
                    </div>
                </div>

                <!-- Botones de Acción / Exportación -->
                <div style="display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 2rem;">
                    <button class="btn btn--primary" id="btn-copy-all">Copiar todas las URLs</button>
                    <button class="btn btn--primary" id="btn-download-csv" style="background-color: #2ecc71 !important; border-color: #2ecc71 !important; color: #ffffff !important;">Exportar a CSV</button>
                    <button class="btn btn--ghost" id="btn-download-json">Exportar a JSON</button>
                    <button class="btn btn--ghost" onclick="resetTool()" style="margin-left: auto; color: #e74c3c !important; border-color: rgba(231,76,60,0.3) !important;">Limpiar Herramienta</button>
                </div>

                <!-- Tabla de URLs Auditada -->
                <div style="overflow-x: auto;">
                    <table class="seo-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: .85rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.08); color: #fff;">
                                <th style="padding: 1rem; font-weight: 800; cursor: pointer;" onclick="sortTable('url')">URL <span id="sort-icon-url"></span></th>
                                <th style="padding: 1rem; font-weight: 800; cursor: pointer; width: 150px;" onclick="sortTable('lastmod')">Última Modif. <span id="sort-icon-lastmod"></span></th>
                                <th style="padding: 1rem; font-weight: 800; text-align: center; width: 90px; cursor: pointer;" onclick="sortTable('images')">Imágenes <span id="sort-icon-images"></span></th>
                                <th style="padding: 1rem; font-weight: 800; width: 160px; cursor: pointer;" onclick="sortTable('sitemap')">Sitemap de Origen <span id="sort-icon-sitemap"></span></th>
                            </tr>
                        </thead>
                        <tbody id="url-table-body">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- Paginación de Seguridad -->
                <div id="table-pagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; font-size: .8rem; color: var(--muted);">
                    <span id="pagination-info">Mostrando 0 de 0 URLs</span>
                    <button class="btn btn--ghost" id="btn-load-more" style="padding: .5rem 1rem; font-size: .75rem; display: none;">Cargar más resultados</button>
                </div>

            </div>

        </div>

      </div>

      <!-- Sección de Criterio Técnico SEO "Non-Commodity" -->
      <div class="criterio-section" style="margin-top: 5rem;">
        <span class="section-label">Fundamentos del SEO de Trinchera</span>
        <h2>Errores críticos en Sitemaps XML que destrozan tu Crawl Budget</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>URLs sin estatus 200 OK</h3>
            <p>El error básico número uno es inyectar en el sitemap URLs que devuelven redirecciones (301, 302) o errores (404, 500). El sitemap es una carta de recomendación de indexación directa a Googlebot.</p>
            <p>Si fuerzas al bot a rastrear URLs que redirigen, estás quemando su límite de rastreo diario (Crawl Budget) en procesar redirecciones inútiles en lugar de indexar nuevos contenidos de calidad.</p>
          </div>

          <div class="criterio-card">
            <h3>Directivas 'noindex' contradictorias</h3>
            <p>Si una URL tiene la cabecera o metaetiqueta `noindex`, **nunca** debe incluirse en el sitemap XML. Esto confunde al robot de indexación, provocando avisos de indexación contradictorios en Google Search Console y retrasando la desindexación de las páginas que realmente deseas ocultar.</p>
          </div>

          <div class="criterio-card">
            <h3>Sitemaps inflados y desactualizados</h3>
            <p>Un sitemap que pesa más de 50MB o que supera las 50,000 URLs viola las especificaciones de Schema.org. Utiliza Sitemap Indexes para segmentar tus contenidos lógicamente (páginas, posts, categorías) y mantén la etiqueta `<lastmod>` actualizada de forma sincrónica con tu base de datos para notificar solo los cambios reales.</p>
          </div>
        </div>
      </div>

      <!-- Valoraciones -->
      <?php render_rating_widget('extractor-sitemap'); ?>

    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Tienes problemas de rastreo o indexación?',
    'subtitle'  => 'Audito tu archivo robots.txt, la lógica del sitemap, códigos de estado HTTP y arquitecturas web complejas para potenciar tu posicionamiento orgánico real.',
    'btn_label' => 'Auditar mi rastreo técnico',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<style>
/* Estilos premium específicos para el Extractor */
.seo-table th {
    border-bottom: 2px solid rgba(255,255,255,0.08);
    color: #fff;
    padding: 1rem;
    text-transform: uppercase;
    font-size: .75rem;
    letter-spacing: .05em;
}
.seo-table td {
    padding: 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    vertical-align: middle;
}
.seo-table tbody tr:hover {
    background: rgba(255,255,255,0.01);
}
.seo-table tr.critical-row {
    background: rgba(231,76,60,0.03);
}
.seo-table tr.critical-row:hover {
    background: rgba(231,76,60,0.05);
}
.warning-badge {
    background: rgba(231,76,60,0.15);
    color: #e74c3c;
    border: 1px solid rgba(231,76,60,0.2);
    font-size: .68rem;
    font-weight: 800;
    padding: .15rem .4rem;
    border-radius: .35rem;
    text-transform: uppercase;
    margin-left: .5rem;
    display: inline-block;
}
</style>

<script>
// Datos en memoria
let extractedUrls = [];
let filteredUrls = [];
let processedSitemaps = [];
let currentSortColumn = 'url';
let currentSortDirection = 'asc';

// Lógica de paginación progresiva para no colgar navegadores
let currentPage = 1;
const pageSize = 150;

document.addEventListener('DOMContentLoaded', function() {
    
    // Asignar listeners
    document.getElementById('btn-parse-sitemap').addEventListener('click', startSitemapFetch);
    document.getElementById('btn-parse-local').addEventListener('click', processLocalText);
    document.getElementById('search-input').addEventListener('input', applyFilters);
    document.getElementById('filter-type').addEventListener('change', applyFilters);
    document.getElementById('btn-copy-all').addEventListener('click', copyAllToClipboard);
    document.getElementById('btn-download-csv').addEventListener('click', exportToCSV);
    document.getElementById('btn-download-json').addEventListener('click', exportToJSON);
    document.getElementById('btn-load-more').addEventListener('click', loadMoreResults);
    
    // Drag and Drop
    setupDragAndDrop();
});

// Pestañas
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabId).style.display = 'block';
    
    // Activar botón respectivo
    const clickedBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.getAttribute('onclick').includes(tabId));
    if (clickedBtn) clickedBtn.classList.add('active');
}

// Drag & Drop Setup
function setupDragAndDrop() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    
    dropZone.addEventListener('click', () => fileInput.click());
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--orange)';
        dropZone.style.background = 'rgba(232,104,26,0.05)';
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.style.borderColor = 'rgba(232,104,26,0.3)';
        dropZone.style.background = 'rgba(0,0,0,0.2)';
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'rgba(232,104,26,0.3)';
        dropZone.style.background = 'rgba(0,0,0,0.2)';
        
        if (e.dataTransfer.files.length > 0) {
            handleFile(e.dataTransfer.files[0]);
        }
    });
    
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            handleFile(fileInput.files[0]);
        }
    });
}

function handleFile(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('raw-xml').value = e.target.result;
        switchTab('tab-upload');
    };
    reader.readAsText(file);
}

// Extracción Remota (AJAX)
function startSitemapFetch() {
    const url = document.getElementById('sm-url').value.trim();
    if (!url) {
        alert('Por favor introduce una URL válida.');
        return;
    }
    
    // Mostrar overlay de carga
    document.getElementById('tab-url').querySelector('#btn-parse-sitemap').disabled = true;
    document.getElementById('btn-text').style.display = 'none';
    document.getElementById('btn-loader').style.display = 'inline-block';
    
    document.getElementById('loading-overlay').style.display = 'block';
    document.getElementById('results-panel').style.display = 'none';
    
    const formData = new FormData();
    formData.append('action', 'parse_sitemap');
    formData.append('url', url);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            extractedUrls = data.urls;
            processedSitemaps = data.sitemaps_processed;
            renderDashboard();
        } else {
            alert(data.message || 'Error al procesar el sitemap.');
            document.getElementById('loading-overlay').style.display = 'none';
        }
    })
    .catch(err => {
        alert('Error de red al conectar con el servidor.');
        document.getElementById('loading-overlay').style.display = 'none';
    })
    .finally(() => {
        document.getElementById('tab-url').querySelector('#btn-parse-sitemap').disabled = false;
        document.getElementById('btn-text').style.display = 'inline-block';
        document.getElementById('btn-loader').style.display = 'none';
    });
}

// Extracción Local
function processLocalText() {
    const text = document.getElementById('raw-xml').value.trim();
    if (!text) {
        alert('Carga un fichero o pega código XML primero.');
        return;
    }
    
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(text, "text/xml");
        const parserError = xmlDoc.getElementsByTagName("parsererror");
        
        if (parserError.length > 0) {
            throw new Error("El texto pegado no es un XML válido. Verifica etiquetas de apertura y cierre.");
        }
        
        const rootName = xmlDoc.documentElement.nodeName;
        const urls = [];
        
        if (rootName === "sitemapindex") {
            alert("Advertencia técnica: Se ha detectado un Índice de Sitemaps. Debido a las políticas de seguridad CORS del navegador local, no se pueden rastrear los sub-sitemaps automáticamente en local. Procesa la URL de forma remota o pega el XML del sitemap individual.");
        }
        
        const urlElements = xmlDoc.getElementsByTagName("url");
        for (let i = 0; i < urlElements.length; i++) {
            const el = urlElements[i];
            const loc = el.getElementsByTagName("loc")[0]?.textContent?.trim() || "";
            if (!loc) continue;
            
            const lastmod = el.getElementsByTagName("lastmod")[0]?.textContent?.trim() || "";
            const changefreq = el.getElementsByTagName("changefreq")[0]?.textContent?.trim() || "";
            const priority = el.getElementsByTagName("priority")[0]?.textContent?.trim() || "";
            
            let imageCount = 0;
            // Contar imágenes
            for (let j = 0; j < el.childNodes.length; j++) {
                const child = el.childNodes[j];
                if (child.nodeName.includes("image")) {
                    imageCount++;
                }
            }
            
            urls.push({
                url: loc,
                lastmod: lastmod,
                changefreq: changefreq,
                priority: priority,
                images: imageCount,
                sitemap: 'Pegado local'
            });
        }
        
        if (urls.length === 0) {
            alert('No se encontraron nodos <url> válidos en tu XML.');
            return;
        }
        
        extractedUrls = urls;
        processedSitemaps = ['Subida manual'];
        renderDashboard();
        
    } catch(e) {
        alert('Error al analizar XML: ' + e.message);
    }
}

// Renderizar Panel de Control y Auditorías
function renderDashboard() {
    document.getElementById('loading-overlay').style.display = 'none';
    document.getElementById('results-panel').style.display = 'block';
    
    // Reset de filtros y paginación
    document.getElementById('search-input').value = '';
    document.getElementById('filter-type').value = 'all';
    currentPage = 1;
    
    applyFilters();
}

// Filtrar URLs
function applyFilters() {
    const searchVal = document.getElementById('search-input').value.toLowerCase().trim();
    const typeVal = document.getElementById('filter-type').value;
    
    filteredUrls = extractedUrls.filter(item => {
        // Filtro buscador
        const matchesSearch = item.url.toLowerCase().includes(searchVal) || 
                              item.sitemap.toLowerCase().includes(searchVal);
        
        // Filtro tipo
        let matchesType = true;
        if (typeVal === 'html') {
            matchesType = !item.url.endsWith('.pdf') && !item.url.endsWith('.jpg') && !item.url.endsWith('.png');
        } else if (typeVal === 'pdf') {
            matchesType = item.url.endsWith('.pdf');
        } else if (typeVal === 'images') {
            matchesType = item.images > 0;
        } else if (typeVal === 'critical') {
            matchesType = item.url.length > 75;
        }
        
        return matchesSearch && matchesType;
    });
    
    currentPage = 1;
    updateStats();
    sortAndRenderTable();
}

// Calcular Estadísticas
function updateStats() {
    // Total
    document.getElementById('stat-total').textContent = extractedUrls.length.toLocaleString();
    
    // Sitemaps procesados
    const sitemapsCount = processedSitemaps.length;
    document.getElementById('stat-processed-sitemaps').textContent = `${sitemapsCount} sitemap${sitemapsCount > 1 ? 's' : ''} procesado${sitemapsCount > 1 ? 's' : ''}`;
    
    // URLs Críticas (> 75 chars)
    const longUrls = extractedUrls.filter(item => item.url.length > 75).length;
    document.getElementById('stat-long-urls').textContent = longUrls.toLocaleString();
    
    // Frescura (últimos 30 días)
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    
    let freshCount = 0;
    extractedUrls.forEach(item => {
        if (item.lastmod) {
            const date = new Date(item.lastmod);
            if (!isNaN(date.getTime()) && date >= thirtyDaysAgo) {
                freshCount++;
            }
        }
    });
    
    const freshPct = extractedUrls.length > 0 ? Math.round((freshCount / extractedUrls.length) * 100) : 0;
    document.getElementById('stat-fresh').textContent = freshCount.toLocaleString();
    document.getElementById('stat-fresh-pct').textContent = `${freshPct}% de frescura técnica`;
    
    // Longitud Media
    let totalLen = 0;
    extractedUrls.forEach(item => totalLen += item.url.length);
    const avgLen = extractedUrls.length > 0 ? Math.round(totalLen / extractedUrls.length) : 0;
    document.getElementById('stat-avg-len').textContent = avgLen;
}

// Renderizar Tabla
function sortAndRenderTable() {
    // Aplicar ordenación en memoria
    filteredUrls.sort((a, b) => {
        let valA = a[currentSortColumn];
        let valB = b[currentSortColumn];
        
        // Conversión a int si es conteo de imágenes
        if (currentSortColumn === 'images') {
            valA = parseInt(valA) || 0;
            valB = parseInt(valB) || 0;
        }
        
        if (valA < valB) return currentSortDirection === 'asc' ? -1 : 1;
        if (valA > valB) return currentSortDirection === 'asc' ? 1 : -1;
        return 0;
    });
    
    const tbody = document.getElementById('url-table-body');
    tbody.innerHTML = '';
    
    const totalRecords = filteredUrls.length;
    const recordsToRender = Math.min(currentPage * pageSize, totalRecords);
    
    for (let i = 0; i < recordsToRender; i++) {
        const item = filteredUrls[i];
        const isCritical = item.url.length > 75;
        
        const tr = document.createElement('tr');
        if (isCritical) {
            tr.className = 'critical-row';
        }
        
        // Formatear fecha
        let displayDate = 'Sin fecha';
        if (item.lastmod) {
            const dateObj = new Date(item.lastmod);
            if (!isNaN(dateObj.getTime())) {
                displayDate = dateObj.toLocaleDateString('es-ES', {
                    year: 'numeric', month: 'short', day: 'numeric'
                });
            } else {
                displayDate = item.lastmod; // Fallback string directo
            }
        }
        
        tr.innerHTML = `
            <td style="padding: 1rem; font-family: monospace; word-break: break-all;">
                <a href="${item.url}" target="_blank" rel="noopener noreferrer" style="color: #3498db; text-decoration: none;">${item.url}</a>
                ${isCritical ? `<span class="warning-badge" title="La URL supera los 75 caracteres recomendados para visualización completa en pantallas móviles de Google.">Crítica (${item.url.length} ch)</span>` : ''}
            </td>
            <td style="padding: 1rem; color: #cbd5e1;">${displayDate}</td>
            <td style="padding: 1rem; text-align: center; font-weight: 800; color: ${item.images > 0 ? 'var(--orange)' : 'var(--muted)'};">
                ${item.images > 0 ? `<i class="fa-solid fa-image"></i> ${item.images}` : '-'}
            </td>
            <td style="padding: 1rem; color: var(--muted); font-size: .8rem;">${item.sitemap}</td>
        `;
        tbody.appendChild(tr);
    }
    
    // Actualizar paginador
    document.getElementById('pagination-info').textContent = `Mostrando ${recordsToRender.toLocaleString()} de ${totalRecords.toLocaleString()} URLs filtradas`;
    
    const loadMoreBtn = document.getElementById('btn-load-more');
    if (recordsToRender < totalRecords) {
        loadMoreBtn.style.display = 'inline-block';
    } else {
        loadMoreBtn.style.display = 'none';
    }
}

// Cargar más
function loadMoreResults() {
    currentPage++;
    sortAndRenderTable();
}

// Ordenar Tabla
function sortTable(column) {
    if (currentSortColumn === column) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortColumn = column;
        currentSortDirection = 'asc';
    }
    
    // Limpiar clases de cabecera y actualizar iconos de ordenación
    const cols = ['url', 'lastmod', 'images', 'sitemap'];
    cols.forEach(c => {
        const iconSpan = document.getElementById(`sort-icon-${c}`);
        if (iconSpan) iconSpan.innerHTML = '';
    });
    
    const activeIcon = document.getElementById(`sort-icon-${column}`);
    if (activeIcon) {
        activeIcon.innerHTML = currentSortDirection === 'asc' ? ' &uarr;' : ' &darr;';
    }
    
    sortAndRenderTable();
}

// Copiar todo a portapapeles
function copyAllToClipboard() {
    if (filteredUrls.length === 0) return;
    
    const text = filteredUrls.map(item => item.url).join('\n');
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('btn-copy-all');
        const orig = btn.textContent;
        btn.textContent = '¡Listado Copiado!';
        btn.style.background = '#2ecc71';
        setTimeout(() => {
            btn.textContent = orig;
            btn.style.background = '';
        }, 2000);
    }).catch(() => {
        alert('Error al copiar al portapapeles.');
    });
}

// Exportar a CSV
function exportToCSV() {
    if (filteredUrls.length === 0) return;
    
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "URL,Last Modification,Images Count,Origin Sitemap\n";
    
    filteredUrls.forEach(item => {
        csvContent += `"${item.url}","${item.lastmod}",${item.images},"${item.sitemap}"\n`;
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `sitemap_audit_export_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Exportar a JSON
function exportToJSON() {
    if (filteredUrls.length === 0) return;
    
    const jsonStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(filteredUrls, null, 2));
    const link = document.createElement("a");
    link.setAttribute("href", jsonStr);
    link.setAttribute("download", `sitemap_audit_export_${new Date().toISOString().slice(0,10)}.json`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Reset
function resetTool() {
    extractedUrls = [];
    filteredUrls = [];
    processedSitemaps = [];
    document.getElementById('results-panel').style.display = 'none';
    document.getElementById('loading-overlay').style.display = 'none';
    document.getElementById('sm-url').value = 'https://www.victor-alonso.es/sitemap.xml';
    document.getElementById('raw-xml').value = '';
    switchTab('tab-url');
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
