<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'        => 'Desarrollo WordPress y Temas a Medida',
  'description'  => 'Desarrollo web WordPress enfocado a conversión y SEO. Arquitecturas ligeras, seguras y optimizadas para máxima velocidad de carga.',
  'canonical'    => '/servicios/desarrollo-wordpress/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service'],
  'service_data' => [
      '@id'           => '/servicios/desarrollo-wordpress/#service',
      'name'          => 'Desarrollo WordPress',
      'alternateName' => [
          'Desarrollo WordPress a medida',
          'Programador WordPress',
          'Desarrollo web WordPress'
      ],
      'serviceType'   => 'Desarrollo Web',
      'areaServed'    => [
          ['@type' => 'Country', 'name' => 'España']
      ]
  ],
  'active_nav'   => 'servicios',
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'Desarrollo WordPress', 'url' => ''],
  ],
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Desarrollo WordPress técnico: sin plantillas genéricas, sin <span>código innecesario</span></h1>
      <p class="page-hero-desc">La mayoría de los problemas de rendimiento y SEO en WordPress vienen del código, no del contenido. Un desarrollo técnico sólido es la base que todo lo demás necesita.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">

        <h2>Qué puede significar "desarrollo WordPress" y qué significa para mí</h2>
        <p>Hay muchas formas de "hacer una web en WordPress": instalar un page builder, comprar una plantilla de Themeforest y rellenar. El resultado es una web que funciona aceptablemente bien durante 6 meses y empieza a tener problemas cuando crece.</p>
        <p>Mi enfoque es diferente: desarrollo con código PHP propio, HTML semántico, CSS organizado y JavaScript mínimo. Sin bloat de page builders, sin dependencias innecesarias, sin código que nadie sabe qué hace.</p>

        <h2>Qué desarrollo</h2>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Temas hijos y temas a medida</strong> Desarrollo en PHP puro con la jerarquía de plantillas de WordPress. Cada template tiene un propósito, no hay código duplicado ni lógica de negocio mezclada con la vista.</div></li>
          <li class="checklist-item"><div><strong>Custom Post Types y taxonomías</strong> Modelado de datos correcto desde el principio. No todo tiene que ser una entrada o una página. Los CPT bien diseñados facilitan el mantenimiento y la escalabilidad.</div></li>
          <li class="checklist-item"><div><strong>Campos personalizados</strong> Integración con ACF o campos nativos según el caso. Paneles de administración usables para el cliente, sin necesidad de tocar código para actualizar contenido.</div></li>
          <li class="checklist-item"><div><strong>Rendimiento desde la base</strong> CSS y JS propios y mínimos, imágenes con lazy loading y formatos modernos, caché correctamente configurada, sin scripts de terceros innecesarios.</div></li>
          <li class="checklist-item"><div><strong>SEO técnico desde el código</strong> HTML semántico, datos estructurados, canonicals correctos, etiquetas Open Graph, velocidad de carga pensada desde el diseño. No hay que arreglar el SEO a posteriori si se construye bien desde el principio.</div></li>
          <li class="checklist-item"><div><strong>Integraciones</strong> Conexión con APIs externas, pasarelas de pago, CRMs, servicios de email marketing o cualquier sistema que el negocio ya use.</div></li>
          <li class="checklist-item"><div><strong>WordPress sin WordPress cuando tiene sentido</strong> A veces la mejor solución no es WordPress. Si el proyecto lo requiere, trabajo en PHP puro o Laravel.</div></li>
        </ul>

        <h2>Lo que evito en desarrollo WordPress</h2>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item">Plantillas genéricas de Themeforest con miles de opciones que nadie usa.</li>
          <li class="checklist-item">Page builders pesados (Elementor, WPBakery) para proyectos donde el cliente no necesita editar el diseño.</li>
          <li class="checklist-item">Plugins para cosas que se pueden hacer con 10 líneas de código propio.</li>
          <li class="checklist-item">Código sin comentar que solo el autor original puede mantener.</li>
          <li class="checklist-item">Instalar plugins de seguridad como sustituto de un desarrollo seguro.</li>
        </ul>

      </div>
      <aside class="service-sidebar">
        <div class="card">
          <h3 style="color:var(--orange);margin-bottom:.75rem">Cuéntame tu proyecto</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Descripción del proyecto, plataforma actual, requisitos técnicos y plazo estimado.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Contactar</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Relacionado</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/plugins-wordpress/" style="color:var(--orange)">→ Plugins a medida</a></li>
            <li><a href="/servicios/mantenimiento-wordpress/" style="color:var(--orange)">→ Mantenimiento WordPress</a></li>
            <li><a href="/servicios/seo-tecnico/" style="color:var(--orange)">→ SEO Técnico</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php
  $cta = ['title' => '¿Tienes un proyecto WordPress en mente?', 'subtitle' => 'Cuéntame qué necesitas. Sin compromiso, te digo si puedo ayudarte y cómo lo plantearía.', 'btn_label' => 'Hablar sobre mi proyecto', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
