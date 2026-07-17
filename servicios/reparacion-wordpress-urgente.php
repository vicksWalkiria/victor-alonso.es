<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'        => 'Reparación WordPress urgente y limpieza de malware',
  'description'  => 'Recuperación de WordPress hackeado, errores críticos, caídas y malware. Contención, limpieza, hardening y soporte técnico directo.',
  'canonical'    => '/servicios/reparacion-wordpress-urgente/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service', 'FAQPage'],
  'service_data' => [
      '@id'           => '/servicios/reparacion-wordpress-urgente/#service',
      'name'          => 'Reparación WordPress urgente',
      'alternateName' => [
          'Limpieza de malware WordPress',
          'Recuperar WordPress hackeado',
          'Intervención urgente WordPress'
      ],
      'serviceType'   => 'Soporte Técnico Informático',
      'areaServed'    => [
          ['@type' => 'Country', 'name' => 'España']
      ]
  ],
  'active_nav'   => 'servicios',
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'Reparación WordPress urgente', 'url' => ''],
  ],
  'faq_items' => [
    [
      'q' => '¿En cuánto tiempo estará arreglada mi web?',
      'a' => 'No puedo prometer una resolución en 24 o 48 horas sin haber analizado el problema antes. El primer paso siempre es contener la incidencia y aislar la web para que no empeore. Dependiendo de la gravedad del malware o el error crítico, la reparación completa puede tomar desde unas horas hasta varios días.'
    ],
    [
      'q' => '¿Cuál es el precio de reparar una web infectada?',
      'a' => 'No doy presupuestos fijos a ciegas. Cada infección y error del servidor es diferente. Necesito revisar de forma preliminar el alcance del problema, el estado de tus copias de seguridad (backups) y los accesos disponibles para poder estimar el trabajo técnico necesario.'
    ],
    [
      'q' => '¿Garantizas que no volverán a hackear mi web?',
      'a' => 'Garantizo que entregaré tu web limpia de código malicioso conocido en el momento de la entrega, y con las vulnerabilidades detectadas parcheadas (lo que llamamos hardening). Sin embargo, la seguridad al 100% no existe en internet. Por ello, una vez reparada, es absolutamente vital mantener un plan de prevención y actualización constante.'
    ]
  ]
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Reparación urgente de WordPress y limpieza de <span>malware</span></h1>
      <p class="page-hero-desc">¿Tu web está caída, muestra un error crítico, redirige a sitios de spam o Google la marca como peligrosa? Si estás ante una crisis técnica con tu WordPress, respira y actúa con cabeza. El primer paso es contener el daño.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">

        <!-- INTRO -->
        <div class="alert alert--danger" style="margin-bottom: 2.5rem;">
          <strong>No prometo milagros inmediatos, prometo un método seguro.</strong><br>
          Si tu negocio está paralizado, tu instinto será buscar a alguien que prometa arreglarlo en "1 hora por 50€". Las reparaciones rápidas y reinstalaciones sin diagnóstico suelen borrar evidencias vitales y terminar en una nueva infección a los pocos días. Mi primer objetivo es analizar el vector de entrada y contener la amenaza.
        </div>

        <h2>Problemas urgentes que puedo revisar</h2>
        <div style="display:grid;gap:1.5rem;margin:1.5rem 0 2.5rem;">
          <article>
            <h3 style="color:var(--orange);font-size:1.15rem;margin-bottom:0.5rem;">WordPress hackeado o con malware</h3>
            <p style="margin:0;font-size:0.95rem;">Código malicioso ofuscado inyectado en el core de WordPress, archivos del tema o base de datos debido a vulnerabilidades no parcheadas.</p>
          </article>
          <article>
            <h3 style="color:var(--orange);font-size:1.15rem;margin-bottom:0.5rem;">Error crítico o pantalla en blanco (WSoD)</h3>
            <p style="margin:0;font-size:0.95rem;">La famosa "White Screen of Death" o un error 500 del servidor tras una actualización fallida que bloquea el acceso total a tu web y a tu panel de administración.</p>
          </article>
          <article>
            <h3 style="color:var(--orange);font-size:1.15rem;margin-bottom:0.5rem;">Redirecciones a páginas de spam</h3>
            <p style="margin:0;font-size:0.95rem;">Tráfico móvil o visitas orgánicas de Google que son redirigidas sin tu permiso a webs de apuestas, fraudes, farmacias ilegales o contenido malicioso.</p>
          </article>
          <article>
            <h3 style="color:var(--orange);font-size:1.15rem;margin-bottom:0.5rem;">Web caída tras actualizar (Incompatibilidad)</h3>
            <p style="margin:0;font-size:0.95rem;">Rupturas fatales de compatibilidad entre la versión de PHP del servidor, el tema activo y las últimas versiones de plugins de tu comercio electrónico.</p>
          </article>
          <article>
            <h3 style="color:var(--orange);font-size:1.15rem;margin-bottom:0.5rem;">Aviso de sitio peligroso en Google</h3>
            <p style="margin:0;font-size:0.95rem;">Google Safe Browsing ha detectado la infección y ha bloqueado el acceso a tu web con una pantalla roja ("El sitio web al que vas a acceder es engañoso"), hundiendo tu tráfico y tu reputación.</p>
          </article>
        </div>

        <h2>Qué hago primero para contener el problema</h2>
        <p>Ante una crisis técnica, la precipitación destruye evidencias. Mi primera intervención no es reinstalar WordPress indiscriminadamente, sino <strong>preservar, aislar y contener</strong>:</p>
        <ul class="checklist" style="margin:1rem 0 2.5rem">
          <li class="checklist-item"><div><strong>Preservación:</strong> Realizar un backup "congelado" (snapshot) del estado actual del servidor corrupto antes de tocar o borrar ningún archivo.</div></li>
          <li class="checklist-item"><div><strong>Contención:</strong> Deshabilitar la ejecución de scripts maliciosos, bloquear el acceso público temporalmente si la web compromete gravemente a los usuarios y forzar el cierre de todas las sesiones de usuario activas.</div></li>
        </ul>

        <h2>Proceso de diagnóstico y recuperación</h2>
        <p>Una vez contenida la amenaza o mitigado el error crítico, el trabajo técnico se divide en fases claras:</p>
        <ul class="checklist" style="margin:1rem 0 2.5rem">
          <li class="checklist-item"><div><strong>Investigación Forense:</strong> Revisión de logs de acceso HTTP (Apache/Nginx) y logs de errores de PHP para descubrir <em>cómo</em> entró el atacante (vector de ataque) o <em>qué</em> proceso exacto desencadenó el fallo.</div></li>
          <li class="checklist-item"><div><strong>Limpieza manual:</strong> Extraer el malware a nivel de código sin corromper la funcionalidad legítima de tu diseño o tienda online. No uso limpiadores automáticos destructivos.</div></li>
          <li class="checklist-item"><div><strong>Restauración selectiva:</strong> Volcar copias limpias de los archivos del núcleo (core) de WordPress y repositorios oficiales de plugins, manteniendo tus imágenes y base de datos saneada.</div></li>
        </ul>

        <h2>Qué incluye la limpieza y el hardening (Bastionado)</h2>
        <p>Limpiar el malware es solo el 50% del trabajo. Si dejas la misma puerta abierta, el script automatizado del atacante volverá a entrar mañana por la mañana. Por eso implemento medidas de <strong>Hardening o Bastionado</strong>:</p>
        <ul class="checklist" style="margin:1rem 0 2.5rem">
          <li class="checklist-item"><div>Cambio masivo, forzado y criptográfico de credenciales (Claves de Base de datos, FTP, cuentas de WordPress y Salts).</div></li>
          <li class="checklist-item"><div>Actualización forzada pero segura de todas las dependencias vulnerables detectadas.</div></li>
          <li class="checklist-item"><div>Restricción estricta de permisos de ejecución y escritura a nivel de servidor (archivos y carpetas críticas).</div></li>
          <li class="checklist-item"><div>Instalación y configuración de un Firewall de Aplicación Web (WAF) si el entorno de hosting lo soporta.</div></li>
        </ul>

        <h2>Qué ocurre después de recuperar la web</h2>
        <p>Una vez la plataforma vuelve a ser funcional, estable y limpia, te asisto técnicamente para pedir la reconsideración del dominio a Google (si hubo alerta roja de Safe Browsing) y acelerar el re-rastreo para limpiar las URLs spam indexadas.</p>
        <p>A partir de ahí, la responsabilidad recae en ti: un incidente de esta magnitud con este lucro cesante no puede volver a ocurrir en tu empresa. Es el momento de dejar de improvisar e invertir en un <a href="/servicios/mantenimiento-wordpress/" style="color:var(--orange);font-weight:600;text-decoration:underline;">mantenimiento preventivo profesional recurrente</a>.</p>

        <h2>Caso real de WordPress infectado</h2>
        <div class="card card--dark" style="margin-top: 1.5rem; border-color: var(--orange);">
          <p style="font-size: 0.95rem; line-height: 1.5; color: #cbd5e1; margin-bottom: 1rem;">Lee mi caso de estudio documentado sobre cómo resolví una crisis real de malware en un cliente B2B cuya web fue hackeada el fin de semana. Aprenderás por qué las copias de seguridad alojadas en su propio servidor no sirvieron para absolutamente nada frente al rescate.</p>
          <a href="/casos-reales/#caso-1" class="btn btn--secondary" style="font-size:0.9rem; color:#ffffff; border-color:var(--orange);">Leer caso de estudio: WordPress infectado con malware</a>
        </div>

        <h2>Qué accesos necesito para empezar</h2>
        <p style="margin-bottom: 2rem;">Para poder analizar con rigor la magnitud del problema y auditar las inyecciones de código a bajo nivel, necesito que me proporciones credenciales de administrador de tu <strong>Panel de Hosting (cPanel, Plesk, etc.)</strong>, acceso a los archivos mediante <strong>FTP/SFTP</strong>, acceso a la <strong>Base de Datos (phpMyAdmin)</strong> y un usuario con privilegios de <strong>Administrador en tu panel de WordPress</strong>.</p>

      </div>
      <aside class="service-sidebar">
        <div class="card" style="border:2px solid #e74c3c;">
          <h3 style="color:#e74c3c;margin-bottom:.75rem;font-size:1.25rem;">Solicitar ayuda de emergencia</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Si necesitas intervención urgente, envíame la URL afectada, qué está fallando exactamente y si dispones de alguna copia de seguridad reciente descargable.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center;background:#e74c3c;border-color:#c0392b">Escribir por correo urgente</a>
          <a href="https://wa.me/<?= SITE_PHONE_RAW ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp" style="width:100%;justify-content:center;margin-top:.5rem">WhatsApp Urgencias</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Servicios relacionados</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/mantenimiento-wordpress/" style="color:var(--orange)">→ Mantenimiento preventivo</a></li>
            <li><a href="/servicios/seo-tecnico/" style="color:var(--orange)">→ Auditoría SEO técnica</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <?php
  $cta = [
    'title' => '¿Estás ante una incidencia crítica en tu web?', 
    'subtitle' => 'No dejes que pasen las horas inútilmente. Cada minuto que la web está infectada o caída empeora tu reputación ante Google y ante tus clientes.', 
    'btn_label' => 'Contactar para diagnóstico', 
    'btn_href' => '/contacto/', 
    'whatsapp' => true, 
    'variant' => 'dark'
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
