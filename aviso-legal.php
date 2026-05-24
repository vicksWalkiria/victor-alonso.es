<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

$page = page_config([
  'title'       => 'Aviso Legal',
  'description' => 'Aviso legal de Víctor Alonso SEO. Información sobre el titular del sitio web, condiciones de uso y propiedad intelectual.',
  'canonical'   => '/aviso-legal/',
  'body_class'  => 'page-policy',
  'noindex'     => true,
  'active_nav'  => '',
  'breadcrumbs' => [
    ['label' => 'Aviso Legal', 'url' => ''],
  ],
]);
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Aviso <span>Legal</span></h1>
      <p class="page-hero-desc">Última actualización: mayo de 2026</p>
    </div>
  </section>

  <section class="section">
    <div class="container policy-content">

      <h2>1. Datos identificativos del titular</h2>
      <p>En cumplimiento del artículo 10 de la Ley 34/2002 de Servicios de la Sociedad de la Información (LSSICE), se informa de los datos del titular de este sitio web:</p>
      <ul>
        <li><strong>Nombre:</strong> Víctor Alonso</li>
        <li><strong>Nombre comercial:</strong> Víctor Alonso SEO</li>
        <li><strong>Dirección:</strong> Calle Iris 25, 02005 Albacete (España)</li>
        <li><strong>Email:</strong> <a href="mailto:soy@victor-alonso.es">soy@victor-alonso.es</a></li>
        <li><strong>Teléfono:</strong> <a href="tel:+34675946486">+34 675 946 486</a></li>
        <li><strong>Dominio:</strong> <a href="https://www.victor-alonso.es">https://www.victor-alonso.es</a></li>
      </ul>

      <h2>2. Objeto y ámbito de aplicación</h2>
      <p>El presente aviso legal regula el acceso y uso del sitio web <strong>victor-alonso.es</strong>, cuya titularidad corresponde a Víctor Alonso. El acceso y uso de este sitio implica la aceptación expresa y plena de las condiciones aquí establecidas.</p>

      <h2>3. Propiedad intelectual e industrial</h2>
      <p>Todos los contenidos de este sitio web (textos, imágenes, diseño, código fuente, logotipos y demás elementos) son propiedad de Víctor Alonso o de terceros que han autorizado su uso, y están protegidos por la normativa española e internacional de propiedad intelectual e industrial.</p>
      <p>Queda prohibida su reproducción, distribución, comunicación pública o transformación total o parcial sin autorización expresa por escrito del titular.</p>

      <h2>4. Condiciones de uso</h2>
      <p>El usuario se compromete a utilizar este sitio web de conformidad con la ley, la moral y el orden público, y a no emplearlo para actividades ilícitas o contrarias a los derechos de terceros. El titular se reserva el derecho de denegar o retirar el acceso al sitio, sin necesidad de preaviso, a aquellos usuarios que incumplan estas condiciones.</p>

      <h2>5. Exclusión de responsabilidad</h2>
      <p>El titular no garantiza la disponibilidad, continuidad o infalibilidad del funcionamiento del sitio web, y en consecuencia excluye, en la máxima medida permitida por la legislación vigente, cualquier responsabilidad por los daños y perjuicios que puedan derivarse de la falta de disponibilidad o continuidad del sitio.</p>
      <p>Los contenidos del sitio tienen carácter meramente informativo. La información publicada sobre herramientas, análisis SEO y estrategias no constituye asesoramiento profesional vinculante.</p>

      <h2>6. Política de privacidad y cookies</h2>
      <p>El tratamiento de datos personales se rige por la <a href="/politica-privacidad/">Política de Privacidad</a>. El uso de cookies está regulado en la <a href="/politica-cookies/">Política de Cookies</a>.</p>

      <h2>7. Legislación aplicable y jurisdicción</h2>
      <p>Las presentes condiciones se rigen por la legislación española. Para la resolución de cualquier conflicto que pudiera derivarse del acceso o uso de este sitio, las partes acuerdan someterse a los juzgados y tribunales de Albacete, con renuncia expresa a cualquier otro fuero que pudiera corresponderles.</p>

    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
