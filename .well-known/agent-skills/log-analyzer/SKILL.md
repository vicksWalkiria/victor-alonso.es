# Apache and Nginx Log Analyzer

Analizador de archivos de registro (access log) en formato Common o Combined de servidores web Apache y Nginx.

## Interface
- **Tool Name:** `apacheNginxLogAnalyzer`
- **URL:** `https://www.victor-alonso.es/herramientas/analizador-logs/`
- **Method:** POST
- **Input Parameters:**
  - `logText` (string, required): Líneas de log en formato Apache o Nginx a procesar.

## Capabilities
- Estimación del crawl budget consumido por Googlebot y otros bots de búsqueda
- Detección de errores 404 recurrentes y URLs rotas
- Identificación de IPs sospechosas o ataques de fuerza bruta
- Desglose por códigos de estado HTTP (200, 301, 404, 500) y tipos de archivo
