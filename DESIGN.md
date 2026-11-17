# Sistema de Diseño — Víctor Alonso SEO (`victor-alonso.es`)

Documento de referencia visual y de accesibilidad para todas las plantillas PHP, páginas comerciales y herramientas interactivas de `victor-alonso.es`.

## 1. Posicionamiento Visual y Tono
- **Identidad:** Consultoría de ingeniería informática aplicada a SEO técnico, SEO local (Albacete y Castilla-La Mancha) y WordPress crítico.
- ** Personalidad:** Directa, sobria, verificable y orientada a datos. Cero humo de agencia tradicional.
- **Modos de superficie:**
  - *Persuade* en Home (`/`) y landings de `/servicios/` y `/casos-reales/`.
  - *Operate* en `/herramientas/*` (prioridad absoluta a legibilidad de tablas, métricas `tabular-nums` y controles claros).

## 2. Paleta y Tokens de Color (Calibración WCAG AA)
Nunca utilizar negro puro `#000000` ni acentos con contraste insuficiente sobre texto blanco:

| Token CSS | Valor Hex | Rol y Contraste |
|---|---|---|
| `--black` | `#141619` | Carbón pizarra profundo para fondos oscuros, cabeceras fuertes y pie (`> 17:1` con `#FFFFFF`). |
| `--dark-card` | `#1C1F24` | Superficie elevada sobre secciones oscuras. |
| `--white` | `#FFFFFF` | Superficie base principal. |
| `--bg` | `#F8FAFC` | Fondo alterno frío de alta limpieza para bloques secundarios. |
| `--bg-hover` | `#F1F5F9` | Superficie de fichas técnicas, tablas sutiles y estados hover. |
| `--text` | `#1E293B` | Tinta principal de lectura (`14.6:1` sobre blanco). |
| `--muted` | `#475569` | Texto secundario y metadatos (`7.5:1` sobre blanco, `7.0:1` sobre `--bg`). |
| `--border` | `#E2E8F0` | Divisores de 1px y contornos de tarjetas/tablas. |
| `--orange` | `#C84B09` | Acento corporativo principal calibrado para garantizar contraste **WCAG AA (`4.82:1`)** con texto blanco `#FFFFFF` en botones y etiquetas. |
| `--orange-dark` | `#A63C05` | Estado `:hover` / `:active` de acciones primarias (`6.35:1` con `#FFFFFF`). |
| `--orange-light` | `#FFF3EB` | Tinte cálido suave para fondos de realce e iconos. |
| `--whatsapp` | `#15803D` | Acción directa de WhatsApp con contraste **WCAG AA (`5.02:1`)** sobre texto blanco. |

## 3. Tipografía y Datos Numéricos
- **Familia base (`--font`):** `'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif` (o pila nativa optimizada para cero bloqueo de renderizado / LCP `< 1.2s`).
- **Monoespaciada (`--font-mono`):** `'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace` reservada exclusivamente para bloques de código, logs Apache/Nginx, reglas `.htaccess`, URLs técnicas y JSON-LD.
- **Números tabulares (`font-variant-numeric: tabular-nums`):** Obligatorios en tablas de precios, métricas de herramientas, contadores HTTP, teléfonos y porcentajes.
- **Medida de lectura:** Párrafos de contenido entre `62ch` y `72ch`.

## 4. Superficies del Navegador y Accesibilidad
- Selección de texto (`::selection`) tintada con `--orange` y texto blanco.
- `:focus-visible` con anillo nítido de `2px solid var(--orange)` y `outline-offset: 3px`.
- `scrollbar-color` y `accent-color` alineados con `--orange`.
- Área mínima táctil en controles móviles e interactivos: `>= 44px` de alto sin desbordamiento horizontal (`overflow-x`).

## 5. Prohibiciones Estrictas (Anti-Slop)
1. **Cero emojis Unicode (`🚀`, `✨`, `🛡️`, `📊`, `🔍`)** como iconos de navegación, cabeceras `<h2>`/`<h3>`, insignias o tarjetas. Usar únicamente **SVG inline** con `stroke-width="2"` (o `1.75`) y `aria-hidden="true"`.
2. **Cero dependencias rotas de FontAwesome (`<i class="fa-solid ...">`)**: el sitio no carga hojas de fuentes de iconos externas por rendimiento WPO; todo icono debe ser SVG inline.
3. **Cero resplandores de neón (`box-shadow: 0 0 18px ...`)**, gradientes púrpura-azul, tarjetas anidadas innecesarias o bordes laterales gruesos (`border-left > 1px`) en tarjetas y alertas.
4. **Cero kickers/eyebrows superfluos** encima de `<h1>` cuando el titular ya comunica el servicio y la ubicación con peso propio.
