<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/schema.php';
require_once __DIR__ . '/../includes/ratings-helper.php';

// Controlador AJAX de Valoraciones (Ratings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
    header('Content-Type: application/json');
    $tool_id = trim($_POST['tool_id'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    
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

// Configuración de metadatos de la página
$page = page_config([
    'title'        => 'Añadir Coordenadas GPS a Imágenes Gratis | Editor EXIF SEO Local',
    'description'  => 'Geolocaliza tus imágenes JPG al instante sin subir nada al servidor. Añade, edita o lee coordenadas GPS desde el navegador para potenciar tu SEO Local.',
    'canonical'    => '/herramientas/editor-gps-imagenes/',
    'body_class'   => 'page-tool-gps-exif',
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'editor-gps-imagenes',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Editor GPS en Imágenes', 'url' => ''],
    ],
    'faq_items'    => [
        [
            'q' => '¿Por qué geolocalizar mis imágenes ayuda al SEO Local?',
            'a' => 'Google rastrea y lee los metadatos EXIF de las imágenes originales que subes a tu web (y a tu perfil de Google Business Profile). Incluir coordenadas GPS exactas ayuda a validar y dar contexto semántico de ubicación a tus contenidos visuales, potenciando tu relevancia local.'
        ],
        [
            'q' => '¿Las imágenes se suben a tu servidor?',
            'a' => 'No. Esta herramienta procesa el 100% de los datos directamente en la memoria RAM de tu navegador gracias a JavaScript. Tus fotos originales jamás tocan mi servidor ni viajan por Internet, garantizando máxima privacidad.'
        ],
        [
            'q' => '¿Qué formatos soporta la herramienta?',
            'a' => 'Actualmente, el estándar de metadatos EXIF está altamente optimizado para el formato JPEG (.jpg o .jpeg). Te recomiendo subir siempre imágenes en JPG. Si necesitas hacerlo de forma masiva (en bulk) para grandes volúmenes, contáctame y te preparo un script a medida.'
        ]
    ]
]);

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/breadcrumbs.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/piexifjs@1.0.6/piexif.js"></script>

<main id="main">
  <section class="page-hero" aria-labelledby="gps-h1">
    <div class="container">
      <span class="page-hero-eyebrow">SEO Local y Privacidad</span>
      <h1 id="gps-h1">Añadir Coordenadas GPS a Imágenes (EXIF)</h1>
      <p class="page-hero-desc">Geolocaliza tus imágenes directamente desde el navegador, sin registros y sin subir nada a mis servidores. Potencia tu SEO Local asociando tus fotos al NAP de tu negocio.</p>
    </div>
  </section>

  <section class="section">
    <div class="container" style="max-width: 900px;">
      
      <!-- Cartel de Privacidad TOP -->
      <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); padding: 1.5rem; border-radius: 12px; margin-bottom: 2.5rem; display: flex; gap: 1.25rem; align-items: flex-start; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.02);">
        <i class="fa-solid fa-shield-check" style="color: #10b981; font-size: 1.75rem; margin-top: 0.2rem;"></i>
        <div>
          <h3 style="color: var(--black); font-size: 1.1rem; margin-top: 0; margin-bottom: 0.5rem; font-weight: 700;">✨ 100% Privado: Todo ocurre en tu navegador</h3>
          <p style="margin: 0; font-size: 0.95rem; line-height: 1.6; color: var(--text);">
            A diferencia de otras herramientas que te piden subir la imagen a su servidor (para luego "borrarla"), yo he desarrollado esto en puro JavaScript. <strong>La imagen nunca sale de tu dispositivo</strong>. Es instantáneo, seguro y privado. (PD: Si quieres geolocalizar imágenes en lote/bulk automáticamente, ¡escríbeme!).
          </p>
        </div>
      </div>

      <!-- Sistema de Pestañas (Tabs) -->
      <div class="tabs-container" style="margin-bottom: 2rem;">
        <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--bordergray); margin-bottom: 1.5rem;">
          <button id="tab-write-btn" class="tab-btn active" style="padding: 1rem 1.5rem; background: transparent; border: none; font-size: 1.05rem; font-weight: 700; color: var(--orange); border-bottom: 3px solid var(--orange); cursor: pointer; transform: translateY(2px); transition: all 0.2s;">
            <i class="fa-solid fa-location-dot" style="margin-right: 0.5rem;"></i> Añadir Metadatos
          </button>
          <button id="tab-read-btn" class="tab-btn" style="padding: 1rem 1.5rem; background: transparent; border: none; font-size: 1.05rem; font-weight: 600; color: var(--muted); border-bottom: 3px solid transparent; cursor: pointer; transform: translateY(2px); transition: all 0.2s;">
            <i class="fa-solid fa-magnifying-glass" style="margin-right: 0.5rem;"></i> Leer Metadatos
          </button>
        </div>

        <!-- Pestaña: ESCRIBIR (AÑADIR) METADATOS -->
        <div id="tab-write-content" class="tab-content" style="display: block;">
          <div class="card" style="padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);">
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
              
              <!-- Subida de imagen -->
              <div id="dropzone-write" style="border: 2px dashed rgba(34, 49, 63, 0.2); border-radius: 12px; padding: 2.5rem 1.5rem; text-align: center; background: var(--lightgray); cursor: pointer; transition: all 0.25s ease; position: relative;">
                <input type="file" id="gps-img-write" accept="image/jpeg, image/jpg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10;">
                
                <div id="preview-write-container" style="display: none; position: relative; z-index: 5;">
                   <img id="img-preview" src="" style="max-height: 200px; max-width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin: 0 auto 1rem auto; display: block;" />
                   <p id="filename-preview" style="font-weight: 600; color: var(--black); margin-bottom: 0;"></p>
                </div>

                <div id="prompt-write">
                  <i class="fa-solid fa-image" style="font-size: 3rem; color: var(--orange); margin-bottom: 1.25rem; display: block;"></i>
                  <span style="font-size: 1.1rem; font-weight: 700; color: var(--black); display: block; margin-bottom: 0.5rem;">Arrastra tu imagen JPG aquí</span>
                  <span style="font-size: 0.9rem; color: var(--muted);">o haz clic para explorar</span>
                </div>
              </div>

              <!-- Controles de Ubicación y Mapa -->
              <div>
                <label style="display: block; font-weight: 700; color: var(--black); margin-bottom: 0.5rem; font-size: 0.95rem;">
                  📍 Buscar dirección (o pincha en el mapa):
                </label>
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                  <input type="text" id="search-address" placeholder="Ej: Puerta del Sol, Madrid..." style="flex: 1; padding: 0.75rem 1rem; border: 1px solid rgba(34, 49, 63, 0.2); border-radius: 8px; font-size: 0.95rem;">
                  <button type="button" id="btn-search-map" class="btn btn--secondary" style="white-space: nowrap;">Buscar</button>
                </div>
                
                <div id="map-write" style="height: 300px; border-radius: 12px; border: 1px solid rgba(34,49,63,0.1); margin-bottom: 1rem; z-index: 1;"></div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                  <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--muted); margin-bottom: 0.25rem;">Latitud:</label>
                    <input type="text" id="input-lat" readonly style="width: 100%; padding: 0.5rem; border: 1px solid rgba(0,0,0,0.1); border-radius: 4px; background: #f8f9fa;">
                  </div>
                  <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--muted); margin-bottom: 0.25rem;">Longitud:</label>
                    <input type="text" id="input-lng" readonly style="width: 100%; padding: 0.5rem; border: 1px solid rgba(0,0,0,0.1); border-radius: 4px; background: #f8f9fa;">
                  </div>
                </div>

                <!-- Campos adicionales opcionales EXIF -->
                <details style="margin-bottom: 1.5rem; background: #f8f9fa; border: 1px solid rgba(0,0,0,0.05); border-radius: 8px; padding: 1rem;">
                  <summary style="font-weight: 600; cursor: pointer; color: var(--orange);">➕ Opciones avanzadas (Opcional)</summary>
                  <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                      <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--black); margin-bottom: 0.25rem;">Autor / Artista (Author):</label>
                      <input type="text" id="input-artist" placeholder="Ej: Víctor Alonso" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(0,0,0,0.2); border-radius: 4px;">
                    </div>
                    <div>
                      <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--black); margin-bottom: 0.25rem;">Descripción (ImageDescription):</label>
                      <input type="text" id="input-desc" placeholder="Ej: Fachada de mi negocio en Albacete" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(0,0,0,0.2); border-radius: 4px;">
                    </div>
                  </div>
                </details>

                <button id="btn-generate" class="btn btn--primary btn--lg" style="width: 100%; justify-content: center;" disabled>
                  <i class="fa-solid fa-download" style="margin-right: 0.5rem;"></i> Añadir GPS y Descargar
                </button>

              </div>
            </div>

          </div>
        </div>

        <!-- Pestaña: LEER METADATOS -->
        <div id="tab-read-content" class="tab-content" style="display: none;">
          <div class="card" style="padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);">
            <h2 style="font-size: 1.4rem; margin-bottom: 1rem; color: var(--black);">Verifica los metadatos de tu imagen</h2>
            <p style="font-size: 0.95rem; line-height: 1.6; color: var(--muted); margin-bottom: 2rem;">
              Comprueba si una imagen ya cuenta con datos de geolocalización o autor. Súbela (en local) para auditarla.
            </p>

            <div id="dropzone-read" style="border: 2px dashed rgba(34, 49, 63, 0.2); border-radius: 12px; padding: 2.5rem 1.5rem; text-align: center; background: var(--lightgray); cursor: pointer; transition: all 0.25s ease; position: relative;">
              <input type="file" id="gps-img-read" accept="image/jpeg, image/jpg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10;">
              
              <div id="prompt-read">
                <i class="fa-solid fa-magnifying-glass-location" style="font-size: 3rem; color: var(--orange); margin-bottom: 1.25rem; display: block;"></i>
                <span style="font-size: 1.1rem; font-weight: 700; color: var(--black); display: block; margin-bottom: 0.5rem;">Arrastra aquí tu imagen JPG para leerla</span>
              </div>
            </div>

            <!-- Resultados LECTURA -->
            <div id="read-results" style="display: none; margin-top: 2rem; border-top: 1px solid var(--bordergray); padding-top: 1.5rem;">
              <h3 style="font-size: 1.2rem; color: var(--black); margin-bottom: 1rem;">🔍 Resultados EXIF:</h3>
              
              <div id="read-map" style="height: 250px; border-radius: 12px; border: 1px solid rgba(34,49,63,0.1); margin-bottom: 1.5rem; display: none;"></div>

              <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                  <tbody id="exif-tbody">
                    <!-- Filas inyectadas por JS -->
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- FAQ -->
  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <!-- Valoraciones -->
  <?php render_rating_widget('editor-gps-imagenes', '¿Te ha resultado útil esta herramienta de privacidad?'); ?>

  <!-- CTA -->
  <?php
  $cta = [
    'title'     => '¿Tienes un gran volumen de imágenes para geolocalizar?',
    'subtitle'  => 'Si eres fotógrafo, agencia inmobiliaria o gestionas muchos locales y necesitas geolocalizar miles de imágenes de forma masiva (en bulk), no lo hagas a mano. Escríbeme y preparo un script automatizado para tu caso.',
    'btn_label' => 'Contactar para Script Bulk',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<style>
/* Estilos para pestañas */
.tab-btn:hover {
  color: var(--orange) !important;
}
#dropzone-write.dragover, #dropzone-read.dragover {
  border-color: var(--orange) !important;
  background: rgba(232, 104, 26, 0.03) !important;
}
.table-responsive table th, .table-responsive table td {
  padding: 0.75rem;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  text-align: left;
}
.table-responsive table td:first-child {
  font-weight: 600;
  color: var(--black);
  width: 30%;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA DE PESTAÑAS ---
    const tabWriteBtn = document.getElementById('tab-write-btn');
    const tabReadBtn = document.getElementById('tab-read-btn');
    const tabWriteContent = document.getElementById('tab-write-content');
    const tabReadContent = document.getElementById('tab-read-content');
    
    let writeMap, writeMarker;
    let readMap, readMarker;

    tabWriteBtn.addEventListener('click', () => {
        tabWriteBtn.classList.add('active');
        tabWriteBtn.style.borderBottomColor = 'var(--orange)';
        tabWriteBtn.style.color = 'var(--orange)';
        tabWriteBtn.style.fontWeight = '700';

        tabReadBtn.classList.remove('active');
        tabReadBtn.style.borderBottomColor = 'transparent';
        tabReadBtn.style.color = 'var(--muted)';
        tabReadBtn.style.fontWeight = '600';

        tabWriteContent.style.display = 'block';
        tabReadContent.style.display = 'none';
        if(writeMap) writeMap.invalidateSize();
    });

    tabReadBtn.addEventListener('click', () => {
        tabReadBtn.classList.add('active');
        tabReadBtn.style.borderBottomColor = 'var(--orange)';
        tabReadBtn.style.color = 'var(--orange)';
        tabReadBtn.style.fontWeight = '700';

        tabWriteBtn.classList.remove('active');
        tabWriteBtn.style.borderBottomColor = 'transparent';
        tabWriteBtn.style.color = 'var(--muted)';
        tabWriteBtn.style.fontWeight = '600';

        tabReadContent.style.display = 'block';
        tabWriteContent.style.display = 'none';
        
        // Inicializar mapa de lectura si se muestran resultados
        if(readMap) readMap.invalidateSize();
    });

    // --- MAPA DE ESCRITURA (LEAFLET) ---
    // Centro inicial: Madrid
    writeMap = L.map('map-write').setView([40.416775, -3.703790], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(writeMap);

    writeMarker = L.marker([40.416775, -3.703790], {draggable: true}).addTo(writeMap);
    updateInputs(40.416775, -3.703790);

    writeMarker.on('dragend', function(e) {
        const pos = writeMarker.getLatLng();
        updateInputs(pos.lat, pos.lng);
    });

    writeMap.on('click', function(e) {
        writeMarker.setLatLng(e.latlng);
        updateInputs(e.latlng.lat, e.latlng.lng);
    });

    function updateInputs(lat, lng) {
        document.getElementById('input-lat').value = lat.toFixed(6);
        document.getElementById('input-lng').value = lng.toFixed(6);
    }

    // Buscador Nominatim (OpenStreetMap)
    document.getElementById('btn-search-map').addEventListener('click', () => {
        const query = document.getElementById('search-address').value.trim();
        if(!query) return;

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if(data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    writeMap.setView([lat, lon], 15);
                    writeMarker.setLatLng([lat, lon]);
                    updateInputs(lat, lon);
                } else {
                    alert("Dirección no encontrada. Prueba con algo más genérico.");
                }
            })
            .catch(err => console.error(err));
    });

    // --- LÓGICA DE DRAG & DROP (ESCRITURA) ---
    let currentImageBase64 = null;
    let currentImageName = "";

    const dropWrite = document.getElementById('dropzone-write');
    const inputWrite = document.getElementById('gps-img-write');
    const btnGenerate = document.getElementById('btn-generate');

    ['dragenter', 'dragover'].forEach(evt => dropWrite.addEventListener(evt, e => {
        e.preventDefault(); dropWrite.classList.add('dragover');
    }, false));
    ['dragleave', 'drop'].forEach(evt => dropWrite.addEventListener(evt, e => {
        e.preventDefault(); dropWrite.classList.remove('dragover');
    }, false));

    inputWrite.addEventListener('change', function(e) {
        if(this.files && this.files[0]) processFileWrite(this.files[0]);
    });

    function processFileWrite(file) {
        if(!file.type.match('image/jpeg')) {
            alert("Por favor, sube una imagen JPG o JPEG.");
            return;
        }
        currentImageName = file.name;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            currentImageBase64 = e.target.result;
            
            document.getElementById('prompt-write').style.display = 'none';
            document.getElementById('preview-write-container').style.display = 'block';
            document.getElementById('img-preview').src = currentImageBase64;
            document.getElementById('filename-preview').textContent = currentImageName;
            
            btnGenerate.disabled = false;
        };
        reader.readAsDataURL(file);
    }

    // Convertir de decimal a formato GPS EXIF
    function decimalToGpsFormat(decimal) {
        const degrees = Math.floor(decimal);
        const minFloat = (decimal - degrees) * 60;
        const minutes = Math.floor(minFloat);
        const secFloat = (minFloat - minutes) * 60;
        const seconds = Math.round(secFloat * 100);

        return [
            [degrees, 1],
            [minutes, 1],
            [seconds, 100]
        ];
    }

    // --- GENERAR Y DESCARGAR ---
    btnGenerate.addEventListener('click', () => {
        if(!currentImageBase64) return;

        let lat = parseFloat(document.getElementById('input-lat').value);
        let lng = parseFloat(document.getElementById('input-lng').value);
        let artist = document.getElementById('input-artist').value.trim();
        let desc = document.getElementById('input-desc').value.trim();

        // Calcular referencias (N/S, E/W)
        const latRef = lat >= 0 ? "N" : "S";
        const lngRef = lng >= 0 ? "E" : "W";

        lat = Math.abs(lat);
        lng = Math.abs(lng);

        const gpsLat = decimalToGpsFormat(lat);
        const gpsLng = decimalToGpsFormat(lng);

        try {
            // Leer EXIF actual (o crear uno nuevo si no existe)
            let exifObj;
            try {
                exifObj = piexif.load(currentImageBase64);
            } catch (err) {
                exifObj = {"0th":{}, "Exif":{}, "GPS":{}, "Interop":{}, "1st":{}, "thumbnail":null};
            }

            // Inyectar GPS
            exifObj["GPS"][piexif.GPSIFD.GPSLatitudeRef] = latRef;
            exifObj["GPS"][piexif.GPSIFD.GPSLatitude] = gpsLat;
            exifObj["GPS"][piexif.GPSIFD.GPSLongitudeRef] = lngRef;
            exifObj["GPS"][piexif.GPSIFD.GPSLongitude] = gpsLng;

            // Inyectar Autor y Descripción si existen
            if(artist) exifObj["0th"][piexif.ImageIFD.Artist] = artist;
            if(desc) exifObj["0th"][piexif.ImageIFD.ImageDescription] = desc;

            const exifBytes = piexif.dump(exifObj);
            const newJpegData = piexif.insert(exifBytes, currentImageBase64);

            // Descargar archivo modificado
            const a = document.createElement('a');
            a.href = newJpegData;
            a.download = currentImageName.replace(/\.[^/.]+$/, "") + "-geolocalizada.jpg";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

        } catch (error) {
            console.error(error);
            alert("Ocurrió un error al inyectar los metadatos. Asegúrate de que la imagen sea JPG válido.");
        }
    });

    // --- LÓGICA DE LECTURA (READ EXIF) ---
    const dropRead = document.getElementById('dropzone-read');
    const inputRead = document.getElementById('gps-img-read');

    ['dragenter', 'dragover'].forEach(evt => dropRead.addEventListener(evt, e => {
        e.preventDefault(); dropRead.classList.add('dragover');
    }, false));
    ['dragleave', 'drop'].forEach(evt => dropRead.addEventListener(evt, e => {
        e.preventDefault(); dropRead.classList.remove('dragover');
    }, false));

    inputRead.addEventListener('change', function(e) {
        if(this.files && this.files[0]) processFileRead(this.files[0]);
    });

    function getGpsDecimal(gpsData, ref) {
        if (!gpsData || gpsData.length !== 3) return null;
        let d = gpsData[0][0] / gpsData[0][1];
        let m = gpsData[1][0] / gpsData[1][1];
        let s = gpsData[2][0] / gpsData[2][1];
        let decimal = d + (m / 60) + (s / 3600);
        if (ref === "S" || ref === "W") decimal = decimal * -1;
        return decimal;
    }

    function processFileRead(file) {
        if(!file.type.match('image/jpeg')) {
            alert("Por favor, sube una imagen JPG o JPEG.");
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const dataUrl = e.target.result;
            
            document.getElementById('read-results').style.display = 'block';
            const tbody = document.getElementById('exif-tbody');
            tbody.innerHTML = ''; // Limpiar resultados anteriores
            
            try {
                const exifObj = piexif.load(dataUrl);
                let hasExif = false;
                let parsedLat = null;
                let parsedLng = null;

                // Extraer 0th (Autor, Desc)
                if (exifObj["0th"]) {
                    if(exifObj["0th"][piexif.ImageIFD.Artist]) {
                        addRow(tbody, 'Autor (Artist)', exifObj["0th"][piexif.ImageIFD.Artist]);
                        hasExif = true;
                    }
                    if(exifObj["0th"][piexif.ImageIFD.ImageDescription]) {
                        addRow(tbody, 'Descripción', exifObj["0th"][piexif.ImageIFD.ImageDescription]);
                        hasExif = true;
                    }
                }

                // Extraer GPS
                if(exifObj["GPS"] && exifObj["GPS"][piexif.GPSIFD.GPSLatitude]) {
                    const latRef = exifObj["GPS"][piexif.GPSIFD.GPSLatitudeRef] || "N";
                    const lngRef = exifObj["GPS"][piexif.GPSIFD.GPSLongitudeRef] || "E";
                    
                    parsedLat = getGpsDecimal(exifObj["GPS"][piexif.GPSIFD.GPSLatitude], latRef);
                    parsedLng = getGpsDecimal(exifObj["GPS"][piexif.GPSIFD.GPSLongitude], lngRef);
                    
                    addRow(tbody, 'Latitud', parsedLat.toFixed(6) + ' (' + latRef + ')');
                    addRow(tbody, 'Longitud', parsedLng.toFixed(6) + ' (' + lngRef + ')');
                    hasExif = true;

                    // Mostrar en el mapa de lectura
                    document.getElementById('read-map').style.display = 'block';
                    if(!readMap) {
                        readMap = L.map('read-map').setView([parsedLat, parsedLng], 14);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(readMap);
                        readMarker = L.marker([parsedLat, parsedLng]).addTo(readMap);
                    } else {
                        readMap.setView([parsedLat, parsedLng], 14);
                        readMarker.setLatLng([parsedLat, parsedLng]);
                    }
                    // Dar tiempo al dom
                    setTimeout(()=> readMap.invalidateSize(), 100);
                } else {
                    document.getElementById('read-map').style.display = 'none';
                }

                if(!hasExif) {
                    addRow(tbody, 'Estado', '❌ No se encontraron metadatos EXIF útiles o GPS en esta imagen.');
                } else {
                    addRow(tbody, 'Estado General', '✅ Metadatos leídos correctamente.');
                }

            } catch(err) {
                console.error(err);
                addRow(tbody, 'Error', 'No se pudieron procesar los metadatos. Es posible que la imagen haya sido guardada para web y limpiada.');
                document.getElementById('read-map').style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    }

    function addRow(tbody, key, value) {
        const tr = document.createElement('tr');
        const td1 = document.createElement('td');
        td1.textContent = key;
        const td2 = document.createElement('td');
        td2.textContent = value;
        tr.appendChild(td1);
        tr.appendChild(td2);
        tbody.appendChild(tr);
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
