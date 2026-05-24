<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'        => 'Auditoría SEO técnica y de contenidos',
  'description'  => 'Auditoría SEO completa: estado técnico, indexación, arquitectura, contenido, WPO y datos estructurados. Entregable priorizado por impacto, no un informe de 100 páginas.',
  'canonical'    => '/servicios/auditoria-seo/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service'],
  'service_name' => 'Auditoría SEO',
  'active_nav'   => 'servicios',
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'Auditoría SEO', 'url' => ''],
  ],
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Auditoría SEO: diagnóstico real, prioridades <span>ejecutables</span></h1>
      <p class="page-hero-desc">El SEO útil empieza separando síntomas de causas. Una auditoría sin priorización clara es papel mojado.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">

        <h2>Qué es y qué no es una auditoría SEO</h2>
        <p>Una auditoría SEO no es un listado automático de errores generado por una herramienta. Eso es un rastreo. Una auditoría real implica interpretar los datos, entender el negocio y decidir qué importa y qué no.</p>
        <p>Lo que entrego no es un informe de 80 páginas lleno de screenshots. Es una lista de problemas detectados, ordenados por impacto potencial sobre el tráfico o la conversión, con una explicación de por qué importa y qué acción concreta hay que tomar.</p>

        <h2>Qué analizo en una auditoría</h2>

        <h3>1. Estado técnico</h3>
        <ul class="checklist" style="margin:.75rem 0 1.5rem">
          <li class="checklist-item">Rastreo: ¿puede Google acceder a las páginas clave? ¿Hay bloqueos en robots.txt, noindex mal aplicados o errores de servidor?</li>
          <li class="checklist-item">Indexación: cobertura en Search Console, páginas excluidas y por qué, páginas indexadas que no deberían estarlo.</li>
          <li class="checklist-item">Renderizado: ¿el contenido es visible para Googlebot o depende de JavaScript que no se renderiza correctamente?</li>
          <li class="checklist-item">Canonicals: ¿apuntan a la URL correcta? ¿Hay páginas que se canibalizan entre sí?</li>
          <li class="checklist-item">Redirecciones: cadenas de redirects, bucles, redirecciones a páginas que no existen.</li>
          <li class="checklist-item">Sitemaps: ¿están actualizados? ¿Incluyen las páginas correctas? ¿Excluyen las que no deben indexarse?</li>
          <li class="checklist-item">Datos estructurados: validación en Rich Results Test y Schema.org Validator.</li>
        </ul>

        <h3>2. Core Web Vitals y WPO</h3>
        <ul class="checklist" style="margin:.75rem 0 1.5rem">
          <li class="checklist-item">LCP (Largest Contentful Paint): tiempo hasta que el elemento principal es visible. Objetivo: menos de 2.5s.</li>
          <li class="checklist-item">INP (Interaction to Next Paint): respuesta a interacciones del usuario.</li>
          <li class="checklist-item">CLS (Cumulative Layout Shift): estabilidad visual durante la carga.</li>
          <li class="checklist-item">Cascada de carga: recursos bloqueantes, imágenes sin optimizar, JavaScript diferible.</li>
        </ul>

        <h3>3. Arquitectura de información</h3>
        <ul class="checklist" style="margin:.75rem 0 1.5rem">
          <li class="checklist-item">Estructura de URLs: coherencia, profundidad, duplicados por parámetros.</li>
          <li class="checklist-item">Enlazado interno: distribución del PageRank interno, páginas huérfanas.</li>
          <li class="checklist-item">Jerarquía de categorías y páginas de servicio.</li>
          <li class="checklist-item">Gestión de paginación y facetas en ecommerce o sitios con filtros.</li>
        </ul>

        <h3>4. Contenido</h3>
        <ul class="checklist" style="margin:.75rem 0 1.5rem">
          <li class="checklist-item">Análisis de intención: ¿cada página ataca la intención de búsqueda correcta?</li>
          <li class="checklist-item">Canibalizaciones: ¿varias páginas compiten por las mismas keywords?</li>
          <li class="checklist-item">Thin content: páginas con poco valor añadido que consumen presupuesto de rastreo.</li>
          <li class="checklist-item">Metadatos: titles duplicados, descriptions genéricas, H1 ausentes o mal jerarquizados.</li>
        </ul>

        <h3>5. Análisis de Search Console y logs</h3>
        <ul class="checklist" style="margin:.75rem 0 1.5rem">
          <li class="checklist-item">Evolución de clics, impresiones y CTR por página y query.</li>
          <li class="checklist-item">Cobertura de indexación: estado de páginas descubiertas, rastreadas, excluidas.</li>
          <li class="checklist-item">Análisis de logs del servidor cuando están disponibles: frecuencia de rastreo de Googlebot por página.</li>
        </ul>

        <h2>Entregable</h2>
        <p>El resultado de la auditoría es un documento estructurado con:</p>
        <ul class="checklist" style="margin:1rem 0 1.5rem">
          <li class="checklist-item">Listado de problemas detectados con explicación de su impacto.</li>
          <li class="checklist-item">Priorización por impacto vs. esfuerzo de implementación.</li>
          <li class="checklist-item">Acciones concretas para cada problema, no recomendaciones genéricas.</li>
          <li class="checklist-item">Sesión de revisión para resolver dudas sobre el informe.</li>
        </ul>

        <h2>Opción de implementación incluida</h2>
        <p>Si no tienes equipo técnico o tu desarrollador actual no tiene experiencia en SEO técnico, puedo implementar directamente los cambios prioritarios. Esto evita el cuello de botella habitual: el informe se hace, el cliente lo recibe, y nadie lo ejecuta.</p>

      </div>
      <aside class="service-sidebar">
        <div class="card">
          <h3 style="color:var(--orange);margin-bottom:.75rem">Pedir auditoría</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Cuéntame tu web, tu sector y tu situación actual. Te digo cómo plantearía el análisis.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Contactar</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Relacionado</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/seo-tecnico/" style="color:var(--orange)">→ SEO Técnico</a></li>
            <li><a href="/servicios/seo-albacete/" style="color:var(--orange)">→ SEO en Albacete</a></li>
            <li><a href="/casos-reales/" style="color:var(--orange)">→ Casos reales</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php
  $cta = ['title' => '¿Tu web necesita un diagnóstico?', 'subtitle' => 'Cuéntame qué está pasando. Te digo si tiene sentido hacer una auditoría o si hay acciones más urgentes primero.', 'btn_label' => 'Hablar sobre mi web', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
