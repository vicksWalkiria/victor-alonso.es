<?php
/**
 * related-wordpress.php — Clúster de interlinking de servicios y evidencia técnica WordPress
 * Cumple DESIGN.md: iconos SVG inline stroke-width="2", cero emojis, áreas táctiles >= 44px.
 */
$_current_canonical = $page['canonical'] ?? '';

$_wp_cluster_items = [
    [
        'canonical' => '/servicios/reparacion-wordpress-urgente/',
        'title'     => 'Reparación WordPress Urgente',
        'desc'      => 'Intervención de emergencia por SSH para limpiar malware persistente, resolver errores 500 o pantalla blanca y levantar avisos de Google Safe Browsing.',
        'cta'       => 'Ver servicio de urgencia →',
        'svg'       => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
    ],
    [
        'canonical' => '/servicios/mantenimiento-wordpress/',
        'title'     => 'Mantenimiento WordPress Empresas',
        'desc'      => 'Prevención mensual sin permanencia desde 59 €/mes: copias externas cifradas en Amazon S3, pruebas en Staging, hardening y monitorización 24/7.',
        'cta'       => 'Comparar planes desde 59 € →',
        'svg'       => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>',
    ],
    [
        'canonical' => '/caso-exito-desinfeccion-malware-wordpress/',
        'title'     => 'Caso Real: Desinfección Forense',
        'desc'      => 'Auditoría documentada paso a paso: cómo erradiqué backdoors ocultas en .user.ini, mu-plugins y crons de base de datos en un servidor al 99 % de CPU.',
        'cta'       => 'Leer estudio forense →',
        'svg'       => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
    ],
    [
        'canonical' => '/servicios/plugins-wordpress/',
        'title'     => 'Plugins WordPress a Medida',
        'desc'      => 'Programación en PHP nativo, integraciones con APIs externas, automatizaciones y extensiones ligeras para WooCommerce HPOS desde 150 €.',
        'cta'       => 'Ver desarrollo de plugins →',
        'svg'       => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
    ],
];

$_wp_visible_items = array_values(array_filter($_wp_cluster_items, static function (array $item) use ($_current_canonical): bool {
    return $item['canonical'] !== $_current_canonical;
}));
$_wp_visible_items = array_slice($_wp_visible_items, 0, 3);
?>
<section class="section section--alt" aria-labelledby="wp-cluster-heading">
  <div class="container">
    <h2 class="section-title" id="wp-cluster-heading">Servicios WordPress relacionados</h2>
    <p class="section-intro" style="margin-bottom: 2rem;">Seguridad forense, prevención continua e ingeniería en código para instalaciones WordPress y tiendas WooCommerce críticas.</p>

    <div class="cards-grid">
      <?php foreach ($_wp_visible_items as $_wp_item): ?>
      <article class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
        <div>
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $_wp_item['svg'] ?></svg>
          </div>
          <h3><?= h($_wp_item['title']) ?></h3>
          <p><?= h($_wp_item['desc']) ?></p>
        </div>
        <a href="<?= h($_wp_item['canonical']) ?>" class="card-link" style="min-height:44px; align-items:center;"><?= h($_wp_item['cta']) ?></a>
      </article>
      <?php endforeach; ?>
    </div>

    <div style="margin-top: 1.75rem; padding: 1.15rem 1.5rem; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
      <p style="margin: 0; font-size: 0.92rem; color: var(--text);">
        <strong>¿Necesitas crear una web o tienda WordPress nueva desde cero sin plantillas lentas?</strong> Realizamos el diseño y desarrollo de temas en PHP nativo orientados a Core Web Vitals desde nuestra división de software.
      </p>
      <a href="https://walkiriaapps.com/servicios/desarrollo-wordpress/" target="_blank" rel="noopener noreferrer" class="btn btn--secondary" style="font-size: 0.88rem; padding: 0.6rem 1.15rem; min-height: 44px;">
        Desarrollo WordPress en Walkiria Apps →
      </a>
    </div>
  </div>
</section>
