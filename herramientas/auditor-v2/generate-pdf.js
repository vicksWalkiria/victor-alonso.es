import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function getExecutablePath() {
  if (process.env.CHROMIUM_PATH) return process.env.CHROMIUM_PATH;
  const platform = os.platform();
  if (platform === 'linux') {
    const paths = ['/usr/bin/chromium-browser', '/usr/bin/chromium', '/usr/bin/google-chrome', '/usr/bin/google-chrome-stable'];
    for (const p of paths) if (fs.existsSync(p)) return p;
  } else if (platform === 'darwin') {
    const paths = ['/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', '/Applications/Chromium.app/Contents/MacOS/Chromium'];
    for (const p of paths) if (fs.existsSync(p)) return p;
  }
  return 'chrome';
}

const args = process.argv.slice(2);
let auditId = '';
let tool = 'cookies';

for (let i = 0; i < args.length; i++) {
  if (args[i].startsWith('--id=')) {
    auditId = args[i].split('=')[1];
  } else if (args[i] === '--id' && args[i + 1]) {
    auditId = args[i + 1];
    i++;
  } else if (args[i].startsWith('--tool=')) {
    tool = args[i].split('=')[1];
  } else if (args[i] === '--tool' && args[i + 1]) {
    tool = args[i + 1];
    i++;
  }
}

if (!auditId) {
  console.error('Error: Debes proporcionar --id.');
  process.exit(1);
}

const reportsDir = path.resolve(__dirname, '../../data/reports');
let jsonPath;
let pdfPath;

if (tool === 'logs') {
  const reportDir = path.join(reportsDir, 'logs');
  jsonPath = path.join(reportDir, `${auditId}.json`);
  pdfPath = path.join(reportDir, `${auditId}.pdf`);
} else if (tool === 'entidades') {
  const reportDir = path.join(reportsDir, 'entidades');
  jsonPath = path.join(reportDir, `${auditId}.json`);
  pdfPath = path.join(reportDir, `${auditId}.pdf`);
} else {
  const reportDir = path.join(reportsDir, auditId);
  jsonPath = path.join(reportDir, 'result.json');
  pdfPath = path.join(reportDir, 'informe.pdf');
}

if (!fs.existsSync(jsonPath)) {
  console.error('Error: No se encontró el archivo JSON del reporte en ' + jsonPath);
  process.exit(1);
}

const rawData = fs.readFileSync(jsonPath, 'utf8');
const reportData = JSON.parse(rawData);

function buildCookiesHtml(reportData) {
  const score = reportData.summary.score || 0;
  let gradeColor = '#e74c3c';
  let gradeLetter = 'F';
  if (score >= 90) { gradeColor = '#2ecc71'; gradeLetter = 'A'; }
  else if (score >= 70) { gradeColor = '#f1c40f'; gradeLetter = 'B'; }
  else if (score >= 50) { gradeColor = '#e67e22'; gradeLetter = 'C'; }

  const p_init = reportData.phases.initial;
  const p_rej = reportData.phases.reject;
  const p_acc = reportData.phases.accept;

  const formatCookies = (cookies) => (!cookies || cookies.length === 0) ? 'Ninguna' : cookies.map(c => c.name).join(', ');

  return `
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Auditoría RGPD</title>
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 40px; color: #111; background: #fff; }
    .header { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    .logo { background: #E8681A; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; margin-right: 15px; }
    .title h1 { margin: 0; font-size: 24px; color: #E8681A; }
    .title h2 { margin: 5px 0 0 0; font-size: 16px; color: #666; font-weight: 400; }
    .summary-card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; background: #f9fafb; }
    .score-circle { width: 80px; height: 80px; border-radius: 50%; background: ${gradeColor}; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; }
    .url { font-size: 18px; font-weight: bold; margin-bottom: 10px; word-break: break-all; }
    .section { margin-bottom: 30px; }
    h3 { font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 5px; color: #333; }
    .phase-card { border: 1px solid #eee; border-left: 4px solid #E8681A; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fff; }
    .phase-title { font-weight: bold; color: #E8681A; margin-bottom: 10px; font-size: 15px; }
    .label { font-weight: 600; color: #555; }
    .value { margin-left: 5px; font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 3px; color: #111; font-size: 13px; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #fff; }
    .badge-success { background: #2ecc71; }
    .badge-warning { background: #f1c40f; color: #111; }
    .badge-danger { background: #e74c3c; }
    .findings { list-style-type: none; padding: 0; }
    .findings li { padding: 10px; border-bottom: 1px solid #eee; font-size: 14px; }
    .findings li:last-child { border-bottom: none; }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">VA</div>
    <div class="title">
      <h1>Víctor Alonso SEO</h1>
      <h2>Informe Automático de Cumplimiento RGPD</h2>
    </div>
  </div>
  <div class="summary-card">
    <div>
      <div class="url">URL Analizada: <span style="font-weight:normal;">${reportData.url}</span></div>
      <div>Fecha de auditoría: ${new Date().toLocaleString('es-ES')}</div>
    </div>
    <div style="text-align:center;">
      <div class="score-circle">${gradeLetter}</div>
      <div style="margin-top:8px; font-weight:bold; color:${gradeColor};">${score}/100</div>
    </div>
  </div>
  <div class="section">
    <h3>Infracciones y Advertencias</h3>
    <ul class="findings">
      ${reportData.summary.findings.length === 0 ? 
        '<li><span class="badge badge-success">✓</span> No se han detectado problemas críticos.</li>' : 
        reportData.summary.findings.map(f => {
          let bClass = f.severity === 'alto' ? 'badge-danger' : (f.severity === 'medio' ? 'badge-warning' : 'badge-success');
          return `<li><span class="badge ${bClass}">${f.severity.toUpperCase()}</span> ${f.message}</li>`;
        }).join('')
      }
    </ul>
  </div>
  <div class="section">
    <h3>Auditoría por Fases (Simulación Dinámica)</h3>
    <div class="phase-card">
      <div class="phase-title">Fase 1: Carga Inicial (Sin consentimiento)</div>
      <div><span class="label">Cookies detectadas:</span> <strong>${p_init.cookies.length}</strong></div>
      ${p_init.cookies.length > 0 ? `<div style="margin-top:5px;"><span class="value">${formatCookies(p_init.cookies)}</span></div>` : ''}
      ${p_init.consentMode ? `<div style="margin-top:10px;"><span class="label">Google Consent Mode:</span> <span class="value">Detectado</span></div>` : ''}
    </div>
    <div class="phase-card">
      <div class="phase-title">Fase 2: Tras pulsar "Rechazar todo"</div>
      ${!p_rej.clicked ? 
        `<div><span class="badge badge-warning">⚠️</span> No se ha detectado el botón para rechazar cookies.</div>` : 
        `<div><span class="badge badge-success">✓</span> Botón "${p_rej.buttonText}" pulsado.</div>
         <div style="margin-top:10px;"><span class="label">Cookies post-rechazo:</span> <strong>${p_rej.cookies.length}</strong></div>
         ${p_rej.cookies.length > 0 ? `<div style="margin-top:5px;"><span class="value">${formatCookies(p_rej.cookies)}</span></div>` : ''}
        `
      }
    </div>
    <div class="phase-card">
      <div class="phase-title">Fase 3: Tras pulsar "Aceptar todo"</div>
      ${!p_acc.clicked ? 
        `<div><span class="badge badge-warning">⚠️</span> No se ha detectado el botón para aceptar cookies.</div>` : 
        `<div><span class="badge badge-success">✓</span> Botón "${p_acc.buttonText}" pulsado.</div>
         <div style="margin-top:10px;"><span class="label">Cookies cargadas:</span> <strong>${p_acc.cookies.length}</strong></div>
         ${p_acc.cookies.length > 0 ? `<div style="margin-top:5px;"><span class="value">${formatCookies(p_acc.cookies)}</span></div>` : ''}
        `
      }
    </div>
  </div>
  <div style="margin-top: 40px; font-size: 12px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 10px;">
    Generado por la herramienta de Auditoría de victor-alonso.es. Este documento no constituye asesoramiento legal.
  </div>
</body>
</html>
`;
}

function buildLogsHtml(reportData) {
  const issues = reportData.seo_issues || [];
  
  return `
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Auditoría de Logs</title>
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 40px; color: #111; background: #fff; }
    .header { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    .logo { background: #3498db; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; margin-right: 15px; }
    .title h1 { margin: 0; font-size: 24px; color: #3498db; }
    .title h2 { margin: 5px 0 0 0; font-size: 16px; color: #666; font-weight: 400; }
    .summary-card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; background: #f9fafb; }
    .stat-box { text-align: center; border-left: 1px solid #ddd; padding-left: 20px; }
    .stat-box:first-child { border-left: none; padding-left: 0; text-align: left; }
    .stat-value { font-size: 24px; font-weight: bold; color: #111; margin-top: 5px; }
    .stat-label { font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; }
    .section { margin-bottom: 30px; }
    h3 { font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 5px; color: #333; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #fff; margin-right: 8px; }
    .badge-danger { background: #e74c3c; }
    .badge-warning { background: #f1c40f; color: #111; }
    .badge-info { background: #3498db; }
    .findings { list-style-type: none; padding: 0; }
    .findings li { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; line-height: 1.5; }
    .findings li:last-child { border-bottom: none; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .list-card { border: 1px solid #eee; border-top: 4px solid #3498db; padding: 15px; border-radius: 4px; background: #fff; }
    .list-title { font-weight: bold; color: #3498db; margin-bottom: 10px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #f5f5f5; }
    th { color: #666; font-weight: 600; }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">VA</div>
    <div class="title">
      <h1>Víctor Alonso SEO</h1>
      <h2>Informe de Crawl Budget y Logs Técnicos</h2>
    </div>
  </div>
  
  <div class="summary-card">
    <div class="stat-box">
      <div class="stat-label">Archivo Analizado</div>
      <div class="stat-value" style="font-size:16px;">${reportData.source_name || 'Desconocido'}</div>
      <div style="font-size:12px; color:#888; margin-top:4px;">${reportData.date_start} a ${reportData.date_end}</div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Líneas</div>
      <div class="stat-value">${reportData.parsed_lines || 0}</div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Ancho de Banda</div>
      <div class="stat-value">${reportData.bandwidth_mb || 0} MB</div>
    </div>
    <div class="stat-box">
      <div class="stat-label">IPs Únicas</div>
      <div class="stat-value">${reportData.unique_ips_count || 0}</div>
    </div>
  </div>

  <div class="section">
    <h3>Anomalías y Oportunidades de Rastreo</h3>
    <ul class="findings">
      ${issues.length === 0 ? 
        '<li><span class="badge badge-info">✓</span> No se han detectado problemas críticos en los patrones de rastreo.</li>' : 
        issues.map(f => {
          let bClass = f.type === 'error' ? 'badge-danger' : 'badge-warning';
          return `<li><span class="badge ${bClass}">${f.type.toUpperCase()}</span> <strong>${f.title}:</strong> ${f.desc}</li>`;
        }).join('')
      }
    </ul>
  </div>

  <div class="section grid">
    <div class="list-card">
      <div class="list-title">Top 10 URLs Más Rastreadas (Sin Estáticos)</div>
      <table>
        <tr><th>URL</th><th>Peticiones</th></tr>
        ${reportData.top_urls_no_static ? Object.entries(reportData.top_urls_no_static).slice(0, 10).map(([url, count]) => `<tr><td style="word-break: break-all;">${url}</td><td>${count}</td></tr>`).join('') : ''}
      </table>
    </div>
    
    <div class="list-card">
      <div class="list-title">Top 10 Bots Detectados</div>
      <table>
        <tr><th>Agente</th><th>Peticiones</th></tr>
        ${reportData.top_bots ? Object.entries(reportData.top_bots).slice(0, 10).map(([bot, count]) => `<tr><td style="word-break: break-all;">${bot}</td><td>${count}</td></tr>`).join('') : ''}
      </table>
    </div>
  </div>

  <div class="section list-card" style="border-top-color: #e74c3c;">
    <div class="list-title" style="color: #e74c3c;">Top 10 Errores 404 (Fugas de Crawl Budget)</div>
    <table>
      <tr><th>URL Extraviada</th><th>Peticiones 404</th></tr>
      ${reportData.top_404s && Object.keys(reportData.top_404s).length > 0 ? 
          Object.entries(reportData.top_404s).slice(0, 10).map(([url, count]) => `<tr><td style="word-break: break-all;">${url}</td><td>${count}</td></tr>`).join('') : 
          '<tr><td colspan="2" style="color:#2ecc71;">No se registraron errores 404 significativos.</td></tr>'
      }
    </table>
  </div>

  <div style="margin-top: 40px; font-size: 12px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 10px;">
    Generado por la herramienta de Auditoría de victor-alonso.es. Este informe analiza datos técnicos de peticiones servidor para consultoría SEO.
  </div>
</body>
</html>
`;
}
function buildEntidadesHtml(reportData) {
  const formatList = (list, isTriple = false) => {
    if (!list || list.length === 0) return '<li>No se detectaron datos.</li>';
    if (isTriple) {
      return list.map(item => `<li><span class="badge badge-info">${item.subject}</span> <em>${item.predicate}</em> <span class="badge badge-warning" style="color:#111">${item.object}</span></li>`).join('');
    }
    return list.map(item => `<li><span class="badge" style="background:#444;">${item.type}</span> <strong>${item.name}</strong></li>`).join('');
  };

  return `
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Análisis Semántico</title>
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 40px; color: #111; background: #fff; }
    .header { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    .logo { background: #9b59b6; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; margin-right: 15px; }
    .title h1 { margin: 0; font-size: 24px; color: #9b59b6; }
    .title h2 { margin: 5px 0 0 0; font-size: 16px; color: #666; font-weight: 400; }
    .summary-card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px; background: #f9fafb; }
    .url { font-size: 16px; font-weight: bold; margin-bottom: 5px; word-break: break-all; }
    .section { margin-bottom: 30px; }
    h3 { font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 5px; color: #333; margin-bottom: 15px; }
    .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #fff; margin-right: 8px; margin-bottom: 4px; }
    .badge-info { background: #3498db; }
    .badge-warning { background: #f1c40f; color: #111; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .list-card { border: 1px solid #eee; padding: 15px; border-radius: 4px; background: #fff; }
    .findings { list-style-type: none; padding: 0; margin: 0; font-size: 13px;}
    .findings li { padding: 8px; border-bottom: 1px solid #eee; line-height: 1.4; }
    .findings li:last-child { border-bottom: none; }
    .graph-img { max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 8px; margin-top: 15px; background:#060911; }
    .gap-box { text-align:center; padding: 15px; border-radius: 8px; background: #f9fafb; border: 1px solid #ddd; }
    .gap-value { font-size: 28px; font-weight: bold; margin-top: 5px; }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">VA</div>
    <div class="title">
      <h1>Víctor Alonso SEO</h1>
      <h2>Informe de Extracción Semántica y Knowledge Graph</h2>
    </div>
  </div>
  
  <div class="summary-card">
    <div class="url">URL Analizada: <span style="font-weight:normal;">${reportData.url1}</span></div>
    ${reportData.url2 ? `<div class="url" style="margin-top:10px; color:#e74c3c;">URL Competidor: <span style="font-weight:normal;">${reportData.url2}</span></div>` : ''}
    <div style="font-size:12px; color:#888; margin-top:10px;">Fecha del análisis: ${reportData.date}</div>
  </div>

  <div class="section">
    <h3>Grafo de Conocimiento Extraído</h3>
    <p style="font-size: 14px; color: #555;">Representación visual de las entidades y relaciones lógicas (triples) detectadas en el contenido de la URL principal.</p>
    ${reportData.graphImage ? `<img src="${reportData.graphImage}" class="graph-img" alt="Grafo Semántico">` : '<p>No se proporcionó imagen del grafo.</p>'}
  </div>

  ${reportData.url2 ? `
  <div class="section">
    <h3>Análisis de Brecha Semántica (Semantic Gap)</h3>
    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
      <div class="gap-box" style="flex:1;">
        <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight:bold;">Cobertura Estimada</div>
        <div class="gap-value" style="color: #e8681a;">${reportData.gapStats.score}%</div>
      </div>
      <div class="gap-box" style="flex:1;">
        <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight:bold;">Entidades Comunes</div>
        <div class="gap-value" style="color: #333;">${reportData.gapStats.common}</div>
      </div>
      <div class="gap-box" style="flex:1;">
        <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight:bold;">Brecha (Faltan)</div>
        <div class="gap-value" style="color: #e74c3c;">${reportData.gapStats.missing}</div>
      </div>
    </div>
  </div>
  ` : ''}

  <div class="section grid">
    <div class="list-card" style="border-top: 4px solid #9b59b6;">
      <h4 style="margin:0 0 15px 0; color:#9b59b6;">Entidades Clave Detectadas (${reportData.entities ? reportData.entities.length : 0})</h4>
      <ul class="findings">
        ${formatList(reportData.entities?.slice(0, 40))}
        ${reportData.entities?.length > 40 ? `<li><em>...y ${reportData.entities.length - 40} entidades más</em></li>` : ''}
      </ul>
    </div>
    
    <div class="list-card" style="border-top: 4px solid #3498db;">
      <h4 style="margin:0 0 15px 0; color:#3498db;">Triples Lógicos Principales (${reportData.triples ? reportData.triples.length : 0})</h4>
      <ul class="findings">
        ${formatList(reportData.triples?.slice(0, 30), true)}
        ${reportData.triples?.length > 30 ? `<li><em>...y ${reportData.triples.length - 30} relaciones más</em></li>` : ''}
      </ul>
    </div>
  </div>

  <div style="margin-top: 40px; font-size: 12px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 10px;">
    Generado por la <a href="https://www.victor-alonso.es/herramientas/extractor-entidades/" style="color: #9b59b6; text-decoration: none; font-weight: bold;">Herramienta de Extracción Semántica NLP</a> de victor-alonso.es.
  </div>
</body>
</html>
  `;
}

let htmlContent = '';
if (tool === 'logs') {
  htmlContent = buildLogsHtml(reportData);
} else if (tool === 'entidades') {
  htmlContent = buildEntidadesHtml(reportData);
} else {
  htmlContent = buildCookiesHtml(reportData);
}

(async () => {
  try {
    const browser = await puppeteer.launch({
      executablePath: getExecutablePath(),
      args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--headless=new']
    });

    const page = await browser.newPage();
    await page.setContent(htmlContent, { waitUntil: 'networkidle0' });

    await page.pdf({
      path: pdfPath,
      format: 'A4',
      margin: { top: '0', bottom: '0', left: '0', right: '0' },
      printBackground: true
    });

    await browser.close();
    console.log(`PDF_SUCCESS:${pdfPath}`);
  } catch (error) {
    console.error('Error Puppeteer:', error);
    process.exit(1);
  }
})();
