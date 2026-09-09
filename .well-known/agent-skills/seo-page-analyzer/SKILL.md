# SEO Page Analyzer

Audita cabeceras de respuesta HTTP, tiempos TTFB en milisegundos, metaetiquetas principales (Title, Description, H1), directivas robots y cabeceras de seguridad web para cualquier URL pública.

## Interface
- **Tool Name:** `seoPageAnalyzer`
- **URL:** `https://www.victor-alonso.es/herramientas/analizador-seo/`
- **Method:** GET / Form POST
- **Input Parameters:**
  - `url` (string, uri, required): Dirección web completa a analizar (ej. `https://ejemplo.com`).

## Capabilities
- Análisis de código de respuesta HTTP y redirecciones
- Medición de TTFB (Time to First Byte)
- Comprobación de indexabilidad (robots meta tag, canonical, X-Robots-Tag)
- Verificación de encabezados de seguridad (HSTS, CSP, X-Frame-Options, X-Content-Type-Options)
