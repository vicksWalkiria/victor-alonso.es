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

        <h2>Ficha técnica del servicio</h2>
        <div class="service-ficha" style="background: var(--bg-hover); border-radius: 12px; padding: 2rem; margin-top: 2rem; border: 1px solid var(--border);">
          <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 1rem; font-size: 0.95rem;">
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-check" style="color: var(--orange); margin-right: 0.5rem;"></i> Qué incluye exactamente:</span>
              <span style="color: var(--text);">Funcionalidades adicionales para WordPress, personalización y creación de themes y plantillas, mejoras técnicas, WPO, Core Web Vitals, instalación de scripts y tags de Google Analytics y otras redes. Instalación, maquetación y puesta a punto de páginas web en WordPress. Eliminación de constructores visuales (Divi, Elementor) y sustitución por código HTML y PHP puro con CSS para mayor velocidad.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-xmark" style="color: #ef4444; margin-right: 0.5rem;"></i> Qué NO incluye:</span>
              <span style="color: var(--text);">Elaboración del diseño gráfico ni implementación con constructores visuales.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-box-open" style="color: var(--muted); margin-right: 0.5rem;"></i> Entregables y despliegue:</span>
              <span style="color: var(--text);">Web funcional en entorno de pruebas (no visible durante la validación). La web original no se ve afectada; el paso a producción se hace en pocos minutos para evitar la caída del servicio.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-clock" style="color: var(--muted); margin-right: 0.5rem;"></i> Duración habitual:</span>
              <span style="color: var(--text);">Entre 2 y 4 semanas.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-list-check" style="color: var(--muted); margin-right: 0.5rem;"></i> Requisitos del cliente:</span>
              <span style="color: var(--text);">Proporcionar su propio dominio, así como el diseño o directrices de diseño.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-triangle-exclamation" style="color: var(--orange); margin-right: 0.5rem;"></i> Cuándo NO merece la pena:</span>
              <span style="color: var(--text);">Cuando quieres una web montada con Elementor, pesada, lenta y que realmente no vayas a usar para captar tráfico serio.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-code" style="color: var(--muted); margin-right: 0.5rem;"></i> Stack Tecnológico:</span>
              <span style="color: var(--text);">WordPress, PHP, CSS, HTML.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-star" style="color: var(--orange); margin-right: 0.5rem;"></i> Casos de uso / Ejemplos:</span>
              <span style="color: var(--text);">Proyectos como walkiriaapps.com o aprendizdeseo.top.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-tag" style="color: var(--muted); margin-right: 0.5rem;"></i> Inversión ("Desde"):</span>
              <span style="font-size: 1.1rem; font-weight: 800; color: var(--orange);">450 €</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-handshake" style="color: var(--muted); margin-right: 0.5rem;"></i> Proceso de contratación:</span>
              <span style="color: var(--text);">Siempre trabajo solicitando un 50% por adelantado antes de empezar, y el 50% restante cuando se valida el proyecto en el servidor de pruebas.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem;">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-arrow-right-arrow-left" style="color: var(--muted); margin-right: 0.5rem;"></i> Alternativa si no encajamos:</span>
              <span style="color: var(--text);">Si por presupuesto o enfoque no te encaja, habla conmigo igualmente. Conozco gente del sector de absoluta confianza que te puedo recomendar.</span>
            </li>
          </ul>
        </div>

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
