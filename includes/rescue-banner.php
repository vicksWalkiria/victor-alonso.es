<?php
/**
 * rescue-banner.php — Bloque de intervención técnica urgente / rescate WordPress
 * Diseñado para herramientas técnicas donde los usuarios sufren errores 500, caídas o hackeos.
 */
?>
<aside class="rescue-banner-box" style="margin: 2rem 0; padding: 1.5rem 1.75rem; background: var(--black); color: #F8FAFC; border: 1px solid rgba(200, 75, 9, 0.45); border-radius: var(--radius); box-shadow: var(--shadow);" aria-label="Servicio de rescate técnico urgente">
  <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1.25rem;">
    <div style="max-width:680px;">
      <div style="display:flex; align-items:center; gap:0.55rem; margin-bottom:0.4rem;">
        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FB923C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <strong style="color:#FB923C; font-size:0.9rem; text-transform:uppercase; letter-spacing:0.04em;">¿Tu web está caída, hackeada o arrojando errores 500?</strong>
      </div>
      <p style="margin:0; font-size:0.92rem; color:#CBD5E1; line-height:1.55;">
        Intervengo de urgencia <strong style="color:#FFFFFF;">directamente por SSH</strong>: diagnóstico forense, limpieza de malware con persistencia en WordPress y recuperación de errores críticos de servidor.
      </p>
    </div>
    <div style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
      <a href="/servicios/reparacion-wordpress-urgente/" class="btn btn--ghost" style="font-size:0.86rem; padding:0.65rem 1.1rem; min-height:44px;">Reparación urgente →</a>
      <a href="<?= wa_url('malware') ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp" style="font-size:0.86rem; padding:0.65rem 1.1rem; min-height:44px;" aria-label="Consultar urgencia técnica por WhatsApp">WhatsApp Urgente</a>
    </div>
  </div>
</aside>
