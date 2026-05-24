/**
 * cookie-consent-init.js
 * Inicializa vanilla-cookieconsent v3 con soporte para GA4.
 * GA4 SOLO se carga si el usuario acepta la categoría 'analytics'.
 */
(function () {
  'use strict';

  var gaId = document.body && document.body.getAttribute('data-ga-id');

  /* ── Función que carga GA4 de forma diferida ─────────────────────────── */
  var gaLoaded = false;
  function loadGA() {
    if (gaLoaded || !gaId) return;
    gaLoaded = true;

    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = gtag;

    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId);
    script.onload = function () {
      gtag('js', new Date());
      gtag('config', gaId);
    };
    document.head.appendChild(script);
  }

  /* ── Inicializar CookieConsent ───────────────────────────────────────── */
  CookieConsent.run({

    /* Configuración del comportamiento */
    revision: 1,
    cookie: {
      name: 'cc_cookie',
      expiresAfterDays: 365
    },

    /* Mostrar el banner a usuarios nuevos */
    guiOptions: {
      consentModal: {
        layout: 'bar',
        position: 'bottom',
        equalWeightButtons: false,
        flipButtons: false
      },
      preferencesModal: {
        layout: 'box',
        equalWeightButtons: true,
        flipButtons: false
      }
    },

    /* Categorías de cookies */
    categories: {
      necessary: {
        enabled: true,
        readOnly: true
      },
      analytics: {
        enabled: false,
        autoClear: {
          cookies: [
            { name: /^_ga/ }
          ]
        }
      }
    },

    /* Textos en español */
    language: {
      default: 'es',
      translations: {
        es: {
          consentModal: {
            title: 'Usamos cookies de analítica',
            description: 'Utilizamos Google Analytics para entender cómo se usa el sitio y mejorar la experiencia. No se comparten datos con anunciantes ni terceros con fines comerciales. <a href="/politica-cookies/" class="cc__link">Política de cookies</a>.',
            acceptAllBtn: 'Aceptar',
            acceptNecessaryBtn: 'Rechazar',
            showPreferencesBtn: 'Gestionar preferencias',
            footer: '<a href="/aviso-legal/">Aviso legal</a> · <a href="/politica-privacidad/">Privacidad</a>'
          },
          preferencesModal: {
            title: 'Preferencias de cookies',
            acceptAllBtn: 'Aceptar todas',
            acceptNecessaryBtn: 'Rechazar todas',
            savePreferencesBtn: 'Guardar preferencias',
            closeIconLabel: 'Cerrar',
            serviceCounterLabel: 'Servicio|Servicios',
            sections: [
              {
                title: 'Cookies estrictamente necesarias',
                description: 'Son imprescindibles para el funcionamiento básico del sitio. No recopilan datos de navegación ni se pueden desactivar.',
                linkedCategory: 'necessary'
              },
              {
                title: 'Cookies de analítica',
                description: 'Nos ayudan a entender cómo interactúas con el sitio (páginas vistas, tiempo en página, etc.) usando Google Analytics. Los datos son anónimos y no se utilizan con fines publicitarios.',
                linkedCategory: 'analytics',
                cookieTable: {
                  caption: 'Tabla de cookies',
                  headers: {
                    name: 'Cookie',
                    domain: 'Dominio',
                    desc: 'Descripción',
                    duration: 'Duración'
                  },
                  body: [
                    {
                      name: '_ga',
                      domain: 'victor-alonso.es',
                      desc: 'Distingue usuarios únicos de Google Analytics.',
                      duration: '2 años'
                    },
                    {
                      name: '_ga_*',
                      domain: 'victor-alonso.es',
                      desc: 'Mantiene el estado de sesión de GA4.',
                      duration: '2 años'
                    }
                  ]
                }
              }
            ]
          }
        }
      }
    },

    /* Callbacks */
    onConsent: function () {
      if (CookieConsent.acceptedCategory('analytics')) {
        loadGA();
      }
    },
    onChange: function () {
      if (CookieConsent.acceptedCategory('analytics')) {
        loadGA();
      }
    }

  });

})();
