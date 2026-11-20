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
          'Mantenimiento preventivo WordPress',
          'Mantenimiento WooCommerce'
      ],
      'serviceType'   => 'Mantenimiento Web WordPress y Seguridad de Servidor',
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
      'q' => '¿Qué diferencia hay entre el mantenimiento preventivo WordPress y la reparación urgente?',
      'a' => 'El mantenimiento preventivo es una supervisión técnica continua (desde 59 €/mes) diseñada para evitar caídas, vulnerabilidades y pérdida de rendimiento antes de que ocurran. La reparación urgente es una intervención forense de choque para webs que ya están caídas con Error 500, bloqueadas por Google Safe Browsing o infectadas por malware activo.'
    ],
    [
      'q' => '¿Por qué contratar el mantenimiento WordPress con un ingeniero informático en lugar de activar actualizaciones automáticas?',
      'a' => 'Las actualizaciones automáticas desatendidas ejecutan cambios de código directamente en producción sin comprobar compatibilidades entre PHP, el tema hijo, pasarelas Redsys/Stripe o WooCommerce. Como ingeniero informático administro tu instalación por SSH y WP-CLI, reviso los changelogs técnicos, pruebo saltos mayores en entorno Staging aislado y verifico logs de errores PHP tras cada despliegue.'
    ],
    [
      'q' => '¿Dónde se almacenan las copias de seguridad y con qué frecuencia se ejecutan?',
      'a' => 'Aplico una política de copias externas cifradas en almacenamiento cloud independiente de tu proveedor de hosting (Amazon S3 / almacenamiento compatible S3 en región europea). En los planes Esencial y Negocio Activo los backups son diarios; en WooCommerce Crítico se ejecutan cada 6 a 12 horas para proteger pedidos, clientes y facturas recientes ante cualquier desastre en el servidor de origen.'
    ],
    [
      'q' => '¿Cómo evitáis que una actualización de plugins rompa el diseño o el proceso de pago?',
      'a' => 'En los planes Negocio Activo y WooCommerce Crítico clonamos la web en un subdominio o contenedor Staging bloqueado a buscadores. Aplicamos las actualizaciones allí primero, comprobamos la ausencia de errores fatales en el error_log de PHP, validamos formularios, carrito y pasarelas de pago (Stripe, Redsys, PayPal) y solo promocionamos los cambios a producción cuando la prueba es limpia.'
    ],
    [
      'q' => '¿Tengo que cambiar de servidor o proveedor de hosting para contratar el mantenimiento?',
      'a' => 'No es obligatorio. Trabajo sobre tu infraestructura actual (VPS Linux, Cloudways, SiteGround, Raiola, Webempresa, Dinahosting, Plesk o cPanel) siempre que disponga de acceso SSH/SFTP y recursos mínimos razonables. Si durante la auditoría inicial detecto que tu alojamiento actual estrangula el TTFB o carece de aislamiento de seguridad básico, te recomendaré alternativas con datos objetivos, sin comisiones ocultas.'
    ],
    [
      'q' => '¿Qué cubren las horas técnicas mensuales incluidas y cómo funciona el soporte?',
      'a' => 'El plan Negocio Activo incluye 1 hora mensual y WooCommerce Crítico incluye 2 horas mensuales de ingeniería directa para ajustes evolutivos menores: resolución de advertencias en Search Console, inserción de códigos de seguimiento, ajustes puntuales de CSS/plantilla, creación de redirecciones 301 o configuración de plugins. Hablas directamente conmigo por correo o WhatsApp, sin tickets de nivel 1.'
    ],
    [
      'q' => '¿Existe compromiso de permanencia o penalización por cancelar el servicio?',
      'a' => 'Cero permanencia. Todos los planes se facturan mensualmente con factura deducible para empresas y autónomos, y puedes causar baja avisando antes del siguiente ciclo mensual. Los accesos, el código y las copias de seguridad de tu negocio son siempre propiedad tuya.'
    ]
  ]
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Mantenimiento WordPress Profesional: <span>Seguridad, Staging y Rendimiento</span></h1>
      <p class="page-hero-desc">Administración técnica preventiva por ingeniero informático para empresas y tiendas WooCommerce que no pueden permitirse caídas, hackeos ni actualizaciones a ciegas. Desde 59 €/mes sin permanencia.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">

        <!-- ALERTA URGENCIA -->
        <div class="diag-alert diag-alert--danger" style="margin-bottom: 2.25rem;">
          <div class="diag-alert-title" style="display:flex; align-items:center; gap:0.5rem;">
            <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>¿Tu WordPress ya está caído, lento al extremo o infectado por malware?</span>
          </div>
          <p class="diag-alert-desc" style="margin: 0;">
            Si sufres ahora mismo una pantalla blanca (Error 500), redirecciones spam en móvil o el aviso rojo de Google Chrome, no contrates todavía un plan preventivo: solicita primero mi <a href="/servicios/reparacion-wordpress-urgente/" style="text-decoration: underline; font-weight: 700; color: var(--orange-dark);">servicio de reparación urgente de WordPress por SSH</a>.
          </p>
        </div>

        <h2>Por qué una web de empresa no puede depender del piloto automático</h2>
        <p>WordPress impulsa más del 40 % de internet gracias a un ecosistema abierto de miles de plugins y temas desarrollados por terceros independientes. Esa flexibilidad tiene una contrapartida técnica evidente: cada semana se publican nuevas vulnerabilidades (CVEs) en extensiones populares de formularios, constructores visuales, pasarelas de pago y módulos de caché, al mismo tiempo que los proveedores de alojamiento actualizan las ramas de PHP y MySQL en el servidor.</p>
        <p>Dejar una web corporativa o un e-commerce sin supervisión técnica —o confiar en las actualizaciones automáticas desatendidas del panel— suele desembocar en dos escenarios igual de costosos: o bien los plugins quedan congelados durante meses hasta que un bot automatizado explota una vulnerabilidad conocida e inyecta código malicioso, o bien una actualización mayor se instala sola un viernes por la tarde y rompe silenciosamente el formulario de captación o el pago con tarjeta en Redsys y Stripe.</p>
        <p>Este servicio de <strong>mantenimiento WordPress para empresas</strong> está diseñado para eliminar ambos riesgos de raíz. No delego tu web en perfiles junior ni conecto un panel masivo que pulsa «Actualizar todo» en cincuenta clientes a la vez: gestiono tu instalación personalmente como ingeniero informático mediante consola SSH, WP-CLI, entornos de preproducción (Staging) y copias externas verificadas.</p>

        <h2>Auditoría y puesta a punto inicial incluida en el alta</h2>
        <p>Nunca asumo el mantenimiento mensual de un proyecto a ciegas. Durante los primeros días tras el alta ejecuto un protocolo de saneamiento y estabilización base sin coste adicional sobre tu cuota mensual:</p>
        <ul class="checklist" style="margin:1.25rem 0 2.25rem">
          <li class="checklist-item"><div><strong>Verificación de integridad del núcleo y plugins (WP-CLI):</strong> Comprobación criptográfica contra los repositorios oficiales (<code>wp core verify-checksums</code> y <code>wp plugin verify-checksums</code>) para descartar archivos modificados, drop-ins sospechosos o restos de infecciones antiguas en <code>wp-content/uploads/</code> y <code>mu-plugins</code>.</div></li>
          <li class="checklist-item"><div><strong>Higiene de activos inactivos y deuda técnica:</strong> Purga controlada de temas desactivados que actúan como vectores de ataque, plugins duplicados o abandonados sin soporte en los últimos años y tablas huérfanas en la base de datos.</div></li>
          <li class="checklist-item"><div><strong>Alineación del stack de servidor (PHP 8.x y OPcache):</strong> Revisión de versión de PHP, límites de memoria (<code>memory_limit</code>, <code>max_execution_time</code>), configuración de caché de código de bytes OPcache y diagnóstico de respuesta inicial del servidor (TTFB).</div></li>
          <li class="checklist-item"><div><strong>Despliegue de política de copias 3-2-1 y monitorización:</strong> Conexión del flujo de backups cifrados hacia almacenamiento externo S3, alta en sondas de disponibilidad HTTP/SSL 24/7 y registro de métricas base de Core Web Vitals (LCP, INP y CLS).</div></li>
        </ul>

        <h2>Planes y tarifas de mantenimiento WordPress (sin permanencia)</h2>
        <p>Tres niveles transparentes dimensionados según la criticidad operativa de tu proyecto. Precios cerrados con factura deducible mensual y cero permanencia:</p>

        <div class="plans-grid">
          <article class="plan-card">
            <div>
              <span class="plan-badge">Web Corporativa</span>
              <h3 class="plan-title">Esencial</h3>
              <div class="plan-price tabular-nums">59 € <span>/ mes + IVA</span></div>
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
                  <span>Monitorización Uptime 24/7, SSL y blindaje WAF perimetral</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Respuesta ante caída crítica &lt; <strong class="tabular-nums" style="display:inline;">12 h</strong> laborables</span>
                </li>
              </ul>
            </div>
            <a href="/contacto/" class="btn btn--secondary" style="width:100%;justify-content:center;">Contratar Esencial</a>
          </article>

          <article class="plan-card plan-card--featured">
            <div>
              <span class="plan-badge">Más contratado · Pymes</span>
              <h3 class="plan-title">Negocio Activo</h3>
              <div class="plan-price tabular-nums">99 € <span>/ mes + IVA</span></div>
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
                  <span>Supervisión mensual de Core Web Vitals y limpieza SQL</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span><strong class="tabular-nums" style="display:inline;">1 h/mes incluida</strong> de ajustes técnicos y soporte directo WhatsApp</span>
                </li>
              </ul>
            </div>
            <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center;">Elegir Plan Negocio</a>
          </article>

          <article class="plan-card">
            <div>
              <span class="plan-badge">Tiendas &amp; Alto Tráfico</span>
              <h3 class="plan-title">WooCommerce Crítico</h3>
              <div class="plan-price tabular-nums">149 € <span>/ mes + IVA</span></div>
              <ul class="plan-features">
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Backups externos cada <strong class="tabular-nums" style="display:inline;">6–12 h</strong> protegiendo pedidos y clientes</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Test funcional de carrito, checkout, Redsys/Stripe y emails en Staging</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Auditoría de logs de servidor, HPOS y limpieza de Action Scheduler</span>
                </li>
                <li>
                  <svg class="svg-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  <span><strong>SLA prioritario &lt; <span class="tabular-nums">4 h</span></strong> + <span class="tabular-nums">2 h/mes</span> de bolsa técnica incluida</span>
                </li>
              </ul>
            </div>
            <a href="/contacto/" class="btn btn--secondary" style="width:100%;justify-content:center;">Consultar WooCommerce</a>
          </article>
        </div>

        <h3>Comparativa técnica detallada por plan</h3>
        <p>Desglose exacto de alcance, frecuencias operativas y tiempos de respuesta en cada modalidad (desliza horizontalmente en pantallas pequeñas):</p>
        <div class="comparison-wrap" role="region" aria-label="Tabla comparativa de planes de mantenimiento WordPress" tabindex="0">
          <table class="comparison-table tabular-nums" style="min-width: 620px;">
            <thead>
              <tr>
                <th scope="col">Especificación técnica</th>
                <th scope="col">Esencial</th>
                <th scope="col" style="background: var(--orange-light); color: var(--orange-dark);">Negocio Activo</th>
                <th scope="col">WooCommerce Crítico</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Cuota mensual (+ IVA)</strong></td>
                <td><strong>59 € / mes</strong></td>
                <td style="background: rgba(255, 243, 235, 0.45);"><strong>99 € / mes</strong></td>
                <td><strong>149 € / mes</strong></td>
              </tr>
              <tr>
                <td><strong>Copias externas cifradas (S3)</strong></td>
                <td>Diarias (cada 24 h)</td>
                <td style="background: rgba(255, 243, 235, 0.45);">Diarias (cada 24 h)</td>
                <td>Alta frecuencia (cada 6–12 h)</td>
              </tr>
              <tr>
                <td><strong>Retención histórica externa</strong></td>
                <td>30 días</td>
                <td style="background: rgba(255, 243, 235, 0.45);">60 días</td>
                <td>90 días + snapshots pre-cambio</td>
              </tr>
              <tr>
                <td><strong>Ciclo de actualizaciones revisadas</strong></td>
                <td>Quincenal + parches CVE críticos</td>
                <td style="background: rgba(255, 243, 235, 0.45);">Semanal + parches CVE 24 h</td>
                <td>Semanal + parches CVE inmediatos</td>
              </tr>
              <tr>
                <td><strong>Entorno de pruebas Staging</strong></td>
                <td>Bajo demanda en cambios mayores</td>
                <td style="background: rgba(255, 243, 235, 0.45);">Incluido de serie</td>
                <td>Incluido con clonado seguro de pagos</td>
              </tr>
              <tr>
                <td><strong>Validación post-actualización</strong></td>
                <td>HTTP 200 + portada + formulario</td>
                <td style="background: rgba(255, 243, 235, 0.45);">Flujos de captación + logs PHP</td>
                <td>Carrito, Checkout, Redsys/Stripe y SMTP</td>
              </tr>
              <tr>
                <td><strong>Hardening SSH y reglas WAF</strong></td>
                <td>Incluido</td>
                <td style="background: rgba(255, 243, 235, 0.45);">Incluido + revisión de accesos</td>
                <td>Incluido + auditoría mensual de logs</td>
              </tr>
              <tr>
                <td><strong>Mantenimiento WPO y Base de Datos</strong></td>
                <td>Trimestral</td>
                <td style="background: rgba(255, 243, 235, 0.45);">Mensual (Core Web Vitals + SQL)</td>
                <td>Mensual intensivo (autoload, transients, colas)</td>
              </tr>
              <tr>
                <td><strong>Monitorización Uptime y SSL</strong></td>
                <td>24/7 cada 5 min</td>
                <td style="background: rgba(255, 243, 235, 0.45);">24/7 cada 1 min</td>
                <td>24/7 cada 1 min + alerta transaccional</td>
              </tr>
              <tr>
                <td><strong>Bolsa de horas de ingeniería/mes</strong></td>
                <td>Presupuesto previo cerrado</td>
                <td style="background: rgba(255, 243, 235, 0.45);"><strong>1 h / mes incluida</strong></td>
                <td><strong>2 h / mes incluidas</strong></td>
              </tr>
              <tr>
                <td><strong>Canal de soporte directo</strong></td>
                <td>Email directo con ingeniero</td>
                <td style="background: rgba(255, 243, 235, 0.45);">WhatsApp directo + Email</td>
                <td>WhatsApp prioritario + Teléfono + Email</td>
              </tr>
              <tr>
                <td><strong>Respuesta ante caída crítica</strong></td>
                <td>&lt; 12 h laborables</td>
                <td style="background: rgba(255, 243, 235, 0.45);">&lt; 6 h laborables</td>
                <td><strong>SLA prioritario &lt; 4 h</strong></td>
              </tr>
              <tr>
                <td><strong>Compromiso de permanencia</strong></td>
                <td>0 meses (sin permanencia)</td>
                <td style="background: rgba(255, 243, 235, 0.45);">0 meses (sin permanencia)</td>
                <td>0 meses (sin permanencia)</td>
              </tr>
            </tbody>
          </table>
        </div>

        <h2>Cómo ejecuto cada área técnica de tu mantenimiento mensual</h2>
        <p>Detrás de cada cuota mensual hay procedimientos concretos de administración de sistemas e ingeniería web que protegen tanto tus conversiones como tu posicionamiento orgánico en Google:</p>

        <h3>1. Actualizaciones supervisadas en entorno Staging y lectura de changelogs</h3>
        <p>Antes de actualizar el núcleo de WordPress, WooCommerce o cualquier plugin crítico, reviso el registro de cambios oficial (<em>changelog</em>) del desarrollador en busca de rupturas de retrocompatibilidad (<em>breaking changes</em>), cambios en plantillas de WooCommerce o requisitos mínimos de PHP. Cuando existe un salto de versión mayor o una dependencia delicada, sincronizo el sitio en un entorno <strong>Staging aislado</strong> (protegido con autenticación y bloqueado a motores de búsqueda para evitar contenido duplicado).</p>
        <p>En Staging ejecuto la actualización, inspecciono el archivo <code>error_log</code> de PHP para cazar avisos <code>Deprecated</code> o errores fatales <code>E_ERROR</code> ocultos, compruebo que el DOM renderiza sin regresiones visuales y verifico el envío real de formularios o el flujo de checkout. Solo cuando todo responde sin fallos se replica el cambio en producción, guardando siempre un punto de restauración inmediato previo.</p>

        <h3>2. Copias de seguridad diarias cifradas fuera de tu hosting (Regla 3-2-1)</h3>
        <p>Confiar únicamente en el backup automático que ofrece tu propio hosting en el mismo servidor o panel es una falsa sensación de seguridad: ante un fallo catastrófico de almacenamiento, una suspensión de cuenta, un error humano de borrado o una intrusión que escale privilegios en cPanel/Plesk, tanto la web principal como sus copias locales pueden desaparecer a la vez.</p>
        <p>Implemento copias automatizadas bajo la regla 3-2-1: empaquetado completo de archivos y volcado transaccional de la base de datos SQL, cifrado en tránsito y en reposo, y transferencia automática hacia buckets externos en <strong>Amazon S3</strong> (infraestructura independiente de tu servidor web). En tiendas con el plan <em>WooCommerce Crítico</em> reducimos la ventana de punto de recuperación (RPO) a ciclos de 6–12 horas para que una restauración jamás implique perder los pedidos, facturas o registros de usuarios del día.</p>

        <h3>3. Hardening de servidor y seguridad activa sin plugins pesados</h3>
        <p>Muchos mantenimientos genéricos instalan suites de seguridad sobredimensionadas que ejecutan escaneos constantes sobre MySQL en hora punta, degradando el tiempo de respuesta del servidor (TTFB). Mi enfoque traslada la defensa al nivel perimetral y de configuración nativa del servidor web:</p>
        <ul class="checklist" style="margin:1rem 0 1.75rem">
          <li class="checklist-item"><div><strong>Bastionado en <code>wp-config.php</code> y permisos Linux:</strong> Desactivación de la edición de PHP desde el escritorio (<code>DISALLOW_FILE_EDIT</code>), permisos estrictos (<code>640</code>/<code>644</code> en archivos y <code>755</code> en directorios) y rotación criptográfica de claves y <em>Security Salts</em> ante cualquier cambio de personal en tu empresa.</div></li>
          <li class="checklist-item"><div><strong>Reglas de bloqueo en Apache (<code>.htaccess</code>) o Nginx:</strong> Prohibición total de ejecución de scripts <code>.php</code> dentro de <code>wp-content/uploads/</code>, neutralización de ataques de amplificación y fuerza bruta contra <code>xmlrpc.php</code>, bloqueo de enumeración pública de usuarios y cabeceras HTTP de seguridad (<code>X-Content-Type-Options: nosniff</code>, <code>X-Frame-Options</code>, <code>Referrer-Policy</code>).</div></li>
          <li class="checklist-item"><div><strong>Vigilancia temprana de vulnerabilidades CVE:</strong> Si se publica un fallo de ejecución remota de código o inyección SQL en un plugin instalado en tu web, aplico el parche oficial o una regla de mitigación virtual en el cortafuegos (WAF) sin esperar al ciclo ordinario quincenal o semanal.</div></li>
        </ul>

        <h3>4. Monitorización Uptime 24/7, certificados SSL y auditoría de logs</h3>
        <p>Configuro sondas externas que verifican de forma continua no solo que el servidor responda al ping, sino que devuelva código HTTP 200 real y contenga los marcadores esperados del documento HTML (detectando al instante pantallas en blanco silenciosas o caídas parciales de MySQL). Además, monitorizo la renovación automática de certificados TLS/SSL (Let's Encrypt o comerciales) y el estado del dominio en listas de reputación como Google Safe Browsing.</p>
        <p>En paralelo, reviso periódicamente los registros de acceso y error del servidor web para identificar picos anómalos de consumo de CPU provocados por scrapers agresivos, bucles internos en <code>admin-ajax.php</code> o rastreos descontrolados que encarecen tu factura de hosting y perjudican el rastreo de Googlebot.</p>

        <h3>5. Optimización WPO continua y salud de Base de Datos SQL</h3>
        <p>Una web rápida el día de su lanzamiento suele degradarse seis meses después conforme el equipo sube imágenes de 4 MB sin comprimir, instala scripts de marketing o acumula cientos de miles de filas residuales en MySQL. Dentro de los planes <em>Negocio Activo</em> y <em>WooCommerce Crítico</em> realizo mantenimiento activo de rendimiento:</p>
        <ul class="checklist" style="margin:1rem 0 1.75rem">
          <li class="checklist-item"><div><strong>Control de opciones <code>autoload</code> en <code>wp_options</code>:</strong> Vigilancia del volumen de datos que WordPress carga en memoria en cada petición HTTP (manteniéndolo por debajo del umbral recomendado de 800 KB) y limpieza de datos huérfanos dejados por plugins antiguos.</div></li>
          <li class="checklist-item"><div><strong>Purga de <code>transients</code>, revisiones y colas de WooCommerce:</strong> Saneamiento periódico de sesiones caducadas y tablas <code>wp_actionscheduler_actions</code> / <code>wp_actionscheduler_logs</code> que en tiendas activas llegan a inflar varios gigabytes la base de datos.</div></li>
          <li class="checklist-item"><div><strong>Seguimiento de Core Web Vitals (LCP, INP y CLS):</strong> Verificación mensual para asegurar que nuevas páginas comerciales, banners de cookies o integraciones externas no disparen el bloqueo del hilo principal ni rompan la estabilidad visual en móvil.</div></li>
        </ul>

        <h2>Mantenimiento genérico vs. administración por ingeniero informático</h2>
        <p>La diferencia real no se nota cuando todo va bien, sino el día que un plugin crítico publica una actualización defectuosa o un bot ataca tu servidor:</p>
        <div class="comparison-wrap" role="region" aria-label="Comparativa entre mantenimiento genérico e ingeniería WordPress" tabindex="0">
          <table class="comparison-table">
            <thead>
              <tr>
                <th scope="col">Tarea crítica</th>
                <th scope="col">Auto-Update / Agencia Generalista</th>
                <th scope="col">Mi Servicio de Ingeniería WordPress</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Actualización de plugins</strong></td>
                <td>Automática en producción; si rompe el checkout, te enteras cuando un cliente reclama</td>
                <td>Lectura previa de changelog + prueba en Staging + validación de logs PHP y pagos</td>
              </tr>
              <tr>
                <td><strong>Copias de seguridad</strong></td>
                <td>Alojadas en el mismo servidor o sin verificar jamás si el archivo SQL restaura bien</td>
                <td>Cifradas fuera de tu hosting (Amazon S3) con comprobación periódica de integridad</td>
              </tr>
              <tr>
                <td><strong>Defensa frente a malware</strong></td>
                <td>Instalar un plugin pesado que penaliza el TTFB y consume memoria RAM</td>
                <td>Hardening nativo en servidor (<code>.htaccess</code> / Nginx / <code>wp-config.php</code>) y control por SSH</td>
              </tr>
              <tr>
                <td><strong>Soporte e interlocución</strong></td>
                <td>Tickets de nivel 1 con respuestas copiadas y días de espera entre departamentos</td>
                <td>Trato 100 % directo conmigo por WhatsApp o correo con intervención inmediata en código</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p>Un mantenimiento preventivo bien ejecutado cuesta una fracción de lo que supone un solo día con las campañas de Google Ads enviando tráfico a una página rota o bloqueada. Y si tu web actual arrastra una plantilla obsoleta e imposible de optimizar con Elementor o Divi, podemos plantear una migración limpia desde nuestro servicio de <a href="https://walkiriaapps.com/servicios/desarrollo-wordpress/" target="_blank" rel="noopener noreferrer" style="color:var(--orange);text-decoration:underline;font-weight:600;">desarrollo WordPress a medida en Walkiria Apps</a>.</p>

        <h2>Delimitación clara: qué tareas no cubre la cuota preventiva</h2>
        <p>Para trabajar con total honestidad desde el primer día, conviene separar la administración preventiva de los proyectos evolutivos mayores (que se valoran aparte con presupuesto cerrado previo si superan la bolsa de horas de tu plan):</p>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div>Maquetación completa de nuevas páginas de venta, rediseños integrales de interfaz o trabajos de identidad gráfica.</div></li>
          <li class="checklist-item"><div>Estrategia continua de contenidos, linkbuilding o campañas de <a href="/servicios/seo-tecnico/" style="color:var(--orange);text-decoration:underline;font-weight:600;">consultoría SEO mensual</a> (disponibles como servicio específico).</div></li>
          <li class="checklist-item"><div>Programación desde cero de integraciones complejas con ERPs o funcionalidades inéditas a medida (cubiertas mediante mi servicio de <a href="/servicios/plugins-wordpress/" style="color:var(--orange);text-decoration:underline;font-weight:600;">desarrollo de plugins WordPress a medida</a>).</div></li>
          <li class="checklist-item"><div>Desinfección inicial de webs que llegan al alta ya hackeadas o caídas (requieren primero una <a href="/servicios/reparacion-wordpress-urgente/" style="color:var(--orange);text-decoration:underline;font-weight:600;">intervención urgente de limpieza</a> antes de entrar en cuota mensual).</div></li>
        </ul>

        <!-- Caso real documentado -->
        <div class="card card--dark" style="margin-top: 1.5rem; border-color: var(--orange);">
          <h3 style="color: var(--orange); margin-bottom: 0.5rem;">Evidencia real: el coste de aplazar una actualización crítica</h3>
          <p style="font-size: 0.95rem; line-height: 1.6; color: #cbd5e1; margin-bottom: 0.85rem;">Una clínica de especialidades médicas acudió de urgencia un lunes con el servidor al 99 % de CPU y redirecciones fraudulentas hacia páginas de apuestas en móviles. Un plugin desactualizado había permitido inyectar persistencia oculta en archivos <code>.user.ini</code>, <code>mu-plugins</code> y tareas cron de <code>wp_options</code> que regeneraban la infección cada pocos segundos.</p>
          <p style="font-size: 0.95rem; line-height: 1.6; color: #cbd5e1;">Con un <strong>mantenimiento preventivo supervisado</strong>, la vulnerabilidad se habría parcheado en cuanto salió el aviso de seguridad y las directivas de servidor habrían impedido la ejecución previa de PHP. Documenté todo el proceso técnico de rescate y posterior paso a mantenimiento en este <a href="/caso-exito-desinfeccion-malware-wordpress/" style="color: #FB923C; text-decoration: underline; font-weight: 600;">caso de éxito real de desinfección de malware en WordPress</a>.</p>
        </div>

      </div>

      <aside class="service-sidebar">
        <div class="card">
          <h3 style="color:var(--orange);margin-bottom:.75rem">Protege tu negocio digital</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Envíame la URL de tu WordPress o tienda WooCommerce y en qué proveedor la alojas. Reviso el estado inicial y te indico qué plan encaja con tu proyecto.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Solicitar propuesta</a>
          <a href="<?= wa_url('mantenimiento') ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp" style="width:100%;justify-content:center;margin-top:.5rem">Consultar por WhatsApp</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Resumen rápido</h3>
          <ul style="display:grid;gap:.55rem;font-size:.88rem;color:var(--text);">
            <li class="tabular-nums"><strong>Desde:</strong> 59 €/mes + IVA</li>
            <li><strong>Permanencia:</strong> Sin permanencia</li>
            <li><strong>Backups:</strong> Externos cifrados (S3)</li>
            <li><strong>Ejecución:</strong> Ingeniero por SSH / WP-CLI</li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/related-wordpress.php'; ?>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <?php
  $cta = [
    'title'      => '¿Tu WordPress está realmente protegido hoy?',
    'subtitle'   => 'Delegar el mantenimiento en un ingeniero informático cuesta mucho menos que una sola mañana de ventas o captación bloqueada por una caída.',
    'btn_label'  => 'Hablar sobre tu mantenimiento',
    'btn_href'   => '/contacto/',
    'whatsapp'   => true,
    'wa_context' => 'mantenimiento',
    'variant'    => 'dark'
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
