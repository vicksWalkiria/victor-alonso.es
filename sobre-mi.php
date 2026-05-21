<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

$page = page_config([
  'title'        => 'Sobre mí | Víctor Alonso, consultor SEO e ingeniero informático',
  'description'  => 'Ingeniero informático y consultor SEO técnico en Albacete. Experiencia en SEO, WordPress, PHP, Laravel, analítica web y optimización de rendimiento. Por qué combino SEO y desarrollo.',
  'canonical'    => '/sobre-mi/',
  'body_class'   => 'page-sobre-mi',
  'schema_types' => ['AboutPage'],
  'active_nav'   => 'sobre-mi',
  'breadcrumbs'  => [
    ['label' => 'Sobre mí', 'url' => ''],
  ],
]);
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumbs.php';
?>
<main id="main">

  <section class="page-hero" aria-labelledby="sobre-h1">
    <div class="container">
      <h1 id="sobre-h1">Soy Víctor Alonso: ingeniero informático que <span>entiende el SEO</span> desde el código</h1>
      <p class="page-hero-desc">No solo diagnostico problemas. Los resuelvo directamente. Esa es la diferencia entre un consultor que recomienda y uno que implementa.</p>
    </div>
  </section>

  <section class="section">
    <div class="container about-grid">

      <div class="about-img-wrap">
        <img
          src="/assets/img/victor-alonso-v3.webp"
          alt="Víctor Alonso, consultor SEO e ingeniero informático en Albacete"
          width="360" height="360"
          loading="lazy"
        >
      </div>

      <div class="about-content">
        <span class="section-label">Quién soy</span>
        <h2>Perfil técnico, enfoque práctico</h2>
        <p>Me llamo Víctor Alonso. Soy ingeniero informático, consultor SEO técnico y desarrollador WordPress y PHP. Trabajo desde Albacete con clientes en toda España.</p>
        <p>Empecé desarrollando webs y apps, aprendí SEO porque necesitaba que lo que construía también se encontrara, y acabé especializándome en la intersección de los dos mundos: el SEO que entiende el código y el desarrollo que tiene en cuenta el posicionamiento desde el principio.</p>

        <h2>Por qué combino SEO y desarrollo</h2>
        <p>La mayoría de los problemas SEO que veo en webs son problemas de implementación, no de estrategia. Canonicals mal configurados, JavaScript que impide el rastreo, redirecciones con bucles, imágenes que destrozan el LCP, plugins que inyectan CSS render-blocking sin que nadie lo haya decidido conscientemente.</p>
        <p>Un consultor SEO que no puede tocar el código depende siempre de un desarrollador para implementar sus recomendaciones. Ese cuello de botella hace que el trabajo sea más lento y más caro. Yo elimino ese intermediario cuando el cliente lo necesita.</p>

        <h2>Con qué tecnologías trabajo</h2>
        <div class="about-skills" aria-label="Tecnologías y áreas de trabajo">
          <span class="skill-tag">SEO Técnico</span>
          <span class="skill-tag">Google Search Console</span>
          <span class="skill-tag">Google Analytics 4</span>
          <span class="skill-tag">Core Web Vitals / WPO</span>
          <span class="skill-tag">PHP</span>
          <span class="skill-tag">WordPress</span>
          <span class="skill-tag">Laravel</span>
          <span class="skill-tag">MySQL</span>
          <span class="skill-tag">HTML5 semántico</span>
          <span class="skill-tag">CSS</span>
          <span class="skill-tag">JavaScript</span>
          <span class="skill-tag">Datos estructurados / JSON-LD</span>
          <span class="skill-tag">Screaming Frog</span>
          <span class="skill-tag">Semrush / Ahrefs</span>
          <span class="skill-tag">Nginx / Apache</span>
          <span class="skill-tag">Linux / VPS</span>
        </div>

        <h2>Cómo trabajo con clientes</h2>
        <p>Sin proyectos en los que desaparezco 3 meses y vuelvo con un entregable que nadie sabe cómo implementar. Prefiero ciclos cortos, comunicación directa y acciones concretas que se pueden ejecutar y medir.</p>
        <p>Si necesitas alguien que te explique qué está pasando con tu web en términos que tengan sentido, que priorice lo que importa y que pueda implementarlo, podemos hablar.</p>

        <h2>Proyectos y presencia online</h2>
        <p>Además del trabajo de consultoría, desarrollo y mantengo proyectos propios. El más relevante es <a href="<?= h(SITE_WALKIRIA) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--orange)">Walkiria Apps</a>, donde ofrezco servicios de desarrollo web y SEO a través de mi marca de agencia.</p>

        <div class="about-links">
          <a href="<?= h(SITE_LINKEDIN) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--secondary" aria-label="Ver perfil de LinkedIn de Víctor Alonso">LinkedIn</a>
          <a href="<?= h(SITE_GITHUB) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--secondary" aria-label="Ver GitHub de Víctor Alonso">GitHub</a>
          <a href="<?= h(SITE_WALKIRIA) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--secondary" aria-label="Ver Walkiria Apps">Walkiria Apps</a>
          <a href="/contacto/" class="btn btn--primary">Contactar</a>
        </div>
      </div>

    </div>
  </section>

  <?php
  $cta = ['title' => '¿Quieres trabajar juntos?', 'subtitle' => 'Cuéntame tu proyecto o la situación de tu web. Te respondo con una valoración inicial.', 'btn_label' => 'Enviar mensaje', 'btn_href' => '/contacto/', 'whatsapp' => true, 'variant' => 'dark'];
  require __DIR__ . '/includes/cta.php';
  ?>

</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
