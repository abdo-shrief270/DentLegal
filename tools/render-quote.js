// Renders quote/quote.html to a high-resolution PNG (and a PDF copy).
// Usage: node render-quote.js
const puppeteer = require('puppeteer-core');
const path = require('path');

const CHROME = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const SRC = path.resolve(__dirname, '..', 'quote', 'quote.html');
const OUT = path.resolve(__dirname, '..', 'quote');

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ['--font-render-hinting=none'] });
  const page = await browser.newPage();
  await page.setViewport({ width: 1080, height: 1400, deviceScaleFactor: 2 });
  await page.goto('file:///' + SRC.replace(/\\/g, '/'), { waitUntil: 'networkidle0' });
  await page.evaluate(() => document.fonts.ready);
  const el = await page.$('#quote');
  await el.screenshot({ path: path.join(OUT, 'DentLegal-Price-Quote.png') });
  const { height } = await el.boundingBox();
  await page.pdf({ path: path.join(OUT, 'DentLegal-Price-Quote.pdf'), width: '1080px', height: Math.ceil(height) + 'px', printBackground: true, pageRanges: '1' });
  console.log('saved DentLegal-Price-Quote.png and .pdf (' + Math.round(height) + 'px tall)');
  await browser.close();
})();
