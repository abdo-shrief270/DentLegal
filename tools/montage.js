// Contact sheet for QA: node montage.js <out.png> <cols> <cellWidth> <crop:x,y,w,h|none> <img1> <img2> ...
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');
const CHROME = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';

(async () => {
  const [out, cols, cellW, crop, ...imgs] = process.argv.slice(2);
  const c = crop === 'none' ? null : crop.split(',').map(Number);
  const cells = imgs.map((p) => {
    const b64 = fs.readFileSync(p).toString('base64');
    const name = path.basename(p, '.png');
    const img = c
      ? `<div style="width:${cellW}px;height:${Math.round((cellW * c[3]) / c[2])}px;overflow:hidden;position:relative"><img src="data:image/png;base64,${b64}" style="position:absolute;transform-origin:0 0;transform:scale(${cellW / c[2]}) translate(${-c[0]}px,${-c[1]}px)"></div>`
      : `<img src="data:image/png;base64,${b64}" style="width:${cellW}px;display:block">`;
    return `<figure style="margin:0">${img}<figcaption style="font:12px sans-serif;padding:4px">${name}</figcaption></figure>`;
  }).join('');
  const html = `<html><body style="margin:8px;background:#ddd"><div style="display:grid;grid-template-columns:repeat(${cols},${cellW}px);gap:8px">${cells}</div></body></html>`;
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true });
  const page = await browser.newPage();
  await page.setViewport({ width: cols * (+cellW + 8) + 16, height: 600 });
  await page.setContent(html, { waitUntil: 'load' });
  await page.screenshot({ path: out, fullPage: true });
  await browser.close();
})();
