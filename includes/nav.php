<?php
/**
 * nav.php — Navegación principal del sitio
 */
$_nav = $page['active_nav'] ?? 'inicio';

$_services = [
    ['href' => '/servicios/seo-albacete/',            'label' => 'SEO en Albacete'],
    ['href' => '/servicios/seo-espana/',              'label' => 'SEO para España'],
    ['href' => '/servicios/auditoria-seo/',           'label' => 'Auditoría SEO'],
    ['href' => '/servicios/seo-tecnico/',             'label' => 'SEO Técnico'],
    ['href' => '/servicios/mantenimiento-wordpress/', 'label' => 'Mantenimiento WordPress'],
    ['href' => '/servicios/desarrollo-wordpress/',    'label' => 'Desarrollo WordPress'],
    ['href' => '/servicios/plugins-wordpress/',       'label' => 'Plugins a medida'],
];
?>
<header class="site-header" role="banner">
    <div class="container header-inner">
        <a href="/" class="site-logo" aria-label="Víctor Alonso SEO — Inicio">
            <span class="logo-name">Víctor Alonso</span><span class="logo-tag">SEO</span>
        </a>
        <nav class="site-nav" id="site-nav" aria-label="Navegación principal">
            <ul class="nav-list" role="list">
                <li class="nav-item">
                    <a href="/" class="nav-link<?= $_nav === 'inicio' ? ' active' : '' ?>">Inicio</a>
                </li>
                <li class="nav-item nav-item--has-dropdown">
                    <button class="nav-link nav-dropdown-toggle<?= $_nav === 'servicios' ? ' active' : '' ?>"
                        aria-expanded="false" aria-haspopup="true" aria-controls="nav-dropdown-servicios">
                        Servicios
                        <svg class="nav-chevron" aria-hidden="true" width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <ul class="nav-dropdown" id="nav-dropdown-servicios" role="list">
                        <?php foreach ($_services as $s): ?>
                        <li><a href="<?= h($s['href']) ?>" class="nav-dropdown-link"><?= h($s['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="/sobre-mi/" class="nav-link<?= $_nav === 'sobre-mi' ? ' active' : '' ?>">Sobre mí</a>
                </li>
                <li class="nav-item">
                    <a href="/casos-reales/" class="nav-link<?= $_nav === 'casos' ? ' active' : '' ?>">Casos reales</a>
                </li>
                <li class="nav-item">
                    <a href="/herramientas/" class="nav-link<?= $_nav === 'herramientas' ? ' active' : '' ?>">Herramientas</a>
                </li>
                <li class="nav-item">
                    <a href="/contacto/" class="nav-link nav-link--cta<?= $_nav === 'contacto' ? ' active' : '' ?>">Contacto</a>
                </li>
            </ul>
        </nav>
        <button class="nav-toggle" id="nav-toggle" aria-expanded="false"
            aria-controls="site-nav" aria-label="Abrir menú de navegación">
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
        </button>
    </div>
</header>
