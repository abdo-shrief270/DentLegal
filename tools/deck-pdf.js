// Exports PDFs (deck + documents) and per-slide PNG previews.
// Usage: (serve the site first) node deck-pdf.js [--slides <outDir>]
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = process.env.BASE_URL || 'http://localhost:5173';
const OUT = path.resolve(__dirname, '..', 'site', 'downloads');

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const slidesDir = process.argv.includes('--slides') ? process.argv[process.argv.indexOf('--slides') + 1] : null;
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ['--no-sandbox', '--font-render-hinting=none'] });
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  page.on('requestfailed', (r) => errors.push('request failed: ' + r.url()));

  // Deck PDF: one 1280x720 page per slide
  await page.setViewport({ width: 1280, height: 720 });
  await page.goto(`${BASE}/presentation/index.html?print=1`, { waitUntil: 'networkidle0' });
  await page.evaluate(() => document.fonts.ready);
  await page.pdf({ path: path.join(OUT, 'DentLegal-Presentation.pdf'), width: '1280px', height: '720px', printBackground: true, preferCSSPageSize: true });
  console.log('saved DentLegal-Presentation.pdf');

  // Documents as A4 PDFs
  for (const [file, name] of [['docs/prd.html', 'DentLegal-PRD.pdf'], ['docs/phase2.html', 'DentLegal-Phase2-Proposal.pdf'], ['docs/phase1.html', 'DentLegal-Phase1-Scope.pdf']]) {
    await page.setViewport({ width: 1100, height: 900 });
    await page.goto(`${BASE}/${file}`, { waitUntil: 'networkidle0' });
    await page.evaluate(() => document.fonts.ready);
    await page.pdf({ path: path.join(OUT, name), format: 'A4', printBackground: true, margin: { top: '12mm', bottom: '12mm', left: '10mm', right: '10mm' } });
    console.log('saved', name);
  }

  // Optional per-slide previews for QA / PowerPoint thumbnails
  if (slidesDir) {
    fs.mkdirSync(slidesDir, { recursive: true });
    await page.setViewport({ width: 1280, height: 720 });
    await page.goto(`${BASE}/presentation/index.html#1`, { waitUntil: 'networkidle0' });
    await page.evaluate(() => document.fonts.ready);
    const n = await page.evaluate(() => document.querySelectorAll('.slide').length);
    for (let i = 1; i <= n; i++) {
      await page.evaluate((k) => { location.hash = '#' + k; location.reload(); }, i);
      await page.waitForNetworkIdle();
      await page.evaluate(() => document.fonts.ready);
      await new Promise((r) => setTimeout(r, 200));
      await page.evaluate(() => { const u = document.querySelector('.ui'); if (u) u.style.display = 'none'; });
      await page.screenshot({ path: path.join(slidesDir, `slide-${String(i).padStart(2, '0')}.png`) });
    }
    console.log('saved', n, 'slide previews');
  }
  if (errors.length) console.log('ERRORS:\n' + [...new Set(errors)].join('\n'));
  await browser.close();
})();
