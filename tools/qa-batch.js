// QA batch capture: node qa-batch.js <outDir> <spec.json>
// spec: [{ name, route, w, h, mobile, actions: [{click|eval|hash, wait}] , fresh }]
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');
const CHROME = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = process.env.BASE_URL || 'http://localhost:5173';

(async () => {
  const [outDir, specFile] = process.argv.slice(2);
  const spec = JSON.parse(fs.readFileSync(specFile, 'utf8'));
  fs.mkdirSync(outDir, { recursive: true });
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ['--no-sandbox', '--font-render-hinting=none'] });
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
  for (const s of spec) {
    const mobile = !!s.mobile;
    await page.setViewport({ width: s.w || 1440, height: s.h || 900, deviceScaleFactor: s.scale || (mobile ? 2 : 1), isMobile: mobile, hasTouch: mobile });
    if (s.fresh) {
      await page.goto(BASE + '/prototype/index.html#/', { waitUntil: 'networkidle0' });
      await page.evaluate(() => { localStorage.clear(); });
    }
    await page.goto(BASE + s.route, { waitUntil: 'networkidle0' });
    if (s.reload) await page.reload({ waitUntil: 'networkidle0' });
    await page.evaluate(() => document.fonts && document.fonts.ready);
    for (const a of s.actions || []) {
      try {
        if (a.click) await page.click(a.click);
        if (a.eval) await page.evaluate(a.eval);
        if (a.hash) await page.evaluate((hh) => (location.hash = hh), a.hash);
      } catch (e) { errors.push(`[${s.name}] action failed: ${JSON.stringify(a)} ${e.message}`); }
      await new Promise((r) => setTimeout(r, a.wait || 400));
    }
    await new Promise((r) => setTimeout(r, 350));
    const opts = { path: path.join(outDir, s.name + '.png') };
    if (s.full) opts.fullPage = true;
    if (s.clip) {
      const box = await page.evaluate((sel) => { const r = document.querySelector(sel).getBoundingClientRect(); return { x: r.x, y: r.y, width: r.width, height: r.height }; }, s.clip);
      opts.clip = box;
    }
    await page.screenshot(opts);
    console.log('ok', s.name);
  }
  if (errors.length) console.log('ERRORS:\n' + errors.join('\n'));
  await browser.close();
})();
