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

  // ── Header scroll shadow ─────────────────────────────────────────────────
  const header = document.querySelector('.site-header');
  if (header) {
    const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 10);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
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

})();
