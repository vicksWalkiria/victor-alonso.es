# 🏆 Caso de Éxito: Desinfección Integral y Erradicación de Malware de Persistencia en WordPress

**Cliente:** Clínica de Especialistas Médicos (Anonimizado)  
**Sector:** Salud y Bienestar  
**Objetivo:** Eliminar infecciones recurrentes por malware, reducir la sobrecarga de CPU (99% constante), erradicar mecanismos de persistencia ocultos y restaurar la integridad del sitio web en producción.

> **Resumen Ejecutivo:** Una clínica médica sufría caídas constantes y una sobrecarga de recursos del servidor (CPU al 99% constante) debido a una infección de malware recurrente que se autogeneraba cada vez que era borrada. Llevamos a cabo una auditoría forense a través de SSH, saneamos el core de WordPress, eliminamos scripts de persistencia en archivos ocultos de configuración del servidor y base de datos, y blindamos el entorno perimetral. El servicio se restableció por completo y el consumo del servidor regresó a niveles óptimos de reposo.

---

## 🔍 1. Diagnóstico Inicial y Auditoría de Seguridad

El sitio presentaba un comportamiento errático, redirecciones no autorizadas, caídas intermitentes por consumo excesivo de recursos en el hosting (CPU al 99-100%) y múltiples alertas de seguridad. 

Tras una inspección de archivos y base de datos, se descubrió una infraestructura de malware sofisticada diseñada para garantizar la **persistencia** (el virus se autogeneraba automáticamente cada vez que se eliminaba algún archivo infectado).

---

## 🦠 2. Anatomía de la Infección (Mecanismos de Persistencia)

Se detectaron múltiples backdoors y puntos de entrada camuflados en diferentes niveles del stack de WordPress:

### A. Nivel Servidor (Inyección Pre-Ejecución)
*   **Archivos `.user.ini`**: Inyectados tanto en la raíz como en `/wp-content/`. Contenían la directiva `auto_prepend_file = "/ruta-de-la-web/wp-content/.1cfb1425.php"`. Esto obligaba al intérprete de PHP a ejecutar el código malicioso en cada petición HTTP antes de cargar cualquier parte legítima de la web.
*   **Inyección en `.htaccess`**: Directivas de tipo `auto_prepend_file` apuntando a scripts php infectados.

### B. Nivel Core de WordPress (Manipulación del Sistema)
*   **Backdoor SSO en `mu-plugins/sso-loader.php`**: Permitía inicios de sesión como administrador de forma remota y sin credenciales válidas.
*   **Caché manipulada (`wp-content/object-cache.php` y `db.php`)**: Archivos legítimos del sistema modificados para cargar payloads cifrados en base64.
*   **Archivos ocultos**: Scripts maliciosos camuflados con nombres que simulaban archivos del sistema (`.01fe17d9.php`, `eae879f0.php`, `.63e9870d.php`, `63e9870d.php`, `118de4fa.zip`).

### C. Nivel Plantillas y Plugins
*   **Tema clonador oculto (`perf-theme-child`)**: Un tema hijo no usado que no aparecía en el panel de WordPress pero cuyo archivo `functions.php` contenía un stager de malware que regeneraba el virus en bucle.
*   **Tabla huérfana en base de datos (`wp_wpfm_backup`)**: Rastro de un popular plugin de gestión de archivos con vulnerabilidades RCE (ejecución remota de código) conocidas, una de las vías de entrada históricas.

### D. Nivel Base de Datos (Persistencia Lógica)
*   **Usuario administrador oculto**: Creado bajo el nombre `adm_e5c3784d73` para mantener el acceso al panel.
*   **Tareas cron maliciosas**: Hooks registrados en WordPress (`sc_cron_fetch`, `j7coqs9ezlp9fztb4dww`) para descargar nuevas versiones del virus de forma programada.
*   **Opciones maliciosas**: Registros inyectados en la tabla `wp_options` (`sc_*`, `sso_token`).

---

## 🛠️ 3. Plan de Acción y Desinfección

Para resolver el problema de raíz y garantizar que el virus no volviera a autogenerarse, se implementó una estrategia de **"Tabula Rasa" (Limpieza quirúrgica y reinstalación)**:

### Paso 1: Aislamiento Local y Desinfección
1.  Se descargó la instalación completa en un entorno local y controlado de desarrollo.
2.  Se eliminaron por completo las carpetas del core (`wp-admin` y `wp-includes`) y se reemplazaron por archivos 100% limpios de la versión original.
3.  Se eliminaron de forma segura todos los archivos no pertenecientes al core limpio en el directorio raíz.
4.  Se auditó la plantilla propia (`wp-content/themes/custom-theme`) y se desinfectó su archivo `functions.php` que había sido manipulado con cargadores ofuscados.

### Paso 2: Saneamiento de Base de Datos
1.  Búsqueda y eliminación definitiva del usuario administrador malicioso `adm_e5c3784d73`.
2.  Limpieza de la tabla `wp_options`, eliminando todos los registros correspondientes al virus.
3.  Desactivación y eliminación de las tareas cron maliciosas dentro del registro serializado del sistema.
4.  Borrado de la tabla huérfana y vulnerable `wp_wpfm_backup` asociada al plugin de gestión de archivos comprometido.
5.  Actualización y securización de la contraseña del usuario administrador principal (`admin_wp`) mediante hash seguro.

### Paso 3: Reconstrucción de la Home y Ajustes del Tema
*   Durante el ataque o la limpieza previa del cliente, la página asignada como Portada había sido borrada físicamente de la base de datos, provocando un error 404 en la portada.
*   Se reconstruyó la página con su ID original mediante comandos SQL y se reasignó como página de inicio estática en `page_on_front`.
*   Se implementó un hook limpio en `functions.php` (`pre_get_posts`) para forzar un límite de 9 posts por página en la retícula del blog, asegurando la consistencia del diseño.

---

## 🔒 4. Hardening y Blindaje en Producción

Una vez limpio el sitio en local, se preparó para subirlo al servidor de producción aplicando un blindaje de seguridad avanzado:

### A. Reglas Avanzadas de `.htaccess`
Se diseñó un `.htaccess` centrado en la seguridad:
1.  **Bloqueo de URLs con parámetros (410 Gone)**: El malware usaba llamadas del tipo `?action=...` o `?cmd=...` en el frontend. Se implementó una regla que devuelve un código `410 (Gone)` para cualquier URL del frontend que lleve parámetros de consulta (Query Strings), exceptuando las rutas legítimas de administración y la REST API (`wp-admin`, `wp-json`, `wp-login.php`, `admin-ajax.php` y `wp-cron.php`).
2.  **Excepción para recursos estáticos**: Se añadieron exclusiones para que las imágenes, archivos CSS, JS y tipografías (que usan parámetros como `?ver=1.0.0`) carguen correctamente sin devolver 410.
3.  **Bloqueo de ejecución PHP**: Se denegó la ejecución de scripts PHP en cualquier subdirectorio de `/wp-content/` que no pertenezca estrictamente a plugins o themes legítimos.

### B. Configuración de `wp-config.php`
Se añadieron directivas de seguridad estrictas en el archivo de configuración del servidor:
*   `define( 'FS_METHOD', 'direct' );` -> Resuelve problemas de permisos y evita peticiones de credenciales FTP al actualizar.
*   `define( 'DISALLOW_FILE_EDIT', true );` -> Deshabilita el editor de código integrado en el panel de WordPress para impedir que se inyecte código desde el navegador aunque un atacante consiga entrar al panel de administración.
*   `define( 'WP_POST_REVISIONS', 3 );` -> Limita el número de revisiones guardadas en base de datos para optimizar su rendimiento.
*   **Regeneración de Salts**: Se invalidaron todas las sesiones activas en navegadores de terceros generando claves de seguridad únicas desde la API de WordPress.

### C. Despliegue Limpio por SSH
1.  Se eliminaron **todos los archivos** del directorio remoto para asegurar que no quedaba ningún stager oculto en las carpetas.
2.  Se subieron los archivos limpios y la base de datos migrada a HTTPS sin usar plugins de migración vulnerables.
3.  Se aplicaron permisos Unix correctos de forma recursiva: `755` para directorios, `644` para archivos y `600` restrictivo para `wp-config.php`.

---

## 🕵️‍♂️ 5. Playbook de Auditoría: Detección de Malware en Producción

Para validar de forma regular el estado de otros sitios WordPress en el servidor de producción, se debe seguir este protocolo rápido de auditoría por SSH en el directorio de cada web:

### A. Escaneo de Directivas del Servidor
Comprueba si existen inyecciones en archivos de configuración local:
```bash
# Buscar directivas auto_prepend_file en archivos .user.ini
find . -maxdepth 3 -name ".user.ini" -exec grep -H "auto_prepend_file" {} \;
```

### B. Detección de Archivos Hexadecimales y Ocultos
Localiza backdoors ocultos en la estructura de contenidos:
```bash
# Buscar archivos PHP con patrones hexadecimales de 8 caracteres
find wp-content/ -type f -name "[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f].php" \
  -o -name ".[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f].php" 2>/dev/null
```

### C. Inspección de Dropins y Plugins 'Must-Use'
Verifica si se han inyectado cargadores estáticos de persistencia:
```bash
# Listar archivos mu-plugins y dropins sospechosos
ls -la wp-content/mu-plugins/hyper-toolkit-x.php wp-content/db.php wp-content/advanced-cache.php 2>/dev/null
```

### D. Chequeo de Base de Datos y Crons (WP-CLI)
Audita la persistencia lógica en base de datos:
```bash
# Buscar transients hexadecimales o sc_payload
wp db query "SELECT option_name FROM wp_options WHERE option_name LIKE '%sc_payload%' OR option_name REGEXP '^[0-9a-f]{12}$';"

# Listar y filtrar eventos cron maliciosos
wp cron event list | grep -E "sc_cron_fetch|j7coqs9ezlp9fztb4dww"
```

---

## 📈 6. Resultados Obtenidos

*   **Uso de CPU del Servidor:** Reducido de picos constantes del 99% a valores normales de reposo.
*   **Integridad del Sitio:** 100% libre de virus y backdoors de persistencia.
*   **SEO y Indexación:** El bloqueo 410 frena en seco el indexado de URLs de spam generadas por los atacantes.
*   **Diseño:** Portada recuperada, navegación funcional y retícula del blog alineada.
*   **Rendimiento:** Optimizado mediante eliminación de procesos parasitarios en segundo plano (cron jobs maliciosos).

---

## 📝 7. Conclusiones y Aprendizajes (Key Takeaways)

1.  **El origen real de la brecha**: El ataque inicial se facilitó a través de un plugin de gestión de archivos con vulnerabilidades RCE conocidas (dejando rastro en la tabla `wp_wpfm_backup`). Esto demuestra que desactivar o borrar un plugin desde el panel de WordPress a veces no es suficiente si persisten carpetas huérfanas, conectores PHP vulnerables o configuraciones residuales en base de datos.
2.  **La falacia de la "limpieza superficial"**: El malware moderno no solo infecta archivos de temas o plugins; crea mecanismos de persistencia en archivos de configuración del servidor (`.user.ini`) y drop-ins (`db.php`/`object-cache.php`). Intentar limpiar un sitio web simplemente borrando carpetas visibles o usando plugins automáticos es inútil si no se eliminan las directivas de ejecución PHP pre-ejecución.
3.  **Hacking Ético vs. Automatización**: Los escáneres automáticos de los proveedores de hosting a menudo pasan por alto archivos modificados legítimos del core o configuraciones personalizadas del servidor. Una auditoría manual exhaustiva por SSH es el único método 100% efectivo para localizar y extirpar puertas traseras encubiertas.
4.  **Blindaje a nivel perimetral**: Implementar defensas en el archivo `.htaccess` (como el bloqueo con respuesta `410 Gone` a URLs parametrizadas y la restricción de ejecución PHP en directorios de contenido) detiene los ataques antes de que lleguen a interactuar con WordPress, protegiendo los recursos de hardware del servidor.
