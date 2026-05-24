<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';

// Interceptar acción AJAX de votación
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

$error = null;
$warning = null;
$result = null;

// Límites de seguridad
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('MAX_LINES', 15000); // 15.000 líneas

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stream = null;
    $source_name = '';
    
    // 1. Determinar origen de datos
    if (isset($_FILES['log_file']) && $_FILES['log_file']['error'] === UPLOAD_ERR_OK) {
        $file_size = $_FILES['log_file']['size'];
        if ($file_size > MAX_FILE_SIZE) {
            $error = 'El archivo subido supera el límite de seguridad de 5 MB.';
        } else {
            $stream = fopen($_FILES['log_file']['tmp_name'], 'r');
            $source_name = $_FILES['log_file']['name'];
        }
    } elseif (!empty($_POST['log_text'])) {
        $text_len = strlen($_POST['log_text']);
        if ($text_len > MAX_FILE_SIZE) {
            $error = 'El texto pegado supera el límite de seguridad de 5 MB.';
        } else {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $_POST['log_text']);
            rewind($stream);
            $source_name = 'Texto pegado';
        }
    } else {
        $error = 'Por favor, sube un archivo .log/.txt o pega tus líneas de log.';
    }

    if ($stream && !$error) {
        // Expresión regular optimizada para Common y Combined Log Formats
        // 1: IP, 2: Timestamp, 3: Método, 4: Path, 5: Status, 6: Bytes, 7: Referer (opcional), 8: User-Agent (opcional)
        $pattern = '/^(\S+) \S+ \S+ \[([^\]]+)\] "([A-Z]+) ([^" ]*) ?[^" ]*" (\d{3}) (\d+|-)(?: "([^"]*)" "([^"]*)")?$/';
        
        $total_lines_checked = 0;
        $parsed_lines = 0;
        $total_bytes = 0;
        
        $ips = [];
        $status_codes = [];
        $urls = [];
        $urls_no_static = [];
        $bots = [];
        $errors_404 = [];
        $hourly_distribution = array_fill(0, 24, 0);
        
        $first_date = null;
        $last_date = null;
        
        $lines_processed = 0;
        $has_valid_format = false;
        
        while (($line = fgets($stream)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $total_lines_checked++;
            if ($total_lines_checked > MAX_LINES) {
                $warning = 'El archivo es muy extenso. Por motivos de rendimiento del servidor, solo se han analizado las primeras ' . number_format(MAX_LINES, 0, ',', '.') . ' líneas.';
                break;
            }
            
            if (preg_match($pattern, $line, $matches)) {
                $has_valid_format = true;
                $parsed_lines++;
                
                $ip = $matches[1];
                $date_raw = $matches[2];
                $method = $matches[3];
                $path = $matches[4];
                $status = (int)$matches[5];
                $bytes = ($matches[6] === '-') ? 0 : (int)$matches[6];
                $referer = $matches[7] ?? '';
                $user_agent = $matches[8] ?? '';
                
                $total_bytes += $bytes;
                
                // Rango de fechas
                if ($first_date === null) {
                    $first_date = $date_raw;
                }
                $last_date = $date_raw; // El último será el final
                
                // Distribución por hora
                // Formato de fecha típico: 10/Oct/2000:13:55:36 -0700
                $colon_pos = strpos($date_raw, ':');
                if ($colon_pos !== false && strlen($date_raw) >= $colon_pos + 3) {
                    $hour = (int)substr($date_raw, $colon_pos + 1, 2);
                    if ($hour >= 0 && $hour <= 23) {
                        $hourly_distribution[$hour]++;
                    }
                }
                
                // IPs únicas
                $ips[$ip] = ($ips[$ip] ?? 0) + 1;
                
                // Códigos de estado
                $status_codes[$status] = ($status_codes[$status] ?? 0) + 1;
                
                // URLs totales
                $urls[$path] = ($urls[$path] ?? 0) + 1;
                
                // Filtrar recursos estáticos para SEO
                $is_static = preg_match('/\.(css|js|png|jpe?g|webp|svg|gif|ico|woff2?|ttf|eot|mp4|webm|pdf)(\?.*)?$/i', $path);
                if (!$is_static) {
                    $urls_no_static[$path] = ($urls_no_static[$path] ?? 0) + 1;
                }
                
                // Errores 404
                if ($status === 404) {
                    $errors_404[$path] = ($errors_404[$path] ?? 0) + 1;
                }
                
                // Identificación de Bots de Rastreo y Crawlers
                $ua_lower = strtolower($user_agent);
                $detected_bot = null;
                
                if (strpos($ua_lower, 'googlebot') !== false) {
                    $detected_bot = 'Googlebot';
                } elseif (strpos($ua_lower, 'bingbot') !== false || strpos($ua_lower, 'bingpreview') !== false) {
                    $detected_bot = 'Bingbot';
                } elseif (strpos($ua_lower, 'yandexbot') !== false || strpos($ua_lower, 'yandex') !== false) {
                    $detected_bot = 'YandexBot';
                } elseif (strpos($ua_lower, 'applebot') !== false) {
                    $detected_bot = 'Applebot';
                } elseif (strpos($ua_lower, 'duckduckgo') !== false || strpos($ua_lower, 'duckduckbot') !== false) {
                    $detected_bot = 'DuckDuckBot';
                } elseif (strpos($ua_lower, 'screaming frog') !== false) {
                    $detected_bot = 'Screaming Frog (Auditoría)';
                } elseif (strpos($ua_lower, 'semrushbot') !== false) {
                    $detected_bot = 'SemrushBot';
                } elseif (strpos($ua_lower, 'ahrefsbot') !== false) {
                    $detected_bot = 'AhrefsBot';
                } elseif (strpos($ua_lower, 'mj12bot') !== false) {
                    $detected_bot = 'MJ12Bot (Majestic)';
                } elseif (strpos($ua_lower, 'baiduspider') !== false) {
                    $detected_bot = 'BaiduSpider';
                } elseif (strpos($ua_lower, 'pinterest') !== false) {
                    $detected_bot = 'PinterestBot';
                } elseif (strpos($ua_lower, 'facebookexternalhit') !== false) {
                    $detected_bot = 'Facebook Crawler';
                } elseif (strpos($ua_lower, 'twitterbot') !== false) {
                    $detected_bot = 'TwitterBot';
                } elseif (strpos($ua_lower, 'adsbot-google') !== false || strpos($ua_lower, 'mediapartners-google') !== false) {
                    $detected_bot = 'Google Ads Bot';
                } elseif (preg_match('/(curl|wget|python|libwww-perl|go-http-client|httpclient|java)/i', $ua_lower)) {
                    $detected_bot = 'Scripts de Desarrollador (curl/python...)';
                }
                
                if ($detected_bot) {
                    $bots[$detected_bot] = ($bots[$detected_bot] ?? 0) + 1;
                }
            } else {
                // Si leemos 15 líneas y ninguna coincide, rechazamos por formato
                if ($total_lines_checked >= 15 && !$has_valid_format) {
                    $error = 'El formato del registro no parece ser un log de accesos válido de Apache o Nginx (formatos Common o Combined).';
                    break;
                }
            }
        }
        
        fclose($stream);
        
        if (!$error && $parsed_lines === 0) {
            $error = 'No se ha podido extraer ninguna línea con el formato estándar Common o Combined.';
        }
        
        if (!$error) {
            // Ordenar arrays por frecuencia
            arsort($ips);
            arsort($urls);
            arsort($urls_no_static);
            arsort($bots);
            arsort($errors_404);
            arsort($status_codes);
            
            // Limitar resultados
            $top_ips = array_slice($ips, 0, 10, true);
            $top_urls = array_slice($urls, 0, 10, true);
            $top_urls_no_static = array_slice($urls_no_static, 0, 10, true);
            $top_bots = array_slice($bots, 0, 10, true);
            $top_404s = array_slice($errors_404, 0, 10, true);
            
            $result = [
                'source_name' => $source_name,
                'total_checked' => $total_lines_checked,
                'parsed_lines' => $parsed_lines,
                'bandwidth_mb' => round($total_bytes / (1024 * 1024), 2),
                'unique_ips_count' => count($ips),
                'date_start' => $first_date,
                'date_end' => $last_date,
                'status_codes' => $status_codes,
                'top_ips' => $top_ips,
                'top_urls' => $top_urls,
                'top_urls_no_static' => $top_urls_no_static,
                'top_bots' => $top_bots,
                'top_404s' => $top_404s,
                'hourly_distribution' => $hourly_distribution
            ];
        }
    }
}

$page = page_config([
    'title'        => 'Analizador de Logs Apache y Nginx Online',
    'description'  => 'Sube o pega tu log de accesos (Apache/Nginx) y audita de forma instantánea el rastreo de bots de búsqueda, errores 404, IPs activas y consumo de crawl budget.',
    'canonical'    => '/herramientas/analizador-logs/',
    'body_class'   => 'page-analizador-logs',
    'schema_types' => ['WebApplication'],
    'rating_id'    => 'analizador-logs',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Analizador de Logs', 'url' => ''],
    ],
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<style>
/* Estilos premium para el dashboard */
.tab-container {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    padding-bottom: 0.75rem;
}
.tab-button {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    color: var(--muted);
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 600;
    transition: all 0.3s;
}
.tab-button:hover {
    color: #fff;
    background: rgba(255,255,255,0.08);
}
.tab-button.active {
    background: var(--acento);
    color: #fff;
    border-color: var(--acento);
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}

.drag-area {
    border: 2px dashed rgba(232, 104, 26, 0.3);
    background: rgba(232, 104, 26, 0.02);
    border-radius: 8px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    margin-bottom: 1.5rem;
}
.drag-area:hover, .drag-area.dragover {
    border-color: var(--acento);
    background: rgba(232, 104, 26, 0.06);
}
.drag-icon {
    font-size: 2.5rem;
    color: var(--acento);
    margin-bottom: 0.75rem;
}
.drag-text {
    font-size: 1rem;
    color: #fff;
    margin-bottom: 0.5rem;
}
.drag-subtext {
    font-size: 0.85rem;
    color: var(--muted);
}

.metric-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}
.metric-box {
    background: #0b101c;
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.metric-box-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--muted);
    margin-bottom: 0.5rem;
}
.metric-box-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #fff;
}

.chart-bar-container {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 1rem;
}
.chart-bar-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.9rem;
}
.chart-bar-label {
    width: 90px;
    color: #cbd5e1;
    font-weight: 600;
    flex-shrink: 0;
}
.chart-bar-track {
    background: rgba(255,255,255,0.04);
    height: 14px;
    border-radius: 7px;
    flex-grow: 1;
    position: relative;
    overflow: hidden;
}
.chart-bar-fill {
    height: 100%;
    border-radius: 7px;
    transition: width 1s ease-out;
}
.chart-bar-value {
    width: 60px;
    text-align: right;
    color: #fff;
    font-weight: 700;
    flex-shrink: 0;
}

.bar-color-2xx { background: #2ecc71; }
.bar-color-3xx { background: #f1c40f; }
.bar-color-4xx { background: #e67e22; }
.bar-color-5xx { background: #e74c3c; }
.bar-color-bot { background: #3498db; }

.timeline-graph {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    height: 150px;
    padding-top: 1rem;
    border-bottom: 2px solid rgba(255,255,255,0.08);
    margin-bottom: 0.5rem;
    gap: 4px;
}
.timeline-col {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
    justify-content: flex-end;
}
.timeline-bar {
    background: linear-gradient(180deg, var(--acento) 0%, rgba(232, 104, 26, 0.2) 100%);
    width: 100%;
    min-height: 2px;
    border-radius: 3px 3px 0 0;
    position: relative;
    cursor: pointer;
    transition: all 0.3s;
}
.timeline-bar:hover {
    background: #fff;
}
.timeline-bar:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #000;
    color: #fff;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    white-space: nowrap;
    z-index: 10;
    border: 1px solid rgba(255,255,255,0.1);
}
.timeline-label {
    font-size: 0.75rem;
    color: var(--muted);
    margin-top: 0.5rem;
}

.table-filter-input {
    width: 100%;
    max-width: 320px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    color: #fff;
    font-size: 0.85rem;
    margin-bottom: 1rem;
    transition: all 0.3s;
}
.table-filter-input:focus {
    border-color: var(--acento);
    background: rgba(255,255,255,0.06);
    outline: none;
}

.card-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .chart-bar-item {
        font-size: 0.8rem;
    }
    .chart-bar-label {
        width: 70px;
    }
    .timeline-graph {
        height: 100px;
    }
    .timeline-label {
        font-size: 0.6rem;
    }
}
</style>

<main id="main">

  <section class="page-hero" aria-labelledby="logs-h1">
    <div class="container">
      <span class="hero-eyebrow">Auditoría de Tráfico y Crawling</span>
      <h1 id="logs-h1">Analizador de <span>Logs Apache y Nginx</span></h1>
      <p class="page-hero-desc">Audita la salud técnica de tu web de manera 100% local y segura. Sube tu archivo log y visualiza el comportamiento de los motores de búsqueda, errores 404 y actividad de IPs.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      
      <div class="tool-intro" style="margin-bottom: 2rem;">
        <h2>Audita tu presupuesto de rastreo y errores técnicos</h2>
        <p>Los archivos de logs de tu servidor contienen la verdad absoluta de lo que ocurre en tu web. Esta herramienta procesa tu log Common o Combined directamente en la sesión de PHP (sin guardar registros en disco) permitiéndote optimizar tu SEO técnico al instante.</p>
      </div>

      <!-- Formulario de entrada -->
      <div class="card card--dark" style="margin-bottom: 3rem; border-color: rgba(255,255,255,0.06);">
        
        <div class="tab-container">
          <button type="button" class="tab-button active" onclick="switchTab('subir-archivo')">Subir Archivo (.log, .txt)</button>
          <button type="button" class="tab-button" onclick="switchTab('pegar-texto')">Pegar Líneas de Log</button>
        </div>

        <form action="/herramientas/analizador-logs/" method="POST" enctype="multipart/form-data" id="logsForm">
          
          <!-- Pestaña 1: Subir Archivo -->
          <div id="subir-archivo" class="tab-content active">
            <div class="drag-area" id="dragArea" onclick="document.getElementById('fileInput').click()">
              <div class="drag-icon">📁</div>
              <div class="drag-text">Arrastra tu archivo de log aquí o haz clic para buscarlo</div>
              <div class="drag-subtext">Formatos aceptados: .log, .txt (Máximo 5 MB)</div>
              <input type="file" name="log_file" id="fileInput" style="display: none;" onchange="handleFileSelect(this)">
            </div>
            <div id="fileName" style="text-align: center; color: #2ecc71; font-weight: 600; margin-bottom: 1rem; display: none;"></div>
          </div>

          <!-- Pestaña 2: Pegar Texto -->
          <div id="pegar-texto" class="tab-content">
            <div style="margin-bottom: 1.5rem;">
              <textarea 
                name="log_text" 
                rows="8" 
                class="form-input" 
                placeholder="127.0.0.1 - - [24/May/2026:11:45:22 +0200] &quot;GET /index.php HTTP/1.1&quot; 200 4502 &quot;-&quot; &quot;Googlebot/2.1&quot;..."
                style="font-family: monospace; font-size: 0.85rem; width: 100%; border-radius: 6px;"
              ></textarea>
            </div>
          </div>

          <div style="text-align: right;">
            <button type="submit" class="btn btn--primary" style="margin-top: 0;">Procesar y Analizar Logs</button>
          </div>
        </form>

        <?php if ($error): ?>
          <div class="alert alert--danger" style="margin-top: 1.5rem;"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($warning): ?>
          <div class="alert alert--warning" style="margin-top: 1.5rem;"><?= h($warning) ?></div>
        <?php endif; ?>

      </div>

      <!-- Resultados / Dashboard -->
      <?php if ($result): ?>
        <div class="audit-results" id="dashboard" style="margin-top: 1rem;">
          
          <h3 style="margin-bottom: 2rem; color: var(--orange); display: flex; align-items: center; gap: 0.5rem;">
            <span>📊</span> Dashboard de Análisis: <span style="color: #fff; font-weight: 400;"><?= h($result['source_name']) ?></span>
          </h3>

          <!-- Bloque de Métricas Principales -->
          <div class="metric-card-grid">
            <div class="metric-box">
              <span class="metric-box-label">Peticiones Procesadas</span>
              <span class="metric-box-value"><?= number_format($result['parsed_lines'], 0, ',', '.') ?></span>
            </div>
            <div class="metric-box">
              <span class="metric-box-label">IPs Únicas</span>
              <span class="metric-box-value"><?= number_format($result['unique_ips_count'], 0, ',', '.') ?></span>
            </div>
            <div class="metric-box">
              <span class="metric-box-label">Ancho de Banda</span>
              <span class="metric-box-value"><?= $result['bandwidth_mb'] ?> MB</span>
            </div>
            <div class="metric-box">
              <span class="metric-box-label">Fechas del Log</span>
              <span class="metric-box-value" style="font-size: 0.85rem; font-weight: 600; line-height: 1.4; color: #cbd5e1; margin-top: 0.25rem;">
                Inicio: <?= h($result['date_start']) ?><br>
                Fin: <?= h($result['date_end']) ?>
              </span>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 2rem; margin-bottom: 2.5rem;">
            
            <!-- Códigos de Estado HTTP -->
            <div class="card card--dark" style="margin: 0; background: #0b101c; border-color: rgba(255,255,255,0.05);">
              <h4 style="color: #fff; font-size: 1.15rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🔌</span> Códigos de Respuesta HTTP
              </h4>
              <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1rem;">Distribución de estados devueltos por el servidor.</p>
              
              <div class="chart-bar-container">
                <?php
                $status_groups = [
                    '2xx (Éxito)' => ['codes' => [200, 201, 204, 206], 'color' => 'bar-color-2xx', 'count' => 0],
                    '3xx (Redirección)' => ['codes' => [301, 302, 304, 307, 308], 'color' => 'bar-color-3xx', 'count' => 0],
                    '4xx (Error Cliente)' => ['codes' => [400, 401, 403, 404, 410], 'color' => 'bar-color-4xx', 'count' => 0],
                    '5xx (Error Servidor)' => ['codes' => [500, 502, 503, 504], 'color' => 'bar-color-5xx', 'count' => 0]
                ];
                
                foreach ($result['status_codes'] as $code => $count) {
                    $grouped = false;
                    foreach ($status_groups as $group_label => &$group) {
                        if (in_array((int)$code, $group['codes'])) {
                            $group['count'] += $count;
                            $grouped = true;
                            break;
                        }
                    }
                    if (!$grouped) {
                        // Si es un código raro, agrupar por el primer número
                        $first_num = substr($code, 0, 1);
                        if ($first_num == '2') $status_groups['2xx (Éxito)']['count'] += $count;
                        elseif ($first_num == '3') $status_groups['3xx (Redirección)']['count'] += $count;
                        elseif ($first_num == '4') $status_groups['4xx (Error Cliente)']['count'] += $count;
                        elseif ($first_num == '5') $status_groups['5xx (Error Servidor)']['count'] += $count;
                    }
                }
                
                $max_group_count = 0;
                foreach ($status_groups as $g) {
                    if ($g['count'] > $max_group_count) $max_group_count = $g['count'];
                }
                
                foreach ($status_groups as $label => $g):
                    $pct = $result['parsed_lines'] > 0 ? round(($g['count'] / $result['parsed_lines']) * 100, 1) : 0;
                    $bar_pct = $max_group_count > 0 ? round(($g['count'] / $max_group_count) * 100, 1) : 0;
                ?>
                  <div class="chart-bar-item">
                    <span class="chart-bar-label"><?= $label ?></span>
                    <div class="chart-bar-track">
                      <div class="chart-bar-fill <?= $g['color'] ?>" style="width: <?= $bar_pct ?>%;"></div>
                    </div>
                    <span class="chart-bar-value"><?= number_format($g['count'], 0, ',', '.') ?> <span style="font-size:0.75rem; color:var(--muted); font-weight:400;">(<?= $pct ?>%)</span></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Actividad de Bots de Búsqueda y Crawlers -->
            <div class="card card--dark" style="margin: 0; background: #0b101c; border-color: rgba(255,255,255,0.05);">
              <h4 style="color: #fff; font-size: 1.15rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🤖</span> Top Crawlers y Motores de Búsqueda
              </h4>
              <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1rem;">Visitas detectadas según firmas en el User-Agent.</p>
              
              <div class="chart-bar-container">
                <?php if (empty($result['top_bots'])): ?>
                  <p style="font-size: 0.9rem; color: var(--muted); text-align: center; margin-top: 1.5rem;">No se han detectado firmas conocidas de bots en este registro.</p>
                <?php else: 
                  $max_bot_hits = reset($result['top_bots']);
                  foreach ($result['top_bots'] as $bot_name => $bot_hits):
                      $bot_pct = $max_bot_hits > 0 ? round(($bot_hits / $max_bot_hits) * 100, 1) : 0;
                      $pct_total = $result['parsed_lines'] > 0 ? round(($bot_hits / $result['parsed_lines']) * 100, 1) : 0;
                ?>
                  <div class="chart-bar-item">
                    <span class="chart-bar-label" style="width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= h($bot_name) ?>"><?= h($bot_name) ?></span>
                    <div class="chart-bar-track">
                      <div class="chart-bar-fill bar-color-bot" style="width: <?= $bot_pct ?>%;"></div>
                    </div>
                    <span class="chart-bar-value"><?= number_format($bot_hits, 0, ',', '.') ?> <span style="font-size:0.75rem; color:var(--muted); font-weight:400;">(<?= $pct_total ?>%)</span></span>
                  </div>
                <?php endforeach; endif; ?>
              </div>
            </div>

          </div>

          <!-- Timeline por horas -->
          <div class="card card--dark" style="margin-bottom: 2.5rem; background: #0b101c; border-color: rgba(255,255,255,0.05);">
            <h4 style="color: #fff; font-size: 1.15rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
              <span>⏰</span> Actividad Temporal (Timeline por Horas)
            </h4>
            <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Frecuencia horaria para localizar picos de tráfico en el servidor.</p>
            
            <?php
            $max_hour_hits = max($result['hourly_distribution']);
            ?>
            <div class="timeline-graph">
              <?php foreach ($result['hourly_distribution'] as $hour => $hits): 
                  $bar_h = $max_hour_hits > 0 ? round(($hits / $max_hour_hits) * 100, 1) : 0;
                  $formatted_hour = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
              ?>
                <div class="timeline-col">
                  <div 
                    class="timeline-bar" 
                    style="height: calc(<?= $bar_h ?>% - 4px);"
                    data-tooltip="<?= $formatted_hour ?> — <?= number_format($hits, 0, ',', '.') ?> hits"
                  ></div>
                  <span class="timeline-label"><?= str_pad($hour, 2, '0', STR_PAD_LEFT) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Pestañas de Tablas Detalladas -->
          <div class="card card--dark" style="background: #0b101c; border-color: rgba(255,255,255,0.05);">
            <div class="tab-container" style="border-bottom-color: rgba(255,255,255,0.05);">
              <button type="button" class="tab-button active" onclick="switchTableTab('table-urls-no-static')">URLs Indexables (HTML)</button>
              <button type="button" class="tab-button" onclick="switchTableTab('table-urls')">Todas las URLs</button>
              <button type="button" class="tab-button" onclick="switchTableTab('table-404s')" style="color: #ff6b6b;">Errores 404 (SEO Redir)</button>
              <button type="button" class="tab-button" onclick="switchTableTab('table-ips')">IPs más Activas</button>
            </div>

            <!-- Tabla 1: URLs Indexables / No Estáticas -->
            <div id="table-urls-no-static" class="table-tab-content" style="display: block;">
              <div class="card-header-flex">
                <div>
                  <h4 style="margin: 0; color: #fff;">Top URLs Indexables (Páginas HTML)</h4>
                  <p style="font-size: 0.85rem; color: var(--muted); margin: 0.25rem 0 0 0;">Visualiza únicamente las rutas sin recursos estáticos (CSS, JS, imágenes...).</p>
                </div>
                <input type="text" class="table-filter-input" placeholder="Filtrar por URL..." onkeyup="filterTable(this, 'url-no-static-tbody')">
              </div>
              <table class="tech-table">
                <thead>
                  <tr>
                    <th>URL / Ruta</th>
                    <th style="text-align: right; width: 140px;">Peticiones</th>
                    <th style="text-align: right; width: 100px;">Porcentaje</th>
                  </tr>
                </thead>
                <tbody id="url-no-static-tbody">
                  <?php if (empty($result['top_urls_no_static'])): ?>
                    <tr><td colspan="3" style="text-align:center; color: var(--muted);">No se encontraron URLs de páginas en este registro.</td></tr>
                  <?php else: foreach ($result['top_urls_no_static'] as $url => $hits): 
                      $pct = round(($hits / $result['parsed_lines']) * 100, 1);
                  ?>
                    <tr>
                      <td style="word-break: break-all; font-family: monospace; font-size: 0.85rem; color: var(--acento);"><?= h($url) ?></td>
                      <td style="text-align: right; font-weight: 700; color: #fff;"><?= number_format($hits, 0, ',', '.') ?></td>
                      <td style="text-align: right; color: var(--muted); font-size: 0.85rem;"><?= $pct ?>%</td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Tabla 2: Todas las URLs -->
            <div id="table-urls" class="table-tab-content" style="display: none;">
              <div class="card-header-flex">
                <div>
                  <h4 style="margin: 0; color: #fff;">Top 10 URLs Solicitadas (Total)</h4>
                  <p style="font-size: 0.85rem; color: var(--muted); margin: 0.25rem 0 0 0;">Incluye absolutamente todas las peticiones (páginas, imágenes, fuentes, etc).</p>
                </div>
                <input type="text" class="table-filter-input" placeholder="Filtrar por URL..." onkeyup="filterTable(this, 'url-tbody')">
              </div>
              <table class="tech-table">
                <thead>
                  <tr>
                    <th>URL / Ruta</th>
                    <th style="text-align: right; width: 140px;">Peticiones</th>
                    <th style="text-align: right; width: 100px;">Porcentaje</th>
                  </tr>
                </thead>
                <tbody id="url-tbody">
                  <?php if (empty($result['top_urls'])): ?>
                    <tr><td colspan="3" style="text-align:center; color: var(--muted);">Sin peticiones registradas.</td></tr>
                  <?php else: foreach ($result['top_urls'] as $url => $hits): 
                      $pct = round(($hits / $result['parsed_lines']) * 100, 1);
                  ?>
                    <tr>
                      <td style="word-break: break-all; font-family: monospace; font-size: 0.85rem;"><?= h($url) ?></td>
                      <td style="text-align: right; font-weight: 700; color: #fff;"><?= number_format($hits, 0, ',', '.') ?></td>
                      <td style="text-align: right; color: var(--muted); font-size: 0.85rem;"><?= $pct ?>%</td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Tabla 3: Errores 404 -->
            <div id="table-404s" class="table-tab-content" style="display: none;">
              <div class="card-header-flex">
                <div>
                  <h4 style="margin: 0; color: #ff6b6b;">Top 10 Errores 404 Detectados</h4>
                  <p style="font-size: 0.85rem; color: var(--muted); margin: 0.25rem 0 0 0;">Rutas que devuelven un 404 No Encontrado. Planifica redirecciones 301 para salvar enlaces.</p>
                </div>
                <input type="text" class="table-filter-input" placeholder="Filtrar por URL..." onkeyup="filterTable(this, '404-tbody')">
              </div>
              <table class="tech-table">
                <thead>
                  <tr>
                    <th>Ruta Errónea (404)</th>
                    <th style="text-align: right; width: 140px;">Ocurrencias</th>
                    <th style="text-align: right; width: 100px;">Impacto</th>
                  </tr>
                </thead>
                <tbody id="404-tbody">
                  <?php if (empty($result['top_404s'])): ?>
                    <tr><td colspan="3" style="text-align:center; color: #2ecc71; font-weight: 600; padding: 1.5rem;">✓ ¡Enhorabuena! No se han registrado errores 404 en este fragmento.</td></tr>
                  <?php else: foreach ($result['top_404s'] as $url => $hits): 
                      $pct = round(($hits / $result['parsed_lines']) * 100, 1);
                  ?>
                    <tr>
                      <td style="word-break: break-all; font-family: monospace; font-size: 0.85rem; color: #ff6b6b;"><?= h($url) ?></td>
                      <td style="text-align: right; font-weight: 700; color: #fff;"><?= number_format($hits, 0, ',', '.') ?></td>
                      <td style="text-align: right; color: var(--muted); font-size: 0.85rem;"><?= $pct ?>%</td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Tabla 4: IPs más Activas -->
            <div id="table-ips" class="table-tab-content" style="display: none;">
              <div class="card-header-flex">
                <div>
                  <h4 style="margin: 0; color: #fff;">Top 10 Direcciones IP Activas</h4>
                  <p style="font-size: 0.85rem; color: var(--muted); margin: 0.25rem 0 0 0;">Identifica qué clientes consumen la mayor cantidad de recursos de tu web.</p>
                </div>
                <input type="text" class="table-filter-input" placeholder="Filtrar por IP..." onkeyup="filterTable(this, 'ip-tbody')">
              </div>
              <table class="tech-table">
                <thead>
                  <tr>
                    <th>Dirección IP</th>
                    <th style="text-align: right; width: 140px;">Hits</th>
                    <th style="text-align: right; width: 100px;">Porcentaje</th>
                  </tr>
                </thead>
                <tbody id="ip-tbody">
                  <?php if (empty($result['top_ips'])): ?>
                    <tr><td colspan="3" style="text-align:center; color: var(--muted);">Sin IPs registradas.</td></tr>
                  <?php else: foreach ($result['top_ips'] as $ip => $hits): 
                      $pct = round(($hits / $result['parsed_lines']) * 100, 1);
                  ?>
                    <tr>
                      <td style="font-family: monospace; font-size: 0.85rem; color: #fff;"><?= h($ip) ?></td>
                      <td style="text-align: right; font-weight: 700; color: #fff;"><?= number_format($hits, 0, ',', '.') ?></td>
                      <td style="text-align: right; color: var(--muted); font-size: 0.85rem;"><?= $pct ?>%</td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Glosario Didáctico de Análisis de Logs y SEO -->
          <div class="criterio-section" style="margin-top:4rem; border-top: 1px solid var(--border); padding-top:3rem;">
            <span class="section-label">Glosario de trinchera</span>
            <h2 style="margin-bottom:1.5rem">¿Cómo interpretar estos datos para tu SEO técnico?</h2>
            
            <div class="criterio-grid" style="grid-template-columns: 1fr 1fr; gap:2.5rem;">
              <div>
                <h3 style="color:#fff; font-size:1.15rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">El Presupuesto de Rastreo (Crawl Budget)</h3>
                <ul style="list-style:none; padding:0; display:grid; gap:1.25rem;">
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">¿Qué es el Crawl Budget?</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Es el tiempo y la cantidad de páginas que Googlebot y otros buscadores deciden rastrear en tu sitio web durante un tiempo determinado. Si tu web tiene miles de páginas sin optimizar, Google puede consumir tu presupuesto de rastreo antes de llegar a tus contenidos clave.</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">¿Por qué los recursos estáticos estorban en los logs?</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Normalmente, el 80% de un archivo log corresponde a peticiones de archivos JS, CSS, tipografías e imágenes. Filtrar estos recursos te permite aislar las páginas reales (HTML indexables) para ver de forma transparente qué URLs están rastreando los bots y con qué frecuencia.</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Comportamiento de Googlebot</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Analizar las visitas de Googlebot te indica si el buscador ha descubierto tus últimas URLs publicadas o si está atascado en secciones sin valor debido a una arquitectura web ineficiente o bucles de filtrados de productos.</span>
                  </li>
                </ul>
              </div>
              
              <div>
                <h3 style="color:#fff; font-size:1.15rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Códigos de Respuesta y Errores</h3>
                <ul style="list-style:none; padding:0; display:grid; gap:1.25rem;">
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Errores 404 y su penalización técnica</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Cuando Googlebot rastrea una URL vieja y recibe un código 404, consume tiempo y presupuesto inútilmente. Además, si esa URL tiene enlaces entrantes, estás perdiendo autoridad. Mapear y redirigir con un código 301 (Redirección Permanente) tus principales 404s soluciona este problema de inmediato.</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Los Errores 5xx (Server Error)</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Un exceso de códigos 500, 502 o 504 indica caídas o colapsos de tu hosting. Si Googlebot detecta errores 5xx de forma continuada, reducirá la velocidad de rastreo e incluso podría llegar a desindexar temporalmente URLs importantes al interpretarlas como caídas definitivas del servicio.</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Scrapers, Spammers e IPs Sospechosas</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Al revisar el Top de IPs, si encuentras direcciones que realizan miles de peticiones en un corto periodo de tiempo y no corresponden a motores de búsqueda legítimos, probablemente sean bots de scraping robando tu contenido o atacando formularios. Bloquear estas IPs en tu firewall (.htaccess o Cloudflare) liberará recursos del servidor de inmediato.</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

        </div>
      <?php endif; ?>

      <!-- Secciones didácticas -->
      <div class="criterio-section" style="margin-top: 4rem;">
        <span class="section-label">Desde la trinchera técnica</span>
        <h2>Por qué analizar logs locales en lugar de usar herramientas externas</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>1. Privacidad y Seguridad absoluta (GDPR)</h3>
            <p>Tus logs contienen la dirección IP y el comportamiento exacto de todos tus usuarios. Subir estos logs a servidores de terceros desconocidos puede suponer un incumplimiento grave del RGPD. Nuestra herramienta procesa todo a través de PHP de forma efímera en la memoria RAM del servidor de victor-alonso.es, sin almacenar una sola línea en base de datos ni archivos locales. Tu privacidad es inquebrantable.</p>
          </div>
          
          <div class="criterio-card">
            <h3>2. La Verdad Oculta frente a Google Analytics</h3>
            <p>Los bloqueadores de publicidad, Brave y las extensiones de privacidad impiden que Google Analytics registre entre un 20% y un 40% del tráfico web. Los logs del servidor de Apache o Nginx son imposibles de bloquear. Todo usuario, bot de rastreo o script malicioso deja una huella física obligatoria en tu servidor, ofreciéndote métricas 100% reales.</p>
          </div>

          <div class="criterio-card">
            <h3>3. Optimización Quirúrgica del Rastreo SEO</h3>
            <p>Herramientas como Google Search Console te ofrecen una muestra pequeña de la actividad de Googlebot. Con un análisis de logs puedes conocer en tiempo real la hora exacta de rastreo, qué secciones del código se devuelven lentas o si estás sufriendo de canibalizaciones y bucles infinitos de redirección.</p>
          </div>
        </div>
      </div>

      <!-- Widget de Votación y Rich Snippet -->
      <?php render_rating_widget('analizador-logs'); ?>

    </div>
  </section>

  <!-- CTA final -->
  <?php
  $cta = [
      'title'     => '¿Has detectado problemas graves de rastreo o caídas 5xx?',
      'subtitle'  => 'Un log con exceso de errores técnicos es el síntoma de un servidor mal optimizado o una estructura de enlaces defectuosa.',
      'btn_label' => 'Auditar mi servidor de forma avanzada',
      'btn_href'  => '/contacto/',
      'whatsapp'  => true,
      'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<script>
function switchTab(tabId) {
    // Alternar pestañas activas de formulario
    document.querySelectorAll('#logsForm .tab-content').forEach(el => {
        el.classList.remove('active');
    });
    document.querySelectorAll('#logsForm .tab-button').forEach(el => {
        el.classList.remove('active');
    });
    
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}

function switchTableTab(tableId) {
    // Alternar pestañas del dashboard de tablas
    document.querySelectorAll('.table-tab-content').forEach(el => {
        el.style.display = 'none';
    });
    event.currentTarget.closest('.tab-container').querySelectorAll('.tab-button').forEach(el => {
        el.classList.remove('active');
    });
    
    document.getElementById(tableId).style.display = 'block';
    event.currentTarget.classList.add('active');
}

function handleFileSelect(input) {
    const fileNameDiv = document.getElementById('fileName');
    if (input.files && input.files.length > 0) {
        fileNameDiv.innerText = "✓ Archivo cargado: " + input.files[0].name;
        fileNameDiv.style.display = 'block';
    } else {
        fileNameDiv.style.display = 'none';
    }
}

// Configurar comportamiento drag and drop
const dragArea = document.getElementById('dragArea');
if (dragArea) {
    ['dragenter', 'dragover'].forEach(eventName => {
        dragArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            dragArea.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dragArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            dragArea.classList.remove('dragover');
        }, false);
    });

    dragArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        const fileInput = document.getElementById('fileInput');
        
        if (files && files.length > 0) {
            fileInput.files = files;
            handleFileSelect(fileInput);
        }
    }, false);
}

// Filtro instantáneo de tablas en tiempo real
function filterTable(input, tbodyId) {
    const filter = input.value.toLowerCase();
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    
    const rows = tbody.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const firstCol = rows[i].getElementsByTagName('td')[0];
        if (firstCol) {
            const textValue = firstCol.textContent || firstCol.innerText;
            if (textValue.toLowerCase().indexOf(filter) > -1) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}

// Desplazarse suavemente al dashboard si hay resultados
document.addEventListener('DOMContentLoaded', function() {
    const dashboard = document.getElementById('dashboard');
    if (dashboard) {
        dashboard.scrollIntoView({ behavior: 'smooth' });
    }
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
