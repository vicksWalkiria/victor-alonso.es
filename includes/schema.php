<?php
/**
 * schema.php — Sistema de schemas JSON-LD con @graph
 * Requiere config.php cargado previamente.
 *
 * Arquitectura:
 *   - render_schemas() recopila todos los nodos y emite UN solo bloque @graph.
 *   - Entidades raíz reutilizables:
 *       WebSite        → SITE_URL/#website
 *       Person         → SITE_URL/#person
 *       LocalBusiness  → SITE_URL/#localbusiness   (solo en páginas que lo necesitan)
 *   - Los servicios apuntan a LocalBusiness mediante provider.@id
 */

// ─── Punto de entrada ────────────────────────────────────────────────────────

/**
 * Recopila todos los nodos JSON-LD y los emite como un único bloque @graph.
 */
function render_schemas(array $page): void {
    $nodes = [];

    // ── Nodos globales (todas las páginas) ───────────────────────────────────
    $nodes[] = _node_website();
    $nodes[] = _node_person();
    $nodes[] = _node_breadcrumbs($page);

    // ── Nodos condicionales ──────────────────────────────────────────────────
    $types = $page['schema_types'] ?? [];

    if (in_array('LocalBusiness', $types) || in_array('Service', $types)) {
        $nodes[] = _node_local_business();
    }

    if (in_array('Service', $types) && !empty($page['service_data'])) {
        $nodes[] = _node_service($page['service_data'], $page['description']);
    }

    if (in_array('FAQPage', $types) && !empty($page['faq_items'])) {
        $nodes[] = _node_faq($page['faq_items']);
    }

    if (in_array('AboutPage', $types)) {
        $nodes[] = _node_about_page($page['canonical']);
    }

    if (in_array('ContactPage', $types)) {
        $nodes[] = _node_contact_page($page['canonical']);
    }

    if (in_array('WebApplication', $types) && !empty($page['rating_id'])) {
        require_once __DIR__ . '/ratings-helper.php';
        $ratings    = get_ratings();
        $rating_data = $ratings[$page['rating_id']] ?? null;
        if ($rating_data) {
            $nodes[] = _node_web_application($page['title'], $page['description'], $page['canonical'], $rating_data);
        }
    }

    if (in_array('ItemList', $types) && !empty($page['item_list'])) {
        $nodes[] = _node_item_list($page['title'], $page['canonical'], $page['item_list']);
    }

    // ── Emitir único bloque @graph ───────────────────────────────────────────
    _emit_graph($nodes);
}

// ─── Emisor ──────────────────────────────────────────────────────────────────

function _emit_graph(array $nodes): void {
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => array_values(array_filter($nodes)),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n" . '</script>' . "\n";
}

// ─── Nodos ───────────────────────────────────────────────────────────────────

function _node_website(): array {
    return [
        '@type' => 'WebSite',
        '@id'   => SITE_URL . '/#website',
        'name'  => SITE_NAME,
        'url'   => SITE_URL,
    ];
}

function _node_person(): array {
    return [
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
    ];
}

function _node_local_business(): array {
    return [
        '@type'     => ['LocalBusiness', 'ProfessionalService'],
        '@id'       => SITE_URL . '/#localbusiness',
        'name'      => SITE_NAME,
        'url'       => SITE_URL,
        'telephone' => SITE_PHONE_RAW,
        'email'     => SITE_EMAIL,
        'image'     => SITE_IMAGE,
        'founder'   => ['@id' => SITE_URL . '/#person'],
        'address'   => [
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
            ['@type' => 'City',    'name' => 'Albacete'],
            ['@type' => 'State',   'name' => 'Castilla-La Mancha'],
            ['@type' => 'Country', 'name' => 'España'],
        ],
        'sameAs' => [
            SITE_LINKEDIN,
            SITE_TWITTER_URL,
            SITE_GITHUB,
        ],
    ];
}

/**
 * Nodo Service enriquecido.
 *
 * $data keys soportados:
 *   @id          string   — URI con fragmento, p.ej. '/servicios/seo-albacete/#service'
 *   name         string   — nombre principal del servicio
 *   alternateName array   — nombres alternativos (opcional)
 *   serviceType  string   — categoría del servicio
 *   areaServed   array    — lista de entidades geográficas (opcional)
 *   offers       array    — ['minPrice' => int] (opcional)
 */
function _node_service(array $data, string $description): array {
    $node = [
        '@type'       => 'Service',
        '@id'         => SITE_URL . ($data['@id'] ?? '/#service'),
        'name'        => $data['name'] ?? '',
        'description' => $description,
        'provider'    => ['@id' => SITE_URL . '/#localbusiness'],
    ];

    if (!empty($data['alternateName'])) {
        $node['alternateName'] = $data['alternateName'];
    }

    if (!empty($data['serviceType'])) {
        $node['serviceType'] = $data['serviceType'];
    }

    if (!empty($data['areaServed'])) {
        $node['areaServed'] = $data['areaServed'];
    }

    if (!empty($data['offers']['minPrice'])) {
        $node['offers'] = [
            '@type'              => 'Offer',
            'priceCurrency'      => 'EUR',
            'priceSpecification' => [
                '@type'        => 'PriceSpecification',
                'minPrice'     => $data['offers']['minPrice'],
                'priceCurrency' => 'EUR',
            ],
        ];
    }

    return $node;
}

function _node_faq(array $items): array {
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
    return [
        '@type'      => 'FAQPage',
        'mainEntity' => $entities,
    ];
}

function _node_breadcrumbs(array $page): array {
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

    return [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}

function _node_about_page(string $canonical): array {
    return [
        '@type'     => 'AboutPage',
        'url'       => SITE_URL . $canonical,
        'name'      => 'Sobre Víctor Alonso — Consultor SEO e Ingeniero Informático',
        'about'     => ['@id' => SITE_URL . '/#person'],
        'publisher' => ['@id' => SITE_URL . '/#person'],
    ];
}

function _node_contact_page(string $canonical): array {
    return [
        '@type'      => 'ContactPage',
        'url'        => SITE_URL . $canonical,
        'name'       => 'Contacto — Víctor Alonso SEO',
        'mainEntity' => ['@id' => SITE_URL . '/#person'],
    ];
}

function _node_web_application(string $name, string $description, string $canonical, array $rating_data): array {
    return [
        '@type'                => 'WebApplication',
        'name'                 => $name,
        'description'          => $description,
        'url'                  => SITE_URL . $canonical,
        'applicationCategory'  => 'DeveloperApplication',
        'operatingSystem'      => 'Web',
        'browserRequirements'  => 'Requiere JavaScript',
        'isAccessibleForFree'  => true,
        'creator'              => ['@id' => SITE_URL . '/#person'],
        'offers'               => [
            '@type'         => 'Offer',
            'price'         => '0',
            'priceCurrency' => 'EUR',
        ],
        'provider'             => ['@id' => SITE_URL . '/#localbusiness'],
        'aggregateRating'      => [
            '@type'       => 'AggregateRating',
            'ratingValue' => (string)$rating_data['average'],
            'ratingCount' => (string)$rating_data['count'],
            'bestRating'  => '5',
            'worstRating' => '1',
        ],
    ];
}

function _node_item_list(string $name, string $canonical, array $items): array {
    $list_items = [];
    $pos = 1;
    foreach ($items as $item) {
        if (empty($item['name']) || empty($item['url'])) {
            continue;
        }
        $list_items[] = [
            '@type'    => 'ListItem',
            'position' => $pos,
            'name'     => $item['name'],
            'url'      => SITE_URL . $item['url'],
        ];
        $pos++;
    }
    if (empty($list_items)) {
        return [];
    }
    return [
        '@type'           => 'ItemList',
        'name'            => $name,
        'url'             => SITE_URL . $canonical,
        'itemListElement' => $list_items,
    ];
}

// ─── BC shims: mantienen compatibilidad con código antiguo ───────────────────
// Eliminables una vez que todas las páginas usen service_data.

/** @deprecated Usar service_data en page_config() */
function _schema_service(string $name, string $description, string $canonical): void {}
