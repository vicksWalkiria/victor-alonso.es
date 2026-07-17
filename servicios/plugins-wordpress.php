<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'        => 'Desarrollo de Plugins WordPress a medida',
  'description'  => 'Desarrollo de plugins para WordPress a medida. Soluciones eficientes, seguras y escalables adaptadas a tus necesidades sin sobrecargar la web.',
  'canonical'    => '/servicios/plugins-wordpress/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service'],
  'service_data' => [
      '@id'           => '/servicios/plugins-wordpress/#service',
      'name'          => 'Plugins WordPress a medida',
      'alternateName' => [
          'Desarrollo de plugins WordPress',
          'Programador de plugins',
          'Plugins a medida'
      ],
      'serviceType'   => 'Desarrollo Web',
      'areaServed'    => [
          ['@type' => 'Country', 'name' => 'España']
      ]
  ],
  'active_nav'   => 'servicios',
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'Plugins a medida', 'url' => ''],
  ],
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Plugins WordPress a medida: la funcionalidad que necesitas, sin <span>plugins genéricos</span> que no encajan</h1>
      <p class="page-hero-desc">Si lo que necesitas no lo cubre ningún plugin existente, o el que existe es demasiado pesado para lo que realmente hace, la solución es desarrollarlo a medida.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">

        <h2>Cuándo tiene sentido un plugin a medida</h2>
        <p>No siempre. Primero evalúo si existe una solución ya hecha que sea suficientemente buena, ligera y segura. Si la hay, úsala. Si no existe, si la que existe es un bloque enorme para una función pequeña, o si necesitas algo muy específico del negocio, desarrollo la solución desde cero.</p>

        <h2>Qué puedo desarrollar</h2>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Shortcodes avanzados</strong> Bloques de contenido dinámico reutilizables en el editor. Calculadoras, tablas comparativas, formularios inline, contenido condicional.</div></li>
          <li class="checklist-item"><div><strong>Formularios con lógica propia</strong> Cuando los plugins de formularios estándar no cubren la validación, el flujo o el destino de los datos que necesitas.</div></li>
          <li class="checklist-item"><div><strong>Integraciones con APIs externas</strong> Conectar WordPress con servicios de terceros: CRMs, pasarelas de pago, plataformas de email, ERPs, APIs propias del negocio.</div></li>
          <li class="checklist-item"><div><strong>WooCommerce personalizado</strong> Campos personalizados en el checkout, métodos de envío propios, integraciones de pasarelas, paneles de gestión de pedidos con lógica específica.</div></li>
          <li class="checklist-item"><div><strong>Paneles de administración</strong> Páginas de administración propias en el dashboard de WordPress, con tablas de datos, filtros, exportaciones CSV y acciones personalizadas.</div></li>
          <li class="checklist-item"><div><strong>Automatizaciones internas</strong> Procesos que hoy son manuales y pueden automatizarse: importación de datos, envío de notificaciones, sincronización con otras plataformas, generación de documentos.</div></li>
          <li class="checklist-item"><div><strong>Bloques de Gutenberg personalizados</strong> Bloques a medida con la React API de Gutenberg cuando el editor nativo no cubre lo que el cliente necesita editar.</div></li>
        </ul>

        <h2>Cómo desarrollo un plugin</h2>
        <p>El proceso siempre empieza por entender qué problema resuelve y para quién. Los detalles técnicos vienen después.</p>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Definición de requisitos</strong> Qué tiene que hacer, qué no tiene que hacer, quién lo va a usar y con qué frecuencia.</div></li>
          <li class="checklist-item"><div><strong>Validación y sanitización</strong> Todo input del usuario se valida y sanitiza antes de procesarse. No asumo que el dato viene limpio.</div></li>
          <li class="checklist-item"><div><strong>Seguridad</strong> Nonces, verificación de permisos, escape de output. Los agujeros de seguridad más comunes en WordPress vienen de plugins mal desarrollados.</div></li>
          <li class="checklist-item"><div><strong>Código documentado</strong> Comentarios en funciones, hooks, filtros y clases. El código tiene que ser mantenible por otra persona si fuera necesario.</div></li>
          <li class="checklist-item"><div><strong>Rendimiento</strong> Consultas a la base de datos optimizadas, uso de la API de WordPress correctamente, sin N+1 queries ni lógica pesada en cada carga de página.</div></li>
        </ul>

        <h2>Casos de uso reales</h2>
        <p>Algunos ejemplos del tipo de funcionalidades que he desarrollado o que suelen solicitarse:</p>
        <ul class="checklist" style="margin:1rem 0">
          <li class="checklist-item">Calculadoras de precio con lógica específica del negocio.</li>
          <li class="checklist-item">Sistemas de reservas simples sin pagar por plataformas externas caras.</li>
          <li class="checklist-item">Importadores de productos desde CSV o Excel al catálogo de WooCommerce.</li>
          <li class="checklist-item">Paneles de gestión de clientes con acceso restringido por rol.</li>
          <li class="checklist-item">Integraciones con APIs de mensajería (WhatsApp Business, Telegram, email transaccional).</li>
          <li class="checklist-item">Generadores de documentos PDF desde datos del formulario o del pedido.</li>
        </ul>

        <h2>Ficha técnica del servicio</h2>
        <div class="service-ficha" style="background: var(--bg-hover); border-radius: 12px; padding: 2rem; margin-top: 2rem; border: 1px solid var(--border);">
          <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 1rem; font-size: 0.95rem;">
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-check" style="color: var(--orange); margin-right: 0.5rem;"></i> Qué incluye exactamente:</span>
              <span style="color: var(--text);">Creación o edición de plugins de WordPress para aumentar su funcionalidad, adaptarla a las necesidades exactas del cliente o eliminar carga de código innecesaria.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-xmark" style="color: #ef4444; margin-right: 0.5rem;"></i> Qué NO incluye:</span>
              <span style="color: var(--text);">Diseños de plugins que necesiten un contenido visual muy fuerte (mi enfoque es la eficiencia técnica y funcional).</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-box-open" style="color: var(--muted); margin-right: 0.5rem;"></i> Entregables y despliegue:</span>
              <span style="color: var(--text);">Plugin funcional, testeado primero en una copia de la web del cliente para minimizar cualquier impacto y evitar caídas en producción.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-clock" style="color: var(--muted); margin-right: 0.5rem;"></i> Duración habitual:</span>
              <span style="color: var(--text);">Entre 2 y 4 semanas.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-list-check" style="color: var(--muted); margin-right: 0.5rem;"></i> Requisitos del cliente:</span>
              <span style="color: var(--text);">Acceso para realizar una copia de su sitio web y los diseños previos (si fuesen necesarios para la interfaz del plugin).</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-triangle-exclamation" style="color: var(--orange); margin-right: 0.5rem;"></i> Cuándo NO merece la pena:</span>
              <span style="color: var(--text);">Cuando ya existe un plugin gratuito, seguro y ligero que hace exactamente la función que necesitas.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-code" style="color: var(--muted); margin-right: 0.5rem;"></i> Stack Tecnológico:</span>
              <span style="color: var(--text);">WordPress, PHP, CSS, HTML.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-star" style="color: var(--orange); margin-right: 0.5rem;"></i> Casos de uso / Ejemplos:</span>
              <span style="color: var(--text);">Por ejemplo, mi propio <a href="https://aprendizdeseo.gumroad.com/l/plugin-reviews" target="_blank" rel="noopener noreferrer" style="color:var(--orange);text-decoration:underline;">Plugin de Reseñas</a>.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-tag" style="color: var(--muted); margin-right: 0.5rem;"></i> Inversión ("Desde"):</span>
              <span style="font-size: 1.1rem; font-weight: 800; color: var(--orange);">150 €</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-handshake" style="color: var(--muted); margin-right: 0.5rem;"></i> Proceso de contratación:</span>
              <span style="color: var(--text);">Siempre trabajo solicitando un 50% por adelantado antes de empezar, y el 50% restante cuando la funcionalidad se valida en el servidor de pruebas.</span>
            </li>
            <li style="display: flex; flex-direction: column; gap: 0.25rem;">
              <span style="font-weight: 700; color: var(--black);"><i class="fa-solid fa-arrow-right-arrow-left" style="color: var(--muted); margin-right: 0.5rem;"></i> Alternativa si no encajamos:</span>
              <span style="color: var(--text);">Si no te encaja mi servicio, contacta conmigo igualmente. Conozco a gente del sector de absoluta confianza a los que te puedo derivar.</span>
            </li>
          </ul>
        </div>

      </div>
      <aside class="service-sidebar">
        <div class="card">
          <h3 style="color:var(--orange);margin-bottom:.75rem">¿Tienes una funcionalidad en mente?</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Explícame qué necesitas y te digo si es viable, cuánto costaría desarrollarlo y cuánto tardaría.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Contactar</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Relacionado</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/desarrollo-wordpress/" style="color:var(--orange)">→ Desarrollo WordPress</a></li>
            <li><a href="/servicios/mantenimiento-wordpress/" style="color:var(--orange)">→ Mantenimiento WordPress</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php
  $cta = ['title' => '¿Necesitas una funcionalidad que ningún plugin cubre bien?', 'subtitle' => 'Cuéntame qué hace falta. Te digo si tiene sentido desarrollarlo a medida.', 'btn_label' => 'Hablar sobre mi proyecto', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
