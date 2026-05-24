<?php
/**
 * faq.php — Bloque visual de Preguntas Frecuentes
 * Requiere que $page['faq_items'] esté definido y poblado.
 */
if (!empty($page['faq_items'])): ?>
<section class="section" style="padding-top:2rem; padding-bottom:3rem;">
    <div class="container">
        <h2 style="margin-bottom:1.5rem;">Preguntas Frecuentes (FAQ)</h2>
        <div class="faq-list">
            <?php foreach ($page['faq_items'] as $item): ?>
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <?= h($item['q']) ?>
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="faq-answer"><?= h($item['a']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
