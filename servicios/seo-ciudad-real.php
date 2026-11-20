<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$faq = [
  [
    'q' => '¿Cómo mejoras la captación en Google Maps (Local Pack) para empresas en Ciudad Real capital?',
    'a' => 'Alineo las páginas transaccionales de servicio con tu ficha de Google Business Profile en Ciudad Real capital, estandarizo la coherencia NAP en directorios relevantes, despliego marcado estructurado JSON-LD específico (LocalBusiness y Service con coordenadas y áreas verificadas) y optimizo los Core Web Vitals en móvil para convertir búsquedas cercanas en llamadas y reservas.'
  ],
  [
    'q' => '¿Qué estrategia SEO funciona mejor para empresas industriales y de ingeniería en Puertollano?',
    'a' => 'En el polo industrial, petroquímico y energético de Puertollano las búsquedas tienen menor volumen masivo pero un ticket medio muy alto. Estructuro landings técnicas indexables por servicio industrial, homologación, mantenimiento o suministro B2B (evitando encerrar la información técnica en catálogos PDF mudos para Google) para captar responsables de compras y proyectos en la comarca y en toda España.'
  ],
  [
    'q' => 'Tengo una clínica, despacho o comercio en Alcázar de San Juan: ¿puedo captar clientes de toda La Mancha Centro?',
    'a' => 'Sí. Alcázar de San Juan actúa como cabecera sanitaria, ferroviaria y comercial de La Mancha Centro (Campo de Criptana, Herencia, Pedro Muñoz y Socuéllamos). Diseño una arquitectura local que consolida tu ficha principal en Google Maps para Alcázar y posiciona tus especialidades en las búsquedas comarcales sin recurrir a páginas puerta duplicadas.'
  ],
  [
    'q' => '¿Trabajas el SEO nacional y de e-commerce para bodegas, cooperativas y pymes de Tomelloso?',
    'a' => 'Sí. Para bodegas, cooperativas agroalimentarias, empresas metalmecánicas y tiendas WooCommerce o PrestaShop en Tomelloso y Valdepeñas combino SEO técnico de catálogo (control de facetas duplicadas, presupuesto de rastreo, datos estructurados Product y velocidad de carga móvil) con arquitectura transaccional para impulsar la venta directa y la distribución nacional.'
  ],
  [
    'q' => '¿Qué diferencia tu consultoría frente a una agencia SEO en Ciudad Real generalista?',
    'a' => 'Muchas agencias locales de publicidad o diseño gráfico ofrecen SEO como un complemento secundario o entregan webs lentas basadas en plantillas del Kit Digital sin tocar el servidor. Como ingeniero informático, audito el código fuente, los logs de rastreo de Googlebot, la velocidad real y la intención de búsqueda, implementando personalmente cada mejora sin intermediarios.'
  ],
  [
    'q' => '¿Cuánto cuesta contratar un consultor SEO en Ciudad Real y qué permanencia exiges?',
    'a' => 'No aplico contratos de permanencia obligatoria. Las auditorías SEO técnicas puntuales parten desde 350 € y los planes mensuales de posicionamiento local y orgánico para pymes en la provincia de Ciudad Real parten desde 250 €/mes, adaptados al volumen de URLs y a la competencia real de tu sector.'
  ]
];

$page = page_config([
  'title'        => 'Consultor y Agencia SEO en Ciudad Real · Víctor Alonso',
  'raw_title'    => true,
  'description'  => 'Consultor SEO e ingeniero informático para empresas en Ciudad Real, Alcázar, Tomelloso y Puertollano. Posicionamiento web y Google Maps sin intermediarios.',
  'canonical'    => '/servicios/seo-ciudad-real/',
  'body_class'   => 'page-servicio page-seo-ciudad-real',
  'schema_types' => ['LocalBusiness', 'Service', 'FAQPage'],
  'service_data' => [
      '@id'           => '/servicios/seo-ciudad-real/#service',
      'name'          => 'Consultor SEO y Agencia de Posicionamiento Web en Ciudad Real',
      'alternateName' => [
          'Consultor SEO Ciudad Real',
          'Agencia SEO en Ciudad Real',
          'Posicionamiento web Ciudad Real',
          'Auditoría SEO Ciudad Real'
      ],
      'serviceType'   => 'Consultoría SEO Técnico y Posicionamiento Local',
      'areaServed'    => [
          ['@type' => 'City', 'name' => 'Ciudad Real'],
          ['@type' => 'City', 'name' => 'Puertollano'],
          ['@type' => 'City', 'name' => 'Tomelloso'],
          ['@type' => 'City', 'name' => 'Alcázar de San Juan'],
          ['@type' => 'City', 'name' => 'Valdepeñas'],
          ['@type' => 'AdministrativeArea', 'name' => 'Provincia de Ciudad Real'],
          ['@type' => 'State', 'name' => 'Castilla-La Mancha']
      ],
      'offers'        => [
          'minPrice' => 250
      ]
  ],
  'active_nav'   => 'servicios',
  'faq_items'    => $faq,
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'SEO en Ciudad Real', 'url' => ''],
  ],
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">

  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Consultor SEO en Ciudad Real: Posicionamiento Web con <span>Ingeniería Directa</span></h1>
      <p class="page-hero-desc">Ayudo a empresas de Ciudad Real, Puertollano, Tomelloso, Alcázar de San Juan y Valdepeñas a captar clientes en Google y Google Maps. Sin comerciales, sin humo de agencia y ejecutando yo mismo los cambios técnicos en tu web.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">

        <h2>Por qué muchas webs de empresas en Ciudad Real no despegan en Google</h2>
        <p>En los últimos años decenas de pymes y autónomos de la provincia de Ciudad Real han estrenado web (muchas a través de bonos estandarizados o agencias generalistas de diseño) y se encuentran con el mismo escenario seis meses después: <strong>la web solo aparece cuando buscas el nombre exacto de la empresa</strong>, pero es invisible cuando un cliente busca el servicio en Ciudad Real.</p>
        <p>El motivo rara vez es falta de diseño visual. Cuando audito negocios de Castilla-La Mancha encuentro siempre los mismos frenos estructurales: constructores visuales pesados con tiempos de carga móviles superiores a 4 segundos, una única página genérica de «Servicios» intentando posicionar 8 intenciones distintas, títulos vacíos («Inicio - Nombre Empresa») y fichas de Google Business Profile desconectadas de la arquitectura web.</p>

        <h2>Qué incluye mi servicio de posicionamiento web en Ciudad Real</h2>
        <ul class="checklist" style="margin:1.25rem 0 2.25rem">
          <li class="checklist-item"><div><strong>Auditoría SEO técnica inicial sobre código y Search Console:</strong> Detección de bloqueos de indexación, páginas huérfanas, canibalizaciones, cadenas de redirecciones y cuellos de botella en Core Web Vitals (LCP, INP y CLS).</div></li>
          <li class="checklist-item"><div><strong>Arquitectura transaccional por servicio y localidad:</strong> Creación y optimización de páginas de aterrizaje específicas que responden a búsquedas comerciales reales en Ciudad Real capital y comarcas clave (La Mancha, Campo de Calatrava, Puertollano, Valdepeñas).</div></li>
          <li class="checklist-item"><div><strong>SEO Local y dominio del Local Pack (Google Maps):</strong> Optimización integral de categorías primarias/secundarias en Google Business Profile, marcado JSON-LD <code>LocalBusiness</code> validado, coherencia NAP y estrategia de captación de reseñas reales.</div></li>
          <li class="checklist-item"><div><strong>Implementación directa en WordPress, WooCommerce o PHP/Laravel:</strong> No te envío un PDF de deberes para que te pelees con tu informático. Como ingeniero informático, entro al código y aplico las optimizaciones de forma limpia y segura.</div></li>
          <li class="checklist-item"><div><strong>Medición transparente orientada a negocio:</strong> Panel claro con evolución real de impresiones comerciales, clics orgánicos, llamadas y envíos de formulario en GA4 y Google Search Console.</div></li>
        </ul>

        <h2>Agencia tradicional vs. Consultor SEO e Ingeniero Informático</h2>
        <div class="comparison-wrap">
          <table class="comparison-table">
            <thead>
              <tr>
                <th>Criterio</th>
                <th>Agencia Generalista / Kit Estándar</th>
                <th>Víctor Alonso · Ingeniería SEO</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Interlocutor</strong></td>
                <td>Comercial o gestor de cuentas sin perfil de programación</td>
                <td>Trato 100% directo con el ingeniero que audita y programa</td>
              </tr>
              <tr>
                <td><strong>Ejecución técnica</strong></td>
                <td>Plugins automáticos y constructores lentos (Elementor/Divi)</td>
                <td>Optimización real de código PHP/HTML, servidor, WPO y Schema</td>
              </tr>
              <tr>
                <td><strong>Estrategia de contenidos</strong></td>
                <td>Posts de blog genéricos con IA que no generan ventas</td>
                <td>Landings transaccionales enfocadas en captar presupuestos</td>
              </tr>
              <tr>
                <td><strong>Permanencia y tarifas</strong></td>
                <td>Contratos cerrados de 12 meses con cuotas opacas</td>
                <td>Sin permanencia · Auditoría desde 350 € · Mensual desde 250 €</td>
              </tr>
            </tbody>
          </table>
        </div>

        <h2>Sectores con mayor retorno orgánico en la provincia de Ciudad Real</h2>
        <p>En mercados provinciales la ventaja competitiva es enorme: mientras que a nivel nacional competir requiere años, en Ciudad Real una base técnica impecable permite alcanzar el <strong>Top 3 orgánico y de Google Maps</strong> en plazos muy razonables para:</p>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Clínicas médicas, fisioterapia, odontología y salud:</strong> Captación de pacientes locales en búsquedas por tratamiento y urgencia.</div></li>
          <li class="checklist-item"><div><strong>Despachos de abogados, asesorías fiscales e ingenierías:</strong> Posicionamiento por especialidad jurídica o técnica en Ciudad Real, Alcázar y Tomelloso.</div></li>
          <li class="checklist-item"><div><strong>Industria auxiliar, instaladoras, energía solar y empresas B2B:</strong> Visibilidad regional y nacional para pliegos y contratación industrial (Puertollano y polígonos de la A-43 / A-4).</div></li>
          <li class="checklist-item"><div><strong>Bodegas, cooperativas agroalimentarias y e-commerce especializado:</strong> Arquitectura de categorías WooCommerce/Prestashop para venta directa nacional sin intermediarios.</div></li>
        </ul>

        <h2>Cercanía real en Castilla-La Mancha</h2>
        <p>Opero en Castilla-La Mancha —conectado directamente con Ciudad Real, Manzanares, Tomelloso y Alcázar de San Juan por el eje de la A-43 desde mi despacho principal como <a href="/" style="color:var(--orange);text-decoration:underline;font-weight:600;">consultor SEO en Albacete</a>— atendiendo a empresas de toda la provincia. Esto te permite trabajar con un ingeniero informático sénior que conoce de primera mano el tejido empresarial manchego, con comunicación directa por WhatsApp, teléfono o videollamada y disponibilidad presencial cuando un hito estratégico lo requiere.</p>

      </div>

      <aside class="service-sidebar">
        <div class="card" style="border-color: var(--orange);">
          <h3 style="color:var(--orange);margin-bottom:.75rem">Diagnóstico gratuito en 24h</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Envíame la URL de tu negocio en Ciudad Real. Reviso Search Console, velocidad y competencia local y te doy mi valoración honesta sin compromiso.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Solicitar diagnóstico</a>
          <a href="<?= wa_url('ciudad_real') ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp" style="width:100%;justify-content:center;margin-top:.5rem">Hablar por WhatsApp</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Servicios complementarios</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/auditoria-seo/" style="color:var(--orange)">→ Auditoría SEO Técnica (desde 350 €)</a></li>
            <li><a href="/servicios/seo-espana/" style="color:var(--orange)">→ SEO Nacional e Industrial B2B</a></li>
            <li><a href="/servicios/mantenimiento-wordpress/" style="color:var(--orange)">→ Mantenimiento WordPress Empresas</a></li>
            <li><a href="/casos-reales/" style="color:var(--orange)">→ Estudios de caso reales</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <?php
  $cta = [
    'title' => '¿Quieres captar clientes en Ciudad Real desde Google?',
    'subtitle' => 'Analizo tu web y tu competencia local gratis para decirte con datos reales cuánto margen de crecimiento tienes.',
    'btn_label' => 'Pedir valoración gratuita',
    'btn_href' => '/contacto/',
    'whatsapp' => true,
    'wa_context' => 'ciudad_real',
    'variant' => 'orange'
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
