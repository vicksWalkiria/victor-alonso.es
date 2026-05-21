<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

$page = page_config([
    'title'        => 'Consultor SEO e Ingeniero Informático en Albacete',
    'description'  => 'Víctor Alonso: consultor SEO técnico, ingeniero informático y desarrollador WordPress en Albacete. Diagnóstico real, estrategia ejecutable, implementación propia.',
    'canonical'    => '/',
    'body_class'   => 'page-home',
    'schema_types' => ['LocalBusiness'],
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
        <span class="hero-eyebrow">SEO · Desarrollo Web · WordPress</span>
        <h1 id="hero-heading">
          No todo problema SEO<br>se arregla publicando<br><span>más contenido.</span>
        </h1>
        <p class="hero-desc">
          Soy Víctor Alonso: ingeniero informático y consultor SEO técnico en Albacete.
          Reviso lo que no funciona, priorizo lo que importa y lo implemento directamente.
          Sin intermediarios, sin humo.
        </p>
        <div class="hero-actions">
          <a href="/contacto" class="btn btn--primary btn--lg">Solicitar diagnóstico SEO</a>
          <a href="#servicios" class="btn btn--ghost btn--lg">Ver servicios</a>
        </div>
      </div>
      <div class="hero-img-wrap">
        <img
          src="https://walkiriaapps.com/wp-content/uploads/2021/11/victor-alonso-desarrollador-freelance.jpeg"
          alt="Víctor Alonso, consultor SEO e ingeniero informático en Albacete"
          width="480" height="600"
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
      <h2 class="section-title" id="servicios-heading">Servicios</h2>
      <p class="section-intro" style="margin-bottom:2rem">SEO, desarrollo web y mantenimiento técnico para negocios que necesitan algo más que textos bonitos.</p>
      <div class="cards-grid">
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </div>
          <h3>SEO en Albacete</h3>
          <p>SEO local técnico para negocios en Albacete. Google Business Profile, arquitectura, contenido local y medición.</p>
          <a href="/servicios/seo-albacete" class="card-link">Ver servicio →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          </div>
          <h3>SEO para España</h3>
          <p>Estrategia SEO nacional con enfoque técnico, clusters de contenido, arquitectura de información y análisis de intención.</p>
          <a href="/servicios/seo-espana" class="card-link">Ver servicio →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          </div>
          <h3>Auditoría SEO</h3>
          <p>Revisión completa del estado técnico, arquitectura, contenido, indexación y WPO. Entregable priorizado por impacto.</p>
          <a href="/servicios/auditoria-seo" class="card-link">Ver servicio →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
          </div>
          <h3>SEO Técnico</h3>
          <p>Rastreo, indexación, renderizado, Core Web Vitals, canonicals, sitemaps, datos estructurados, JS SEO y migraciones.</p>
          <a href="/servicios/seo-tecnico" class="card-link">Ver servicio →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <h3>Mantenimiento WordPress</h3>
          <p>Actualizaciones, backups, seguridad, limpieza de malware, WPO y soporte técnico continuo.</p>
          <a href="/servicios/mantenimiento-wordpress" class="card-link">Ver servicio →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
          </div>
          <h3>Desarrollo WordPress</h3>
          <p>Temas a medida, CPT, campos personalizados, rendimiento y SEO técnico desde la base del código.</p>
          <a href="/servicios/desarrollo-wordpress" class="card-link">Ver servicio →</a>
        </article>
        <article class="card">
          <div class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M12 12v.01"/></svg>
          </div>
          <h3>Plugins a medida</h3>
          <p>Automatizaciones, shortcodes, integraciones con APIs, formularios y paneles de administración personalizados.</p>
          <a href="/servicios/plugins-wordpress" class="card-link">Ver servicio →</a>
        </article>
      </div>
    </div>
  </section>

  <!-- Autoridad -->
  <section class="section" aria-labelledby="sobre-heading">
    <div class="container auth-grid">
      <div class="auth-img-wrap">
        <img
          src="https://walkiriaapps.com/wp-content/uploads/2021/11/victor-alonso-desarrollador-freelance.jpeg"
          alt="Víctor Alonso, consultor SEO e ingeniero informático en Albacete"
          width="500" height="500"
          loading="lazy"
        >
      </div>
      <div class="auth-text">
        <span class="section-label">Quién soy</span>
        <h2 id="sobre-heading">Ingeniero informático que hace SEO.<br>Y desarrollador que entiende por qué posiciona.</h2>
        <p>Me llamo Víctor Alonso. Soy ingeniero informático con años de experiencia en SEO técnico, desarrollo WordPress, PHP y analítica web. Trabajo desde Albacete para empresas en España y fuera.</p>
        <p>La combinación de perfil técnico y conocimiento SEO me permite ir más allá del informe: detecto problemas en el código, en la arquitectura y en el contenido, y los resuelvo directamente.</p>
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
        <a href="/sobre-mi" class="btn btn--primary">Más sobre mí</a>
      </div>
    </div>
  </section>

  <!-- Casos reales preview -->
  <section class="section section--alt" aria-labelledby="casos-heading">
    <div class="container">
      <span class="section-label">Sin inventar cifras</span>
      <h2 class="section-title" id="casos-heading">Casos y situaciones reales</h2>
      <p class="section-intro" style="margin-bottom:2rem">Proyectos anonimizados con el contexto, el problema, el diagnóstico y lo que se puede aprender de cada uno.</p>
      <div class="cards-grid">
        <article class="card">
          <h3>WordPress infectado</h3>
          <p>Un cliente llega con la web penalizada en Chrome. Revisión del servidor, limpieza de malware, hardening y restauración de la reputación en Google Safe Browsing.</p>
        </article>
        <article class="card">
          <h3>Caída de tráfico tras migración</h3>
          <p>Cambio de dominio sin redirecciones bien planificadas. Pérdida de autoridad y posiciones. Diagnóstico en Search Console, análisis de cobertura y plan de recuperación.</p>
        </article>
        <article class="card">
          <h3>Web lenta sin saber por qué</h3>
          <p>LCP de 6 segundos en móvil. Análisis de cascada de carga, imágenes sin optimizar, CSS render-blocking y plugins con scripts externos innecesarios.</p>
        </article>
      </div>
      <div style="margin-top:2rem">
        <a href="/casos-reales" class="btn btn--secondary">Ver todos los casos</a>
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
    'title'     => '¿Tienes dudas sobre tu posicionamiento web?',
    'subtitle'  => 'Cuéntame qué ocurre con tu web. Te respondo con una primera valoración sin compromiso.',
    'btn_label' => 'Solicitar diagnóstico SEO',
    'btn_href'  => '/contacto',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require __DIR__ . '/includes/cta.php';
  ?>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>