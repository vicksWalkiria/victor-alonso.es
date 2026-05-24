<?php
/**
 * header.php — Cabecera HTML común
 * Requiere: $page = page_config([...]) ya definido en la página que lo incluye.
 */
$_full_title = h($page['title']) . ' | ' . h(SITE_NAME);
$_canonical  = SITE_URL . $page['canonical'];
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php if (GA_MEASUREMENT_ID !== ''): ?>
    <!-- GA4: preconnect solo; el script se carga diferido al final para no penalizar LCP/TBT -->
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <link rel="preconnect" href="https://www.google-analytics.com" crossorigin>
<?php endif; ?>

    <title><?= $_full_title ?></title>
    <meta name="description" content="<?= h($page['description']) ?>">
    <link rel="canonical" href="<?= h($_canonical) ?>">
<?php if ($page['noindex']): ?>
    <meta name="robots" content="noindex, follow">
<?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= h($_canonical) ?>">
    <meta property="og:title" content="<?= h($page['title']) ?>">
    <meta property="og:description" content="<?= h($page['description']) ?>">
    <meta property="og:image" content="<?= h($page['og_image']) ?>">
    <meta property="og:locale" content="es_ES">
    <meta property="og:site_name" content="<?= h(SITE_NAME) ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="<?= h(SITE_TWITTER) ?>">
    <meta name="twitter:title" content="<?= h($page['title']) ?>">
    <meta name="twitter:description" content="<?= h($page['description']) ?>">
    <meta name="twitter:image" content="<?= h($page['og_image']) ?>">

    <!-- CSS con Cache Busting dinámico -->
    <?php 
    $_css_path = dirname(__DIR__) . '/assets/css/styles.css';
    $_css_version = file_exists($_css_path) ? filemtime($_css_path) : time();
    ?>
    <link rel="preload" href="/assets/css/styles.css?v=<?= $_css_version ?>" as="style">
    <link rel="stylesheet" href="/assets/css/styles.css?v=<?= $_css_version ?>">

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">

<?php if (!empty($page['map'])): ?>
    <!-- Leaflet CSS y JS — solo en páginas con mapa -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous" defer></script>
<?php endif; ?>

    <!-- JSON-LD Schemas -->
    <?php render_schemas($page); ?>
</head>
<body class="<?= h($page['body_class']) ?>"<?= GA_MEASUREMENT_ID !== '' ? ' data-ga-id="' . h(GA_MEASUREMENT_ID) . '"' : '' ?>>

<?php require __DIR__ . '/nav.php'; ?>
