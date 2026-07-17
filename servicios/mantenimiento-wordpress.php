<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'        => 'Mantenimiento WordPress profesional | Seguridad y soporte',
  'description'  => 'Mantenimiento WordPress con actualizaciones controladas, copias externas, monitorización, seguridad, WPO y soporte técnico directo.',
  'canonical'    => '/servicios/mantenimiento-wordpress/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service', 'FAQPage'],
  'service_data' => [
      '@id'           => '/servicios/mantenimiento-wordpress/#service',
      'name'          => 'Mantenimiento WordPress profesional',
      'alternateName' => [
          'Soporte técnico WordPress',
          'Mantenimiento web WordPress',
          'Mantenimiento preventivo WordPress'
      ],
      'serviceType'   => 'Mantenimiento Web',
      'areaServed'    => [
          ['@type' => 'Country', 'name' => 'España']
      ]
  ],
  'active_nav'   => 'servicios',
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'Mantenimiento WordPress', 'url' => ''],
  ],
  'faq_items' => [
    [
      'q' => '¿Qué diferencia hay entre mantenimiento y reparación urgente?',
      'a' => 'El mantenimiento es un trabajo preventivo mensual para evitar que tu web se rompa o sea hackeada. La reparación urgente es una intervención de emergencia cuando la web ya está caída, rota o infectada por malware.'
    ],
    [
      'q' => '¿Dónde se almacenan las copias de seguridad?',
      'a' => 'Las copias de seguridad (backups) completas se envían a un almacenamiento en la nube cifrado y totalmente externo a tu servidor (ej. Amazon S3). Guardar los backups en el mismo servidor donde está alojada tu web es un riesgo inaceptable de pérdida total de datos si el disco falla o es hackeado.'
    ],
    [
      'q' => '¿Cómo gestionas las actualizaciones de riesgo en plugins y plantillas?',
      'a' => 'Nunca activo la actualización automática a ciegas. Previamente leo el changelog del desarrollador. Si hay un cambio estructural o un salto de versión mayor (ej. de v2 a v3), clono tu web en un entorno seguro (Staging), aplico la actualización, y compruebo que la web y los procesos de compra sigan funcionando correctamente antes de subirlo a producción.'
    ]
  ]
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Mantenimiento WordPress profesional: seguridad, soporte y <span>rendimiento</span></h1>
      <p class="page-hero-desc">Un servicio preventivo para que tu negocio online funcione sin interrupciones. Actualizaciones seguras, copias externas y un soporte técnico al que puedes llamar cuando lo necesitas.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">

        <!-- ALERTA URGENCIA -->
        <div class="alert alert--danger" style="margin-bottom: 2rem;">
          <strong>¿Tu web ya está caída o hackeada por malware?</strong><br>
          Si estás en medio de una crisis (pantalla en blanco, redirecciones a webs de apuestas, alertas rojas de seguridad en Google Chrome), no necesitas un mantenimiento preventivo mensual, necesitas un rescate inmediato. <a href="/servicios/reparacion-wordpress-urgente/" style="text-decoration: underline; font-weight: bold; color: inherit;">Accede al servicio de reparación urgente de WordPress</a>.
        </div>

        <h2>Para quién es este servicio de soporte</h2>
        <p>Este servicio de mantenimiento no está enfocado en blogs personales ni en webs abandonadas. Está diseñado exclusivamente para <strong>empresas, e-commerces y profesionales</strong> que dependen de su página web para vender, captar leads o sostener su reputación comercial. Si tu web deja de funcionar un viernes por la tarde y supone una pérdida económica para ti, necesitas un profesional técnico cubriéndote las espaldas.</p>

        <h2>Qué incluye la puesta a punto inicial</h2>
        <p>Antes de asumir el mantenimiento periódico de cualquier plataforma, necesito garantizar que la base técnica sea higiénica y sólida. Por ello, el servicio incluye una auditoría y alta técnica:</p>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Auditoría de seguridad profunda:</strong> Identificación y cierre de vulnerabilidades activas, reconfiguración de permisos de archivos y blindaje del panel.</div></li>
          <li class="checklist-item"><div><strong>Saneamiento de código:</strong> Eliminación de plugins inactivos, plantillas huérfanas o código inyectado abandonado.</div></li>
          <li class="checklist-item"><div><strong>Despliegue de política de Backups:</strong> Configuración de los ciclos de copia de seguridad redundante con subida a cloud externa.</div></li>
          <li class="checklist-item"><div><strong>Baseline de rendimiento WPO:</strong> Medición inicial de los tiempos de respuesta del servidor (TTFB) y renderizado de la interfaz.</div></li>
        </ul>

        <h2>Qué incluye el mantenimiento preventivo mensual</h2>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Actualizaciones controladas y probadas:</strong> WordPress core, temas y plugins se actualizan bajo supervisión. Las versiones de alto riesgo se prueban siempre en un entorno clonado aislado (Staging) para evitar roturas en la web real.</div></li>
          <li class="checklist-item"><div><strong>Backups programados y comprobados:</strong> Copias completas incrementales fuera de tu infraestructura de alojamiento. Si el centro de datos de tu proveedor se quema, tu web seguirá a salvo en otra ubicación geográfica.</div></li>
          <li class="checklist-item"><div><strong>Monitorización Uptime 24/7:</strong> Pings automatizados. Si el servidor de tu web deja de dar respuesta, recibo una alerta prioritaria en mi teléfono.</div></li>
          <li class="checklist-item"><div><strong>Seguridad activa (Hardening y WAF):</strong> Bloqueo proactivo de ataques de fuerza bruta hacia /wp-admin, protección de formularios contra SPAM y parcheo virtual.</div></li>
          <li class="checklist-item"><div><strong>Auditoría de rendimiento recurrente:</strong> Comprobación para evitar que nuevos contenidos o funcionalidades introduzcan cuellos de botella técnicos o lentitud.</div></li>
        </ul>

        <h2>Lo que NO incluye este plan</h2>
        <p>La delimitación clara evita falsas expectativas. El plan preventivo no cubre:</p>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item">La creación de nuevas landing pages, rediseños estéticos de la plantilla web o labores de diseño gráfico.</li>
          <li class="checklist-item">Servicios de redacción de contenidos, posicionamiento SEO mensual o Community Management.</li>
          <li class="checklist-item">Desarrollo a medida de nuevas funcionalidades transaccionales o pasarelas de pago (se presupuestan de forma independiente al mantenimiento).</li>
        </ul>

        <h2>La diferencia real entre prevenir y curar</h2>
        <div class="card card--dark" style="margin-top: 1.5rem; border-color: var(--orange);">
          <h3 style="color: var(--orange); margin-bottom: 0.5rem;">Caso real: El ataque de malware el fin de semana</h3>
          <p style="font-size: 0.95rem; line-height: 1.5; color: #cbd5e1;">Una empresa B2B me contactó de urgencia un lunes porque su web estaba bloqueada por la alerta roja de Google <em>"El sitio web al que vas a acceder es engañoso"</em>. Llevaban meses sin actualizar un plugin de formularios. Un bot había inyectado scripts ofuscados la madrugada del sábado anterior redirigiendo todo el tráfico de dispositivos móviles a webs de fraudes.</p>
          <p style="font-size: 0.95rem; line-height: 1.5; color: #cbd5e1;">Si hubieran tenido el <strong>mantenimiento preventivo</strong>, el plugin se habría actualizado antes del exploit. Y en el peor escenario, nuestra monitorización de integridad de archivos habría lanzado una alerta a las 4 de la mañana, permitiéndome restaurar el backup en 10 minutos y aislar la vulnerabilidad antes de que Google rastreara el malware.</p>
        </div>

      </div>
      <aside class="service-sidebar">
        <div class="card">
          <h3 style="color:var(--orange);margin-bottom:.75rem">Protege tu negocio digital</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Pide presupuesto de mantenimiento indicando la URL de tu web, en qué servidor la alojas y qué problemas sueles encontrarte habitualmente.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Solicitar presupuesto</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Servicios relacionados</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/reparacion-wordpress-urgente/" style="color:var(--orange)">→ Reparación Urgente</a></li>
            <li><a href="/servicios/seo-tecnico/" style="color:var(--orange)">→ SEO Técnico para WordPress</a></li>
            <li><a href="/casos-reales/" style="color:var(--orange)">→ Casos de estudio</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <?php
  $cta = ['title' => '¿Tu WordPress está realmente protegido?', 'subtitle' => 'Pagar un mantenimiento profesional y transparente es mucho más barato que el lucro cesante de una caída no resuelta.', 'btn_label' => 'Hablar sobre tu mantenimiento', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
