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

$page = page_config([
    'title'        => 'GMB-Web Checker: Extensión de Auditoría SEO Local',
    'description'  => 'Extensión gratuita para Chrome que audita la coherencia SEO local entre una ficha de Google Business Profile y su página web asociada.',
    'canonical'    => '/herramientas/auditor-seo-local-gmb/',
    'body_class'   => 'page-auditor-gmb',
    'schema_types' => ['SoftwareApplication', 'FAQPage'],
    'rating_id'    => 'auditor-seo-local-gmb',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Auditor GMB Extension', 'url' => ''],
    ],
    'faq_items' => [
        [
            'q' => '¿Por qué es importante la coherencia entre GMB y mi web?',
            'a' => 'Google utiliza tu sitio web como fuente de confianza para validar los datos de tu ficha de Google Business Profile (GMB). Si la dirección, el teléfono o los servicios difieren, la confianza de Google baja, afectando a tu posicionamiento en el Local Pack (mapas).'
        ],
        [
            'q' => '¿Qué páginas rastrea exactamente la extensión?',
            'a' => 'La extensión rastrea automáticamente la Home de tu sitio web y, si detecta enlaces internos, extrae también páginas clave como Contacto, Aviso Legal o páginas de Servicios específicos de tu ciudad para buscar coincidencias de teléfono y Schema Markup en profundidad.'
        ],
        [
            'q' => '¿Qué significa el Score de Coherencia?',
            'a' => 'Es una puntuación sobre 100 que evalúa tu alineación técnica local. Penaliza gravemente incongruencias como tener un teléfono distinto en la web o no tener un Schema LocalBusiness válido. Una puntuación por encima de 85 se considera óptima.'
        ],
        [
            'q' => '¿Es segura de usar? ¿Envía mis datos a algún sitio?',
            'a' => 'Totalmente segura. La extensión procesa toda la información de manera local en tu propio navegador. No utiliza servidores externos, ni bases de datos, ni APIs de terceros. Es open-source y puedes revisar todo el código en su repositorio público de GitHub.'
        ]
    ],
    'software_data' => [
        'name' => 'GMB-Web Coherence Checker',
        'operatingSystem' => 'Chrome, Edge, Brave',
        'applicationCategory' => 'BrowserExtension',
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'EUR'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="gmb-h1">
    <div class="container">
      <span class="hero-eyebrow">Auditoría SEO Local en 1 clic</span>
      <h1 id="gmb-h1">Extensión <span>GMB-Web Checker</span></h1>
      <p class="page-hero-desc">Audita la coherencia técnica entre cualquier ficha de Google Maps y su página web asociada directamente desde tu navegador.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      
      <div class="tool-intro" style="margin-bottom: 3rem;">
        <h2>Verifica el NAP y Schema en segundos</h2>
        <p>GMB-Web Checker es una extensión open-source para navegadores basados en Chromium (Google Chrome, Edge, Brave). Su objetivo es auditar en un solo clic si los datos técnicos de una ficha local en Google (Business Profile) coinciden con la información declarada en la web del negocio.</p>
      </div>

      <div class="cards-grid" style="grid-template-columns: 1fr 1fr; gap: 2.5rem; align-items: start;">
        
        <!-- Descarga y Uso -->
        <div class="card" style="padding: 2.5rem;">
          <h3 style="margin-bottom:1.5rem;color:var(--orange)">📥 Descarga e Instalación</h3>
          
          <p style="margin-bottom:1.5rem;">La extensión es de código abierto y totalmente gratuita. Al no estar (todavía) publicada en la Chrome Web Store oficial, debes instalarla en <strong>Modo Desarrollador</strong>.</p>
          
          <ol class="steps-list" style="margin-bottom: 2rem;">
            <li style="margin-bottom: 1rem;">
              <strong>1. Descarga el código fuente</strong><br>
              Entra al <a href="https://github.com/vicksWalkiria/GMB-web-checker" target="_blank" rel="noopener">repositorio público de GitHub</a>, haz clic en el botón verde <code>Code</code> y luego en <code>Download ZIP</code>.
            </li>
            <li style="margin-bottom: 1rem;">
              <strong>2. Descomprime el archivo</strong><br>
              Extrae el contenido del ZIP en una carpeta de tu ordenador.
            </li>
            <li style="margin-bottom: 1rem;">
              <strong>3. Activa el Modo Desarrollador en Chrome</strong><br>
              Escribe <code>chrome://extensions/</code> en la barra de direcciones de Chrome y activa el "Modo desarrollador" (esquina superior derecha).
            </li>
            <li style="margin-bottom: 1rem;">
              <strong>4. Carga la extensión</strong><br>
              Haz clic en el botón <strong>"Cargar descomprimida"</strong> (Load unpacked) y selecciona la carpeta donde extrajiste los archivos. ¡Listo!
            </li>
          </ol>
          
          <div style="display:flex;gap:1rem;flex-wrap:wrap">
            <a href="https://github.com/vicksWalkiria/GMB-web-checker/archive/refs/heads/master.zip" class="btn btn--primary" target="_blank" rel="noopener">
              <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:0.5rem"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
              Descargar ZIP Directo
            </a>
            <a href="https://github.com/vicksWalkiria/GMB-web-checker" class="btn btn--ghost" target="_blank" rel="noopener">
              Ver Repositorio GitHub
            </a>
          </div>
        </div>

        <!-- Captura de pantalla -->
        <div class="card" style="padding:0; overflow:hidden; border:none; background:transparent">
            <img src="/assets/img/gmb-web-checker.webp" alt="Captura de pantalla de la extensión GMB-Web Checker analizando una ficha SEO local en Google" style="width:100%; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display:block;">
        </div>

      </div>

      <!-- Funcionalidades de la extensión -->
      <div class="criterio-section" style="margin-top:4rem">
        <span class="section-label">¿Qué hace exactamente la extensión?</span>
        <h2>Motor de rastreo local incorporado</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>🤖 Mini-crawler interno inteligente</h3>
            <p>La extensión no solo audita la Home. Descarga la página principal, detecta todos los enlaces internos y <strong>puntúa y clasifica semánticamente</strong> las páginas candidatas para rastrear las más relevantes (p.ej. <code>/contacto/</code> o <code>/servicios/seo-albacete/</code>).</p>
          </div>

          <div class="criterio-card">
            <h3>🔎 Coherencia NAP estricta</h3>
            <p>Verifica si el nombre de tu negocio en Maps está respaldado semánticamente en el H1 o Title de la web. Además, extrae recursivamente todos los teléfonos visibles de la web para asegurar que el teléfono local de la ficha esté presente en el código.</p>
          </div>

          <div class="criterio-card">
            <h3>📦 Deep Schema Analysis</h3>
            <p>Detecta y parsea cualquier JSON-LD presente en la web. Comprueba la existencia de esquemas <code>LocalBusiness</code>, verifica si declaran correctamente el mismo número de teléfono que GMB, y valida si enlazan los perfiles sociales con <code>sameAs</code>.</p>
          </div>
          
          <div class="criterio-card">
            <h3>📝 Exportación a Markdown</h3>
            <p>Genera en un clic un informe profesional y trazable en formato Markdown. Te indica no solo el veredicto (Aprobado/Fallo), sino <strong>exactamente en qué URL</strong> se ha detectado el Schema Markup o la categoría local para máxima transparencia.</p>
          </div>
        </div>
      </div>

      <!-- Widget de Votación y Rich Snippet -->
      <?php render_rating_widget('auditor-seo-local-gmb'); ?>

    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿La coherencia técnica de tu web te está penalizando?',
    'subtitle'  => 'Instalar un Schema y rellenar un perfil de Google Business no es suficiente. Auditamos y unificamos tus entidades digitales para liderar el SEO Local de tu ciudad.',
    'btn_label' => 'Solicitar auditoría SEO Local',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
