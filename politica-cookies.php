<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

$page = page_config([
  'title'       => 'Política de Cookies',
  'description' => 'Política de cookies de Víctor Alonso SEO. Información sobre los tipos de cookies utilizadas, su finalidad y cómo gestionarlas.',
  'canonical'   => '/politica-cookies/',
  'body_class'  => 'page-policy',
  'noindex'     => true,
  'active_nav'  => '',
  'breadcrumbs' => [
    ['label' => 'Política de Cookies', 'url' => ''],
  ],
]);
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Política de <span>Cookies</span></h1>
      <p class="page-hero-desc">Última actualización: mayo de 2026</p>
    </div>
  </section>

  <section class="section">
    <div class="container policy-content">

      <h2>1. ¿Qué son las cookies?</h2>
      <p>Las cookies son pequeños archivos de texto que un sitio web almacena en tu navegador cuando lo visitas. Sirven para que el sitio recuerde información sobre tu visita (idioma, preferencias, etc.) y para recopilar estadísticas de uso.</p>

      <h2>2. Cookies que utilizamos</h2>
      <p>Este sitio web utiliza únicamente cookies de analítica mediante <strong>Google Analytics 4 (GA4)</strong>. No se utilizan cookies publicitarias, de seguimiento de terceros ni de redes sociales.</p>

      <table class="policy-table">
        <thead>
          <tr>
            <th>Cookie</th>
            <th>Proveedor</th>
            <th>Finalidad</th>
            <th>Duración</th>
            <th>Tipo</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>_ga</code></td>
            <td>Google Analytics</td>
            <td>Distinguir usuarios únicos</td>
            <td>2 años</td>
            <td>Analítica</td>
          </tr>
          <tr>
            <td><code>_ga_*</code></td>
            <td>Google Analytics</td>
            <td>Mantener el estado de sesión</td>
            <td>2 años</td>
            <td>Analítica</td>
          </tr>
        </tbody>
      </table>

      <p>Google Analytics se configura en este sitio con anonimización de IP activa. Los datos recopilados son estadísticos y no permiten identificar a usuarios de forma individual. Puedes consultar la política de privacidad de Google en <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">policies.google.com/privacy</a>.</p>

      <h2>3. Cómo gestionar o desactivar las cookies</h2>
      <p>Puedes configurar tu navegador para rechazar o eliminar las cookies en cualquier momento. Ten en cuenta que desactivar algunas cookies puede afectar al funcionamiento del sitio. Instrucciones según navegador:</p>
      <ul>
        <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer">Google Chrome</a></li>
        <li><a href="https://support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-sitios-web-rastrear-preferencias" target="_blank" rel="noopener noreferrer">Mozilla Firefox</a></li>
        <li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" rel="noopener noreferrer">Safari</a></li>
        <li><a href="https://support.microsoft.com/es-es/windows/eliminar-y-administrar-cookies-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener noreferrer">Microsoft Edge</a></li>
      </ul>
      <p>También puedes instalar el complemento oficial de inhabilitación de Google Analytics: <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener noreferrer">tools.google.com/dlpage/gaoptout</a>.</p>

      <h2>4. Responsable del tratamiento</h2>
      <p>Víctor Alonso — <a href="mailto:soy@victor-alonso.es">soy@victor-alonso.es</a>. Para más información, consulta la <a href="/politica-privacidad/">Política de Privacidad</a>.</p>

      <h2>5. Cambios en esta política</h2>
      <p>Esta política puede actualizarse cuando se modifiquen las cookies utilizadas en el sitio. La fecha de última actualización siempre estará indicada al inicio del documento.</p>

    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
