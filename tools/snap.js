// Quick QA capture: node snap.js <url-path#route> <out.png> [width] [height] [mobile] [actions-json]
const puppeteer = require('puppeteer-core');
const CHROME = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = process.env.BASE_URL || 'http://localhost:5173';

(async () => {
  const [route, out, w = 1440, h = 900, mobile = '0', actions = '[]'] = process.argv.slice(2);
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ['--no-sandbox', '--font-render-hinting=none'] });
  const page = await browser.newPage();
  const isMobile = mobile === '1';
  await page.setViewport({ width: +w, height: +h, deviceScaleFactor: isMobile ? 2 : 1, isMobile, hasTouch: isMobile });
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
  await page.goto(BASE + route, { waitUntil: 'networkidle0' });
  await page.evaluate(() => document.fonts && document.fonts.ready);
  for (const a of JSON.parse(actions)) {
    if (a.click) await page.click(a.click);
    if (a.eval) await page.evaluate(a.eval);
    if (a.hash) await page.evaluate((hh) => (location.hash = hh), a.hash);
    await new Promise((r) => setTimeout(r, a.wait || 400));
  }
  await new Promise((r) => setTimeout(r, 300));
  await page.screenshot({ path: out });
  if (errors.length) console.log('ERRORS:\n' + errors.join('\n'));
  await browser.close();
})();
