<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$page = page_config([
  'title'       => 'Política de Privacidad',
  'description' => 'Política de privacidad de Víctor Alonso SEO conforme al RGPD. Información sobre el tratamiento de datos personales, derechos y contacto del responsable.',
  'canonical'   => '/politica-privacidad/',
  'body_class'  => 'page-policy',
  'noindex'     => true,
  'active_nav'  => '',
  'breadcrumbs' => [
    ['label' => 'Política de Privacidad', 'url' => ''],
  ],
]);
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<main id="main">
  <section class="page-hero" aria-labelledby="page-h1">
    <div class="container">
      <h1 id="page-h1">Política de <span>Privacidad</span></h1>
      <p class="page-hero-desc">Última actualización: mayo de 2026</p>
    </div>
  </section>

  <section class="section">
    <div class="container policy-content">

      <h2>1. Responsable del tratamiento</h2>
      <p>En cumplimiento del Reglamento (UE) 2016/679 del Parlamento Europeo y del Consejo (RGPD) y la Ley Orgánica 3/2018 de Protección de Datos Personales (LOPDGDD), te informamos de que el responsable del tratamiento de tus datos personales es:</p>
      <ul>
        <li><strong>Nombre:</strong> Víctor Alonso</li>
        <li><strong>Nombre comercial:</strong> Víctor Alonso SEO</li>
        <li><strong>Dirección:</strong> Calle Iris 25, 02005 Albacete (España)</li>
        <li><strong>Email:</strong> <a href="mailto:soy@victor-alonso.es">soy@victor-alonso.es</a></li>
        <li><strong>Teléfono:</strong> <a href="tel:+34675946486">+34 675 946 486</a></li>
        <li><strong>Web:</strong> <a href="https://www.victor-alonso.es">https://www.victor-alonso.es</a></li>
      </ul>

      <h2>2. Datos que se recopilan y finalidad</h2>
      <p>Los datos personales que se pueden tratar a través de este sitio web son:</p>
      <ul>
        <li><strong>Formulario de contacto:</strong> nombre, correo electrónico y mensaje. Finalidad: responder a tu consulta y gestionar la relación comercial. Base jurídica: interés legítimo y consentimiento del interesado.</li>
        <li><strong>Herramientas SEO del sitio:</strong> las herramientas de análisis (analizador SEO, analizador de logs, extractor de entidades, etc.) procesan únicamente las URLs o datos que tú introduces de forma explícita. El procesamiento se realiza en la memoria RAM del servidor de forma efímera y no se almacena ningún dato en bases de datos ni archivos.</li>
        <li><strong>Datos de navegación y analítica:</strong> mediante Google Analytics 4 (GA4) se recopilan datos estadísticos de navegación de forma anonimizada. Más información en la <a href="/politica-cookies/">Política de Cookies</a>.</li>
      </ul>

      <h2>3. Conservación de los datos</h2>
      <p>Los datos facilitados a través del formulario de contacto se conservarán durante el tiempo necesario para atender tu consulta y, en su caso, para el cumplimiento de las obligaciones legales. Los datos de analítica web están sujetos a los plazos de retención de Google Analytics.</p>

      <h2>4. Destinatarios y transferencias internacionales</h2>
      <p>No se cederán datos a terceros, salvo obligación legal o en los siguientes casos con base en garantías adecuadas:</p>
      <ul>
        <li><strong>Formspree Inc.</strong> (procesador del formulario de contacto): proveedor ubicado en EE. UU. con garantías adecuadas conforme al marco UE-EE. UU.</li>
        <li><strong>Google LLC</strong> (Google Analytics 4): proveedor de analítica ubicado en EE. UU. bajo el marco EU-US Data Privacy Framework.</li>
      </ul>

      <h2>5. Derechos del interesado</h2>
      <p>Tienes derecho a:</p>
      <ul>
        <li>Acceder a tus datos personales.</li>
        <li>Solicitar la rectificación de datos inexactos.</li>
        <li>Solicitar la supresión de tus datos cuando ya no sean necesarios.</li>
        <li>Oponerte al tratamiento o solicitar su limitación.</li>
        <li>Solicitar la portabilidad de los datos.</li>
        <li>Retirar el consentimiento en cualquier momento.</li>
      </ul>
      <p>Para ejercer estos derechos, puedes contactarme en <a href="mailto:soy@victor-alonso.es">soy@victor-alonso.es</a>. También tienes derecho a presentar una reclamación ante la <a href="https://www.aepd.es" target="_blank" rel="noopener noreferrer">Agencia Española de Protección de Datos (AEPD)</a>.</p>

      <h2>6. Seguridad</h2>
      <p>Se han adoptado las medidas técnicas y organizativas necesarias para garantizar la seguridad de los datos personales y evitar su alteración, pérdida, tratamiento o acceso no autorizado. Este sitio utiliza conexión segura HTTPS en todo momento.</p>

      <h2>7. Cambios en esta política</h2>
      <p>Esta política puede actualizarse en cualquier momento. La fecha de última actualización siempre estará indicada al inicio del documento. Se recomienda revisarla periódicamente.</p>

    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
