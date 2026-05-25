<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'        => 'SEO Técnico: Indexación, Rastreo y WPO',
  'description'  => 'Servicio de SEO técnico: análisis de rastreo, indexación, renderizado, Core Web Vitals, canonicals, datos estructurados, JS SEO y migraciones web.',
  'canonical'    => '/servicios/seo-tecnico/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service'],
  'service_name' => 'SEO Técnico',
  'active_nav'   => 'servicios',
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'SEO Técnico', 'url' => ''],
  ],
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">SEO Técnico: lo que Google necesita para <span>rastrear, entender e indexar</span> tu web</h1>
      <p class="page-hero-desc">El SEO técnico no es solo velocidad. Es todo lo que facilita o dificulta que Google descubra, procese y posicione tu contenido correctamente.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">

        <h2>Por qué el SEO técnico importa antes que el contenido</h2>
        <p>Publicar contenido nuevo en una web con problemas técnicos es como abrir un negocio con la entrada bloqueada. Google puede ignorar páginas si no puede rastrearlas, no las indexa si las señales son contradictorias, y no las posiciona bien si el rendimiento es pésimo.</p>
        <p>Antes de recomendar más artículos o más páginas de servicio, reviso si la base técnica está sana.</p>

        <h2>Áreas de trabajo en SEO técnico</h2>

        <h3>Rastreo y presupuesto de rastreo</h3>
        <p>Googlebot no tiene tiempo ilimitado para rastrear tu web. Analizo cómo gasta ese presupuesto: páginas irrelevantes que consume, páginas clave que no visita, frecuencia de rastreo por tipo de URL. El objetivo es que Googlebot dedique su tiempo a las páginas que importan.</p>

        <h3>Indexación</h3>
        <p>Reviso la cobertura de Search Console y el estado de cada tipo de página: indexadas correctamente, excluidas por noindex, descubiertas pero no rastreadas, o bloqueadas en robots.txt. Hay webs donde el 40% de las páginas indexadas no deberían estarlo.</p>

        <h3>Renderizado y JavaScript SEO</h3>
        <p>Si tu web usa React, Vue, Angular o cualquier framework con renderizado del lado del cliente, parte del contenido puede ser invisible para Googlebot. Analizo qué ve Google realmente frente a lo que ve el usuario usando la herramienta de inspección de URLs de Search Console.</p>

        <h3>Core Web Vitals</h3>
        <ul class="checklist" style="margin:.75rem 0 1.5rem">
          <li class="checklist-item"><div><strong>LCP (Largest Contentful Paint)</strong> Identifico el elemento LCP, analizo por qué tarda en cargar y propongo correcciones: preload, compresión, CDN o mejoras de servidor.</div></li>
          <li class="checklist-item"><div><strong>INP (Interaction to Next Paint)</strong> Analizo el main thread y el JavaScript que bloquea la respuesta a interacciones del usuario.</div></li>
          <li class="checklist-item"><div><strong>CLS (Cumulative Layout Shift)</strong> Detecto elementos sin dimensiones definidas, iframes, anuncios o fuentes que provocan saltos de layout.</div></li>
        </ul>

        <h3>Arquitectura y enlazado interno</h3>
        <p>La estructura de la web determina cómo fluye la autoridad entre páginas y qué señales recibe Google sobre qué es más importante. Analizo la profundidad de las páginas clave, las páginas huérfanas sin enlazar y la distribución del PageRank interno.</p>

        <h3>Canonicals y contenido duplicado</h3>
        <p>Los canonicals mal configurados confunden a Google sobre cuál es la versión principal de una página. Analizo conflictos entre canonical, noindex, hreflang y parámetros de URL que pueden generar duplicados no intencionados.</p>

        <h3>Redirecciones</h3>
        <p>Cadenas de redirecciones (A→B→C), bucles de redirección y redirects a páginas 404 son problemas que consumen presupuesto de rastreo y diluyen la autoridad de los enlaces entrantes. Mapeamiento completo y corrección.</p>

        <h3>Sitemaps</h3>
        <p>Un sitemap mal mantenido incluye URLs con noindex, páginas 404 o URLs con parámetros. Un sitemap correcto es una guía de prioridades para Googlebot, no un volcado automático de todas las URLs del CMS.</p>

        <h3>Datos estructurados</h3>
        <p>Implementación y validación de schemas en JSON-LD: Product, Article, FAQPage, BreadcrumbList, LocalBusiness, HowTo, Event y otros según el tipo de negocio. Los rich results mejoran el CTR aunque no posicionen más alto.</p>

        <h3>Migraciones web</h3>
        <p>Los cambios de dominio, de plataforma o de estructura URL son momentos de alto riesgo para el SEO. Una migración mal planificada puede suponer meses de recuperación. Planifico el mapeamiento de redirecciones, controlo el proceso en Search Console y hago seguimiento post-migración.</p>

        <h3>Facetas y paginación en ecommerce</h3>
        <p>Las páginas de filtros generan miles de URLs duplicadas o de bajo valor. Analizo qué facetas deben indexarse, cuáles deben bloquearse en robots.txt y cómo implementar la paginación sin perder autoridad.</p>

      </div>
      <aside class="service-sidebar">
        <div class="card">
          <h3 style="color:var(--orange);margin-bottom:.75rem">Diagnóstico técnico</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Cuéntame tu situación. Te digo por dónde empezaría.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Contactar</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Relacionado</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/auditoria-seo/" style="color:var(--orange)">→ Auditoría SEO</a></li>
            <li><a href="/servicios/desarrollo-wordpress/" style="color:var(--orange)">→ Desarrollo WordPress</a></li>
            <li><a href="/servicios/mantenimiento-wordpress/" style="color:var(--orange)">→ Mantenimiento WordPress</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php
  $cta = ['title' => '¿Tu web tiene problemas técnicos que frenan el posicionamiento?', 'subtitle' => 'Cuéntame qué está pasando. Te hago una valoración inicial sin compromiso.', 'btn_label' => 'Hablar sobre mi web', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
