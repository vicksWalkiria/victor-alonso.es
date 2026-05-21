/**
 * map.js — Mapa Leaflet de Albacete (carga diferida)
 * Solo se incluye en páginas que lo necesitan.
 */

(function () {
  'use strict';

  var MAP_EL = document.getElementById('map');
  if (!MAP_EL) return;

  // Oficina: Calle Iris 25, Albacete

  function initMap() {
    if (!window.L) return;
    var lat = 38.9978354, lng = -1.8567414;
    var map = L.map('map').setView([lat, lng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19
    }).addTo(map);

    var icon = L.icon({
      iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
      shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
      iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34],
      shadowSize: [41, 41]
    });

    L.marker([lat, lng], { icon: icon })
      .addTo(map)
      .bindPopup(
        '<strong>Víctor Alonso SEO</strong><br>' +
        'Calle Iris 25, Albacete<br>' +
        '<a href="tel:+34675946486">+34 675 946 486</a>'
      )
      .openPopup();
  }

  // Carga diferida con IntersectionObserver
  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          initMap();
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '200px' });
    observer.observe(MAP_EL);
  } else {
    initMap();
  }

})();
