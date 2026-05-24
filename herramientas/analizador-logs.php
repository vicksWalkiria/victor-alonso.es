<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/schema.php';
require_once dirname(__DIR__) . '/includes/ratings-helper.php';

// Interceptar acción AJAX de votación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
  header('Content-Type: application/json');
  $tool_id = trim($_POST['tool_id'] ?? '');
  $rating = (int) ($_POST['rating'] ?? 0);

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

/**
 * Formatea fechas de log apache de "24/May/2026:00:07:51 +0000" a "24 de Mayo, 2026 — 00:07:51"
 */
function format_log_date($date_str)
{
  if (empty($date_str))
    return '-';
  $parts = explode(' ', $date_str);
  $date_time_part = $parts[0];

  $dt = DateTime::createFromFormat('d/M/Y:H:i:s', $date_time_part);
  if ($dt) {
    $months = [
      'Jan' => 'Enero',
      'Feb' => 'Febrero',
      'Mar' => 'Marzo',
      'Apr' => 'Abril',
      'May' => 'Mayo',
      'Jun' => 'Junio',
      'Jul' => 'Julio',
      'Aug' => 'Agosto',
      'Sep' => 'Septiembre',
      'Oct' => 'Octubre',
      'Nov' => 'Noviembre',
      'Dec' => 'Diciembre'
    ];
    $day = $dt->format('d');
    $month_en = $dt->format('M');
    $month_es = $months[$month_en] ?? $month_en;
    $year = $dt->format('Y');
    $time = $dt->format('H:i:s');

    $tz_part = isset($parts[1]) ? ' (' . $parts[1] . ')' : '';
    return "$day de $month_es, $year — $time$tz_part";
  }
  return $date_str;
}

/**
 * Humaniza el intervalo de fechas del log con cálculo de duración preciso en español
 */
function humanize_log_dates($start_str, $end_str)
{
  if (empty($start_str) || empty($end_str))
    return '-';

  $parts_start = explode(' ', $start_str);
  $parts_end = explode(' ', $end_str);

  $dt_start = DateTime::createFromFormat('d/M/Y:H:i:s', $parts_start[0]);
  $dt_end = DateTime::createFromFormat('d/M/Y:H:i:s', $parts_end[0]);

  if ($dt_start && $dt_end) {
    $months = [
      'Jan' => 'Enero',
      'Feb' => 'Febrero',
      'Mar' => 'Marzo',
      'Apr' => 'Abril',
      'May' => 'Mayo',
      'Jun' => 'Junio',
      'Jul' => 'Julio',
      'Aug' => 'Agosto',
      'Sep' => 'Septiembre',
      'Oct' => 'Octubre',
      'Nov' => 'Noviembre',
      'Dec' => 'Diciembre'
    ];

    $day_s = $dt_start->format('d');
    $month_s = $months[$dt_start->format('M')] ?? $dt_start->format('M');
    $year_s = $dt_start->format('Y');
    $time_s = $dt_start->format('H:i');

    $day_e = $dt_end->format('d');
    $month_e = $months[$dt_end->format('M')] ?? $dt_end->format('M');
    $year_e = $dt_end->format('Y');
    $time_e = $dt_end->format('H:i');

    $diff = $dt_start->diff($dt_end);
    $duration_parts = [];
    if ($diff->d > 0)
      $duration_parts[] = $diff->d . ($diff->d == 1 ? ' día' : ' días');
    if ($diff->h > 0)
      $duration_parts[] = $diff->h . ($diff->h == 1 ? ' hora' : ' horas');
    if ($diff->i > 0)
      $duration_parts[] = $diff->i . ($diff->i == 1 ? ' minuto' : ' minutos');

    $duration_str = implode(', ', $duration_parts);
    if (empty($duration_str)) {
      $duration_str = $diff->s . ($diff->s == 1 ? ' segundo' : ' segundos');
    }

    if ($day_s === $day_e && $month_s === $month_e && $year_s === $year_e) {
      return "<strong>$day_s de $month_s, $year_s</strong><br><span style='font-size:0.75rem; color:#4b5563; font-weight:normal;'>De $time_s a $time_e<br>(Duración: $duration_str)</span>";
    } else {
      return "<strong>Del $day_s de $month_s al $day_e de $month_e, $year_e</strong><br><span style='font-size:0.75rem; color:#4b5563; font-weight:normal;'>De $time_s a $time_e<br>(Duración: $duration_str)</span>";
    }
  }
  return "$start_str a $end_str";
}

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

    $filter_hour_start = isset($_POST['filter_hour_start']) && $_POST['filter_hour_start'] !== '' ? (int) $_POST['filter_hour_start'] : null;
    $filter_hour_end = isset($_POST['filter_hour_end']) && $_POST['filter_hour_end'] !== '' ? (int) $_POST['filter_hour_end'] : null;

    $total_lines_checked = 0;
    $parsed_lines = 0;
    $total_bytes = 0;
    $js_entries = [];

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
        $ip = $matches[1];
        $date_raw = $matches[2];

        // Extraer hora y filtrar por rango si aplica
        $hour = 0;
        $colon_pos = strpos($date_raw, ':');
        if ($colon_pos !== false && strlen($date_raw) >= $colon_pos + 3) {
          $hour = (int) substr($date_raw, $colon_pos + 1, 2);
        }

        if ($filter_hour_start !== null && $filter_hour_end !== null) {
          if ($hour < $filter_hour_start || $hour > $filter_hour_end) {
            $has_valid_format = true; // El formato es correcto, aunque se filtre
            continue;
          }
        }

        $has_valid_format = true;
        $parsed_lines++;

        $method = $matches[3];
        $path = $matches[4];
        $status = (int) $matches[5];
        $bytes = ($matches[6] === '-') ? 0 : (int) $matches[6];
        $referer = $matches[7] ?? '';
        $user_agent = $matches[8] ?? '';

        $total_bytes += $bytes;

        // Rango de fechas
        if ($first_date === null) {
          $first_date = $date_raw;
        }
        $last_date = $date_raw; // El último será el final

        // Obtener fecha YYYY-MM-DD
        $date_ymd = '';
        $space_pos = strpos($date_raw, ' ');
        $date_part_only = ($space_pos !== false) ? substr($date_raw, 0, $space_pos) : $date_raw;
        $colon_pos_first = strpos($date_part_only, ':');
        $d_part = ($colon_pos_first !== false) ? substr($date_part_only, 0, $colon_pos_first) : $date_part_only;
        $d_parts = explode('/', $d_part);
        if (count($d_parts) === 3) {
          $months_map = [
            'Jan' => '01',
            'Feb' => '02',
            'Mar' => '03',
            'Apr' => '04',
            'May' => '05',
            'Jun' => '06',
            'Jul' => '07',
            'Aug' => '08',
            'Sep' => '09',
            'Oct' => '10',
            'Nov' => '11',
            'Dec' => '12'
          ];
          $year = $d_parts[2];
          $month = $months_map[$d_parts[1]] ?? '01';
          $day = str_pad($d_parts[0], 2, '0', STR_PAD_LEFT);
          $date_ymd = "$year-$month-$day";
        }

        // Distribución por hora
        // Formato de fecha típico: 10/Oct/2000:13:55:36 -0700
        $colon_pos = strpos($date_raw, ':');
        if ($colon_pos !== false && strlen($date_raw) >= $colon_pos + 3) {
          $hour = (int) substr($date_raw, $colon_pos + 1, 2);
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

        // Guardar entrada comprimida para JavaScript
        $js_entries[] = [
          $ip,
          $hour,
          $path,
          $status,
          $detected_bot ? $detected_bot : '',
          $is_static ? 1 : 0,
          $bytes,
          $date_ymd,
          $user_agent
        ];
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
        'hourly_distribution' => $hourly_distribution,
        'js_entries' => $js_entries
      ];

      // Save result for PDF generation
      $result['audit_id'] = 'log_' . bin2hex(random_bytes(16));
      $reports_dir = dirname(dirname(__DIR__)) . '/data/reports/logs';
      if (!is_dir($reports_dir)) {
          mkdir($reports_dir, 0777, true);
      }
      // Limpiar logs antiguos (más de 1 hora)
      $old_logs = glob($reports_dir . '/*.json');
      if ($old_logs) {
          foreach ($old_logs as $file) {
              if (filemtime($file) < time() - 3600) @unlink($file);
          }
      }
      file_put_contents($reports_dir . '/' . $result['audit_id'] . '.json', json_encode($result));
    }
  }
}

$page = page_config([
  'title' => 'Analizador de Logs Apache y Nginx Online',
  'description' => 'Sube o pega tu log de accesos (Apache/Nginx) y audita de forma instantánea el rastreo de bots de búsqueda, errores 404, IPs activas y consumo de crawl budget.',
  'canonical' => '/herramientas/analizador-logs/',
  'body_class' => 'page-analizador-logs',
  'schema_types' => ['WebApplication', 'FAQPage'],
  'rating_id' => 'analizador-logs',
  'active_nav' => 'herramientas',
  'breadcrumbs' => [
    ['label' => 'Herramientas', 'url' => '/herramientas/'],
    ['label' => 'Analizador de Logs', 'url' => ''],
  ],
  'faq_items' => [
    [
      'q' => '¿Por qué es importante analizar los logs del servidor para el SEO?',
      'a' => 'El análisis de logs es la única forma 100% fiable de saber exactamente cómo rastrea Googlebot (y otros buscadores) tu página web. Permite descubrir fugas de crawl budget, páginas huérfanas que Google sigue visitando, errores 404 ocultos y cuellos de botella de rendimiento que no aparecen en Google Search Console.'
    ],
    [
      'q' => '¿Qué es el Crawl Budget y cómo afecta a mi web?',
      'a' => 'El Crawl Budget (o presupuesto de rastreo) es el tiempo y recursos que Google destina a explorar tu web. Si Google gasta su presupuesto rastreando URLs con parámetros inútiles, redirecciones infinitas o recursos estáticos bloqueados, no tendrá tiempo de indexar tu contenido de valor, perjudicando tu posicionamiento.'
    ],
    [
      'q' => '¿Están seguros mis datos al subir un log a esta herramienta?',
      'a' => 'Totalmente seguros. Esta herramienta procesa el log de accesos temporalmente en memoria para generar las gráficas y el informe PDF. Una vez abandonas la página o el informe expira, los datos se eliminan automáticamente del servidor.'
    ],
    [
      'q' => '¿Qué formatos de archivo de log admite el analizador?',
      'a' => 'El analizador está optimizado para procesar los formatos estándar Common Log Format y Combined Log Format, que son los utilizados por defecto en servidores Apache y Nginx.'
    ]
  ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<style>
  /* Sobrescribir botón CTA para tener un precioso y llamativo borde negro */
  .cta-block--orange .btn--primary {
    border: 2px solid #111111 !important;
    background: #ffffff !important;
    color: #111111 !important;
    font-weight: 800 !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12) !important;
    transition: all 0.3s ease !important;
  }

  .cta-block--orange .btn--primary:hover {
    background: #111111 !important;
    color: #ffffff !important;
    border-color: #111111 !important;
  }

  /* Estilos premium para el dashboard con colores corporativos (Blanco, Naranja, Negro) */
  .tab-container {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    padding-bottom: 0.75rem;
  }

  .tab-button {
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.08);
    color: var(--muted);
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 600;
    transition: all 0.3s;
  }

  .tab-button:hover {
    color: #111;
    background: rgba(0, 0, 0, 0.04);
  }

  .tab-button.active {
    background: #e8681a !important;
    color: #fff !important;
    border-color: #e8681a !important;
  }

  .tab-content {
    display: none;
  }

  .tab-content.active {
    display: block;
  }

  .drag-area {
    border: 2px dashed rgba(232, 104, 26, 0.3);
    background: rgba(232, 104, 26, 0.01);
    border-radius: 8px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    margin-bottom: 1.5rem;
  }

  .drag-area:hover,
  .drag-area.dragover {
    border-color: #e8681a !important;
    background: rgba(232, 104, 26, 0.05);
  }

  .drag-icon {
    font-size: 2.5rem;
    color: #e8681a !important;
    margin-bottom: 0.75rem;
  }

  .drag-text {
    font-size: 1rem;
    color: #111;
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
    background: #ffffff !important;
    border: 1px solid #111111 !important;
    border-left: 4px solid #e8681a !important;
    /* Detalle de marca naranja */
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
  }

  .metric-box-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #4b5563 !important;
    /* Alto contraste */
    font-weight: 700 !important;
    margin-bottom: 0.5rem;
  }

  .metric-box-value {
    font-size: 1.6rem;
    font-weight: 850;
    color: #e8681a !important;
    /* Naranja en las métricas principales */
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
    width: 140px;
    color: #111111;
    font-weight: 600;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .chart-bar-track {
    background: rgba(0, 0, 0, 0.04);
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
    width: 70px;
    text-align: right;
    color: #111111;
    font-weight: 700;
    flex-shrink: 0;
  }

  .bar-color-2xx {
    background: #2ecc71;
  }

  .bar-color-3xx {
    background: #f1c40f;
  }

  .bar-color-4xx {
    background: #e67e22;
  }

  .bar-color-5xx {
    background: #e74c3c;
  }

  .bar-color-bot {
    background: #e8681a;
  }

  /* Naranja corporativo */

  .http-status-tab-btn:hover {
    background: #e8681a !important;
    color: #ffffff !important;
    border-color: #e8681a !important;
  }

  .http-status-tab-btn.active {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15) !important;
  }

  .timeline-graph {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    height: 150px;
    padding-top: 1rem;
    border-bottom: 2px solid rgba(0, 0, 0, 0.08);
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
    background: linear-gradient(180deg, #e8681a 0%, rgba(232, 104, 26, 0.15) 100%) !important;
    width: 100%;
    min-height: 2px;
    border-radius: 3px 3px 0 0;
    position: relative;
    cursor: pointer;
    transition: all 0.3s;
  }

  .timeline-bar:hover {
    background: #111111 !important;
  }

  .timeline-bar:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #111111;
    color: #fff;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    white-space: nowrap;
    z-index: 10;
    border: 1px solid #e8681a;
  }

  .timeline-label {
    font-size: 0.75rem;
    color: var(--muted);
    margin-top: 0.5rem;
  }

  .table-filter-input {
    width: 100%;
    max-width: 320px;
    background: #ffffff;
    border: 1px solid #111111;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    color: #111111;
    font-size: 0.85rem;
    margin-bottom: 1rem;
    transition: all 0.3s;
  }

  .table-filter-input:focus {
    border-color: #e8681a !important;
    box-shadow: 0 0 0 2px rgba(232, 104, 26, 0.1);
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

  /* Sobrescribir tema para el Dashboard y Tablas (Blanco, Naranja y Negro de alto contraste claro) */
  #dashboard {
    color: #111111 !important;
  }

  #dashboard .card--dark {
    background: #ffffff !important;
    /* Fondo blanco sólido */
    border: 1px solid #111111 !important;
    /* Borde negro de la marca */
    border-top: 4px solid #e8681a !important;
    /* Cabecera naranja corporativo */
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05) !important;
    border-radius: 8px !important;
  }

  #dashboard p {
    color: #4b5563 !important;
    /* Texto gris intermedio súper legible */
  }

  #dashboard .tech-table {
    border: 1px solid #111111 !important;
    background: #ffffff !important;
    border-collapse: collapse !important;
    width: 100% !important;
  }

  #dashboard .tech-table th {
    background: #111111 !important;
    /* Encabezado negro sólido corporativo */
    color: #ffffff !important;
    /* Texto blanco puro */
    font-weight: 700 !important;
    border-bottom: 2px solid #e8681a !important;
    /* Línea divisoria naranja */
    border-right: 1px solid #333333 !important;
    padding: 0.75rem 1rem !important;
  }

  #dashboard .tech-table td {
    color: #111111 !important;
    /* Texto de celdas negro */
    background: #ffffff !important;
    border-bottom: 1px solid #eeeeee !important;
    padding: 0.75rem 1rem !important;
  }

  #dashboard .tech-table tr:hover td {
    background: #fff9f5 !important;
    /* Hover naranja translúcido ultra suave */
  }

  #dashboard .tech-table td.monospace-url {
    color: #e8681a !important;
    /* URLs en un precioso tono naranja cálido corporativo */
    font-weight: 600 !important;
    font-family: monospace !important;
    font-size: 0.85rem !important;
  }

  /* Estilos de impresión premium y optimizados para PDF */
  @media print {
    body {
      background: #ffffff !important;
      color: #000000 !important;
    }

    /* Ocultar la cabecera del sitio, footer, navegación lateral, migas, hero y formulario */
    .site-header,
    .site-footer,
    .breadcrumbs,
    .page-hero,
    .tool-intro,
    #logsForm,
    .alert,
    .criterio-section,
    .tab-container,
    .table-filter-input,
    .btn-pdf-export {
      display: none !important;
    }

    /* Ajustes de maquetación del dashboard */
    #main,
    .container,
    #dashboard,
    .audit-results {
      width: 100% !important;
      max-width: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
      border: none !important;
      box-shadow: none !important;
      background: #ffffff !important;
      color: #000000 !important;
    }

    #dashboard .card--dark {
      border: 1px solid #111111 !important;
      box-shadow: none !important;
      margin-bottom: 2rem !important;
      page-break-inside: avoid !important;
    }

    .table-tab-content {
      display: block !important;
      margin-bottom: 3.5rem !important;
      page-break-inside: avoid !important;
    }

    .metric-card-grid {
      display: grid !important;
      grid-template-columns: repeat(4, 1fr) !important;
      gap: 1rem !important;
    }

    .metric-box {
      border: 1px solid #111111 !important;
      box-shadow: none !important;
      padding: 1rem !important;
      page-break-inside: avoid !important;
    }

    .tech-table th {
      background: #111111 !important;
      color: #ffffff !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    .tech-table td {
      background: #ffffff !important;
      color: #000000 !important;
    }

    * {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
  }
</style>

<main id="main">

  <section class="page-hero" aria-labelledby="logs-h1">
    <div class="container">
      <span class="hero-eyebrow">Auditoría de Tráfico y Crawling</span>
      <h1 id="logs-h1">Analizador de <span>Logs Apache y Nginx</span></h1>
      <p class="page-hero-desc">Audita la salud técnica de tu web de manera 100% local y segura. Sube tu archivo log y
        visualiza el comportamiento de los motores de búsqueda, errores 404 y actividad de IPs.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">

      <div class="tool-intro" style="margin-bottom: 2rem;">
        <h2>Audita tu presupuesto de rastreo y errores técnicos</h2>
        <p>Los archivos de logs de tu servidor contienen la verdad absoluta de lo que ocurre en tu web. Esta herramienta
          procesa tu log Common o Combined directamente en la sesión de PHP (sin guardar registros en disco)
          permitiéndote optimizar tu SEO técnico al instante.</p>
      </div>

      <!-- Formulario de entrada -->
      <div class="card"
        style="margin-bottom: 3rem; background: #ffffff !important; border: 1px solid #111111 !important; border-top: 4px solid #e8681a !important; box-shadow: 0 4px 20px rgba(0,0,0,0.02) !important; border-radius: 8px !important; padding: 2rem;">

        <div class="tab-container">
          <button type="button" class="tab-button active" onclick="switchTab('subir-archivo')">Subir Archivo (.log,
            .txt)</button>
          <button type="button" class="tab-button" onclick="switchTab('pegar-texto')">Pegar Líneas de Log</button>
        </div>

        <form action="/herramientas/analizador-logs/" method="POST" enctype="multipart/form-data" id="logsForm">

          <!-- Pestaña 1: Subir Archivo -->
          <div id="subir-archivo" class="tab-content active">
            <div class="drag-area" id="dragArea" onclick="document.getElementById('fileInput').click()">
              <div class="drag-icon">📁</div>
              <div class="drag-text">Arrastra tu archivo de log aquí o haz clic para buscarlo</div>
              <div class="drag-subtext">Formatos aceptados: .log, .txt (Máximo 5 MB)</div>
              <input type="file" name="log_file" id="fileInput" style="display: none;"
                onchange="handleFileSelect(this)">
            </div>
            <div id="fileName"
              style="text-align: center; color: #2ecc71; font-weight: 600; margin-bottom: 1rem; display: none;"></div>
          </div>

          <!-- Pestaña 2: Pegar Texto -->
          <div id="pegar-texto" class="tab-content">
            <div style="margin-bottom: 1.5rem;">
              <textarea name="log_text" rows="8" class="form-input"
                placeholder="127.0.0.1 - - [24/May/2026:11:45:22 +0200] &quot;GET /index.php HTTP/1.1&quot; 200 4502 &quot;-&quot; &quot;Googlebot/2.1&quot;..."
                style="font-family: monospace; font-size: 0.85rem; width: 100%; border-radius: 6px; background: #ffffff; border: 1px solid #111111; color: #111111; padding: 0.75rem;"></textarea>
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

          <div
            style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
            <h3 style="margin: 0; color: #e8681a; display: flex; align-items: center; gap: 0.5rem;">
              <span>📊</span> Dashboard de Análisis: <span
                style="color: #111111; font-weight: 400;"><?= h($result['source_name']) ?></span>
            </h3>
            <a href="/herramientas/auditor-cookies-api.php?action=pdf&tool=logs&id=<?= h($result['audit_id'] ?? '') ?>" class="btn btn--secondary btn-pdf-export"
              style="display: inline-flex; align-items: center; gap: 0.5rem; margin: 0; padding: 0.5rem 1.2rem; font-size: 0.85rem; border: 1px solid #111111; background: #ffffff; color: #111111; border-radius: 6px; cursor: pointer; transition: all 0.3s; font-weight: 600; text-decoration: none;">
              <span>🖨️</span> Descargar Informe PDF
            </a>
          </div>

          <!-- Filtro temporal reactivo en cliente (JS) -->
          <div
            style="background: #ffffff; border: 1px solid #111111; border-top: 4px solid #e8681a; border-radius: 8px; padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.02);"
            class="btn-pdf-export">
            <div
              style="display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;"
              onclick="toggleClientFilter()">
              <h4 style="margin: 0; color: #111111; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>⏱️</span> Filtrar Informe (Fechas y Horas) en Tiempo Real
              </h4>
              <span id="clientFilterIndicator" style="font-weight: 700; color: #e8681a; font-size: 0.9rem;">[ Mostrar
                Filtro ]</span>
            </div>
            <div id="clientFilterDrawer"
              style="display: none; margin-top: 1.25rem; border-top: 1px dashed #eeeeee; padding-top: 1.25rem;">

              <!-- Rango de Fechas -->
              <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
                <div style="flex: 1; min-width: 200px;">
                  <label
                    style="font-size: 0.85rem; font-weight: 700; color: #111111; display: block; margin-bottom: 0.5rem;">
                    📅 Fecha de Inicio:
                  </label>
                  <select id="dateStartSelect" onchange="updateHourlyFilter()"
                    style="width: 100%; padding: 0.45rem 0.75rem; border: 1px solid #111111; border-radius: 4px; font-size: 0.85rem; background: #ffffff; color: #111111; font-weight: 600; cursor: pointer;"></select>
                </div>
                <div style="flex: 1; min-width: 200px;">
                  <label
                    style="font-size: 0.85rem; font-weight: 700; color: #111111; display: block; margin-bottom: 0.5rem;">
                    📅 Fecha de Fin:
                  </label>
                  <select id="dateEndSelect" onchange="updateHourlyFilter()"
                    style="width: 100%; padding: 0.45rem 0.75rem; border: 1px solid #111111; border-radius: 4px; font-size: 0.85rem; background: #ffffff; color: #111111; font-weight: 600; cursor: pointer;"></select>
                </div>
              </div>

              <!-- Rango de Horas -->
              <div
                style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center; border-top: 1px dashed #eeeeee; padding-top: 1.25rem;">
                <div style="flex-grow: 1; min-width: 280px;">
                  <label
                    style="font-size: 0.85rem; font-weight: 700; color: #111111; display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Rango de Horas Seleccionado:</span>
                    <span id="rangeLabel" style="color: #e8681a; font-family: monospace;">00:00 a 23:59</span>
                  </label>
                  <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted);">00:00</span>
                    <input type="range" id="hourStartSlider" min="0" max="23" value="0" oninput="updateHourlyFilter()"
                      style="flex-grow:1; accent-color:#e8681a;">
                    <span style="font-weight: 600; font-size: 0.85rem; color:#4b5563;">a</span>
                    <input type="range" id="hourEndSlider" min="0" max="23" value="23" oninput="updateHourlyFilter()"
                      style="flex-grow:1; accent-color:#e8681a;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted);">23:59</span>
                  </div>
                </div>
                <button type="button" class="btn btn--secondary" onclick="resetHourlyFilter()"
                  style="margin: 0; padding: 0.5rem 1rem; font-size: 0.85rem; border: 1px solid #111111; background: #fff; color: #111; border-radius: 6px;">Mostrar
                  Todo</button>
              </div>
              <p style="font-size: 0.8rem; color: var(--muted); margin: 0.75rem 0 0 0; line-height: 1.4;">
                Modifica el rango de fechas y horas para ver cómo el informe al completo se vuelve a calcular
                reactivamente al instante.
              </p>
            </div>
          </div>

          <!-- Bloque de Métricas Principales -->
          <div class="metric-card-grid">
            <div class="metric-box">
              <span class="metric-box-label">Peticiones Procesadas</span>
              <span class="metric-box-value"
                id="metric-parsed-lines"><?= number_format($result['parsed_lines'], 0, ',', '.') ?></span>
            </div>
            <div class="metric-box">
              <span class="metric-box-label">IPs Únicas</span>
              <span class="metric-box-value"
                id="metric-unique-ips"><?= number_format($result['unique_ips_count'], 0, ',', '.') ?></span>
            </div>
            <div class="metric-box">
              <span class="metric-box-label">Ancho de Banda</span>
              <span class="metric-box-value" id="metric-bandwidth"><?= $result['bandwidth_mb'] ?> MB</span>
            </div>
            <div class="metric-box">
              <span class="metric-box-label">Intervalo y Duración</span>
              <span class="metric-box-value" id="metric-dates-humanized"
                style="font-size: 0.85rem; font-weight: 600; line-height: 1.4; color: #111111; margin-top: 0.25rem; display: block;">
                <?= humanize_log_dates($result['date_start'], $result['date_end']) ?>
              </span>
            </div>
          </div>

          <!-- Códigos de Estado HTTP -->
          <div class="card card--dark" style="margin-bottom: 2.5rem;">
            <h4
              style="color: #111111; font-size: 1.15rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
              <span>🔌</span> Códigos de Respuesta HTTP
            </h4>
            <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Distribución de estados devueltos
              por el servidor.</p>

            <div class="chart-bar-container" id="status-codes-bar-container" style="margin-bottom: 1.5rem;">
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
                  if (in_array((int) $code, $group['codes'])) {
                    $group['count'] += $count;
                    $grouped = true;
                    break;
                  }
                }
                if (!$grouped) {
                  $first_num = substr($code, 0, 1);
                  if ($first_num == '2')
                    $status_groups['2xx (Éxito)']['count'] += $count;
                  elseif ($first_num == '3')
                    $status_groups['3xx (Redirección)']['count'] += $count;
                  elseif ($first_num == '4')
                    $status_groups['4xx (Error Cliente)']['count'] += $count;
                  elseif ($first_num == '5')
                    $status_groups['5xx (Error Servidor)']['count'] += $count;
                }
              }

              $max_group_count = 0;
              foreach ($status_groups as $g) {
                if ($g['count'] > $max_group_count)
                  $max_group_count = $g['count'];
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
                  <span class="chart-bar-value"><?= number_format($g['count'], 0, ',', '.') ?> <span
                      style="font-size:0.75rem; color:var(--muted); font-weight:400;">(<?= $pct ?>%)</span></span>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Desglose de Códigos y URLs Afectadas (Pestañas y Acordeones) -->
            <div style="margin-top: 2rem; border-top: 1px dashed #dddddd; padding-top: 1.5rem;" class="btn-pdf-export">
              <h5
                style="margin: 0 0 1rem 0; color: #111111; font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                <span>🔍</span> Desglose de Códigos y URLs Afectadas
              </h5>
              <div class="http-tabs-container"
                style="display: flex; gap: 0.5rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
                <button type="button" class="http-status-tab-btn" data-group="2xx" onclick="switchHttpStatusTab('2xx')"
                  style="padding: 0.5rem 1rem; border: 1px solid #111111; border-radius: 4px; background: #ffffff; color: #111111; font-weight: 700; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">
                  2xx (<span id="http-tab-badge-2xx">0</span>)
                </button>
                <button type="button" class="http-status-tab-btn" data-group="3xx" onclick="switchHttpStatusTab('3xx')"
                  style="padding: 0.5rem 1rem; border: 1px solid #111111; border-radius: 4px; background: #ffffff; color: #111111; font-weight: 700; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">
                  3xx (<span id="http-tab-badge-3xx">0</span>)
                </button>
                <button type="button" class="http-status-tab-btn active" data-group="4xx"
                  onclick="switchHttpStatusTab('4xx')"
                  style="padding: 0.5rem 1rem; border: 1px solid #111111; border-radius: 4px; background: #111111; color: #ffffff; font-weight: 700; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">
                  4xx (<span id="http-tab-badge-4xx">0</span>)
                </button>
                <button type="button" class="http-status-tab-btn" data-group="5xx" onclick="switchHttpStatusTab('5xx')"
                  style="padding: 0.5rem 1rem; border: 1px solid #111111; border-radius: 4px; background: #ffffff; color: #111111; font-weight: 700; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">
                  5xx (<span id="http-tab-badge-5xx">0</span>)
                </button>
              </div>
              <div id="http-status-tab-content">
                <!-- Se poblará dinámicamente mediante Javascript al filtrar -->
              </div>
            </div>
          </div>

          <!-- Actividad de Bots de Búsqueda y Crawlers -->
          <div class="card card--dark" style="margin-bottom: 2.5rem;">
            <h4
              style="color: #111111; font-size: 1.15rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
              <span>🤖</span> Top Crawlers y Motores de Búsqueda
            </h4>
            <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Visitas detectadas según firmas en
              el User-Agent.</p>

            <div class="chart-bar-container" id="bots-bar-container" style="margin-bottom: 1.5rem;">
              <?php if (empty($result['top_bots'])): ?>
                <p style="font-size: 0.9rem; color: var(--muted); text-align: center; margin-top: 1.5rem;">No se han
                  detectado firmas conocidas de bots en este registro.</p>
              <?php else:
                $max_bot_hits = reset($result['top_bots']);
                foreach ($result['top_bots'] as $bot_name => $bot_hits):
                  $bot_pct = $max_bot_hits > 0 ? round(($bot_hits / $max_bot_hits) * 100, 1) : 0;
                  $pct_total = $result['parsed_lines'] > 0 ? round(($bot_hits / $result['parsed_lines']) * 100, 1) : 0;
                  ?>
                  <div class="chart-bar-item">
                    <span class="chart-bar-label"
                      style="width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                      title="<?= h($bot_name) ?>"><?= h($bot_name) ?></span>
                    <div class="chart-bar-track">
                      <div class="chart-bar-fill bar-color-bot" style="width: <?= $bot_pct ?>%;"></div>
                    </div>
                    <span class="chart-bar-value"><?= number_format($bot_hits, 0, ',', '.') ?> <span
                        style="font-size:0.75rem; color:var(--muted); font-weight:400;">(<?= $pct_total ?>%)</span></span>
                  </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Glosario explicativo interactivo de Crawlers -->
            <details style="margin-top: 1.5rem; border: 1px solid #111111; border-radius: 6px; background: #fcfcfc;"
              class="btn-pdf-export">
              <summary
                style="font-weight: 700; cursor: pointer; color: #111111; padding: 0.75rem 1rem; outline: none; font-size: 0.85rem; user-select: none;"
                onmouseover="this.style.background='#fff9f5'" onmouseout="this.style.background='transparent'">
                💡 ¿Qué significa cada crawler o bot? (Haz clic para ver explicaciones SEO)
              </summary>
              <div
                style="padding: 1.25rem; border-top: 1px solid #111111; background: #ffffff; border-radius: 0 0 6px 6px;">
                <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; line-height: 1.6; color: #4b5563;">
                  <li style="margin-bottom: 0.75rem;">
                    <strong style="color: #111111;">Googlebot / Google Ads Bot:</strong> El rastreador oficial de Google.
                    Escanea tu sitio web para indexarlo en los resultados de búsqueda globales y verificar las landings de
                    anuncios en Google Ads.
                  </li>
                  <li style="margin-bottom: 0.75rem;">
                    <strong style="color: #111111;">Bingbot:</strong> El bot de búsqueda oficial de Microsoft Bing. Su
                    comportamiento es similar al de Googlebot, posicionando tu contenido en Bing y Yahoo.
                  </li>
                  <li style="margin-bottom: 0.75rem;">
                    <strong style="color: #e8681a;">Scripts de Desarrollador (curl/python...):</strong> Peticiones
                    automáticas realizadas mediante herramientas de terminal o librerías de software (como `curl`, `wget`,
                    scripts de `Python`, `Node.js`, etc.). Esto suele deberse a desarrolladores realizando integraciones,
                    rastreos manuales de scraping o peticiones técnicas automatizadas (no proceden de buscadores
                    comerciales).
                  </li>
                  <li style="margin-bottom: 0.75rem;">
                    <strong style="color: #111111;">AhrefsBot / SemrushBot / MJ12Bot:</strong> Bots comerciales de
                    plataformas de marketing y SEO. Auditan la estructura, enlazado interno y velocidad de tu sitio para
                    generar los datos mostrados en herramientas profesionales de SEO.
                  </li>
                  <li style="margin-bottom: 0.75rem;">
                    <strong style="color: #111111;">Applebot:</strong> Robot oficial de Apple usado para nutrir las
                    búsquedas de Siri y las sugerencias integradas en dispositivos macOS, iOS y iPadOS.
                  </li>
                  <li style="margin-bottom: 0.75rem;">
                    <strong style="color: #111111;">Facebook Crawler / TwitterBot:</strong> Bots de redes sociales que
                    acceden a tus páginas cuando alguien comparte un enlace en su plataforma. Escanean las etiquetas Open
                    Graph (`og:image`, `og:title`) para componer la previsualización del enlace.
                  </li>
                  <li style="margin-bottom: 0.75rem;">
                    <strong style="color: #111111;">Screaming Frog (Auditoría):</strong> Escáner técnico de SEO
                    especializado que emula a los robots de búsqueda para encontrar enlaces rotos, redirecciones, bucles u
                    optimizaciones de metaetiquetas.
                  </li>
                </ul>
              </div>
            </details>
          </div>

          <!-- Timeline por horas -->
          <div class="card card--dark" style="margin-bottom: 2.5rem;">
            <h4
              style="color: #111111; font-size: 1.15rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
              <span>⏰</span> Actividad Temporal (Timeline por Horas)
            </h4>
            <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Frecuencia horaria para localizar
              picos de tráfico en el servidor.</p>

            <?php
            $max_hour_hits = max($result['hourly_distribution']);
            ?>
            <div class="timeline-graph">
              <?php foreach ($result['hourly_distribution'] as $hour => $hits):
                $bar_h = $max_hour_hits > 0 ? round(($hits / $max_hour_hits) * 100, 1) : 0;
                $formatted_hour = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                ?>
                <div class="timeline-col">
                  <div class="timeline-bar" style="height: calc(<?= $bar_h ?>% - 4px);"
                    data-tooltip="<?= $formatted_hour ?> — <?= number_format($hits, 0, ',', '.') ?> hits"></div>
                  <span class="timeline-label"><?= str_pad($hour, 2, '0', STR_PAD_LEFT) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Pestañas de Tablas Detalladas -->
          <div class="card card--dark">
            <div class="tab-container" style="border-bottom-color: rgba(0,0,0,0.05); flex-wrap: wrap; gap: 0.5rem;">
              <button type="button" class="tab-button active" onclick="switchTableTab('table-urls-no-static')">URLs
                Indexables (HTML)</button>
              <button type="button" class="tab-button" onclick="switchTableTab('table-urls')">Todas las URLs</button>
              <button type="button" class="tab-button" onclick="switchTableTab('table-404s')"
                style="color: #ff6b6b;">Errores 404 (SEO Redir)</button>
              <button type="button" class="tab-button" onclick="switchTableTab('table-ips')">IPs más Activas</button>
              <button type="button" class="tab-button" onclick="switchTableTab('table-bots')">Bots / User-Agents</button>
            </div>

            <!-- Tabla 1: URLs Indexables / No Estáticas -->
            <div id="table-urls-no-static" class="table-tab-content" style="display: block;">
              <div class="card-header-flex">
                <div>
                  <h4 style="margin: 0; color: #111111;">Top URLs Indexables (Páginas HTML)</h4>
                  <p style="font-size: 0.85rem; color: var(--muted); margin: 0.25rem 0 0 0;">Visualiza únicamente las
                    rutas sin recursos estáticos (CSS, JS, imágenes...).</p>
                </div>
                <input type="text" class="table-filter-input" placeholder="Filtrar por URL..."
                  onkeyup="filterTable(this, 'url-no-static-tbody')">
              </div>

              <div style="display: flex; gap: 2rem; flex-wrap: wrap; margin-top: 1rem;">
                <div style="flex: 1 1 500px; min-width: 0;">
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
                        <tr>
                          <td colspan="3" style="text-align:center; color: var(--muted);">No se encontraron URLs de páginas
                            en este registro.</td>
                        </tr>
                      <?php else:
                        foreach ($result['top_urls_no_static'] as $url => $hits):
                          $pct = round(($hits / $result['parsed_lines']) * 100, 1);
                          ?>
                          <tr>
                            <td class="monospace-url"><?= h($url) ?></td>
                            <td style="text-align: right; font-weight: 700; color: #111111;">
                              <?= number_format($hits, 0, ',', '.') ?></td>
                            <td style="text-align: right; color: var(--muted); font-size: 0.85rem;"><?= $pct ?>%</td>
                          </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                  </table>
                </div>

                <!-- Canvas de Gráfico Donut de Tráfico (Top 5) -->
                <?php if (!empty($result['top_urls_no_static'])): ?>
                  <div
                    style="flex: 0 0 300px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #111111; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin: 0 auto; page-break-inside: avoid;">
                    <h5
                      style="margin: 0 0 1.25rem 0; font-size: 0.85rem; color: #111111; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; text-align: center;">
                      Distribución de Tráfico (Top 5)</h5>
                    <div
                      style="position: relative; width: 160px; height: 160px; display: flex; align-items: center; justify-content: center;">
                      <canvas id="urlDonutChart" width="160" height="160" style="width: 160px; height: 160px;"></canvas>
                    </div>
                    <div id="donutLegend"
                      style="display: flex; flex-direction: column; gap: 0.5rem; width: 100%; margin-top: 1.25rem; font-size: 0.75rem; color: #333333;">
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Tabla 2: Todas las URLs -->
            <div id="table-urls" class="table-tab-content" style="display: none;">
              <div class="card-header-flex">
                <div>
                  <h4 style="margin: 0; color: #111111;">Top 10 URLs Solicitadas (Total)</h4>
                  <p style="font-size: 0.85rem; color: var(--muted); margin: 0.25rem 0 0 0;">Incluye absolutamente todas
                    las peticiones (páginas, imágenes, fuentes, etc).</p>
                </div>
                <input type="text" class="table-filter-input" placeholder="Filtrar por URL..."
                  onkeyup="filterTable(this, 'url-tbody')">
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
                    <tr>
                      <td colspan="3" style="text-align:center; color: var(--muted);">Sin peticiones registradas.</td>
                    </tr>
                  <?php else:
                    foreach ($result['top_urls'] as $url => $hits):
                      $pct = round(($hits / $result['parsed_lines']) * 100, 1);
                      ?>
                      <tr>
                        <td class="monospace-url"><?= h($url) ?></td>
                        <td style="text-align: right; font-weight: 700; color: #111111;">
                          <?= number_format($hits, 0, ',', '.') ?></td>
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
                  <p style="font-size: 0.85rem; color: var(--muted); margin: 0.25rem 0 0 0;">Rutas que devuelven un 404 No
                    Encontrado. Planifica redirecciones 301 para salvar enlaces.</p>
                </div>
                <input type="text" class="table-filter-input" placeholder="Filtrar por URL..."
                  onkeyup="filterTable(this, '404-tbody')">
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
                    <tr>
                      <td colspan="3" style="text-align:center; color: #2ecc71; font-weight: 600; padding: 1.5rem;">✓
                        ¡Enhorabuena! No se han registrado errores 404 en este fragmento.</td>
                    </tr>
                  <?php else:
                    foreach ($result['top_404s'] as $url => $hits):
                      $pct = round(($hits / $result['parsed_lines']) * 100, 1);
                      ?>
                      <tr>
                        <td class="monospace-url" style="color: #ff6b6b !important;"><?= h($url) ?></td>
                        <td style="text-align: right; font-weight: 700; color: #111111;">
                          <?= number_format($hits, 0, ',', '.') ?></td>
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
                  <h4 style="margin: 0; color: #111111;">Top 10 Direcciones IP Activas</h4>
                  <p style="font-size: 0.85rem; color: var(--muted); margin: 0.25rem 0 0 0;">Identifica qué clientes
                    consumen la mayor cantidad de recursos de tu web.</p>
                </div>
                <input type="text" class="table-filter-input" placeholder="Filtrar por IP..."
                  onkeyup="filterTable(this, 'ip-tbody')">
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
                    <tr>
                      <td colspan="3" style="text-align:center; color: var(--muted);">Sin IPs registradas.</td>
                    </tr>
                  <?php else:
                    foreach ($result['top_ips'] as $ip => $hits):
                      $pct = round(($hits / $result['parsed_lines']) * 100, 1);
                      ?>
                      <tr>
                        <td style="font-family: monospace; font-size: 0.85rem; color: #111111;"><?= h($ip) ?></td>
                        <td style="text-align: right; font-weight: 700; color: #111111;">
                          <?= number_format($hits, 0, ',', '.') ?></td>
                        <td style="text-align: right; color: var(--muted); font-size: 0.85rem;"><?= $pct ?>%</td>
                      </tr>
                    <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Tabla 5: Bots y User-Agents -->
            <div id="table-bots" class="table-tab-content" style="display: none;">
              <div class="card-header-flex">
                <div>
                  <h4 style="margin: 0; color: #111111;">Bots y Motores de Búsqueda</h4>
                  <p style="font-size: 0.85rem; color: var(--muted); margin: 0.25rem 0 0 0;">Frecuencia de rastreo
                    detallada de rastreadores web (crawlers) identificados.</p>
                </div>
                <input type="text" class="table-filter-input" placeholder="Filtrar por Bot..."
                  onkeyup="filterTable(this, 'bot-tbody')">
              </div>
              <table class="tech-table">
                <thead>
                  <tr>
                    <th>Nombre del Bot / User-Agent</th>
                    <th style="text-align: right; width: 140px;">Peticiones (Hits)</th>
                    <th style="text-align: right; width: 100px;">Porcentaje</th>
                  </tr>
                </thead>
                <tbody id="bot-tbody">
                  <?php if (empty($result['top_bots'])): ?>
                    <tr>
                      <td colspan="3" style="text-align:center; color: var(--muted);">No se detectó actividad de bots de
                        búsqueda en este registro.</td>
                    </tr>
                  <?php else:
                    foreach ($result['top_bots'] as $bot => $hits):
                      $pct = round(($hits / $result['parsed_lines']) * 100, 1);
                      ?>
                      <tr>
                        <td style="font-weight: 600; color: #111111;"><?= h($bot) ?></td>
                        <td style="text-align: right; font-weight: 700; color: #e8681a;">
                          <?= number_format($hits, 0, ',', '.') ?></td>
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
                <h3
                  style="color:#111; font-size:1.15rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">
                  El Presupuesto de Rastreo (Crawl Budget)</h3>
                <ul style="list-style:none; padding:0; display:grid; gap:1.25rem;">
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">¿Qué es
                      el Crawl Budget?</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Es el tiempo y la
                      cantidad de páginas que Googlebot y otros buscadores deciden rastrear en tu sitio web durante un
                      tiempo determinado. Si tu web tiene miles de páginas sin optimizar, Google puede consumir tu
                      presupuesto de rastreo antes de llegar a tus contenidos clave.</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">¿Por qué
                      los recursos estáticos estorban en los logs?</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Normalmente, el 80%
                      de un archivo log corresponde a peticiones de archivos JS, CSS, tipografías e imágenes. Filtrar
                      estos recursos te permite aislar las páginas reales (HTML indexables) para ver de forma transparente
                      qué URLs están rastreando los bots y con qué frecuencia.</span>
                  </li>
                  <li>
                    <strong
                      style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Comportamiento
                      de Googlebot</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Analizar las
                      visitas de Googlebot te indica si el buscador ha descubierto tus últimas URLs publicadas o si está
                      atascado en secciones sin valor debido a una arquitectura web ineficiente o bucles de filtrados de
                      productos.</span>
                  </li>
                </ul>
              </div>

              <div>
                <h3
                  style="color:#111; font-size:1.15rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">
                  Códigos de Respuesta y Errores</h3>
                <ul style="list-style:none; padding:0; display:grid; gap:1.25rem;">
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Errores
                      404 y su penalización técnica</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Cuando Googlebot
                      rastrea una URL vieja y recibe un código 404, consume tiempo y presupuesto inútilmente. Además, si
                      esa URL tiene enlaces entrantes, estás perdiendo autoridad. Mapear y redirigir con un código 301
                      (Redirección Permanente) tus principales 404s soluciona este problema de inmediato.</span>
                  </li>
                  <li>
                    <strong style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Los
                      Errores 5xx (Server Error)</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Un exceso de
                      códigos 500, 502 o 504 indica caídas o colapsos de tu hosting. Si Googlebot detecta errores 5xx de
                      forma continuada, reducirá la velocidad de rastreo e incluso podría llegar a desindexar
                      temporalmente URLs importantes al interpretarlas como caídas definitivas del servicio.</span>
                  </li>
                  <li>
                    <strong
                      style="color:var(--orange); font-size:0.95rem; display:block; margin-bottom:0.15rem;">Scrapers,
                      Spammers e IPs Sospechosas</strong>
                    <span style="font-size:0.9rem; color:var(--text); line-height:1.5; display:block;">Al revisar el Top
                      de IPs, si encuentras direcciones que realizan miles de peticiones en un corto periodo de tiempo y
                      no corresponden a motores de búsqueda legítimos, probablemente sean bots de scraping robando tu
                      contenido o atacando formularios. Bloquear estas IPs en tu firewall (.htaccess o Cloudflare)
                      liberará recursos del servidor de inmediato.</span>
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
            <p>Tus logs contienen la dirección IP y el comportamiento exacto de todos tus usuarios. Subir estos logs a
              servidores de terceros desconocidos puede suponer un incumplimiento grave del RGPD. Mi herramienta procesa
              todo a través de PHP de forma efímera en la memoria RAM del servidor de victor-alonso.es, sin almacenar
              una sola línea en base de datos ni archivos locales. Tu privacidad es inquebrantable.</p>
          </div>

          <div class="criterio-card">
            <h3>2. La Verdad Oculta frente a Google Analytics</h3>
            <p>Los bloqueadores de publicidad, Brave y las extensiones de privacidad impiden que Google Analytics
              registre entre un 20% y un 40% del tráfico web. Los logs del servidor de Apache o Nginx son imposibles de
              bloquear. Todo usuario, bot de rastreo o script malicioso deja una huella física obligatoria en tu
              servidor, ofreciéndote métricas 100% reales.</p>
          </div>

          <div class="criterio-card">
            <h3>3. Optimización Quirúrgica del Rastreo SEO</h3>
            <p>Herramientas como Google Search Console te ofrecen una muestra pequeña de la actividad de Googlebot. Con
              un análisis de logs puedes conocer en tiempo real la hora exacta de rastreo, qué secciones del código se
              devuelven lentas o si estás sufriendo de canibalizaciones y bucles infinitos de redirección.</p>
          </div>
        </div>
      </div>

      <!-- Widget de Votación y Rich Snippet -->
      <?php render_rating_widget('analizador-logs'); ?>

    </div>
  </section>

  <?php require dirname(__DIR__) . '/includes/faq.php'; ?>

  <!-- CTA final -->
  <?php
  $cta = [
    'title' => '¿Has detectado problemas graves de rastreo o caídas 5xx?',
    'subtitle' => 'Un log con exceso de errores técnicos es el síntoma de un servidor mal optimizado o una estructura de enlaces defectuosa.',
    'btn_label' => 'Auditar mi servidor de forma avanzada',
    'btn_href' => '/contacto/',
    'whatsapp' => true,
    'variant' => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>


</main>

<script>
  // Cargar datos procesados para filtrado dinámico en cliente (tiempo real)
  const logEntries = <?php echo isset($result['js_entries']) ? json_encode($result['js_entries']) : '[]'; ?>;
  const initialStartDate = <?php echo isset($result['date_start']) ? json_encode($result['date_start']) : '""'; ?>;
  const initialEndDate = <?php echo isset($result['date_end']) ? json_encode($result['date_end']) : '""'; ?>;

  function parseLogDateJs(dateStr) {
    if (!dateStr) return null;
    const parts = dateStr.split(' ')[0].split(':');
    const dateParts = parts[0].split('/'); // [24, May, 2026]
    const months = {
      'Jan': 'Enero', 'Feb': 'Febrero', 'Mar': 'Marzo', 'Apr': 'Abril',
      'May': 'Mayo', 'Jun': 'Junio', 'Jul': 'Julio', 'Aug': 'Agosto',
      'Sep': 'Septiembre', 'Oct': 'Octubre', 'Nov': 'Noviembre', 'Dec': 'Diciembre'
    };
    return {
      day: parseInt(dateParts[0]),
      month: months[dateParts[1]] || dateParts[1],
      year: parseInt(dateParts[2]),
      time: parts.slice(1).join(':') // "00:07:51"
    };
  }

  function populateDateSelectors() {
    const startSelect = document.getElementById('dateStartSelect');
    const endSelect = document.getElementById('dateEndSelect');
    if (!startSelect || !endSelect) return;

    // Obtener días únicos ordenados
    const days = [...new Set(logEntries.map(e => e[7]))].filter(Boolean).sort();

    startSelect.innerHTML = '';
    endSelect.innerHTML = '';

    const monthsNames = {
      '01': 'Enero', '02': 'Febrero', '03': 'Marzo', '04': 'Abril',
      '05': 'Mayo', '06': 'Junio', '07': 'Julio', '08': 'Agosto',
      '09': 'Septiembre', '10': 'Octubre', '11': 'Noviembre', '12': 'Diciembre'
    };

    days.forEach(d => {
      const parts = d.split('-'); // [YYYY, MM, DD]
      const display = `${parseInt(parts[2])} de ${monthsNames[parts[1]] || parts[1]}, ${parts[0]}`;

      const optStart = document.createElement('option');
      optStart.value = d;
      optStart.textContent = display;
      startSelect.appendChild(optStart);

      const optEnd = document.createElement('option');
      optEnd.value = d;
      optEnd.textContent = display;
      endSelect.appendChild(optEnd);
    });

    if (days.length > 0) {
      startSelect.value = days[0];
      endSelect.value = days[days.length - 1];
    }
  }

  function humanizeUserAgent(uaStr, detectedBot) {
    if (detectedBot) {
      if (detectedBot.includes('Script') || detectedBot.includes('curl') || detectedBot.includes('python')) {
        return `💻 Script / Scraper`;
      }
      return `🤖 ${detectedBot}`;
    }

    if (!uaStr || uaStr === '-' || uaStr === 'Desconocido') {
      return `👤 Usuario Legítimo`;
    }

    const ua = uaStr.toLowerCase();

    // Detectar OS
    let os = '';
    if (ua.includes('windows')) os = 'Windows';
    else if (ua.includes('android')) os = 'Android';
    else if (ua.includes('iphone') || ua.includes('ipad')) os = 'iOS';
    else if (ua.includes('macintosh') || ua.includes('mac os')) os = 'macOS';
    else if (ua.includes('linux')) os = 'Linux';

    // Detectar Navegador
    let browser = '';
    if (ua.includes('firefox')) browser = 'Firefox';
    else if (ua.includes('opr/') || ua.includes('opera')) browser = 'Opera';
    else if (ua.includes('edg/')) browser = 'Edge';
    else if (ua.includes('chrome')) browser = 'Chrome';
    else if (ua.includes('safari') && !ua.includes('chrome')) browser = 'Safari';

    if (browser && os) {
      return `👤 ${browser} (${os})`;
    } else if (browser) {
      return `👤 ${browser}`;
    } else if (os) {
      return `👤 Dispositivo (${os})`;
    }

    return `👤 Usuario Legítimo`;
  }

  function renderHttpStatusDesglose(filteredEntries) {
    const contentDiv = document.getElementById('http-status-tab-content');
    if (!contentDiv) return;

    // Agrupar peticiones por código y URLs con detalles
    const groups = {
      '2xx': {},
      '3xx': {},
      '4xx': {},
      '5xx': {}
    };

    filteredEntries.forEach(entry => {
      const url = entry[2];
      const status = entry[3].toString();
      const firstNum = status.charAt(0);
      let groupKey = '';
      if (firstNum === '2') groupKey = '2xx';
      else if (firstNum === '3') groupKey = '3xx';
      else if (firstNum === '4') groupKey = '4xx';
      else if (firstNum === '5') groupKey = '5xx';

      if (groupKey) {
        if (!groups[groupKey][status]) {
          groups[groupKey][status] = { total: 0, urls: {} };
        }
        groups[groupKey][status].total++;
        if (!groups[groupKey][status].urls[url]) {
          groups[groupKey][status].urls[url] = { total: 0, details: [] };
        }
        groups[groupKey][status].urls[url].total++;
        groups[groupKey][status].urls[url].details.push({
          ip: entry[0],
          time: entry[1].toString().padStart(2, '0') + ':00',
          date: entry[7],
          ua: entry[8] || 'Desconocido',
          bot: entry[4] || ''
        });
      }
    });

    // Obtener la pestaña activa actualmente
    const activeTab = document.querySelector('.http-status-tab-btn.active')?.dataset.group || '4xx';

    // Actualizar los contadores numéricos en las pestañas en vivo
    const groupsTotal = { '2xx': 0, '3xx': 0, '4xx': 0, '5xx': 0 };
    Object.keys(groups).forEach(gk => {
      Object.keys(groups[gk]).forEach(st => {
        groupsTotal[gk] += groups[gk][st].total;
      });
      const badge = document.getElementById(`http-tab-badge-${gk}`);
      if (badge) badge.innerText = new Intl.NumberFormat('es-ES').format(groupsTotal[gk]);
    });

    // Obtener datos del grupo activo
    const activeGroupData = groups[activeTab];
    if (Object.keys(activeGroupData).length === 0) {
      contentDiv.innerHTML = `<p style="text-align: center; color: var(--muted); padding: 2rem 0; font-size: 0.9rem; margin: 0;">No se registraron peticiones con códigos ${activeTab} en el rango seleccionado.</p>`;
      return;
    }

    // Ordenar códigos de estado por volumen descendente
    const sortedStatusCodes = Object.entries(activeGroupData).sort((a, b) => b[1].total - a[1].total);

    let html = '';
    sortedStatusCodes.forEach(([statusCode, data]) => {
      // Ordenar las top 15 URLs por peticiones
      const sortedUrls = Object.entries(data.urls).sort((a, b) => b[1].total - a[1].total).slice(0, 15);

      html += `
        <details class="http-code-details-box" style="margin-bottom: 0.75rem; border: 1px solid #111111; border-radius: 6px; background: #ffffff;">
            <summary style="font-weight: 700; cursor: pointer; color: #111111; padding: 0.75rem 1rem; user-select: none; display: flex; justify-content: space-between; align-items: center; outline: none; transition: background 0.15s; font-size: 0.9rem;" onmouseover="this.style.background='#fff9f5'" onmouseout="this.style.background='transparent'">
                <span style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-family: monospace; font-size: 1.05rem; font-weight: 800; color: ${statusCode.startsWith('4') || statusCode.startsWith('5') ? '#ff4d4d' : '#2ecc71'};">
                        [Código ${statusCode}]
                    </span>
                </span>
                <span style="font-size: 0.85rem; color: #4b5563; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem;">
                    ${new Intl.NumberFormat('es-ES').format(data.total)} peticiones
                    <span style="color: #e8681a; font-weight: 700; font-size: 0.75rem;">▶ Desplegar URLs</span>
                </span>
            </summary>
            <div style="padding: 1rem; border-top: 1px solid #111111; background: #fafafa;">
                <p style="font-size: 0.85rem; font-weight: 700; color: #111111; margin: 0 0 0.75rem 0;">
                    Top URLs que causaron el código ${statusCode} (haz clic en cualquier URL para desglosar sus peticiones detalladas):
                </p>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; background: #ffffff; border: 1px solid #dddddd;">
                        <thead>
                            <tr style="background: #111111; color: #ffffff;">
                                <th style="padding: 0.5rem; text-align: left; font-size: 0.8rem; border: 1px solid #111111;">URL Afectada</th>
                                <th style="padding: 0.5rem; text-align: right; width: 110px; font-size: 0.8rem; border: 1px solid #111111;">Peticiones</th>
                                <th style="padding: 0.5rem; text-align: right; width: 90px; font-size: 0.8rem; border: 1px solid #111111;">% Código</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${sortedUrls.map(([url, countObj]) => {
        const urlPct = ((countObj.total / data.total) * 100).toFixed(1);
        return `
                                <tr style="border-bottom: 1px solid #eeeeee;">
                                    <td style="padding: 0.5rem; border: 1px solid #eeeeee; font-family: monospace; font-size: 0.8rem; color: #e8681a; word-break: break-all;">${escapeHtml(url)}</td>
                                    <td style="padding: 0.5rem; border: 1px solid #eeeeee; text-align: right; font-weight: 700; color: #111111;">${new Intl.NumberFormat('es-ES').format(countObj.total)}</td>
                                    <td style="padding: 0.5rem; border: 1px solid #eeeeee; text-align: right; color: var(--muted);">${urlPct}%</td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="padding: 0; border: none; background: #fafafa;">
                                        <details style="margin: 0.25rem 0.5rem 0.5rem 0.5rem; border: 1px solid #dddddd; border-radius: 4px; background: #ffffff;">
                                            <summary style="font-size: 0.75rem; color: #e8681a; cursor: pointer; font-weight: 700; padding: 0.35rem 0.5rem; outline: none; user-select: none;" onmouseover="this.style.background='#fff9f5'" onmouseout="this.style.background='transparent'">
                                                🔎 Ver IPs y User Agents de esta URL (${countObj.total} hits)
                                            </summary>
                                            <div style="padding: 0.75rem; max-height: 200px; overflow-y: auto; border-top: 1px solid #eeeeee;">
                                                <table style="width: 100%; border-collapse: collapse; font-size: 0.75rem;">
                                                    <thead>
                                                        <tr style="background: #111111; color: #ffffff; font-weight: 700;">
                                                            <th style="padding: 0.35rem; text-align: left;">IP</th>
                                                            <th style="padding: 0.35rem; text-align: left;">Fecha / Hora</th>
                                                            <th style="padding: 0.35rem; text-align: left;">User Agent</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${countObj.details.slice(0, 30).map(d => `
                                                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                                                <td style="padding: 0.35rem; font-family: monospace; font-weight: 600; color: #111111;">${escapeHtml(d.ip)}</td>
                                                                <td style="padding: 0.35rem; color: #4b5563; white-space: nowrap;">${escapeHtml(d.date)} ${escapeHtml(d.time)}</td>
                                                                <td style="padding: 0.35rem; color: #4b5563; font-weight: 600;" title="${escapeHtml(d.ua)}">${escapeHtml(humanizeUserAgent(d.ua, d.bot))}</td>
                                                            </tr>
                                                        `).join('')}
                                                        ${countObj.details.length > 30 ? `
                                                            <tr>
                                                                <td colspan="3" style="text-align: center; color: var(--muted); padding: 0.5rem; font-style: italic;">... y ${countObj.details.length - 30} peticiones más ...</td>
                                                            </tr>
                                                        ` : ''}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                                `;
      }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
        `;
    });
    contentDiv.innerHTML = html;
  }

  function switchHttpStatusTab(group) {
    document.querySelectorAll('.http-status-tab-btn').forEach(btn => {
      if (btn.dataset.group === group) {
        btn.classList.add('active');
        btn.style.background = '#111111';
        btn.style.color = '#ffffff';
      } else {
        btn.classList.remove('active');
        btn.style.background = '#ffffff';
        btn.style.color = '#111111';
      }
    });

    // Disparar redibujado de la pestaña sin alterar filtros generales
    const startHour = parseInt(document.getElementById('hourStartSlider').value);
    const endHour = parseInt(document.getElementById('hourEndSlider').value);
    const startDate = document.getElementById('dateStartSelect')?.value || '';
    const endDate = document.getElementById('dateEndSelect')?.value || '';

    const filtered = logEntries.filter(entry => {
      const hour = entry[1];
      const dateString = entry[7];
      if (startDate && dateString < startDate) return false;
      if (endDate && dateString > endDate) return false;
      return hour >= startHour && hour <= endHour;
    });

    renderHttpStatusDesglose(filtered);
  }

  function humanizeFilteredDatesJs(startHour, endHour) {
    const startDateSelect = document.getElementById('dateStartSelect');
    const endDateSelect = document.getElementById('dateEndSelect');

    const activeStartDate = startDateSelect && startDateSelect.value ? startDateSelect.value : '';
    const activeEndDate = endDateSelect && endDateSelect.value ? endDateSelect.value : '';

    function formatYmdToRaw(ymd) {
      if (!ymd) return '';
      const parts = ymd.split('-');
      const monthsNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const monthIdx = parseInt(parts[1]) - 1;
      const monthShort = monthsNames[monthIdx] || 'May';
      return `${parseInt(parts[2])}/${monthShort}/${parts[0]}:00:00:00 +0000`;
    }

    const startParsed = parseLogDateJs(activeStartDate ? formatYmdToRaw(activeStartDate) : initialStartDate);
    const endParsed = parseLogDateJs(activeEndDate ? formatYmdToRaw(activeEndDate) : initialEndDate);
    if (!startParsed || !endParsed) return '-';

    const hStart = startHour.toString().padStart(2, '0') + ':00';
    const hEnd = endHour.toString().padStart(2, '0') + ':59';

    let hoursDiff = endHour - startHour;
    if (hoursDiff < 0) hoursDiff = 0;

    let durationStr = '';
    if (startParsed.day === endParsed.day && startParsed.month === endParsed.month && startParsed.year === endParsed.year) {
      durationStr = hoursDiff === 0 ? "1 hora" : `${hoursDiff + 1} horas`;
      return `<strong>${startParsed.day} de ${startParsed.month}, ${startParsed.year}</strong><br><span style='font-size:0.75rem; color:#4b5563; font-weight:normal;'>De ${hStart} a ${hEnd}<br>(Duración: ${durationStr})</span>`;
    } else {
      const dStart = new Date(activeStartDate || initialStartDate.split(':')[0]);
      const dEnd = new Date(activeEndDate || initialEndDate.split(':')[0]);
      const diffTime = Math.abs(dEnd - dStart);
      const daysDiff = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (daysDiff > 0) {
        durationStr = `${daysDiff + 1} días y ${hoursDiff + 1} horas`;
      } else {
        durationStr = `${hoursDiff + 1} horas`;
      }
      return `<strong>Del ${startParsed.day} de ${startParsed.month} al ${endParsed.day} de ${endParsed.month}, ${endParsed.year}</strong><br><span style='font-size:0.75rem; color:#4b5563; font-weight:normal;'>De ${hStart} a ${hEnd}<br>(Duración: ${durationStr})</span>`;
    }
  }

  function toggleClientFilter() {
    const drawer = document.getElementById('clientFilterDrawer');
    const indicator = document.getElementById('clientFilterIndicator');
    if (drawer.style.display === 'none') {
      drawer.style.display = 'block';
      indicator.innerText = '[ Ocultar Filtro ]';
    } else {
      drawer.style.display = 'none';
      indicator.innerText = '[ Mostrar Filtro ]';
    }
  }

  function escapeHtml(string) {
    return String(string).replace(/[&<>"']/g, function (s) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
      }[s];
    });
  }

  function renderTable(tbodyId, topEntries, parsedLines, type) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    if (topEntries.length === 0) {
      if (type === '404') {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color: #2ecc71; font-weight: 600; padding: 1.5rem;">✓ ¡Enhorabuena! No se han registrado errores 404 en este rango.</td></tr>';
      } else {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color: var(--muted);">Sin registros.</td></tr>';
      }
      return;
    }

    let html = '';
    topEntries.forEach(([key, hits]) => {
      const pct = parsedLines > 0 ? ((hits / parsedLines) * 100).toFixed(1) : '0';
      const formattedHits = new Intl.NumberFormat('es-ES').format(hits);

      if (type === 'url-no-static' || type === 'url') {
        html += `<tr>
                <td class="monospace-url">${escapeHtml(key)}</td>
                <td style="text-align: right; font-weight: 700; color: #111111;">${formattedHits}</td>
                <td style="text-align: right; color: var(--muted); font-size: 0.85rem;">${pct}%</td>
            </tr>`;
      } else if (type === '404') {
        html += `<tr>
                <td class="monospace-url" style="color: #ff6b6b !important;">${escapeHtml(key)}</td>
                <td style="text-align: right; font-weight: 700; color: #111111;">${formattedHits}</td>
                <td style="text-align: right; color: var(--muted); font-size: 0.85rem;">${pct}%</td>
            </tr>`;
      } else if (type === 'ip') {
        html += `<tr>
                <td style="font-family: monospace; font-size: 0.85rem; color: #111111;">${escapeHtml(key)}</td>
                <td style="text-align: right; font-weight: 700; color: #111111;">${formattedHits}</td>
                <td style="text-align: right; color: var(--muted); font-size: 0.85rem;">${pct}%</td>
            </tr>`;
      } else if (type === 'bot') {
        html += `<tr>
                <td style="font-weight: 600; color: #111111;">${escapeHtml(key)}</td>
                <td style="text-align: right; font-weight: 700; color: #e8681a;">${formattedHits}</td>
                <td style="text-align: right; color: var(--muted); font-size: 0.85rem;">${pct}%</td>
            </tr>`;
      }
    });
    tbody.innerHTML = html;
  }

  function updateHourlyFilter() {
    const startHour = parseInt(document.getElementById('hourStartSlider').value);
    const endHour = parseInt(document.getElementById('hourEndSlider').value);

    if (startHour > endHour) {
      document.getElementById('hourEndSlider').value = startHour;
    }

    const actualEndHour = parseInt(document.getElementById('hourEndSlider').value);

    const startDateSelect = document.getElementById('dateStartSelect');
    const endDateSelect = document.getElementById('dateEndSelect');
    let startDate = startDateSelect ? startDateSelect.value : '';
    let endDate = endDateSelect ? endDateSelect.value : '';

    if (startDate && endDate && startDate > endDate) {
      endDateSelect.value = startDate;
      endDate = startDate;
    }

    const labelStart = startHour.toString().padStart(2, '0') + ':00';
    const labelEnd = actualEndHour.toString().padStart(2, '0') + ':59';
    document.getElementById('rangeLabel').innerText = `${labelStart} a ${labelEnd}`;

    const filtered = logEntries.filter(entry => {
      const hour = entry[1];
      const dateString = entry[7];
      if (startDate && dateString < startDate) return false;
      if (endDate && dateString > endDate) return false;
      return hour >= startHour && hour <= actualEndHour;
    });

    const parsedCount = filtered.length;
    document.getElementById('metric-parsed-lines').innerText = new Intl.NumberFormat('es-ES').format(parsedCount);

    const uniqueIpsSet = new Set();
    let totalBytes = 0;
    filtered.forEach(entry => {
      uniqueIpsSet.add(entry[0]);
      totalBytes += entry[6];
    });

    document.getElementById('metric-unique-ips').innerText = new Intl.NumberFormat('es-ES').format(uniqueIpsSet.size);
    document.getElementById('metric-bandwidth').innerText = (totalBytes / (1024 * 1024)).toFixed(2) + " MB";
    document.getElementById('metric-dates-humanized').innerHTML = humanizeFilteredDatesJs(startHour, actualEndHour);

    const statusCounts = {};
    filtered.forEach(entry => {
      const status = entry[3];
      statusCounts[status] = (statusCounts[status] || 0) + 1;
    });

    const statusGroups = {
      '2xx (Éxito)': { codes: [200, 201, 204, 206], color: 'bar-color-2xx', count: 0 },
      '3xx (Redirección)': { codes: [301, 302, 304, 307, 308], color: 'bar-color-3xx', count: 0 },
      '4xx (Error Cliente)': { codes: [400, 401, 403, 404, 410], color: 'bar-color-4xx', count: 0 },
      '5xx (Error Servidor)': { codes: [500, 502, 503, 504], color: 'bar-color-5xx', count: 0 }
    };

    Object.entries(statusCounts).forEach(([code, count]) => {
      const c = parseInt(code);
      let grouped = false;
      Object.entries(statusGroups).forEach(([label, g]) => {
        if (g.codes.includes(c)) {
          g.count += count;
          grouped = true;
        }
      });
      if (!grouped) {
        const firstNum = code.charAt(0);
        if (firstNum === '2') statusGroups['2xx (Éxito)'].count += count;
        else if (firstNum === '3') statusGroups['3xx (Redirección)'].count += count;
        else if (firstNum === '4') statusGroups['4xx (Error Cliente)'].count += count;
        else if (firstNum === '5') statusGroups['5xx (Error Servidor)'].count += count;
      }
    });

    let maxGroupCount = 0;
    Object.values(statusGroups).forEach(g => {
      if (g.count > maxGroupCount) maxGroupCount = g.count;
    });

    let statusHtml = '';
    Object.entries(statusGroups).forEach(([label, g]) => {
      const pct = parsedCount > 0 ? ((g.count / parsedCount) * 100).toFixed(1) : '0';
      const barPct = maxGroupCount > 0 ? ((g.count / maxGroupCount) * 100).toFixed(1) : '0';
      const formattedCount = new Intl.NumberFormat('es-ES').format(g.count);
      statusHtml += `
            <div class="chart-bar-item">
                <span class="chart-bar-label">${label}</span>
                <div class="chart-bar-track">
                    <div class="chart-bar-fill ${g.color}" style="width: ${barPct}%;"></div>
                </div>
                <span class="chart-bar-value">${formattedCount} <span style="font-size:0.75rem; color:var(--muted); font-weight:400;">(${pct}%)</span></span>
            </div>
        `;
    });
    document.getElementById('status-codes-bar-container').innerHTML = statusHtml;

    // Renderizar desglose detallado interactivo de códigos HTTP
    renderHttpStatusDesglose(filtered);

    const botCounts = {};
    filtered.forEach(entry => {
      const bot = entry[4];
      if (bot) {
        if (!botCounts[bot]) {
          botCounts[bot] = { total: 0, uas: {}, urls: {} };
        }
        botCounts[bot].total++;
        const ua = entry[8] || 'Desconocido';
        const url = entry[2];
        botCounts[bot].uas[ua] = (botCounts[bot].uas[ua] || 0) + 1;
        botCounts[bot].urls[url] = (botCounts[bot].urls[url] || 0) + 1;
      }
    });

    const sortedBots = Object.entries(botCounts).sort((a, b) => b[1].total - a[1].total).slice(0, 10);
    const maxBotHits = sortedBots.length > 0 ? sortedBots[0][1].total : 0;

    const botsContainer = document.getElementById('bots-bar-container');
    if (botsContainer) {
      if (sortedBots.length === 0) {
        botsContainer.innerHTML = '<p style="font-size: 0.9rem; color: var(--muted); text-align: center; margin-top: 1.5rem;">No se han detectado firmas conocidas de bots en este rango.</p>';
      } else {
        let botsHtml = '';
        sortedBots.forEach(([botName, data]) => {
          const botPct = maxBotHits > 0 ? ((data.total / maxBotHits) * 100).toFixed(1) : '0';
          const pctTotal = parsedCount > 0 ? ((data.total / parsedCount) * 100).toFixed(1) : '0';
          const formattedBotHits = new Intl.NumberFormat('es-ES').format(data.total);

          const sortedUas = Object.entries(data.uas).sort((a, b) => b[1] - a[1]).slice(0, 5);
          const sortedUrls = Object.entries(data.urls).sort((a, b) => b[1] - a[1]).slice(0, 10);

          botsHtml += `
                    <details style="margin-bottom: 0.75rem; border: 1px solid #111111; border-radius: 6px; background: #ffffff;">
                        <summary style="font-weight: 700; cursor: pointer; color: #111111; padding: 0.75rem 1rem; user-select: none; display: flex; align-items: center; outline: none; transition: background 0.15s; font-size: 0.9rem;" onmouseover="this.style.background='#fff9f5'" onmouseout="this.style.background='transparent'">
                            <div style="display: flex; align-items: center; gap: 1rem; flex-grow: 1; min-width: 0;">
                                <span style="width: 140px; font-weight: 700; color: #111111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex-shrink: 0;" title="${escapeHtml(botName)}">${escapeHtml(botName)}</span>
                                <div class="chart-bar-track" style="margin: 0; flex-grow: 1;">
                                    <div class="chart-bar-fill bar-color-bot" style="width: ${botPct}%;"></div>
                                </div>
                                <span class="chart-bar-value" style="width: 110px; font-weight: 700; color: #e8681a; text-align: right; flex-shrink: 0; margin-left: 1rem;">
                                    ${formattedBotHits} <span style="font-size:0.75rem; color:var(--muted); font-weight:400;">(${pctTotal}%)</span>
                                </span>
                            </div>
                            <span style="color: #e8681a; font-size: 0.75rem; margin-left: 1.5rem; font-weight: 800; flex-shrink: 0;">▼ Ver detalles</span>
                        </summary>
                        <div style="padding: 1.25rem; border-top: 1px solid #111111; background: #fafafa; border-radius: 0 0 6px 6px;">
                            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                                <!-- Columna de URLs visitadas -->
                                <div style="flex: 1; min-width: 280px;">
                                    <h5 style="margin: 0 0 0.75rem 0; font-size: 0.8rem; font-weight: 700; color: #111111; display: flex; align-items: center; gap: 0.25rem;">
                                        <span>🔗</span> Top 10 URLs visitadas por este Crawler:
                                    </h5>
                                    <div style="overflow-x: auto;">
                                        <table style="width: 100%; border-collapse: collapse; font-size: 0.75rem; background: #ffffff; border: 1px solid #eeeeee;">
                                            <thead>
                                                <tr style="background: #111111; color: #ffffff;">
                                                    <th style="padding: 0.4rem 0.5rem; text-align: left;">URL</th>
                                                    <th style="padding: 0.4rem 0.5rem; text-align: right; width: 80px;">Hits</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${sortedUrls.map(([u, h]) => `
                                                    <tr style="border-bottom: 1px solid #eeeeee;">
                                                        <td style="padding: 0.4rem 0.5rem; font-family: monospace; color: #e8681a; word-break: break-all;">${escapeHtml(u)}</td>
                                                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-weight: 700; color: #111111;">${new Intl.NumberFormat('es-ES').format(h)}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- Columna de User Agents -->
                                <div style="flex: 1; min-width: 280px;">
                                    <h5 style="margin: 0 0 0.75rem 0; font-size: 0.8rem; font-weight: 700; color: #111111; display: flex; align-items: center; gap: 0.25rem;">
                                        <span>🖥️</span> User Agents detectados bajo esta firma:
                                    </h5>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        ${sortedUas.map(([ua, h]) => `
                                            <div style="padding: 0.6rem; background: #ffffff; border: 1px solid #eeeeee; border-radius: 4px; font-size: 0.75rem;">
                                                <div style="font-family: monospace; word-break: break-all; color: #4b5563; line-height: 1.4;">${escapeHtml(ua)}</div>
                                                <div style="margin-top: 0.35rem; font-weight: 700; color: #e8681a; text-align: right;">${new Intl.NumberFormat('es-ES').format(h)} peticiones</div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>
                `;
        });
        botsContainer.innerHTML = botsHtml;
      }
    }

    const ipCounts = {};
    const urlCounts = {};
    const urlNoStaticCounts = {};
    const error404Counts = {};

    filtered.forEach(entry => {
      const ip = entry[0];
      const url = entry[2];
      const status = entry[3];
      const isStatic = entry[5] === 1;

      ipCounts[ip] = (ipCounts[ip] || 0) + 1;
      urlCounts[url] = (urlCounts[url] || 0) + 1;
      if (!isStatic) {
        urlNoStaticCounts[url] = (urlNoStaticCounts[url] || 0) + 1;
      }
      if (status === 404) {
        error404Counts[url] = (error404Counts[url] || 0) + 1;
      }
    });

    const topUrlsNoStatic = Object.entries(urlNoStaticCounts).sort((a, b) => b[1] - a[1]).slice(0, 10);
    const topUrls = Object.entries(urlCounts).sort((a, b) => b[1] - a[1]).slice(0, 10);
    const top404s = Object.entries(error404Counts).sort((a, b) => b[1] - a[1]).slice(0, 10);
    const topIps = Object.entries(ipCounts).sort((a, b) => b[1] - a[1]).slice(0, 10);

    renderTable('url-no-static-tbody', topUrlsNoStatic, parsedCount, 'url-no-static');
    renderTable('url-tbody', topUrls, parsedCount, 'url');
    renderTable('404-tbody', top404s, parsedCount, '404');
    renderTable('ip-tbody', topIps, parsedCount, 'ip');

    const sortedBotsCompatible = sortedBots.map(([name, data]) => [name, data.total]);
    renderTable('bot-tbody', sortedBotsCompatible, parsedCount, 'bot');

    redrawDonutChart(topUrlsNoStatic.slice(0, 5));
  }

  function resetHourlyFilter() {
    document.getElementById('hourStartSlider').value = 0;
    document.getElementById('hourEndSlider').value = 23;

    const startSelect = document.getElementById('dateStartSelect');
    const endSelect = document.getElementById('dateEndSelect');
    if (startSelect && startSelect.options.length > 0) {
      startSelect.selectedIndex = 0;
    }
    if (endSelect && endSelect.options.length > 0) {
      endSelect.selectedIndex = endSelect.options.length - 1;
    }
    updateHourlyFilter();
  }

  function redrawDonutChart(top5Entries) {
    const canvas = document.getElementById("urlDonutChart");
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    const legendContainer = document.getElementById("donutLegend");
    if (legendContainer) legendContainer.innerHTML = "";

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (top5Entries.length === 0) {
      const parentCard = canvas.closest('div[style*="flex: 0 0 300px"]');
      if (parentCard) parentCard.style.display = 'none';
      return;
    }

    const parentCard = canvas.closest('div[style*="flex: 0 0 300px"]');
    if (parentCard) parentCard.style.display = 'block';

    const totalHits = top5Entries.reduce((acc, [_, val]) => acc + val, 0);
    const colors = ["#e8681a", "#111111", "#4b5563", "#94a3b8", "#cbd5e1"];

    const size = 160;
    const center = size / 2;
    const radius = size / 2 - 5;

    let startAngle = -0.5 * Math.PI;

    top5Entries.forEach(([url, hits], index) => {
      const pct = totalHits > 0 ? ((hits / totalHits) * 100).toFixed(1) : '0';
      const sliceAngle = totalHits > 0 ? (hits / totalHits) * 2 * Math.PI : 0;
      const color = colors[index % colors.length];

      ctx.beginPath();
      ctx.fillStyle = color;
      ctx.moveTo(center, center);
      ctx.arc(center, center, radius, startAngle, startAngle + sliceAngle);
      ctx.closePath();
      ctx.fill();

      startAngle += sliceAngle;

      if (legendContainer) {
        const shortenedUrl = url.length > 22 ? url.substring(0, 20) + '...' : url;
        const item = document.createElement("div");
        item.style.display = "flex";
        item.style.alignItems = "center";
        item.style.gap = "0.5rem";
        item.innerHTML = `
                <span style="display:inline-block; width:8px; height:8px; background:${color}; border-radius:50%; flex-shrink:0;"></span>
                <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex-grow:1; font-weight:600;" title="${url}">${shortenedUrl}</span>
                <span style="font-weight:700; color:#111111; margin-left:auto;">${pct}%</span>
            `;
        legendContainer.appendChild(item);
      }
    });

    ctx.beginPath();
    ctx.fillStyle = "#ffffff";
    ctx.arc(center, center, radius * 0.55, 0, 2 * Math.PI);
    ctx.fill();
  }

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
  document.addEventListener('DOMContentLoaded', function () {
    const dashboard = document.getElementById('dashboard');
    if (dashboard) {
      dashboard.scrollIntoView({ behavior: 'smooth' });
    }

    // Inicializar selectores de fecha y filtros si hay datos
    if (typeof logEntries !== 'undefined' && logEntries.length > 0) {
      populateDateSelectors();
      updateHourlyFilter();
    }

    // Dibujar gráfico circular Donut
    const canvas = document.getElementById("urlDonutChart");
    if (canvas) {
      const rawData = <?php echo isset($result['top_urls_no_static']) ? json_encode(array_slice($result['top_urls_no_static'], 0, 5, true)) : '{}'; ?>;
      redrawDonutChart(Object.entries(rawData));
    }
  });
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>