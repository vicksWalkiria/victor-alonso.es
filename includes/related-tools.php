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
        'service_href'  => '/servicios/seo-albacete/'
    ],
    'auditor-seo-local-gmb.php' => [
        'label' => 'Auditor SEO Local',
        'href'  => '/herramientas/auditor-seo-local-gmb/',
        'cat'   => 'local',
        'service_label' => 'SEO Local en Albacete',
        'service_href'  => '/servicios/seo-albacete/'
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
<!-- Callout a Consultor SEO Freelance en Albacete -->
<div style="background: linear-gradient(135deg, rgba(232, 104, 26, 0.08) 0%, rgba(232, 104, 26, 0.02) 100%); border: 1px solid rgba(232, 104, 26, 0.25); border-radius: 12px; padding: 1.25rem 1.5rem; margin-top: 3rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
  <div>
    <h4 style="color: var(--black); font-size: 1.05rem; font-weight: 700; margin-bottom: 0.25rem;">¿Buscas un consultor SEO freelance en Albacete?</h4>
    <p style="color: var(--muted); font-size: 0.88rem; margin: 0;">Diagnóstico técnico e implementación directa por Víctor Alonso (ingeniero informático independiente, sin agencias ni intermediarios).</p>
  </div>
  <a href="/servicios/seo-albacete/" class="btn btn--primary" style="font-size: 0.85rem; padding: 0.6rem 1.25rem; white-space: nowrap; border-radius: 8px; font-weight: 700;">
    Consultoría SEO en Albacete →
  </a>
</div>

<div class="tool-interlinking" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
  <h4 style="color: var(--black); font-size: 1.15rem; margin-bottom: 1.25rem;">Sigue explorando:</h4>
  
  <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
    
    <!-- Enlaces comerciales y de negocio -->
    <a href="<?= htmlspecialchars($service_href) ?>" class="btn btn--primary" style="font-size:0.85rem; padding: 0.5rem 1rem; height: auto; border-radius: 6px; font-weight: 700;">
      <i class="fa-solid fa-briefcase" style="margin-right:0.4rem;"></i> <?= htmlspecialchars($service_label) ?>
    </a>

    <a href="/servicios/seo-albacete/" class="btn btn--secondary" style="font-size:0.85rem; padding: 0.5rem 1rem; height: auto; border-radius: 6px; font-weight: 600; color: var(--orange); border-color: rgba(232, 104, 26, 0.3); background: rgba(232, 104, 26, 0.05);">
      <i class="fa-solid fa-location-dot" style="margin-right:0.4rem;"></i> SEO Local Albacete
    </a>
    
    <a href="/casos-reales/" class="btn btn--secondary" style="font-size:0.85rem; padding: 0.5rem 1rem; height: auto; border-radius: 6px; font-weight: 600; color: var(--orange); border-color: rgba(232, 104, 26, 0.3); background: rgba(232, 104, 26, 0.05);">
      <i class="fa-solid fa-chart-line" style="margin-right:0.4rem;"></i> Ver Casos Reales
    </a>

    <!-- Herramientas relacionadas selectivas -->
    <?php foreach ($related_tools as $tool): ?>
      <a href="<?= htmlspecialchars($tool['href']) ?>" class="btn btn--secondary" style="font-size:0.85rem; padding: 0.5rem 0.9rem; height: auto; font-weight: 500; border-radius: 6px;">
        <i class="fa-solid fa-wrench" style="margin-right:0.3rem; opacity:0.6;"></i> <?= htmlspecialchars($tool['label']) ?>
      </a>
    <?php endforeach; ?>

    <!-- Vuelta al Hub -->
    <a href="/herramientas/" style="font-size: 0.85rem; color: var(--muted); font-weight: 600; text-decoration: underline; margin-left: auto;">
      Volver a todas las herramientas →
    </a>

  </div>
</div>
