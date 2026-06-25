<?php
$current_page = basename($_SERVER['SCRIPT_NAME']);

$tools_list = [
    [
        'file' => 'analizador-logs.php',
        'href' => '/herramientas/analizador-logs/',
        'label' => 'Analizador de Logs HTTP'
    ],
    [
        'file' => 'extractor-sitemap.php',
        'href' => '/herramientas/extractor-sitemap/',
        'label' => 'Extractor de Sitemap XML'
    ],
    [
        'file' => 'calculadora-wpo.php',
        'href' => '/herramientas/calculadora-wpo/',
        'label' => 'Calculadora de Impacto WPO'
    ],
    [
        'file' => 'auditor-cookies.php',
        'href' => '/herramientas/auditor-cookies/',
        'label' => 'Auditor de Cookies RGPD'
    ],
    [
        'file' => 'generador-schema-local.php',
        'href' => '/herramientas/generador-schema-local/',
        'label' => 'Generador de Schema Local'
    ],
    [
        'file' => 'extractor-entidades.php',
        'href' => '/herramientas/extractor-entidades/',
        'label' => 'Extractor de Entidades'
    ],
    [
        'file' => 'tester-htaccess.php',
        'href' => '/herramientas/tester-htaccess/',
        'label' => 'Tester de Reglas .htaccess'
    ],
    [
        'file' => 'auditor-seo-local-gmb.php',
        'href' => '/herramientas/auditor-seo-local-gmb/',
        'label' => 'Auditor SEO Local GMB'
    ],
    [
        'file' => 'analizador-seo.php',
        'href' => '/herramientas/analizador-seo/',
        'label' => 'Analizador SEO'
    ],
    [
        'file' => 'generador-informe-gsc.php',
        'href' => '/herramientas/generador-informe-gsc/',
        'label' => 'Generador GSC PDF'
    ],
    [
        'file' => 'editor-metadatos-imagenes.php',
        'href' => '/herramientas/editor-metadatos-imagenes/',
        'label' => 'Editor de Metadatos EXIF'
    ]
];
?>
<div class="tool-interlinking" style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border);">
  <h4 style="color: var(--black); font-size: 1.1rem; margin-bottom: 1.25rem;">Otras herramientas SEO técnicas gratuitas:</h4>
  <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
    <?php foreach ($tools_list as $tool): ?>
      <?php if ($tool['file'] !== $current_page): ?>
        <a href="<?= htmlspecialchars($tool['href']) ?>" class="btn btn--secondary" style="font-size:0.82rem; padding: 0.5rem 0.9rem; height: auto; font-weight: 500; border-radius: 6px;"><?= htmlspecialchars($tool['label']) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
