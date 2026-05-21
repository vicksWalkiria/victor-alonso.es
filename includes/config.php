<?php
/**
 * config.php — Configuración global del sitio
 * victor-alonso.es
 */

// ─── Ruta base del proyecto ─────────────────────────────────────────────────
define('BASE_DIR', dirname(__DIR__));  // public_html/

// ─── Constantes globales ────────────────────────────────────────────────────

define('SITE_NAME',         'Víctor Alonso SEO');
define('SITE_URL',          'https://www.victor-alonso.es');
define('SITE_PHONE',        '+34 675 946 486');
define('SITE_PHONE_RAW',    '+34675946486');
define('SITE_EMAIL',        'victor@walkiriaapps.com');
define('SITE_LOCALITY',     'Albacete');
define('SITE_COUNTRY',      'ES');
define('SITE_LANG',         'es-ES');
define('SITE_TWITTER',      '@vicks630');
define('SITE_LINKEDIN',     'https://www.linkedin.com/in/vialonso/');
define('SITE_GITHUB',       'https://github.com/vicksWalkiria');
define('SITE_TWITTER_URL',  'https://x.com/vicks630');
define('SITE_WALKIRIA',     'https://www.walkiriaapps.com');
define('SITE_IMAGE',        'https://www.victor-alonso.es/social.webp');
define('SITE_AUTHOR_IMAGE', 'https://walkiriaapps.com/wp-content/uploads/2021/11/victor-alonso-desarrollador-freelance.jpeg');
define('FORMSPREE',         'https://formspree.io/f/xwpkllpr');

// ─── Helpers ────────────────────────────────────────────────────────────────

/**
 * Escapa HTML de forma segura.
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Configura los metadatos de la página.
 *
 * Opciones disponibles:
 *   title        string  Título de la página (sin el sufijo de marca)
 *   description  string  Meta description
 *   canonical    string  Ruta relativa, ej: '/servicios/seo-albacete.php'
 *   og_image     string  URL absoluta de imagen OG (por defecto: SITE_IMAGE)
 *   body_class   string  Clase CSS del body
 *   schema_types array   Tipos de schema a renderizar, ej: ['Service', 'FAQPage']
 *   breadcrumbs  array   Array de ['label' => '', 'url' => ''] (la home se añade automáticamente)
 *   noindex      bool    true para añadir noindex,nofollow
 *   active_nav   string  Sección activa del menú: 'inicio', 'servicios', 'sobre-mi', 'casos', 'contacto'
 *   map          bool    true para cargar Leaflet en esta página
 *   faq_items    array   Array de ['q' => '', 'a' => ''] para schema FAQPage
 *   service_name string  Nombre del servicio (para schema Service)
 */
function page_config(array $opts): array {
    $defaults = [
        'title'        => SITE_NAME,
        'description'  => '',
        'canonical'    => '/',
        'og_image'     => SITE_IMAGE,
        'body_class'   => 'page',
        'schema_types' => [],
        'breadcrumbs'  => [],
        'noindex'      => false,
        'active_nav'   => 'inicio',
        'map'          => false,
        'faq_items'    => [],
        'service_name' => '',
    ];
    return array_merge($defaults, $opts);
}
