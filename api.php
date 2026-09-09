<?php
/**
 * api.php — Índice de API para agentes de IA
 * victor-alonso.es
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Link: </.well-known/api-catalog>; rel="api-catalog", </openapi.json>; rel="service-desc"', false);

echo json_encode([
    'name' => 'Víctor Alonso SEO Tools API',
    'version' => '1.0.0',
    'description' => 'Herramientas de auditoría SEO técnica y análisis de servidor',
    'documentation' => 'https://www.victor-alonso.es/llms.txt',
    'openapi' => 'https://www.victor-alonso.es/openapi.json',
    'endpoints' => [
        'seoPageAnalyzer' => 'https://www.victor-alonso.es/herramientas/analizador-seo/',
        'apacheNginxLogAnalyzer' => 'https://www.victor-alonso.es/herramientas/analizador-logs/'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
