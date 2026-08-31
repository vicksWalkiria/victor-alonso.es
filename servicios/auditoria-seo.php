<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$faq = [
  ['q' => '¿Qué incluye exactamente una auditoría SEO técnica?', 'a' => 'Analizo los 5 pilares críticos: rastreo e indexabilidad (robots.txt, canonicals, noindex, errores 404/500), Core Web Vitals y WPO (LCP, INP, CLS), arquitectura web y canibalizaciones, análisis de logs del servidor y datos de Search Console para detectar caídas de tráfico.'],
  ['q' => '¿Cuánto tiempo se tarda en realizar la auditoría SEO técnica?', 'a' => 'El plazo habitual es de 5 a 7 días laborables. Al finalizar entrego un informe técnico priorizado por impacto económico vs esfuerzo y una sesión explicativa para resolver cualquier duda con tu equipo.'],
  ['q' => '¿Qué diferencia hay entre una auditoría automática y una profesional?', 'a' => 'Las herramientas automáticas (Semrush, Ahrefs, Sitebulb) solo generan listados de alertas sin contexto de negocio. Una auditoría técnica manual interpreta los logs reales, Search Console y el código para priorizar lo que realmente te hará ganar tráfico y ventas.'],
  ['q' => '¿Puedes implementar tú mismo las soluciones técnicas?', 'a' => 'Sí. Como ingeniero informático y desarrollador web, ofrezco la opción de implementar directamente las soluciones en WordPress, Laravel, Shopify, Prestashop o código a medida, evitando que el informe quede sin ejecutar.'],
  ['q' => '¿Qué accesos necesitas para iniciar la auditoría?', 'a' => 'Solo necesito acceso como usuario a Google Search Console, Google Analytics 4 y, si es posible, acceso temporal al panel de control o logs del servidor web.']
];

$page = page_config([
  'title'        => 'Auditoría SEO Técnica y Web: Diagnóstico Profesional | Víctor Alonso',
  'description'  => 'Auditoría SEO técnica realizada por consultor e ingeniero. Detecto y soluciono errores de rastreo, indexación, WPO y Core Web Vitals para recuperar tráfico.',
  'canonical'    => '/servicios/auditoria-seo/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service', 'FAQPage'],
  'service_data' => [
      '@id'           => '/servicios/auditoria-seo/#service',
      'name'          => 'Auditoría SEO técnica',
      'alternateName' => [
          'Auditoría SEO técnica',
          'Auditor SEO técnico',
          'Auditoría técnica SEO',
          'Auditoría SEO técnica avanzada',
          'Consultor SEO técnico'
      ],
      'serviceType'   => 'Auditoría SEO Técnica y Diagnóstico Web',
      'areaServed'    => [
          ['@type' => 'Country', 'name' => 'España']
      ],
      'offers'        => [
          'minPrice' => 350
      ]
  ],
  'active_nav'   => 'servicios',
  'faq_items'    => $faq,
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
      <h1 id="page-h1">Auditoría SEO Técnica: Diagnóstico Profundo y Plan de Acción</h1>
      <p class="page-hero-desc">El SEO útil empieza separando síntomas de causas reales. Auditoría SEO técnica integral realizada por ingeniero informático para proyectos que necesitan recuperar tráfico y solucionar problemas de indexabilidad.</p>
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

        <h2>Ficha técnica del servicio</h2>
        <div class="service-ficha" style="background: var(--bg-hover); border-radius: 12px; padding: 2rem; margin-top: 2rem; border: 1px solid var(--border);">
          <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 1rem; font-size: 0.95rem;">
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-check" style="color: var(--orange); margin-right: 0.5rem;"></i> Qué incluye exactamente:</span>
              <span style="color: var(--text);">Auditoría técnica SEO integral: detección de errores de enlazado interno, bucles de redirecciones, problemas técnicos estructurales, lentitud (WPO/Core Web Vitals), duplicidades y páginas huérfanas.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-xmark" style="color: #ef4444; margin-right: 0.5rem;"></i> Qué NO incluye:</span>
              <span style="color: var(--text);">Los arreglos y ejecuciones en el código (la implementación se puede contratar y presupuestar aparte).</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-box-open" style="color: var(--muted); margin-right: 0.5rem;"></i> Entregables:</span>
              <span style="color: var(--text);">Informe PDF ejecutivo con los detalles de los problemas detectados y la especificación técnica exacta de la solución para cada uno.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-clock" style="color: var(--muted); margin-right: 0.5rem;"></i> Duración habitual:</span>
              <span style="color: var(--text);">1 semana de trabajo intensivo.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-list-check" style="color: var(--muted); margin-right: 0.5rem;"></i> Requisitos del cliente:</span>
              <span style="color: var(--text);">No importa el tipo de web. Solo necesito accesos a Google Search Console, Google Analytics y (si es posible) al backend/servidor.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-bullseye" style="color: var(--orange); margin-right: 0.5rem;"></i> Cuándo ES MÁS NECESARIA:</span>
              <span style="color: var(--text);">Es indispensable cuando el equipo de desarrollo no ha tenido en cuenta el SEO o cuando mucha gente ha pasado por la web creando contenido y artículos a lo largo del tiempo sin una estructura lógica.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-code" style="color: var(--muted); margin-right: 0.5rem;"></i> Tecnologías / CMS compatibles:</span>
              <span style="color: var(--text);">Cualquier tecnología WEB (WordPress, Shopify, Prestashop, desarrollos a medida en React/Angular/PHP, etc.).</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-tag" style="color: var(--muted); margin-right: 0.5rem;"></i> Inversión ("Desde"):</span>
              <span style="font-size: 1.1rem; font-weight: 800; color: var(--orange);">350 €</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-handshake" style="color: var(--muted); margin-right: 0.5rem;"></i> Proceso de contratación:</span>
              <span style="color: var(--text);">Siempre trabajo solicitando un 50% por adelantado antes de empezar la auditoría, y el 50% restante a la entrega del informe final.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem;">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-arrow-right-arrow-left" style="color: var(--muted); margin-right: 0.5rem;"></i> Alternativa si no encajamos:</span>
              <span style="color: var(--text);">Si buscas un consultor SEO para cuota mensual o el presupuesto no encaja, habla conmigo igualmente. Conozco a gente del sector de confianza a la que te puedo derivar.</span>
            </li>
          </ul>
        </div>

        <h2>Preguntas frecuentes sobre la auditoría SEO técnica</h2>
        <div class="faq-list" style="margin-top:1.5rem">
          <?php foreach ($faq as $item): ?>
          <div class="faq-item">
            <button class="faq-question" aria-expanded="false">
              <?= h($item['q']) ?>
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer"><?= h($item['a']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>

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
            <li><a href="/servicios/seo-albacete/" style="color:var(--orange)">→ SEO local en Albacete</a></li>
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
