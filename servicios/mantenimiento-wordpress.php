<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'        => 'Servicio Mantenimiento WordPress Empresas (Desde 59 €/mes)',
  'raw_title'    => true,
  'description'  => 'Servicio de mantenimiento WordPress para empresas desde 59 €/mes: actualizaciones en Staging, backups externos diarios S3, seguridad activa, WPO y soporte directo.',
  'canonical'    => '/servicios/mantenimiento-wordpress/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service', 'FAQPage'],
  'service_data' => [
      '@id'           => '/servicios/mantenimiento-wordpress/#service',
      'name'          => 'Servicio de Mantenimiento WordPress para Empresas',
      'alternateName' => [
          'Servicio mantenimiento WordPress',
          'Soporte técnico WordPress',
          'Mantenimiento web WordPress',
          'Mantenimiento preventivo WordPress'
      ],
      'serviceType'   => 'Mantenimiento Web WordPress',
      'areaServed'    => [
          ['@type' => 'Country', 'name' => 'España']
      ],
      'offers'        => [
          'minPrice'   => 59,
          'maxPrice'   => 149,
          'offerCount' => 3
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
      'q' => '¿Por qué externalizar el mantenimiento WordPress con un informático y no con una agencia genérica?',
      'a' => 'Muchas agencias delegan el mantenimiento en personal no técnico o activan actualizaciones automáticas a ciegas. Como ingeniero informático, reviso los changelogs antes de actualizar, pruebo saltos de versión mayor en entornos Staging y aplico políticas de hardening y backups externos que protegen la continuidad de tu facturación.'
    ],
    [
      'q' => '¿Dónde se almacenan las copias de seguridad?',
      'a' => 'Las copias de seguridad (backups) completas se envían a un almacenamiento en la nube cifrado y totalmente externo a tu servidor (ej. Amazon S3). Guardar los backups en el mismo servidor donde está alojada tu web es un riesgo inaceptable de pérdida total de datos si el disco falla o es hackeado.'
    ],
    [
      'q' => '¿Cómo gestionas las actualizaciones de riesgo en plugins y plantillas?',
      'a' => 'Nunca activo la actualización automática a ciegas. Previamente leo el changelog del desarrollador. Si hay un cambio estructural o un salto de versión mayor (ej. de v2 a v3), clono tu web en un entorno seguro (Staging), aplico la actualización, y compruebo que la web y los procesos de compra sigan funcionando correctamente antes de subirlo a producción.'
    ],
    [
      'q' => '¿Qué ocurre si mi web se cae mientras tengo el mantenimiento activo?',
      'a' => 'Al contar con monitorización Uptime 24/7 y copias de seguridad verificadas fuera de tu hosting, recibo una alerta inmediata para intervenir y restablecer el servicio en minutos, evitando pérdidas de ventas o penalizaciones en Google.'
    ]
  ]
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Mantenimiento WordPress Profesional: <span>Seguridad, Soporte y Rendimiento</span></h1>
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

        <h2>A quién va dirigido el servicio de mantenimiento WordPress</h2>
        <p>Este servicio de mantenimiento no está enfocado en blogs personales ni en webs abandonadas. Está diseñado exclusivamente para <strong>empresas, e-commerces y profesionales</strong> que dependen de su página web para vender, captar leads o sostener su reputación comercial. Si tu web deja de funcionar un viernes por la tarde y supone una pérdida económica para ti, necesitas un profesional técnico cubriéndote las espaldas.</p>

        <h2>Puesta a punto inicial del mantenimiento WordPress</h2>
        <p>Antes de asumir el mantenimiento periódico de cualquier plataforma, necesito garantizar que la base técnica sea higiénica y sólida. Por ello, el servicio incluye una auditoría y alta técnica:</p>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Auditoría de seguridad profunda:</strong> Identificación y cierre de vulnerabilidades activas, reconfiguración de permisos de archivos y blindaje del panel.</div></li>
          <li class="checklist-item"><div><strong>Saneamiento de código:</strong> Eliminación de plugins inactivos, plantillas huérfanas o código inyectado abandonado.</div></li>
          <li class="checklist-item"><div><strong>Despliegue de política de Backups:</strong> Configuración de los ciclos de copia de seguridad redundante con subida a cloud externa.</div></li>
          <li class="checklist-item"><div><strong>Baseline de rendimiento WPO:</strong> Medición inicial de los tiempos de respuesta del servidor (TTFB) y renderizado de la interfaz.</div></li>
        </ul>

        <h2>Qué incluye el mantenimiento web WordPress mensual</h2>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Actualizaciones controladas y probadas:</strong> WordPress core, temas y plugins se actualizan bajo supervisión. Las versiones de alto riesgo se prueban siempre en un entorno clonado aislado (Staging) para evitar roturas en la web real.</div></li>
          <li class="checklist-item"><div><strong>Backups programados y comprobados:</strong> Copias completas incrementales fuera de tu infraestructura de alojamiento. Si el centro de datos de tu proveedor se quema, tu web seguirá a salvo en otra ubicación geográfica.</div></li>
          <li class="checklist-item"><div><strong>Monitorización Uptime 24/7:</strong> Pings automatizados. Si el servidor de tu web deja de dar respuesta, recibo una alerta prioritaria en mi teléfono.</div></li>
          <li class="checklist-item"><div><strong>Seguridad activa (Hardening y WAF):</strong> Bloqueo proactivo de ataques de fuerza bruta hacia /wp-admin, protección de formularios contra SPAM y parcheo virtual.</div></li>
          <li class="checklist-item"><div><strong>Auditoría de rendimiento recurrente:</strong> Comprobación para evitar que nuevos contenidos o funcionalidades introduzcan cuellos de botella técnicos o lentitud.</div></li>
        </ul>

        <h2>Planes y tarifas de mantenimiento WordPress (sin permanencia)</h2>
        <p>Precios claros según la criticidad de tu web. Todos los planes son ejecutados personalmente por mí como ingeniero informático, sin permanencia obligatoria y con factura deducible mensual:</p>
        <div class="plans-grid">
          <article class="plan-card">
            <div>
              <span class="plan-badge">Web Corporativa</span>
              <h3 class="plan-title">Esencial</h3>
              <div class="plan-price">59 € <span>/ mes + IVA</span></div>
              <ul class="plan-features">
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Backups diarios cifrados en nube externa (Amazon S3)</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Actualizaciones revisadas quincenales de core, tema y plugins</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Monitorización Uptime 24/7 y blindaje WAF perimetral</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Respuesta ante caída crítica &lt; 12 h laborables</span>
                </li>
              </ul>
            </div>
            <a href="/contacto/" class="btn btn--secondary" style="width:100%;justify-content:center;">Contratar Esencial</a>
          </article>

          <article class="plan-card plan-card--featured">
            <div>
              <span class="plan-badge">Más contratado · Pymes</span>
              <h3 class="plan-title">Negocio Activo</h3>
              <div class="plan-price">99 € <span>/ mes + IVA</span></div>
              <ul class="plan-features">
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Todo lo incluido en Esencial + <strong>pruebas en entorno Staging</strong></span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Actualizaciones semanales y revisión de changelogs críticos</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Supervisión mensual de Core Web Vitals y base de datos</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span><strong>1 h/mes incluida</strong> de ajustes técnicos y soporte directo WhatsApp</span>
                </li>
              </ul>
            </div>
            <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center;">Elegir Plan Negocio</a>
          </article>

          <article class="plan-card">
            <div>
              <span class="plan-badge">Tiendas &amp; Alto Tráfico</span>
              <h3 class="plan-title">WooCommerce Crítico</h3>
              <div class="plan-price">149 € <span>/ mes + IVA</span></div>
              <ul class="plan-features">
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Backups cada 6–12 horas sin perder pedidos ni clientes</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Test funcional de checkout, Redsys/Stripe y emails en Staging</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Auditoría de logs de servidor y limpieza de transients SQL</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span><strong>SLA prioritario &lt; 4 h</strong> + 2 h/mes de bolsa técnica incluida</span>
                </li>
              </ul>
            </div>
            <a href="/contacto/" class="btn btn--secondary" style="width:100%;justify-content:center;">Consultar WooCommerce</a>
          </article>
        </div>

        <h2>Por qué contratar un servicio de mantenimiento WordPress con ingeniería real</h2>
        <p>A diferencia de las agencias de marketing generalistas o los planes básicos de hosting que se limitan a activar actualizaciones automáticas sin supervisión, gestiono cada instalación por SSH como ingeniero informático:</p>
        <div class="comparison-wrap">
          <table class="comparison-table">
            <thead>
              <tr>
                <th>Tarea crítica</th>
                <th>Mantenimiento Genérico / Auto-Update</th>
                <th>Mi Servicio de Mantenimiento WordPress</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Actualización de plugins</strong></td>
                <td>Automática en producción (si rompe el checkout, nadie se entera hasta que un cliente avisa)</td>
                <td>Lectura previa de changelog + clonado en Staging para versiones mayores + verificación visual</td>
              </tr>
              <tr>
                <td><strong>Copias de seguridad</strong></td>
                <td>Guardadas en el mismo servidor o sin probar su restauración</td>
                <td>Cifradas y enviadas fuera de tu hosting (S3 externo) con prueba periódica de integridad</td>
              </tr>
              <tr>
                <td><strong>Seguridad frente a malware</strong></td>
                <td>Instalar un plugin pesado que consume CPU</td>
                <td>Hardening en <code>.htaccess</code> / <code>wp-config.php</code>, bloqueo de ejecución PHP en uploads y revisión de logs</td>
              </tr>
              <tr>
                <td><strong>Interlocución ante crisis</strong></td>
                <td>Sistema de tickets nivel 1 con respuestas prefabricadas</td>
                <td>Contacto directo conmigo por WhatsApp o teléfono con acceso inmediato por SSH</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p>Un mantenimiento preventivo profesional no es un gasto prescindible: es el seguro de continuidad de tu negocio en internet para evitar caídas imprevistas, pérdidas de ventas y bloqueos de seguridad en Google. Si necesitas además refactorizar una plantilla lenta, revisa mi servicio de <a href="/servicios/desarrollo-wordpress/" style="color:var(--orange);text-decoration:underline;font-weight:600;">desarrollo WordPress a medida</a>.</p>

        <h2>Qué tareas no incluye el mantenimiento de WordPress</h2>
        <p>La delimitación clara evita falsas expectativas. El plan preventivo no cubre:</p>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item">La creación de nuevas landing pages, rediseños estéticos de la plantilla web o labores de diseño gráfico.</li>
          <li class="checklist-item">Servicios de redacción de contenidos, posicionamiento SEO mensual o Community Management.</li>
          <li class="checklist-item">Desarrollo a medida de nuevas funcionalidades transaccionales o pasarelas de pago (se presupuestan de forma independiente al mantenimiento).</li>
        </ul>

        <!-- La diferencia real entre prevenir y curar -->
        <div class="card card--dark" style="margin-top: 1.5rem; border-color: var(--orange);">
          <h3 style="color: var(--orange); margin-bottom: 0.5rem;">Caso real: El ataque de malware el fin de semana</h3>
          <p style="font-size: 0.95rem; line-height: 1.5; color: #cbd5e1;">Una empresa B2B me contactó de urgencia un lunes porque su web estaba bloqueada por la alerta roja de Google <em>"El sitio web al que vas a acceder es engañoso"</em>. Llevaban meses sin actualizar un plugin de formularios. Un bot había inyectado scripts ofuscados la madrugada del sábado anterior redirigiendo todo el tráfico de dispositivos móviles a webs de fraudes.</p>
          <p style="font-size: 0.95rem; line-height: 1.5; color: #cbd5e1;">Si hubieran tenido el <strong>mantenimiento preventivo</strong>, el plugin se habría actualizado antes del exploit. Y en el peor escenario, nuestra monitorización de integridad de archivos habría lanzado una alerta a las 4 de la mañana, permitiéndome restaurar el backup en 10 minutos y aislar la vulnerabilidad antes de que Google rastreara el malware. Puedes leer los detalles de esta auditoría y desinfección forense en mi <a href="/caso-exito-desinfeccion-malware-wordpress/" style="color: var(--orange); text-decoration: underline; font-weight: 600;">caso de éxito de desinfección de malware en WordPress</a>.</p>
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
