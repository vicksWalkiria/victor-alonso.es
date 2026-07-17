## 2026-07-17

- **URL:** `/` (Home)
- **Cambio:** Actualizado title a "Consultor SEO en Albacete | Víctor Alonso", description orientada a servicio técnico, CTA final adaptado a "Cuéntame qué está frenando tu web" y enlace a landing de servicio local con anchor "SEO local para empresas de Albacete".
- **Motivo:** SEO-01. Consolidar la Home como landing para consultas de "consultor SEO en Albacete" y diferenciar el enlace hacia la landing específica de SEO local.
- **KPI Asociado:** Leads orgánicos, clics no branded.
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** `/servicios/seo-albacete/` y navegación principal
- **Cambio:** Actualizado title a "SEO local en Albacete para empresas | Víctor Alonso", description orientada a visibilidad local. Reescrito el H1 y las secciones principales (H2, H3) para estructurar el contenido como un servicio local (entregables, diagnóstico) y no repetir la propuesta genérica de la Home. Cambiado el anchor del menú a "SEO local en Albacete" e incluido un enlace de marca a la Home.
- **Motivo:** SEO-02. Diferenciar la landing de SEO local para evitar la canibalización semántica con la Home y enfocarla a una intención más específica de Google Business Profile y posicionamiento por zonas.
- **KPI Asociado:** Evolución del clúster de consultas "SEO local Albacete" en GSC; clics hacia esta landing.
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** Varias (`/`, `/herramientas/analizador-logs/`, `/herramientas/tester-htaccess/`, `/herramientas/analizador-seo/`)
- **Cambio:** Reordenado el enlazado interno comercial. En la Home se han sustituido los anchors genéricos "Ver servicio" por anchors exactos ("auditoría SEO técnica", "servicio de SEO técnico", "mantenimiento WordPress"). En las herramientas se han ajustado los enlaces para apuntar con la intención correcta (ej. "migraciones y redirecciones SEO", "auditoría SEO manual").
- **Motivo:** SEO-03. Mejorar la distribución de PageRank interno y asegurar que cada anchor text refuerza la intención asignada a la URL de destino, eliminando señales contradictorias.
- **KPI Asociado:** Mejora de posición media en servicios core.
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** `/servicios/mantenimiento-wordpress/` y relacionados (`/casos-reales.php`)
- **Cambio:** Reescribir contenido para centrar la intención en mantenimiento "preventivo". Añadidas secciones de "Para quién es", "Qué incluye la puesta a punto", "Lo que NO incluye", "La diferencia entre prevenir y curar" y "FAQs técnicas". Actualizados metadatos (Title y Description). Añadidos enlaces desde casos reales e integrados enlaces salientes a reparación urgente.
- **Motivo:** SEO-04. Resolver la intención mixta que estaba lastrando la URL, separando las emergencias (malware/hackeos) de los servicios recurrentes para escalar en SERPs de "mantenimiento wordpress profesional".
- **KPI Asociado:** Impresiones y clics del clúster de mantenimiento (Search Console).
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** `/servicios/reparacion-wordpress-urgente/`
- **Cambio:** Creada nueva landing dedicada a emergencias, limpieza de malware, caídas y errores críticos. Incorporados metadatos, FAQs (Schema FAQPage), secciones forenses y enlazado desde Home, Mantenimiento y Casos reales. Añadida a sitemap.xml.
- **Motivo:** SEO-05. Separar la intención de emergencias y hackeos de la del mantenimiento preventivo recurrente. Responde específicamente a consultas transaccionales críticas de alta prioridad ("wordpress hackeado", "urgencia", "reparación").
- **KPI Asociado:** Impresiones de consultas urgentes en GSC y leads directos vía WhatsApp.
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** `/herramientas/tester-htaccess/`
- **Cambio:** Optimización de metadatos y contenido de la herramienta de simulador `.htaccess`. Añadida tabla de contenidos (TOC), corrección de explicación técnica sobre redirecciones 302 y 301, y alerta de aviso sobre limitaciones del entorno simulado (no sustituye staging).
- **Motivo:** SEO-06. Consolidar el CTR de la página en SERPs mediante metadatos optimizados y aumentar el tiempo de permanencia con mejor estructuración y copy técnico sin sobredensidad de palabras clave.
- **KPI Asociado:** Mejora de posición media (de 10.1 a top 5) y CTR para consultas como "htaccess tester" o "rewrite rule htaccess".
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** `/herramientas/analizador-logs/`
- **Cambio:** Mejoras editoriales en la herramienta Analizador de Logs. Sustitución de terminología determinista ("verdad absoluta") por explicaciones objetivas de las ventajas frente a GA4 y GSC. Añadido botón "Cargar ejemplo de prueba" para facilitar el onboarding y descubrimiento del dashboard sin fricción. Añadidas secciones descriptivas sobre toma de decisiones reales (huérfanas, spider traps) y limitaciones explícitas (no detecta canibalizaciones ni UX). Reforzado el copy sobre protección y borrado efímero en RAM (RGPD).
- **Motivo:** SEO-07. Aumentar la tasa de conversión (uso de la herramienta) al reducir la barrera de entrada y generar más confianza mediante transparencia técnica estricta.
- **KPI Asociado:** Incremento de leads y uso de la herramienta. Retención en la página.
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** `/herramientas/analizador-seo/`
- **Cambio:** Optimización completa de metadatos (Title y H1) para reenfocar la herramienta hacia "Analizador SEO técnico de URLs" y evitar sobreprometer "auditorías SEO" completas. Se han matizado todas las respuestas del FAQ (H1, TTFB, Seguridad) para alinearlas con la realidad técnica de Google (las cabeceras no posicionan por sí mismas, pero previenen catástrofes). Se ha añadido una sección explícita con la lista de comprobaciones y limitaciones, y se han vinculado las alertas rojas directamente con los servicios de consultoría pertinentes.
- **Motivo:** SEO-08. Ajustar el H1 con la intención de búsqueda real de los usuarios, mejorar el rigor editorial de la landing y abrir canales de captación más directos ante alertas de redirecciones, TTFB alto o problemas de indexación.
- **KPI Asociado:** Mejora de posición media para `analizador seo` y `analizador técnico urls`. Aumento del CTR interno hacia las landings de servicios.
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.
- **URL:** `/herramientas/generador-schema-local/`
- **Cambio:** Mejoras UX y estructurales en el Generador de Schema LocalBusiness. Se han añadido botones para descargar el JSON directamente y validarlo, así como eventos de rastreo analítico (copia, descarga, validación). Se ha rebajado la afirmación de que el Schema consolida el Local Pack directamente, y se ha mejorado la instrucción sobre campos obligatorios y opcionales. El CTA contextual ahora apunta explícitamente a `/servicios/seo-local/`.
- **Motivo:** SEO-09. Mejorar la utilidad de la herramienta para los usuarios y poder medir correctamente qué interacciones de conversión ocurren con el código generado.
- **KPI Asociado:** Eventos de uso (`schema_copy_click`, `schema_download_click`) y CTR a la landing de servicio local.
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.
- **URL:** `/herramientas/` (Hub)
- **Cambio:** Reestructuración de la página índice de herramientas. Se ha priorizado el orden de las tarjetas (Tester .htaccess, Logs, Schema, Analizador SEO) y se ha incorporado un sistema de filtrado dinámico por categorías (Rastreo, Redirecciones, Rendimiento, etc.). Se ha enriquecido el diseño de las tarjetas añadiendo cajas de metadatos informativos (Entrada, Salida, Proceso local vs servidor) y un tracker de eventos de clics a cada herramienta. Se ha sincronizado el marcado `ItemList` con el nuevo orden visual.
- **Motivo:** SEO-10. Mantener la utilidad del Hub sin sobreoptimizarlo para "suite SEO". Mejorar la experiencia de usuario y facilitar la búsqueda de utilidades para aumentar el CTR interno.
- **KPI Asociado:** Distribución equilibrada de clics a herramientas (`tool_hub_click`).
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** Varias (Herramientas, Casos reales, Servicios)
- **Cambio:** Auditoría profunda de copy técnico. Se han rebajado afirmaciones absolutas ("el robots.txt soluciona duplicados", "el crawl budget hunde tu web pequeña") hacia explicaciones más ajustadas a la realidad algorítmica de Google. Sustituidos conceptos deterministas por dinámicas de eficiencia de rastreo, señales Core Web Vitals y manejo conjunto de directivas (noindex + canonical).
- **Motivo:** SEO-13. Consolidar la credibilidad técnica de Víctor Alonso como consultor SEO de alto nivel, evitando dogmas genéricos que abundan en el sector B2C.
- **KPI Asociado:** Ninguno directo (Mejora de E-E-A-T).
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** Múltiples URLs (Herramientas y Servicios)
- **Cambio:** Optimización de longitud de etiquetas Title y Meta Description en 14 páginas clave para evitar truncamiento en las SERPs (límite de ~600px/985px) y eliminar sufijos redundantes. Redacción orientada a "Problema + Beneficio".
- **Motivo:** Mejorar el CTR (Click-Through Rate) orgánico asegurando que el mensaje comercial se lea completo en resultados de búsqueda.
- **KPI Asociado:** Mejora del CTR en GSC en las próximas 4 semanas.
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.

- **URL:** `/robots.txt`
- **Cambio:** Bloqueado el directorio `/cdn-cgi/` mediante `Disallow: /cdn-cgi/`.
- **Motivo:** Evitar falsos 404 reportados en Search Console generados por los rastreadores de protección de email de Cloudflare.
- **KPI Asociado:** Desaparición de los errores 404 en Search Console.
- **Commit:** Pendiente
- **Estado de validación:** Implementado en código.
