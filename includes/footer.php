<?php
/**
 * footer.php — Pie de página común
 * Requiere config.php cargado.
 */
?>
<footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">

        <div class="footer-brand">
            <a href="/" class="site-logo footer-logo" aria-label="Víctor Alonso SEO — Inicio">
                <span class="logo-name">Víctor Alonso</span><span class="logo-tag">SEO</span>
            </a>
            <p class="footer-tagline">Consultor SEO e ingeniero informático en Albacete.<br>Diagnóstico técnico, estrategia real, implementación.</p>
            <div class="footer-social" aria-label="Redes sociales">
                <a href="<?= h(SITE_LINKEDIN) ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn de Víctor Alonso">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                </a>
                <a href="<?= h(SITE_TWITTER_URL) ?>" target="_blank" rel="noopener noreferrer" aria-label="X/Twitter de Víctor Alonso">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="<?= h(SITE_GITHUB) ?>" target="_blank" rel="noopener noreferrer" aria-label="GitHub de Víctor Alonso">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                </a>
            </div>
        </div>

        <nav class="footer-nav" aria-label="Navegación del pie de página">
            <div class="footer-nav-col">
                <h3 class="footer-nav-title">Servicios SEO</h3>
                <ul role="list">
                    <li><a href="/servicios/seo-albacete.php">SEO en Albacete</a></li>
                    <li><a href="/servicios/seo-espana.php">SEO para España</a></li>
                    <li><a href="/servicios/auditoria-seo.php">Auditoría SEO</a></li>
                    <li><a href="/servicios/seo-tecnico.php">SEO Técnico</a></li>
                </ul>
            </div>
            <div class="footer-nav-col">
                <h3 class="footer-nav-title">WordPress y Desarrollo</h3>
                <ul role="list">
                    <li><a href="/servicios/mantenimiento-wordpress.php">Mantenimiento WordPress</a></li>
                    <li><a href="/servicios/desarrollo-wordpress.php">Desarrollo WordPress</a></li>
                    <li><a href="/servicios/plugins-wordpress.php">Plugins a medida</a></li>
                </ul>
            </div>
            <div class="footer-nav-col">
                <h3 class="footer-nav-title">Información</h3>
                <ul role="list">
                    <li><a href="/sobre-mi.php">Sobre mí</a></li>
                    <li><a href="/casos-reales.php">Casos reales</a></li>
                    <li><a href="/contacto.php">Contacto</a></li>
                    <a href="<?= h(SITE_WALKIRIA) ?>" target="_blank" rel="noopener noreferrer">Walkiria Apps</a>
                </ul>
            </div>
            <div class="footer-nav-col footer-contact">
                <h3 class="footer-nav-title">Contacto</h3>
                <address>
                    <p><strong>Víctor Alonso SEO</strong></p>
                    <p><?= h(SITE_LOCALITY) ?>, España</p>
                    <p><a href="tel:<?= h(SITE_PHONE_RAW) ?>"><?= h(SITE_PHONE) ?></a></p>
                    <p><a href="mailto:<?= h(SITE_EMAIL) ?>"><?= h(SITE_EMAIL) ?></a></p>
                </address>
            </div>
        </nav>

    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Víctor Alonso SEO. Albacete, España.</p>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js" defer></script>
