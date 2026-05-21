<?php
/**
 * schema.php — Sistema de schemas JSON-LD
 * Requiere config.php cargado previamente.
 */

/**
 * Renderiza todos los bloques JSON-LD para la página.
 */
function render_schemas(array $page): void {
    // ── Schemas globales (todas las páginas) ──────────────────────────────
    _schema_website();
    _schema_person();
    _schema_breadcrumbs($page);

    // ── Schemas condicionales ──────────────────────────────────────────────
    $types = $page['schema_types'] ?? [];

    if (in_array('LocalBusiness', $types)) {
        _schema_local_business();
    }

    if (in_array('Service', $types) && !empty($page['service_name'])) {
        _schema_service($page['service_name'], $page['description'], $page['canonical']);
    }

    if (in_array('FAQPage', $types) && !empty($page['faq_items'])) {
        _schema_faq($page['faq_items']);
    }

    if (in_array('AboutPage', $types)) {
        _schema_about_page($page['canonical']);
    }

    if (in_array('ContactPage', $types)) {
        _schema_contact_page($page['canonical']);
    }

    if (in_array('WebApplication', $types) && !empty($page['rating_id'])) {
        require_once __DIR__ . '/ratings-helper.php';
        $ratings = get_ratings();
        $rating_data = $ratings[$page['rating_id']] ?? null;
        if ($rating_data) {
            _schema_web_application($page['title'], $page['description'], $page['canonical'], $rating_data);
        }
    }
}

// ─── Schemas privados ────────────────────────────────────────────────────────

function _print_schema(array $data): void {
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n" . '</script>' . "\n";
}

function _schema_website(): void {
    _print_schema([
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => SITE_NAME,
        'url'      => SITE_URL,
    ]);
}

function _schema_person(): void {
    _print_schema([
        '@context'    => 'https://schema.org',
        '@type'       => 'Person',
        '@id'         => SITE_URL . '/#person',
        'name'        => 'Víctor Alonso',
        'jobTitle'    => 'Consultor SEO e Ingeniero Informático',
        'description' => 'Ingeniero informático, consultor SEO técnico y desarrollador WordPress/PHP especializado en diagnóstico, optimización y mantenimiento web para negocios en Albacete y toda España.',
        'url'         => SITE_URL,
        'image'       => SITE_AUTHOR_IMAGE,
        'telephone'   => SITE_PHONE_RAW,
        'email'       => SITE_EMAIL,
        'address'     => [
            '@type'           => 'PostalAddress',
            'addressLocality' => SITE_LOCALITY,
            'addressCountry'  => SITE_COUNTRY,
        ],
        'sameAs' => [
            SITE_LINKEDIN,
            SITE_TWITTER_URL,
            SITE_GITHUB,
            SITE_WALKIRIA,
        ],
    ]);
}

function _schema_local_business(): void {
    _print_schema([
        '@context'    => 'https://schema.org',
        '@type'       => ['LocalBusiness', 'ProfessionalService'],
        '@id'         => SITE_URL . '/#business',
        'name'        => SITE_NAME,
        'url'         => SITE_URL,
        'telephone'   => SITE_PHONE_RAW,
        'email'       => SITE_EMAIL,
        'image'       => SITE_IMAGE,
        'founder'     => ['@id' => SITE_URL . '/#person'],
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'Calle Iris 25',
            'addressLocality' => SITE_LOCALITY,
            'addressRegion'   => 'Castilla-La Mancha',
            'postalCode'      => '02005',
            'addressCountry'  => SITE_COUNTRY,
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => '38.9978354',
            'longitude' => '-1.8567414',
        ],
        'areaServed' => [
            ['@type' => 'City', 'name' => 'Albacete'],
            ['@type' => 'State', 'name' => 'Castilla-La Mancha'],
            ['@type' => 'Country', 'name' => 'España'],
        ],
        'sameAs' => [
            SITE_LINKEDIN,
            SITE_TWITTER_URL,
            SITE_GITHUB,
        ],
    ]);
}

function _schema_service(string $name, string $description, string $canonical): void {
    _print_schema([
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        'name'        => $name,
        'description' => $description,
        'url'         => SITE_URL . $canonical,
        'provider'    => ['@id' => SITE_URL . '/#person'],
        'areaServed'  => [
            ['@type' => 'Country', 'name' => 'España'],
        ],
        'serviceType' => $name,
    ]);
}

function _schema_faq(array $items): void {
    $entities = [];
    foreach ($items as $item) {
        $entities[] = [
            '@type'          => 'Question',
            'name'           => $item['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $item['a'],
            ],
        ];
    }
    _print_schema([
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $entities,
    ]);
}

function _schema_breadcrumbs(array $page): void {
    $items = [
        [
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Inicio',
            'item'     => SITE_URL . '/',
        ],
    ];

    $pos = 2;
    foreach ($page['breadcrumbs'] as $crumb) {
        $item = [
            '@type'    => 'ListItem',
            'position' => $pos,
            'name'     => $crumb['label'],
        ];
        if (!empty($crumb['url'])) {
            $item['item'] = SITE_URL . $crumb['url'];
        }
        $items[] = $item;
        $pos++;
    }

    _print_schema([
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ]);
}

function _schema_about_page(string $canonical): void {
    _print_schema([
        '@context'  => 'https://schema.org',
        '@type'     => 'AboutPage',
        'url'       => SITE_URL . $canonical,
        'name'      => 'Sobre Víctor Alonso — Consultor SEO e Ingeniero Informático',
        'about'     => ['@id' => SITE_URL . '/#person'],
        'publisher' => ['@id' => SITE_URL . '/#person'],
    ]);
}

function _schema_contact_page(string $canonical): void {
    _print_schema([
        '@context'     => 'https://schema.org',
        '@type'        => 'ContactPage',
        'url'          => SITE_URL . $canonical,
        'name'         => 'Contacto — Víctor Alonso SEO',
        'mainEntity'   => ['@id' => SITE_URL . '/#person'],
    ]);
}

function _schema_web_application(string $name, string $description, string $canonical, array $rating_data): void {
    _print_schema([
        '@context'          => 'https://schema.org',
        '@type'             => 'WebApplication',
        'name'              => $name,
        'description'       => $description,
        'url'               => SITE_URL . $canonical,
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem'   => 'All',
        'browserRequirements' => 'Requires HTML5, CSS3, JavaScript',
        'provider'          => ['@id' => SITE_URL . '/#person'],
        'aggregateRating'   => [
            '@type'       => 'AggregateRating',
            'ratingValue' => (string)$rating_data['average'],
            'ratingCount' => (string)$rating_data['count'],
            'bestRating'  => '5',
            'worstRating' => '1'
        ]
    ]);
}
