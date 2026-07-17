<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';

@set_time_limit(120);

// Función auxiliar: descargar contenido simulando Googlebot y admitiendo gzip
function fetch_sitemap_content($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, ''); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $googlebot = 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
    curl_setopt($ch, CURLOPT_USERAGENT, $googlebot);
    
    $content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || !$content) {
        return false;
    }
    
    if (substr($content, 0, 2) === "\x1f\x8b") {
        $decoded = @gzdecode($content);
        if ($decoded) {
            $content = $decoded;
        }
    }
    
    return $content;
}

function parse_sitemap_xml($xml_content, &$all_urls, &$processed_sitemaps, $depth = 0, $parent_url = '') {
    if ($depth > 3) return;
    
    $disableEntities = false;
    if (PHP_VERSION_ID < 80000) {
        $disableEntities = libxml_disable_entity_loader(true);
    }
    $internalErrors = libxml_use_internal_errors(true);
    
    $xml = @simplexml_load_string($xml_content, 'SimpleXMLElement', LIBXML_NOCDATA);
    
    if (!$xml) {
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader($disableEntities);
        }
        return;
    }
    
    $root_name = $xml->getName();
    
    if ($root_name === 'sitemapindex') {
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
        $sitemap_name = basename($parent_url ? $parent_url : 'sitemap.xml');
        
        foreach ($xml->url as $url_node) {
            $loc = trim((string)$url_node->loc);
            if (!$loc) continue;
            
            $lastmod = trim((string)$url_node->lastmod);
            
            $all_urls[] = [
                'url' => $loc,
                'lastmod' => $lastmod ? $lastmod : '',
                'sitemap' => $sitemap_name
            ];
        }
    }
    
    libxml_clear_errors();
    libxml_use_internal_errors($internalErrors);
    if (PHP_VERSION_ID < 80000) {
        libxml_disable_entity_loader($disableEntities);
    }
}

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
                'message' => 'No se pudo descargar el sitemap. Verifica que la URL esté online y permita peticiones externas.'
            ]);
            exit;
        }
        
        $all_urls = [];
        $processed_sitemaps = [$sitemap_url];
        
        parse_sitemap_xml($initial_content, $all_urls, $processed_sitemaps, 0, $sitemap_url);
        
        if (empty($all_urls)) {
            echo json_encode([
                'success' => false,
                'message' => 'El XML se descargó correctamente pero no se encontraron etiquetas <url> válidas.'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'urls' => $all_urls
        ]);
        exit;
    }
}

// Configuración de Página
$page = page_config([
    'title'        => 'Analizador de páginas huérfanas',
    'description'  => 'Detecta páginas huérfanas cotejando el Sitemap XML de tu web con el listado de URLs de tu crawler (Screaming Frog, Sitebulb).',
    'canonical'    => '/herramientas/analizador-paginas-huerfanas/',
    'body_class'   => 'page-analizador-huerfanas',
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'analizador-paginas-huerfanas',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Analizador de Páginas Huérfanas', 'url' => ''],
    ],
    'faq_items' => [
        [
            'q' => '¿Qué es una página huérfana?',
            'a' => 'Una página huérfana es aquella que existe en tu servidor (y a menudo en tu Sitemap) pero no recibe ningún enlace interno desde el resto de la web. Esto hace que sea inaccesible tanto para los usuarios como para los robots de rastreo a través de la navegación normal.'
        ],
        [
            'q' => '¿Por qué las páginas huérfanas son perjudiciales para el SEO?',
            'a' => 'Si una página no tiene enlaces internos, Google asume que no es importante y difícilmente la posicionará. Además, generan rastreos ineficientes sin aportar valor a tu arquitectura web.'
        ],
        [
            'q' => '¿Cómo funciona esta herramienta?',
            'a' => 'Coteja dos fuentes de datos: las URLs indexables declaradas en tu Sitemap XML contra las URLs que tu crawler (Screaming Frog, etc.) ha sido capaz de encontrar rastreando los enlaces internos. Las que están en el Sitemap pero no en el rastreo, son huérfanas.'
        ],
        [
            'q' => '¿Qué formato de CSV necesita la herramienta?',
            'a' => 'Cualquier CSV o archivo de texto donde las URLs se encuentren en la primera columna. Es compatible directamente con las exportaciones de Screaming Frog (Internal HTML).'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="huerfanas-h1">
    <div class="container">
      <span class="hero-eyebrow">Arquitectura y Enlazado Interno</span>
      <h1 id="huerfanas-h1">Analizador de <span>Páginas Huérfanas</span></h1>
      <p class="page-hero-desc">Cruza los datos de tu Sitemap XML con el rastreo de tu crawler para detectar URLs indexables que no reciben enlaces internos.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      
      <div class="tool-intro">
        <h2>Encuentra el contenido aislado de tu web</h2>
        <p>Introduce la URL de tu Sitemap XML y sube el archivo CSV de las URLs encontradas por tu crawler (como Screaming Frog). La herramienta cotejará ambas listas y te mostrará aquellas URLs que están en el Sitemap pero no son rastreables internamente.</p>
      </div>

      <div class="extractor-grid">
        
        <div class="tool-card tool-card--accent">
            <form id="tool-form" class="tab-content active" onsubmit="event.preventDefault(); startAnalysis();">
                <div class="tool-layout-grid" style="grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem;">
                    
                    <div>
                        <h3 style="color: var(--orange); margin-bottom: 1.25rem; font-size: 1.1rem; font-weight: 800; text-transform: uppercase;">1. URLs Rastreadas (CSV)</h3>
                        <div id="drop-zone" style="border: 2px dashed rgba(232,104,26,.3); border-radius: 1rem; padding: 2rem; text-align: center; background: rgba(0,0,0,.2); cursor: pointer; transition: all 0.3s ease;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            <p style="font-weight: 700; margin-bottom: .25rem; font-size: .95rem;">Sube el CSV del Crawler</p>
                            <p style="font-size: .8rem; color: var(--muted);" id="file-name-display">Formato: URLs en primera columna</p>
                            <input type="file" id="file-input" accept=".csv,.txt" style="display: none;">
                        </div>
                    </div>

                    <div>
                        <h3 style="color: var(--orange); margin-bottom: 1.25rem; font-size: 1.1rem; font-weight: 800; text-transform: uppercase;">2. URLs Declaradas (Sitemap)</h3>
                        <div class="tool-form__group">
                            <label class="tool-form__label" for="sm-url">URL del Sitemap XML</label>
                            <input type="url" class="tool-form__input" id="sm-url" value="https://www.victor-alonso.es/sitemap.xml" placeholder="https://tuweb.com/sitemap.xml" style="font-family: monospace;" required>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--muted); margin-top: 0.5rem;">Se extraerán recursivamente todas las URLs de este Sitemap o Sitemap Index.</p>
                    </div>

                </div>

                <button type="submit" class="btn btn--primary" id="btn-analyze" style="width: 100%; justify-content: center; padding: 1.1rem;">
                    <span id="btn-text">Cruzar Datos y Analizar Huérfanas</span>
                    <span id="btn-loader" class="loader-spinner" style="display: none;"></span>
                </button>
            </form>
        </div>

        <div id="loading-overlay" class="tool-card tool-card--accent" style="display: none; text-align: center;">
            <div class="loader-spinner" style="width: 50px; height: 50px; border-width: 5px; border-top-color: var(--orange); margin: 0 auto 1.5rem;"></div>
            <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--black); margin-bottom: 0.5rem;" id="loading-title">Analizando y cruzando datos...</h3>
            <p style="color: var(--muted); font-size: .9rem;" id="loading-status">Descargando Sitemap y procesando CSV...</p>
        </div>

        <div id="results-panel" style="display: none;">
            
            <div class="tool-layout-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 2.5rem;">
                
                <div class="tool-card" style="border-left: 4px solid #3498db;">
                    <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em;">URLs en Sitemap</span>
                    <span id="stat-sitemap" style="display: block; font-size: 2.25rem; font-weight: 900; color: #3498db; margin-top: .25rem;">0</span>
                    <span style="display: block; font-size: .78rem; color: var(--muted); margin-top: .25rem;">URLs declaradas</span>
                </div>

                <div class="tool-card" style="border-left: 4px solid #2ecc71;">
                    <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em;">URLs Rastreadas</span>
                    <span id="stat-crawled" style="display: block; font-size: 2.25rem; font-weight: 900; color: #2ecc71; margin-top: .25rem;">0</span>
                    <span style="display: block; font-size: .78rem; color: var(--muted); margin-top: .25rem;">Encontradas en el CSV</span>
                </div>

                <div class="tool-card" style="border-left: 4px solid #e74c3c;">
                    <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em;">Páginas Huérfanas</span>
                    <span id="stat-orphans" style="display: block; font-size: 2.25rem; font-weight: 900; color: #e74c3c; margin-top: .25rem;">0</span>
                    <span style="display: block; font-size: .78rem; color: var(--muted); margin-top: .25rem;">En Sitemap pero NO rastreadas</span>
                </div>

            </div>

            <div class="tool-card">
                <h3 style="color: var(--black); margin-bottom: 1.25rem; font-size: 1.2rem; font-weight: 800;">URLs Huérfanas Detectadas</h3>
                
                <div style="display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 2rem;">
                    <button class="btn btn--primary" id="btn-copy-all">Copiar Huérfanas</button>
                    <button class="btn btn--primary" id="btn-download-csv" style="background-color: #2ecc71 !important; border-color: #2ecc71 !important; color: #ffffff !important;">Exportar a CSV</button>
                    <button class="btn btn--secondary" onclick="location.reload()" style="margin-left: auto; color: #e74c3c !important; border-color: rgba(231,76,60,0.3) !important;">Limpiar Herramienta</button>
                </div>

                <div style="overflow-x: auto;">
                    <table class="seo-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: .85rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border);">
                                <th style="padding: 1rem; font-weight: 800;">URL Huérfana</th>
                                <th style="padding: 1rem; font-weight: 800; width: 150px;">Sitemap Origen</th>
                                <th style="padding: 1rem; font-weight: 800; width: 150px;">Última Modif.</th>
                            </tr>
                        </thead>
                        <tbody id="orphans-table-body">
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

      </div>

      <div class="criterio-section" style="margin-top: 5rem;">
        <span class="section-label">Estrategia SEO para URLs Huérfanas</span>
        <h2>¿Qué hacer con las páginas huérfanas encontradas?</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>1. Si el contenido NO es valioso</h3>
            <p>Si las URLs encontradas corresponden a paginaciones antiguas, etiquetas vacías, o contenidos desfasados, el primer paso es <strong>eliminarlas del Sitemap XML</strong>. Dependiendo del caso, aplica una redirección 301 a una página relevante o devuelve un código 410 (Gone).</p>
          </div>

          <div class="criterio-card">
            <h3>2. Si el contenido SÍ es valioso</h3>
            <p>Si la página debe indexar y posicionar (por ejemplo, un artículo pilar o un producto importante), entonces tiene un grave problema de arquitectura. Debes <strong>crear enlaces internos hacia ella</strong> desde el menú, desde la portada o desde otros artículos relacionados (clusters).</p>
          </div>

          <div class="criterio-card">
            <h3>3. Error común: Sitemaps inflados</h3>
            <p>Muchos CMS como WordPress incluyen por defecto páginas de autor, fechas y archivos adjuntos en el Sitemap sin enlazar a ellos desde el frontend. Esto genera ineficiencias de rastreo. Configura tu plugin SEO para excluir estos tipos de contenido.</p>
          </div>
        </div>
      </div>

      <?php require dirname(__DIR__) . '/includes/related-tools.php'; ?>
      <?php render_rating_widget('analizador-paginas-huerfanas'); ?>

    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <?php
  $cta = [
    'title'     => '¿Problemas de enlazado e indexación?',
    'subtitle'  => 'Como consultor SEO, audito en profundidad la arquitectura de tu sitio web, optimizo tu enlazado interno y maximizo el rendimiento de rastreo de Googlebot.',
    'btn_label' => 'Mejorar mi Arquitectura SEO',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<style>
.seo-table th {
    border-bottom: 2px solid var(--border);
    color: var(--black);
    padding: 1rem;
    text-transform: uppercase;
    font-size: .75rem;
    letter-spacing: .05em;
}
.seo-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    color: var(--text);
}
.seo-table tbody tr:hover {
    background: var(--bg-hover);
}
.seo-table tr.critical-row {
    background: rgba(231,76,60,0.03);
}
.seo-table tr.critical-row:hover {
    background: rgba(231,76,60,0.05);
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js" integrity="sha512-dfvX84090q0b3e58eTqB9xV6X3D0U1yN76V/Nnp+L6B47x9Yl4t153Vq5e3/w5vFjD6vVvYf4bB/eI/3G1/xXg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
let csvUrls = new Set();
let sitemapUrls = [];
let orphanUrls = [];

document.addEventListener('DOMContentLoaded', function() {
    setupDragAndDrop();
    
    document.getElementById('btn-copy-all').addEventListener('click', copyAllToClipboard);
    document.getElementById('btn-download-csv').addEventListener('click', exportToCSV);
});

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
    document.getElementById('file-name-display').textContent = 'Analizando ' + file.name + '...';
    document.getElementById('file-name-display').style.color = 'var(--orange)';
    document.getElementById('file-name-display').style.fontWeight = 'bold';
    
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const text = e.target.result;
        csvUrls.clear();
        
        // Expresión regular para capturar cualquier URL válida que empiece por http/https
        // Se detiene al encontrar comillas, comas, punto y coma, tabulaciones o espacios
        const regex = /https?:\/\/[^"';,\t\n\r\s]+/gi;
        let match;
        
        while ((match = regex.exec(text)) !== null) {
            let url = match[0].trim();
            // Limpiar trailing slash
            csvUrls.add(url.replace(/\/$/, ''));
        }
        
        if (csvUrls.size > 0) {
            document.getElementById('file-name-display').textContent = file.name + ' (' + csvUrls.size.toLocaleString() + ' URLs detectadas)';
            document.getElementById('file-name-display').style.color = '#2ecc71';
        } else {
            document.getElementById('file-name-display').textContent = file.name + ' (No se detectaron URLs)';
            document.getElementById('file-name-display').style.color = '#e74c3c';
            alert("No se detectó ninguna URL en el archivo. Esto puede ocurrir si el archivo es un Excel binario (.xlsx) renombrado, o si Screaming Frog lo exportó sin el protocolo 'http'. Por favor, asegúrate de subir un CSV de texto plano.");
        }
    };
    
    reader.onerror = function() {
        alert("El navegador no pudo leer el archivo local.");
    };
    
    reader.readAsText(file);
}

function startAnalysis() {
    if (csvUrls.size === 0) {
        alert("Por favor, sube un archivo CSV válido con URLs en la primera columna.");
        return;
    }
    
    const sitemapUrl = document.getElementById('sm-url').value.trim();
    if (!sitemapUrl) {
        alert("Introduce la URL del Sitemap XML.");
        return;
    }
    
    document.getElementById('btn-analyze').disabled = true;
    document.getElementById('btn-text').style.display = 'none';
    document.getElementById('btn-loader').style.display = 'inline-block';
    
    document.getElementById('loading-overlay').style.display = 'block';
    document.getElementById('results-panel').style.display = 'none';
    
    const formData = new FormData();
    formData.append('action', 'parse_sitemap');
    formData.append('url', sitemapUrl);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            sitemapUrls = data.urls;
            processOrphans();
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
        document.getElementById('btn-analyze').disabled = false;
        document.getElementById('btn-text').style.display = 'inline-block';
        document.getElementById('btn-loader').style.display = 'none';
    });
}

function processOrphans() {
    orphanUrls = [];
    
    sitemapUrls.forEach(item => {
        const checkUrl = item.url.trim().replace(/\/$/, '');
        if (!csvUrls.has(checkUrl)) {
            orphanUrls.push(item);
        }
    });
    
    renderResults();
}

function renderResults() {
    document.getElementById('loading-overlay').style.display = 'none';
    document.getElementById('results-panel').style.display = 'block';
    
    document.getElementById('stat-sitemap').textContent = sitemapUrls.length.toLocaleString();
    document.getElementById('stat-crawled').textContent = csvUrls.size.toLocaleString();
    document.getElementById('stat-orphans').textContent = orphanUrls.length.toLocaleString();
    
    const tbody = document.getElementById('orphans-table-body');
    tbody.innerHTML = '';
    
    if (orphanUrls.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 2rem; color: #2ecc71; font-weight: bold;">¡Enhorabuena! No se encontraron páginas huérfanas. Todas las URLs del sitemap están enlazadas internamente.</td></tr>';
        return;
    }
    
    orphanUrls.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = 'critical-row';
        
        const tdUrl = document.createElement('td');
        const a = document.createElement('a');
        a.href = item.url;
        a.target = "_blank";
        a.textContent = item.url;
        a.style.color = "var(--orange)";
        a.style.textDecoration = "none";
        a.style.fontWeight = "600";
        tdUrl.appendChild(a);
        
        const tdSitemap = document.createElement('td');
        tdSitemap.textContent = item.sitemap;
        
        const tdDate = document.createElement('td');
        tdDate.textContent = item.lastmod || 'N/A';
        
        tr.appendChild(tdUrl);
        tr.appendChild(tdSitemap);
        tr.appendChild(tdDate);
        tbody.appendChild(tr);
    });
}

function copyAllToClipboard() {
    if (orphanUrls.length === 0) return;
    const text = orphanUrls.map(u => u.url).join('\n');
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('btn-copy-all');
        const originalText = btn.textContent;
        btn.textContent = '¡Copiado!';
        setTimeout(() => btn.textContent = originalText, 2000);
    });
}

function exportToCSV() {
    if (orphanUrls.length === 0) return;
    
    let csv = 'URL,Sitemap,LastMod\n';
    orphanUrls.forEach(row => {
        csv += `"${row.url}","${row.sitemap}","${row.lastmod}"\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "paginas_huerfanas.csv");
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
