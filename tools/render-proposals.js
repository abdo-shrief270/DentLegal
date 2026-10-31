// Renders proposals/project1.html and project2.html to A4 PDFs (+ page previews for QA).
// Usage: node render-proposals.js [--previews <dir>]
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const DIR = path.resolve(__dirname, '..', 'proposals');
const DOCS = [
  ['project1.html', 'DentLegal-Project1-AI-Pilot.pdf'],
  ['project2.html', 'DentLegal-Project2-Full-Release.pdf'],
];

(async () => {
  const previews = process.argv.includes('--previews') ? process.argv[process.argv.indexOf('--previews') + 1] : null;
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ['--font-render-hinting=none', '--allow-file-access-from-files'] });
  const page = await browser.newPage();
  for (const [src, out] of DOCS) {
    await page.goto('file:///' + path.join(DIR, src).replace(/\\/g, '/'), { waitUntil: 'networkidle0' });
    await page.evaluate(() => document.fonts.ready);
    await page.pdf({
      path: path.join(DIR, out), format: 'A4', printBackground: true, preferCSSPageSize: true,
      displayHeaderFooter: true, headerTemplate: '<div></div>',
      footerTemplate: '<div style="width:100%;font-size:8px;color:#8a9aa1;text-align:center;font-family:Tahoma">DentLegal · <span class="pageNumber"></span> / <span class="totalPages"></span></div>',
    });
    console.log('saved', out);
    if (previews) {
      fs.mkdirSync(previews, { recursive: true });
      await page.setViewport({ width: 794, height: 1123, deviceScaleFactor: 1 });
      await page.emulateMediaType('print');
      await page.screenshot({ path: path.join(previews, src.replace('.html', '.png')), fullPage: true });
      await page.emulateMediaType('screen');
    }
  }
  await browser.close();
})();
