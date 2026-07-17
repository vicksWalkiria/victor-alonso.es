<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$faq = [
  ['q' => '¿Qué diferencia hay entre SEO local y SEO orgánico?', 'a' => 'El SEO local trabaja la visibilidad en búsquedas con intención geográfica, como "fontanero en Albacete". Implica optimizar Google Business Profile, conseguir reseñas, citas NAP consistentes y contenidos que mencionen la localidad de forma natural. El SEO orgánico trabaja posicionamiento sin componente local. Ambos pueden coexistir en una misma estrategia.'],
  ['q' => '¿Cuánto tiempo tarda en verse resultados del SEO local?', 'a' => 'Depende del nivel de competencia, la edad del dominio y el estado de partida. En sectores poco competidos de Albacete, pueden verse mejoras en 2-3 meses con trabajo constante. En sectores más saturados, el plazo razonable es 6-12 meses. Quien te prometa resultados rápidos sin conocer tu caso no te está siendo honesto.'],
  ['q' => '¿Necesito Google Business Profile para el SEO local?', 'a' => 'Para negocios con presencia física en Albacete o que atienden a clientes locales, GBP es prácticamente obligatorio. Es el elemento más visible en las búsquedas locales y el Local Pack. Sin perfil verificado y bien optimizado, cualquier esfuerzo en tu web queda incompleto.'],
  ['q' => '¿Puedes trabajar SEO local para empresas fuera de Albacete?', 'a' => 'Sí. Trabajo SEO local para empresas en toda Castilla-La Mancha y España. El proceso es el mismo independientemente de la localidad. La diferencia está en el nivel de competencia y en el conocimiento del mercado local, que en otros territorios requiere más investigación previa.'],
  ['q' => '¿El SEO local sirve para cualquier tipo de negocio?', 'a' => 'No para todos. Tiene más sentido para negocios con clientela local: hostelería, clínicas, asesorías, fontaneros, abogados, academias, comercios físicos, talleres, etc. Para negocios 100% digitales sin componente geográfico, la estrategia es diferente.'],
];

$page = page_config([
  'title'        => 'SEO local en Albacete para empresas | Víctor Alonso',
  'description'  => 'Mejora la visibilidad local de tu empresa en Albacete con una estrategia técnica: Google Business Profile, arquitectura, contenido local y medición.',
  'canonical'    => '/servicios/seo-albacete/',
  'body_class'   => 'page-servicio page-seo-albacete',
  'schema_types' => ['LocalBusiness', 'Service', 'FAQPage'],
  'service_data' => [
      '@id'           => '/servicios/seo-albacete/#service',
      'name'          => 'Consultor SEO en Albacete',
      'alternateName' => [
          'SEO en Albacete',
          'SEO local en Albacete',
          'Posicionamiento web en Albacete'
      ],
      'serviceType'   => 'Consultoría SEO local',
      'areaServed'    => [
          ['@type' => 'City', 'name' => 'Albacete'],
          ['@type' => 'AdministrativeArea', 'name' => 'Provincia de Albacete'],
          ['@type' => 'Country', 'name' => 'España']
      ],
      'offers'        => [
          'minPrice' => 150
      ]
  ],
  'active_nav'   => 'servicios',
  'map'          => true,
  'faq_items'    => $faq,
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'SEO local en Albacete', 'url' => ''],
  ],
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">SEO local en Albacete para empresas y negocios</h1>
      <p class="page-hero-desc">Servicio orientado a negocios que dependen de clientes de su zona. El SEO local tiene reglas propias que lo diferencian de una consultoría SEO general: optimización de Google Business Profile, arquitectura de servicios locales y medición de llamadas.</p>
    </div>
  </section>

  <section class="section" aria-labelledby="intro-seo-albacete">
    <div class="container service-detail">
      <div class="service-content">
        <h2 id="intro-seo-albacete">Qué incluye el servicio</h2>
        <p>El SEO local en Albacete no es solo meter la palabra "Albacete" en el título. Trabajo con un proceso claro y entregables reales para tu empresa:</p>
        <ul class="checklist" style="margin:1.5rem 0">
          <li class="checklist-item"><div><h3>Diagnóstico local</h3> Auditoría completa de tu perfil GBP y mapa de páginas locales necesarias.</div></li>
          <li class="checklist-item"><div><h3>Arquitectura y páginas de servicio</h3> Estructura que Google puede rastrear sin problemas como explico en mi servicio de <a href="/servicios/seo-tecnico/" style="color:var(--orange)">SEO Técnico</a>.</div></li>
          <li class="checklist-item"><div><h3>Optimización de Google Business Profile</h3> Perfil verificado, con categorías correctas, horario actualizado, fotos y gestión de reseñas. (Tip: usa mi <a href="/herramientas/generador-schema-local/" style="color:var(--orange)">generador Schema Local</a> gratuito).</div></li>
          <li class="checklist-item"><div><h3>Contenido local</h3> Páginas que responden a lo que un usuario de Albacete busca realmente cuando necesita tu servicio.</div></li>
          <li class="checklist-item"><div><h3>Consistencia NAP y menciones</h3> Auditoría y corrección de Nombre, Dirección y Teléfono en directorios relevantes de Albacete.</div></li>
          <li class="checklist-item"><div><h3>Medición de llamadas y contactos</h3> Configuración de conversiones en Analytics para que sepas exactamente qué contactos genera el SEO.</div></li>
        </ul>

        <h2>Qué reviso en la web y en Google Business Profile</h2>
        <p>Cuando analizo un proyecto local en Albacete mediante una <a href="/servicios/auditoria-seo/" style="color:var(--orange)">Auditoría SEO completa</a>, reviso en este orden:</p>
        <ol style="display:grid;gap:.6rem;margin:1rem 0 1.5rem;padding-left:1.25rem;list-style:decimal;font-size:.92rem;color:var(--muted)">
          <li>Estado del perfil de Google Business Profile y categorías seleccionadas.</li>
          <li>Coherencia de NAP (nombre, dirección, teléfono) en la web, GBP y directorios.</li>
          <li>Indexación y cobertura en Google Search Console: páginas excluidas, errores de rastreo.</li>
          <li>Velocidad de carga en móvil. Core Web Vitals (LCP, INP, CLS).</li>
          <li>Arquitectura de la web: ¿hay páginas de servicio por zona? ¿Están bien enlazadas?</li>
          <li>Análisis de intención de búsqueda para las keywords locales objetivo.</li>
          <li>Canibalizaciones: ¿varias páginas compiten por las mismas keywords?</li>
          <li>Reseñas: volumen, cadencia, respuestas del propietario.</li>
          <li>Datos estructurados: LocalBusiness, BreadcrumbList, FAQPage.</li>
        </ol>

        <h2>Errores habituales que veo en webs de empresas de Albacete</h2>
        <p>No es una crítica, es una observación de lo que se repite:</p>
        <ul class="checklist" style="margin:1rem 0 1.5rem">
          <li class="checklist-item"><div>Tener una sola página de servicios genérica en lugar de páginas específicas por servicio y zona.</div></li>
          <li class="checklist-item"><div>GBP sin verificar o con información desactualizada (horario, descripción, fotos).</div></li>
          <li class="checklist-item"><div>Web en WordPress con WPO pésimo: LCP superior a 4 segundos en móvil. Esto lo soluciono directamente mediante mi servicio de <a href="/servicios/mantenimiento-wordpress/" style="color:var(--orange)">mantenimiento de WordPress</a> enfocado a rendimiento.</div></li>
          <li class="checklist-item"><div>Contenido copiado de competidores o generado con IA sin revisión, que no responde a intención real.</div></li>
          <li class="checklist-item"><div>NAP inconsistente: el teléfono en la web no coincide con el del GBP ni con el de los directorios.</div></li>
          <li class="checklist-item"><div>Ninguna estrategia de reseñas. Google interpreta pocas reseñas como señal de baja popularidad local.</div></li>
        </ul>

        <h2>Para qué negocios de Albacete tiene sentido</h2>
        <p>El SEO local es especialmente adecuado cuando:</p>
        <ul class="checklist" style="margin:1rem 0 1.5rem">
          <li class="checklist-item"><div>Tu negocio atiende a clientes en Albacete o zona de Castilla-La Mancha de forma presencial.</div></li>
          <li class="checklist-item"><div>Detectas que tu competencia aparece en Google y tú no, o apareces en posiciones bajas.</div></li>
          <li class="checklist-item"><div>Tienes una web que ya existe pero no genera contactos desde búsquedas orgánicas.</div></li>
          <li class="checklist-item"><div>Acabas de abrir un negocio y quieres construir visibilidad desde el principio bien hecha.</div></li>
          <li class="checklist-item"><div>Quieres reducir la dependencia de publicidad de pago y construir tráfico orgánico sostenible.</div></li>
        </ul>

        <h2>Cuándo NO tiene sentido contratar SEO</h2>
        <p>Prefiero ser directo para evitar perder tu tiempo y el mío:</p>
        <ul class="checklist" style="margin:1rem 0 1.5rem">
          <li class="checklist-item"><div>Si buscas resultados en 2-4 semanas. El SEO no funciona así.</div></li>
          <li class="checklist-item"><div>Si tu producto o servicio no tiene demanda de búsqueda real. El SEO no crea demanda.</div></li>
          <li class="checklist-item"><div>Si no tienes presupuesto para implementar los cambios técnicos que el diagnóstico encuentre.</div></li>
          <li class="checklist-item"><div>Si tu negocio funciona solo por boca a boca y no necesitas más volumen. Hay prioridades mejores.</div></li>
        </ul>

        <!-- Mapa -->
        <h2>Área de trabajo principal: Albacete y provincia</h2>
        <p>Trabajo de forma remota y presencial en Albacete. Para proyectos fuera de la provincia, la colaboración es 100% online.</p>
        <div class="map-wrap" style="margin:1.5rem 0">
          <div id="map" role="application" aria-label="Mapa de Albacete, zona de trabajo principal"></div>
          <noscript>
            <p class="map-fallback">Mapa no disponible sin JavaScript. Trabajamos en <strong>Albacete, Castilla-La Mancha</strong>.</p>
          </noscript>
        </div>

        <!-- FAQ -->
        <h2>Preguntas frecuentes sobre SEO en Albacete</h2>
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
        <div class="card" style="margin-bottom:1.5rem">
          <h3 style="color:var(--orange);margin-bottom:.75rem">Solicitar primera valoración</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Cuéntame qué ocurre con tu web y te respondo con un diagnóstico sin compromiso directo de <a href="/" style="color:inherit;text-decoration:underline;">Víctor Alonso, consultor SEO</a>.</p>
           <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Contactar</a>
          <a href="https://wa.me/<?= SITE_PHONE_RAW ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp" style="width:100%;justify-content:center;margin-top:.5rem">WhatsApp</a>
        </div>
        <div class="card">
          <h3 style="margin-bottom:.75rem">Otros servicios</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/auditoria-seo/" style="color:var(--orange)">→ Auditoría SEO</a></li>
            <li><a href="/servicios/seo-tecnico/" style="color:var(--orange)">→ SEO Técnico</a></li>
            <li><a href="/servicios/seo-espana/" style="color:var(--orange)">→ SEO para España</a></li>
            <li><a href="/servicios/seo-loterias-albacete/" style="color:var(--orange)">→ SEO para Loterías</a></li>
            <li><a href="/servicios/mantenimiento-wordpress/" style="color:var(--orange)">→ Mantenimiento WordPress</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php
  $cta = ['title' => '¿Quieres saber cómo está tu web en Albacete?', 'subtitle' => 'Pídeme un primer análisis. Te digo qué está frenando tu posicionamiento y qué tiene sentido hacer.', 'btn_label' => 'Solicitar diagnóstico', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<script src="/assets/js/map.js" defer></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
