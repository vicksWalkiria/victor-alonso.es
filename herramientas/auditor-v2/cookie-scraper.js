import puppeteer from 'puppeteer';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { fileURLToPath } from 'node:url';

// Parse CLI Arguments
const args = process.argv.slice(2);
let targetUrl = '';
let auditId = '';

for (let i = 0; i < args.length; i++) {
  if (args[i].startsWith('--url=')) {
    targetUrl = args[i].split('=')[1];
  } else if (args[i] === '--url' && args[i + 1]) {
    targetUrl = args[i + 1];
    i++;
  }
  if (args[i].startsWith('--id=')) {
    auditId = args[i].split('=')[1];
  } else if (args[i] === '--id' && args[i + 1]) {
    auditId = args[i + 1];
    i++;
  }
}

if (!targetUrl || !auditId) {
  console.error('Error: Debes proporcionar --url y --id.');
  process.exit(1);
}

// Normalize URL
try {
  if (!targetUrl.startsWith('http://') && !targetUrl.startsWith('https://')) {
    targetUrl = 'https://' + targetUrl;
  }
  new URL(targetUrl);
} catch (e) {
  console.error('Error: URL inválida.');
  process.exit(1);
}

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Base directory for reports: /var/www/html/victor-alonso.es/data/reports/<id>
const reportsDir = path.resolve(__dirname, '../../data/reports', auditId);
if (!fs.existsSync(reportsDir)) {
  fs.mkdirSync(reportsDir, { recursive: true });
}

// Helpers for Status updates
function updateStatus(status, step, progress) {
  const statusPath = path.join(reportsDir, 'status.json');
  fs.writeFileSync(statusPath, JSON.stringify({
    status,
    step,
    progress,
    updated_at: Math.floor(Date.now() / 1000)
  }, null, 2));
}

// Known CMP selectors dictionary
const CMP_SELECTORS = {
  accept: [
    '.cky-btn-accept', // CookieYes
    '.cmplz-accept', // Complianz
    '#CybotCookiebotDialogBodyLevelButtonLevelOptinAllowAll', // Cookiebot
    '#onetrust-accept-btn-handler', // OneTrust
    '.iubenda-cs-accept-btn', // Iubenda
    '#didomi-notice-agree-button', // Didomi
    '[data-cookieconsent="accept"]', // Axeptio
    '#axeptio_btn_acceptAll', // Axeptio
    '.optanon-allow-all', // Optanon
    '#hs-eu-confirmation-button' // HubSpot
  ],
  reject: [
    '.cky-btn-reject', // CookieYes
    '.cmplz-deny', // Complianz
    '#CybotCookiebotDialogBodyButtonDecline', // Cookiebot
    '#onetrust-reject-all-handler', // OneTrust
    '.iubenda-cs-reject-btn', // Iubenda
    '#didomi-notice-disagree-button', // Didomi
    '[data-cookieconsent="decline"]', // Axeptio
    '#axeptio_btn_dismiss' // Axeptio
  ]
};

// Known Cookies DB for classification
const COOKIE_DB = {
  '_ga': { provider: 'Google Analytics', category: 'analítica', desc: 'ID único de usuario para estadísticas de Google Analytics (GA4)' },
  '_gid': { provider: 'Google Analytics', category: 'analítica', desc: 'ID único para registrar estadísticas de comportamiento' },
  '_gat': { provider: 'Google Analytics', category: 'analítica', desc: 'Limitación de tasa de peticiones de Google Analytics' },
  '_gcl_au': { provider: 'Google Ads', category: 'marketing', desc: 'Conversiones y publicidad personalizada de Google Ads' },
  '_fbp': { provider: 'Facebook Pixel', category: 'marketing', desc: 'Seguimiento de conversiones y audiencias de Meta/Facebook' },
  'fr': { provider: 'Facebook Pixel', category: 'marketing', desc: 'Cookie publicitaria principal de Facebook' },
  '_clck': { provider: 'Microsoft Clarity', category: 'analítica', desc: 'ID de sesión único para mapas de calor y grabaciones' },
  '_clsk': { provider: 'Microsoft Clarity', category: 'analítica', desc: 'Estado de sesión para mapas de calor' },
  'hjSession': { provider: 'Hotjar', category: 'analítica', desc: 'Sesión activa del usuario para análisis cualitativos' },
  '_hjid': { provider: 'Hotjar', category: 'analítica', desc: 'ID de usuario único de Hotjar' },
  'cookieyes-consent': { provider: 'CookieYes', category: 'técnica', desc: 'Estado de consentimiento de cookies del banner' },
  'elementor': { provider: 'Elementor', category: 'técnica', desc: 'Preferencias de diseño y visualización' },
  'wp-settings-': { provider: 'WordPress', category: 'técnica', desc: 'Configuración del panel de administración' },
  'wordpress_logged_in_': { provider: 'WordPress', category: 'técnica', desc: 'Sesión activa de usuario WordPress' }
};

function classifyCookie(name) {
  const lowerName = name.toLowerCase();
  for (const [key, data] of Object.entries(COOKIE_DB)) {
    if (lowerName.startsWith(key)) {
      return data;
    }
  }
  return { provider: 'Desconocido', category: 'desconocida', desc: 'Cookie sin catalogación estándar identificada.' };
}

function extractConsentMode(urlStr) {
  try {
    const parsed = new URL(urlStr);
    if (parsed.hostname.includes('google-analytics.com') || parsed.hostname.includes('analytics.google.com')) {
      const gcs = parsed.searchParams.get('gcs');
      const gcd = parsed.searchParams.get('gcd');
      if (gcs || gcd) {
        return { gcs, gcd };
      }
    }
  } catch (e) {}
  return null;
}

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

// Heuristic button search
async function findButton(page, type) {
  // 1. Check known selectors first
  const selectors = CMP_SELECTORS[type];
  for (const sel of selectors) {
    try {
      const el = await page.$(sel);
      if (el) {
        const visible = await page.evaluate(node => {
          const rect = node.getBoundingClientRect();
          return rect.width > 0 && rect.height > 0 && window.getComputedStyle(node).display !== 'none' && window.getComputedStyle(node).visibility !== 'hidden';
        }, el);
        if (visible) {
          return { element: el, text: sel, method: 'cmp_selector' };
        }
      }
    } catch (e) {}
  }

  // 2. Scan all candidate elements
  const candidates = await page.$$('button, a, [role="button"], div[class*="button" i], span[class*="button" i]');
  const acceptTerms = ['aceptar todo', 'aceptar', 'permitir', 'consiento', 'consentir', 'entendido', 'accept all', 'accept', 'allow', 'agree', 'ok'];
  const rejectTerms = ['rechazar todo', 'denegar', 'rechazar', 'solo necesarias', 'no aceptar', 'declinar', 'reject all', 'reject', 'deny', 'decline'];
  const terms = type === 'accept' ? acceptTerms : rejectTerms;

  for (const el of candidates) {
    try {
      const text = await page.evaluate(node => node.textContent.trim().toLowerCase(), el);
      const isMatch = terms.some(term => term.length <= 3 ? text === term : (text === term || text.includes(term)));
      if (!isMatch) continue;

      const visible = await page.evaluate(node => {
        const rect = node.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0 && window.getComputedStyle(node).display !== 'none' && window.getComputedStyle(node).visibility !== 'hidden';
      }, el);
      if (!visible) continue;

      const insideCookieContainer = await page.evaluate(node => {
        let parent = node.parentElement;
        while (parent) {
          const id = (parent.id || '').toLowerCase();
          const cls = (parent.className || '').toLowerCase();
          if (id.includes('cookie') || id.includes('consent') || id.includes('privacy') || id.includes('cmp') || id.includes('banner') ||
              cls.includes('cookie') || cls.includes('consent') || cls.includes('privacy') || cls.includes('cmp') || cls.includes('banner')) {
            return true;
          }
          parent = parent.parentElement;
        }
        return false;
      }, el);

      if (insideCookieContainer) {
        return { element: el, text, method: 'cookie_container' };
      }
    } catch (e) {}
  }

  // 3. Fallback: Any visible text match of reasonable length
  for (const el of candidates) {
    try {
      const text = await page.evaluate(node => node.textContent.trim().toLowerCase(), el);
      const isMatch = terms.some(term => term.length <= 3 ? text === term : (text === term || (text.length < 30 && text.includes(term))));
      if (!isMatch) continue;

      const visible = await page.evaluate(node => {
        const rect = node.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0 && window.getComputedStyle(node).display !== 'none' && window.getComputedStyle(node).visibility !== 'hidden';
      }, el);
      if (visible) {
        return { element: el, text, method: 'text_fallback' };
      }
    } catch (e) {}
  }

  return null;
}

// Main execution block
async function run() {
  updateStatus('running', 'Iniciando navegador real headless...', 10);
  
  let browser;
  const result = {
    id: auditId,
    url: targetUrl,
    final_url: targetUrl,
    status: 'failed',
    created_at: Math.floor(Date.now() / 1000),
    completed_at: null,
    phases: {
      initial: { cookies: [], localStorage: [], sessionStorage: [], requests: [], screenshot: 'fase1_inicio.png', consentMode: null },
      reject: { clicked: false, buttonText: null, cookies: [], requests: [], screenshot: 'fase2_rechazado.png', consentMode: null },
      accept: { clicked: false, buttonText: null, cookies: [], requests: [], screenshot: 'fase3_aceptado.png', consentMode: null }
    },
    summary: { score: 100, risk: 'bajo', findings: [] },
    errors: []
  };

  const basePrivateDir = '/home/aprendiz/web/victor-alonso.es/private';
  const chromeTmpBase = path.join(basePrivateDir, 'chrome-tmp');
  const safeAuditId = auditId.replace(/[^a-zA-Z0-9_-]/g, '');
  const userDataDir = path.join(chromeTmpBase, safeAuditId, 'profile');
  const cacheDir = path.join(chromeTmpBase, safeAuditId, 'cache');

  try {
    fs.mkdirSync(userDataDir, { recursive: true });
    fs.mkdirSync(cacheDir, { recursive: true });
  } catch (e) {
    console.error('Error creando dirs temporales de Chrome:', e);
  }

  try {
    browser = await puppeteer.launch({
      executablePath: '/usr/bin/chromium-browser',
      headless: true,
      dumpio: true,
      timeout: 60000,
      protocolTimeout: 60000,
      userDataDir,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--disable-software-rasterizer',
        '--disable-extensions',
        '--no-first-run',
        '--no-default-browser-check',
        `--disk-cache-dir=${cacheDir}`,
        `--user-data-dir=${userDataDir}`
      ]
    });

    const page = await browser.newPage();
    page.setDefaultNavigationTimeout(60000);
    page.setDefaultTimeout(60000);

    // Set desktop User-Agent and spoof webdriver to bypass bot detection in CMPs
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
    await page.evaluateOnNewDocument(() => {
      Object.defineProperty(navigator, 'webdriver', {
        get: () => undefined
      });
    });

    // Enable request interception to block heavy assets (images, fonts, media)
    await page.setRequestInterception(true);
    
    // Store request log for each phase
    let capturedRequests = [];
    page.on('request', req => {
      const type = req.resourceType();
      const urlStr = req.url();
      
      // Log external connections (excluding target domain)
      try {
        const targetDomain = new URL(targetUrl).hostname.replace('www.', '');
        const reqDomain = new URL(urlStr).hostname.replace('www.', '');
        if (reqDomain !== targetDomain && reqDomain !== 'localhost' && !reqDomain.includes('127.0.0.1')) {
          capturedRequests.push({ url: urlStr, type });
        }
      } catch (e) {}

      if (['image', 'font', 'media'].includes(type)) {
        req.abort();
      } else {
        req.continue();
      }
    });

    // ==========================================
    // FASE 1: Carga Inicial (Sin Consentimiento)
    // ==========================================
    console.log('[DEBUG] Starting Phase 1');
    updateStatus('running', 'Cargando web en estado limpio (sin consentimiento)...', 25);
    capturedRequests = [];
    
    try {
      console.log('[DEBUG] Going to URL...');
      const response = await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: 45000 });
      console.log('[DEBUG] URL loaded');
      result.final_url = page.url();
    } catch (gotoErr) {
      console.warn('Timeout during initial goto, proceeding anyway...');
      result.final_url = page.url();
    }
    
    console.log('[DEBUG] Waiting 6s for JS...');
    // Wait a brief period for deferred JS to run
    await new Promise(r => setTimeout(r, 6000));
    
    console.log('[DEBUG] Capturing cookies...');
    // Capture Phase 1 Data
    const initialCookies = await page.cookies();
    result.phases.initial.cookies = initialCookies.map(c => ({
      name: c.name,
      value: c.value,
      domain: c.domain,
      path: c.path,
      expires: c.expires,
      classification: classifyCookie(c.name)
    }));

    console.log('[DEBUG] Capturing localStorage...');
    result.phases.initial.localStorage = await page.evaluate(() => {
      const items = [];
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        items.push({ key, value: localStorage.getItem(key) });
      }
      return items;
    });

    console.log('[DEBUG] Capturing sessionStorage...');
    result.phases.initial.sessionStorage = await page.evaluate(() => {
      const items = [];
      for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        items.push({ key, value: sessionStorage.getItem(key) });
      }
      return items;
    });

    result.phases.initial.requests = [...capturedRequests];

    console.log('[DEBUG] Checking consent mode...');
    // Check Consent Mode parameters in GA requests
    for (const req of capturedRequests) {
      const cm = extractConsentMode(req.url);
      if (cm) {
        result.phases.initial.consentMode = cm;
        break;
      }
    }

    console.log('[DEBUG] Taking Phase 1 screenshot...');
    // Capture screenshot
    await page.screenshot({ path: path.join(reportsDir, 'fase1_inicio.png') });

    // ==========================================
    // FASE 2: Simulación de Clic en "Rechazar"
    // ==========================================
    console.log('[DEBUG] Starting Phase 2');
    updateStatus('running', 'Buscando y simulando clic en "Rechazar cookies"...', 50);
    capturedRequests = [];

    console.log('[DEBUG] Finding reject button...');
    const rejectBtn = await findButton(page, 'reject');
    if (rejectBtn) {
      console.log('[DEBUG] Reject button found:', rejectBtn.text);
      result.phases.reject.clicked = true;
      result.phases.reject.buttonText = rejectBtn.text;

      // Click and wait
      console.log('[DEBUG] Clicking reject button...');
      await rejectBtn.element.click();
      console.log('[DEBUG] Waiting 4s after click...');
      await new Promise(r => setTimeout(r, 4000));

      const rejectCookies = await page.cookies();
      result.phases.reject.cookies = rejectCookies.map(c => ({
        name: c.name,
        value: c.value,
        domain: c.domain,
        path: c.path,
        expires: c.expires,
        classification: classifyCookie(c.name)
      }));
      result.phases.reject.requests = [...capturedRequests];

      for (const req of capturedRequests) {
        const cm = extractConsentMode(req.url);
        if (cm) {
          result.phases.reject.consentMode = cm;
          break;
        }
      }

      await page.screenshot({ path: path.join(reportsDir, 'fase2_rechazado.png') });
    } else {
      result.phases.reject.clicked = false;
      result.phases.reject.buttonText = null;
      // Copy initial state to represent no action taken
      result.phases.reject.cookies = [...result.phases.initial.cookies];
      result.phases.reject.requests = [];
    }

    // ==========================================
    // FASE 3: Simulación de Clic en "Aceptar"
    // ==========================================
    updateStatus('running', 'Cargando de nuevo y simulando clic en "Aceptar cookies"...', 75);
    capturedRequests = [];

    // Clear cookies/storage to start fresh
    const client = await page.target().createCDPSession();
    await client.send('Network.clearBrowserCookies');
    await client.send('Network.clearBrowserCache');
    await page.evaluate(() => {
      localStorage.clear();
      sessionStorage.clear();
    });

    try {
      await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: 45000 });
    } catch (gotoErr) {}
    await new Promise(r => setTimeout(r, 4000));

    const acceptBtn = await findButton(page, 'accept');
    if (acceptBtn) {
      result.phases.accept.clicked = true;
      result.phases.accept.buttonText = acceptBtn.text;

      await acceptBtn.element.click();
      await new Promise(r => setTimeout(r, 4000));

      const acceptCookies = await page.cookies();
      result.phases.accept.cookies = acceptCookies.map(c => ({
        name: c.name,
        value: c.value,
        domain: c.domain,
        path: c.path,
        expires: c.expires,
        classification: classifyCookie(c.name)
      }));
      result.phases.accept.requests = [...capturedRequests];

      for (const req of capturedRequests) {
        const cm = extractConsentMode(req.url);
        if (cm) {
          result.phases.accept.consentMode = cm;
          break;
        }
      }

      await page.screenshot({ path: path.join(reportsDir, 'fase3_aceptado.png') });
    } else {
      result.phases.accept.clicked = false;
      result.phases.accept.buttonText = null;
    }

    // ==========================================
    // EVALUACIÓN DE SCORING Y DIAGNÓSTICO
    // ==========================================
    updateStatus('running', 'Evaluando resultados y calculando puntuación...', 90);
    
    let score = 100;
    const findings = [];

    // Rule 1: Marketing/Analytics cookies loaded BEFORE consent
    const initialTrackers = result.phases.initial.cookies.filter(
      c => ['marketing', 'analítica'].includes(c.classification.category)
    );
    if (initialTrackers.length > 0) {
      score -= 40;
      findings.push({
        type: 'initial_leak',
        severity: 'alto',
        message: `Se inyectaron ${initialTrackers.length} cookie(s) de analítica/marketing antes de recibir consentimiento (${initialTrackers.map(c => c.name).join(', ')}).`
      });
    }

    // Rule 2: Marketing/Analytics requests loaded BEFORE consent
    const initialNetworkTrackers = result.phases.initial.requests.filter(req => {
      const url = req.url.toLowerCase();
      return url.includes('google-analytics.com') || url.includes('analytics.google.com') ||
             url.includes('facebook.com/tr') || url.includes('connect.facebook.net') ||
             url.includes('hotjar.com') || url.includes('clarity.ms');
    });
    if (initialNetworkTrackers.length > 0) {
      score -= 30;
      findings.push({
        type: 'initial_request_leak',
        severity: 'alto',
        message: 'Se realizaron conexiones a servidores de seguimiento (Google Analytics/Meta Pixel) antes del consentimiento.'
      });
    }

    // Rule 3: Reject compliance
    if (result.phases.reject.clicked) {
      const postRejectTrackers = result.phases.reject.cookies.filter(
        c => ['marketing', 'analítica'].includes(c.classification.category)
      );
      if (postRejectTrackers.length > 0) {
        score -= 30;
        findings.push({
          type: 'reject_leak',
          severity: 'alto',
          message: `Infracción grave: Siguen activas cookies de analítica/marketing tras pulsar "Rechazar" (${postRejectTrackers.map(c => c.name).join(', ')}).`
        });
      }
    } else {
      findings.push({
        type: 'no_reject_button',
        severity: 'medio',
        message: 'No se ha detectado el botón "Rechazar" en el banner, lo cual no cumple con las directrices de la AEPD.'
      });
    }

    // Clean score bounds
    result.summary.score = Math.max(0, score);
    result.summary.risk = result.summary.score >= 90 ? 'bajo' : (result.summary.score >= 50 ? 'medio' : 'alto');
    result.summary.findings = findings;
    result.status = 'done';

  } catch (err) {
    console.error('Error durante la auditoría:', err);
    result.status = 'failed';
    result.errors.push(err.message);
  } finally {
    result.completed_at = Math.floor(Date.now() / 1000);
    if (browser) {
      try {
        await browser.close();
      } catch (closeErr) {
        console.error('Error closing browser:', closeErr.message);
      }
    }

    // Cleanup Chrome temp directories
    try {
      const basePrivateDir = '/home/aprendiz/web/victor-alonso.es/private';
      const chromeTmpBase = path.join(basePrivateDir, 'chrome-tmp');
      const safeAuditId = auditId.replace(/[^a-zA-Z0-9_-]/g, '');
      const chromeTmpDir = path.join(chromeTmpBase, safeAuditId);
      
      if (fs.existsSync(chromeTmpDir)) {
        fs.rmSync(chromeTmpDir, { recursive: true, force: true });
      }
    } catch (rmErr) {
      console.error('Error cleaning up Chrome temp dirs:', rmErr.message);
    }
    
    // Save output report
    try {
      const reportPath = path.join(reportsDir, 'result.json');
      fs.writeFileSync(reportPath, JSON.stringify(result, null, 2));
    } catch (writeErr) {
      console.error('Error writing result.json:', writeErr.message);
    }

    // Mark completion in status.json
    try {
      updateStatus(result.status, result.status === 'done' ? 'Auditoría completada con éxito.' : 'Error durante el análisis.', 100);
    } catch (statusErr) {}

    // Delete concurrency lock file if it exists
    const lockPath = path.resolve(__dirname, '../../data/reports/locks', `${auditId}.lock`);
    if (fs.existsSync(lockPath)) {
      try {
        fs.unlinkSync(lockPath);
      } catch (e) {}
    }
  }
}

run();
