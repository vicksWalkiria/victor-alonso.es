<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';

@set_time_limit(120);

// Descargar y limpiar el contenido semántico principal de una URL con validación de SSL y códigos de estado
function fetch_clean_semantic_text($url, &$error_msg = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    // Simular Googlebot Móvil exacto
    $googlebot = 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
    curl_setopt($ch, CURLOPT_USERAGENT, $googlebot);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    curl_close($ch);
    
    if ($curl_errno !== 0) {
        if ($curl_errno === 60 || $curl_errno === 51) {
            $error_msg = 'Error de seguridad SSL: El certificado SSL de esta URL está vencido, no es válido o está mal configurado. Esto penaliza severamente el posicionamiento orgánico en motores de búsqueda.';
        } else {
            $error_msg = 'Error de conexión a la URL (' . $curl_errno . '): ' . $curl_error;
        }
        return false;
    }
    
    if ($http_code !== 200) {
        $error_msg = 'Error HTTP ' . $http_code . ' en servidor de destino. Comprueba que responde correctamente con un código HTTP 200.';
        return false;
    }
    
    if (!$html) {
        $error_msg = 'La página de destino está vacía o el contenido HTML es ilegible.';
        return false;
    }
    
    // Decodificación gzip de seguridad redundante
    if (substr($html, 0, 2) === "\x1f\x8b") {
        $decoded = @gzdecode($html);
        if ($decoded) {
            $html = $decoded;
        }
    }
    
    // Desactivar temporalmente reporte de errores en carga de DOM
    $internalErrors = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($internalErrors);
    
    $xpath = new DOMXPath($dom);
    
    // Eliminar elementos ruidosos que falsean el análisis semántico
    $noise_tags = ['script', 'style', 'header', 'footer', 'nav', 'aside', 'noscript', 'iframe', 'svg'];
    foreach ($noise_tags as $tag) {
        $nodes = $xpath->query('//' . $tag);
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }
    
    // Intentar buscar contenedores de contenido principal
    $content_containers = [
        '//main',
        '//article',
        '//div[@id="content"]',
        '//div[contains(@class, "content")]',
        '//div[contains(@class, "post")]',
        '//body'
    ];
    
    $text_content = '';
    foreach ($content_containers as $query) {
        $container = $xpath->query($query)->item(0);
        if ($container) {
            $text_content = $container->textContent;
            break;
        }
    }
    
    if (!$text_content) {
        $text_content = $dom->textContent;
    }
    
    // Limpieza de espacios en blanco
    $text_content = preg_replace('/\s+/', ' ', $text_content);
    $text_content = trim($text_content);
    
    // Cortar textos excesivamente largos para no desbordar memoria del navegador
    if (mb_strlen($text_content) > 25000) {
        $text_content = mb_substr($text_content, 0, 25000) . '...';
    }
    
    return $text_content;
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
    
    if ($_POST['action'] === 'extract_entities') {
        header('Content-Type: application/json');
        $url1 = trim($_POST['url1'] ?? '');
        $url2 = trim($_POST['url2'] ?? '');
        
        if (!$url1 || filter_var($url1, FILTER_VALIDATE_URL) === false) {
            echo json_encode(['success' => false, 'message' => 'La URL principal proporcionada no es válida.']);
            exit;
        }
        
        $error_msg1 = '';
        $text1 = fetch_clean_semantic_text($url1, $error_msg1);
        if (!$text1) {
            echo json_encode(['success' => false, 'message' => 'No he podido descargar ni procesar la URL principal. ' . ($error_msg1 ?: 'Comprueba que sea accesible públicamente.')]);
            exit;
        }
        
        $text2 = '';
        if ($url2) {
            if (filter_var($url2, FILTER_VALIDATE_URL) === false) {
                echo json_encode(['success' => false, 'message' => 'La segunda URL para la brecha semántica no es válida.']);
                exit;
            }
            $error_msg2 = '';
            $text2 = fetch_clean_semantic_text($url2, $error_msg2);
            if (!$text2) {
                echo json_encode(['success' => false, 'message' => 'No he podido descargar ni procesar la segunda URL (competidor). ' . ($error_msg2 ?: 'Comprueba que sea accesible públicamente.')]);
                exit;
            }
        }
        
        echo json_encode([
            'success' => true,
            'url1' => $url1,
            'text1' => $text1,
            'url2' => $url2,
            'text2' => $text2
        ]);
        exit;
    }
}

// Configuración de Página
$page = page_config([
    'title'        => 'Extractor de Entidades y Grafos Semánticos',
    'description'  => 'Audita la cobertura de entidades semánticas de tu web. Extrae triples, agrupa temáticas y compara brechas de entidades frente a tus competidores.',
    'canonical'    => '/herramientas/extractor-entidades/',
    'body_class'   => 'page-extractor-entidades',
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'extractor-entidades',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Extractor Semántico de Entidades', 'url' => ''],
    ],
    'faq_items' => [
        [
            'q' => '¿Por qué la extracción semántica es vital para el SEO actual?',
            'a' => 'Google ha evolucionado de un buscador léxico (que cuenta cuántas veces repites una palabra) a un buscador semántico basado en entidades (Knowledge Graph). Si tu contenido no menciona las entidades, conceptos y marcas que el algoritmo asocia matemáticamente a una temática, te costará horrores posicionar por mucho enlace que consigas.'
        ],
        [
            'q' => '¿Qué es exactamente la "Brecha Semántica" (Semantic Gap)?',
            'a' => 'Es la diferencia de cobertura de conceptos entre tu contenido y el de la competencia que ya posiciona en el top 3. Esta herramienta compara ambas URLs, extrae sus grafos de conocimiento y te muestra exactamente de qué conceptos clave están hablando tus competidores que tú has omitido en tu redacción.'
        ],
        [
            'q' => '¿Cómo funciona técnicamente este motor NLP?',
            'a' => 'Utilizamos un pipeline de Procesamiento de Lenguaje Natural (NLP) que limpia tu HTML de ruido (menús, footers), tokeniza el texto y aplica reglas de extracción de entidades nombradas (NER) cruzadas con una ontología técnica SEO/Web para modelar un grafo aproximado de cómo un robot entiende tu página.'
        ],
        [
            'q' => '¿Debo forzar la inclusión de las entidades que me faltan?',
            'a' => 'Nunca. El Keyword Stuffing penaliza, y el "Entity Stuffing" también. Debes utilizar las entidades sugeridas como una guía estructural para ampliar secciones de tu contenido, respondiendo a nuevas preguntas o profundizando en la temática de forma natural para el usuario.'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="semantic-h1">
    <div class="container">
      <span class="hero-eyebrow">SEO Semántico Avanzado y NLP</span>
      <h1 id="semantic-h1">Extractor de <span>Entidades Semánticas</span></h1>
      <p class="page-hero-desc">Visualiza una aproximación semántica de tu contenido: entidades, relaciones y posibles brechas frente a competidores.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      
      <div class="tool-intro">
        <h2>Aproxima el mapa semántico que un sistema NLP puede extraer de tu contenido</h2>
        <p>Introduce la URL de tu artículo o landing. Mi servidor limpiará el contenido de código ruidoso, mi motor de lenguaje natural extraerá las entidades nombradas y relaciones lógicas mediante reglas NLP y un diccionario técnico de ontología propio, permitiéndote auditar el grafo semántico aproximado y las brechas de cobertura frente a tus competidores.</p>
      </div>

      <div class="extractor-grid">
        
        <!-- Bloque de Entrada / Configuración -->
        <form id="semantic-analysis-form" class="card border-orange" style="padding: 2.5rem; border-radius: 1.5rem; margin-bottom: 2rem;"
          toolname="semanticEntityExtractor"
          tooldescription="Extrae entidades semánticas de una URL y compara las brechas de cobertura semántica con una URL competidora opcional, modelando un grafo conceptual e identificando conceptos clave omitidos."
          toolautosubmit="false">
            <h3 style="color: var(--orange); margin-bottom: 1.25rem; font-size: 1.3rem; font-weight: 800; text-transform: uppercase;">1. Configurar Análisis Semántico</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="sm-url1">URL Principal (Tu página o artículo) *</label>
                    <input type="url" class="form-input" id="sm-url1" value="https://www.victor-alonso.es/sobre-mi/" placeholder="https://tuweb.com/tu-articulo/" style="font-family: monospace;">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sm-url2">URL de Competidor (Opcional - Para Brecha Semántica)</label>
                    <input type="url" class="form-input" id="sm-url2" placeholder="https://competidor.com/su-articulo/" style="font-family: monospace;">
                </div>
            </div>

            <button type="submit" class="btn btn--primary" id="btn-analyze-entities" style="width: 100%; justify-content: center; padding: 1.1rem;">
                <span id="btn-text">Extraer Entidades y Modelar Grafo</span>
                <span id="btn-loader" class="loader-spinner" style="display: none;"></span>
            </button>
        </form>

        <!-- Pantalla de carga animada -->
        <div id="loading-overlay" class="card card--dark" style="display: none; padding: 3rem; text-align: center; border-radius: 1.5rem; margin-bottom: 2rem;">
            <div class="loader-spinner" style="width: 50px; height: 50px; border-width: 5px; border-top-color: var(--orange); margin: 0 auto 1.5rem;"></div>
            <h3 style="font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;" id="loading-title">Scrapeando contenido de forma limpia...</h3>
            <p style="color: var(--muted); font-size: .9rem;" id="loading-status">Llamando a mi servidor web para descargar el texto libre de scripts y cabeceras...</p>
        </div>

        <!-- Dashboard Completo de Resultados (Oculto inicialmente) -->
        <div id="results-panel" style="display: none;">
            
            <!-- Aviso de precisión profesional -->
            <div class="card card--dark" style="padding: 1.1rem 1.5rem; border-radius: 1rem; border: 1px solid rgba(232,104,26,0.3); border-left: 4px solid var(--orange); margin-bottom: 1.5rem; background: #0b101c; display: flex; align-items: flex-start; gap: 1rem; text-align: left;">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 0.15rem;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <p style="font-size: .8rem; color: #ffffff; margin: 0; line-height: 1.6;">
                    <strong style="color: var(--orange);">Aviso de precisión profesional:</strong> Este análisis es una aproximación técnica útil y no sustituye una auditoría semántica manual. La herramienta usa extracción NLP ligera, diccionario de ontología técnica y heurísticas de proximidad para detectar patrones conceptuales y brechas, no para replicar exactamente el Knowledge Graph oficial de Google.
                </p>
            </div>
            
            <!-- Pestañas de Visualización & Exportaciones -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div class="tab-container" style="display: flex; gap: 1rem;">
                    <button class="tab-btn active" onclick="switchVisualTab('tab-graph')">Grafo semántico aproximado</button>
                    <button class="tab-btn" onclick="switchVisualTab('tab-list')">Lista de Entidades & Relaciones</button>
                    <button class="tab-btn" id="btn-tab-gap" onclick="switchVisualTab('tab-gap')" style="display: none;">Análisis de Brecha Semántica</button>
                </div>
                <div style="display: flex; gap: .75rem; flex-wrap: wrap;">
                    <button class="btn btn--secondary" id="btn-export-csv" style="display: flex; align-items: center;">
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: .4rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Exportar CSV
                    </button>
                    <button class="btn btn--secondary" id="btn-export-pdf" style="display: flex; align-items: center;">
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: .4rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Exportar Informe PDF
                    </button>
                </div>
            </div>

            <!-- Guardar informe por email -->
            <div id="entities-email-card" style="display: none; background: #0b101c; border: 1px solid rgba(255,255,255,0.08); padding: 1.5rem; border-radius: 1.25rem; margin-bottom: 1.5rem; text-align: left;">
              <h4 style="color: #fff; font-size: 1.05rem; margin: 0 0 0.25rem 0; font-weight: 700;">Recibir informe semántico en tu email</h4>
              <p style="margin: 0 0 1rem 0; font-size: 0.82rem; color: #cbd5e1;">
                Te enviaremos el documento PDF con el análisis de brecha semántica y la lista de entidades clave de tu competidor de forma gratuita.
              </p>
              
              <form id="entities-email-form" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0;"
                toolname="emailSemanticReport"
                tooldescription="Envía el informe semántico detallado en formato PDF al correo electrónico especificado."
                toolautosubmit="false">
                <input type="email" name="email" id="entities-email-input" placeholder="tu@email.com" required style="flex: 1; min-width: 250px; padding: 0.65rem 1rem; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; font-size: 0.9rem; color: #fff; background: #060911;" class="tool-form__input">
                <button type="submit" id="btn-entities-send-email" class="btn btn--primary" style="background: var(--orange); border: none; padding: 0.65rem 1.25rem; font-weight: 600; font-size: 0.9rem; margin: 0; min-width: 150px; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                  Enviar informe
                </button>
                <button type="button" id="btn-entities-direct-download" class="btn btn--secondary" style="margin: 0; padding: 0.65rem 1.25rem; font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.15); background: transparent; color: #fff;">
                  Descargar directo
                </button>
              </form>
              <div id="entities-email-status" style="display: none; font-size: 0.85rem; margin-top: 0.5rem; font-weight: 600;"></div>
            </div>

            <!-- Pestaña 1: Grafo en Canvas -->
            <div id="tab-graph" class="tab-visual-content active">
                <div class="card card--dark" style="padding: 1.5rem; border-radius: 1.5rem; position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <div style="text-align: left;">
                            <h3 style="color: #fff; font-size: 1.25rem; font-weight: 800; margin-bottom: 0.25rem;">Visualizador del Grafo Semántico</h3>
                            <p style="font-size: .8rem; color: #cbd5e1; margin: 0;">Interactúa con el grafo: arrastra los nodos, haz zoom o filtra categorías abajo.</p>
                        </div>
                        <div class="legend" style="display: flex; gap: .75rem; flex-wrap: wrap; font-size: .7rem;">
                            <span class="legend-badge org">Organizaciones / Marcas</span>
                            <span class="legend-badge person">Personas</span>
                            <span class="legend-badge tech">Tecnologías / Frameworks</span>
                            <span class="legend-badge concept">Conceptos SEO / Temas</span>
                            <span class="legend-badge place">Lugares / Ubicaciones</span>
                        </div>
                    </div>
                    
                    <!-- Filtros interactivos de tipo de entidad -->
                    <div style="display: flex; gap: 1.2rem; margin-bottom: 1.25rem; flex-wrap: wrap; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1rem;">
                        <span style="font-size: .75rem; color: #fff; font-weight: 800; text-transform: uppercase;">Mostrar en Grafo:</span>
                        <label style="display: flex; align-items: center; gap: .4rem; font-size: .75rem; color: #cbd5e1; cursor: pointer; user-select: none;">
                            <input type="checkbox" id="filter-org" checked onchange="triggerFilterUpdate()" style="accent-color: #e8681a; cursor: pointer;"> <span style="color: #e8681a; font-weight: bold;">Organizaciones</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: .4rem; font-size: .75rem; color: #cbd5e1; cursor: pointer; user-select: none;">
                            <input type="checkbox" id="filter-person" checked onchange="triggerFilterUpdate()" style="accent-color: #2ecc71; cursor: pointer;"> <span style="color: #2ecc71; font-weight: bold;">Personas</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: .4rem; font-size: .75rem; color: #cbd5e1; cursor: pointer; user-select: none;">
                            <input type="checkbox" id="filter-tech" checked onchange="triggerFilterUpdate()" style="accent-color: #3498db; cursor: pointer;"> <span style="color: #3498db; font-weight: bold;">Tecnologías</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: .4rem; font-size: .75rem; color: #cbd5e1; cursor: pointer; user-select: none;">
                            <input type="checkbox" id="filter-concept" checked onchange="triggerFilterUpdate()" style="accent-color: #9b59b6; cursor: pointer;"> <span style="color: #9b59b6; font-weight: bold;">Conceptos SEO</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: .4rem; font-size: .75rem; color: #cbd5e1; cursor: pointer; user-select: none;">
                            <input type="checkbox" id="filter-place" checked onchange="triggerFilterUpdate()" style="accent-color: #f1c40f; cursor: pointer;"> <span style="color: #f1c40f; font-weight: bold;">Lugares</span>
                        </label>
                    </div>

                    <div style="background: #060911; border-radius: 1rem; border: 1px solid rgba(255,255,255,0.04); overflow: hidden; position: relative; height: 500px;">
                        <canvas id="graph-canvas" width="1000" height="500" style="display: block; cursor: grab;"></canvas>
                        
                        <!-- Panel flotante de controles de zoom (vertical estilo Google Maps) -->
                        <div style="position: absolute; bottom: 1rem; right: 1rem; display: flex; flex-direction: column; gap: .5rem; z-index: 10; align-items: flex-end;">
                            <button class="zoom-btn" onclick="zoomGraph(1.2)">+</button>
                            <button class="zoom-btn" onclick="zoomGraph(0.8)">&minus;</button>
                            <button class="zoom-btn" onclick="resetGraphView()" style="width: auto; min-width: 38px; padding: 0 .6rem; font-size: .65rem; text-transform: uppercase; white-space: nowrap;">Centrar</button>
                        </div>
                        
                        <!-- Tooltip del nodo flotante -->
                        <div id="graph-tooltip" style="position: absolute; background: rgba(11,17,30,0.95); border: 1px solid var(--orange); border-radius: .5rem; padding: .5rem 1rem; color: #fff; font-size: .75rem; display: none; pointer-events: none; box-shadow: 0 10px 20px rgba(0,0,0,0.5); z-index: 20;"></div>
                    </div>
                </div>
            </div>

            <!-- Pestaña 2: Lista de Entidades & Triples -->
            <div id="tab-list" class="tab-visual-content" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem;">
                    
                    <!-- Columna de Entidades -->
                    <div class="card card--dark" style="padding: 2rem; border-radius: 1.5rem;">
                        <h3 style="color: #fff; font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: .75rem; text-align: left;">Entidades Encontradas (<span id="total-entities-count">0</span>)</h3>
                        <div id="entities-list-wrapper" style="max-height: 400px; overflow-y: auto; padding-right: .5rem;">
                            <!-- Inyectado dinámicamente -->
                        </div>
                    </div>

                    <!-- Columna de Triples Semánticos -->
                    <div class="card card--dark" style="padding: 2rem; border-radius: 1.5rem;">
                        <h3 style="color: #fff; font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: .75rem; text-align: left;">Relaciones Detectadas</h3>
                        <div id="triples-list-wrapper" style="max-height: 400px; overflow-y: auto; padding-right: .5rem;">
                            <!-- Inyectado dinámicamente -->
                        </div>
                    </div>

                </div>
            </div>

            <!-- Pestaña 3: Análisis de Brecha Semántica -->
            <div id="tab-gap" class="tab-visual-content" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
                    
                    <!-- Panel de Score de Cobertura Semántica y Diagnóstico de Riesgo -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: .5rem;">
                        <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; border: 1px solid rgba(255,255,255,0.04); position: relative;">
                            <span style="font-size: .75rem; font-weight: 800; text-transform: uppercase; color: var(--muted); margin-bottom: 0.75rem;">Cobertura Semántica Estimada</span>
                            <div style="position: relative; width: 100px; height: 100px; display: flex; justify-content: center; align-items: center;">
                                <svg width="100" height="100" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="6" />
                                    <circle cx="50" cy="50" r="42" fill="none" id="score-ring" stroke="var(--orange)" stroke-width="6" stroke-dasharray="263.89" stroke-dashoffset="263.89" style="transition: stroke-dashoffset 0.8s ease;" />
                                </svg>
                                <span id="score-text" style="position: absolute; font-size: 1.5rem; font-weight: 900; color: #fff;">0%</span>
                            </div>
                        </div>
                        <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; display: flex; flex-direction: column; justify-content: center; border: 1px solid rgba(255,255,255,0.04); text-align: left;">
                            <h4 style="font-size: .75rem; font-weight: 800; text-transform: uppercase; color: var(--muted); margin: 0 0 .5rem 0;">Diagnóstico de Riesgo Editorial</h4>
                            <div style="margin-bottom: .5rem;">
                                <span id="gap-risk-badge" class="badge" style="background: rgba(231,76,60,0.15); color: #e74c3c; padding: .3rem .6rem; border-radius: .4rem; font-size: .65rem; font-weight: 800; text-transform: uppercase;">Cargando...</span>
                            </div>
                            <p id="gap-risk-desc" style="font-size: .75rem; color: #cbd5e1; margin: 0; line-height: 1.45;"></p>
                        </div>
                    </div>
                    
                    <!-- Stats comparativos -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid var(--orange); text-align: left;">
                            <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted);">Entidades Comunes</span>
                            <span id="gap-common" style="display: block; font-size: 2rem; font-weight: 900; color: #fff; margin-top: .25rem;">0</span>
                        </div>
                        <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid #2ecc71; text-align: left;">
                            <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted);">Tus Entidades Únicas</span>
                            <span id="gap-unique1" style="display: block; font-size: 2rem; font-weight: 900; color: #2ecc71; margin-top: .25rem;">0</span>
                        </div>
                        <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid #e74c3c; text-align: left;">
                            <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted);">Entidades en Competidor</span>
                            <span id="gap-unique2" style="display: block; font-size: 2rem; font-weight: 900; color: #e74c3c; margin-top: .25rem;">0</span>
                        </div>
                        <div class="card card--dark" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid #3498db; text-align: left;">
                            <span style="display: block; font-size: .8rem; font-weight: 800; text-transform: uppercase; color: var(--muted);">Solapamiento Semántico</span>
                            <span id="gap-overlap-pct" style="display: block; font-size: 2rem; font-weight: 900; color: #3498db; margin-top: .25rem;">0%</span>
                        </div>
                    </div>

                    <!-- Cuadro de sugerencias de brecha semántica -->
                    <div class="card card--dark" style="padding: 2rem; border-radius: 1.5rem; border: 1px dashed rgba(231,76,60,0.3); text-align: left;">
                        <h3 style="color: #e74c3c; font-size: 1.25rem; font-weight: 800; margin-bottom: 1rem;">Oportunidades de Cobertura (Conceptos potencialmente no cubiertos):</h3>
                        <p style="font-size: .9rem; color: #cbd5e1; margin-bottom: 1.5rem;">He detectado las siguientes entidades conceptuales fuertes en el contenido de tu competidor que están ausentes en tu texto. Integrarlas con sentido puede ayudarte a cubrir mejor el contexto temático de la consulta:</p>
                        
                        <div id="gap-keywords-box" style="display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 2rem;">
                            <!-- Inyectado dinámicamente -->
                        </div>
                    </div>
                    
                    <!-- Tabla de Prioridad Editorial -->
                    <div class="card card--dark" style="padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.04); text-align: left;">
                        <h3 style="color: #fff; font-size: 1.15rem; font-weight: 800; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: .75rem;">Prioridad Editorial de Entidades para Optimizar</h3>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: .8rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.08); color: var(--muted); font-weight: 800;">
                                        <th style="padding: .75rem .5rem;">Entidad Ausente</th>
                                        <th style="padding: .75rem .5rem;">Categoría</th>
                                        <th style="padding: .75rem .5rem; text-align: center;">Frecuencia Competidor</th>
                                        <th style="padding: .75rem .5rem;">Acción Recomendada</th>
                                    </tr>
                                </thead>
                                <tbody id="editorial-table-body">
                                    <!-- Inyectado dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

        </div>

      </div>

      <!-- Sección de Criterio Técnico SEO "Non-Commodity" -->
      <div class="criterio-section" style="margin-top: 5rem;">
        <span class="section-label">Procesamiento de Lenguaje Natural y SEO</span>
        <h2>¿Por qué Google clasifica tus contenidos por Entidades y no por Keywords?</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>Del "Strings to Things" de Google</h3>
            <p>En el año 2012, Google introdujo el Knowledge Graph iniciando la transición de "cadenas de texto" a "cosas". Los algoritmos modernos (BERT, MUM) no analizan la repetición de palabras clave independientes.</p>
            <p>Mapean entidades (conceptos reales, marcas, personas) y miden su relación (predicados). Si tu web habla de "desarrollo web" pero carece de entidades de soporte lógicas como "bases de datos", "código limpio" o "servidores", carecerá de relevancia semántica a ojos del buscador.</p>
          </div>

          <div class="criterio-card">
            <h3>La importancia del NAP en SEO Local</h3>
            <p>En SEO local, la consistencia NAP (Nombre, Dirección, Teléfono) es una relación de entidad en el Grafo de Google. Si tu marcado Schema, tu web y tus directorios declaran exactamente las mismas coordenadas y relaciones físicas, Google consolida tu entidad local aumentando directamente tu visibilidad en Google Maps.</p>
          </div>

          <div class="criterio-card">
            <h3>La minería de brecha semántica (Semantic Gap)</h3>
            <p>La optimización on-page científica consiste en auditar a las webs que ya ocupan las primeras posiciones en Google, extraer su Grafo de Conocimiento e identificar qué nodos conceptuales están cubriendo ellos que tú has pasado por alto. No es añadir más texto; es dotar a tu contenido de mayor profundidad temática.</p>
          </div>
        </div>
      </div>

      <!-- Enlazado Interno de Herramientas Relacionadas -->
      <?php require dirname(__DIR__) . '/includes/related-tools.php'; ?>

      <!-- Valoraciones -->
      <?php render_rating_widget('extractor-entidades'); ?>

    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Quieres diseñar una estrategia de contenido semántico?',
    'subtitle'  => 'Mapeo el universo conceptual de tu nicho, detecto brechas de indexación de tus competidores y diseño arquitecturas web semánticas orientadas a dominar el Grafo de Conocimiento.',
    'btn_label' => 'Auditar mi semántica técnica',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<!-- Inclusión de la librería NLP Compromise por CDN -->
<script src="https://cdn.jsdelivr.net/npm/compromise@14.14.0/builds/compromise.min.js"></script>

<style>
/* Estilos premium de neón y badges */

.legend-badge {
    padding: .35rem .75rem;
    border-radius: .5rem;
    font-weight: 700;
    color: #fff;
}
.legend-badge.org { background: rgba(232,104,26,0.15); border: 1px solid var(--orange); color: var(--orange); }
.legend-badge.person { background: rgba(46,204,113,0.15); border: 1px solid #2ecc71; color: #2ecc71; }
.legend-badge.tech { background: rgba(52,152,219,0.15); border: 1px solid #3498db; color: #3498db; }
.legend-badge.concept { background: rgba(155,89,182,0.15); border: 1px solid #9b59b6; color: #9b59b6; }
.legend-badge.place { background: rgba(241,196,15,0.15); border: 1px solid #f1c40f; color: #f1c40f; }

.zoom-btn {
    background: rgba(11,17,30,0.9);
    border: 1px solid rgba(255,255,255,0.08);
    color: #fff;
    width: 38px;
    height: 38px;
    border-radius: .5rem;
    font-size: 1.1rem;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: all 0.2s ease;
}
.zoom-btn:hover {
    border-color: var(--orange);
    color: var(--orange);
}

.ent-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .75rem 1rem;
    background: rgba(0,0,0,0.2);
    border-radius: .75rem;
    border: 1px solid rgba(255,255,255,0.03);
    margin-bottom: .5rem;
}
.ent-badge {
    font-size: .65rem;
    text-transform: uppercase;
    font-weight: 800;
    padding: .2rem .5rem;
    border-radius: .35rem;
}
.ent-badge.org { background: rgba(232,104,26,0.15); color: var(--orange); }
.ent-badge.person { background: rgba(46,204,113,0.15); color: #2ecc71; }
.ent-badge.tech { background: rgba(52,152,219,0.15); color: #3498db; }
.ent-badge.concept { background: rgba(155,89,182,0.15); color: #9b59b6; }
.ent-badge.place { background: rgba(241,196,15,0.15); color: #f1c40f; }

.triple-card {
    padding: .85rem 1.1rem;
    background: rgba(255,255,255,0.01);
    border: 1px solid rgba(255,255,255,0.03);
    border-radius: .75rem;
    margin-bottom: .6rem;
    font-size: .82rem;
}
.triple-sub { color: #fff; font-weight: 700; font-family: monospace; }
.triple-pred { color: var(--orange); font-weight: 800; text-transform: uppercase; font-size: .75rem; margin: 0 .5rem; }
.triple-obj { color: #cbd5e1; font-weight: 700; font-family: monospace; }

.gap-keyword {
    background: rgba(231,76,60,0.1);
    color: #e74c3c;
    border: 1px solid rgba(231,76,60,0.15);
    padding: .4rem .9rem;
    border-radius: 2rem;
    font-size: .8rem;
    font-weight: 700;
    font-family: monospace;
}
</style>

<script>
// Ontología técnica y semántica personalizada (Español & Inglés)
const techDictionary = {
    'react': 'tech', 'reactjs': 'tech', 'angular': 'tech', 'vue': 'tech', 'vuejs': 'tech', 'nextjs': 'tech',
    'php': 'tech', 'wordpress': 'tech', 'laravel': 'tech', 'symfony': 'tech', 'magento': 'tech', 'prestashop': 'tech',
    'node': 'tech', 'nodejs': 'tech', 'javascript': 'tech', 'js': 'tech', 'css': 'tech', 'html': 'tech', 'sass': 'tech',
    'android': 'tech', 'ios': 'tech', 'java': 'tech', 'kotlin': 'tech', 'swift': 'tech', 'flutter': 'tech',
    'mysql': 'tech', 'postgresql': 'tech', 'mongodb': 'tech', 'redis': 'tech', 'docker': 'tech', 'git': 'tech',
    'seo': 'concept', 'wpo': 'concept', 'sem': 'concept', 'analytics': 'concept', 'cro': 'concept', 'schema': 'concept',
    'sitemap': 'concept', 'robots.txt': 'concept', 'canonical': 'concept', 'crawling': 'concept', 'indexación': 'concept',
    'algoritmo': 'concept', 'entidad': 'concept', 'entidades': 'concept', 'nlp': 'concept', 'knowledge graph': 'concept',
    'albacete': 'place', 'españa': 'place', 'madrid': 'place', 'barcelona': 'place', 'walkiria': 'org',
    'walkiria apps': 'org', 'google': 'org', 'googlebot': 'org', 'screaming frog': 'org'
};

const customVerbs = [
    'desarrolla', 'programa', 'optimiza', 'crea', 'fundó', 'diseña', 'gestiona',
    'integra', 'utiliza', 'aplica', 'ayuda', 'posiciona', 'domina', 'ofrece', 'ofreciendo'
];

// Datos del análisis en memoria
let analysisData = {
    entities1: [],
    entities2: [],
    triples1: [],
    triples2: [],
    graph1: { nodes: [], links: [] }
};

// Configuración del canvas de física del Grafo
const canvas = document.getElementById('graph-canvas');
const ctx = canvas.getContext('2d');
let nodes = [];
let links = [];
let dragNode = null;
let transform = { x: 0, y: 0, scale: 1 };
let isPanning = false;
let startPan = { x: 0, y: 0 };
let animationFrameId = null;

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('semantic-analysis-form').addEventListener('submit', function(e) {
        e.preventDefault();
        startSemanticScraping();
    });
    
    // Configurar exportaciones
    document.getElementById('btn-export-csv').addEventListener('click', exportEntitiesToCSV);
    
    // Toggle de la tarjeta de email al hacer clic en Exportar PDF
    document.getElementById('btn-export-pdf').addEventListener('click', function() {
        const card = document.getElementById('entities-email-card');
        const statusMsg = document.getElementById('entities-email-status');
        if (statusMsg) statusMsg.style.display = 'none';
        if (card.style.display === 'none') {
            card.style.display = 'block';
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            card.style.display = 'none';
        }
    });

    // Descarga directa desde la tarjeta de email
    document.getElementById('btn-entities-direct-download').addEventListener('click', function() {
        exportGraphToPDF(null);
    });

    // Envío de email desde el formulario
    document.getElementById('entities-email-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const emailVal = document.getElementById('entities-email-input').value.trim();
        if (emailVal) {
            exportGraphToPDF(emailVal);
        }
    });
    
    // Configurar interacciones del canvas
    setupCanvasInteraction();
});

// Exportar Grafo de Conocimiento Semántico como PDF usando Puppeteer
async function exportGraphToPDF(email = null) {
    if (nodes.length === 0) {
        alert("Primero debes extraer entidades para generar el informe.");
        return;
    }
    
    // Si se pasa email, usamos el botón de enviar email como loader
    const btn = email ? document.getElementById('btn-entities-send-email') : document.getElementById('btn-export-pdf');
    const statusMsg = document.getElementById('entities-email-status');
    if (email && statusMsg) {
        statusMsg.style.display = 'none';
    }

    const originalContent = btn.innerHTML;
    btn.innerHTML = email 
      ? '<span class="loader-spinner" style="display:inline-block; margin-right:5px; border-color:#fff transparent transparent transparent; width:12px; height:12px; border-width:2px;"></span> Enviando...'
      : '<span class="loader-spinner" style="display:inline-block; margin-right:5px; border-color:#e74c3c transparent transparent transparent; width:12px; height:12px; border-width:2px;"></span> Generando PDF...';
    btn.disabled = true;

    // Crear un canvas temporal para asegurar el fondo oscuro premium en la imagen
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.width;
    tempCanvas.height = canvas.height;
    const tempCtx = tempCanvas.getContext('2d');
    tempCtx.fillStyle = '#060911';
    tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
    tempCtx.drawImage(canvas, 0, 0);
    const graphImage = tempCanvas.toDataURL("image/png");

    let score = '0';
    let gapCommon = '0';
    let gapMissing = '0';
    
    if (document.getElementById('score-text')) {
        score = document.getElementById('score-text').innerText.replace('%', '');
    }
    if (document.getElementById('gap-common')) {
        gapCommon = document.getElementById('gap-common').innerText;
    }
    if (document.getElementById('gap-missing')) {
        gapMissing = document.getElementById('gap-missing').innerText;
    }

    const payload = {
        email: email,
        url1: document.getElementById('sm-url1').value,
        url2: document.getElementById('sm-url2').value,
        entities: analysisData.entities1,
        triples: analysisData.triples1,
        graphImage: graphImage,
        gapStats: {
            score: score,
            common: gapCommon,
            missing: gapMissing
        }
    };

    try {
        const response = await fetch('/herramientas/extractor-entidades-pdf.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (response.ok) {
            const blob = await response.blob();
            if (blob.type === 'application/json') {
                const text = await blob.text();
                const res = JSON.parse(text);
                if (email) {
                    if (res.success) {
                        statusMsg.style.color = '#2ecc71';
                        statusMsg.innerHTML = '🟢 ¡Informe enviado con éxito! Revisa tu bandeja de entrada.';
                        statusMsg.style.display = 'block';
                        document.getElementById('entities-email-input').value = '';
                    } else {
                        statusMsg.style.color = '#e74c3c';
                        statusMsg.innerHTML = '⚠️ Error: ' + (res.message || 'No se pudo enviar.');
                        statusMsg.style.display = 'block';
                    }
                } else {
                    alert('Error al generar PDF: ' + (res.message || 'Error desconocido'));
                }
            } else {
                if (email) {
                    statusMsg.style.color = '#2ecc71';
                    statusMsg.innerHTML = '🟢 ¡Informe enviado con éxito!';
                    statusMsg.style.display = 'block';
                } else {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `informe-semantico-${Date.now()}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                }
            }
        } else {
            if (email) {
                statusMsg.style.color = '#e74c3c';
                statusMsg.innerHTML = '⚠️ Error de servidor al enviar el email.';
                statusMsg.style.display = 'block';
            } else {
                alert('Error al generar PDF: el servidor devolvió un error ' + response.status);
            }
        }
    } catch (err) {
        console.error(err);
        if (email) {
            statusMsg.style.color = '#e74c3c';
            statusMsg.innerHTML = '⚠️ Error de red o conexión al enviar el email.';
            statusMsg.style.display = 'block';
        } else {
            alert('Error de red o conexión al generar PDF.');
        }
    } finally {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

// Exportar listado de entidades a un archivo CSV estructurado
function exportEntitiesToCSV() {
    if (analysisData.entities1.length === 0) {
        alert("Primero debes extraer entidades.");
        return;
    }
    
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"; // BOM para Excel
    csvContent += "Entidad,Tipo,Frecuencia\n";
    
    analysisData.entities1.forEach(ent => {
        const escapedName = ent.name.replace(/"/g, '""');
        csvContent += `"${escapedName}","${ent.type.toUpperCase()}",${ent.frequency}\n`;
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "entidades-semanticas-victor-alonso.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Pestañas
function switchVisualTab(tabId) {
    document.querySelectorAll('.tab-visual-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabId).style.display = 'block';
    
    // Activar botón respectivo
    const clickedBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.getAttribute('onclick').includes(tabId));
    if (clickedBtn) clickedBtn.classList.add('active');
    
    // Si entramos al grafo, re-arrancar animación si se había pausado
    if (tabId === 'tab-graph') {
        startGraphSimulation();
    }
}

// Scrapeo Ajax
function startSemanticScraping() {
    const url1 = document.getElementById('sm-url1').value.trim();
    const url2 = document.getElementById('sm-url2').value.trim();
    
    if (!url1) {
        alert('Introduce al menos la URL principal.');
        return;
    }
    
    // Reset e interfaces de carga
    document.getElementById('btn-analyze-entities').disabled = true;
    document.getElementById('btn-text').style.display = 'none';
    document.getElementById('btn-loader').style.display = 'inline-block';
    
    document.getElementById('loading-overlay').style.display = 'block';
    document.getElementById('results-panel').style.display = 'none';
    document.getElementById('btn-tab-gap').style.display = 'none';
    
    // Parar simulación de grafo previa
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }
    
    const formData = new FormData();
    formData.append('action', 'extract_entities');
    formData.append('url1', url1);
    formData.append('url2', url2);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            processSemanticNLP(data);
        } else {
            alert(data.message);
            document.getElementById('loading-overlay').style.display = 'none';
        }
    })
    .catch(err => {
        alert('Error de red al conectar con el servidor.');
        document.getElementById('loading-overlay').style.display = 'none';
    })
    .finally(() => {
        document.getElementById('btn-analyze-entities').disabled = false;
        document.getElementById('btn-text').style.display = 'inline-block';
        document.getElementById('btn-loader').style.display = 'none';
    });
}

// Pipeline NLP & NER Reactivo
function processSemanticNLP(data) {
    document.getElementById('loading-status').textContent = 'Ejecutando minería semántica y clasificación NLP en navegador...';
    
    setTimeout(() => {
        // 1. Extraer entidades URL 1
        analysisData.entities1 = runNLPAnalysis(data.text1);
        analysisData.triples1 = runTripleExtraction(data.text1, analysisData.entities1);
        
        // 2. Extraer entidades URL 2 si existe
        if (data.text2) {
            analysisData.entities2 = runNLPAnalysis(data.text2);
            analysisData.triples2 = runTripleExtraction(data.text2, analysisData.entities2);
            document.getElementById('btn-tab-gap').style.display = 'inline-block';
            
            // Procesar brecha semántica
            renderSemanticGap(analysisData.entities1, analysisData.entities2);
        }
        
        // 3. Generar Grafo
        buildGraphData(analysisData.entities1, analysisData.triples1);
        
        // 4. Renderizar listas
        renderLists();
        
        // Finalizar y mostrar panel
        document.getElementById('loading-overlay').style.display = 'none';
        document.getElementById('results-panel').style.display = 'block';
        
        switchVisualTab('tab-graph');
    }, 100);
}

// Analizador de entidades base
function runNLPAnalysis(text) {
    const seen = new Set();
    const entities = [];
    
    // Stopwords extendidas en Español e Inglés para limpiar ruido gramatical
    const stopwords = new Set([
        'que', 'qué', 'para', 'como', 'cómo', 'este', 'esta', 'estos', 'estas', 'ese', 'esa', 'esos', 'esas', 
        'aquel', 'aquella', 'donde', 'dónde', 'cuando', 'cuándo', 'quien', 'quién', 'pero', 'por', 'con', 'sin', 
        'sobre', 'tras', 'desde', 'hasta', 'entre', 'hacia', 'durante', 'mediante', 'excepto', 'salvo', 'incluso', 
        'más', 'mas', 'muy', 'tan', 'tanto', 'todo', 'toda', 'todos', 'todas', 'nada', 'algo', 'alguno', 'alguna', 
        'algunos', 'algunas', 'ninguno', 'ninguna', 'otro', 'otra', 'otros', 'otras', 'mismo', 'misma', 'mismos', 
        'mismas', 'cuyo', 'cuya', 'cuyos', 'cuyas', 'este', 'esto', 'aquello', 'ellos', 'ellas', 'nosotros', 'nosotras', 
        'usted', 'ustedes', 'suyo', 'suya', 'suyos', 'suyas', 'tuyo', 'tuya', 'tuyos', 'tuyas', 'mío', 'mía', 'míos', 
        'mías', 'nuestro', 'nuestra', 'nuestros', 'nuestras', 'yo', 'me', 'mi', 'mis', 'tu', 'tus', 'su', 'sus', 
        'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'y', 'o', 'u', 'e', 'ni', 'si', 'no', 'del', 'al',
        'perfil', 'proyectos', 'currículum', 'además', 'hola', 'quién', 'quiénes', 'quien', 'quienes', 'trabajo', 
        'clientes', 'ver', 'interactivo', 'especialista', 'enfoque', 'práctico', 'técnico', 'quién', 'quien', 
        'cómo', 'como', 'cuál', 'cual', 'cuáles', 'cuales', 'algún', 'cada', 'tienen', 'tiene', 'tenemos', 'tengo',
        'soy', 'eres', 'es', 'somos', 'sois', 'son', 'fui', 'fuiste', 'fue', 'fuimos', 'fueron', 'hacer', 'hecho',
        'hace', 'hacen', 'hacemos', 'hago', 'puedes', 'puede', 'pueden', 'podemos', 'puedo', 'saber', 'sabes',
        'sabe', 'saben', 'sabemos', 'sé', 'quieres', 'quiere', 'quieren', 'queremos', 'quiero', 'esa', 'para', 'saber'
    ]);
    
    // Función auxiliar para registrar la entidad con tipo y relevancia
    function addEntity(name, type, weight) {
        let cleanName = name.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g, "").trim();
        
        // Limpiar artículos o verbos iniciales de frases como "Soy Víctor Alonso" -> "Víctor Alonso"
        const words = cleanName.split(/\s+/);
        if (words.length > 1) {
            const firstWord = words[0].toLowerCase();
            if (firstWord === 'soy' || firstWord === 'el' || firstWord === 'la' || firstWord === 'los' || firstWord === 'las' || firstWord === 'un' || firstWord === 'una') {
                cleanName = cleanName.substring(words[0].length).trim();
            }
        }
        
        if (cleanName.length < 3 || cleanName.length > 35) return;
        
        const lower = cleanName.toLowerCase();
        
        // Evitar registrar stopwords directas
        if (stopwords.has(lower) || lower === 'que' || lower === 'qué' || lower === 'para') return;
        
        // Si la frase se compone exclusivamente de stopwords, descartar
        const wordsInPhrase = lower.split(/\s+/);
        const allStopwords = wordsInPhrase.every(w => stopwords.has(w));
        if (allStopwords) return;
        
        if (seen.has(lower)) {
            const existing = entities.find(e => e.name.toLowerCase() === lower);
            if (existing) {
                existing.frequency++;
                existing.weight = Math.min(10, existing.weight + 0.5);
            }
            return;
        }
        
        seen.add(lower);
        entities.push({
            name: cleanName,
            type: type,
            weight: weight,
            frequency: 1
        });
    }
    
    // FASE 1: Alta prioridad - Diccionario Ontológico Local (100% Precisión)
    const textLower = text.toLowerCase();
    Object.keys(techDictionary).forEach(key => {
        const regexKey = new RegExp('\\b' + key.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + '\\b', 'g');
        const matches = textLower.match(regexKey);
        if (matches) {
            const prettyName = key.toUpperCase() === key ? key : key.charAt(0).toUpperCase() + key.slice(1);
            addEntity(prettyName, techDictionary[key], 4);
            
            // Asignar el número exacto de apariciones reales
            const count = matches.length;
            const existing = entities.find(e => e.name.toLowerCase() === key);
            if (existing) {
                existing.frequency = count;
                existing.weight = Math.min(10, 4 + count * 0.25);
            }
        }
    });
    
    // FASE 2: Extracciones estándar de Compromise.js (Si no han sido pre-taggeadas)
    const doc = nlp(text);
    doc.people().json().forEach(p => addEntity(p.text, 'person', 3));
    doc.organizations().json().forEach(o => addEntity(o.text, 'org', 3));
    doc.places().json().forEach(pl => addEntity(pl.text, 'place', 3));
    
    // FASE 3: Heurística de Sustantivos Propios (Nombres Propios desconocidos de fallback)
    const regexProper = /\b([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*)\b/g;
    let match;
    while ((match = regexProper.exec(text)) !== null) {
        const word = match[1].trim();
        const lowerWord = word.toLowerCase();
        
        // Evitar capturar stopwords capitalizadas
        if (stopwords.has(lowerWord)) continue;
        
        if (word.length > 3 && !seen.has(lowerWord)) {
            // Evitar palabras al inicio de frase tras puntuación
            const idx = match.index;
            const before = text.substring(Math.max(0, idx - 3), idx).trim();
            if (before.endsWith('.') || before.endsWith('?') || before.endsWith('!')) {
                continue; 
            }
            
            addEntity(word, 'org', 2);
        }
    }
    
    // Ordenar por relevancia e importancia temática y limitar a las top 35
    return entities.sort((a,b) => (b.frequency * b.weight) - (a.frequency * a.weight)).slice(0, 35);
}

// Extractor de triples semánticos (Sujeto - Predicado - Objeto) de precisión secuencial
function runTripleExtraction(text, entities) {
    const triples = [];
    const sentences = text.split(/[.?!]+/);
    const seenTriples = new Set();
    
    sentences.forEach(sentence => {
        const sentenceLower = sentence.toLowerCase();
        
        // 1. Encontrar qué entidades están presentes y su posición exacta en la oración
        const presentEntities = [];
        entities.forEach(ent => {
            const index = sentenceLower.indexOf(ent.name.toLowerCase());
            if (index !== -1) {
                presentEntities.push({
                    name: ent.name,
                    index: index
                });
            }
        });
        
        // 2. Ordenar las entidades presentes por su orden de aparición real en la frase
        presentEntities.sort((a, b) => a.index - b.index);
        
        if (presentEntities.length >= 2) {
            // 3. Buscar el verbo/conector que esté entre las dos primeras entidades consecutivas
            for (let i = 0; i < presentEntities.length - 1; i++) {
                const entA = presentEntities[i];
                const entB = presentEntities[i + 1];
                
                // Extraer el fragmento de texto entre ambas entidades para buscar el conector
                const textBetween = sentenceLower.substring(entA.index + entA.name.length, entB.index);
                
                // Buscar si hay algún verbo conector de nuestra lista en este fragmento
                const wordsBetween = textBetween.split(/[\s,.:;()]+/);
                const foundVerb = customVerbs.find(verb => wordsBetween.includes(verb));
                
                if (foundVerb) {
                    const tripleKey = `${entA.name}-${foundVerb}-${entB.name}`.toLowerCase();
                    if (!seenTriples.has(tripleKey)) {
                        seenTriples.add(tripleKey);
                        triples.push({
                            subject: entA.name,
                            predicate: foundVerb,
                            object: entB.name
                        });
                    }
                    break; // Generar una relación fuerte por frase para evitar redundancias
                }
            }
        }
    });
    
    return triples.slice(0, 15); // Limitar a los 15 mejores triples lógicos
}

// Renderizar listas en UI
function renderLists() {
    // 1. Lista de entidades
    const listWrapper = document.getElementById('entities-list-wrapper');
    listWrapper.innerHTML = '';
    document.getElementById('total-entities-count').textContent = analysisData.entities1.length;
    
    analysisData.entities1.forEach(ent => {
        const item = document.createElement('div');
        item.className = 'ent-item';
        item.innerHTML = `
            <span style="font-weight: 700; color: #fff;">${ent.name}</span>
            <div style="display: flex; gap: .5rem; align-items: center;">
                <span style="font-size: .75rem; color: #cbd5e1; margin-right: .5rem;">Frecuencia: ${ent.frequency}</span>
                <span class="ent-badge ${ent.type}">${ent.type}</span>
            </div>
        `;
        listWrapper.appendChild(item);
    });
    
    // 2. Lista de triples
    const triplesWrapper = document.getElementById('triples-list-wrapper');
    triplesWrapper.innerHTML = '';
    
    if (analysisData.triples1.length === 0) {
        triplesWrapper.innerHTML = '<p style="color: #cbd5e1; font-size: .85rem; text-align: center; margin-top: 2rem;">No he podido deducir triples semánticos claros en base a los conectores verbales. Inténtalo con un artículo que contenga más enunciados descriptivos.</p>';
    } else {
        analysisData.triples1.forEach(tr => {
            const card = document.createElement('div');
            card.className = 'triple-card';
            card.innerHTML = `
                <span class="triple-sub">${tr.subject}</span>
                <span class="triple-pred">${tr.predicate}</span>
                <span class="triple-obj">${tr.object}</span>
            `;
            triplesWrapper.appendChild(card);
        });
    }
}

// Modelar Brecha Semántica comparativa
function renderSemanticGap(entities1, entities2) {
    const set1 = new Set(entities1.map(e => e.name.toLowerCase()));
    const set2 = new Set(entities2.map(e => e.name.toLowerCase()));
    
    // Comunes
    const common = entities1.filter(e => set2.has(e.name.toLowerCase())).length;
    document.getElementById('gap-common').textContent = common;
    
    // Únicas
    const unique1 = entities1.filter(e => !set2.has(e.name.toLowerCase())).length;
    document.getElementById('gap-unique1').textContent = unique1;
    
    const unique2List = entities2.filter(e => !set1.has(e.name.toLowerCase()));
    document.getElementById('gap-unique2').textContent = unique2List.length;
    
    // Solapamiento (Jaccard Index)
    const union = new Set([...set1, ...set2]).size;
    const overlapPct = union > 0 ? Math.round((common / union) * 100) : 0;
    document.getElementById('gap-overlap-pct').textContent = `${overlapPct}%`;
    
    // Cobertura Semántica Estimada
    const totalCompetitorEntities = entities2.length;
    const semanticCoverage = totalCompetitorEntities > 0 ? Math.round((common / totalCompetitorEntities) * 100) : 100;
    
    // Actualizar anillo circular SVG
    const ring = document.getElementById('score-ring');
    const scoreText = document.getElementById('score-text');
    if (ring && scoreText) {
        const radius = 42;
        const circumference = 2 * Math.PI * radius; // 263.89
        const offset = circumference - (semanticCoverage / 100) * circumference;
        ring.style.strokeDashoffset = offset;
        scoreText.textContent = `${semanticCoverage}%`;
    }
    
    // Actualizar diagnóstico de riesgo editorial
    const riskBadge = document.getElementById('gap-risk-badge');
    const riskDesc = document.getElementById('gap-risk-desc');
    if (riskBadge && riskDesc) {
        if (semanticCoverage < 35) {
            riskBadge.textContent = 'Riesgo Crítico';
            riskBadge.style.background = 'rgba(231,76,60,0.15)';
            riskBadge.style.color = '#e74c3c';
            riskDesc.innerHTML = 'Tu artículo tiene una cobertura conceptual muy deficiente frente a este competidor. Es altamente probable que los motores de búsqueda perciban tu contenido como poco profundo o incompleto para resolver la intención de búsqueda del usuario.';
        } else if (semanticCoverage < 70) {
            riskBadge.textContent = 'Cobertura Intermedia';
            riskBadge.style.background = 'rgba(241,196,15,0.15)';
            riskBadge.style.color = '#f1c40f';
            riskDesc.innerHTML = 'Tu cobertura semántica es aceptable pero mejorable. Existen oportunidades importantes para profundizar en conceptos técnicos secundarios que tu competidor sí está analizando en detalle.';
        } else {
            riskBadge.textContent = 'Relevancia Sólida';
            riskBadge.style.background = 'rgba(46,204,113,0.15)';
            riskBadge.style.color = '#2ecc71';
            riskDesc.innerHTML = 'Excelente nivel de cobertura temático. Tu contenido cubre de forma robusta la mayoría de las entidades y conceptos relevantes del competidor. Concéntrate en la legibilidad y la experiencia del usuario.';
        }
    }
    
    // Inyectar brecha semántica (Keywords del competidor que te faltan)
    const gapBox = document.getElementById('gap-keywords-box');
    gapBox.innerHTML = '';
    
    if (unique2List.length === 0) {
        gapBox.innerHTML = '<p style="color: #2ecc71; font-weight: 700; font-size: .9rem;">¡Excelente! Cubres el 100% de las entidades detectadas en el artículo de tu competidor. No hay brecha semántica disponible.</p>';
    } else {
        unique2List.forEach(ent => {
            const badge = document.createElement('span');
            badge.className = 'gap-keyword';
            badge.textContent = `+ ${ent.name}`;
            gapBox.appendChild(badge);
        });
    }
    
    // Rellenar Tabla de Prioridad Editorial
    const tableBody = document.getElementById('editorial-table-body');
    if (tableBody) {
        tableBody.innerHTML = '';
        if (unique2List.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #2ecc71; padding: 2rem 0; font-weight: 700;">¡Ninguna brecha detectada! Tu cobertura es óptima.</td></tr>';
        } else {
            // Ordenar por frecuencia del competidor de mayor a menor
            const sortedUnique2 = [...unique2List].sort((a, b) => b.frequency - a.frequency);
            
            sortedUnique2.forEach(ent => {
                let action = '';
                let priorityBadge = '';
                
                if (ent.frequency >= 5) {
                    action = '<strong>Prioridad Alta:</strong> Crear un bloque explicativo dedicado o sección H2/H3 específica sobre este concepto.';
                    priorityBadge = `<span class="badge" style="background: rgba(231,76,60,0.1); color: #e74c3c; border: 1px solid rgba(231,76,60,0.2); font-size: .65rem; padding: .2rem .5rem;">Crítica (${ent.frequency})</span>`;
                } else if (ent.frequency >= 2) {
                    action = '<strong>Prioridad Media:</strong> Añadir ejemplos prácticos e integrar de forma natural el término en párrafos explicativos.';
                    priorityBadge = `<span class="badge" style="background: rgba(241,196,15,0.1); color: #f1c40f; border: 1px solid rgba(241,196,15,0.2); font-size: .65rem; padding: .2rem .5rem;">Importante (${ent.frequency})</span>`;
                } else {
                    action = '<strong>Prioridad Baja:</strong> Considerar su mención en oraciones de soporte para ampliar la riqueza del contexto semántico.';
                    priorityBadge = `<span class="badge" style="background: rgba(52,152,219,0.1); color: #3498db; border: 1px solid rgba(52,152,219,0.2); font-size: .65rem; padding: .2rem .5rem;">Recomendada (${ent.frequency})</span>`;
                }
                
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid rgba(255,255,255,0.04)';
                tr.innerHTML = `
                    <td style="padding: 1rem .5rem; font-weight: 700; color: #fff; text-align: left;">${ent.name}</td>
                    <td style="padding: 1rem .5rem; text-align: left;"><span class="legend-badge ${ent.type}" style="font-size: .65rem; padding: .2rem .5rem; border-radius: .3rem; display: inline-block;">${ent.type.toUpperCase()}</span></td>
                    <td style="padding: 1rem .5rem; text-align: center;">${priorityBadge}</td>
                    <td style="padding: 1rem .5rem; color: #cbd5e1; text-align: left;">${action}</td>
                `;
                tableBody.appendChild(tr);
            });
        }
    }
}

// ----------------------------------------------------
// MOTOR DE FÍSICA Y RENDERIZADO DEL GRAFO (CANVAS)
// ----------------------------------------------------
function buildGraphData(entities, triples) {
    nodes = [];
    links = [];
    
    // Centrar la vista del Grafo por defecto
    resetGraphView();
    
    // 1. Crear nodos a partir de las entidades distribuidas cerca de su centro orbital
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    entities.forEach((ent, i) => {
        let targetX = centerX;
        let targetY = centerY;
        
        // Coordenada destino por tipo para inicializar en su nube respectiva
        if (ent.type === 'org') {
            targetX = centerX - 240;
            targetY = centerY - 50;
        } else if (ent.type === 'person') {
            targetX = centerX - 60;
            targetY = centerY - 140;
        } else if (ent.type === 'tech') {
            targetX = centerX + 240;
            targetY = centerY - 50;
        } else if (ent.type === 'concept' || ent.type === 'place') {
            targetX = centerX + 60;
            targetY = centerY + 140;
        }
        
        // Disposición inicial orbital aleatoria cerca de su centro de atracción
        const angle = Math.random() * Math.PI * 2;
        const radius = 10 + Math.random() * 40;
        
        nodes.push({
            id: ent.name,
            label: ent.name,
            type: ent.type,
            weight: ent.weight,
            frequency: ent.frequency,
            radius: 12 + ent.weight * 1.5,
            x: targetX + Math.cos(angle) * radius,
            y: targetY + Math.sin(angle) * radius,
            vx: 0,
            vy: 0
        });
    });
    
    // 2. Crear aristas a partir de los triples
    triples.forEach(tr => {
        // Comprobar que ambas entidades existan en nuestros nodos activos
        const sourceNode = nodes.find(n => n.id === tr.subject);
        const targetNode = nodes.find(n => n.id === tr.object);
        
        if (sourceNode && targetNode) {
            links.push({
                source: sourceNode,
                target: targetNode,
                label: tr.predicate
            });
        }
    });
    
    // Iniciar bucle de físicas del Grafo
    startGraphSimulation();
}

function startGraphSimulation() {
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }
    
    function step() {
        updatePhysics();
        drawGraph();
        animationFrameId = requestAnimationFrame(step);
    }
    
    animationFrameId = requestAnimationFrame(step);
}

// Algoritmo Force-Directed Physics (Atracción/Repulsión/Gravedad)
function updatePhysics() {
    const kRepulsion = 2600; // Aumentado fuertemente para un mayor espaciado general
    const kAttraction = 0.04; // Hooke constante
    const kGravity = 0.015;   // Fuerza central
    const friction = 0.88;    // Rozamiento para detener oscilación
    
    // 1. Repulsión entre todos los nodos con zona de amortiguación de colisión de texto
    for (let i = 0; i < nodes.length; i++) {
        const nodeA = nodes[i];
        if (nodeA === dragNode) continue;
        
        for (let j = 0; j < nodes.length; j++) {
            if (i === j) continue;
            const nodeB = nodes[j];
            
            const dx = nodeA.x - nodeB.x;
            const dy = nodeA.y - nodeB.y;
            let dist = Math.hypot(dx, dy);
            
            if (dist < 1) dist = 1;
            
            // Colchón elástico de colisión de texto (45px de seguridad)
            const minSafeDistance = nodeA.radius + nodeB.radius + 45;
            let force = 0;
            
            if (dist < minSafeDistance) {
                // Si violan el espacio de texto seguro, se repelen con una fuerza elástica extremadamente potente
                force = (minSafeDistance - dist) * 0.95;
            } else {
                // Ley de Coulomb estándar más fuerte
                force = (kRepulsion * nodeA.weight * nodeB.weight) / (dist * dist);
            }
            
            nodeA.vx += (dx / dist) * force * 0.1;
            nodeA.vy += (dy / dist) * force * 0.1;
        }
    }
    
    // 2. Atracción entre nodos enlazados por aristas (links)
    links.forEach(link => {
        const nodeA = link.source;
        const nodeB = link.target;
        
        const dx = nodeB.x - nodeA.x;
        const dy = nodeB.y - nodeA.y;
        const dist = Math.hypot(dx, dy);
        
        if (dist < 1) return;
        
        // Ley de Hooke: fuerza proporcional al estiramiento
        const force = kAttraction * dist;
        
        const fx = (dx / dist) * force;
        const fy = (dy / dist) * force;
        
        if (nodeA !== dragNode) {
            nodeA.vx += fx;
            nodeA.vy += fy;
        }
        if (nodeB !== dragNode) {
            nodeB.vx -= fx;
            nodeB.vy -= fy;
        }
    });
    
    // 3. Gravedad hacia su centro orbital por categoría (color) para agrupar por constelaciones
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    nodes.forEach(node => {
        if (node === dragNode) return;
        
        let targetX = centerX;
        let targetY = centerY;
        
        // Asignar centros orbitales específicos por categoría
        if (node.type === 'org') {
            targetX = centerX - 240;
            targetY = centerY - 50;
        } else if (node.type === 'person') {
            targetX = centerX - 60;
            targetY = centerY - 140;
        } else if (node.type === 'tech') {
            targetX = centerX + 240;
            targetY = centerY - 50;
        } else if (node.type === 'concept' || node.type === 'place') {
            targetX = centerX + 60;
            targetY = centerY + 140;
        }
        
        const dx = targetX - node.x;
        const dy = targetY - node.y;
        
        node.vx += dx * kGravity;
        node.vy += dy * kGravity;
        
        // Aplicar velocidad, damping y fricción
        node.x += node.vx;
        node.y += node.vy;
        
        node.vx *= friction;
        node.vy *= friction;
    });
}

// Comprobar si un nodo es visible basado en los filtros interactivos
function isNodeVisible(type) {
    const showOrg = document.getElementById('filter-org') ? document.getElementById('filter-org').checked : true;
    const showPerson = document.getElementById('filter-person') ? document.getElementById('filter-person').checked : true;
    const showTech = document.getElementById('filter-tech') ? document.getElementById('filter-tech').checked : true;
    const showConcept = document.getElementById('filter-concept') ? document.getElementById('filter-concept').checked : true;
    const showPlace = document.getElementById('filter-place') ? document.getElementById('filter-place').checked : true;
    
    if (type === 'org') return showOrg;
    if (type === 'person') return showPerson;
    if (type === 'tech') return showTech;
    if (type === 'concept') return showConcept;
    if (type === 'place') return showPlace;
    return true;
}

// Handler de cambio de filtros
function triggerFilterUpdate() {
    // La simulación de físicas e inercia está activa en bucle continuo,
    // por lo que el Canvas se redibujará automáticamente en el próximo frame.
}

// Pintar el lienzo en Canvas
function drawGraph() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    ctx.save();
    ctx.translate(transform.x, transform.y);
    ctx.scale(transform.scale, transform.scale);
    
    // 1. Dibujar líneas de aristas (links)
    links.forEach(link => {
        // Ocultar arista si alguna de las dos entidades asociadas está desactivada en el filtro
        if (!isNodeVisible(link.source.type) || !isNodeVisible(link.target.type)) return;
        
        ctx.beginPath();
        ctx.moveTo(link.source.x, link.source.y);
        ctx.lineTo(link.target.x, link.target.y);
        ctx.strokeStyle = 'rgba(255,255,255,0.06)';
        ctx.lineWidth = 1.5;
        ctx.stroke();
        
        // Pintar texto del verbo conector en el centro de la línea
        const midX = (link.source.x + link.target.x) / 2;
        const midY = (link.source.y + link.target.y) / 2;
        ctx.fillStyle = '#94a3b8';
        ctx.font = 'bold 7px monospace';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(link.label.toUpperCase(), midX, midY);
    });
    
    // 2. Dibujar nodos
    nodes.forEach(node => {
        // Ocultar nodo si está desactivado en el filtro interactivo
        if (!isNodeVisible(node.type)) return;
        
        // Determinar colores premium
        let color = '#3498db'; // tech por defecto
        let glowColor = 'rgba(52,152,219,0.3)';
        
        if (node.type === 'org') {
            color = '#e8681a'; // orange corporativo
            glowColor = 'rgba(232,104,26,0.3)';
        } else if (node.type === 'person') {
            color = '#2ecc71';
            glowColor = 'rgba(46,204,113,0.3)';
        } else if (node.type === 'concept') {
            color = '#9b59b6';
            glowColor = 'rgba(155,89,182,0.3)';
        } else if (node.type === 'place') {
            color = '#f1c40f'; // amarillo oro para lugares
            glowColor = 'rgba(241,196,15,0.3)';
        }
        
        // Halo de neón difuminado en hover/drag
        if (node === dragNode) {
            ctx.shadowColor = color;
            ctx.shadowBlur = 20;
        } else {
            ctx.shadowBlur = 0;
        }
        
        // Dibujar círculo del nodo
        ctx.beginPath();
        ctx.arc(node.x, node.y, node.radius, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();
        
        // Bordes de nodo elegantes
        ctx.strokeStyle = 'rgba(255,255,255,0.15)';
        ctx.lineWidth = 1;
        ctx.stroke();
        
        // Quitar sombras para dibujar texto
        ctx.shadowBlur = 0;
        
        // Dibujar texto de la entidad
        ctx.fillStyle = '#ffffff';
        ctx.font = `bold ${Math.max(9, 8 + node.weight * 0.4)}px sans-serif`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(node.label, node.x, node.y + node.radius + 12);
    });
    
    ctx.restore();
}

// Eventos de interacción con el lienzo Canvas
function setupCanvasInteraction() {
    let hoveredNode = null;
    const tooltip = document.getElementById('graph-tooltip');
    
    canvas.addEventListener('mousedown', function(e) {
        const mouse = getCanvasMouseCoords(e);
        
        // Comprobar si se ha pulsado sobre un nodo (solo nodos visibles)
        const hit = nodes.find(node => isNodeVisible(node.type) && Math.hypot(node.x - mouse.x, node.y - mouse.y) < node.radius);
        
        if (hit) {
            dragNode = hit;
            canvas.style.cursor = 'grabbing';
        } else {
            isPanning = true;
            startPan = { x: e.clientX - transform.x, y: e.clientY - transform.y };
            canvas.style.cursor = 'grabbing';
        }
    });
    
    canvas.addEventListener('mousemove', function(e) {
        const mouse = getCanvasMouseCoords(e);
        
        if (dragNode) {
            dragNode.x = mouse.x;
            dragNode.y = mouse.y;
            dragNode.vx = 0;
            dragNode.vy = 0;
        } else if (isPanning) {
            transform.x = e.clientX - startPan.x;
            transform.y = e.clientY - startPan.y;
        } else {
            // Lógica de hover en nodos y tooltip interactivo (solo para nodos visibles)
            const hit = nodes.find(node => isNodeVisible(node.type) && Math.hypot(node.x - mouse.x, node.y - mouse.y) < node.radius);
            
            if (hit) {
                canvas.style.cursor = 'pointer';
                if (hoveredNode !== hit) {
                    hoveredNode = hit;
                    
                    // Mostrar y posicionar tooltip
                    tooltip.style.display = 'block';
                    tooltip.innerHTML = `
                        <strong>${hit.label}</strong><br/>
                        <span style="color:var(--orange)">Tipo:</span> ${hit.type.toUpperCase()}<br/>
                        <span style="color:var(--orange)">Mención:</span> ${hit.frequency} veces
                    `;
                }
                
                // Mover tooltip con el cursor
                const rect = canvas.getBoundingClientRect();
                tooltip.style.left = `${e.clientX - rect.left + 15}px`;
                tooltip.style.top = `${e.clientY - rect.top + 15}px`;
                
            } else {
                canvas.style.cursor = 'grab';
                hoveredNode = null;
                tooltip.style.display = 'none';
            }
        }
    });
    
    window.addEventListener('mouseup', function() {
        if (dragNode || isPanning) {
            dragNode = null;
            isPanning = false;
            canvas.style.cursor = 'grab';
        }
    });
    
    // Zoom con rueda
    canvas.addEventListener('wheel', function(e) {
        e.preventDefault();
        const zoomIntensity = 0.15;
        const mouse = getCanvasMouseCoords(e);
        
        const wheel = e.deltaY < 0 ? 1 : -1;
        const zoomFactor = Math.exp(wheel * zoomIntensity);
        
        // Zoom centrado en el cursor del ratón
        transform.x -= mouse.x * (zoomFactor - 1) * transform.scale;
        transform.y -= mouse.y * (zoomFactor - 1) * transform.scale;
        transform.scale *= zoomFactor;
        
        // Limitar escala de zoom
        transform.scale = Math.max(0.3, Math.min(3, transform.scale));
    });
    
    // Doble clic para re-centrar
    canvas.addEventListener('dblclick', resetGraphView);
}

function getCanvasMouseCoords(e) {
    const rect = canvas.getBoundingClientRect();
    const clientX = e.clientX - rect.left;
    const clientY = e.clientY - rect.top;
    
    // Transformar coordenadas de pantalla a coordenadas transformadas del Canvas
    return {
        x: (clientX - transform.x) / transform.scale,
        y: (clientY - transform.y) / transform.scale
    };
}

function zoomGraph(factor) {
    transform.scale *= factor;
    transform.scale = Math.max(0.3, Math.min(3, transform.scale));
}

function resetGraphView() {
    transform.scale = 0.9;
    transform.x = canvas.width * 0.05;
    transform.y = canvas.height * 0.05;
}

function resetGraphViewCenter() {
    resetGraphView();
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
