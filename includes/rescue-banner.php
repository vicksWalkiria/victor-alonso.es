<?php
/**
 * rescue-banner.php — Bloque de intervención técnica urgente / rescate WordPress
 * Diseñado para herramientas técnicas donde los usuarios sufren errores 500, caídas o hackeos.
 */
?>
<aside class="rescue-banner-box" style="margin: 2rem 0; padding: 1.25rem 1.5rem; background: linear-gradient(135deg, rgba(232,104,26,0.07) 0%, rgba(26,26,26,0.6) 100%); border: 1px solid rgba(232,104,26,0.25); border-left: 4px solid var(--orange, #e8681a); border-radius: 8px;" aria-label="Servicio de rescate técnico urgente">
  <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1.25rem;">
    <div style="max-width:700px;">
      <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.35rem;">
        <span style="font-size:1.1rem;" aria-hidden="true">🚨</span>
        <strong style="color:var(--orange, #e8681a); font-size:0.92rem; text-transform:uppercase; letter-spacing:0.5px;">¿Tu web está caída, hackeada o arrojando errores 500?</strong>
      </div>
      <p style="margin:0; font-size:0.9rem; color:var(--text, #d1d5db); line-height:1.5;">
        Intervengo de urgencia en <strong>menos de 24 horas directamente por SSH</strong>: diagnóstico y limpieza de malware en WordPress, errores críticos de servidor y recuperación integral garantizada por ingeniero informático.
      </p>
    </div>
    <div style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
      <a href="/servicios/reparacion-wordpress-urgente/" class="btn btn--ghost" style="font-size:0.85rem; padding:0.55rem 1rem; text-decoration:none;">Reparación urgente 24h →</a>
      <a href="<?= wa_url('malware') ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp" style="font-size:0.85rem; padding:0.55rem 1rem; text-decoration:none;" aria-label="Consultar urgencia técnica por WhatsApp">WhatsApp Urgente</a>
    </div>
  </div>
</aside>
