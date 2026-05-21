<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

$page = page_config([
  'title'        => 'Contacto | Cuéntame qué ocurre con tu web',
  'description'  => 'Contacta con Víctor Alonso para servicios de SEO, desarrollo WordPress o mantenimiento web. Teléfono, WhatsApp o formulario. Albacete y toda España.',
  'canonical'    => '/contacto/',
  'body_class'   => 'page-contacto',
  'schema_types' => ['ContactPage'],
  'active_nav'   => 'contacto',
  'map'          => true,
  'breadcrumbs'  => [
    ['label' => 'Contacto', 'url' => ''],
  ],
]);
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumbs.php';
?>
<main id="main">

  <section class="page-hero" aria-labelledby="contacto-h1">
    <div class="container">
      <h1 id="contacto-h1">Cuéntame qué ocurre con tu web</h1>
      <p class="page-hero-desc">Te respondo con una primera valoración. Sin compromiso. Sin formularios de 20 campos. Solo cuéntame el problema.</p>
    </div>
  </section>

  <section class="section">
    <div class="container contact-grid">

      <div class="contact-form-wrap">
        <h2 style="margin-bottom:1.5rem">Envíame un mensaje</h2>
        <form
          id="contact-form"
          action="<?= h(FORMSPREE) ?>"
          method="POST"
          novalidate
          aria-label="Formulario de contacto"
        >
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="nombre">Nombre <span aria-hidden="true">*</span></label>
              <input class="form-input" type="text" id="nombre" name="nombre" required autocomplete="name" placeholder="Tu nombre">
            </div>
            <div class="form-group">
              <label class="form-label" for="email">Email <span aria-hidden="true">*</span></label>
              <input class="form-input" type="email" id="email" name="email" required autocomplete="email" placeholder="tu@email.com">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="telefono">Teléfono <span style="font-weight:400;color:var(--muted)">(opcional)</span></label>
              <input class="form-input" type="tel" id="telefono" name="telefono" autocomplete="tel" placeholder="+34 600 000 000">
            </div>
            <div class="form-group">
              <label class="form-label" for="web">Tu web <span style="font-weight:400;color:var(--muted)">(opcional)</span></label>
              <input class="form-input" type="url" id="web" name="web" placeholder="https://tuweb.com">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="tipo">¿Qué necesitas?</label>
            <select class="form-select" id="tipo" name="tipo_proyecto">
              <option value="">Selecciona una opción...</option>
              <option value="seo-albacete">SEO en Albacete</option>
              <option value="seo-espana">SEO para España</option>
              <option value="auditoria-seo">Auditoría SEO</option>
              <option value="seo-tecnico">SEO Técnico</option>
              <option value="mantenimiento-wordpress">Mantenimiento WordPress</option>
              <option value="desarrollo-wordpress">Desarrollo WordPress</option>
              <option value="plugins-wordpress">Plugin a medida</option>
              <option value="otro">Otro / No lo sé aún</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="mensaje">Cuéntame tu situación <span aria-hidden="true">*</span></label>
            <textarea class="form-textarea" id="mensaje" name="mensaje" required placeholder="¿Qué está pasando con tu web? ¿Qué quieres mejorar? Cuanto más contexto me des, mejor podré valorarlo."></textarea>
          </div>

          <div class="form-group">
            <div class="form-check">
              <input type="checkbox" id="legal" name="legal" required>
              <label for="legal">He leído y acepto que mis datos sean usados para responder a esta consulta, sin fines comerciales adicionales.</label>
            </div>
          </div>

          <input type="hidden" name="_subject" value="Nuevo contacto — victor-alonso.es">
          <input type="hidden" name="_replyto" value="<?= h(SITE_EMAIL) ?>">
          <input type="hidden" name="_next" value="<?= h(SITE_URL) ?>/gracias/">

          <button type="submit" class="btn btn--primary btn--lg">Enviar mensaje</button>
        </form>
      </div>

      <aside class="contact-sidebar">
        <div class="card" style="margin-bottom:1.5rem">
          <h2 style="font-size:1.1rem;margin-bottom:1rem">Datos de contacto</h2>
          <div class="contact-info-item">
            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.65 3.49 2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 6 6l.86-.86a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 21.32 16l.6.92z"/></svg>
            <a href="tel:<?= h(SITE_PHONE_RAW) ?>"><?= h(SITE_PHONE) ?></a>
          </div>
          <div class="contact-info-item">
            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <a href="mailto:<?= h(SITE_EMAIL) ?>"><?= h(SITE_EMAIL) ?></a>
          </div>
          <div class="contact-info-item">
            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span><?= h(SITE_LOCALITY) ?>, España</span>
          </div>
          <div style="margin-top:1.25rem">
            <a href="https://wa.me/<?= SITE_PHONE_RAW ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp" style="width:100%;justify-content:center">
              <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
              Escríbeme por WhatsApp
            </a>
          </div>
        </div>

        <div class="card">
          <h3 style="margin-bottom:.75rem">Área de trabajo</h3>
          <div class="map-wrap">
            <div id="map" role="application" aria-label="Mapa de Albacete"></div>
            <noscript><p class="map-fallback">Albacete, Castilla-La Mancha, España.</p></noscript>
          </div>
        </div>
      </aside>

    </div>
  </section>

</main>

<script src="/assets/js/map.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
