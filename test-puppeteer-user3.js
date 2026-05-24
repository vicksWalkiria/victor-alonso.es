import puppeteer from 'puppeteer-core';

(async () => {
  const profileDir = '/home/aprendiz/web/victor-alonso.es/public_html/data/reports/puppeteer_profiles_test_manual3';
  const cacheDir = '/home/aprendiz/web/victor-alonso.es/public_html/data/reports/puppeteer_cache_test_manual3';

  try {
      console.log('Launching browser with DIRECT BINARY (NO PIPE)...');
      const browser = await puppeteer.launch({
        executablePath: '/usr/lib/chromium-browser/chromium-browser',
        headless: true,
        dumpio: true,
        timeout: 60000,
        protocolTimeout: 60000,
        userDataDir: profileDir,
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
          `--user-data-dir=${profileDir}`
        ]
      });

      console.log('Browser launched!');
      const page = await browser.newPage();
      await page.goto('https://example.com', {
        waitUntil: 'domcontentloaded',
        timeout: 15000
      });

      console.log('Title: ', await page.title());

      await browser.close();
  } catch (err) {
      console.error('Test failed:', err);
  }
})();
