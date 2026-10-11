<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

$page = page_config([
    'title'        => 'Consultor SEO en Albacete · Freelance e Ingeniero Informático',
    'description'  => '¿Tu negocio no aparece en Google? Consultor SEO freelance e Ingeniero Informático en Albacete. Auditoría técnica y trato directo sin agencias ni costes inflados.',
    'canonical'    => '/',
    'body_class'   => 'page-home',
    'schema_types' => ['LocalBusiness', 'Service'],
    'service_data' => [
        '@id' => '/#seo-service',
        'name' => 'Consultoría SEO Freelance en Albacete',
        'serviceType' => 'Consultor SEO técnico, WPO y WordPress Freelance',
        'areaServed' => [
            ['@type' => 'Place', 'name' => 'Albacete']
        ]
    ],
    'active_nav'   => 'inicio',
    'breadcrumbs'  => [],
]);

require __DIR__ . '/includes/header.php';
?>

<main id="main">

  <!-- Hero -->
  <section class="hero" aria-labelledby="hero-heading">
    <div class="container hero-inner">
      <div class="hero-content">
        <span class="hero-eyebrow">Consultor SEO en Albacete · Freelance · Ingeniero Informático</span>
        <h1 id="hero-heading">
          Consultor SEO en Albacete
        </h1>
        <p class="hero-desc">
          Soy Víctor Alonso: ingeniero informático y consultor SEO técnico freelance en Albacete.
          Mejoro el posicionamiento web de tu empresa con diagnóstico técnico real,
          estrategia ejecutable e implementación directa. Sin comerciales, sin intermediarios de agencia y sin humo.
        </p>
        <p class="hero-desc" style="margin-top:1rem;">
          Trato directo de profesional independiente especializado en posicionamiento SEO técnico, auditorías web, SEO local y desarrollo WordPress optimizado para empresas de Albacete.
        </p>
        <div class="hero-actions" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
          <a href="/contacto/" class="btn btn--primary btn--lg">Solicitar diagnóstico gratuito</a>
          <a href="<?= wa_url('seo') ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp btn--lg" style="display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">
            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Hablar por WhatsApp
          </a>
          <a href="#servicios" class="btn btn--ghost btn--lg">Ver servicios</a>
        </div>
      </div>
      <div class="hero-img-wrap">
        <img
          src="/assets/img/victor-alonso-v3.webp"
          alt="Víctor Alonso, consultor SEO e ingeniero informático en Albacete"
          width="360" height="360"
          fetchpriority="high"
        >
      </div>
    </div>
  </section>

  <!-- Problemas reales -->
  <section class="section" aria-labelledby="problemas-heading">
    <div class="container">
      <span class="section-label">El diagnóstico primero</span>
      <h2 class="section-title" id="problemas-heading">Síntomas que veo con frecuencia</h2>
      <p class="section-intro">Antes de proponer soluciones, reviso por qué tu web no está funcionando como debería. Estos son los problemas más habituales que encuentro.</p>
      <div class="problems-grid" style="margin-top:2rem">
        <div class="problem-item">
          <div class="problem-bullet" aria-hidden="true"></div>
          <div>
            <h3>Web que no convierte</h3>
            <p>El tráfico llega pero no genera contactos ni ventas. Suele ser un problema de arquitectura, contenido o velocidad, no de volumen.</p>
          </div>
        </div>
        <div class="problem-item">
          <div class="problem-bullet" aria-hidden="true"></div>
          <div>
            <h3>Tráfico que cae tras un update de Google</h3>
            <p>Contenido que no responde a la intención de búsqueda, páginas que canibalizan entre sí o señales técnicas débiles.</p>
          </div>
        </div>
        <div class="problem-item">
          <div class="problem-bullet" aria-hidden="true"></div>
          <div>
            <h3>WordPress lento</h3>
            <p>Plugins acumulados, imágenes sin optimizar, temas inflados o falta de caché. Un WPO mal resuelto lastra el Core Web Vitals y el posicionamiento.</p>
          </div>
        </div>
        <div class="problem-item">
          <div class="problem-bullet" aria-hidden="true"></div>
          <div>
            <h3>Errores de indexación</h3>
            <p>Google no rastrea bien tus páginas clave, indexa las que no debe o tu sitemap no refleja la arquitectura real del sitio.</p>
          </div>
        </div>
        <div class="problem-item">
          <div class="problem-bullet" aria-hidden="true"></div>
          <div>
            <h3>Contenido que no ataca intención de búsqueda</h3>
            <p>Páginas escritas pensando en el producto, no en cómo busca el usuario. El resultado es posicionamiento para keywords sin tráfico cualificado.</p>
          </div>
        </div>
        <div class="problem-item">
          <div class="problem-bullet" aria-hidden="true"></div>
          <div>
            <h3>Falta de medición real</h3>
            <p>Analytics mal configurado, sin conversiones definidas o con datos contaminados. Sin medir bien, no se puede mejorar nada con criterio.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Metodología -->
  <section class="section section--dark" aria-labelledby="metodologia-heading">
    <div class="container">
      <span class="section-label" style="color:var(--orange)">Cómo trabajo</span>
      <h2 class="section-title" id="metodologia-heading" style="color:#fff">Metodología, no ocurrencias</h2>
      <p class="section-intro" style="color:rgba(255,255,255,.65);margin-bottom:2.5rem">Prefiero priorizar 10 acciones ejecutables antes que entregar una auditoría de 80 páginas que nadie implementa.</p>
      <div class="method-steps">
        <div class="method-step">
          <h3>Diagnóstico</h3>
          <p>Revisión técnica, de contenido y de arquitectura. Separo síntomas de causas reales.</p>
        </div>
        <div class="method-step">
          <h3>Priorización</h3>
          <p>Ordeno las acciones por impacto potencial vs. esfuerzo. No todo tiene el mismo retorno.</p>
        </div>
        <div class="method-step">
          <h3>Implementación</h3>
          <p>Ejecuto directamente los cambios técnicos. No solo recomendaciones que luego nadie aplica.</p>
        </div>
        <div class="method-step">
          <h3>Medición</h3>
          <p>Compruebo que los cambios tienen efecto real en Search Console, Analytics y posiciones.</p>
        </div>
        <div class="method-step">
          <h3>Iteración</h3>
          <p>El SEO no es un proyecto único. Ajusto según datos, no según tendencias del sector.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Servicios -->
  <section class="section section--alt" id="servicios" aria-labelledby="servicios-heading">
    <div class="container">
      <span class="section-label">Lo que hago</span>
      <h2 class="section-title" id="servicios-heading">Servicios de posicionamiento SEO en Albacete y optimización técnica</h2>
      <p class="section-intro" style="margin-bottom:2rem">SEO técnico, posicionamiento web en Albacete, desarrollo WordPress y mantenimiento para negocios que necesitan algo más que textos bonitos.</p>
      <div class="cards-grid">
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </div>
          <h3>Agencia & Posicionamiento SEO Local</h3>
          <p>Servicio integral de posicionamiento web para empresas de Albacete: optimización de Google Business Profile, Local Pack, visibilidad y captación de clientes locales.</p>
          <a href="/servicios/seo-albacete/" class="card-link">Servicios de posicionamiento web en Albacete →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          </div>
          <h3>SEO para España</h3>
          <p>Estrategia SEO nacional con enfoque técnico, clusters de contenido, arquitectura de información y análisis de intención.</p>
          <a href="/servicios/seo-espana/" class="card-link">Servicio de SEO nacional →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          </div>
          <h3>Auditoría SEO Técnica</h3>
          <p>Revisión completa del estado técnico, arquitectura, contenido, indexación y WPO. Entregable priorizado por impacto.</p>
          <a href="/servicios/auditoria-seo/" class="card-link">Auditoría SEO técnica y web →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
          </div>
          <h3>SEO Técnico</h3>
          <p>Rastreo, indexación, renderizado, Core Web Vitals, canonicals, sitemaps, datos estructurados, JS SEO y migraciones.</p>
          <a href="/servicios/seo-tecnico/" class="card-link">servicio de SEO técnico →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <h3>Mantenimiento WordPress</h3>
          <p>Actualizaciones, backups, seguridad, WPO y soporte continuo. Si tu web está hackeada, pide una <a href="/servicios/reparacion-wordpress-urgente/" style="color:inherit;text-decoration:underline;font-weight:600;">reparación urgente de WordPress</a>.</p>
          <a href="/servicios/mantenimiento-wordpress/" class="card-link">mantenimiento WordPress →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
          </div>
          <h3>Desarrollo WordPress</h3>
          <p>Temas a medida, CPT, campos personalizados, rendimiento y SEO técnico desde la base del código.</p>
          <a href="/servicios/desarrollo-wordpress/" class="card-link">Ver servicio →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M12 12v.01"/></svg>
          </div>
          <h3>Plugins a medida</h3>
          <p>Automatizaciones, shortcodes, integraciones con APIs, formularios y paneles de administración personalizados.</p>
          <a href="/servicios/plugins-wordpress/" class="card-link">Ver servicio →</a>
        </article>
      </div>
    </div>
  </section>

  <!-- Por qué yo: diferencial -->
  <section class="section" aria-labelledby="diferencial-heading">
    <div class="container auth-grid">
      <div class="auth-img-wrap">
        <img
          src="/assets/img/victor-alonso-v3.webp"
          alt="Víctor Alonso, consultor SEO en Albacete e ingeniero informático"
          width="360" height="360"
          loading="lazy"
        >
      </div>
      <div class="auth-text">
        <span class="section-label">Por qué trabajar conmigo</span>
        <h2 id="diferencial-heading">Por qué trabajar conmigo como consultor SEO en Albacete</h2>
        <p>Me llamo Víctor Alonso. Soy ingeniero informático con años de experiencia en SEO técnico, WordPress, PHP y analítica web. Trabajo desde Albacete para empresas en España y fuera.</p>
        <p>La diferencia frente a una agencia SEO: detecto problemas directamente en el código, en la arquitectura y en el contenido, y los resuelvo yo mismo sin pasar por cadenas de subcontratación.</p>
        <ul style="margin:1rem 0 1.5rem; padding-left:1.25rem; display:flex; flex-direction:column; gap:.5rem;">
          <li>Ingeniero informático: entiendo el código, no solo los informes</li>
          <li>Desarrollo plugins, herramientas SEO propias y automatizaciones</li>
          <li>No solo estrategia: implemento los cambios técnicos directamente</li>
          <li>Experiencia en WordPress, Laravel, PHP, WPO y Search Console</li>
          <li>Trato directo, sin comerciales ni reuniones innecesarias</li>
        </ul>
        <div class="auth-tags" aria-label="Especialidades">
          <span class="auth-tag">SEO Técnico</span>
          <span class="auth-tag">PHP / WordPress</span>
          <span class="auth-tag">Core Web Vitals</span>
          <span class="auth-tag">Laravel</span>
          <span class="auth-tag">Datos estructurados</span>
          <span class="auth-tag">Google Analytics 4</span>
          <span class="auth-tag">Search Console</span>
          <span class="auth-tag">WPO</span>
        </div>
        <a href="/sobre-mi/" class="btn btn--primary">Más sobre mí</a>
      </div>
    </div>
  </section>

  <!-- Herramientas SEO propias -->
  <section class="section section--alt" aria-labelledby="herramientas-heading">
    <div class="container">
      <span class="section-label">Herramientas creadas por mí</span>
      <h2 class="section-title" id="herramientas-heading">Herramientas SEO gratuitas</h2>
      <p class="section-intro" style="margin-bottom:2rem">Como consultor SEO en Albacete con perfil técnico, desarrollo mis propias herramientas de análisis. Úsalas gratis.</p>
      <div class="cards-grid">
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          </div>
          <h3>Analizador de logs</h3>
          <p>Analiza los logs de acceso de tu servidor para detectar qué URLs rastrea Googlebot, con qué frecuencia y dónde pierde el tiempo.</p>
          <a href="/herramientas/analizador-logs/" class="card-link">Usar herramienta →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </div>
          <h3>Analizador SEO</h3>
          <p>Revisión técnica básica de cualquier URL: title, meta description, H1, canonicals, robots y velocidad de carga.</p>
          <a href="/herramientas/analizador-seo/" class="card-link">Usar herramienta →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <h3>Generador Schema Local</h3>
          <p>Genera el marcado Schema LocalBusiness correcto para tu negocio en Albacete o cualquier ciudad. Listo para copiar y pegar.</p>
          <a href="/herramientas/generador-schema-local/" class="card-link">Usar herramienta →</a>
        </article>
      </div>
      <div style="margin-top:2rem">
        <a href="/herramientas/" class="btn btn--secondary">Ver todas las herramientas SEO</a>
      </div>
    </div>
  </section>

  <!-- Casos reales preview -->
  <section class="section" aria-labelledby="casos-heading">
    <div class="container">
      <span class="section-label">Sin inventar cifras</span>
      <h2 class="section-title" id="casos-heading">Casos reales de posicionamiento web y SEO técnico</h2>
      <p class="section-intro" style="margin-bottom:2rem">Proyectos anonimizados con el contexto, el problema, el diagnóstico y lo que se puede aprender de cada uno.</p>
      <div class="cards-grid">
        <article class="card">
          <h3>Malware de persistencia en WordPress</h3>
          <p>Clínica médica con caídas y CPU al 99%. Limpieza forense por SSH, eliminación de scripts de autorregeneración y blindaje de seguridad perimetral.</p>
          <a href="/caso-exito-desinfeccion-malware-wordpress/" class="card-link">Ver caso de éxito →</a>
        </article>
        <article class="card">
          <h3>Caída de tráfico tras migración de dominio</h3>
          <p>Cambio de dominio sin redirecciones bien planificadas. Pérdida de autoridad y posiciones. Diagnóstico en Search Console, análisis de cobertura y plan de recuperación.</p>
          <a href="/casos-reales/#caso-2" class="card-link">Ver caso de estudio →</a>
        </article>
        <article class="card">
          <h3>Web lenta: LCP de 6 segundos en móvil</h3>
          <p>Análisis de cascada de carga, imágenes sin optimizar, CSS render-blocking y plugins con scripts externos innecesarios. Resultado: Core Web Vitals en verde.</p>
          <a href="/casos-reales/#caso-3" class="card-link">Ver caso de estudio →</a>
        </article>
      </div>
      <div style="margin-top:2rem">
        <a href="/casos-reales/" class="btn btn--secondary">Ver casos reales</a>
      </div>
    </div>
  </section>

  <!-- Testimonios -->
  <section class="section section--dark" aria-labelledby="testimonios-heading">
    <div class="container">
      <span class="section-label" style="color:var(--orange)">Lo que dicen</span>
      <h2 class="section-title" id="testimonios-heading" style="color:#fff">Clientes reales</h2>
      <div class="testimonials-grid" style="margin-top:2rem">
        <blockquote class="testimonial">
          <p class="testimonial-stars" aria-label="5 de 5 estrellas">★★★★★</p>
          <p class="testimonial-text">"Nos ayudó con un WordPress infectado, rápido y formal. Explicó qué había pasado y cómo evitarlo."</p>
          <footer class="testimonial-author">David Lara Arroyo</footer>
        </blockquote>
        <blockquote class="testimonial">
          <p class="testimonial-stars" aria-label="5 de 5 estrellas">★★★★★</p>
          <p class="testimonial-text">"No solo resolvió el problema, sino que me dio herramientas para ser más independiente en futuras incidencias."</p>
          <footer class="testimonial-author">Sonia Gual</footer>
        </blockquote>
        <blockquote class="testimonial">
          <p class="testimonial-stars" aria-label="5 de 5 estrellas">★★★★★</p>
          <p class="testimonial-text">"Arregló todo cuando tuve problemas con mi web. Rápido, directo y sin complicaciones innecesarias."</p>
          <footer class="testimonial-author">Pedro de Hierro</footer>
        </blockquote>
      </div>
    </div>
  </section>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => 'Cuéntame qué está frenando tu web',
    'subtitle'  => 'Revisaré el caso y te daré una primera valoración SEO gratuita y sin compromiso, sin reuniones innecesarias.',
    'btn_label' => 'Solicitar diagnóstico SEO gratuito',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'wa_context'=> 'general',
    'variant'   => 'orange',
  ];
  require __DIR__ . '/includes/cta.php';
  ?>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>