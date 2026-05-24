<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';

// Interceptar acción AJAX de votación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
    header('Content-Type: application/json');
    $tool_id = trim($_POST['tool_id'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    
    // Evitar que voten dos veces (Cookie por 1 año)
    if (isset($_COOKIE['voted_' . $tool_id])) {
        echo json_encode(['success' => false, 'message' => 'Ya has valorado esta herramienta.']);
        exit;
    }
    
    $res = save_vote($tool_id, $rating);
    if ($res) {
        setcookie('voted_' . $tool_id, '1', time() + (365 * 24 * 60 * 60), '/');
        echo json_encode([
            'success' => true,
            'count' => $res['count'],
            'average' => $res['average']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar valoración.']);
    }
    exit;
}

$page = page_config([
    'title'        => 'Generador de Schema LocalBusiness JSON-LD',
    'description'  => 'Genera gratis el marcado estructurado de datos de Schema.org en formato JSON-LD recomendado por Google para posicionamiento SEO local.',
    'canonical'    => '/herramientas/generador-schema-local/',
    'body_class'   => 'page-generador-schema',
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'generador-schema-local',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Generador Schema Local', 'url' => ''],
    ],
    'faq_items' => [
        [
            'q' => '¿Dónde se debe colocar el código JSON-LD en la web?',
            'a' => 'Lo ideal es inyectarlo dentro de la etiqueta <head> de la página que represente a tu negocio (normalmente la Home o la página de Contacto). Si usas WordPress, puedes insertarlo a través de un hook en functions.php o con plugins de inserción de headers/footers.'
        ],
        [
            'q' => '¿Por qué Google prefiere JSON-LD frente a Microdatos?',
            'a' => 'JSON-LD permite separar completamente el marcado semántico de la maquetación HTML visual. A diferencia de los microdatos o RDFa, que te obligan a entrelazar el código con las etiquetas div o span, JSON-LD se carga como un script limpio e invisible para el usuario, pero muy fácil de parsear para Googlebot.'
        ],
        [
            'q' => '¿Puedo poner varios schemas LocalBusiness en mi web?',
            'a' => 'Solo si tienes múltiples ubicaciones físicas (franquicias, sedes). En ese caso, cada página de ubicación (Location Page) debe llevar su propio Schema LocalBusiness específico con las coordenadas, teléfono y dirección exactos de esa sucursal.'
        ],
        [
            'q' => 'El validador de Google marca advertencias, ¿es grave?',
            'a' => 'Existen errores (rojos) y advertencias (naranjas). Los errores impiden que Google lea el schema y deben corregirse obligatoriamente. Las advertencias (como "falta campo priceRange") son sugerencias de campos recomendados pero no obligatorios. Tu marcado funcionará perfectamente aunque tenga advertencias.'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">

  <section class="page-hero" aria-labelledby="schema-h1">
    <div class="container">
      <span class="hero-eyebrow">SEO Local Semántico</span>
      <h1 id="schema-h1">Generador Schema <span>JSON-LD Local</span></h1>
      <p class="page-hero-desc">Genera el marcado estructurado recomendado por Google para consolidar la consistencia de tu negocio físico en los resultados de búsqueda.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      
      <div class="tool-intro">
        <h2>Estructura la información de tu negocio</h2>
        <p>Rellena los datos de tu empresa a continuación. Generaremos en tiempo real el bloque estructurado en formato JSON-LD. Copia el código e instálalo en el <code>&lt;head&gt;</code> de tu página web.</p>
      </div>

      <div class="schema-tool-grid">
        
        <!-- Formulario interactivo -->
        <div class="schema-inputs card">
          <h3 style="margin-bottom:1.25rem;color:var(--orange)">1. Introduce los datos del negocio</h3>
          
          <div class="form-group">
            <label class="form-label" for="sc-type">Tipo de Negocio</label>
            <select class="form-select" id="sc-type">
              <option value="LocalBusiness">Negocio Local Genérico (LocalBusiness)</option>
              <option value="ProfessionalService" selected>Servicio Profesional (ProfessionalService)</option>
              <option value="LegalService">Servicio Legal / Abogados (LegalService)</option>
              <option value="MedicalBusiness">Clínica / Servicio Médico (MedicalBusiness)</option>
              <option value="AutomotiveBusiness">Taller / Negocio de Automoción</option>
              <option value="Restaurant">Restaurante / Hostelería</option>
              <option value="Store">Tienda Física / Ecommerce Local</option>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="sc-name">Nombre del Negocio *</label>
              <input type="text" class="form-input" id="sc-name" value="Mi Negocio SEO" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="sc-url">URL Principal *</label>
              <input type="url" class="form-input" id="sc-url" value="https://minegocio.com" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="sc-phone">Teléfono *</label>
              <input type="tel" class="form-input" id="sc-phone" value="+34 600 000 000" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="sc-logo">URL del Logo (opcional)</label>
              <input type="url" class="form-input" id="sc-logo" placeholder="https://minegocio.com/logo.png">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="sc-image">URL de Foto de Negocio (opcional)</label>
              <input type="url" class="form-input" id="sc-image" value="https://minegocio.com/foto-local.jpg" placeholder="https://minegocio.com/foto-local.jpg">
            </div>
            <div class="form-group">
              <label class="form-label" for="sc-price">Rango de Precios (opcional)</label>
              <select class="form-select" id="sc-price">
                <option value="">No especificar</option>
                <option value="$" selected>Económico ($)</option>
                <option value="$$">Moderado ($$)</option>
                <option value="$$$">Caro ($$$)</option>
                <option value="$$$$">Muy caro ($$$$)</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" style="grid-column: span 2">
              <label class="form-label" for="sc-street">Calle y número *</label>
              <input type="text" class="form-input" id="sc-street" value="Calle Ancha 10" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="sc-postal">Código Postal *</label>
              <input type="text" class="form-input" id="sc-postal" value="02001" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="sc-locality">Ciudad / Municipio *</label>
              <input type="text" class="form-input" id="sc-locality" value="Albacete" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="sc-lat">Latitud (coordenada) *</label>
              <input type="text" class="form-input" id="sc-lat" value="38.9942">
            </div>
            <div class="form-group">
              <label class="form-label" for="sc-lng">Longitud (coordenada) *</label>
              <input type="text" class="form-input" id="sc-lng" value="-1.8585">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="sc-same">Redes sociales (una por línea)</label>
            <textarea class="form-textarea" id="sc-same" rows="3" placeholder="https://www.facebook.com/minegocio&#10;https://www.instagram.com/minegocio"></textarea>
          </div>
        </div>

        <!-- Código JSON-LD Generado -->
        <div class="schema-output card" style="display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <h3 style="margin-bottom:1rem;color:var(--orange)">2. Código JSON-LD Generado</h3>
            <p style="font-size:.82rem;color:var(--muted);margin-bottom:1rem">Este código le dice a los algoritmos de Google exactamente qué eres, dónde estás y cómo contactar contigo de manera estructurada.</p>
            <pre class="schema-code-box"><code id="schema-code"></code></pre>
          </div>
          
          <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem">
            <button class="btn btn--primary" id="btn-copy-schema">Copiar código JSON-LD</button>
            <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener noreferrer" class="btn btn--ghost">Validar en Google</a>
          </div>
        </div>

      </div>

      <!-- Textos de criterio - NO COMMODITY -->
      <div class="criterio-section" style="margin-top:3.5rem">
        <span class="section-label">Verdades del SEO técnico</span>
        <h2>La realidad sobre los datos estructurados en SEO Local</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>Schema no te hará subir posiciones mágicamente</h3>
            <p>Mucha gente en el mundillo te dirá que inyectar Schema LocalBusiness te subirá directamente al top del Local Pack de Google Maps. No funciona así. El marcado estructurado es una capa de entendimiento semántico.</p>
            <p>No añade ranking; reduce fricción. Le ahorra recursos a Googlebot a la hora de relacionar los datos NAP de tu web con tu perfil de Google Business Profile. Si a Google le cuesta menos entender quién eres, catalogará tu negocio de manera correcta más rápido.</p>
          </div>

          <div class="criterio-card">
            <h3>La consistencia del NAP es innegociable</h3>
            <p>El nombre, dirección y teléfono (NAP) que definas en tu Schema JSON-LD debe ser EXACTAMENTE idéntico al que figura en tu ficha de Google Maps y en cualquier directorio relevante. Si en un sitio escribes "Calle Iris 25" y en tu schema pones "C/ Iris, Nº 25, Bajo", el algoritmo puede tener problemas para consolidar esas entidades como un único negocio sólido, restando confianza local.</p>
          </div>

          <div class="criterio-card">
            <h3>El riesgo de los marcados fraudulentos</h3>
            <p>Google monitoriza y penaliza el marcado fraudulento o inconsistente con el contenido visible de la página. Si marcas valoraciones de estrellas ficticias o utilizas un tipo de negocio que no se corresponde con tu actividad real, puedes recibir una acción manual en Search Console por datos estructurados engañosos.</p>
          </div>
        </div>
      </div>

      <!-- Widget de Votación y Rich Snippet -->
      <?php render_rating_widget('generador-schema-local'); ?>

    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Quieres optimizar tu visibilidad local?',
    'subtitle'  => 'El SEO local va mucho más allá de una ficha en Maps. Consolidamos tu estructura web para que domines los resultados locales.',
    'btn_label' => 'Quiero una consultoría SEO local',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const scType = document.getElementById('sc-type');
    const scName = document.getElementById('sc-name');
    const scUrl = document.getElementById('sc-url');
    const scPhone = document.getElementById('sc-phone');
    const scLogo = document.getElementById('sc-logo');
    const scImage = document.getElementById('sc-image');
    const scPrice = document.getElementById('sc-price');
    const scStreet = document.getElementById('sc-street');
    const scPostal = document.getElementById('sc-postal');
    const scLocality = document.getElementById('sc-locality');
    const scLat = document.getElementById('sc-lat');
    const scLng = document.getElementById('sc-lng');
    const scSame = document.getElementById('sc-same');
    const schemaCode = document.getElementById('schema-code');
    const btnCopy = document.getElementById('btn-copy-schema');

    function generateSchema() {
        const logoVal = scLogo.value.trim();
        const imageVal = scImage.value.trim();
        const priceVal = scPrice.value.trim();
        const sameAsVal = scSame.value.trim().split('\n').filter(url => url.trim() !== '');

        const schema = {
            "@context": "https://schema.org",
            "@type": scType.value,
            "name": scName.value.trim(),
            "url": scUrl.value.trim(),
            "telephone": scPhone.value.trim(),
            "address": {
                "@type": "PostalAddress",
                "streetAddress": scStreet.value.trim(),
                "postalCode": scPostal.value.trim(),
                "addressLocality": scLocality.value.trim(),
                "addressCountry": "ES"
            }
        };

        if (logoVal) {
            schema["logo"] = logoVal;
        }

        if (imageVal) {
            schema["image"] = imageVal;
        }

        if (priceVal) {
            schema["priceRange"] = priceVal;
        }

        const lat = scLat.value.trim();
        const lng = scLng.value.trim();
        if (lat && lng) {
            schema["geo"] = {
                "@type": "GeoCoordinates",
                "latitude": lat,
                "longitude": lng
            };
        }

        if (sameAsVal.length > 0) {
            schema["sameAs"] = sameAsVal;
        }

        // Generar JSON y aplicar escape de seguridad de trinchera frente a XSS
        let jsonString = JSON.stringify(schema, null, 4);
        jsonString = jsonString.replace(/<\/script>/ig, '<\\/script>');

        const fullScript = `<script type="application/ld+json">\n${jsonString}\n<\/script>`;
        schemaCode.textContent = fullScript;
    }

    const inputs = [scType, scName, scUrl, scPhone, scLogo, scImage, scPrice, scStreet, scPostal, scLocality, scLat, scLng, scSame];
    inputs.forEach(input => {
        if(input) {
            input.addEventListener('input', generateSchema);
            input.addEventListener('change', generateSchema);
        }
    });

    generateSchema();

    // Lógica UX de Auto-Limpieza inteligente al hacer foco en valores de ejemplo
    const defaultValues = {
        'sc-name': 'Mi Negocio SEO',
        'sc-url': 'https://minegocio.com',
        'sc-phone': '+34 600 000 000',
        'sc-image': 'https://minegocio.com/foto-local.jpg',
        'sc-street': 'Calle Ancha 10',
        'sc-postal': '02001',
        'sc-locality': 'Albacete',
        'sc-lat': '38.9942',
        'sc-lng': '-1.8585'
    };

    Object.keys(defaultValues).forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('focus', function() {
                if (this.value === defaultValues[id]) {
                    this.value = '';
                    generateSchema();
                }
            });
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.value = defaultValues[id];
                    generateSchema();
                }
            });
        }
    });

    btnCopy.addEventListener('click', function() {
        navigator.clipboard.writeText(schemaCode.textContent).then(() => {
            const originalText = btnCopy.textContent;
            btnCopy.textContent = '¡Copiado con éxito!';
            btnCopy.style.background = '#2ecc71';
            setTimeout(() => {
                btnCopy.textContent = originalText;
                btnCopy.style.background = '';
            }, 2000);
        }).catch(err => {
            alert('No se pudo copiar de forma automática. Selecciona el código manualmente.');
        });
    });

});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
