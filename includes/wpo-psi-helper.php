<?php
/**
 * Caché y rate limiting para PageSpeed Insights (calculadora WPO).
 */

define('WPO_PSI_CACHE_DIR', BASE_DIR . '/data/wpo-psi-cache');
define('WPO_PSI_RATE_DIR', BASE_DIR . '/data/wpo-psi-rate');
define('WPO_PSI_CACHE_TTL', 12 * 3600); // 12 h — las métricas no cambian cada minuto
define('WPO_PSI_RATE_SECONDS', 90);    // mínimo entre llamadas reales a Google por IP

function wpo_psi_ensure_dir(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function wpo_psi_normalize_url(string $url): string {
    $url = trim($url);
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }
    return rtrim(strtolower($url), '/');
}

function wpo_psi_cache_path(string $url): string {
    return WPO_PSI_CACHE_DIR . '/' . hash('sha256', wpo_psi_normalize_url($url)) . '.json';
}

/**
 * @return array{score:int,tier:string,metrics:array}|null
 */
function wpo_psi_cache_get(string $url): ?array {
    $path = wpo_psi_cache_path($url);
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false) {
        return null;
    }
    $entry = json_decode($raw, true);
    if (!is_array($entry) || empty($entry['metrics']) || empty($entry['cached_at'])) {
        return null;
    }
    if (time() - (int)$entry['cached_at'] > WPO_PSI_CACHE_TTL) {
        @unlink($path);
        return null;
    }
    return [
        'score'   => (int)$entry['score'],
        'tier'    => (string)$entry['tier'],
        'metrics' => $entry['metrics'],
        'cached_at' => (int)$entry['cached_at'],
    ];
}

function wpo_psi_cache_set(string $url, array $payload): void {
    wpo_psi_ensure_dir(WPO_PSI_CACHE_DIR);
    $entry = [
        'url'       => wpo_psi_normalize_url($url),
        'score'     => $payload['score'],
        'tier'      => $payload['tier'],
        'metrics'   => $payload['metrics'],
        'cached_at' => time(),
    ];
    file_put_contents(wpo_psi_cache_path($url), json_encode($entry), LOCK_EX);
}

function wpo_psi_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', (string)$_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/** Segundos restantes antes de poder llamar a Google de nuevo, o 0 si puede. */
function wpo_psi_rate_limit_remaining(string $ip): int {
    wpo_psi_ensure_dir(WPO_PSI_RATE_DIR);
    $path = WPO_PSI_RATE_DIR . '/' . hash('sha256', $ip) . '.txt';
    if (!is_file($path)) {
        return 0;
    }
    $last = (int)@file_get_contents($path);
    $elapsed = time() - $last;
    if ($elapsed >= WPO_PSI_RATE_SECONDS) {
        return 0;
    }
    return WPO_PSI_RATE_SECONDS - $elapsed;
}

function wpo_psi_rate_limit_touch(string $ip): void {
    wpo_psi_ensure_dir(WPO_PSI_RATE_DIR);
    $path = WPO_PSI_RATE_DIR . '/' . hash('sha256', $ip) . '.txt';
    file_put_contents($path, (string)time(), LOCK_EX);
}

function wpo_psi_extract_metrics(array $data): ?array {
    if (!isset($data['lighthouseResult'])) {
        return null;
    }
    $lh = $data['lighthouseResult'];
    $score = isset($lh['categories']['performance']['score'])
        ? (int)round($lh['categories']['performance']['score'] * 100)
        : 0;

    $audits = $lh['audits'] ?? [];
    $lcp_ms = $audits['largest-contentful-paint']['numericValue'] ?? 0;
    $lcp_s = round($lcp_ms / 1000, 2);

    $cls = $audits['cumulative-layout-shift']['displayValue'] ?? 'N/A';
    if ($cls === 'N/A' && isset($audits['cumulative-layout-shift']['numericValue'])) {
        $cls = round($audits['cumulative-layout-shift']['numericValue'], 3);
    }

    $tbt_ms = $audits['total-blocking-time']['numericValue'] ?? 0;

    $tier = 'red';
    if ($score >= 90) {
        $tier = 'green';
    } elseif ($score >= 50) {
        $tier = 'orange';
    }

    return [
        'score'   => $score,
        'tier'    => $tier,
        'metrics' => [
            'lcp' => $lcp_s . ' s',
            'cls' => $cls,
            'tbt' => round($tbt_ms) . ' ms',
        ],
    ];
}

function wpo_psi_compute_financials(int $visits, float $ticket, float $conversion, float $lcp_s): array {
    $projected_revenue = $visits * ($conversion / 100) * $ticket;
    $delay = max(0.0, $lcp_s - 2.5);
    $loss_percentage = min(0.90, $delay * 0.07);
    $revenue_lost = $projected_revenue * $loss_percentage;

    return [
        'projected_revenue' => round($projected_revenue, 2),
        'revenue_lost'      => round($revenue_lost, 2),
        'loss_percentage'   => round($loss_percentage * 100, 1),
    ];
}

function wpo_psi_lcp_seconds_from_metrics(array $metrics): float {
    $lcp = $metrics['lcp'] ?? '0 s';
    return (float)preg_replace('/[^0-9.]/', '', (string)$lcp);
}

function wpo_psi_fetch(string $url): array {
    $api_base = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    $params = [
        'url'      => $url,
        'strategy' => 'mobile',
        'category' => 'performance',
    ];
    if (defined('GOOGLE_PSI_API_KEY') && GOOGLE_PSI_API_KEY !== '') {
        $params['key'] = GOOGLE_PSI_API_KEY;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $api_base . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 55,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'VictorAlonsoSEOBot/1.0',
        CURLOPT_HEADER         => true,
    ]);

    $raw = curl_exec($ch);
    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'message' => 'Error de conexión con Google: ' . $err];
    }

    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers_raw = substr((string)$raw, 0, $header_size);
    $body = substr((string)$raw, $header_size);
    $data = json_decode($body, true);

    if ($http_code === 200 && !isset($data['error'])) {
        $metrics = wpo_psi_extract_metrics($data);
        if ($metrics === null) {
            return ['ok' => false, 'message' => 'No se han podido extraer datos de rendimiento para esta URL.'];
        }
        return ['ok' => true, 'payload' => $metrics];
    }

    $error_msg = $data['error']['message'] ?? 'Error desconocido al invocar la API de PageSpeed.';
    if ($http_code === 429) {
        $retry = 60;
        if (preg_match('/retry-after:\s*(\d+)/i', $headers_raw, $m)) {
            $retry = max(30, (int)$m[1]);
        }
        $error_msg = "Cuota de Google PageSpeed agotada (límite ~60 análisis cada 100 s por clave). Espera {$retry} s y vuelve a intentarlo, o prueba con la misma URL más tarde (usamos caché 12 h).";
    } elseif ($http_code === 403 && stripos($error_msg, 'IP address restriction') !== false) {
        $error_msg = 'La clave de PageSpeed tiene restricción de IP y el servidor no está autorizado. Añade la IP del servidor en Google Cloud Console → Credentials.';
    }

    return ['ok' => false, 'message' => 'Google PSI devolvió un error: ' . $error_msg, 'http_code' => $http_code];
}

function wpo_psi_build_response(array $psi, int $visits, float $ticket, float $conversion, bool $from_cache = false): array {
    $lcp_s = wpo_psi_lcp_seconds_from_metrics($psi['metrics']);
    $financials = wpo_psi_compute_financials($visits, $ticket, $conversion, $lcp_s);

    $out = [
        'success' => true,
        'data'    => [
            'score'      => $psi['score'],
            'tier'       => $psi['tier'],
            'metrics'    => $psi['metrics'],
            'financials' => $financials,
        ],
    ];
    if ($from_cache && !empty($psi['cached_at'])) {
        $hours = max(1, (int)round((time() - $psi['cached_at']) / 3600));
        $out['data']['cache_note'] = "Métricas de rendimiento en caché (hace ~{$hours} h). Los importes se recalculan con tus datos.";
    }
    return $out;
}
