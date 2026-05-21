/**
 * analytics-deferred.js — GA4 sin bloquear el hilo principal en la carga inicial
 * Carga gtag tras idle, timeout o primera interacción del usuario.
 */
(function () {
  'use strict';

  var gaId = document.body && document.body.getAttribute('data-ga-id');
  if (!gaId) return;

  window.dataLayer = window.dataLayer || [];
  function gtag() {
    window.dataLayer.push(arguments);
  }
  window.gtag = gtag;

  var loaded = false;

  function loadGA() {
    if (loaded) return;
    loaded = true;

    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId);
    script.onload = function () {
      gtag('js', new Date());
      gtag('config', gaId);
    };
    document.head.appendChild(script);
  }

  if ('requestIdleCallback' in window) {
    requestIdleCallback(loadGA, { timeout: 4000 });
  } else {
    window.addEventListener('load', function () {
      setTimeout(loadGA, 2500);
    });
  }

  ['scroll', 'click', 'keydown', 'touchstart'].forEach(function (eventName) {
    window.addEventListener(eventName, loadGA, { once: true, passive: true });
  });
})();
