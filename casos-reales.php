<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

$page = page_config([
  'title'        => 'Casos de estudio SEO y problemas resueltos',
  'description'  => 'Descubre cómo he solucionado penalizaciones, problemas de migración y arquitectura técnica en proyectos reales, devolviendo la visibilidad SEO.',
  'canonical'    => '/casos-reales/',
  'body_class'   => 'page-casos',
  'schema_types' => [],
  'active_nav'   => 'casos',
  'breadcrumbs'  => [
    ['label' => 'Casos reales', 'url' => ''],
  ],
]);
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumbs.php';
?>
<main id="main">

  <section class="page-hero" aria-labelledby="casos-h1">
    <div class="container">
      <h1 id="casos-h1">Casos reales: problemas que he resuelto y <span>qué se puede aprender</span> de cada uno</h1>
      <p class="page-hero-desc">Proyectos anonimizados con el contexto, el problema, el diagnóstico y las acciones tomadas. Casos reales de <a href="/" style="color:var(--orange)">posicionamiento web en Albacete</a> y toda España. Sin porcentajes inventados ni resultados inflados.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <p style="color:var(--muted);font-size:.9rem;margin-bottom:3rem;max-width:640px">
        <strong>Nota:</strong> Los casos están anonimizados para proteger la privacidad de los clientes. No se incluyen cifras de tráfico concretas ni resultados garantizados. Cada caso es diferente.
      </p>

      <div class="cases-grid">

        <!-- Caso 1 -->
        <article class="case-card" aria-labelledby="caso-1">
          <div class="case-card-head">
            <h2 id="caso-1">WordPress infectado con malware y penalizado por Google</h2>
            <p>Sector: servicios profesionales · Tipo de sitio: WordPress con WooCommerce</p>
          </div>
          <div class="case-card-body">
            <div class="case-detail">
              <h3>Contexto</h3>
              <p>Un cliente llega con la web mostrando advertencias de Chrome. Google Safe Browsing la había marcado como sitio peligroso. Las ventas en la tienda online habían caído a cero en pocas horas.</p>
            </div>
            <div class="case-detail">
              <h3>Problema</h3>
              <p>Código malicioso inyectado en varios archivos PHP del tema y en la base de datos. El hackeo había ocurrido semanas antes sin que nadie lo detectara, porque el malware era silencioso y solo redirigía a los usuarios que llegaban desde Google.</p>
            </div>
            <div class="case-detail">
              <h3>Diagnóstico</h3>
              <p>Revisión completa de archivos del servidor, comparación con copias de seguridad y análisis de logs de acceso. Se identificaron tres puntos de entrada: un plugin desactualizado con vulnerabilidad conocida, una instalación de WordPress antigua y credenciales débiles en el panel de hosting.</p>
            </div>
            <div class="case-detail">
              <h3>Acciones</h3>
              <p>Limpieza completa de archivos infectados, saneamiento de la base de datos, actualización a la última versión de WordPress y plugins, cambio de credenciales y solicitud de revisión a Google Safe Browsing. La web quedó limpia en 48 horas.</p>
            </div>
            <div class="case-detail" style="grid-column:1/-1">
              <h3>Qué aprender</h3>
              <p>Un WordPress sin actualizar y sin backups es una bomba de relojería. No es cuestión de si pasa, sino de cuándo. Si tu web ha sido hackeada, actúa rápido y solicita una <a href="/servicios/reparacion-wordpress-urgente/" style="text-decoration:underline">reparación urgente</a>. Si aún estás a tiempo, recuerda que el coste de un <a href="/servicios/mantenimiento-wordpress/" style="text-decoration:underline">mantenimiento preventivo</a> es siempre menor que el coste y el impacto en negocio de una recuperación de emergencia.</p>
            </div>
          </div>
        </article>

        <!-- Caso 2 -->
        <article class="case-card" aria-labelledby="caso-2">
          <div class="case-card-head">
            <h2 id="caso-2">Caída de tráfico tras migración de dominio sin redirecciones</h2>
            <p>Sector: retail local · Tipo de sitio: WordPress</p>
          </div>
          <div class="case-card-body">
            <div class="case-detail">
              <h3>Contexto</h3>
              <p>Una empresa cambia de dominio sin contar con asesoramiento técnico. La agencia encargada del nuevo diseño configuró el DNS y dio la web por terminada. Nadie había planificado las redirecciones del dominio antiguo.</p>
            </div>
            <div class="case-detail">
              <h3>Problema</h3>
              <p>En menos de dos semanas, el tráfico orgánico había caído de forma drástica. El dominio antiguo seguía teniendo toda la autoridad acumulada, pero ahora era inaccesible. El nuevo dominio partía de cero en Google.</p>
            </div>
            <div class="case-detail">
              <h3>Diagnóstico</h3>
              <p>Análisis del dominio antiguo en Ahrefs y Search Console. Inventario de URLs con tráfico y backlinks en el dominio anterior. Revisión del estado de las redirecciones existentes: había algunas configuradas incorrectamente con redirect 302 en lugar de 301.</p>
            </div>
            <div class="case-detail">
              <h3>Acciones</h3>
              <p>Mapeamiento completo de URLs antiguas a nuevas, implementación de redirecciones 301 para todas las URLs con tráfico histórico, corrección de los errores en Search Console y monitorización semanal de la cobertura de indexación durante los meses siguientes.</p>
            </div>
            <div class="case-detail" style="grid-column:1/-1">
              <h3>Qué aprender</h3>
              <p>Una migración de dominio es una operación quirúrgica. Los errores se pagan durante meses. Si tu agencia de diseño no menciona las redirecciones antes de lanzar, pregúntalo explícitamente.</p>
            </div>
          </div>
        </article>

        <!-- Caso 3 -->
        <article class="case-card" aria-labelledby="caso-3">
          <div class="case-card-head">
            <h2 id="caso-3">Web con LCP de 7 segundos en móvil</h2>
            <p>Sector: hostelería · Tipo de sitio: WordPress con Elementor</p>
          </div>
          <div class="case-card-body">
            <div class="case-detail">
              <h3>Contexto</h3>
              <p>Un restaurante tenía una web visualmente atractiva pero con un rendimiento pésimo en móvil. PageSpeed Insights marcaba un LCP de 7 segundos y un CLS elevado.</p>
            </div>
            <div class="case-detail">
              <h3>Diagnóstico</h3>
              <p>Análisis de la cascada de carga: imagen hero sin optimizar (3 MB en JPEG), CSS de Elementor y del tema cargando de forma bloqueante, 6 fuentes de Google Fonts cargadas síncronamente, 4 plugins de redes sociales inyectando scripts externos en cada página.</p>
            </div>
            <div class="case-detail">
              <h3>Acciones</h3>
              <p>Conversión de imágenes críticas a WebP con dimensiones correctas, implementación de preload para el LCP, diferimiento de CSS no crítico, carga asíncrona de fuentes, eliminación de plugins de redes sociales innecesarios y configuración correcta de caché.</p>
            </div>
            <div class="case-detail" style="grid-column:1/-1">
              <h3>Qué aprender</h3>
              <p>El rendimiento web no se arregla con un plugin de caché. Hay que analizar qué carga, en qué orden y por qué. Un diseño bonito con un rendimiento pésimo perjudica tanto la experiencia de usuario como el posicionamiento. Si quieres evitar que tu web se degrade con el tiempo, la optimización WPO debe integrarse en un <a href="/servicios/mantenimiento-wordpress/" style="text-decoration:underline">mantenimiento técnico continuo</a>.</p>
            </div>
          </div>
        </article>

        <!-- Caso 4 -->
        <article class="case-card" aria-labelledby="caso-4">
          <div class="case-card-head">
            <h2 id="caso-4">Caída de tráfico tras actualización de Google</h2>
            <p>Sector: servicios profesionales · Tipo de sitio: WordPress</p>
          </div>
          <div class="case-card-body">
            <div class="case-detail">
              <h3>Contexto</h3>
              <p>Una web de servicios que había mantenido posiciones estables durante meses pierde entre el 30-40% del tráfico orgánico en pocos días coincidiendo con un broad core update de Google.</p>
            </div>
            <div class="case-detail">
              <h3>Diagnóstico</h3>
              <p>Análisis de qué páginas habían perdido tráfico e impresiones en Search Console. La mayoría eran páginas de servicio con contenido genérico que no respondían bien a la intención de búsqueda. También había problemas de canibalización: varias páginas atacando las mismas keywords con contenido muy similar.</p>
            </div>
            <div class="case-detail">
              <h3>Acciones</h3>
              <p>Consolidación de páginas canibalizadas, reescritura de contenido enfocada en intención de búsqueda real, mejora de la estructura de los textos con jerarquía correcta de headings, y trabajo de enlazado interno para reforzar las páginas prioritarias.</p>
            </div>
            <div class="case-detail" style="grid-column:1/-1">
              <h3>Qué aprender</h3>
              <p>Los broad core updates de Google no se "combaten". Revelan qué páginas tenían posiciones frágiles. Si el contenido no aportaba valor real antes del update, el update solo aceleró lo que iba a pasar de todas formas.</p>
            </div>
          </div>
        </article>

        <!-- Caso 5 -->
        <article class="case-card" aria-labelledby="caso-5">
          <div class="case-card-head">
            <h2 id="caso-5">Proyecto local sin arquitectura de información</h2>
            <p>Sector: servicios locales · Tipo de sitio: WordPress</p>
          </div>
          <div class="case-card-body">
            <div class="case-detail">
              <h3>Contexto</h3>
              <p>Un negocio local lleva dos años publicando contenido en el blog sin ver resultados. La web tiene muchas entradas pero ninguna página de servicio bien desarrollada.</p>
            </div>
            <div class="case-detail">
              <h3>Diagnóstico</h3>
              <p>Sin arquitectura de información clara, el contenido del blog estaba atacando keywords sin intención transaccional mientras las páginas de servicio eran prácticamente invisibles. Los artículos del blog tenían más visibilidad en Google que las páginas que generaban contactos.</p>
            </div>
            <div class="case-detail">
              <h3>Acciones</h3>
              <p>Diseño de la arquitectura correcta: páginas de servicio como pilares, artículos del blog como satélites enlazando hacia ellas, actualización del enlazado interno y mejora del contenido de las páginas de servicio con intención transaccional clara.</p>
            </div>
            <div class="case-detail" style="grid-column:1/-1">
              <h3>Qué aprender</h3>
              <p>El blog no es la estrategia SEO. Es una parte de ella. Publicar artículos sin tener una arquitectura clara de qué páginas quieres posicionar es construir sin cimientos.</p>
            </div>
          </div>
        </article>

        <!-- Caso 6 -->
        <article class="case-card" aria-labelledby="caso-6">
          <div class="case-card-head">
            <h2 id="caso-6">Ecommerce con miles de páginas duplicadas no intencionadas</h2>
            <p>Sector: ecommerce · Tipo de sitio: WooCommerce</p>
          </div>
          <div class="case-card-body">
            <div class="case-detail">
              <h3>Contexto</h3>
              <p>Una tienda online con catálogo medio-grande tenía un rendimiento SEO muy por debajo de sus competidores a pesar de tener un dominio con cierta antigüedad y algunos backlinks.</p>
            </div>
            <div class="case-detail">
              <h3>Diagnóstico</h3>
              <p>La combinación de filtros de WooCommerce con parámetros de URL estaba generando miles de URLs duplicadas que Google estaba indexando. El presupuesto de rastreo se consumía en URLs sin valor y las páginas de producto relevantes recibían pocas visitas de Googlebot.</p>
            </div>
            <div class="case-detail">
              <h3>Acciones</h3>
              <p>Análisis de qué parámetros de URL generaban duplicados, gestión del rastreo mediante robots.txt para reducir peticiones ineficientes, implementación de canonicals correctos en las páginas de categoría con filtros y limpieza del sitemap para que solo incluyera URLs indexables.</p>
            </div>
            <div class="case-detail" style="grid-column:1/-1">
              <h3>Qué aprender</h3>
              <p>En ecommerce, el problema técnico más común no es el contenido sino la gestión del presupuesto de rastreo. Miles de URLs duplicadas diluyen la atención de Googlebot y pueden hacer que tus páginas importantes se rastreen con menor frecuencia.</p>
            </div>
          </div>
        </article>

      </div>
    </div>
  </section>

  <?php
  $cta = ['title' => '¿Tu caso se parece a alguno de estos?', 'subtitle' => 'Cuéntame qué está pasando con tu web. Identificar bien el problema es el primer paso.', 'btn_label' => 'Hablar sobre mi situación', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require __DIR__ . '/includes/cta.php';
  ?>

</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
