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

/**
 * Comprueba si una URL es potencialmente insegura o privada (prevención de SSRF)
 */
function is_unsafe_url($url) {
    $parsed = parse_url($url);
    if (!$parsed || empty($parsed['host'])) {
        return true;
    }

    // Permitir exclusivamente esquemas http y https
    $scheme = strtolower($parsed['scheme'] ?? '');
    if ($scheme !== 'http' && $scheme !== 'https') {
        return true;
    }

    // Bloquear puertos no estándar (solo 80 y 443 permitidos)
    $port = $parsed['port'] ?? null;
    if ($port !== null && !in_array((int)$port, [80, 443], true)) {
        return true;
    }

    $host = trim($parsed['host']);
    
    // Eliminar corchetes si es una dirección IPv6 directa
    if (strpos($host, '[') === 0 && substr($host, -1) === ']') {
        $host = substr($host, 1, -1);
    }
    
    // Bloquear localhost y loopback explícitamente
    if (in_array(strtolower($host), ['localhost', 'localhost.localdomain'])) {
        return true;
    }

    $ips = [];
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $ips[] = $host;
    } else {
        // Resolver registros DNS A y AAAA
        $records_a = @dns_get_record($host, DNS_A);
        if (is_array($records_a)) {
            foreach ($records_a as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }
        $records_aaaa = @dns_get_record($host, DNS_AAAA);
        if (is_array($records_aaaa)) {
            foreach ($records_aaaa as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }
        
        // Fallback a gethostbyname para IPv4
        if (empty($ips)) {
            $ip_resolved = gethostbyname($host);
            if ($ip_resolved && $ip_resolved !== $host) {
                $ips[] = $ip_resolved;
            }
        }
    }

    if (empty($ips)) {
        return true; // Si no se puede resolver ninguna IP, bloquear por precaución
    }

    foreach ($ips as $ip) {
        // Verificar si la IP es pública (no privada ni reservada)
        $is_valid_public = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
        if (!$is_valid_public) {
            return true; // IP privada o de rango reservado detectada
        }
    }

    return false;
}

/**
 * Controla el límite de solicitudes por IP (10 solicitudes en 10 minutos) y guarda el log de peticiones.
 */
function check_rate_limit_and_log($url_audited) {
    $log_file = dirname(__DIR__) . '/data/auditor_logs.json';
    $dir = dirname($log_file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    // Ofuscar IP con hash md5 y salt para cumplir con GDPR
    $ip_hash = md5($ip . 'cookie_audit_salt_55');
    $now = time();

    $logs = [];
    if (file_exists($log_file)) {
        $logs = json_decode(@file_get_contents($log_file), true) ?: [];
    }

    $cleaned_logs = [];
    $ip_count = 0;
    $domain_last_request_time = 0;

    $parsed_current = parse_url($url_audited);
    $current_domain = strtolower($parsed_current['host'] ?? '');

    foreach ($logs as $entry) {
        // Conservar histórico de las últimas 24 horas en el log, pero usar 10 min para rate limit
        if ($now - $entry['timestamp'] < 86400) {
            $cleaned_logs[] = $entry;
        }
        
        if ($now - $entry['timestamp'] < 600) {
            if ($entry['ip_hash'] === $ip_hash) {
                $ip_count++;
            }
            
            $parsed_entry = parse_url($entry['url']);
            $entry_domain = strtolower($parsed_entry['host'] ?? '');
            if ($entry_domain === $current_domain && $entry['ip_hash'] === $ip_hash) {
                if ($entry['timestamp'] > $domain_last_request_time) {
                    $domain_last_request_time = $entry['timestamp'];
                }
            }
        }
    }

    // Validar límite de tasa
    if ($ip_count >= 10) {
        return 'Has superado el límite de análisis permitido (10 solicitudes cada 10 minutos). Por favor, espera un momento.';
    }

    // Validar anti-loop de análisis del mismo dominio
    if ($domain_last_request_time > 0 && ($now - $domain_last_request_time) < 10) {
        return 'Por favor, espera al menos 10 segundos antes de volver a analizar el mismo dominio.';
    }

    // Registrar solicitud
    $cleaned_logs[] = [
        'ip_hash'   => $ip_hash,
        'timestamp' => $now,
        'url'       => $url_audited,
        'error'     => ''
    ];

    @file_put_contents($log_file, json_encode($cleaned_logs, JSON_PRETTY_PRINT), LOCK_EX);
    return true;
}

/**
 * Registra un error de conexión técnica en el log para diagnóstico interno
 */
function log_audit_error($url_audited, $error_msg) {
    $log_file = dirname(__DIR__) . '/data/auditor_logs.json';
    if (!file_exists($log_file)) return;

    $logs = json_decode(@file_get_contents($log_file), true) ?: [];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ip_hash = md5($ip . 'cookie_audit_salt_55');

    // Buscar la última petición de esta IP y actualizar el mensaje de error
    for ($i = count($logs) - 1; $i >= 0; $i--) {
        if ($logs[$i]['ip_hash'] === $ip_hash && $logs[$i]['url'] === $url_audited) {
            $logs[$i]['error'] = $error_msg;
            break;
        }
    }

    @file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT), LOCK_EX);
}

$error = null;
$result = null;
$url = '';

// Procesar el análisis si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = trim($_POST['url'] ?? '');
    
    if (empty($url)) {
        $error = 'Por favor, introduce una URL válida.';
    } else {
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $error = 'El formato de la URL no es válido.';
        } elseif (is_unsafe_url($url)) {
            $error = 'La dirección URL introducida está restringida o no es segura.';
        } else {
            // Verificar límite de tasa y registrar log
            $rate_limit_res = check_rate_limit_and_log($url);
            if ($rate_limit_res !== true) {
                $error = $rate_limit_res;
            } else {
            $redirect_chain = [];
            $max_redirects = 5;
            $redirect_count = 0;
            $current_url = $url;
            $html_content = '';
            $cookies_set = [];
            $ssl_invalid_detected = false;
            
            // Guardar cookies a lo largo de la cadena de redirecciones
            while ($redirect_count <= $max_redirects) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $current_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_USERAGENT, 'VictorAlonsoCookieBot/1.0 (+https://www.victor-alonso.es/herramientas/auditor-cookies)');

                // Prevenir DNS Rebinding antes de iniciar la solicitud
                if (is_unsafe_url($current_url)) {
                    $error = 'La dirección URL de destino no es segura y ha sido bloqueada.';
                    curl_close($ch);
                    break;
                }

                $response = curl_exec($ch);
                
                // Reintentar si falla SSL para poder auditar de todas formas
                if (curl_errno($ch) && (curl_errno($ch) == 60 || curl_errno($ch) == 51 || stripos(curl_error($ch), 'ssl') !== false || stripos(curl_error($ch), 'certificate') !== false)) {
                    $ssl_invalid_detected = true;
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    
                    // Revalidar IP del redirect/esquema antes del segundo intento
                    if (is_unsafe_url($current_url)) {
                        $error = 'La dirección URL de destino no es segura y ha sido bloqueada.';
                        curl_close($ch);
                        break;
                    }
                    
                    $response = curl_exec($ch);
                }

                if (curl_errno($ch)) {
                    $raw_err = curl_error($ch);
                    log_audit_error($url, "cURL Error en $current_url: " . $raw_err);
                    $error = 'No se ha podido establecer conexión con la web indicada. Revisa que la URL sea pública y accesible.';
                    curl_close($ch);
                    break;
                }

                $info = curl_getinfo($ch);
                $header_size = $info['header_size'];
                $headers_raw = substr($response, 0, $header_size);
                $html_content = substr($response, $header_size);
                curl_close($ch);

                // Capturar cookies de las cabeceras Set-Cookie
                if (preg_match_all('/^Set-Cookie:\s*([^;=]+)=([^;]*)/mi', $headers_raw, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $c_name = trim($match[1]);
                        $c_value = trim($match[2]);
                        
                        // Clasificar la cookie
                        $category = 'necesaria';
                        $provider = 'Propio (First-party)';
                        $description = 'Cookie técnica de servidor o sesión.';
                        
                        $name_lower = strtolower($c_name);
                        if (strpos($name_lower, '_ga') === 0) {
                            $category = 'analitica';
                            $provider = 'Google Analytics';
                            $description = 'Identificador único de usuario para Google Analytics.';
                        } elseif (strpos($name_lower, '_gid') === 0) {
                            $category = 'analitica';
                            $provider = 'Google Analytics';
                            $description = 'Identificador temporal de usuario para Google Analytics.';
                        } elseif (strpos($name_lower, '_fbp') === 0 || strpos($name_lower, '_fbc') === 0) {
                            $category = 'marketing';
                            $provider = 'Facebook Pixel';
                            $description = 'Seguimiento de conversiones y perfiles de publicidad de Facebook.';
                        } elseif (strpos($name_lower, '_hj') === 0) {
                            $category = 'analitica';
                            $provider = 'Hotjar';
                            $description = 'Persiste el ID del usuario único de Hotjar en el navegador.';
                        } elseif (strpos($name_lower, '_clck') === 0 || strpos($name_lower, '_clsk') === 0) {
                            $category = 'analitica';
                            $provider = 'Microsoft Clarity';
                            $description = 'Estadísticas y grabaciones de navegación de Microsoft.';
                        } elseif (strpos($name_lower, '_uet') === 0) {
                            $category = 'marketing';
                            $provider = 'Bing Ads';
                            $description = 'Seguimiento de publicidad y conversiones de Microsoft Bing.';
                        } elseif (strpos($name_lower, '_tt_enable') === 0) {
                            $category = 'marketing';
                            $provider = 'TikTok Pixel';
                            $description = 'Seguimiento de conversiones de anuncios de TikTok.';
                        }
                        
                        $cookies_set[$c_name] = [
                            'value'       => substr($c_value, 0, 30) . (strlen($c_value) > 30 ? '...' : ''),
                            'category'    => $category,
                            'provider'    => $provider,
                            'description' => $description,
                            'url'         => $current_url
                        ];
                    }
                }

                // Seguir redirecciones 3xx manualmente
                if ($info['http_code'] >= 300 && $info['http_code'] < 400 && preg_match('/^Location:\s*([^\r\n]+)/mi', $headers_raw, $loc_matches)) {
                    $redirect_url = trim($loc_matches[1]);
                    
                    // Resolver rutas relativas si es necesario
                    if (strpos($redirect_url, '/') === 0) {
                        $parsed = parse_url($current_url);
                        $redirect_url = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $redirect_url;
                    }
                    
                    // Validación de seguridad SSRF en la redirección
                    if (is_unsafe_url($redirect_url)) {
                        $error = 'La redirección a la URL destino ha sido bloqueada por motivos de seguridad.';
                        break;
                    }
                    
                    $redirect_chain[] = [
                        'code' => $info['http_code'],
                        'from' => $current_url,
                        'to'   => $redirect_url
                    ];
                    
                    $current_url = $redirect_url;
                    $redirect_count++;
                } else {
                    break;
                }
            }

            if (!$error) {
                // Analizar el HTML en busca de scripts de seguimiento
                $detected_scripts = [];
                
                // Mapeo de patrones de script de seguimiento
                $script_patterns = [
                    'Google Tag Manager' => [
                        'pattern' => '/googletagmanager\.com\/gtm\.js/i',
                        'desc'    => 'Gestor de etiquetas de Google.'
                    ],
                    'Google Analytics (gtag.js)' => [
                        'pattern' => '/googletagmanager\.com\/gtag\/js/i',
                        'desc'    => 'Script principal de medición de Google Analytics 4.'
                    ],
                    'Google Analytics (analytics.js legacy)' => [
                        'pattern' => '/google-analytics\.com\/analytics\.js/i',
                        'desc'    => 'Versión antigua y obsoleta de Google Analytics.'
                    ],
                    'Facebook Pixel' => [
                        'pattern' => '/connect\.facebook\.net\/[a-z_]+\/fbevents\.js/i',
                        'desc'    => 'Seguimiento publicitario y conversiones de Facebook.'
                    ],
                    'Hotjar' => [
                        'pattern' => '/static\.hotjar\.com\/c\/hotjar-/i',
                        'desc'    => 'Mapas de calor y grabación de sesiones de usuario.'
                    ],
                    'Microsoft Clarity' => [
                        'pattern' => '/clarity\.ms\/tag/i',
                        'desc'    => 'Herramienta de análisis del comportamiento del usuario de Microsoft.'
                    ],
                    'TikTok Pixel' => [
                        'pattern' => '/analytics\.tiktok\.com\/i18n\/pixel/i',
                        'desc'    => 'Píxel de seguimiento publicitario para anuncios en TikTok.'
                    ],
                    'Google AdSense' => [
                        'pattern' => '/pagead2\.googlesyndication\.com\/pagead\/js\/adsbygoogle\.js/i',
                        'desc'    => 'Red de anuncios contextuales de Google.'
                    ],
                    'Matomo / Piwik' => [
                        'pattern' => '/(piwik|matomo)\.js/i',
                        'desc'    => 'Plataforma auto-alojada de analítica de privacidad.'
                    ]
                ];

                // Extraer todos los tags <script>
                if (preg_match_all('/<script\b([^>]*)>(.*?)<\/script>/is', $html_content, $script_tags, PREG_SET_ORDER)) {
                    foreach ($script_tags as $tag) {
                        $attrs = $tag[1];
                        $inner = $tag[2];
                        
                        // Buscar src en los atributos
                        $src = '';
                        if (preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $src_match)) {
                            $src = $src_match[1];
                        }
                        
                        // Verificar patrones
                        foreach ($script_patterns as $name => $info) {
                            $matches_pattern = false;
                            if ($src && preg_match($info['pattern'], $src)) {
                                $matches_pattern = true;
                            } elseif (preg_match($info['pattern'], $inner)) {
                                $matches_pattern = true;
                            }

                            if ($matches_pattern) {
                                // Determinar si está bloqueado/configurado con tipo text/plain (para consentimiento dinámico)
                                $is_blocked = false;
                                if (preg_match('/type=["\']text\/plain["\']/i', $attrs)) {
                                    $is_blocked = true;
                                }
                                
                                $detected_scripts[$name] = [
                                    'name'       => $name,
                                    'src'        => $src ? substr($src, 0, 60) . (strlen($src) > 60 ? '...' : '') : 'Código en línea (Inline)',
                                    'blocked'    => $is_blocked,
                                    'desc'       => $info['desc']
                                ];
                            }
                        }
                    }
                }

                // Analizar la presencia de enlaces a las páginas legales en el HTML
                $legal_pages = [
                    'Política de Cookies' => [
                        'pattern' => '/href=["\'][^"\']*(cookie)[^"\']*["\']/i',
                        'found'   => false
                    ],
                    'Política de Privacidad' => [
                        'pattern' => '/href=["\'][^"\']*(privac|privacy|proteccion-datos|proteccion-de-datos)[^"\']*["\']/i',
                        'found'   => false
                    ],
                    'Aviso Legal' => [
                        'pattern' => '/href=["\'][^"\']*(legal|condiciones|terminos)[^"\']*["\']/i',
                        'found'   => false
                    ]
                ];

                foreach ($legal_pages as $page_name => &$p_info) {
                    if (preg_match($p_info['pattern'], $html_content)) {
                        $p_info['found'] = true;
                    }
                }
                unset($p_info);

                // 1. Detección de Banners y CMPs
                $banner_patterns = [
                    'Cookiebot' => '/(cookiebot\.com|id="cookiebanner"|id="CybotCookiebotDialog")/i',
                    'CookieYes' => '/(cookieyes\.com|id="cookieyes-banner"|class="[^"]*cookieyes-consent[^"]*")/i',
                    'Complianz' => '/(complianz\.io|class="[^"]*cmplz-cookiebanner[^"]*")/i',
                    'OneTrust' => '/(onetrust\.com|id="onetrust-banner-sdk"|class="[^"]*onetrust-consent[^"]*")/i',
                    'Didomi' => '/(didomi\.io|id="didomi-host"|class="[^"]*didomi-popup[^"]*")/i',
                    'Iubenda' => '/(iubenda\.com|id="iubenda-cs-banner"|class="[^"]*iubenda-cs[^"]*")/i',
                    'Axeptio' => '/(axeptio\.eu|id="axeptio_overlay"|class="[^"]*axeptio[^"]*")/i',
                    'Osano' => '/(osano\.com|class="[^"]*osano-cookie[^"]*")/i',
                    'Termly' => '/(termly\.io|id="termly-consent[^"]*")/i',
                    'Quantcast Choice' => '/(quantcast\.mgr\.consensu\.org|id="qc-cmp2-container")/i'
                ];

                $detected_cmp = 'Desconocido o personalizado';
                $banner_detected = false;

                foreach ($banner_patterns as $cmp_name => $cmp_pattern) {
                    if (preg_match($cmp_pattern, $html_content)) {
                        $detected_cmp = $cmp_name;
                        $banner_detected = true;
                    }
                }

                // Si no se ha detectado un CMP de marca pero el HTML contiene clases comunes de banners genéricos
                if (!$banner_detected) {
                    $generic_banner_patterns = [
                        '/class="[^"]*(cookie-banner|cookie-consent|cc-window|cc-banner)[^"]*"/i',
                        '/id="[^"]*(cookie-banner|cookie-consent|cc-window|cc-banner)[^"]*"/i'
                    ];
                    foreach ($generic_banner_patterns as $g_pat) {
                        if (preg_match($g_pat, $html_content)) {
                            $banner_detected = true;
                            break;
                        }
                    }
                }

                // Detección de Google Consent Mode / Parámetros
                $consent_mode_detected = false;
                $consent_mode_patterns = [
                    '/gtag\([\'"]consent[\'"]/i',
                    '/gcd=/i',
                    '/gcs=/i',
                    '/data-cookiecategory=/i',
                    '/data-consent=/i',
                    '/data-category=/i',
                    '/CookieConsent/i'
                ];
                foreach ($consent_mode_patterns as $cm_pat) {
                    if (preg_match($cm_pat, $html_content)) {
                        $consent_mode_detected = true;
                        break;
                    }
                }

                // Detección de botones de Aceptar / Rechazar aproximados
                $accept_button_detected = false;
                $reject_button_detected = false;

                // Patrones comunes para botones de consentimiento
                $accept_patterns = [
                    '/accept-cookies/i', '/btn-accept/i', '/cookie-accept/i', '/cky-btn-accept/i',
                    '/cmplz-accept/i', '/>\s*(Aceptar todo|Aceptar|Entendido|Permitir todas)\s*</i'
                ];
                $reject_patterns = [
                    '/reject-cookies/i', '/btn-reject/i', '/cookie-reject/i', '/cky-btn-reject/i',
                    '/cmplz-deny/i', '/>\s*(Rechazar todo|Rechazar|Denegar|Solo necesarias)\s*</i'
                ];

                foreach ($accept_patterns as $a_pat) {
                    if (preg_match($a_pat, $html_content)) {
                        $accept_button_detected = true;
                        break;
                    }
                }
                foreach ($reject_patterns as $r_pat) {
                    if (preg_match($r_pat, $html_content)) {
                        $reject_button_detected = true;
                        break;
                    }
                }

                // 2. Extracción de iframes de terceros
                $detected_iframes = [];
                $iframe_providers = [
                    'YouTube' => [
                        'pattern' => '/(youtube\.com|youtu\.be)/i',
                        'desc'    => 'Reproductor de vídeo incrustado de YouTube. Deposita cookies publicitarias y de seguimiento de Google.',
                        'severity' => 'Alto'
                    ],
                    'Google Maps' => [
                        'pattern' => '/(google\.com\/maps|google\.es\/maps|maps\.google)/i',
                        'desc'    => 'Mapas interactivos de Google. Instala cookies de tracking de preferencias y geolocalización.',
                        'severity' => 'Alto'
                    ],
                    'reCAPTCHA' => [
                        'pattern' => '/(google\.com\/recaptcha|recaptcha\.net)/i',
                        'desc'    => 'Sistema de seguridad anti-bot de Google. Rastrea el comportamiento del usuario con fines publicitarios y analíticos.',
                        'severity' => 'Alto'
                    ],
                    'Vimeo' => [
                        'pattern' => '/player\.vimeo\.com/i',
                        'desc'    => 'Reproductor de vídeo incrustado de Vimeo. Genera cookies de estadísticas y preferencias.',
                        'severity' => 'Alto'
                    ],
                    'Calendly' => [
                        'pattern' => '/calendly\.com/i',
                        'desc'    => 'Widget para agendar reuniones de Calendly. Utiliza cookies analíticas propias y de terceros.',
                        'severity' => 'Medio'
                    ],
                    'Facebook' => [
                        'pattern' => '/facebook\.com\/plugins/i',
                        'desc'    => 'Plugins sociales de Facebook (como botones de Me gusta o feeds). Realiza tracking activo de usuarios.',
                        'severity' => 'Alto'
                    ],
                    'Instagram' => [
                        'pattern' => '/instagram\.com\/embed/i',
                        'desc'    => 'Publicaciones o feeds integrados de Instagram. Rastrea hábitos del usuario.',
                        'severity' => 'Alto'
                    ],
                    'TikTok' => [
                        'pattern' => '/tiktok\.com\/embed/i',
                        'desc'    => 'Vídeos integrados de TikTok. Recopila identificadores y datos de navegación.',
                        'severity' => 'Alto'
                    ],
                    'Twitter / X' => [
                        'pattern' => '/platform\.twitter\.com/i',
                        'desc'    => 'Widgets de publicaciones integradas de Twitter/X. Almacena cookies de seguimiento social.',
                        'severity' => 'Alto'
                    ]
                ];

                if (preg_match_all('/<iframe\b([^>]*)>/is', $html_content, $iframe_tags, PREG_SET_ORDER)) {
                    foreach ($iframe_tags as $iframe) {
                        $attrs = $iframe[1];
                        $src = '';
                        if (preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $src_match)) {
                            $src = $src_match[1];
                        }
                        
                        foreach ($iframe_providers as $prov_name => $prov_info) {
                            if ($src && preg_match($prov_info['pattern'], $src)) {
                                $detected_iframes[$prov_name] = [
                                    'name'     => $prov_name,
                                    'src'      => substr($src, 0, 60) . (strlen($src) > 60 ? '...' : ''),
                                    'severity' => $prov_info['severity'],
                                    'desc'     => $prov_info['desc']
                                ];
                            }
                        }
                    }
                }

                // 3. Peticiones externas por dominio (Script, Link, Img, Iframe)
                $external_requests = [];
                
                // Extraer dominios de scripts
                if (preg_match_all('/<script\b[^>]*src=["\']([^"\']+)["\']/is', $html_content, $scripts_found)) {
                    foreach ($scripts_found[1] as $s_url) {
                        $parsed_u = parse_url($s_url);
                        $d = $parsed_u['host'] ?? '';
                        if ($d) {
                            $external_requests[strtolower($d)][] = 'Script';
                        }
                    }
                }
                
                // Extraer dominios de stylesheets y links de preconnect/prefetch
                if (preg_match_all('/<link\b[^>]*(href|rel)=["\']([^"\']+)["\']/is', $html_content, $links_found, PREG_SET_ORDER)) {
                    foreach ($links_found as $l_item) {
                        $href = '';
                        $rel = '';
                        if (preg_match('/href=["\']([^"\']+)["\']/i', $l_item[0], $href_m)) {
                            $href = $href_m[1];
                        }
                        if (preg_match('/rel=["\']([^"\']+)["\']/i', $l_item[0], $rel_m)) {
                            $rel = $rel_m[1];
                        }
                        $parsed_u = parse_url($href);
                        $d = $parsed_u['host'] ?? '';
                        if ($d) {
                            $type = 'Recurso / Enlace';
                            if (stripos($rel, 'stylesheet') !== false) {
                                $type = 'Estilo (CSS)';
                            } elseif (stripos($rel, 'preconnect') !== false || stripos($rel, 'dns-prefetch') !== false) {
                                $type = 'Preconexión DNS';
                            }
                            $external_requests[strtolower($d)][] = $type;
                        }
                    }
                }
                
                // Extraer dominios de imágenes externas
                if (preg_match_all('/<img\b[^>]*src=["\']([^"\']+)["\']/is', $html_content, $imgs_found)) {
                    foreach ($imgs_found[1] as $img_url) {
                        if (strpos($img_url, 'data:') === 0) continue; // ignorar base64
                        $parsed_u = parse_url($img_url);
                        $d = $parsed_u['host'] ?? '';
                        if ($d) {
                            $external_requests[strtolower($d)][] = 'Imagen';
                        }
                    }
                }

                // Extraer dominios de iframes
                if (preg_match_all('/<iframe\b[^>]*src=["\']([^"\']+)["\']/is', $html_content, $iframes_found)) {
                    foreach ($iframes_found[1] as $ifr_url) {
                        $parsed_u = parse_url($ifr_url);
                        $d = $parsed_u['host'] ?? '';
                        if ($d) {
                            $external_requests[strtolower($d)][] = 'Iframe (Incrustado)';
                        }
                    }
                }

                // Filtrar dominios de primer nivel propios (first-party)
                $own_host = parse_url($url, PHP_URL_HOST) ?? '';
                if ($own_host) {
                    $own_host_parts = explode('.', strtolower($own_host));
                    $own_domain = implode('.', array_slice($own_host_parts, -2)); // ej: victor-alonso.es
                } else {
                    $own_domain = '';
                }

                $formatted_requests = [];
                foreach ($external_requests as $domain => $types) {
                    // Ignorar subdominios de sí mismo
                    if ($own_domain && (strpos($domain, $own_domain) !== false)) {
                        continue;
                    }
                    
                    $types = array_unique($types);
                    
                    // Asignar riesgo de dominio externo
                    $d_risk = 'Medio';
                    $d_name = strtolower($domain);
                    if (strpos($d_name, 'google') !== false || strpos($d_name, 'facebook') !== false || 
                        strpos($d_name, 'doubleclick') !== false || strpos($d_name, 'hotjar') !== false ||
                        strpos($d_name, 'clarity') !== false || strpos($d_name, 'tiktok') !== false ||
                        strpos($d_name, 'cookiebot') !== false || strpos($d_name, 'cookieyes') !== false) {
                        $d_risk = 'Alto';
                    }
                    
                    $formatted_requests[] = [
                        'domain' => $domain,
                        'types'  => implode(', ', $types),
                        'risk'   => $d_risk
                    ];
                }

                // Calcular la puntuación de cumplimiento
                $score = 100;
                $violations = [];
                
                // 1. Cookies no funcionales seteadas en el primer impacto
                $unsafe_cookies_count = 0;
                foreach ($cookies_set as $c_name => $c_data) {
                    if ($c_data['category'] !== 'necesaria') {
                        $unsafe_cookies_count++;
                        $violations[] = "Se ha depositado la cookie de terceros <strong>{$c_name}</strong> ({$c_data['provider']}) antes de que el usuario acepte el banner.";
                    }
                }
                if ($unsafe_cookies_count > 0) {
                    $score -= 35;
                }

                // 2. Scripts de tracking no bloqueados
                $unblocked_scripts_count = 0;
                foreach ($detected_scripts as $s_name => $s_data) {
                    if (!$s_data['blocked']) {
                        $unblocked_scripts_count++;
                        $violations[] = "El script de <strong>{$s_name}</strong> está cargado de forma agresiva y directa en el HTML (no bloqueado en espera de consentimiento).";
                    }
                }
                if ($unblocked_scripts_count > 0) {
                    $score -= 35;
                }

                // 3. Iframes de terceros no bloqueados
                $unblocked_iframes_count = 0;
                foreach ($detected_iframes as $ifr_name => $ifr_data) {
                    $unblocked_iframes_count++;
                    $violations[] = "Se ha detectado un iframe de <strong>{$ifr_name}</strong> que se carga directamente sin bloqueo de consentimiento.";
                }
                if ($unblocked_iframes_count > 0) {
                    $score -= 20;
                }

                // 4. Páginas legales faltantes
                foreach ($legal_pages as $p_name => $p_data) {
                    if (!$p_data['found']) {
                        $score -= 10;
                        $violations[] = "No se detecta enlace a la página de {$p_name} en el menú o pie de página del HTML analizado.";
                    }
                }

                // Asegurar rango de score
                $score = max(0, $score);

                // Clasificar por nota
                if ($score >= 90) {
                    $grade = 'A';
                    $grade_class = 'grade-a';
                    $grade_desc = '¡Apto! Excelente nivel de cumplimiento. No se han detectado cookies ni scripts no consentidos en el primer hit.';
                } elseif ($score >= 70) {
                    $grade = 'B';
                    $grade_class = 'grade-b';
                    $grade_desc = 'Aceptable. Sin scripts de analítica activos por defecto, pero se detecta alguna deficiencia leve.';
                } elseif ($score >= 50) {
                    $grade = 'C';
                    $grade_class = 'grade-c';
                    $grade_desc = 'Alerta de Riesgo. La web carga trackers de forma descuidada o carece de los enlaces obligatorios.';
                } else {
                    $grade = 'F';
                    $grade_class = 'grade-f';
                    $grade_desc = 'Riesgo alto de incumplimiento. Se han detectado cookies o scripts de seguimiento activos antes de consentimiento, lo que podría vulnerar los criterios exigidos por la normativa de protección de datos.';
                }

                $result = [
                    'final_url'        => $current_url,
                    'ssl_invalid'      => $ssl_invalid_detected,
                    'score'            => $score,
                    'grade'            => $grade,
                    'grade_class'      => $grade_class,
                    'grade_desc'       => $grade_desc,
                    'cookies_set'      => $cookies_set,
                    'detected_scripts' => $detected_scripts,
                    'legal_pages'      => $legal_pages,
                    'violations'       => $violations,
                    'redirects'        => $redirect_chain,
                    'detected_cmp'     => $detected_cmp,
                    'banner_detected'  => $banner_detected,
                    'consent_mode'     => $consent_mode_detected,
                    'accept_btn'       => $accept_button_detected,
                    'reject_btn'       => $reject_button_detected,
                    'detected_iframes' => $detected_iframes,
                    'external_requests'=> $formatted_requests
                ];
            }
            }
        }
    }
}

$page = page_config([
    'title'        => 'Auditor de Cookies RGPD Gratis | Comprobar cookies de una web',
    'description'  => 'Audita online si tu web cumple con el RGPD y la AEPD. Detecta cookies de analítica, marketing e iframes cargados antes del consentimiento.',
    'canonical'    => '/herramientas/auditor-cookies/',
    'body_class'   => 'page-herramientas-auditor-cookies',
    'active_nav'   => 'herramientas',
    'breadcrumbs'  => [
        ['label' => 'Herramientas', 'url' => '/herramientas/'],
        ['label' => 'Auditor de Cookies', 'url' => ''],
    ],
    'schema_types' => ['WebApplication', 'FAQPage'],
    'rating_id'    => 'auditor-cookies',
    'faq_items'    => [
        [
            'q' => '¿Cómo funciona el Auditor de Cookies?',
            'a' => 'El auditor realiza peticiones cURL simulando una primera visita sin consentimiento previo. Analiza las cabeceras HTTP Set-Cookie, las etiquetas script incrustadas, iframes de terceros y conexiones externas para diagnosticar qué recursos se cargan antes de que el usuario interactúe con el banner de consentimiento.'
        ],
        [
            'q' => '¿Qué cookies se consideran seguras para cargar antes del consentimiento?',
            'a' => 'Únicamente las cookies estrictamente necesarias para el funcionamiento técnico de la web (como guardar el estado de la sesión o las preferencias de idioma/consentimiento). Las cookies de analítica (Google Analytics, Hotjar) y marketing (Facebook Pixel, TikTok Pixel) requieren consentimiento explícito antes de ser depositadas.'
        ],
        [
            'q' => '¿Por qué mi web tiene un Score bajo si tengo banner de cookies?',
            'a' => 'Muchos banners o CMPs de cookies son meramente estéticos (visuales) pero no bloquean la ejecución técnica real de los scripts en segundo plano. Si las etiquetas de Google Tag Manager o GA4 se cargan directamente en el HTML de tu sitio sin tipo "text/plain" o sin control condicional, seguirán instalando cookies de seguimiento en la primera carga.'
        ],
        [
            'q' => '¿Es obligatorio ofrecer un botón de rechazar cookies?',
            'a' => 'Sí. Conforme a las últimas directrices de la AEPD y el Reglamento General de Protección de Datos (RGPD), las páginas web deben ofrecer la opción de rechazar todas las cookies no necesarias al mismo nivel de visibilidad y con la misma facilidad que la opción de aceptarlas. No se permiten botones escondidos o flujos complejos para el rechazo.'
        ]
    ]
]);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<main id="main">
  <section class="page-hero" aria-labelledby="tool-h1">
    <div class="container">
      <h1 id="tool-h1">Auditor de Cookies <span>RGPD Gratis</span></h1>
      <p class="page-hero-desc">Audita tu web en vivo. Nuestro bot analiza si tu página respeta los estándares del RGPD/LOPDGDD bloqueando correctamente los scripts de seguimiento y las cookies de terceros antes de que el usuario haga clic en aceptar.</p>
    </div>
  </section>

  <style>
    /* Estilos Premium para el Auditor de Cookies */
    .compliance-grade-box {
      background: #0b101c;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2.5rem;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    .grade-circle {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      font-weight: 900;
      margin-bottom: 1.5rem;
      box-shadow: 0 0 25px rgba(255, 255, 255, 0.05);
      position: relative;
    }
    .grade-circle::after {
      content: '';
      position: absolute;
      inset: -4px;
      border-radius: 50%;
      border: 4px solid transparent;
    }
    
    .grade-a .grade-circle {
      color: #2ecc71;
      background: rgba(46, 204, 113, 0.05);
    }
    .grade-a .grade-circle::after { border-color: #2ecc71; }
    
    .grade-b .grade-circle {
      color: #3498db;
      background: rgba(52, 152, 219, 0.05);
    }
    .grade-b .grade-circle::after { border-color: #3498db; }
    
    .grade-c .grade-circle {
      color: #f1c40f;
      background: rgba(241, 196, 15, 0.05);
    }
    .grade-c .grade-circle::after { border-color: #f1c40f; }
    
    .grade-f .grade-circle {
      color: #e74c3c;
      background: rgba(231, 76, 60, 0.05);
    }
    .grade-f .grade-circle::after { border-color: #e74c3c; }

    .result-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 700;
      display: inline-block;
    }
    .badge-success { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
    .badge-warning { background: rgba(241, 196, 15, 0.15); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); }
    .badge-danger { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }

    .violation-list {
      list-style: none;
      padding: 0;
      margin: 1.5rem 0 0 0;
      text-align: left;
      width: 100%;
    }
    .violation-item {
      padding: 0.75rem 1rem 0.75rem 2.25rem;
      border-bottom: 1px solid var(--border);
      font-size: 0.92rem;
      color: #e2e8f0;
      position: relative;
    }
    .violation-item::before {
      content: '✗';
      color: #e74c3c;
      font-weight: bold;
      position: absolute;
      left: 0.75rem;
      top: 0.75rem;
    }
    
    .legal-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      margin: 1.5rem 0;
      width: 100%;
    }
    .legal-card {
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1rem;
      text-align: center;
    }
    
    @media (max-width: 768px) {
      .legal-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <section class="section">
    <div class="container">
      
      <!-- Explicación del Auditor de Cookies -->
      <div style="max-width:800px; margin:0 auto 2.5rem auto; color:#cbd5e1; font-size:0.95rem; line-height:1.6;">
        <p style="margin-bottom:1rem; font-weight:600; color:#fff; text-align:center;">¿Qué comprueba esta herramienta online?</p>
        <ul style="padding-left:0; display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1rem; margin-bottom:1.5rem; list-style-type:none;">
          <li style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">🔍 <strong>Inyección de Cookies:</strong> Analiza si se depositan cookies no técnicas en la primera carga sin consentimiento.</li>
          <li style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">🚀 <strong>Bloqueo de Scripts:</strong> Verifica si etiquetas como Google Tag Manager, GA4 o Facebook Pixel se ejecutan directamente.</li>
          <li style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">📺 <strong>Iframes de Terceros:</strong> Detecta la carga de reproductores de vídeo, mapas o widgets antes de su aceptación.</li>
          <li style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">🛡️ <strong>Estructura Legal:</strong> Comprueba la presencia de enlaces visibles a la Política de Cookies, Privacidad y Aviso Legal.</li>
        </ul>
      </div>

      <!-- Formulario de Entrada -->
      <div class="card card--dark" style="margin-bottom:3rem;">
        <form method="POST" action="" style="display:flex; flex-direction:column; gap:1.25rem;">
          <div>
            <label for="url" style="display:block; margin-bottom:0.5rem; font-weight:600; color:#fff;">Dirección URL a auditar</label>
            <input type="text" id="url" name="url" placeholder="https://miweb.com" value="<?= h($url) ?>" required style="width:100%; padding:0.85rem 1rem; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:6px; color:#fff; font-size:1rem; outline:none; transition:border-color 0.3s;" onfocus="this.style.borderColor='var(--orange)'" onblur="this.style.borderColor='var(--border)'">
          </div>
          
          <?php if ($error): ?>
            <div style="background:rgba(231,76,60,0.1); border:1px solid rgba(231,76,60,0.3); color:#e74c3c; padding:0.75rem 1rem; border-radius:6px; font-size:0.9rem;">
              ⚠️ <?= h($error) ?>
            </div>
          <?php endif; ?>
          
          <button type="submit" class="btn btn--primary" style="align-self:flex-start; padding:0.85rem 2rem;">Iniciar Auditoría RGPD</button>
        </form>
      </div>

      <!-- Descargo de Responsabilidad (Disclaimer) -->
      <div style="background: rgba(255,255,255,0.01); border: 1px dashed rgba(255,255,255,0.08); padding: 1.25rem; border-radius: 8px; font-size: 0.82rem; color: #94a3b8; margin-bottom: 3rem; line-height: 1.6;">
        <strong style="color: #cbd5e1; display: block; margin-bottom: 0.25rem;">⚖️ Descargo de responsabilidad (Disclaimer)</strong>
        Esta herramienta se ofrece de forma gratuita y con fines meramente orientativos, didácticos y de auditoría técnica. El análisis automatizado se basa en la simulación de carga y en la detección de cookies comunes en cabeceras HTTP y scripts en el HTML inicial. Puesto que existen inyecciones dinámicas complejas y cookies de comportamiento que escapan al análisis estático, la herramienta podría ofrecer falsos positivos o negativos. <strong>Este informe no constituye, en ningún caso, asesoramiento legal ni formal</strong>. El propietario de esta web queda eximido de cualquier responsabilidad ante reclamaciones, inspecciones o sanciones impuestas por la AEPD (Agencia Española de Protección de Datos) u otras autoridades de control relativas al estado de cumplimiento del sitio analizado. Para una auditoría legal vinculante, consulta con un profesional del derecho digital o una asesoría jurídica especializada.
      </div>

      <!-- Resultados del Análisis -->
      <?php if ($result): ?>
        <div class="result-container" style="display:grid; grid-template-columns: 350px 1fr; gap:2.5rem; margin-bottom:4rem;">
          
          <!-- Columna Lateral: Nota Global -->
          <div class="compliance-grade-box <?= $result['grade_class'] ?>">
            <span class="section-label" style="margin-bottom: 1rem;">Nivel de Cumplimiento</span>
            <div class="grade-circle"><?= $result['grade'] ?></div>
            <h2 style="font-size:1.6rem; color:#fff; margin-bottom:0.5rem;">Puntuación: <?= $result['score'] ?>/100</h2>
            <p style="font-size:0.9rem; color:#94a3b8; line-height:1.5;"><?= h($result['grade_desc']) ?></p>
            
            <?php if ($result['ssl_invalid']): ?>
              <div style="background:rgba(241,196,15,0.1); border:1px solid rgba(241,196,15,0.3); color:#f1c40f; padding:0.75rem; border-radius:6px; font-size:0.8rem; margin-top:1.5rem; text-align:left;">
                ⚠️ <strong>Aviso:</strong> El certificado SSL de esta web no es válido o ha expirado. Hemos procedido con el análisis desactivando la comprobación estricta de SSL.
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Columna Principal: Informe Técnico -->
          <div style="display:flex; flex-direction:column; gap:2rem;">
            
            <!-- Listado de Violaciones Detectadas -->
            <div class="card card--dark" style="padding:2rem;">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Infracciones y Advertencias</h3>
              <?php if (empty($result['violations'])): ?>
                <div style="background:rgba(46,204,113,0.1); border:1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:0.85rem 1rem; border-radius:6px; font-size:0.92rem;">
                  ✓ ¡Impecable! No se han detectado infracciones de cookies ni scripts cargados directamente. Tu web está bien configurada.
                </div>
              <?php else: ?>
                <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:0.5rem;">Se han detectado los siguientes riesgos legales que podrían ser objeto de sanción por la AEPD (Agencia Española de Protección de Datos):</p>
                <ul class="violation-list">
                  <?php foreach ($result['violations'] as $violation): ?>
                    <li class="violation-item"><?= strip_tags($violation, '<strong><code>') ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>

            <!-- Diagnóstico Técnico Interpretado -->
            <div class="card card--dark" style="padding:2rem; border-left:4px solid var(--orange);">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Diagnóstico Técnico Interpretado</h3>
              
              <div style="font-size:0.95rem; line-height:1.6; color:#e2e8f0; display:flex; flex-direction:column; gap:1.25rem;">
                <?php if (empty($result['violations'])): ?>
                  <p>🟢 <strong>Prioridad de arreglo: Baja.</strong> Tu sitio web cumple correctamente con las directrices de la AEPD en cuanto al bloqueo previo de cookies y scripts comerciales. No se requiere ninguna acción correctiva urgente.</p>
                <?php else: ?>
                  <div>
                    <strong style="color:#fff;">Prioridad de arreglo:</strong> 
                    <?php if ($result['score'] < 50): ?>
                      <span class="result-badge badge-danger">Alta</span>
                      <p style="margin-top:0.5rem; color:#94a3b8; font-size:0.9rem;">Se están depositando cookies de terceros o cargando herramientas de seguimiento (como Analytics o píxeles) inmediatamente al entrar al sitio. Esto expone a la web a riesgos de cumplimiento normativo.</p>
                    <?php else: ?>
                      <span class="result-badge badge-warning">Media</span>
                      <p style="margin-top:0.5rem; color:#94a3b8; font-size:0.9rem;">El sitio web bloquea parte de los trackers, pero se han detectado deficiencias de configuración o la ausencia de páginas de políticas legales obligatorias.</p>
                    <?php endif; ?>
                  </div>

                  <?php 
                  $has_cookie_violation = false;
                  $has_script_violation = false;
                  $has_legal_violation = false;
                  foreach ($result['violations'] as $violation) {
                      if (stripos($violation, 'cookie') !== false) {
                          $has_cookie_violation = true;
                      }
                      if (stripos($violation, 'script') !== false) {
                          $has_script_violation = true;
                      }
                      if (stripos($violation, 'enlace') !== false || stripos($violation, 'página') !== false) {
                          $has_legal_violation = true;
                      }
                  }
                  ?>

                  <?php if ($has_cookie_violation || $has_script_violation || $has_legal_violation): ?>
                    <div style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:6px; border:1px solid rgba(255,255,255,0.05);">
                      <strong style="color:#fff; display:block; margin-bottom:0.5rem;">📝 Recomendación del Auditor:</strong>
                      <ul style="margin:0; padding-left:1.25rem; font-size:0.9rem; color:#cbd5e1; display:flex; flex-direction:column; gap:0.5rem;">
                        <?php if ($has_cookie_violation): ?>
                          <li><strong>Fuga de cookies antes del consentimiento:</strong> Esto suele ocurrir cuando Google Analytics 4 u otros trackers están pegados directamente en el código de la cabecera (<code>&lt;head&gt;</code>) de la página sin ningún tipo de condicionalidad, o si se utiliza un plugin de cookies visual (que muestra el aviso pero no realiza el bloqueo técnico).</li>
                        <?php endif; ?>
                        <?php if ($has_script_violation): ?>
                          <li><strong>Etiquetas de script no bloqueadas:</strong> Si utilizas WordPress, te recomendamos revisar si herramientas como Site Kit, Complianz, CookieYes o el propio tema están duplicando etiquetas o cargándolas directamente sin el filtrado obligatorio (como el tipo <code>type="text/plain"</code>).</li>
                        <?php endif; ?>
                        <?php if ($has_legal_violation): ?>
                          <li><strong>Enlaces legales ausentes:</strong> Es recomendable por la normativa aplicable que los enlaces permanentes al Aviso Legal, Política de Privacidad y Política de Cookies estén visibles y accesibles desde todas las páginas de tu web (habitualmente en el pie de página).</li>
                        <?php endif; ?>
                      </ul>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>

            <!-- Estado del Banner y CMP Detectado -->
            <div class="card card--dark" style="padding:2rem;">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Estado de Gestión de Consentimiento (Banner / CMP)</h3>
              <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:1.5rem;">Análisis automatizado de la presencia de herramientas para la obtención del consentimiento y su configuración:</p>
              
              <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem;">
                <div style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:6px; border:1px solid rgba(255,255,255,0.05);">
                  <span style="font-size:0.8rem; color:#64748b; display:block; margin-bottom:0.25rem;">Gestor detectado (CMP)</span>
                  <strong style="color:#fff; font-size:1.05rem;"><?= h($result['detected_cmp']) ?></strong>
                </div>

                <div style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:6px; border:1px solid rgba(255,255,255,0.05);">
                  <span style="font-size:0.8rem; color:#64748b; display:block; margin-bottom:0.25rem;">Presencia de Banner</span>
                  <strong style="color:#fff; font-size:1.05rem;">
                    <?= $result['banner_detected'] ? '🟢 Detectado' : '🔴 No detectado en el HTML' ?>
                  </strong>
                </div>

                <div style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:6px; border:1px solid rgba(255,255,255,0.05);">
                  <span style="font-size:0.8rem; color:#64748b; display:block; margin-bottom:0.25rem;">Google Consent Mode</span>
                  <strong style="color:#fff; font-size:1.05rem;">
                    <?= $result['consent_mode'] ? '🟢 Configurado' : '⚪ Sin indicios en el HTML' ?>
                  </strong>
                </div>
              </div>

              <div style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.05); font-size:0.88rem; color:#94a3b8; display:flex; flex-direction:column; gap:0.5rem;">
                <div>
                  <strong>¿Opción de aceptación explícita?:</strong> 
                  <?= $result['accept_btn'] ? '🟢 Detectada (Botón de aceptación aproximado encontrado)' : '⚠️ No se ha detectado claramente un botón con texto de aceptar. Revisa visualmente.' ?>
                </div>
                <div>
                  <strong>¿Opción de rechazo al mismo nivel?:</strong> 
                  <?= $result['reject_btn'] ? '🟢 Detectada (Botón de rechazo aproximado encontrado)' : '🔴 No se detecta un botón con texto claro para rechazar. La AEPD exige ofrecer el botón de rechazar al mismo nivel de visibilidad que el de aceptar.' ?>
                </div>
              </div>
            </div>

            <!-- Menú Legal / Enlaces obligatorios -->
            <div class="card card--dark" style="padding:2rem;">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Presencia de Páginas Legales Obligatorias</h3>
              <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:1rem;">Toda web corporativa o que recopile analíticas debe enlazar de manera visible estas políticas:</p>
              <div class="legal-grid">
                <?php foreach ($result['legal_pages'] as $name => $data): ?>
                  <div class="legal-card">
                    <div style="font-size:1.25rem; margin-bottom:0.35rem;"><?= $data['found'] ? '🟢' : '🔴' ?></div>
                    <strong style="color:#fff; font-size:0.9rem; display:block;"><?= h($name) ?></strong>
                    <span style="font-size:0.8rem; color:#64748b;"><?= $data['found'] ? 'Enlace detectado' : 'No encontrado' ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <?php if (!empty($result['detected_iframes'])): ?>
              <!-- Iframes de Terceros Detectados -->
              <div class="card card--dark" style="padding:2rem;">
                <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Incrustados (Iframes) de Terceros</h3>
                <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:1rem;">Elementos embebidos en el HTML inicial que conectan con servicios externos. Deben estar bloqueados hasta obtener el consentimiento:</p>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:#fff;">
                        <th style="padding:0.75rem 0.5rem;">Servicio</th>
                        <th style="padding:0.75rem 0.5rem;">Dirección URL del Iframe</th>
                        <th style="padding:0.75rem 0.5rem;">Riesgo RGPD</th>
                        <th style="padding:0.75rem 0.5rem;">Impacto en Privacidad</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['detected_iframes'] as $ifr): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05); color:#cbd5e1;">
                          <td style="padding:0.75rem 0.5rem;"><strong><?= h($ifr['name']) ?></strong></td>
                          <td style="padding:0.75rem 0.5rem; font-family:monospace; font-size:0.8rem; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= h($ifr['src']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <span class="result-badge <?= $ifr['severity'] === 'Alto' ? 'badge-danger' : 'badge-warning' ?>">
                              <?= h($ifr['severity']) ?>
                            </span>
                          </td>
                          <td style="padding:0.75rem 0.5rem; font-size:0.82rem; color:#94a3b8;"><?= h($ifr['desc']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($result['external_requests'])): ?>
              <!-- Peticiones de Red a Dominios Externos -->
              <div class="card card--dark" style="padding:2rem;">
                <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Conexiones a Dominios de Terceros</h3>
                <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:1rem;">Dominios externos a los que el navegador realiza peticiones durante la carga del HTML inicial (scripts, CSS, fuentes, imágenes, preconexiones, etc.):</p>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:#fff;">
                        <th style="padding:0.75rem 0.5rem;">Dominio de Tercero</th>
                        <th style="padding:0.75rem 0.5rem;">Tipos de Recursos</th>
                        <th style="padding:0.75rem 0.5rem;">Riesgo de Privacidad</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['external_requests'] as $req): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05); color:#cbd5e1;">
                          <td style="padding:0.75rem 0.5rem; font-family:monospace;"><strong><?= h($req['domain']) ?></strong></td>
                          <td style="padding:0.75rem 0.5rem; font-size:0.82rem; color:#94a3b8;"><?= h($req['types']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <span class="result-badge <?= $req['risk'] === 'Alto' ? 'badge-danger' : 'badge-warning' ?>">
                              <?= h($req['risk']) ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>

            <!-- Tabla de Cookies de 1er Impacto -->
            <div class="card card--dark" style="padding:2rem;">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Cookies detectadas en cabeceras HTTP durante la carga inicial</h3>
              <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:1rem;">Las cookies que se envían en las cabeceras HTTP de respuesta inmediatamente al abrir la URL antes de pulsar ningún botón de banner:</p>
              
              <?php if (empty($result['cookies_set'])): ?>
                <div style="background:rgba(46,204,113,0.05); border:1px solid rgba(46,204,113,0.1); color:#2ecc71; padding:0.85rem 1rem; border-radius:6px; font-size:0.9rem;">
                  ✓ No se han detectado cookies en las cabeceras HTTP durante la carga inicial.
                </div>
              <?php else: ?>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:#fff;">
                        <th style="padding:0.75rem 0.5rem;">Nombre</th>
                        <th style="padding:0.75rem 0.5rem;">Proveedor</th>
                        <th style="padding:0.75rem 0.5rem;">Propósito</th>
                        <th style="padding:0.75rem 0.5rem;">Tipo / Severidad</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['cookies_set'] as $name => $data): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                          <td style="padding:0.75rem 0.5rem; font-weight:700; color:#fff;"><?= h($name) ?></td>
                          <td style="padding:0.75rem 0.5rem;"><?= h($data['provider']) ?></td>
                          <td style="padding:0.75rem 0.5rem; color:#94a3b8;"><?= h($data['description']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <?php if ($data['category'] === 'necesaria'): ?>
                              <span class="result-badge badge-success">Necesaria / Técnica</span>
                            <?php else: ?>
                              <span class="result-badge badge-danger">No Consentida (Incumple)</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

            <!-- Tabla de Scripts Analizados -->
            <div class="card card--dark" style="padding:2rem;">
              <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Scripts de Seguimiento en HTML</h3>
              <p style="font-size:0.92rem; color:#94a3b8; margin-bottom:1rem;">Scripts de terceros detectados en el código de la web y su estado de bloqueo:</p>
              
              <?php if (empty($result['detected_scripts'])): ?>
                <div style="background:rgba(46,204,113,0.05); border:1px solid rgba(46,204,113,0.1); color:#2ecc71; padding:0.85rem 1rem; border-radius:6px; font-size:0.9rem;">
                  ✓ No se han encontrado scripts de seguimiento conocidos cargados en el código HTML de esta página.
                </div>
              <?php else: ?>
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.88rem;">
                    <thead>
                      <tr style="border-bottom:1px solid var(--border); color:#fff;">
                        <th style="padding:0.75rem 0.5rem;">Herramienta</th>
                        <th style="padding:0.75rem 0.5rem;">Origen (SRC)</th>
                        <th style="padding:0.75rem 0.5rem;">Estado</th>
                        <th style="padding:0.75rem 0.5rem;">Cumplimiento</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($result['detected_scripts'] as $name => $data): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                          <td style="padding:0.75rem 0.5rem; font-weight:700; color:#fff;"><?= h($name) ?></td>
                          <td style="padding:0.75rem 0.5rem; font-family:monospace; font-size:0.8rem; color:#94a3b8;"><?= h($data['src']) ?></td>
                          <td style="padding:0.75rem 0.5rem;">
                            <?= $data['blocked'] ? 'Bloqueado (tipo text/plain)' : 'Ejecución directa' ?>
                          </td>
                          <td style="padding:0.75rem 0.5rem;">
                            <?php if ($data['blocked']): ?>
                              <span class="result-badge badge-success">Correcto</span>
                            <?php else: ?>
                              <span class="result-badge badge-danger">Infracción</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

            <!-- Cadena de redirecciones si hubo -->
            <?php if (!empty($result['redirects'])): ?>
              <div class="card card--dark" style="padding:2rem;">
                <h3 style="color:#fff; font-size:1.25rem; margin-bottom:1rem; border-left:3px solid var(--orange); padding-left:0.5rem">Cadena de Redireccionamientos Auditados</h3>
                <ol style="margin:0; padding-left:1.25rem; font-size:0.9rem; color:#e2e8f0; display:flex; flex-direction:column; gap:0.5rem;">
                  <?php foreach ($result['redirects'] as $index => $r): ?>
                    <li>
                      <strong>Redirección [<?= $r['code'] ?>]</strong>: 
                      <span style="font-family:monospace; font-size:0.8rem; color:#94a3b8; display:block; margin-top:0.15rem; word-break:break-all;"><?= h($r['from']) ?> → <?= h($r['to']) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ol>
              </div>
            <?php endif; ?>

          </div>
        </div>
      <?php endif; ?>

      <!-- Sección de Criterio Técnico y RGPD -->
      <div class="criterio-section" style="margin-top:4rem">
        <span class="section-label">Desde la trinchera legal</span>
        <h2>El criterio de la AEPD sobre el consentimiento de cookies</h2>
        
        <div class="criterio-grid">
          <div class="criterio-card">
            <h3>El falso banner de "Aceptar" y "Aceptar"</h3>
            <p>Muchos desarrolladores cometen el error de instalar un banner visual y creer que están en regla. Sin embargo, la normativa de cookies europea y española dicta que <strong>el botón de rechazar debe estar al mismo nivel de visibilidad</strong> que el de aceptar.</p>
            <p>Si tu banner obliga al usuario a navegar por 3 pantallas para denegar o carece de botón directo de rechazo en su pantalla inicial, tu web está incumpliendo con las directrices vigentes y se expone a posibles reclamaciones o sanciones administrativas.</p>
          </div>
          
          <div class="criterio-card">
            <h3>La trampa de Google Analytics 4 (GA4)</h3>
            <p>Para la ley europea, un identificador de usuario único (como el ID del parámetro <code>_ga</code> que usa Analytics) es considerado un <strong>dato personal</strong>. Por lo tanto, no se puede almacenar en el navegador ni transmitir a servidores de Google antes de que el usuario lo autorice de forma afirmativa.</p>
            <p>Muchas plantillas de WordPress inyectan GA4 de forma dura en el <code>&lt;head&gt;</code> bloqueando únicamente de manera visual la barra. Esto instala las cookies de Analytics de inmediato al primer milisegundo de entrada, pudiendo dar lugar a reclamaciones o sanciones administrativas.</p>
          </div>

          <div class="criterio-card">
            <h3>Cómo solucionar el bloqueo sin perder WPO</h3>
            <p>Para cumplir al 100%, debes modificar tus etiquetas <code>&lt;script&gt;</code> de marketing y analítica para que carguen con el tipo <code>type="text/plain"</code> y un identificador de categoría. De este modo, el navegador las ignorará por completo durante la carga inicial.</p>
            <p>Cuando el usuario interactúa y acepta tus cookies, un script de consentimiento ligero de código abierto (como <strong><code>vanilla-cookieconsent</code></strong>) reactiva esos scripts en vivo en memoria sin ralentizar la carga inicial de tu servidor.</p>
          </div>
        </div>
      </div>

      <!-- Widget de Votación y Rich Snippet -->
      <?php render_rating_widget('auditor-cookies'); ?>

    </div>
  </section>

  <!-- CTA final -->
  <?php
  $cta = [
    'title'     => '¿Quieres bloquear correctamente tus cookies y evitar sustos con la AEPD?',
    'subtitle'  => 'Corrige tu banner de cookies y tus scripts de tracking.',
    'btn_label' => 'Solucionar mi banner de cookies',
    'btn_href'  => '/contacto/',
    'whatsapp'  => true,
    'variant'   => 'orange',
  ];
  require dirname(__DIR__) . '/includes/cta.php';
  ?>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
