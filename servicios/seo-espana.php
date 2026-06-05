<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'        => 'Consultor SEO en España para empresas',
  'description'  => 'Servicio de SEO nacional con arquitectura de información, clusters de contenido, SEO técnico, análisis de intención y medición real. Sin contenido IA genérico.',
  'canonical'    => '/servicios/seo-espana/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service'],
  'service_data' => [
      '@id'           => '/servicios/seo-espana/#service',
      'name'          => 'SEO para empresas en España',
      'alternateName' => [
          'SEO nacional',
          'Posicionamiento web en España',
          'Consultor SEO España'
      ],
      'serviceType'   => 'Consultoría SEO Nacional',
      'areaServed'    => [
          ['@type' => 'Country', 'name' => 'España']
      ]
  ],
  'active_nav'   => 'servicios',
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'SEO para España', 'url' => ''],
  ],
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">SEO para empresas en España con enfoque técnico y <span>estratégico</span></h1>
      <p class="page-hero-desc">Una web lenta, mal enlazada o con canibalizaciones puede tener buen diseño y aun así no competir. El SEO nacional empieza con un diagnóstico honesto, no con un plan de contenidos.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">
        <h2>SEO nacional: más variables, misma lógica</h2>
        <p>Trabajar SEO para proyectos con alcance nacional añade capas de complejidad: más competencia en las SERPs, arquitecturas más grandes que rastrear, intenciones de búsqueda más variadas y la necesidad de priorizar con criterio cuando el presupuesto es limitado.</p>
        <p>Mi enfoque no cambia: antes de producir contenido, reviso el estado técnico. Antes de crear páginas nuevas, analizo si las existentes funcionan. Antes de recomendar publicar, analizo qué tiene posibilidades reales de posicionar en el plazo y el sector concreto.</p>

        <h2>Qué incluye un trabajo de SEO nacional</h2>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Auditoría técnica de partida</strong> Estado de rastreo, indexación, canonicals, sitemaps, velocidad, datos estructurados y errores activos en Search Console.</div></li>
          <li class="checklist-item"><div><strong>Arquitectura de información</strong> Revisión o diseño de la estructura de URL, jerarquía de categorías, páginas pillar y páginas cluster. Una arquitectura plana y coherente reduce el presupuesto de rastreo y mejora el enlazado interno.</div></li>
          <li class="checklist-item"><div><strong>Análisis de intención de búsqueda</strong> Clasificar las keywords por intención (informacional, transaccional, navegacional) y asignar la página adecuada a cada tipo. No se puede posicionar una ficha de producto para una búsqueda informacional.</div></li>
          <li class="checklist-item"><div><strong>Estrategia de clusters de contenido</strong> Agrupar temáticamente las páginas alrededor de un topic principal. Mejora la autoridad temática percibida por Google y facilita el enlazado interno natural.</div></li>
          <li class="checklist-item"><div><strong>SEO técnico continuo</strong> Rastreo e indexación, Core Web Vitals, hreflang si aplica, paginación, filtros, facetas y control de contenido duplicado.</div></li>
          <li class="checklist-item"><div><strong>Contenido útil, no contenido IA genérico</strong> El contenido producido con IA sin revisión editorial tiene un techo bajo en Google. Prefiero menos páginas bien escritas que un sitio lleno de texto que no aporta nada que no esté ya indexado en cualquier otro sitio.</div></li>
          <li class="checklist-item"><div><strong>Migraciones</strong> Cambios de dominio, de plataforma o de estructura. Planificación de redirecciones, control de pérdida de autoridad y seguimiento post-migración en Search Console.</div></li>
          <li class="checklist-item"><div><strong>Medición</strong> Configuración correcta de GA4, conversiones, segmentos de tráfico orgánico y seguimiento de posiciones. Sin datos fiables no hay decisiones con criterio.</div></li>
        </ul>

        <h2>Lo que no hago</h2>
        <p>Para que no haya sorpresas:</p>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item">No genero contenido IA en masa sin estrategia y sin revisión.</li>
          <li class="checklist-item">No compro enlaces en redes de blogs privadas (PBN) ni en sitios de baja calidad.</li>
          <li class="checklist-item">No prometo posiciones concretas en fechas concretas.</li>
          <li class="checklist-item">No entrego auditorías de 100 páginas que nadie implementa. Entrego prioridades ejecutables.</li>
        </ul>

        <h2>Priorización: el criterio que más falta</h2>
        <p>En proyectos nacionales es habitual tener una lista de 80 acciones pendientes y no saber por dónde empezar. Uso una matriz de impacto potencial vs. esfuerzo de implementación para ordenar el trabajo según el negocio, el sector y los recursos disponibles.</p>
        <p>No todo vale lo mismo. Arreglar un problema de indexación que afecta a 200 páginas tiene más impacto que reescribir una meta description.</p>
      </div>

      <aside class="service-sidebar">
        <div class="card">
          <h3 style="color:var(--orange);margin-bottom:.75rem">¿Hablamos de tu proyecto?</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Cuéntame tu sector, tu web y tus objetivos. Te digo si tiene sentido trabajar juntos y por dónde empezaría.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Contactar</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Servicios relacionados</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/auditoria-seo/" style="color:var(--orange)">→ Auditoría SEO</a></li>
            <li><a href="/servicios/seo-tecnico/" style="color:var(--orange)">→ SEO Técnico</a></li>
            <li><a href="/servicios/seo-albacete/" style="color:var(--orange)">→ SEO en Albacete</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php
  $cta = ['title' => '¿Quieres analizar tu situación SEO?', 'subtitle' => 'Sin compromiso. Te digo qué está frenando tu posicionamiento y qué acciones tienen más sentido para tu negocio.', 'btn_label' => 'Solicitar primera valoración', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
