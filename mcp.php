<?php
/**
 * mcp.php — Endpoint MCP (Model Context Protocol)
 * victor-alonso.es
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$serverCardPath = __DIR__ . '/.well-known/mcp/server-card.json';
if (file_exists($serverCardPath)) {
    echo file_get_contents($serverCardPath);
    exit;
}

echo json_encode([
    'jsonrpc' => '2.0',
    'result' => [
        'name' => 'victor-alonso-seo-tools',
        'version' => '1.0.0'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
