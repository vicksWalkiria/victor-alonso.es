<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'        => 'Mantenimiento WordPress y Soporte Técnico',
  'description'  => 'Servicio de mantenimiento WordPress: actualizaciones, backups automatizados, seguridad, limpieza de malware, optimización de rendimiento y soporte técnico continuo.',
  'canonical'    => '/servicios/mantenimiento-wordpress/',
  'body_class'   => 'page-servicio',
  'schema_types' => ['Service'],
  'service_data' => [
      '@id'         => '/servicios/mantenimiento-wordpress/#service',
      'name'        => 'Mantenimiento WordPress',
      'serviceType' => 'Mantenimiento Web'
  ],
  'active_nav'   => 'servicios',
  'breadcrumbs'  => [
    ['label' => 'Servicios', 'url' => '/#servicios'],
    ['label' => 'Mantenimiento WordPress', 'url' => ''],
  ],
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Mantenimiento WordPress: tu web actualizada, segura y <span>funcionando</span></h1>
      <p class="page-hero-desc">Un WordPress sin mantenimiento es una superficie de ataque. Los plugins desactualizados, las vulnerabilidades conocidas y la falta de backups son los principales motivos de hackeos y pérdidas de datos.</p>
    </div>
  </section>

  <section class="section">
    <div class="container service-detail">
      <div class="service-content">

        <h2>¿Qué incluye el mantenimiento WordPress?</h2>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item"><div><strong>Actualizaciones controladas</strong> WordPress core, temas y plugins se actualizan de forma regular y controlada, con prueba previa en entorno de staging cuando hay actualizaciones de riesgo. No se actualiza todo a la vez sin revisar compatibilidades.</div></li>
          <li class="checklist-item"><div><strong>Backups automatizados</strong> Copias de seguridad completas (base de datos + archivos) con almacenamiento externo al propio servidor. Un backup en el mismo servidor que la web no es un backup real.</div></li>
          <li class="checklist-item"><div><strong>Monitorización de uptime</strong> Alertas inmediatas si la web deja de responder. Tiempo de reacción reducido frente a caídas de servidor o errores críticos.</div></li>
          <li class="checklist-item"><div><strong>Seguridad activa</strong> Revisión de usuarios con permisos excesivos, contraseñas débiles, archivos con permisos incorrectos, cabeceras de seguridad HTTP y accesos sospechosos en logs.</div></li>
          <li class="checklist-item"><div><strong>Limpieza de malware</strong> Si la web ha sido comprometida: análisis completo del servidor, eliminación del código malicioso, revisión de backdoors y restauración de la funcionalidad. Gestión ante Google Safe Browsing si la web ha sido marcada.</div></li>
          <li class="checklist-item"><div><strong>Revisión y optimización de plugins</strong> Un WordPress con 40 plugins activos tiene 40 superficies de ataque y 40 fuentes potenciales de conflicto y lentitud. Reviso qué plugins son necesarios, cuáles se pueden sustituir por código propio y cuáles simplemente sobran.</div></li>
          <li class="checklist-item"><div><strong>WPO y rendimiento</strong> Revisión periódica de Core Web Vitals, caché, compresión, imágenes y carga de recursos. El rendimiento no es algo que se configure una vez y se olvida: los plugins nuevos pueden romper lo que funcionaba.</div></li>
          <li class="checklist-item"><div><strong>Soporte técnico</strong> Canal de comunicación directo para incidencias. Respuesta humana, no tickets automáticos.</div></li>
        </ul>

        <h2>Lo que no hago en mantenimiento</h2>
        <ul class="checklist" style="margin:1rem 0 2rem">
          <li class="checklist-item">No instalo plugins de seguridad "todo en uno" que ralentizan la web y dan falsa sensación de control.</li>
          <li class="checklist-item">No actualizo plugins sin revisar el changelog y el impacto potencial.</li>
          <li class="checklist-item">No guardo backups solo en el servidor donde está la web.</li>
          <li class="checklist-item">No ignoro los avisos de Search Console ni los errores en el log de PHP.</li>
        </ul>

        <h2>¿Tu WordPress ha sido hackeado?</h2>
        <p>Es más común de lo que parece. Los síntomas más frecuentes son: redirecciones a webs externas, contenido spam indexado en Google, alertas de Chrome/Google Safe Browsing, o simplemente la web que deja de funcionar sin razón aparente.</p>
        <p>Si estás en esta situación, escríbeme. El primer paso es contener el daño, no el diagnóstico completo.</p>

      </div>
      <aside class="service-sidebar">
        <div class="card">
          <h3 style="color:var(--orange);margin-bottom:.75rem">¿Necesitas mantenimiento?</h3>
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:1.25rem">Cuéntame tu situación actual. Si tienes una urgencia, escríbeme directamente por WhatsApp.</p>
          <a href="/contacto/" class="btn btn--primary" style="width:100%;justify-content:center">Contactar</a>
          <a href="https://wa.me/<?= SITE_PHONE_RAW ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp" style="width:100%;justify-content:center;margin-top:.5rem">WhatsApp urgencias</a>
        </div>
        <div class="card" style="margin-top:1rem">
          <h3 style="margin-bottom:.75rem">Relacionado</h3>
          <ul style="display:grid;gap:.5rem;font-size:.88rem">
            <li><a href="/servicios/desarrollo-wordpress/" style="color:var(--orange)">→ Desarrollo WordPress</a></li>
            <li><a href="/servicios/plugins-wordpress/" style="color:var(--orange)">→ Plugins a medida</a></li>
            <li><a href="/casos-reales/" style="color:var(--orange)">→ Casos reales</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  <?php
  $cta = ['title' => '¿Tu WordPress está actualizado y con backups?', 'subtitle' => 'Si no tienes respuesta clara a esas dos preguntas, merece la pena que hablemos.', 'btn_label' => 'Hablar sobre mantenimiento', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
