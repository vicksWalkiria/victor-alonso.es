<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

$page = page_config([
  'title'        => 'Mensaje recibido | Gracias por contactar',
  'description'  => 'He recibido tu mensaje. Te respondo en breve con una primera valoración.',
  'canonical'    => '/gracias/',
  'body_class'   => 'page-gracias',
  'schema_types' => [],
  'noindex'      => true,
  'active_nav'   => 'contacto',
  'breadcrumbs'  => [],
]);
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <div class="gracias-wrap">
    <div>
      <div class="gracias-icon" aria-hidden="true">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <h1 style="font-size:2rem;margin-bottom:.75rem">Mensaje recibido</h1>
      <p style="color:var(--muted);max-width:480px;margin:0 auto 2rem">
        Gracias por escribirme. Lo leeré con atención y te responderé en un plazo razonable con una primera valoración sobre tu situación.
      </p>
      <div class="btn-group" style="justify-content:center">
        <a href="/" class="btn btn--secondary">Volver al inicio</a>
        <a href="/#servicios" class="btn btn--primary">Ver servicios</a>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
