/**
 * main.js — JavaScript vainilla mínimo
 * victor-alonso.es
 */

(function () {
  'use strict';

  // ── Menú móvil ──────────────────────────────────────────────────────────
  const toggle = document.getElementById('nav-toggle');
  const nav    = document.getElementById('site-nav');

  if (toggle && nav) {
    const navList = nav.querySelector('.nav-list');

    toggle.addEventListener('click', () => {
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      navList.classList.toggle('open', !expanded);
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', (e) => {
      if (!nav.contains(e.target) && !toggle.contains(e.target)) {
        toggle.setAttribute('aria-expanded', 'false');
        navList.classList.remove('open');
      }
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        toggle.setAttribute('aria-expanded', 'false');
        navList.classList.remove('open');
        toggle.focus();
      }
    });
  }

  // ── Dropdown Servicios ───────────────────────────────────────────────────
  const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');

  dropdownToggles.forEach((btn) => {
    const dropdown = document.getElementById(btn.getAttribute('aria-controls'));
    if (!dropdown) return;

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      // Cerrar otros dropdowns
      dropdownToggles.forEach((other) => {
        if (other !== btn) {
          other.setAttribute('aria-expanded', 'false');
          const otherDd = document.getElementById(other.getAttribute('aria-controls'));
          if (otherDd) otherDd.classList.remove('open');
        }
      });
      btn.setAttribute('aria-expanded', String(!expanded));
      dropdown.classList.toggle('open', !expanded);
    });

    document.addEventListener('click', () => {
      btn.setAttribute('aria-expanded', 'false');
      dropdown.classList.remove('open');
    });
  });

  // ── Header scroll shadow (rAF para evitar reflows forzados en scroll) ───
  const header = document.querySelector('.site-header');
  if (header) {
    let scrollTicking = false;
    const updateScrollState = () => {
      header.classList.toggle('scrolled', window.scrollY > 10);
      scrollTicking = false;
    };
    const onScroll = () => {
      if (scrollTicking) return;
      scrollTicking = true;
      requestAnimationFrame(updateScrollState);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    requestAnimationFrame(updateScrollState);
  }

  // ── FAQ accordion ────────────────────────────────────────────────────────
  document.querySelectorAll('.faq-question').forEach((btn) => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      const isOpen = item.classList.contains('open');
      // Cerrar todos
      document.querySelectorAll('.faq-item.open').forEach((el) => el.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
    });
  });

  // ── Validación básica formulario ─────────────────────────────────────────
  const form = document.getElementById('contact-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      const email = form.querySelector('[type="email"]');
      const legal = form.querySelector('[name="legal"]');

      if (email && !email.value.includes('@')) {
        e.preventDefault();
        email.focus();
        email.setCustomValidity('Introduce un correo electrónico válido.');
        email.reportValidity();
        return;
      }
      if (legal && !legal.checked) {
        e.preventDefault();
        legal.focus();
        legal.setCustomValidity('Debes aceptar la política de privacidad.');
        legal.reportValidity();
      }
    });

    // Limpiar custom validity al modificar
    form.querySelectorAll('input, textarea').forEach((el) => {
      el.addEventListener('input', () => el.setCustomValidity(''));
    });
  }

  // ── Widget Flotante de WhatsApp ──────────────────────────────────────────
  const waWidget = document.getElementById('floating-whatsapp');
  const waClose  = document.getElementById('floating-whatsapp-close');

  if (waWidget && waClose) {
    if (localStorage.getItem('whatsapp_dismissed') === 'true') {
      waWidget.classList.add('dismissed');
    }
    waClose.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      waWidget.classList.add('dismissed');
      localStorage.setItem('whatsapp_dismissed', 'true');
    });
  }

  // ── Widget Flotante de Contacto ─────────────────────────────────────────
  const contactWidget = document.getElementById('floating-contact');
  const contactClose  = document.getElementById('floating-contact-close');

  if (contactWidget && contactClose) {
    if (localStorage.getItem('contact_dismissed') === 'true') {
      contactWidget.classList.add('dismissed');
    }
    contactClose.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      contactWidget.classList.add('dismissed');
      localStorage.setItem('contact_dismissed', 'true');
    });
  }

  // ── Eventos de Seguimiento de GA4 (Conversiones y Objetivos) ────────────────
  const trackEvent = (eventName, params) => {
    if (typeof window.gtag === 'function') {
      window.gtag('event', eventName, params);
    } else {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push(['event', eventName, params]);
    }
  };

  // Intercepta submits de formularios de herramientas
  document.addEventListener('submit', (e) => {
    const form = e.target;
    const toolName = form.getAttribute('toolname');
    
    if (toolName) {
      const eventParams = {};
      
      // Extrae la URL analizada para guardarla en GA4
      const urlInput = form.querySelector('input[type="url"], input[name="url"], input#seo-url-input, input#test-url, input#sm-url');
      if (urlInput && urlInput.value) {
        eventParams.analyzed_url = urlInput.value.trim();
      }
      
      const sitemapInput = form.querySelector('input[name="sitemap_url"], input#sitemap-url-input');
      if (sitemapInput && sitemapInput.value) {
        eventParams.sitemap_url = sitemapInput.value.trim();
      }

      const businessInput = form.querySelector('input[name="name"], input#local-business-name');
      if (businessInput && businessInput.value) {
        eventParams.business_name = businessInput.value.trim();
      }

      // Mapea toolname a evento específico
      let eventName = '';
      switch (toolName) {
        case 'seoPageAnalyzer':
          eventName = 'use_analizador_seo';
          break;
        case 'cookieConsentAuditor':
          eventName = 'use_auditor_cookies';
          break;
        case 'apacheNginxLogAnalyzer':
          eventName = 'use_analizador_logs';
          break;
        case 'htaccessTester':
          eventName = 'use_tester_htaccess';
          break;
        case 'semanticEntityExtractor':
          eventName = 'use_extractor_entidades';
          break;
        case 'localBusinessSchemaGenerator':
          eventName = 'use_generador_schema';
          break;
        case 'wpoLossCalculator':
          eventName = 'use_calculadora_wpo';
          break;
        case 'sitemapUrlExtractor':
        case 'sitemapRawExtractor':
          eventName = 'use_extractor_sitemap';
          break;
        case 'exifMetadataEditor':
          eventName = 'use_editor_metadatos';
          break;
        case 'gscReportGenerator':
          eventName = 'use_generador_informe_gsc';
          break;
        case 'orphanPagesAnalyzer':
          eventName = 'use_analizador_huerfanas';
          break;
      }

      if (eventName) {
        trackEvent(eventName, eventParams);
      }
    }
  });

  // Intercepta clics en enlaces de salida e interacciones
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href') || '';
    
    // 1. WhatsApp Clicks
    if (href.includes('wa.me') || href.includes('whatsapp.com')) {
      trackEvent('click_whatsapp', { destination: href });
    }
    // 2. Phone Calls Clicks
    else if (href.startsWith('tel:')) {
      trackEvent('click_phone', { phone_number: href });
    }
    // 3. Email mailto Clicks
    else if (href.startsWith('mailto:')) {
      trackEvent('click_email', { email_address: href });
    }
    // 4. Social Links Clicks
    else if (href.includes('linkedin.com')) {
      trackEvent('click_social_linkedin', { destination: href });
    }
    else if (href.includes('twitter.com') || href.includes('x.com')) {
      trackEvent('click_social_twitter', { destination: href });
    }
    // 5. Walkiria Apps Clicks
    else if (href.includes('walkiriaapps.com') || href.includes('walkiria.io') || href.includes('walkiria')) {
      trackEvent('click_walkiria_apps', { destination: href });
    }
    // 6. GMB Checker ZIP Download / GitHub clicks
    else if (href.includes('GMB-web-checker')) {
      trackEvent('use_auditor_seo_local', {
        download_zip: href.endsWith('.zip') ? 1 : 0,
        visit_github: href.endsWith('.zip') ? 0 : 1
      });
    }
  });

  // ── Tracking de vistas de casos de éxito ────────────────────────────────────
  if (document.body && document.body.classList.contains('page-caso-detalle')) {
    const h1 = document.querySelector('h1');
    const caseTitle = h1 ? h1.textContent.trim() : document.title.split('|')[0].trim();
    trackEvent('view_success_case', {
      case_title: caseTitle
    });
  }

})();
