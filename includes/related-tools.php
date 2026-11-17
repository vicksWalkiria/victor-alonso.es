<?php
$current_page = basename($_SERVER['SCRIPT_NAME']);

// Mapeo de cada herramienta a su clúster temático
$tool_data = [
    'analizador-paginas-huerfanas.php' => [
        'label' => 'Analizador Huérfanas',
        'href'  => '/herramientas/analizador-paginas-huerfanas/',
        'cat'   => 'rastreo',
        'service_label' => 'Auditoría SEO',
        'service_href'  => '/servicios/auditoria-seo/'
    ],
    'analizador-logs.php' => [
        'label' => 'Analizador de Logs',
        'href'  => '/herramientas/analizador-logs/',
        'cat'   => 'rastreo',
        'service_label' => 'Auditoría Técnica SEO',
        'service_href'  => '/servicios/seo-tecnico/'
    ],
    'extractor-sitemap.php' => [
        'label' => 'Extractor Sitemap XML',
        'href'  => '/herramientas/extractor-sitemap/',
        'cat'   => 'rastreo',
        'service_label' => 'Auditoría SEO',
        'service_href'  => '/servicios/auditoria-seo/'
    ],
    'analizador-seo.php' => [
        'label' => 'Analizador SEO URLs',
        'href'  => '/herramientas/analizador-seo/',
        'cat'   => 'rastreo',
        'service_label' => 'Auditoría SEO',
        'service_href'  => '/servicios/auditoria-seo/'
    ],
    'tester-htaccess.php' => [
        'label' => 'Tester .htaccess',
        'href'  => '/herramientas/tester-htaccess/',
        'cat'   => 'servidor',
        'service_label' => 'Servicio SEO Técnico',
        'service_href'  => '/servicios/seo-tecnico/'
    ],
    'generador-schema-local.php' => [
        'label' => 'Schema LocalBusiness',
        'href'  => '/herramientas/generador-schema-local/',
        'cat'   => 'local',
        'service_label' => 'SEO Local en Albacete',
        'service_href'  => '/contacto/'
    ],
    'auditor-seo-local-gmb.php' => [
        'label' => 'Auditor SEO Local',
        'href'  => '/herramientas/auditor-seo-local-gmb/',
        'cat'   => 'local',
        'service_label' => 'SEO Local en Albacete',
        'service_href'  => '/contacto/'
    ],
    'calculadora-wpo.php' => [
        'label' => 'Calculadora WPO',
        'href'  => '/herramientas/calculadora-wpo/',
        'cat'   => 'rendimiento',
        'service_label' => 'Mantenimiento WordPress',
        'service_href'  => '/servicios/mantenimiento-wordpress/'
    ],
    'extractor-entidades.php' => [
        'label' => 'Extractor Semántico',
        'href'  => '/herramientas/extractor-entidades/',
        'cat'   => 'contenido',
        'service_label' => 'SEO para Empresas',
        'service_href'  => '/servicios/seo-espana/'
    ],
    'generador-informe-gsc.php' => [
        'label' => 'Generador GSC',
        'href'  => '/herramientas/generador-informe-gsc/',
        'cat'   => 'contenido',
        'service_label' => 'Auditoría SEO',
        'service_href'  => '/servicios/auditoria-seo/'
    ],
    'editor-metadatos-imagenes.php' => [
        'label' => 'Editor EXIF GPS',
        'href'  => '/herramientas/editor-metadatos-imagenes/',
        'cat'   => 'privacidad',
        'service_label' => 'Auditoría SEO',
        'service_href'  => '/servicios/auditoria-seo/'
    ],
    'auditor-cookies.php' => [
        'label' => 'Auditor Cookies RGPD',
        'href'  => '/herramientas/auditor-cookies/',
        'cat'   => 'privacidad',
        'service_label' => 'Mantenimiento WordPress',
        'service_href'  => '/servicios/mantenimiento-wordpress/'
    ]
];

// Obtener datos de la herramienta actual (o fallback si no está)
$current_cat = $tool_data[$current_page]['cat'] ?? 'rastreo';
$service_label = $tool_data[$current_page]['service_label'] ?? 'Auditoría SEO Avanzada';
$service_href  = $tool_data[$current_page]['service_href'] ?? '/servicios/auditoria-seo/';

// Seleccionar herramientas relacionadas (misma categoría, excluyendo la actual, max 3)
$related_tools = [];
foreach ($tool_data as $file => $data) {
    if ($file !== $current_page && $data['cat'] === $current_cat) {
        $related_tools[] = $data;
    }
}
// Si hay menos de 2 en la misma categoría, rellenar con algunas genéricas
if (count($related_tools) < 2) {
    foreach ($tool_data as $file => $data) {
        if ($file !== $current_page && !in_array($data, $related_tools)) {
            $related_tools[] = $data;
            if (count($related_tools) >= 3) break;
        }
    }
}
$related_tools = array_slice($related_tools, 0, 3);
?>
<!-- Callout a Agencia y Consultoría SEO en Albacete -->
<div style="background: var(--orange-light); border: 1px solid rgba(200, 75, 9, 0.25); border-radius: 12px; padding: 1.25rem 1.5rem; margin-top: 3rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
  <div>
    <h3 style="color: var(--black); font-size: 1.05rem; font-weight: 700; margin-bottom: 0.25rem;">¿Prefieres que audite e implemente los cambios por ti?</h3>
    <p style="color: var(--muted); font-size: 0.88rem; margin: 0;">Diagnóstico técnico en Search Console, logs y código por Víctor Alonso (ingeniero informático y consultor SEO en Albacete, sin intermediarios).</p>
  </div>
  <a href="/contacto/" class="btn btn--primary" style="font-size: 0.85rem; padding: 0.65rem 1.25rem; min-height: 44px; white-space: nowrap; border-radius: 8px; font-weight: 700;">
    Solicitar diagnóstico SEO →
  </a>
</div>

<div class="tool-interlinking" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
  <h3 style="color: var(--black); font-size: 1.1rem; margin-bottom: 1.15rem;">Sigue explorando:</h3>
  
  <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
    
    <!-- Enlaces comerciales y de negocio -->
    <a href="<?= htmlspecialchars($service_href) ?>" class="btn btn--primary" style="font-size:0.85rem; padding: 0.55rem 1rem; min-height: 44px; border-radius: 6px; font-weight: 700;">
      <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      <?= htmlspecialchars($service_label) ?>
    </a>

    <a href="/" class="btn btn--secondary" style="font-size:0.85rem; padding: 0.55rem 1rem; min-height: 44px; border-radius: 6px; font-weight: 600; color: var(--orange); border-color: rgba(200, 75, 9, 0.3); background: var(--orange-light);">
      <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      Agencia SEO Albacete
    </a>
    
    <a href="/casos-reales/" class="btn btn--secondary" style="font-size:0.85rem; padding: 0.55rem 1rem; min-height: 44px; border-radius: 6px; font-weight: 600; color: var(--orange); border-color: rgba(200, 75, 9, 0.3); background: var(--orange-light);">
      <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
      Ver Casos Reales
    </a>

    <!-- Herramientas relacionadas selectivas -->
    <?php foreach ($related_tools as $tool): ?>
      <a href="<?= htmlspecialchars($tool['href']) ?>" class="btn btn--secondary" style="font-size:0.85rem; padding: 0.55rem 0.95rem; min-height: 44px; font-weight: 500; border-radius: 6px;">
        <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.7;"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        <?= htmlspecialchars($tool['label']) ?>
      </a>
    <?php endforeach; ?>

    <!-- Vuelta al Hub -->
    <a href="/herramientas/" style="font-size: 0.85rem; color: var(--muted); font-weight: 600; text-decoration: underline; margin-left: auto;">
      Volver a todas las herramientas →
    </a>

  </div>
</div>
