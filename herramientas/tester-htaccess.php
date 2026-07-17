<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';

$page = page_config([
    'title'        => 'Tester .htaccess: RewriteRule y redirecciones 301',
    'description'  => 'Prueba RewriteRule, RewriteCond y redirecciones 301 sin tocar el servidor. Simulador .htaccess online con traza paso a paso.',
    'canonical'    => '/herramientas/tester-htaccess/',
    'body_class'   => 'page-tester-htaccess',
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'tester-htaccess',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Tester de .htaccess', 'url' => ''],
    ],
    'faq_items' => [
        [
            'q' => '¿Qué es un archivo .htaccess y para qué sirve en SEO?',
            'a' => 'El archivo .htaccess (Hypertext Access) es un fichero de configuración a nivel de directorio compatible con servidores web Apache. Para SEO es una herramienta fundamental: permite forzar el uso de HTTPS, redirigir URLs antiguas a nuevas con redirecciones 301 para no perder autoridad en migraciones, gestionar la canonicalización (con o sin www), y bloquear el rastreo de bots dañinos.'
        ],
        [
            'q' => '¿Cómo funciona este probador de .htaccess online?',
            'a' => 'Este tester funciona mediante un motor de simulación avanzado escrito en JavaScript que emula los casos más habituales del comportamiento de Apache y su módulo mod_rewrite. Al introducir tus reglas y una URL de prueba, el simulador analiza línea por línea las condiciones (RewriteCond) y reglas (RewriteRule), mostrándote de forma didáctica un desglose paso a paso de qué reglas coinciden y cuál es la URL final resultante.'
        ],
        [
            'q' => '¿Qué ventajas tiene un simulador ejecutado en el navegador frente a uno en servidor?',
            'a' => 'La principal ventaja es la inmediatez y la seguridad. Al ejecutarse 100% en tu navegador, las pruebas son instantáneas, sin esperas por peticiones al servidor. Además, es completamente seguro: si creas accidentalmente un bucle infinito en tus reglas de redirección, el simulador lo detecta y lo detiene de forma segura, evitando que tu servidor real se caiga con un error 500.'
        ],
        [
            'q' => '¿Qué significan las banderas [R=301, L] en mod_rewrite?',
            'a' => 'Son flags o banderas que alteran el comportamiento de la regla. [R=301] indica una redirección física externa con código de estado 301 (Movido Permanentemente), lo que traspasa la fuerza SEO. [L] (Last) le dice a Apache que, si esa regla coincide, no evalúe más reglas del bloque en esa iteración. [NC] (No Case) ignora mayúsculas y minúsculas en el emparejamiento.'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="tester-h1">
    <div class="container">
      <h1 id="tester-h1">Tester de .htaccess: <span>Validador de redirecciones en vivo</span></h1>
      <p class="page-hero-desc">Prueba tus reglas de redirección de Apache en tiempo real. Un motor de simulación mod_rewrite 100% en cliente con traza explicativa paso a paso en español y libre de riesgos.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">

      <!-- PRESENTACIÓN DE LA HERRAMIENTA -->
      <div class="tool-intro">
        <h2>Simulador mod_rewrite e intérprete de redirecciones</h2>
        <p>Escribe o pega el contenido de tu archivo <code>.htaccess</code> a la izquierda, introduce una URL de prueba a la derecha y analiza instantáneamente cómo se comporta el servidor.</p>
        <div class="alert alert--info" style="margin-top: 1rem; text-align: left; font-size: 0.9rem;">
          <strong>Aviso sobre las pruebas:</strong> Esta herramienta es un simulador. No reproduce todos los módulos, variables ni configuraciones complejas de Apache. Una prueba positiva aquí ayuda a validar tu lógica, pero no sustituye probar los cambios en un entorno de staging antes de subirlos a producción.
        </div>
      </div>

      <!-- WORKSPACE DEL TESTER (Unified side-by-side workspace) -->
      <div class="htaccess-panel htaccess-tester-grid" style="margin-bottom: 2rem;">
        
        <!-- PANEL IZQUIERDO: EDITOR Y RECETAS -->
        <div class="htaccess-editor-panel">
          <div class="panel-header">
            <h3><i class="fa-solid fa-code" style="color: var(--orange);"></i> Archivo .htaccess</h3>
            
            <div class="presets-container">
              <select id="htaccess-presets" class="form-input form-input--sm" style="width: auto; display: inline-block;">
                <option value="">⚡ Recetas Rápidas (Cargar plantilla)...</option>
                <option value="https">Forzar HTTP a HTTPS</option>
                <option value="www">Forzar WWW (con www)</option>
                <option value="nowww">Forzar Sin WWW (non-www)</option>
                <option value="slash">Añadir barra al final (Trailing Slash)</option>
                <option value="noslash">Eliminar barra al final</option>
                <option value="wp">Reescrituras estándar de WordPress</option>
                <option value="bots">Bloquear bots de IA (ChatGPT/Claude)</option>
                <option value="redirects">Redirecciones 301 de migración simples</option>
              </select>
            </div>
          </div>
          
          <div class="editor-wrapper">
            <div class="line-numbers" id="line-numbers">1</div>
            <textarea id="htaccess-code" class="code-editor" placeholder="# Escribe o pega tus directivas Apache .htaccess aquí...

RewriteEngine On
RewriteBase /

# Ejemplo de redirección 301:
Redirect 301 /contacto-viejo /contacto/
" spellcheck="false"></textarea>
          </div>
          <div style="margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
            <span class="text-muted" style="font-size: 0.78rem;">Prueba en cliente. Ninguna regla se guarda en el servidor.</span>
            <button type="button" id="btn-clear-code" class="wpo-btn-share" style="background: transparent; color: var(--muted); border: none; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem;">
              <i class="fa-solid fa-trash-can"></i> Limpiar editor
            </button>
          </div>
        </div>

        <!-- PANEL DERECHO: PRUEBA Y RESULTADOS -->
        <div class="htaccess-results-panel">
          <div class="panel-header">
            <h3><i class="fa-solid fa-vial" style="color: var(--orange);"></i> Escenario de Prueba</h3>
          </div>

          <form id="htaccess-test-form" class="card wpo-form-card" style="margin-bottom: 0; background: rgba(232, 104, 26, 0.02); border: 1.5px solid rgba(232, 104, 26, 0.2); box-shadow: none;"
            toolname="htaccessTester"
            tooldescription="Prueba reglas de redirección y reescritura de un archivo .htaccess de Apache simulando peticiones HTTP con User-Agent y cabeceras personalizadas."
            toolautosubmit="false">
            <div class="form-group">
              <label class="form-label" for="test-url" style="color: var(--black);">URL a probar <span>*</span></label>
              <input type="url" class="form-input" id="test-url" required value="http://victor-alonso.es/contacto-viejo" placeholder="http://tusitio.com/pagina-a-testear">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="test-ua" style="color: var(--black);">User-Agent del cliente</label>
                <select class="form-input" id="test-ua">
                  <option value="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (Chrome/120.0.0.0)">Navegador Estándar (Chrome / Desktop)</option>
                  <option value="Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36">Navegador Móvil (Chrome Mobile)</option>
                  <option value="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)">Googlebot (Bot de Google)</option>
                  <option value="Mozilla/5.0 (compatible; ChatGPT-User; +https://openai.com/gptbot)">GPTBot (OpenAI / ChatGPT)</option>
                  <option value="ClaudeBot/1.0 (+http://www.anthropic.com/claudebot)">ClaudeBot (Anthropic)</option>
                </select>
              </div>

              <div class="form-group" style="display: flex; align-items: center; padding-top: 1.5rem;">
                <label class="form-checkbox-label" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--black); font-size: 0.88rem; cursor: pointer;">
                  <input type="checkbox" id="test-files-exists" checked style="accent-color: var(--orange); width: 18px; height: 18px;">
                  Simular que existen los archivos estáticos y carpetas reales
                </label>
              </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.25rem;">
              <button type="submit" class="btn btn--primary" style="flex: 1; justify-content: center; font-weight: 700;">
                <i class="fa-solid fa-play" style="margin-right: 0.25rem;"></i> Simular Redirecciones
              </button>
              
              <button type="button" id="btn-share-test" class="btn btn--secondary" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: all 0.2s;">
                <i class="fa-solid fa-share-nodes"></i> Compartir Test
              </button>
            </div>
          </form>
        </div>

      </div>

      <!-- RESULTADO GLOBAL A ANCHO COMPLETO -->
      <div id="test-summary-box" class="htaccess-panel" style="display: none; margin-top: 2rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
          <span id="badge-status-code" class="htaccess-badge">301 Redirect</span>
          <h3 style="color: var(--black); font-size: 1.4rem; margin-top: 0.75rem; margin-bottom: 0.25rem; font-weight: 800;">Resultado de la evaluación:</h3>
          <p id="txt-redirect-desc" style="color: var(--muted); font-size: 0.95rem; margin-bottom: 1rem;">La URL original fue redirigida externamente.</p>
        </div>

        <!-- DIAGRAMA DE NODOS DE DIRECCIONAMIENTO -->
        <div class="redirection-flow">
          <div class="flow-node">
            <span class="node-label">URL Inicial</span>
            <span id="node-url-start" class="node-val">http://...</span>
          </div>
          <div id="flow-arrow-1" class="flow-arrow">
            <span id="flow-arrow-label" class="arrow-label">301</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
          <div id="flow-node-end" class="flow-node">
            <span class="node-label" id="node-end-label">URL de Salida</span>
            <span id="node-url-end" class="node-val">https://...</span>
          </div>
        </div>

        <!-- DETALLE DEL DESGLOSE PASO A PASO -->
        <div style="margin-top: 2.5rem;">
          <h4 style="color: var(--black); font-size: 1.1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 700;">
            <i class="fa-solid fa-list-check" style="color: var(--orange);"></i> Traza de Ejecución Explicativa (Paso a Paso):
          </h4>
          <div id="trace-logs-container" class="trace-logs">
            <!-- Se rellena dinámicamente -->
        </div>
      </div>
    </div>

      <!-- TABLA DE CONTENIDOS -->
      <nav class="toc" style="margin-top: 3rem; margin-bottom: 3rem; background: var(--bg); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border);">
        <h2 style="font-size: 1.1rem; margin-top: 0; margin-bottom: 1rem;">Contenidos</h2>
        <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 0.5rem; font-size: 0.95rem;">
          <li><a href="#seo-tecnico-indexacion" style="color: var(--orange); text-decoration: underline;">1. Por qué un mal .htaccess afecta al SEO</a></li>
          <li><a href="#como-funciona-mod-rewrite" style="color: var(--orange); text-decoration: underline;">2. ¿Cómo funciona el motor mod_rewrite?</a></li>
          <li><a href="#ejemplos-htaccess" style="color: var(--orange); text-decoration: underline;">3. Recetario de redirecciones útiles</a></li>
          <li><a href="#faq" style="color: var(--orange); text-decoration: underline;">4. Preguntas frecuentes</a></li>
        </ul>
      </nav>

      <!-- TEXTOS DE CRITERIO - NO COMMODITY -->
      <div id="seo-tecnico-indexacion" class="criterio-section" style="margin-top: 4.5rem; border-top: 1px solid var(--border); padding-top: 4rem;">
        <span class="section-label">SEO Técnico e Indexación</span>
        <h2>¿Por qué un mal .htaccess destruye tu SEO en segundos?</h2>
        
        <div class="criterio-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-top: 2rem;">
          <div class="criterio-card" style="background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem;">
            <h3 style="color: var(--black); margin-bottom: 0.75rem;"><span style="color:var(--orange)">01.</span> Loops de redirección infinitos</h3>
            <p style="font-size: 0.9rem; color: var(--muted); line-height: 1.6;">Ocurre cuando la regla A redirige a la URL B, y la regla B redirige a la URL A. Los buscadores y usuarios entran en un bucle sin fin que aborta la carga con un código de error de red. Para Googlebot, esto equivale a una página caída, perdiendo su posición en las SERPs rápidamente.</p>
          </div>

          <div class="criterio-card" style="background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem;">
            <h3 style="color: var(--black); margin-bottom: 0.75rem;"><span style="color:var(--orange)">02.</span> Redirecciones 302 temporales</h3>
            <p style="font-size: 0.9rem; color: var(--muted); line-height: 1.6;">Si usas la bandera <code>[R]</code> sin especificar el código (ej: <code>[R=301,L]</code>), Apache aplicará por defecto una redirección temporal 302. Aunque una redirección temporal puede ser interpretada por los buscadores con el tiempo, para un traslado definitivo o consolidación siempre deben utilizarse redirecciones permanentes (301 o 308) para traspasar eficientemente el PageRank.</p>
          </div>

          <div class="criterio-card" style="background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem;">
            <h3 style="color: var(--black); margin-bottom: 0.75rem;"><span style="color:var(--orange)">03.</span> Pérdida del Query String</h3>
            <p style="font-size: 0.9rem; color: var(--muted); line-height: 1.6;">Al reescribir URLs dinámicas, si no utilizas la bandera QSA (Query String Append), Apache desechará cualquier parámetro de seguimiento (como IDs de afiliado, UTMs de analítica o filtros de producto). Tus campañas de marketing perderán datos de atribución y los listados filtrados se romperán.</p>
          </div>
        </div>

        <div style="margin-top: 3rem; background: rgba(232, 104, 26, 0.02); border: 1px dashed rgba(232, 104, 26, 0.3); padding: 1.75rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(232, 104, 26, 0.02);">
          <h3 style="color: var(--black); font-size: 1.15rem; margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 700;">
            <i class="fa-solid fa-circle-question" style="color: var(--orange);"></i> ¿Dudas con la configuración de tu servidor o redirecciones?
          </h3>
          <p style="font-size: 0.95rem; line-height: 1.6; margin: 0; color: var(--text);">
            Si estás planificando una migración compleja de dominio o temes romper la indexación de tu web, contar con un soporte técnico especializado marca la diferencia. Como especialista en desarrollo web, puedo ayudarte a realizar una <a href="/servicios/seo-tecnico/">migraciones y redirecciones SEO</a> seguras, así como una <a href="/servicios/auditoria-seo/">auditoría SEO</a> profunda de rastreo. En <a href="/">Víctor Alonso SEO</a> implementamos los cambios directamente en tu servidor de manera segura.
          </p>
        </div>
      </div>

      <!-- EXPLICACIÓN TÉCNICA DEL FUNCIONAMIENTO -->
      <div id="como-funciona-mod-rewrite" class="criterio-section" style="margin-top: 4rem; border-top: 1px solid var(--border); padding-top: 4rem;">
        <span class="section-label">Especificaciones técnicas</span>
        <h2>¿Cómo funciona el motor de reescritura mod_rewrite?</h2>
        <div class="criterio-grid" style="grid-template-columns: 1fr 1fr; gap: 2.5rem; margin-top: 2rem;">
          <div>
            <h3 style="color: var(--orange); font-size: 1.1rem; margin-bottom: 0.5rem;">El ciclo de evaluación de Apache</h3>
            <p style="font-size: 0.92rem; color: var(--muted); line-height: 1.6;">Apache procesa las directivas secuencialmente de arriba a abajo. Cuando encuentra un bloque <code>RewriteCond</code>, los acumula en memoria. Al llegar al siguiente <code>RewriteRule</code>, primero verifica si el patrón de la URL coincide. Si coincide, evalúa las condiciones acumuladas. Si todas son ciertas (o el flag OR las conecta), aplica la sustitución y detiene el flujo si tiene la bandera <code>[L]</code>.</p>
          </div>
          <div>
            <h3 style="color: var(--orange); font-size: 1.1rem; margin-bottom: 0.5rem;">Sustitución Regex y Retro-referencias</h3>
            <p style="font-size: 0.92rem; color: var(--muted); line-height: 1.6;">El simulador soporta retro-referencias avanzadas. Los grupos capturados mediante paréntesis en la <code>RewriteRule</code> se pueden inyectar en el destino utilizando <code>$1</code>, <code>$2</code>... <code>$9</code>. Del mismo modo, los grupos capturados en la última condición coincidente se inyectan con <code>%1</code>, <code>%2</code>... <code>%9</code>, permitiendo reescrituras ultra-precisas basadas en variables como el Host.</p>
          </div>
        </div>
      </div>

      <!-- Enlazado Interno de Herramientas Relacionadas -->
      <?php require dirname(__DIR__) . '/includes/related-tools.php'; ?>

      <!-- WIDGET DE VOTACIÓN -->
      <?php render_rating_widget('tester-htaccess', '¿Te ha sido de utilidad este probador de .htaccess?'); ?>

      <!-- SECCIÓN DE EJEMPLOS ESTÁTICOS INDEXABLES (SEO Y UX) -->
      <div id="ejemplos-htaccess" class="criterio-section" style="margin-top: 4rem; border-top: 1px solid var(--border); padding-top: 4rem;">
        <span class="section-label">Recetario Útil</span>
        <h2>Ejemplos prácticos de .htaccess que puedes probar y usar</h2>
        <p style="font-size: 0.95rem; color: var(--muted); line-height: 1.6; margin-bottom: 2.5rem; max-width: 800px;">
          Aquí tienes una selección de las configuraciones y redirecciones más habituales en Apache. Haz clic en cualquiera de ellas para <strong>cargar el ejemplo directamente en el simulador</strong> y ver cómo se evalúa.
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
          
          <!-- EJEMPLO 1: HTTPS -->
          <div style="background: #ffffff; border: 1.5px solid rgba(232, 104, 26, 0.2); border-radius: 12px; padding: 2rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div>
              <h3 style="color: var(--black); margin-bottom: 0.5rem; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-lock" style="color: #10b981;"></i> Forzar tráfico HTTPS (SSL)
              </h3>
              <p style="font-size: 0.88rem; color: var(--muted); line-height: 1.6; margin-bottom: 1rem;">
                Indispensable para asegurar que toda tu web se sirve bajo una conexión cifrada SSL. Redirige de forma permanente (301) cualquier petición insegura HTTP a la versión segura HTTPS.
              </p>
              <pre style="background: rgba(232, 104, 26, 0.03); border: 1px solid rgba(232, 104, 26, 0.3); border-radius: 6px; padding: 1rem; overflow-x: auto; margin-bottom: 1.5rem;"><code style="font-family: monospace; font-size: 0.85rem; color: var(--black); line-height: 1.5; font-weight: 600;">RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]</code></pre>
            </div>
            <button type="button" class="btn btn--primary" onclick="cargarEjemplo('RewriteEngine On\nRewriteCond %{HTTPS} off\nRewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]', 'http://victor-alonso.es/quienes-somos?ref=seo')" style="font-size: 0.8rem; padding: 0.5rem 1rem; align-self: flex-start;">
              Cargar y Probar Ejemplo
            </button>
          </div>

          <!-- EJEMPLO 2: WWW to Non-WWW -->
          <div style="background: #ffffff; border: 1.5px solid rgba(232, 104, 26, 0.2); border-radius: 12px; padding: 2rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div>
              <h3 style="color: var(--black); margin-bottom: 0.5rem; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-globe" style="color: var(--orange);"></i> Forzar dominio sin WWW
              </h3>
              <p style="font-size: 0.88rem; color: var(--muted); line-height: 1.6; margin-bottom: 1rem;">
                Evita problemas de contenido duplicado consolidando tu fuerza de marca bajo una sola variante. Este ejemplo redirige de forma permanente las visitas que incluyan www. a la versión limpia sin www.
              </p>
              <pre style="background: rgba(232, 104, 26, 0.03); border: 1px solid rgba(232, 104, 26, 0.3); border-radius: 6px; padding: 1rem; overflow-x: auto; margin-bottom: 1.5rem;"><code style="font-family: monospace; font-size: 0.85rem; color: var(--black); line-height: 1.5; font-weight: 600;">RewriteEngine On
RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]</code></pre>
            </div>
            <button type="button" class="btn btn--primary" onclick="cargarEjemplo('RewriteEngine On\nRewriteCond %{HTTP_HOST} ^www\\.(.*)$ [NC]\nRewriteRule ^(.*)$ https://%1/$1 [R=301,L]', 'https://www.victor-alonso.es/blog/')" style="font-size: 0.8rem; padding: 0.5rem 1rem; align-self: flex-start;">
              Cargar y Probar Ejemplo
            </button>
          </div>

          <!-- EJEMPLO 3: Redirección de directorio -->
          <div style="background: #ffffff; border: 1.5px solid rgba(232, 104, 26, 0.2); border-radius: 12px; padding: 2rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div>
              <h3 style="color: var(--black); margin-bottom: 0.5rem; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-folder-open" style="color: #3b82f6;"></i> Redirección 301 de Categoría completa
              </h3>
              <p style="font-size: 0.88rem; color: var(--muted); line-height: 1.6; margin-bottom: 1rem;">
                Ideal para reestructuraciones de blog o e-commerce. Esta directiva captura cualquier subcarpeta o archivo dentro de un directorio antiguo y lo mueve de forma inteligente a su nueva ruta, conservando la query string de analítica.
              </p>
              <pre style="background: rgba(232, 104, 26, 0.03); border: 1px solid rgba(232, 104, 26, 0.3); border-radius: 6px; padding: 1rem; overflow-x: auto; margin-bottom: 1.5rem;"><code style="font-family: monospace; font-size: 0.85rem; color: var(--black); line-height: 1.5; font-weight: 600;">RedirectMatch 301 ^/blog/categoria/(.*)$ /articulos/$1</code></pre>
            </div>
            <button type="button" class="btn btn--primary" onclick="cargarEjemplo('RedirectMatch 301 ^/blog/categoria/(.*)$ /articulos/$1', 'http://victor-alonso.es/blog/categoria/seo-tecnico?page=2')" style="font-size: 0.8rem; padding: 0.5rem 1rem; align-self: flex-start;">
              Cargar y Probar Ejemplo
            </button>
          </div>

          <!-- EJEMPLO 4: Bloqueo de bots IA -->
          <div style="background: #ffffff; border: 1.5px solid rgba(232, 104, 26, 0.2); border-radius: 12px; padding: 2rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div>
              <h3 style="color: var(--black); margin-bottom: 0.5rem; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-robot" style="color: #ef4444;"></i> Bloquear rastreo de Bots de IA
              </h3>
              <p style="font-size: 0.88rem; color: var(--muted); line-height: 1.6; margin-bottom: 1rem;">
                Protege tus derechos de autor y ahorra recursos de tu servidor denegando explícitamente el acceso a bots agresivos de raspado de IA (como ChatGPT de OpenAI) devolviendo un código 403 Forbidden.
              </p>
              <pre style="background: rgba(232, 104, 26, 0.03); border: 1px solid rgba(232, 104, 26, 0.3); border-radius: 6px; padding: 1rem; overflow-x: auto; margin-bottom: 1.5rem;"><code style="font-family: monospace; font-size: 0.85rem; color: var(--black); line-height: 1.5; font-weight: 600;">RewriteEngine On
RewriteCond %{HTTP_USER_AGENT} (ChatGPT-User) [NC]
RewriteRule ^(.*)$ - [F,L]</code></pre>
            </div>
            <button type="button" class="btn btn--primary" onclick="cargarEjemplo('RewriteEngine On\nRewriteCond %{HTTP_USER_AGENT} (ChatGPT-User) [NC]\nRewriteRule ^(.*)$ - [F,L]', 'http://victor-alonso.es/restricted-data')" style="font-size: 0.8rem; padding: 0.5rem 1rem; align-self: flex-start;">
              Cargar y Probar Ejemplo
            </button>
          </div>

        </div>
      </div>

    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Tu migración web te da sudores fríos?',
    'subtitle'  => 'El mapeo e implementación de redirecciones 301 masivas es el paso más crítico de un rediseño o migración de dominio. Puedo encargarme de blindar tu autoridad.',
    'btn_label' => 'Quiero asesoramiento SEO Técnico',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];

  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<!-- MOTOR DE SIMULACIÓN Y LÓGICA INTERACTIVA EN JAVASCRIPT -->
<script>
/**
 * Función de escape para evitar vulnerabilidades XSS
 */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Motor de Simulación de .htaccess para Apache mod_rewrite
 * vicksWalkiria / victor-alonso.es
 */
class HtaccessSimulator {
    constructor() {
        this.maxIterations = 12; // Máximo de bucles de redirección interna
    }

    /**
     * Parsea el código de texto plano a tokens estructurados.
     */
    parseRules(codeText) {
        const lines = codeText.split('\n');
        const rules = [];
        let pendingConditions = [];
        let rewriteBase = '/';
        let rewriteEngine = false;

        for (let i = 0; i < lines.length; i++) {
            const rawLine = lines[i];
            const line = rawLine.trim();

            // Ignorar vacíos y comentarios puros
            if (line === '' || line.startsWith('#')) {
                continue;
            }

            // 1. RewriteEngine
            let match = line.match(/^RewriteEngine\s+(on|off)/i);
            if (match) {
                rewriteEngine = match[1].toLowerCase() === 'on';
                rules.push({
                    type: 'RewriteEngine',
                    value: rewriteEngine,
                    lineNum: i + 1,
                    raw: rawLine
                });
                continue;
            }

            // 2. RewriteBase
            match = line.match(/^RewriteBase\s+(\S+)/i);
            if (match) {
                rewriteBase = match[1];
                rules.push({
                    type: 'RewriteBase',
                    value: rewriteBase,
                    lineNum: i + 1,
                    raw: rawLine
                });
                continue;
            }

            // 3. RewriteCond
            // Formato: RewriteCond TestString CondPattern [Flags]
            match = line.match(/^RewriteCond\s+(\S+)\s+(\S+)(?:\s+\[(.*)\])?/i);
            if (match) {
                pendingConditions.push({
                    type: 'RewriteCond',
                    testString: match[1],
                    condPattern: match[2],
                    flagsStr: match[3] || '',
                    lineNum: i + 1,
                    raw: rawLine
                });
                continue;
            }

            // 4. RewriteRule
            // Formato: RewriteRule Pattern Substitution [Flags]
            match = line.match(/^RewriteRule\s+(\S+)\s+(\S+)(?:\s+\[(.*)\])?/i);
            if (match) {
                rules.push({
                    type: 'RewriteRule',
                    pattern: match[1],
                    substitution: match[2],
                    flagsStr: match[3] || '',
                    conditions: [...pendingConditions],
                    lineNum: i + 1,
                    raw: rawLine
                });
                pendingConditions = []; // Resetear acumulado
                continue;
            }

            // 5. Redirect
            // Formato: Redirect [status] URL-path URL
            // Status opcional: 301, 302, temp, permanent, seeother
            match = line.match(/^Redirect\s+(\d{3}|temp|permanent|seeother|gone)?\s*(\S+)\s+(\S+)/i);
            if (match) {
                let status = '302';
                const statusArg = (match[1] || '').toLowerCase();
                if (statusArg === 'permanent' || statusArg === '301') status = '301';
                else if (statusArg === 'temp' || statusArg === '302') status = '302';
                else if (statusArg === 'seeother' || statusArg === '303') status = '303';
                else if (statusArg === 'gone' || statusArg === '410') status = '410';

                rules.push({
                    type: 'Redirect',
                    status: status,
                    urlPath: match[2],
                    destination: match[3],
                    lineNum: i + 1,
                    raw: rawLine
                });
                continue;
            }

            // 6. RedirectMatch
            // Formato: RedirectMatch [status] Regex URL
            match = line.match(/^RedirectMatch\s+(\d{3}|temp|permanent|seeother|gone)?\s*(\S+)\s+(\S+)/i);
            if (match) {
                let status = '302';
                const statusArg = (match[1] || '').toLowerCase();
                if (statusArg === 'permanent' || statusArg === '301') status = '301';
                else if (statusArg === 'temp' || statusArg === '302') status = '302';
                else if (statusArg === 'seeother' || statusArg === '303') status = '303';
                else if (statusArg === 'gone' || statusArg === '410') status = '410';

                rules.push({
                    type: 'RedirectMatch',
                    status: status,
                    pattern: match[2],
                    destination: match[3],
                    lineNum: i + 1,
                    raw: rawLine
                });
                continue;
            }

            // Desconocido u otros bloques (IfModule, etc) que no interpretamos
            rules.push({
                type: 'Ignored',
                lineNum: i + 1,
                raw: rawLine
            });
        }

        return { rules, rewriteBase, rewriteEngine };
    }

    /**
     * Ejecuta el simulador sobre una URL y variables de entorno dadas.
     */
    simulate(htaccessCode, initialUrl, clientOptions) {
        const parsed = this.parseRules(htaccessCode);
        const { rules } = parsed;
        let rewriteEngine = parsed.rewriteEngine;
        let rewriteBase = parsed.rewriteBase;

        const trace = [];
        let iteration = 0;
        let finished = false;
        let currentUrl = new URL(initialUrl);

        // Variables del cliente
        const userAgent = clientOptions.userAgent || '';
        const filesExists = !!clientOptions.filesExists;

        // Estado de salida
        let finalStatusCode = 200;
        let finalActionType = 'no_match'; // 'redirect', 'rewrite', 'forbidden', 'gone', 'no_match'

        trace.push({
            type: 'init',
            message: `<strong>URL de entrada:</strong> <code>${currentUrl.href}</code>`,
            details: `Host: <code>${currentUrl.hostname}</code> | Path: <code>${currentUrl.pathname}</code> | Protocolo: <code>${currentUrl.protocol.replace(':', '')}</code>`
        });

        while (iteration < this.maxIterations && !finished) {
            iteration++;
            let rewriteAppliedThisIteration = false;

            trace.push({
                type: 'loop_start',
                message: `<strong>Iteración #${iteration}</strong> en el ciclo de reescritura interna.`
            });

            for (const r of rules) {
                if (r.type === 'RewriteEngine') {
                    rewriteEngine = r.value;
                    trace.push({
                        type: 'info',
                        line: r.lineNum,
                        raw: r.raw,
                        message: `RewriteEngine configurado en <strong>${rewriteEngine ? 'ON' : 'OFF'}</strong>.`
                    });
                    continue;
                }

                if (r.type === 'RewriteBase') {
                    rewriteBase = r.value;
                    trace.push({
                        type: 'info',
                        line: r.lineNum,
                        raw: r.raw,
                        message: `RewriteBase establecido en <code>${rewriteBase}</code>.`
                    });
                    continue;
                }

                if (r.type === 'Ignored') {
                    continue;
                }

                // ─── A. EVALUACIÓN DIRECTIVA: Redirect ─────────────────────────
                if (r.type === 'Redirect') {
                    const matchPath = r.urlPath;
                    const dest = r.destination;
                    const path = currentUrl.pathname;

                    // Redirect coincide por prefijo de ruta
                    if (path.startsWith(matchPath)) {
                        // Concatenar el sobrante de la ruta original al destino
                        const relativePart = path.substring(matchPath.length);
                        let finalDest = dest;

                        // Si el destino es absoluto, lo usamos. Si es relativo, lo hacemos absoluto
                        if (!dest.startsWith('http://') && !dest.startsWith('https://')) {
                            const cleanDest = dest.startsWith('/') ? dest : '/' + dest;
                            finalDest = `${currentUrl.protocol}//${currentUrl.hostname}${cleanDest}`;
                        }

                        // Resolver posibles URLs con barras dobles
                        if (relativePart.length > 0) {
                            if (finalDest.endsWith('/') && relativePart.startsWith('/')) {
                                finalDest += relativePart.substring(1);
                            } else if (!finalDest.endsWith('/') && !relativePart.startsWith('/')) {
                                finalDest += '/' + relativePart;
                            } else {
                                finalDest += relativePart;
                            }
                        }

                        // Resolver Query String (Redirect en Apache por defecto conserva la query string)
                        const destUrlObj = new URL(finalDest);
                        if (currentUrl.search) {
                            // Fusionar parámetros sin pisar
                            destUrlObj.search = currentUrl.search;
                        }

                        finalStatusCode = parseInt(r.status);
                        finalActionType = r.status === '410' ? 'gone' : 'redirect';
                        currentUrl = destUrlObj;
                        finished = true;

                        trace.push({
                            type: 'match_redirect',
                            line: r.lineNum,
                            raw: r.raw,
                            message: `Directiva <code>Redirect ${r.status}</code> coincidente. Path <code>${path}</code> coincide con prefijo <code>${matchPath}</code>.`,
                            details: `Redirigiendo a <code>${currentUrl.href}</code> con código <strong>${finalStatusCode}</strong>.`
                        });
                        break; // Sale del bucle de reglas
                    } else {
                        trace.push({
                            type: 'skip',
                            line: r.lineNum,
                            raw: r.raw,
                            message: `Directiva <code>Redirect</code> no coincide. Path <code>${path}</code> no empieza por <code>${matchPath}</code>.`
                        });
                    }
                    continue;
                }

                // ─── B. EVALUACIÓN DIRECTIVA: RedirectMatch ────────────────────
                if (r.type === 'RedirectMatch') {
                    const pattern = r.pattern;
                    let dest = r.destination;
                    const path = currentUrl.pathname;

                    // Convertir patrón a regex
                    try {
                        const regex = new RegExp(pattern);
                        const match = path.match(regex);

                        if (match) {
                            // Sustituir capture groups $1 a $9
                            for (let g = 1; g <= 9; g++) {
                                if (match[g] !== undefined) {
                                    dest = dest.replace(new RegExp('\\$' + g, 'g'), match[g]);
                                }
                            }

                            let finalDest = dest;
                            if (!dest.startsWith('http://') && !dest.startsWith('https://')) {
                                const cleanDest = dest.startsWith('/') ? dest : '/' + dest;
                                finalDest = `${currentUrl.protocol}//${currentUrl.hostname}${cleanDest}`;
                            }

                            const destUrlObj = new URL(finalDest);
                            if (currentUrl.search) {
                                destUrlObj.search = currentUrl.search;
                            }

                            finalStatusCode = parseInt(r.status);
                            finalActionType = r.status === '410' ? 'gone' : 'redirect';
                            currentUrl = destUrlObj;
                            finished = true;

                            trace.push({
                                type: 'match_redirect',
                                line: r.lineNum,
                                raw: r.raw,
                                message: `Directiva <code>RedirectMatch ${r.status}</code> coincidente.`,
                                details: `Coincidencia regex exitosa. Redirigiendo a <code>${currentUrl.href}</code> con código <strong>${finalStatusCode}</strong>.`
                            });
                            break;
                        } else {
                            trace.push({
                                type: 'skip',
                                line: r.lineNum,
                                raw: r.raw,
                                message: `Directiva <code>RedirectMatch</code> no coincide. Path <code>${path}</code> no casa con regex <code>${pattern}</code>.`
                            });
                        }
                    } catch (e) {
                        trace.push({
                            type: 'error',
                            line: r.lineNum,
                            raw: r.raw,
                            message: `Error de sintaxis en la expresión regular: <code>${pattern}</code>.`
                        });
                    }
                    continue;
                }

                // ─── C. EVALUACIÓN DIRECTIVA: RewriteRule ──────────────────────
                if (r.type === 'RewriteRule') {
                    if (!rewriteEngine) {
                        trace.push({
                            type: 'skip',
                            line: r.lineNum,
                            raw: r.raw,
                            message: `RewriteRule ignorada porque <code>RewriteEngine</code> está apagado.`
                        });
                        continue;
                    }

                    // Apache mod_rewrite en .htaccess compara contra el path sin barra inicial
                    let matchPath = currentUrl.pathname;
                    if (matchPath.startsWith('/')) {
                        matchPath = matchPath.substring(1);
                    }

                    let rulePattern = r.pattern;
                    let isNegated = false;

                    if (rulePattern.startsWith('!')) {
                        isNegated = true;
                        rulePattern = rulePattern.substring(1);
                    }

                    // Interpretar flags de la regla
                    const flags = this.parseFlags(r.flagsStr);
                    const isCaseInsensitive = !!flags.NC;

                    let regexFlags = '';
                    if (isCaseInsensitive) regexFlags += 'i';

                    try {
                        const regex = new RegExp(rulePattern, regexFlags);
                        const ruleMatch = matchPath.match(regex);
                        const isMatchSuccess = isNegated ? !ruleMatch : !!ruleMatch;

                        if (isMatchSuccess) {
                            trace.push({
                                type: 'match_start',
                                line: r.lineNum,
                                raw: r.raw,
                                message: `Coincidencia de patrón: <code>${r.pattern}</code> casa con <code>${matchPath}</code>.`
                            });

                            // Evaluar las condiciones RewriteCond asociadas
                            let condsMet = true;
                            let lastCondMatchGroups = [];

                            for (const cond of r.conditions) {
                                // Reemplazar variables de servidor en el string de prueba
                                const evaluatedTestString = this.evaluateServerVariables(cond.testString, currentUrl, userAgent, filesExists);
                                
                                let condPattern = cond.condPattern;
                                let isCondNegated = false;
                                if (condPattern.startsWith('!')) {
                                    isCondNegated = true;
                                    condPattern = condPattern.substring(1);
                                }

                                const condFlags = this.parseFlags(cond.flagsStr);
                                const isCondNC = !!condFlags.NC;

                                let condRegexFlags = '';
                                if (isCondNC) condRegexFlags += 'i';

                                let currentCondMet = false;

                                // Procesar operadores de coincidencia especiales de Apache
                                if (condPattern === '-f') {
                                    // Simulación de archivo físico existente
                                    // Si simula que existen archivos, o si el path parece un archivo de assets estáticos
                                    const isAsset = /\.(css|js|png|jpg|jpeg|gif|webp|svg|ico|pdf|xml|txt)$/i.test(currentUrl.pathname);
                                    currentCondMet = filesExists || isAsset;
                                } else if (condPattern === '-d') {
                                    // Simulación de directorio existente
                                    currentCondMet = filesExists || currentUrl.pathname.endsWith('/');
                                } else {
                                    // Búsqueda regex normal
                                    try {
                                        const condRegex = new RegExp(condPattern, condRegexFlags);
                                        const condMatch = evaluatedTestString.match(condRegex);
                                        currentCondMet = isCondNegated ? !condMatch : !!condMatch;
                                        
                                        if (currentCondMet && condMatch) {
                                            lastCondMatchGroups = condMatch;
                                        }
                                    } catch (e) {
                                        trace.push({
                                            type: 'error',
                                            line: cond.lineNum,
                                            raw: cond.raw,
                                            message: `Error de sintaxis en regex de condición: <code>${condPattern}</code>.`
                                        });
                                        currentCondMet = false;
                                    }
                                }

                                if (isCondNegated && (condPattern === '-f' || condPattern === '-d')) {
                                    currentCondMet = !currentCondMet;
                                }

                                trace.push({
                                    type: currentCondMet ? 'cond_ok' : 'cond_fail',
                                    line: cond.lineNum,
                                    raw: cond.raw,
                                    message: `Evaluando Condición: <code>${evaluatedTestString}</code> frente a <code>${cond.condPattern}</code> $\\rightarrow$ <strong>${currentCondMet ? 'COINCIDE' : 'NO COINCIDE'}</strong>.`
                                });

                                // Control de operador OR (Si es OR y coincide, o si es normal y no coincide)
                                // De forma simplificada para el simulador:
                                if (!currentCondMet) {
                                    // Si no coincide y no tiene flag OR, rompemos el bloque
                                    if (!condFlags.OR) {
                                        condsMet = false;
                                        break;
                                    }
                                } else {
                                    // Si coincide y tiene flag OR, podemos dar el bloque cond por válido (o continuar si quedan no-ORs)
                                    if (condFlags.OR) {
                                        // Avanzamos marcando que el OR se ha cumplido
                                        condsMet = true;
                                    }
                                }
                            }

                            if (condsMet) {
                                // Aplicar la reescritura
                                let sub = r.substitution;

                                if (sub === '-') {
                                    // El flag '-' indica que no hay sustitución (pasa de largo, útil para flags o reescrituras vacías)
                                    trace.push({
                                        type: 'info',
                                        line: r.lineNum,
                                        raw: r.raw,
                                        message: `Sustitución indicada con '<code>-</code>' (sin cambios de URL).`
                                    });

                                    rewriteAppliedThisIteration = true;

                                    if (flags.F) {
                                        finalStatusCode = 403;
                                        finalActionType = 'forbidden';
                                        finished = true;
                                        trace.push({
                                            type: 'rewrite_stop',
                                            line: r.lineNum,
                                            raw: r.raw,
                                            message: `Regla emparejada con bandera <strong>[F] (Forbidden)</strong>. Deteniendo flujo con estado <strong>403 Forbidden</strong>.`
                                        });
                                        break;
                                    }

                                    if (flags.G) {
                                        finalStatusCode = 410;
                                        finalActionType = 'gone';
                                        finished = true;
                                        trace.push({
                                            type: 'rewrite_stop',
                                            line: r.lineNum,
                                            raw: r.raw,
                                            message: `Regla emparejada con bandera <strong>[G] (Gone)</strong>. Deteniendo flujo con estado <strong>410 Gone</strong>.`
                                        });
                                        break;
                                    }

                                    if (flags.L) {
                                        trace.push({
                                            type: 'info',
                                            line: r.lineNum,
                                            message: `Bandera <strong>[L] (Last)</strong> detectada con sustitución vacía. Finalizando evaluación en esta iteración.`
                                        });
                                        break;
                                    }
                                } else {
                                    // 1. Inyectar variables de captura de la regla ($1 a $9)
                                    if (ruleMatch) {
                                        for (let g = 1; g <= 9; g++) {
                                            if (ruleMatch[g] !== undefined) {
                                                sub = sub.replace(new RegExp('\\$' + g, 'g'), ruleMatch[g]);
                                            }
                                        }
                                    }

                                    // 2. Inyectar variables de captura de condiciones (%1 a %9)
                                    if (lastCondMatchGroups) {
                                        for (let g = 1; g <= 9; g++) {
                                            if (lastCondMatchGroups[g] !== undefined) {
                                                sub = sub.replace(new RegExp('\\%' + g, 'g'), lastCondMatchGroups[g]);
                                            }
                                        }
                                    }

                                    // 3. Evaluar variables de servidor en la sustitución (ej: %{HTTP_HOST})
                                    sub = this.evaluateServerVariables(sub, currentUrl, userAgent, filesExists);
                                    let newUrlStr = sub;

                                    // Banderas de terminación rápida de acceso
                                    if (flags.F) {
                                        finalStatusCode = 403;
                                        finalActionType = 'forbidden';
                                        finished = true;
                                        trace.push({
                                            type: 'rewrite_stop',
                                            line: r.lineNum,
                                            raw: r.raw,
                                            message: `Regla emparejada con bandera <strong>[F] (Forbidden)</strong>. Deteniendo flujo con estado <strong>403 Forbidden</strong>.`
                                        });
                                        break;
                                    }

                                    if (flags.G) {
                                        finalStatusCode = 410;
                                        finalActionType = 'gone';
                                        finished = true;
                                        trace.push({
                                            type: 'rewrite_stop',
                                            line: r.lineNum,
                                            raw: r.raw,
                                            message: `Regla emparejada con bandera <strong>[G] (Gone)</strong>. Deteniendo flujo con estado <strong>410 Gone</strong>.`
                                        });
                                        break;
                                    }

                                    // Resolver URL relativa o absoluta
                                    let isAbsoluteRedirect = sub.startsWith('http://') || sub.startsWith('https://');
                                    let targetUrlObj;

                                    if (isAbsoluteRedirect) {
                                        targetUrlObj = new URL(newUrlStr);
                                    } else {
                                        // Asegurar barra al principio
                                        let cleanPath = newUrlStr;
                                        if (!cleanPath.startsWith('/')) {
                                            // Aplicar el RewriteBase
                                            let base = rewriteBase.endsWith('/') ? rewriteBase : rewriteBase + '/';
                                            cleanPath = base + cleanPath;
                                        }
                                        // Limpiar posibles barras dobles al inicio
                                        cleanPath = cleanPath.replace(/\/+/g, '/');
                                        targetUrlObj = new URL(`${currentUrl.protocol}//${currentUrl.hostname}${cleanPath}`);
                                    }

                                    // Gestionar Query String y bandera QSA
                                    if (flags.QSA) {
                                        // Fusionar query strings
                                        if (currentUrl.search) {
                                            const originalParams = new URLSearchParams(currentUrl.search);
                                            const newParams = new URLSearchParams(targetUrlObj.search);
                                            
                                            // Combinar
                                            for (const [key, value] of originalParams.entries()) {
                                                newParams.append(key, value);
                                            }
                                            targetUrlObj.search = newParams.toString();
                                        }
                                    } else {
                                        // Si la sustitución tiene su propia query string, se queda.
                                        // Si no tiene, por comportamiento estándar de Apache, se arrastra la original.
                                        if (!targetUrlObj.search && currentUrl.search) {
                                            targetUrlObj.search = currentUrl.search;
                                        }
                                    }

                                    currentUrl = targetUrlObj;
                                    rewriteAppliedThisIteration = true;

                                    // Evaluar tipo de redirección o reescritura interna
                                    if (flags.R) {
                                        finalStatusCode = typeof flags.R === 'number' ? flags.R : 302;
                                        finalActionType = 'redirect';
                                        
                                        // Redirección externa para el simulador rompe flujo e inicia siguiente iteración
                                        trace.push({
                                            type: 'rewrite_redirect',
                                            line: r.lineNum,
                                            raw: r.raw,
                                            message: `Redirección Externa coincidente <strong>[R=${finalStatusCode}]</strong>. URL modificada a: <code>${currentUrl.href}</code>.`
                                        });

                                        if (flags.L) {
                                            trace.push({
                                                type: 'info',
                                                line: r.lineNum,
                                                message: `Bandera <strong>[L] (Last)</strong> detectada. Rompiendo esta iteración.`
                                            });
                                        }
                                        break; // Inicia siguiente loop o termina
                                    } else {
                                        finalActionType = 'rewrite';
                                        trace.push({
                                            type: 'rewrite_internal',
                                            line: r.lineNum,
                                            raw: r.raw,
                                            message: `Reescritura Interna (silenciosa) aplicada. Ruta reescrita a: <code>${currentUrl.pathname}${currentUrl.search}</code>.`
                                        });

                                        if (flags.L) {
                                            trace.push({
                                                type: 'info',
                                                line: r.lineNum,
                                                message: `Bandera <strong>[L] (Last)</strong> detectada. Finalizando evaluación en esta iteración.`
                                            });
                                            break; // Rompe el for para forzar nueva iteración interna
                                        }
                                    }
                                }
                            } else {
                                trace.push({
                                    type: 'info',
                                    line: r.lineNum,
                                    message: `RewriteRule no aplicada debido a que las condiciones <strong>RewriteCond</strong> no se cumplieron.`
                                });
                            }
                        } else {
                            trace.push({
                                type: 'skip',
                                line: r.lineNum,
                                raw: r.raw,
                                message: `RewriteRule no coincide con el path actual <code>${matchPath}</code>.`
                            });
                        }
                    } catch (e) {
                        trace.push({
                            type: 'error',
                            line: r.lineNum,
                            raw: r.raw,
                            message: `Error de expresión regular en RewriteRule: <code>${rulePattern}</code>.`
                        });
                    }
                }
            }

            // Si es un redirect externo real, terminamos
            if (finalActionType === 'redirect') {
                finished = true;
            }

            // Si en esta iteración no hemos aplicado ningún cambio en absoluto, paramos para evitar bucles inútiles
            if (!rewriteAppliedThisIteration && finalActionType !== 'redirect') {
                finished = true;
                trace.push({
                    type: 'loop_end',
                    message: `Iteración finalizada. Ninguna regla de reescritura modificó la URL en esta ronda. Ciclo terminado.`
                });
            }
        }

        // Detección de bucle infinito (error de Apache 500)
        if (iteration >= this.maxIterations && !finished) {
            finalStatusCode = 500;
            finalActionType = 'loop_error';
            trace.push({
                type: 'error_loop',
                message: `<strong>¡ERROR 500! Bucle de redirección detectado.</strong> El motor de Apache ha alcanzado el límite de reescrituras internas seguidas (${this.maxIterations}). Comprueba que tus reglas no redirijan de forma circular.`
            });
        }

        return {
            finalUrl: currentUrl.href,
            statusCode: finalStatusCode,
            actionType: finalActionType,
            trace: trace
        };
    }

    /**
     * Parsea las banderas encerradas entre corchetes, ej: [R=301,L,NC]
     */
    parseFlags(flagsStr) {
        const flags = {};
        if (!flagsStr) return flags;

        const parts = flagsStr.split(',');
        for (const p of parts) {
            const trimmed = p.trim().toUpperCase();
            if (trimmed.startsWith('R=')) {
                flags.R = parseInt(trimmed.substring(2)) || 302;
            } else if (trimmed === 'R') {
                flags.R = 302;
            } else if (trimmed === 'L') {
                flags.L = true;
            } else if (trimmed === 'NC') {
                flags.NC = true;
            } else if (trimmed === 'QSA') {
                flags.QSA = true;
            } else if (trimmed === 'F') {
                flags.F = true;
            } else if (trimmed === 'G') {
                flags.G = true;
            } else if (trimmed === 'OR') {
                flags.OR = true;
            }
        }
        return flags;
    }

    /**
     * Evalúa las variables de servidor al estilo Apache %{VARIABLE}
     */
    evaluateServerVariables(testString, urlObj, userAgent, filesExists) {
        let result = testString;

        const variables = {
            'HTTP_HOST': urlObj.hostname,
            'HTTPS': urlObj.protocol === 'https:' ? 'on' : 'off',
            'REQUEST_URI': urlObj.pathname,
            'QUERY_STRING': urlObj.search ? urlObj.search.substring(1) : '',
            'HTTP_USER_AGENT': userAgent,
            'REQUEST_FILENAME': urlObj.pathname,
            'THE_REQUEST': `GET ${urlObj.pathname}${urlObj.search} HTTP/1.1`
        };

        for (const [key, val] of Object.entries(variables)) {
            const regex = new RegExp('%\\{' + key + '\\}', 'g');
            result = result.replace(regex, val);
        }

        return result;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// ACCIONES INTERACTIVAS Y PRESSETS DE RECETAS RÁPIDAS
// ─────────────────────────────────────────────────────────────────────────────
const presets = {
    https: `RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]`,

    www: `RewriteEngine On
RewriteCond %{HTTP_HOST} !^www\\. [NC]
RewriteRule ^(.*)$ https://www.%{HTTP_HOST}/$1 [R=301,L]`,

    nowww: `RewriteEngine On
RewriteCond %{HTTP_HOST} ^www\\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]`,

    slash: `RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_URI} !(.*)/$
RewriteRule ^(.*)$ /$1/ [R=301,L]`,

    noslash: `RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} (.*)/$
RewriteRule ^(.*?)/$ /$1 [R=301,L]`,

    wp: `# BEGIN WordPress
RewriteEngine On
RewriteBase /
RewriteRule ^index\\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
# END WordPress`,

    bots: `RewriteEngine On
RewriteCond %{HTTP_USER_AGENT} (ChatGPT-User|GPTBot|ClaudeBot) [NC]
RewriteRule ^(.*)$ - [F,L]`,

    redirects: `# Redirecciones 301 clásicas
Redirect 301 /quienes-somos /sobre-mi/
Redirect 301 /servicios/seo-local /servicios/seo-albacete/

# Redirección con patrones de regex
RedirectMatch 301 ^/blog/categoria/(.*)$ /articulos/$1`
};

document.addEventListener('DOMContentLoaded', function() {
    const codeArea = document.getElementById('htaccess-code');
    const lineNumbers = document.getElementById('line-numbers');
    const presetsSelect = document.getElementById('htaccess-presets');
    const clearBtn = document.getElementById('btn-clear-code');
    const shareBtn = document.getElementById('btn-share-test');
    const form = document.getElementById('htaccess-test-form');
    const summaryBox = document.getElementById('test-summary-box');

    // Helper para tracking de Google Analytics (GA4) y GTM
    function trackEvent(eventName, params) {
        if (typeof window.gtag === 'function') {
            window.gtag('event', eventName, params);
        } else if (typeof window.dataLayer !== 'undefined' && Array.isArray(window.dataLayer)) {
            window.dataLayer.push({
                event: eventName,
                ...params
            });
        }
    }

    // 1. Sincronizar números de línea en el editor
    function updateLineNumbers() {
        const linesCount = codeArea.value.split('\n').length;
        let linesHtml = '';
        for (let i = 1; i <= linesCount; i++) {
            linesHtml += `<div>${i}</div>`;
        }
        lineNumbers.innerHTML = linesHtml;
    }

    codeArea.addEventListener('input', updateLineNumbers);
    codeArea.addEventListener('scroll', function() {
        lineNumbers.scrollTop = codeArea.scrollTop;
    });

    updateLineNumbers();

    // 1b. Función global para cargar ejemplos interactivos desde la sección SEO
    window.cargarEjemplo = function(codigo, url, nombreEjemplo) {
        codeArea.value = codigo;
        updateLineNumbers();
        if (url) {
            document.getElementById('test-url').value = url;
        }

        let inferredName = nombreEjemplo;
        if (!inferredName) {
            if (codigo.includes('HTTPS')) inferredName = 'Forzar HTTPS';
            else if (codigo.includes('HTTP_HOST')) inferredName = 'Sin WWW';
            else if (codigo.includes('RedirectMatch')) inferredName = 'RedirectMatch';
            else if (codigo.includes('ChatGPT')) inferredName = 'Bloquear Bots IA';
            else inferredName = 'Ejemplo Genérico';
        }

        // Evento Analytics
        trackEvent('htaccess_load_example', {
            example_title: inferredName
        });

        // Desplazamiento suave hasta el editor
        codeArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Ejecución automática inmediata tras breve delay para la transición visual
        setTimeout(() => {
            form.querySelector('button[type="submit"]').click();
        }, 500);
    };

    // 2. Carga de presets
    presetsSelect.addEventListener('change', function() {
        const val = this.value;
        if (val && presets[val]) {
            codeArea.value = presets[val];
            updateLineNumbers();
            
            // Evento Analytics
            trackEvent('htaccess_load_preset', {
                preset_name: val
            });

            // Ejecutar simulación automática al cambiar receta
            triggerSimulation();
        }
        this.value = ''; // Resetear
    });

    // 3. Limpiar código
    clearBtn.addEventListener('click', function() {
        codeArea.value = '';
        updateLineNumbers();
        summaryBox.style.display = 'none';
        
        // Evento Analytics
        trackEvent('htaccess_clear_editor', {});

        codeArea.focus();
    });

    // 4. Compartir prueba (Codificación URL)
    shareBtn.addEventListener('click', function() {
        const code = btoa(unescape(encodeURIComponent(codeArea.value)));
        const url = encodeURIComponent(document.getElementById('test-url').value);
        const ua = document.getElementById('test-ua').selectedIndex;
        const fe = document.getElementById('test-files-exists').checked ? '1' : '0';

        const shareUrl = `${window.location.origin}${window.location.pathname}?c=${code}&u=${url}&ua=${ua}&fe=${fe}`;

        // Evento Analytics
        trackEvent('htaccess_share_test', {
            rules_count: codeArea.value.split('\n').length
        });

        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('¡Enlace de prueba copiado al portapapeles! Envíalo a un compañero para compartir exactamente este test.');
        }).catch(err => {
            console.error('Error al copiar:', err);
        });
    });

    // 5. Cargar datos si vienen en la URL
    function loadFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const cParam = params.get('c');
        const uParam = params.get('u');
        const uaParam = params.get('ua');
        const feParam = params.get('fe');

        if (cParam) {
            try {
                codeArea.value = decodeURIComponent(escape(atob(cParam)));
                updateLineNumbers();
            } catch (e) {
                console.error('Error al decodificar código htaccess');
            }
        }

        if (uParam) {
            document.getElementById('test-url').value = decodeURIComponent(uParam);
        }

        if (uaParam) {
            document.getElementById('test-ua').selectedIndex = parseInt(uaParam) || 0;
        }

        if (feParam) {
            document.getElementById('test-files-exists').checked = feParam === '1';
        }

        if (cParam || uParam) {
            triggerSimulation();
        }
    }

    // 6. Lanzar Simulación
    function triggerSimulation() {
        const htaccessText = codeArea.value;
        const testUrlVal = document.getElementById('test-url').value.trim();
        const userAgentVal = document.getElementById('test-ua').value;
        const filesExistsVal = document.getElementById('test-files-exists').checked;

        if (!testUrlVal) return;

        // Limpiar URL por si no tiene protocolo
        let normalizedUrl = testUrlVal;
        if (!/^https?:\/\//i.test(normalizedUrl)) {
            normalizedUrl = 'http://' + normalizedUrl;
        }

        try {
            new URL(normalizedUrl);
        } catch (e) {
            alert('Por favor introduce una URL válida para realizar la simulación.');
            return;
        }

        // Evento Analytics
        trackEvent('htaccess_simulate', {
            test_url: normalizedUrl,
            ua_type: userAgentVal.substring(0, 50),
            files_exist: filesExistsVal ? 1 : 0,
            rules_count: htaccessText.split('\n').length
        });

        const simulator = new HtaccessSimulator();
        const res = simulator.simulate(htaccessText, normalizedUrl, {
            userAgent: userAgentVal,
            filesExists: filesExistsVal
        });

        renderResults(res, normalizedUrl);
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        triggerSimulation();
    });

    // Renderizar resultados en la UI
    function renderResults(res, startUrl) {
        const badge = document.getElementById('badge-status-code');
        const desc = document.getElementById('txt-redirect-desc');
        const nodeStart = document.getElementById('node-url-start');
        const nodeEnd = document.getElementById('node-url-end');
        const nodeEndLabel = document.getElementById('node-end-label');
        const arrow = document.getElementById('flow-arrow-1');
        const arrowLabel = document.getElementById('flow-arrow-label');
        const traceContainer = document.getElementById('trace-logs-container');

        // Configurar badges según el estado
        badge.className = 'htaccess-badge';
        nodeEndLabel.innerText = 'URL de Salida';

        if (res.actionType === 'redirect') {
            badge.innerText = `${res.statusCode} Redirect`;
            badge.classList.add('badge--redirect');
            desc.innerText = 'La URL fue redirigida correctamente a una dirección externa o reescrita con cambio visual.';
            arrow.style.display = 'flex';
            arrowLabel.innerText = res.statusCode;
            nodeEnd.style.display = 'block';
            nodeEnd.innerText = res.finalUrl;
        } else if (res.actionType === 'rewrite') {
            badge.innerText = '200 OK (Rewrite Interno)';
            badge.classList.add('badge--rewrite');
            desc.innerText = 'Se aplicó una reescritura interna y silenciosa. La URL del navegador no cambia, pero el servidor carga otra ruta.';
            arrow.style.display = 'flex';
            arrowLabel.innerText = 'Internal';
            nodeEnd.style.display = 'block';
            nodeEnd.innerText = res.finalUrl.replace(new URL(startUrl).origin, ''); // Mostrar sólo el path relativo para denotar interna
            nodeEndLabel.innerText = 'Ruta Interna Servida';
        } else if (res.actionType === 'forbidden') {
            badge.innerText = '403 Forbidden';
            badge.classList.add('badge--danger');
            desc.innerText = 'El acceso a este recurso fue explícitamente bloqueado y denegado por el servidor.';
            arrow.style.display = 'none';
            nodeEnd.style.display = 'none';
        } else if (res.actionType === 'gone') {
            badge.innerText = '410 Gone';
            badge.classList.add('badge--danger');
            desc.innerText = 'El recurso solicitado ha sido marcado como "Desaparecido Permanentemente". Google lo desindexará rápidamente.';
            arrow.style.display = 'none';
            nodeEnd.style.display = 'none';
        } else if (res.actionType === 'loop_error') {
            badge.innerText = '500 Internal Error';
            badge.classList.add('badge--danger');
            desc.innerText = 'Bucle de redirecciones infinitas. El servidor detiene el procesamiento para no colapsar.';
            arrow.style.display = 'none';
            nodeEnd.style.display = 'none';
        } else {
            badge.innerText = '200 OK';
            badge.classList.add('badge--ok');
            desc.innerText = 'Ninguna regla afectó a la URL. Se sirve el recurso original directamente.';
            arrow.style.display = 'none';
            nodeEnd.style.display = 'none';
        }

        // Nodos
        nodeStart.innerText = startUrl;

        // Renderizar trazas de logs
        let traceHtml = '';
        res.trace.forEach(t => {
            let lineIndicator = t.line ? `<span class="trace-line-num">Línea ${t.line}</span>` : '';
            let rawCode = t.raw ? `<pre class="trace-raw"><code>${escapeHtml(t.raw.trim())}</code></pre>` : '';
            let icon = '<i class="fa-solid fa-circle-info text-muted"></i>';
            let itemClass = '';

            if (t.type === 'init' || t.type === 'loop_start') {
                icon = '<i class="fa-solid fa-play text-muted"></i>';
                itemClass = 'trace-item--init';
            } else if (t.type === 'match_start' || t.type === 'cond_ok') {
                icon = '<i class="fa-solid fa-circle-check" style="color: #10b981;"></i>';
                itemClass = 'trace-item--success';
            } else if (t.type === 'cond_fail' || t.type === 'skip') {
                icon = '<i class="fa-solid fa-circle-xmark text-muted"></i>';
                itemClass = 'trace-item--skip';
            } else if (t.type === 'rewrite_redirect' || t.type === 'rewrite_internal' || t.type === 'match_redirect') {
                icon = '<i class="fa-solid fa-circle-right" style="color: var(--orange);"></i>';
                itemClass = 'trace-item--rewrite';
            } else if (t.type.startsWith('error') || t.type === 'rewrite_stop') {
                icon = '<i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i>';
                itemClass = 'trace-item--error';
            }

            // Sanitización de trazas contra inyección de HTML malicioso (XSS)
            let cleanMessage = t.message || '';
            let cleanDetails = t.details || '';

            cleanMessage = cleanMessage
                .replace(/<code>(.*?)<\/code>/gi, (m, p1) => `<code>${escapeHtml(p1)}</code>`)
                .replace(/<strong>(.*?)<\/strong>/gi, (m, p1) => `<strong>${escapeHtml(p1)}</strong>`);

            cleanDetails = cleanDetails
                .replace(/<code>(.*?)<\/code>/gi, (m, p1) => `<code>${escapeHtml(p1)}</code>`)
                .replace(/<strong>(.*?)<\/strong>/gi, (m, p1) => `<strong>${escapeHtml(p1)}</strong>`);

            traceHtml += `
            <div class="trace-item ${itemClass}">
                <div class="trace-meta">
                    ${icon}
                    ${lineIndicator}
                </div>
                <div class="trace-content">
                    <p class="trace-msg">${cleanMessage}</p>
                    ${rawCode}
                    ${cleanDetails ? `<p class="trace-details">${cleanDetails}</p>` : ''}
                </div>
            </div>`;
        });

        traceContainer.innerHTML = traceHtml;
        summaryBox.style.display = 'block';

        // Hacer scroll automático suave al resultado
        summaryBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    loadFromUrl();
});
</script>

<style>
/* Estilos premium para el Validador de .htaccess */
.htaccess-tester-grid {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 2rem;
    align-items: start;
    margin-bottom: 3rem;
}

.htaccess-panel {
    background: #ffffff;
    border: 1.5px solid rgba(232, 104, 26, 0.2);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(232, 104, 26, 0.04);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border);
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
}

.panel-header h3 {
    margin: 0;
    font-size: 1.15rem;
    color: var(--black);
    font-weight: 700;
}

/* Editor de Código Estilizado */
.editor-wrapper {
    display: flex;
    background: #f8fafc;
    border: 1.5px solid rgba(232, 104, 26, 0.25);
    border-radius: 8px;
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.9rem;
    position: relative;
    overflow: hidden;
    transition: all 0.2s ease;
}

.editor-wrapper:focus-within {
    border-color: var(--orange);
    box-shadow: 0 0 0 3px rgba(232, 104, 26, 0.12);
}

.line-numbers {
    padding: 1rem 0.5rem 1rem 0.75rem;
    background: rgba(232, 104, 26, 0.03);
    color: rgba(232, 104, 26, 0.45);
    border-right: 1px solid rgba(232, 104, 26, 0.15);
    text-align: right;
    user-select: none;
    line-height: 1.5;
    min-width: 32px;
    overflow: hidden;
}

.code-editor {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--black);
    padding: 1rem;
    line-height: 1.5;
    resize: vertical;
    min-height: 380px;
    font-family: inherit;
    font-size: inherit;
    outline: none;
    overflow-y: auto;
    white-space: pre;
    tab-size: 4;
    caret-color: var(--orange);
}

.code-editor::placeholder {
    color: #94a3b8;
}

/* Formulario */
.form-checkbox-label input {
    margin: 0;
}

/* Badges de Estado */
.htaccess-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.badge--redirect {
    background: rgba(232, 104, 26, 0.15);
    border: 1px solid rgba(232, 104, 26, 0.4);
    color: var(--orange);
}
.badge--rewrite {
    background: rgba(52, 152, 219, 0.15);
    border: 1px solid rgba(52, 152, 219, 0.4);
    color: #3498db;
}
.badge--ok {
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.4);
    color: #10b981;
}
.badge--danger {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.4);
    color: #f87171;
}

/* Diagrama de Flujo */
.redirection-flow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    background: rgba(232, 104, 26, 0.02);
    border: 1px dashed rgba(232, 104, 26, 0.25);
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.flow-node {
    flex: 1;
    background: #ffffff;
    border: 1.5px solid rgba(232, 104, 26, 0.2);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    max-width: 260px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}

.node-label {
    display: block;
    font-size: 0.72rem;
    color: var(--muted);
    text-transform: uppercase;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.node-val {
    display: block;
    font-size: 0.82rem;
    color: var(--black);
    word-break: break-all;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
}

.flow-arrow {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: var(--orange);
    font-size: 1.2rem;
}

.arrow-label {
    font-size: 0.75rem;
    font-weight: 700;
    background: rgba(232, 104, 26, 0.1);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    margin-bottom: 0.25rem;
}

/* Trazas de Logs */
.trace-logs {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: #ffffff;
    border: 1.5px solid rgba(232, 104, 26, 0.25);
    border-radius: 8px;
    padding: 1.25rem;
    max-height: 480px;
    overflow-y: auto;
    box-shadow: 0 4px 15px rgba(0,0,0,0.02);
}

.trace-item {
    display: flex;
    gap: 1rem;
    border-bottom: 1px solid rgba(232, 104, 26, 0.08);
    padding-bottom: 0.75rem;
}

.trace-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.trace-meta {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 68px;
}

.trace-meta i {
    font-size: 1.1rem;
    margin-bottom: 0.4rem;
}

.trace-line-num {
    font-size: 0.68rem;
    color: var(--orange);
    background: rgba(232, 104, 26, 0.08);
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
    font-weight: 600;
}

.trace-content {
    flex: 1;
}

.trace-msg {
    font-size: 0.88rem;
    color: var(--black);
    margin: 0 0 0.35rem;
    line-height: 1.45;
}

.trace-raw {
    background: rgba(232, 104, 26, 0.03);
    border: 1px solid rgba(232, 104, 26, 0.15);
    border-radius: 4px;
    padding: 0.35rem 0.6rem;
    margin: 0.4rem 0;
    overflow-x: auto;
}

.trace-raw code {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.8rem;
    color: var(--black);
    font-weight: 600;
}

.trace-details {
    font-size: 0.8rem;
    color: var(--muted);
    margin: 0.25rem 0 0;
}

/* Modificadores de color para trazas */
.trace-item--init { border-left: 3px solid #334155; padding-left: 0.75rem; }
.trace-item--success { border-left: 3px solid #10b981; padding-left: 0.75rem; }
.trace-item--rewrite { border-left: 3px solid var(--orange); padding-left: 0.75rem; }
.trace-item--skip { border-left: 3px solid #1e293b; padding-left: 0.75rem; opacity: 0.7; }
.trace-item--error { border-left: 3px solid #ef4444; padding-left: 0.75rem; }

@media (max-width: 991px) {
    .htaccess-tester-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
