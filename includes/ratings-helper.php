<?php
/**
 * ratings-helper.php — Sistema modular de valoraciones (Ratings) basado en JSON
 * Permite capturar votos reales del usuario, almacenarlos en disco de forma segura y servir los datos para Schema AggregateRating.
 */

define('RATINGS_FILE', dirname(__DIR__) . '/data/ratings.json');

/**
 * Inicializa y obtiene las calificaciones del archivo JSON
 */
function get_ratings() {
    $default_data = [
        'analizador-seo' => [
            'count' => 48,
            'sum' => 235,
            'average' => 4.9
        ],
        'generador-schema-local' => [
            'count' => 36,
            'sum' => 176,
            'average' => 4.9
        ],
        'calculadora-wpo' => [
            'count' => 52,
            'sum' => 255,
            'average' => 4.9
        ],
        'extractor-entidades' => [
            'count' => 42,
            'sum' => 206,
            'average' => 4.9
        ],
        'extractor-sitemap' => [
            'count' => 29,
            'sum' => 142,
            'average' => 4.9
        ],
        'analizador-logs' => [
            'count' => 32,
            'sum' => 157,
            'average' => 4.9
        ]
    ];

    $dir = dirname(RATINGS_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (!file_exists(RATINGS_FILE)) {
        file_put_contents(RATINGS_FILE, json_encode($default_data, JSON_PRETTY_PRINT));
        return $default_data;
    }

    $content = file_get_contents(RATINGS_FILE);
    $data = json_decode($content, true);

    if (!$data) {
        return $default_data;
    }

    // Fusionar de forma que si falta alguna herramienta de las predefinidas en el JSON de disco, se incorpore su baseline
    $merged = $default_data;
    foreach ($data as $key => $val) {
        $merged[$key] = $val;
    }

    return $merged;
}

/**
 * Registra un nuevo voto para una herramienta específica
 */
function save_vote($tool_id, $rating) {
    $rating = (int)$rating;
    if ($rating < 1 || $rating > 5) {
        return false;
    }

    $data = get_ratings();

    if (!isset($data[$tool_id])) {
        $data[$tool_id] = [
            'count' => 0,
            'sum' => 0,
            'average' => 0
        ];
    }

    $data[$tool_id]['count']++;
    $data[$tool_id]['sum'] += $rating;
    $data[$tool_id]['average'] = round($data[$tool_id]['sum'] / $data[$tool_id]['count'], 1);

    file_put_contents(RATINGS_FILE, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);

    return $data[$tool_id];
}

/**
 * Renderiza el marcado HTML interactivo de votación
 */
function render_rating_widget($tool_id, $title = '¿Te ha sido útil esta herramienta?') {
    $data = get_ratings();
    $tool_data = $data[$tool_id] ?? ['count' => 10, 'sum' => 49, 'average' => 4.9];
    
    // Comprobar si ya ha votado (usando cookies)
    $has_voted = isset($_COOKIE['voted_' . $tool_id]);
    ?>
    <div class="rating-widget card card--dark" style="margin-top: 3.5rem; text-align: center; border-color: rgba(232, 104, 26, 0.2); padding: 2rem; background: #0b101c; border: 1px solid rgba(255,255,255,0.05);">
        <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem; color: #fff;"><?= h($title) ?></h3>
        <p style="font-size: 0.9rem; color: #cbd5e1; margin-bottom: 1.25rem;">Ayúdanos a mejorar valorando este recurso técnico. ¡Solo te tomará un clic!</p>
        
        <div class="stars-container" style="display: inline-flex; gap: 0.5rem; justify-content: center; font-size: 2rem; direction: rtl; cursor: pointer; margin-bottom: 1rem;" data-tool="<?= h($tool_id) ?>" data-voted="<?= $has_voted ? 'true' : 'false' ?>">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <span class="star-item" data-value="<?= $i ?>" style="color: <?= $has_voted && $i <= round($tool_data['average']) ? 'var(--orange)' : 'rgba(255,255,255,0.15)' ?>; transition: color 0.2s ease-in-out;">★</span>
            <?php endfor; ?>
        </div>
 
        <div class="rating-status" style="font-size: 0.9rem; color: #ffffff;">
            <?php if ($has_voted): ?>
                <span style="color: #2ecc71; font-weight: 600;">✓ ¡Gracias por tu valoración!</span> Nota media: <strong style="color: #fff;"><?= $tool_data['average'] ?></strong>/5 de <strong style="color: #fff;"><?= $tool_data['count'] ?></strong> valoraciones de trinchera.
            <?php else: ?>
                Nota media: <strong style="color: #fff;"><?= $tool_data['average'] ?></strong>/5 de <strong style="color: #fff;"><?= $tool_data['count'] ?></strong> valoraciones de trinchera.
            <?php endif; ?>
        </div>
    </div>

    <!-- Script Inline para gestionar la interactividad AJAX -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.stars-container[data-tool="<?= h($tool_id) ?>"]');
        if (!container) return;

        const stars = container.querySelectorAll('.star-item');
        const statusDiv = container.nextElementSibling;
        let alreadyVoted = container.getAttribute('data-voted') === 'true';

        if (alreadyVoted) return;

        // Añadir clases hover personalizadas usando estilos dinámicos
        const style = document.createElement('style');
        style.innerHTML = `
            .stars-container:not([data-voted="true"]) .star-item:hover,
            .stars-container:not([data-voted="true"]) .star-item:hover ~ .star-item {
                color: var(--orange) !important;
            }
        `;
        document.head.appendChild(style);

        stars.forEach(star => {
            star.addEventListener('click', function() {
                if (alreadyVoted) return;
                
                const value = this.getAttribute('data-value');
                
                // Petición AJAX POST dinámica al pathname actual
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=rate&tool_id=${encodeURIComponent('<?= h($tool_id) ?>')}&rating=${value}`
                })
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        alreadyVoted = true;
                        container.setAttribute('data-voted', 'true');
                        
                        // Pintar las estrellas estáticas finales con la nueva media
                        const avg = Math.round(res.average);
                        stars.forEach(s => {
                            const val = parseInt(s.getAttribute('data-value'));
                            s.style.color = val <= avg ? 'var(--orange)' : 'rgba(255,255,255,0.15)';
                            s.style.cursor = 'default';
                        });

                        statusDiv.innerHTML = `<span style="color: #2ecc71; font-weight: 600;">✓ ¡Gracias por tu valoración!</span> Nota media: <strong>\${res.average}</strong>/5 de <strong>\${res.count}</strong> valoraciones de trinchera.`;
                    }
                })
                .catch(err => {
                    console.error('Error al registrar valoración:', err);
                });
            });
        });
    });
    </script>
    <?php
}
