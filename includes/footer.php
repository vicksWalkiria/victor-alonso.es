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
                    <li><a href="/servicios/seo-albacete/">SEO en Albacete</a></li>
                    <li><a href="/servicios/seo-espana/">SEO para España</a></li>
                    <li><a href="/servicios/auditoria-seo/">Auditoría SEO</a></li>
                    <li><a href="/servicios/seo-tecnico/">SEO Técnico</a></li>
                </ul>
            </div>
            <div class="footer-nav-col">
                <h3 class="footer-nav-title">WordPress y Desarrollo</h3>
                <ul role="list">
                    <li><a href="/servicios/mantenimiento-wordpress/">Mantenimiento WordPress</a></li>
                    <li><a href="/servicios/desarrollo-wordpress/">Desarrollo WordPress</a></li>
                    <li><a href="/servicios/plugins-wordpress/">Plugins a medida</a></li>
                </ul>
            </div>
            <div class="footer-nav-col">
                <h3 class="footer-nav-title">Información</h3>
                <ul role="list">
                    <li><a href="/sobre-mi/">Sobre mí</a></li>
                    <li><a href="/casos-reales/">Casos reales</a></li>
                    <li><a href="/herramientas/">Herramientas SEO</a></li>
                    <li><a href="/contacto/">Contacto</a></li>
                    <li><a href="<?= h(SITE_WALKIRIA) ?>" target="_blank" rel="noopener noreferrer">Walkiria Apps</a></li>
                </ul>
            </div>
            <div class="footer-nav-col footer-contact">
                <h3 class="footer-nav-title">Contacto</h3>
                <address>
                    <p><strong>Víctor Alonso SEO</strong></p>
                    <p><?= h(SITE_ADDRESS) ?></p>
                    <p><?= h(SITE_LOCALITY) ?>, España</p>
                    <p><a href="tel:<?= h(SITE_PHONE_RAW) ?>"><?= h(SITE_PHONE) ?></a></p>
                    <p><a href="mailto:<?= h(SITE_EMAIL) ?>"><?= h(SITE_EMAIL) ?></a></p>
                </address>
            </div>
        </nav>

    </div>

    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <p>&copy; <?= date('Y') ?> Víctor Alonso SEO. Albacete, España.</p>
            <nav class="footer-legal" aria-label="Páginas legales">
                <a href="/aviso-legal/">Aviso legal</a>
                <a href="/politica-privacidad/">Privacidad</a>
                <a href="/politica-cookies/">Cookies</a>
            </nav>
        </div>
    </div>
</footer>

<!-- Widget flotante de Contacto cerrable -->
<div id="floating-contact" class="floating-whatsapp" style="bottom: 96px; z-index: 998;" aria-label="Contacto por Correo">
    <button class="floating-whatsapp__close" id="floating-contact-close" aria-label="Cerrar widget de contacto">×</button>
    <a href="/contacto/" class="floating-whatsapp__link" style="background:#E8681A; box-shadow: 0 4px 16px rgba(232, 104, 26, 0.4);" aria-label="Ir a la página de contacto">
        <svg aria-hidden="true" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
        </svg>
    </a>
</div>

<!-- Widget flotante de WhatsApp cerrable -->
<div id="floating-whatsapp" class="floating-whatsapp" aria-label="Chat de WhatsApp">
    <button class="floating-whatsapp__close" id="floating-whatsapp-close" aria-label="Cerrar widget de WhatsApp">×</button>
    <a href="https://wa.me/<?= SITE_PHONE_RAW ?>" target="_blank" rel="noopener noreferrer" class="floating-whatsapp__link" aria-label="Contactar por WhatsApp">
        <svg aria-hidden="true" width="30" height="30" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>
</div>

<!-- Cookie Consent JS -->
<script defer src="https://cdn.jsdelivr.net/npm/vanilla-cookieconsent@3/dist/cookieconsent.umd.js"></script>
<?php
$_cc_init_path = dirname(__DIR__) . '/assets/js/cookie-consent-init.js';
$_cc_init_version = file_exists($_cc_init_path) ? filemtime($_cc_init_path) : time();
?>
<script defer src="/assets/js/cookie-consent-init.js?v=<?= $_cc_init_version ?>"></script>
<script src="/assets/js/main.js" defer></script>
