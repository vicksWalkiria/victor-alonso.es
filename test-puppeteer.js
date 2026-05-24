import puppeteer from 'puppeteer-core';
(async () => {
  try {
    console.log('Launching browser...');
    const browser = await puppeteer.launch({
      executablePath: '/usr/lib/chromium-browser/chromium-browser',
      pipe: true,
      userDataDir: '/home/aprendiz/test_chrome_profile',
      args: [
        '--remote-debugging-pipe',
        '--no-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu'
      ],
      dumpio: true,
      timeout: 30000
    });
    console.log('Browser launched successfully!');
    const page = await browser.newPage();
    console.log('Page created. Navigating to example.com...');
    await page.goto('https://example.com');
    console.log('Success! Closing browser...');
    await browser.close();
  } catch (err) {
    console.error('Test failed:', err);
  }
})();
