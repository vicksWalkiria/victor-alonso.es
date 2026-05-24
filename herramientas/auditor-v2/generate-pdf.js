import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Find appropriate Chromium executable path dynamically
function getExecutablePath() {
  if (process.env.CHROMIUM_PATH) {
    return process.env.CHROMIUM_PATH;
  }
  const platform = os.platform();
  if (platform === 'linux') {
    const paths = [
      '/usr/bin/chromium-browser',
      '/usr/bin/chromium',
      '/usr/bin/google-chrome',
      '/usr/bin/google-chrome-stable'
    ];
    for (const p of paths) {
      if (fs.existsSync(p)) return p;
    }
  } else if (platform === 'darwin') {
    const paths = [
      '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
      '/Applications/Chromium.app/Contents/MacOS/Chromium'
    ];
    for (const p of paths) {
      if (fs.existsSync(p)) return p;
    }
  }
  return 'chrome'; // Fallback
}

// Parse CLI Arguments
const args = process.argv.slice(2);
let auditId = '';

for (let i = 0; i < args.length; i++) {
  if (args[i].startsWith('--id=')) {
    auditId = args[i].split('=')[1];
  } else if (args[i] === '--id' && args[i + 1]) {
    auditId = args[i + 1];
    i++;
  }
}

if (!auditId) {
  console.error('Error: Debes proporcionar --id.');
  process.exit(1);
}

const reportsDir = path.resolve(__dirname, '../../data/reports');
const reportDir = path.join(reportsDir, auditId);
const jsonPath = path.join(reportDir, 'result.json');
const pdfPath = path.join(reportDir, 'informe.pdf');

if (!fs.existsSync(jsonPath)) {
  console.error('Error: No se encontró el archivo JSON del reporte.');
  process.exit(1);
}

const rawData = fs.readFileSync(jsonPath, 'utf8');
const reportData = JSON.parse(rawData);

// Determinar el score y color (igual que en PHP)
const score = reportData.summary.score || 0;
let gradeClass = 'grade-f';
let gradeColor = '#e74c3c';
let gradeLetter = 'F';
if (score >= 90) { gradeClass = 'grade-a'; gradeColor = '#2ecc71'; gradeLetter = 'A'; }
else if (score >= 70) { gradeClass = 'grade-b'; gradeColor = '#f1c40f'; gradeLetter = 'B'; }
else if (score >= 50) { gradeClass = 'grade-c'; gradeColor = '#e67e22'; gradeLetter = 'C'; }

// Extraer fases
const p_init = reportData.phases.initial;
const p_rej = reportData.phases.reject;
const p_acc = reportData.phases.accept;

const formatCookies = (cookies) => {
  if (!cookies || cookies.length === 0) return 'Ninguna';
  return cookies.map(c => c.name).join(', ');
};

const htmlContent = `
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Auditoría RGPD</title>
  <style>
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 40px;
      color: #111;
      background: #fff;
    }
    .header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 2px solid #eee;
      padding-bottom: 20px;
    }
    .logo {
      background: #E8681A;
      color: #fff;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 20px;
      margin-right: 15px;
    }
    .title h1 {
      margin: 0;
      font-size: 24px;
      color: #E8681A;
    }
    .title h2 {
      margin: 5px 0 0 0;
      font-size: 16px;
      color: #666;
      font-weight: 400;
    }
    .summary-card {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f9fafb;
    }
    .score-circle {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: ${gradeColor};
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      font-weight: bold;
    }
    .url {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 10px;
      word-break: break-all;
    }
    .section {
      margin-bottom: 30px;
    }
    h3 {
      font-size: 18px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 5px;
      color: #333;
    }
    .phase-card {
      border: 1px solid #eee;
      border-left: 4px solid #E8681A;
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 4px;
      background: #fff;
    }
    .phase-title {
      font-weight: bold;
      color: #E8681A;
      margin-bottom: 10px;
      font-size: 15px;
    }
    .label {
      font-weight: 600;
      color: #555;
    }
    .value {
      margin-left: 5px;
      font-family: monospace;
      background: #f3f4f6;
      padding: 2px 6px;
      border-radius: 3px;
      color: #111;
      font-size: 13px;
    }
    .badge {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
      color: #fff;
    }
    .badge-success { background: #2ecc71; }
    .badge-warning { background: #f1c40f; color: #111; }
    .badge-danger { background: #e74c3c; }
    
    .findings {
      list-style-type: none;
      padding: 0;
    }
    .findings li {
      padding: 10px;
      border-bottom: 1px solid #eee;
      font-size: 14px;
    }
    .findings li:last-child {
      border-bottom: none;
    }
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
