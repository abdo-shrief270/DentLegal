// Builds the native PowerPoint version of the client presentation (Arabic, RTL).
// Usage: node build-pptx.js   → site/downloads/DentLegal-Presentation.pptx
const pptxgen = require('pptxgenjs');
const puppeteer = require('puppeteer-core');
const qrcode = require('qrcode-generator');
const fs = require('fs');
const path = require('path');
const { applyTheme } = require('./lib/apply_theme.js');

const CHROME = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const SITE = path.resolve(__dirname, '..', 'site');
const SHOTS = path.join(SITE, 'screenshots');
const OUT = path.join(SITE, 'downloads', 'DentLegal-Presentation.pptx');

// Proposal metadata lives in site/assets/config.js (window.DL_CONFIG = {...})
const cfgSrc = fs.readFileSync(path.join(SITE, 'assets', 'config.js'), 'utf8');
const CFG = new Function('window', cfgSrc + '; return window.DL_CONFIG;')({});

const THEME = {
  name: 'DentLegal', headFontFace: 'Arial', bodyFontFace: 'Arial',
  colors: { dk1: '10242C', lt1: 'FFFFFF', dk2: '0A2E3B', lt2: 'EBF7F4', accent1: '12806F', accent2: 'B8924F', accent3: '6A45C2', accent4: '16505F', accent5: '2459C9', accent6: 'C43C3C', hlink: '0D6B5D', folHlink: '6A45C2' },
};
const HEX = { navy950: '061F28', navy: '0A2E3B', navy800: '0F3D4C', teal: '12806F', teal500: '16A08A', teal100: 'D3EFE9', teal50: 'EBF7F4', mint: '8FE3D3', gold: 'B8924F', gold100: 'F5ECDC', violet: '6A45C2', violet50: 'F1ECFC', blue50: 'EAF0FD', line: 'E2E9EB', bg: 'F3F6F7', muted: '5D7079', faint: '8A9AA1', ink2: '33474F', paleText: 'B7CFD5', phone: '0B1418', gray: '8A9AA1' };

const ICONS = {
  alert: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
  clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
  scale: '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
  lock: '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
  briefcase: '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
  sparkles: '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>',
  stamp: '<path d="M5 22h14"/><path d="M19.27 13.73A2.5 2.5 0 0 0 17.5 13h-11A2.5 2.5 0 0 0 4 15.5V17a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-1.5c0-.66-.26-1.3-.73-1.77Z"/><path d="M14 13V8.5C14 7 15 7 15 5a3 3 0 0 0-3-3c-1.69 0-3 1-3 3s1 2 1 3.5V13"/>',
  stethoscope: '<path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/>',
  settings: '<path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/>',
  scan: '<path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8h8"/><path d="M7 12h10"/><path d="M7 16h6"/>',
  file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M16 13H8"/><path d="M16 17H8"/>',
  chat: '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
  clipboard: '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
  bell: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
  history: '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
  phone: '<rect width="14" height="20" x="5" y="2" rx="2"/><path d="M12 18h.01"/>',
  database: '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>',
  shield: '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
  check: '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
  calendar: '<rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>',
  gift: '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>',
  pen: '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
  tooth: '<path d="M12 5.5c-1.4-1-2.6-1.7-4.3-1.7C5 3.8 3.5 5.9 3.5 8.6c0 2 .8 3.4 1.3 5 .6 2 .7 6.6 2.4 6.6 1.6 0 1.6-3.2 2.4-5 .4-.9.9-1.4 2.4-1.4s2 .5 2.4 1.4c.8 1.8.8 5 2.4 5 1.7 0 1.8-4.6 2.4-6.6.5-1.6 1.3-3 1.3-5 0-2.7-1.5-4.8-4.2-4.8-1.7 0-2.9.7-4.3 1.7Z"/><path d="M12 7.6v4.6"/><path d="M9.2 8.6h5.6"/>',
};

async function renderIcons(pairs) {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true });
  const page = await browser.newPage();
  await page.setViewport({ width: 160, height: 160 });
  const out = {};
  for (const [name, color] of pairs) {
    await page.setContent(`<html><body style="margin:0;background:transparent"><svg id="s" width="128" height="128" viewBox="0 0 24 24" fill="none" stroke="#${color}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ICONS[name]}</svg></body></html>`);
    const el = await page.$('#s');
    out[name + ':' + color] = 'image/png;base64,' + (await el.screenshot({ omitBackground: true, encoding: 'base64' }));
  }
  let qr = null;
  const url = CFG.demoUrl ? CFG.demoUrl.replace(/\/$/, '') + '/prototype/' : '';
  if (url) {
    const q = qrcode(0, 'M'); q.addData(url); q.make();
    await page.setContent(`<html><body style="margin:0;background:#fff"><div id="q" style="display:inline-block;padding:12px;background:#fff">${q.createSvgTag({ cellSize: 8, margin: 0 })}</div></body></html>`);
    qr = 'image/png;base64,' + (await (await page.$('#q')).screenshot({ encoding: 'base64' }));
  }
  await browser.close();
  return { icons: out, qr, url };
}

// pptxgenjs writes one <a:pPr> per run for multi-run paragraphs; keep only the first so the XML stays schema-valid.
async function dedupeParagraphProps(file) {
  const JSZip = require('jszip');
  const zip = await JSZip.loadAsync(fs.readFileSync(file));
  for (const name of Object.keys(zip.files).filter((n) => /^ppt\/(slides|slideLayouts|slideMasters)\/[^/]+\.xml$/.test(n))) {
    const xml = await zip.file(name).async('string');
    const fixed = xml.replace(/<a:p>([\s\S]*?)<\/a:p>/g, (m, inner) => {
      let seen = false;
      return '<a:p>' + inner.replace(/<a:pPr\b[^>]*?(?:\/>|>[\s\S]*?<\/a:pPr>)/g, (pp) => (seen ? '' : ((seen = true), pp))) + '</a:p>';
    });
    if (fixed !== xml) zip.file(name, fixed);
  }
  fs.writeFileSync(file, await zip.generateAsync({ type: 'nodebuffer', compression: 'DEFLATE' }));
}

(async () => {
  const iconPairs = [];
  const need = (n, c) => { const k = n + ':' + c; if (!iconPairs.find((p) => p[0] + ':' + p[1] === k)) iconPairs.push([n, c]); return k; };
  // pre-register every icon/color the deck uses
  const ICON_USE = [['clock', HEX.teal], ['scale', HEX.teal], ['lock', HEX.teal], ['briefcase', HEX.teal], ['sparkles', HEX.violet], ['stamp', HEX.gold], ['stethoscope', HEX.teal], ['scale', HEX.gold], ['settings', HEX.violet], ['scan', HEX.teal], ['file', HEX.violet], ['chat', HEX.teal], ['clipboard', HEX.teal], ['bell', HEX.teal], ['history', HEX.mint], ['phone', HEX.teal], ['database', HEX.mint], ['shield', HEX.gold], ['check', HEX.teal], ['calendar', HEX.teal], ['gift', HEX.teal], ['pen', HEX.teal], ['tooth', 'FFFFFF'], ['check', HEX.mint]];
  ICON_USE.forEach(([n, c]) => need(n, c));
  const { icons, qr, url } = await renderIcons(iconPairs);
  const IC = (n, c) => icons[n + ':' + c];

  const pres = new pptxgen();
  pres.layout = 'LAYOUT_WIDE'; // 13.333 x 7.5 in
  pres.rtlMode = true;
  pres.theme = { headFontFace: THEME.headFontFace, bodyFontFace: THEME.bodyFontFace };
  pres.author = CFG.provider;
  pres.company = 'DentLegal';
  pres.title = 'DentLegal — العرض التقديمي';
  pres.subject = 'فكرة المنتج والمرحلة الأولى والمرحلة الثانية';
  const C = pres.SchemeColor;
  const W = 13.333, M = 0.6, CW = W - 2 * M;

  // ---------- layouts ----------
  const footer = (dark) => [
    { text: { text: 'DentLegal', options: { x: W - M - 2, y: 7.0, w: 2, h: 0.3, fontSize: 10, color: dark ? '6F949D' : HEX.faint, align: 'right' } } },
  ];
  pres.defineSlideMaster({
    title: 'DL_CONTENT', background: { color: 'FFFFFF' },
    objects: [
      { placeholder: { options: { name: 'kicker', type: 'body', x: M, y: 0.42, w: CW, h: 0.4, fontSize: 14, bold: true, color: C.accent1, align: 'right', valign: 'middle', margin: 0, rtlMode: true, lang: 'ar-SA' }, text: '' } },
      { placeholder: { options: { name: 'title', type: 'title', x: M, y: 0.82, w: CW, h: 0.8, fontSize: 30, bold: true, color: C.text2, align: 'right', valign: 'middle', margin: 0, rtlMode: true, lang: 'ar-SA' }, text: '' } },
      ...footer(false),
    ],
    slideNumber: { x: M, y: 7.0, w: 0.8, h: 0.3, fontSize: 10, color: HEX.faint },
  });
  pres.defineSlideMaster({
    title: 'DL_TINT', background: { color: HEX.teal50 },
    objects: [
      { placeholder: { options: { name: 'kicker', type: 'body', x: M, y: 0.42, w: CW, h: 0.4, fontSize: 14, bold: true, color: C.accent1, align: 'right', valign: 'middle', margin: 0, rtlMode: true, lang: 'ar-SA' }, text: '' } },
      { placeholder: { options: { name: 'title', type: 'title', x: M, y: 0.82, w: CW, h: 0.8, fontSize: 30, bold: true, color: C.text2, align: 'right', valign: 'middle', margin: 0, rtlMode: true, lang: 'ar-SA' }, text: '' } },
      ...footer(false),
    ],
    slideNumber: { x: M, y: 7.0, w: 0.8, h: 0.3, fontSize: 10, color: HEX.faint },
  });
  pres.defineSlideMaster({
    title: 'DL_DARK', background: { color: HEX.navy },
    objects: [{ placeholder: { options: { name: 'title', type: 'title', x: M, y: 2.6, w: CW, h: 1.2, fontSize: 48, bold: true, color: C.background1, align: 'right', valign: 'middle', margin: 0, rtlMode: true, lang: 'ar-SA' }, text: '' } }],
  });

  // ---------- helpers ----------
  const R = { rtlMode: true, align: 'right', isTextBox: true, margin: 0, valign: 'top', lang: 'ar-SA' };
  const dash = (v) => (typeof v === 'string' ? v.replace(/ — /g, ' – ') : v);
  // Split mixed Arabic/Latin text into script runs: Latin runs tagged en-US, Arabic runs ar-SA.
  // A single run tagged ar-SA that contains Latin words can be drawn reversed by PowerPoint.
  const LATIN = /([A-Za-z][A-Za-z0-9 .+\-/&@_]*[A-Za-z0-9]|[A-Za-z])/;
  const splitScripts = (str, opts) => String(str).split(LATIN).filter((p) => p !== '').map((p) => ({ text: p, options: { ...opts, lang: opts.rtlMode === false || /^[A-Za-z]/.test(p) ? 'en-US' : 'ar-SA' } }));
  const runs = (t, o) => {
    const base = { rtlMode: o.rtlMode !== false, align: o.align || 'right' };
    const items = Array.isArray(t) ? t : [{ text: t, options: {} }];
    const out = [];
    items.forEach((x) => {
      const parts = splitScripts(dash(x.text), { ...base, ...(x.options || {}) });
      // breakLine / bullet belong to the paragraph: keep bullet on the first part, breakLine on the last
      parts.forEach((p, i) => {
        if (i > 0) delete p.options.bullet;
        if (i < parts.length - 1) delete p.options.breakLine;
      });
      out.push(...parts);
    });
    return out;
  };
  let objN = 0;
  const nm = (p) => `${p}-${++objN}`;
  const text = (s, t, o) => s.addText(runs(t, o), { ...R, objectName: nm('text'), ...o });
  const box = (s, x, y, w, h, fill = 'FFFFFF', line = HEX.line, r = 0.14) => s.addShape(pres.shapes.ROUNDED_RECTANGLE, { x, y, w, h, rectRadius: r, fill: { color: fill }, line: { color: line, width: 0.75 }, objectName: nm('card') });
  const iconTile = (s, x, y, name, color, bg, size = 0.6) => {
    s.addShape(pres.shapes.ROUNDED_RECTANGLE, { x, y, w: size, h: size, rectRadius: 0.12, fill: { color: bg }, line: { color: bg, width: 0 }, objectName: nm('icon-bg') });
    const p = size * 0.25;
    s.addImage({ data: IC(name, color), x: x + p, y: y + p, w: size - 2 * p, h: size - 2 * p, objectName: nm('icon') });
  };
  const fromRight = (i, w, gap, right = W - M) => right - (i + 1) * w - i * gap;
  const phone = (s, file, x, y, w) => {
    const h = w * (1688 / 780);
    const b = 0.07;
    s.addShape(pres.shapes.ROUNDED_RECTANGLE, { x: x - b, y: y - b, w: w + 2 * b, h: h + 2 * b, rectRadius: 0.22, fill: { color: HEX.phone }, line: { color: HEX.phone, width: 0 }, shadow: { type: 'outer', color: '0A2E3B', opacity: 0.25, blur: 10, offset: 4, angle: 90 }, objectName: nm('phone') });
    s.addImage({ path: path.join(SHOTS, file), x, y, w, h, objectName: nm('screen'), altText: 'لقطة من تطبيق الطبيب' });
    return h;
  };
  const shot = (s, file, x, y, w) => {
    const h = w * (1350 / 2160);
    s.addShape(pres.shapes.RECTANGLE, { x: x - 0.02, y: y - 0.02, w: w + 0.04, h: h + 0.04, fill: { color: HEX.line }, line: { color: HEX.line, width: 0 }, shadow: { type: 'outer', color: '0A2E3B', opacity: 0.18, blur: 12, offset: 4, angle: 90 }, objectName: nm('shot-frame') });
    s.addImage({ path: path.join(SHOTS, file), x, y, w, h, objectName: nm('shot'), altText: 'لقطة من بوابات الويب' });
    return h;
  };
  const content = (title, kicker, section, master = 'DL_CONTENT') => {
    const s = pres.addSlide({ masterName: master, sectionTitle: section });
    s.addText(dash(kicker), { placeholder: 'kicker', rtlMode: true, lang: 'ar-SA' });
    s.addText(dash(title), { placeholder: 'title', rtlMode: true, lang: 'ar-SA', lang: 'ar-SA' });
    return s;
  };
  const card = (s, x, y, w, h, icon, iconColor, iconBg, title, body, o = {}) => {
    box(s, x, y, w, h, o.fill || 'FFFFFF', o.line || HEX.line);
    iconTile(s, x + w - 0.25 - 0.6, y + 0.25, icon, iconColor, iconBg);
    text(s, title, { x: x + 0.25, y: y + 1.0, w: w - 0.5, h: 0.42, fontSize: o.titleSize || 17, bold: true, color: C.text1 });
    text(s, body, { x: x + 0.25, y: y + 1.45, w: w - 0.5, h: h - 1.6, fontSize: o.bodySize || 14, color: HEX.muted, lineSpacingMultiple: 1.15 });
  };

  const S1 = 'فكرة المنتج', S2 = 'المرحلة الأولى', S3 = 'المرحلة الثانية';

  // ===== 1. Cover =====
  pres.addSection({ title: S1 });
  {
    const s = pres.addSlide({ masterName: 'DL_DARK', sectionTitle: S1 });
    s.addShape(pres.shapes.ROUNDED_RECTANGLE, { x: W - M - 0.75, y: 0.9, w: 0.75, h: 0.75, rectRadius: 0.16, fill: { color: HEX.teal500 }, line: { color: HEX.teal500, width: 0 }, objectName: 'logo-bg' });
    s.addImage({ data: IC('tooth', 'FFFFFF'), x: W - M - 0.62, y: 1.03, w: 0.49, h: 0.49, objectName: 'logo' });
    text(s, 'DentLegal', { x: W - M - 4, y: 1.0, w: 3.1, h: 0.55, fontSize: 26, bold: true, color: 'FFFFFF', valign: 'middle', rtlMode: false });
    text(s, 'الحماية القانونية لأطباء الأسنان', { x: 5.2, y: 2.0, w: W - M - 5.2, h: 1.9, fontSize: 44, bold: true, color: 'FFFFFF', valign: 'middle' });
    text(s, 'فكرة المنتج · المرحلة الأولى (الاستكشاف والتصميم والنموذج) · المرحلة الثانية (التطوير الكامل)', { x: 5.2, y: 4.05, w: W - M - 5.2, h: 0.9, fontSize: 17, color: HEX.paleText });
    text(s, `${CFG.provider}   ·   ${CFG.docDateLabel}`, { x: 5.2, y: 5.25, w: W - M - 5.2, h: 0.4, fontSize: 14, color: HEX.mint });
    phone(s, 'm03-home.png', 0.9, 1.0, 1.95);
    phone(s, 'm13-approved.png', 3.1, 1.6, 1.95);
    s.addNotes('افتتاح: DentLegal منصة رقمية لحماية أطباء الأسنان قانونيًا. العرض في ثلاثة أقسام: فكرة المنتج، المرحلة الأولى، المرحلة الثانية.');
  }

  // ===== 2. Problem =====
  {
    const s = content('الشكوى الطبية لحظة حرجة… والطبيب يواجهها وحده', 'القسم الأول · فكرة المنتج — المشكلة', S1);
    const items = [['clock', 'مهل قصيرة', 'إفادة رسمية مطلوبة قبل موعد التحقيق، ومهلة 60 يومًا للتظلم من قرار الهيئة.'], ['scale', 'مسار نظامي معقّد', 'شكوى، تحقيق، إحالة للهيئة الصحية الشرعية، جلسات وقرار.'], ['lock', 'خصوصية المريض', 'ما الذي يجوز الإفصاح عنه للدفاع؟ وكيف تُرسل المستندات بأمان؟'], ['briefcase', 'استشارة بطيئة ومكلفة', 'قليل من المحامين متخصص في طب الأسنان، والتواصل متفرق.']];
    const w = (CW - 3 * 0.25) / 4;
    items.forEach(([ic, t, b], i) => card(s, fromRight(i, w, 0.25), 1.9, w, 2.6, ic, HEX.teal, HEX.teal50, t, b));
    const stages = ['تقديم الشكوى', 'التحقيق الأولي', 'التحقيق التفصيلي', 'الإحالة للهيئة', 'نظر الدعوى', 'القرار والتظلم'];
    text(s, 'المسار النظامي للشكوى:', { x: W - M - 2.4, y: 4.78, w: 2.4, h: 0.4, fontSize: 14, bold: true, color: HEX.muted, valign: 'middle' });
    const pw = 1.5;
    stages.forEach((st, i) => {
      const x = W - M - 2.5 - (i + 1) * pw - i * 0.12;
      s.addShape(pres.shapes.ROUNDED_RECTANGLE, { x, y: 4.8, w: pw, h: 0.38, rectRadius: 0.19, fill: { color: HEX.teal50 }, line: { color: HEX.teal100, width: 0.75 }, objectName: nm('stage') });
      text(s, st, { x, y: 4.8, w: pw, h: 0.38, fontSize: 12, bold: true, color: C.accent1, align: 'center', valign: 'middle' });
    });
    box(s, M, 5.5, CW, 1.15, HEX.bg, HEX.bg);
    text(s, [{ text: 'في المقابل: ', options: { bold: true } }, { text: 'التأمين ضد الأخطاء المهنية إلزامي على جميع أطباء الأسنان (المادة 41 من نظام مزاولة المهن الصحية) — الوعي بالمخاطر موجود، والحل المتخصص غائب.' }], { x: M + 0.3, y: 5.62, w: CW - 0.6, h: 0.9, fontSize: 15, color: HEX.ink2, valign: 'middle' });
    s.addNotes('المشكلة: المهل القصيرة، تعقيد المسار النظامي، حساسية بيانات المريض، وصعوبة الوصول لاستشارة متخصصة.');
  }

  // ===== 3. Solution =====
  {
    const s = content('من الشكوى إلى ردٍّ معتمد قانونيًا — خلال ساعات لا أسابيع', 'الحل', S1, 'DL_TINT');
    const rows = [['sparkles', HEX.violet, HEX.violet50, 'تحليل ذكي مبني على الأنظمة السعودية', 'قوة الموقف، المخاطر، الإجراءات العاجلة — وكل نقطة بمصدرها النظامي.'], ['stamp', HEX.gold, HEX.gold100, 'اعتماد من محامٍ مرخّص', 'الذكاء الاصطناعي يصيغ، والمحامي يراجع ويعدّل ويوقّع — لا رد دون اعتماد.'], ['lock', HEX.teal, 'FFFFFF', 'خصوصية وأمان من البداية', 'المريض برمز، المستندات مشفّرة، وكل اطلاع مسجّل.']];
    rows.forEach(([ic, c, bg, t, b], i) => {
      const y = 2.15 + i * 1.45;
      iconTile(s, W - M - 0.7, y, ic, c, bg, 0.7);
      text(s, t, { x: 3.6, y: y - 0.02, w: W - M - 0.95 - 3.6, h: 0.45, fontSize: 19, bold: true, color: C.text1 });
      text(s, b, { x: 3.6, y: y + 0.45, w: W - M - 0.95 - 3.6, h: 0.7, fontSize: 15, color: HEX.muted });
    });
    phone(s, 'm09-analysis.png', 0.9, 1.75, 2.3);
    s.addNotes('القيمة: تحليل مُسند، اعتماد بشري إلزامي، وخصوصية من التصميم.');
  }

  // ===== 4. Personas =====
  {
    const s = content('ثلاثة أدوار، تجربة مصممة لكل منها', 'الفئات المستهدفة', S1);
    const cols = [['stethoscope', HEX.teal, HEX.teal50, 'طبيب الأسنان', 'المشترك الأساسي: تسجيل القضايا، المستندات، التحليل الذكي، المستشار، النماذج، ومتابعة الجلسات.', 'تطبيق iOS و Android', 'm03-home.png'], ['scale', HEX.gold, HEX.gold100, 'المحامي المراجِع', 'قائمة مراجعة بمهل، مساحة عمل بجوار المصادر، تعديل بتتبع التغييرات، اعتماد وتوقيع.', 'بوابة ويب', 'w04-lawyer-review.png'], ['settings', HEX.violet, HEX.violet50, 'مدير المنصة', 'توثيق الأطباء، إسناد المحامين، قاعدة المعرفة، الصلاحيات، وسجل التدقيق.', 'لوحة تحكم ويب', 'w06-admin-overview.png']];
    const w = (CW - 2 * 0.3) / 3;
    cols.forEach(([ic, c, bg, t, b, tag, img], i) => {
      const x = fromRight(i, w, 0.3);
      box(s, x, 1.85, w, 4.75);
      iconTile(s, x + w - 0.85, 2.1, ic, c, bg);
      text(s, t, { x: x + 0.25, y: 2.85, w: w - 0.5, h: 0.4, fontSize: 18, bold: true, color: C.text1 });
      text(s, b, { x: x + 0.25, y: 3.3, w: w - 0.5, h: 1.2, fontSize: 13.5, color: HEX.muted });
      s.addImage({ path: path.join(SHOTS, img), x: x + 0.25, y: 4.55, w: w - 0.5, h: 1.35, sizing: { type: 'crop', w: w - 0.5, h: 1.35 }, objectName: nm('thumb') });
      text(s, tag, { x: x + 0.25, y: 6.05, w: w - 0.5, h: 0.35, fontSize: 12, bold: true, color: c });
    });
    s.addNotes('الأدوار: الطبيب (جوال)، المحامي (ويب)، الإدارة (ويب). حسابات العيادات خارج الإصدار الأول.');
  }

  // ===== 5. Features =====
  {
    const s = content('كل ما يحتاجه الطبيب في قضيته — في مكان واحد', 'أهم المميزات', S1);
    const f = [['briefcase', HEX.teal, HEX.teal50, 'إدارة القضايا', '6 مراحل نظامية، مواعيد، وسجل زمني.'], ['scan', HEX.teal, HEX.teal50, 'مستندات + OCR', 'رفع ومسح وتشفير واستخراج تلقائي.'], ['sparkles', HEX.violet, HEX.violet50, 'تحليل ذكي', 'قوة الموقف والمخاطر والإجراءات.'], ['file', HEX.violet, HEX.violet50, 'مسودة رد مُسندة', 'صياغة رسمية باستشهادات قابلة للتحقق.'], ['stamp', HEX.gold, HEX.gold100, 'اعتماد المحامي', 'مراجعة، إصدارات، توقيع ورمز تحقق.'], ['chat', HEX.teal, HEX.teal50, 'المستشار الذكي', 'إجابات بالمصادر وتحويل لمحامٍ.'], ['clipboard', HEX.teal, HEX.teal50, 'النماذج والإقرارات', 'إقرارات موافقة مصاغة قانونيًا.'], ['bell', HEX.teal, HEX.teal50, 'تنبيهات ذكية', 'الجلسات، المهل، التأمين والاشتراك.']];
    const w = (CW - 3 * 0.25) / 4, h = 2.3;
    f.forEach(([ic, c, bg, t, b], i) => card(s, fromRight(i % 4, w, 0.25), 1.9 + Math.floor(i / 4) * (h + 0.25), w, h, ic, c, bg, t, b, { titleSize: 16, bodySize: 13.5 }));
  }

  // ===== 6. Flow =====
  {
    const s = content('دورة القضية: من تسجيلها حتى اعتماد رد المحامي', 'User Flow', S1);
    const st = [['تسجيل القضية', 'معالج من 4 خطوات — المريض برمز فقط', 'FFFFFF', HEX.line], ['المستندات', 'رفع أو مسح، تشفير، واستخراج OCR', 'FFFFFF', HEX.line], ['التحليل الذكي', 'إخفاء الهوية، استرجاع المواد، التقييم', HEX.violet50, 'D9CDF5'], ['مسودة مُسندة', 'إفادة رسمية مع الاستشهادات', HEX.violet50, 'D9CDF5'], ['مراجعة المحامي', 'تعديل أو طلب معلومات، ثم توقيع', HEX.gold100, 'EAD9B8'], ['رد معتمد', 'إشعار، PDF موقّع، تقديم للجهة', HEX.teal50, HEX.teal100]];
    const gap = 0.28, w = (CW - 5 * gap) / 6, y = 2.0, h = 2.6;
    st.forEach(([t, b, fill, line], i) => {
      const x = fromRight(i, w, gap);
      box(s, x, y, w, h, fill, line);
      s.addShape(pres.shapes.OVAL, { x: x + w - 0.62, y: y + 0.22, w: 0.42, h: 0.42, fill: { color: HEX.navy }, line: { color: HEX.navy, width: 0 }, objectName: nm('num') });
      text(s, String(i + 1), { x: x + w - 0.62, y: y + 0.22, w: 0.42, h: 0.42, fontSize: 14, bold: true, color: 'FFFFFF', align: 'center', valign: 'middle' });
      text(s, t, { x: x + 0.18, y: y + 0.85, w: w - 0.36, h: 0.45, fontSize: 16, bold: true, color: C.text1 });
      text(s, b, { x: x + 0.18, y: y + 1.35, w: w - 0.36, h: 1.1, fontSize: 13, color: HEX.muted });
      if (i < 5) text(s, '←', { x: x - gap, y: y + h / 2 - 0.2, w: gap, h: 0.4, fontSize: 16, color: HEX.faint, align: 'center', valign: 'middle' });
    });
    box(s, M, 5.05, CW, 1.25, HEX.bg, HEX.bg);
    iconTile(s, W - M - 0.95, 5.33, 'history', HEX.mint, HEX.navy, 0.68);
    text(s, 'ومع كل مرحلة نظامية جديدة (التحقيق التفصيلي، الإحالة للهيئة الصحية الشرعية، التظلم) تبدأ دورة رد جديدة داخل القضية نفسها — وكل خطوة مسجلة في سجل التدقيق.', { x: M + 0.3, y: 5.15, w: CW - 1.6, h: 1.05, fontSize: 15, color: HEX.ink2, valign: 'middle' });
    s.addNotes('المسار الرئيسي المحاكى بالكامل في الجولة الموجهة داخل النموذج التفاعلي.');
  }

  // ===== 7. Mobile screenshots =====
  {
    const s = content('تجربة عربية واضحة على الجوال', 'Screenshots · تطبيق الطبيب', S1, 'DL_TINT');
    const list = [['m03-home.png', 'الرئيسية والمواعيد'], ['m07-upload.png', 'المستندات و OCR'], ['m08-processing.png', 'المعالجة الذكية'], ['m10-draft.png', 'مسودة مع استشهادات'], ['m15-consult.png', 'المستشار الذكي']];
    const w = 1.95, gap = (CW - 5 * w) / 4;
    list.forEach(([f, cap], i) => {
      const x = fromRight(i, w, gap);
      const h = phone(s, f, x, 1.85, w);
      text(s, cap, { x: x - 0.1, y: 1.85 + h + 0.15, w: w + 0.2, h: 0.35, fontSize: 13, bold: true, color: HEX.ink2, align: 'center' });
    });
  }

  // ===== 8. Web screenshots =====
  {
    const s = content('مساحة عمل المحامي ولوحة الإدارة', 'Screenshots · بوابات الويب', S1);
    const w = (CW - 0.4) / 2;
    const h = shot(s, 'w04-lawyer-review.png', W - M - w, 1.95, w);
    shot(s, 'w06-admin-overview.png', M, 1.95, w);
    text(s, 'مراجعة المسودة بجوار المصادر وفحوصات الجودة', { x: W - M - w, y: 1.95 + h + 0.2, w, h: 0.35, fontSize: 14, bold: true, color: HEX.ink2, align: 'center' });
    text(s, 'مؤشرات المنصة وصحة الذكاء الاصطناعي', { x: M, y: 1.95 + h + 0.2, w, h: 0.35, fontSize: 14, bold: true, color: HEX.ink2, align: 'center' });
  }

  // ===== 9. Trustworthy AI =====
  {
    const s = content('لا ادعاء دون مصدر، ولا رد دون محامٍ', 'ذكاء اصطناعي موثوق', S1);
    const pts = [['استرجاع معزز (RAG) ', 'من الأنظمة واللوائح المفهرسة فقط، بإصداراتها السارية.'], ['استشهادات قابلة للتحقق ', '— نقرة تعرض نص المادة، وتحقق آلي من كل استشهاد.'], ['إخفاء هوية المريض ', 'قبل أي معالجة بالنموذج اللغوي.'], ['يرفض التخمين ', 'عند ضعف المصادر ويقترح التحويل لمحامٍ.'], ['اعتماد بشري إلزامي ', 'مع إصدارات وتتبع تغييرات وسجل تدقيق.']];
    pts.forEach(([b, t], i) => {
      const y = 2.0 + i * 0.85;
      s.addImage({ data: IC('check', HEX.teal), x: W - M - 0.38, y: y + 0.04, w: 0.34, h: 0.34, objectName: nm('check') });
      text(s, [{ text: b, options: { bold: true, color: HEX.navy } }, { text: t }], { x: 6.9, y, w: W - M - 0.55 - 6.9, h: 0.75, fontSize: 15.5, color: HEX.ink2 });
    });
    shot(s, 'w08-admin-kb.png', M, 2.1, 5.9);
  }

  // ===== 10. Demo link =====
  {
    const s = pres.addSlide({ masterName: 'DL_DARK', sectionTitle: S1 });
    text(s, 'رابط تجربة Prototype', { x: 4.2, y: 1.5, w: W - M - 4.2, h: 0.4, fontSize: 15, bold: true, color: HEX.mint });
    text(s, 'جرّب دورة القضية بنفسك', { x: 4.2, y: 2.0, w: W - M - 4.2, h: 1.0, fontSize: 38, bold: true, color: 'FFFFFF', valign: 'middle' });
    text(s, 'النموذج التفاعلي يعمل على الجوال والكمبيوتر، بثلاثة أدوار وجولة موجهة من 10 خطوات — ببيانات تجريبية بالكامل.', { x: 4.2, y: 3.15, w: W - M - 4.2, h: 1.0, fontSize: 17, color: HEX.paleText });
    text(s, url || '[يُضاف رابط النشر هنا]', { x: 4.2, y: 4.4, w: W - M - 4.2, h: 0.5, fontSize: 18, color: HEX.mint, rtlMode: false, hyperlink: url ? { url } : undefined });
    if (qr) { s.addImage({ data: qr, x: 1.0, y: 2.0, w: 2.6, h: 2.6, objectName: 'qr' }); text(s, 'امسح الرمز للفتح على الجوال', { x: 0.7, y: 4.75, w: 3.2, h: 0.4, fontSize: 13, color: HEX.paleText, align: 'center' }); }
    else { box(s, 1.0, 2.0, 2.6, 2.6, HEX.navy800, '2A5563'); text(s, 'يظهر رمز QR هنا بعد إضافة رابط النشر في config.js وإعادة البناء', { x: 1.15, y: 2.6, w: 2.3, h: 1.4, fontSize: 12, color: HEX.paleText, align: 'center', valign: 'middle' }); }
  }

  // ===== 11. Phase 1 divider =====
  pres.addSection({ title: S2 });
  {
    const s = pres.addSlide({ masterName: 'DL_DARK', sectionTitle: S2 });
    text(s, 'القسم الثاني', { x: M, y: 2.0, w: CW, h: 0.4, fontSize: 16, bold: true, color: HEX.mint });
    s.addText('المرحلة الأولى', { placeholder: 'title', rtlMode: true, lang: 'ar-SA' });
    text(s, 'Product Discovery · UI/UX · Interactive Prototype', { x: M, y: 3.9, w: CW, h: 0.5, fontSize: 20, color: HEX.paleText });
    text(s, '01', { x: M, y: 5.2, w: 3, h: 1.6, fontSize: 110, bold: true, color: '1F4E59', align: 'left', rtlMode: false });
  }

  // ===== 12. Phase 1 deliverables =====
  {
    const s = content('12 مخرجًا — جميعها قابلة للفتح والتجربة', 'نطاق Discovery & Design والمخرجات', S2);
    const d = ['تحليل شامل للفكرة والمتطلبات (PRD)', 'تحديد المميزات والوحدات (12 وحدة)', 'أدوار المستخدمين والصلاحيات', 'User Flow لجميع السيناريوهات', 'تصميم UI/UX عربي للسوق السعودي', 'تصميم الشاشات الأساسية (33 شاشة)', 'نموذج HTML تفاعلي للجوال والكمبيوتر', 'محاكاة دورة القضية حتى الاعتماد', 'لقطات شاشة حقيقية (32 لقطة)', 'تصور تقني Flutter + Laravel + RAG', 'هذا العرض التقديمي (HTML · PDF · PPTX)', 'عرض المرحلة الثانية (نطاق، جدول، دفعات)'];
    const w = (CW - 2 * 0.3) / 3;
    d.forEach((t, i) => {
      const col = i % 3, row = Math.floor(i / 3);
      const x = fromRight(col, w, 0.3), y = 1.95 + row * 0.78;
      s.addImage({ data: IC('check', HEX.teal), x: x + w - 0.36, y: y + 0.05, w: 0.32, h: 0.32, objectName: nm('check') });
      text(s, t, { x, y, w: w - 0.5, h: 0.6, fontSize: 15, color: C.text1 });
    });
    box(s, M, 5.3, CW, 1.1, HEX.bg, HEX.bg);
    text(s, 'ضوابط المرحلة: لا Backend فعلي، ولا نماذج ذكاء اصطناعي حقيقية، ولا بيانات مرضى حقيقية — تجربة HTML ببيانات تجريبية قابلة للنشر على استضافة مجانية.', { x: M + 0.3, y: 5.4, w: CW - 0.6, h: 0.9, fontSize: 14.5, color: HEX.ink2, valign: 'middle' });
  }

  // ===== 13. Phase 1 duration/cost/acceptance =====
  {
    const s = content('تعاقد مستقل — يُعتمد قبل أي التزام بالتطوير', 'المدة · التكلفة · الاستلام والتعديلات', S2);
    const w = (CW - 2 * 0.3) / 3;
    card(s, fromRight(0, w, 0.3), 1.9, w, 2.55, 'calendar', HEX.teal, HEX.teal50, 'مدة التنفيذ', 'مكتملة: الاستكشاف ← التصميم والنموذج ← التصور التقني والعرض — جاهزة للمراجعة الآن.');
    card(s, fromRight(1, w, 0.3), 1.9, w, 2.55, 'gift', HEX.teal, 'FFFFFF', 'التكلفة: دون تكلفة', 'مقدّمة ضمن تجهيز العرض، واعتمادها لا يُلزم بالتعاقد على المرحلة الثانية.', { fill: HEX.teal50, line: HEX.teal100 });
    card(s, fromRight(2, w, 0.3), 1.9, w, 2.55, 'pen', HEX.teal, HEX.teal50, 'التعديلات والاستلام', 'جولتا تعديل، ملاحظات مجمّعة خلال 5 أيام عمل، تنفيذ خلال 3–5 أيام، ثم توقيع نموذج الاعتماد.');
    box(s, M, 4.7, CW, 1.75);
    text(s, 'شروط الانتقال للمرحلة الثانية:', { x: M + 0.3, y: 4.85, w: CW - 0.6, h: 0.4, fontSize: 15, bold: true, color: C.text1 });
    const cond = ['① الموافقة على نطاق العمل', '② اعتماد التصميمات وتجربة المستخدم', '③ اعتماد مواصفات النظام', '④ الاتفاق على السعر النهائي', '⑤ الاتفاق على الجدول والدفعات', '⑥ إبرام اتفاق المرحلة الثانية'];
    const cw = (CW - 0.6) / 3;
    cond.forEach((t, i) => text(s, t, { x: fromRight(i % 3, cw, 0, W - M - 0.3), y: 5.35 + Math.floor(i / 3) * 0.45, w: cw, h: 0.4, fontSize: 13.5, color: HEX.ink2 }));
  }

  // ===== 14. Phase 2 divider =====
  pres.addSection({ title: S3 });
  {
    const s = pres.addSlide({ masterName: 'DL_DARK', sectionTitle: S3 });
    text(s, 'القسم الثالث', { x: M, y: 2.0, w: CW, h: 0.4, fontSize: 16, bold: true, color: HEX.mint });
    s.addText('المرحلة الثانية', { placeholder: 'title', rtlMode: true, lang: 'ar-SA' });
    text(s, 'Full Production Development — تعاقد مستقل', { x: M, y: 3.9, w: CW, h: 0.5, fontSize: 20, color: HEX.paleText });
    text(s, '02', { x: M, y: 5.2, w: 3, h: 1.6, fontSize: 110, bold: true, color: '1F4E59', align: 'left', rtlMode: false });
  }

  // ===== 15. Phase 2 scope =====
  {
    const s = content('تحويل النموذج المعتمد إلى نظام تجاري حقيقي', 'نطاق التطوير الفعلي', S3);
    const c4 = [['phone', HEX.teal, HEX.teal50, 'Flutter', 'Android و iOS · جميع الشاشات المعتمدة · التكامل مع API · حماية البيانات · الإشعارات والجلسات.'], ['database', HEX.mint, HEX.navy, 'Laravel', 'الصلاحيات · القضايا · سجلات المرضى المرمّزة · المستندات · سير اعتماد المحامي · API · التدقيق.'], ['sparkles', HEX.violet, HEX.violet50, 'AI و RAG', 'OCR وذكاء المستندات · قاعدة متجهات · الاسترجاع · مسودات بالاستشهادات · الإصدارات.'], ['shield', HEX.gold, HEX.gold100, 'الجودة والنشر', 'اختبارات وحدات وتكامل · أمان واختراق · أداء · استضافة ومراقبة · توثيق وتدريب.']];
    const w = (CW - 3 * 0.25) / 4;
    c4.forEach(([ic, c, bg, t, b], i) => card(s, fromRight(i, w, 0.25), 1.9, w, 3.05, ic, c, bg, t, b));
    box(s, M, 5.2, CW, 1.15);
    iconTile(s, W - M - 0.9, 5.42, 'scale', HEX.gold, HEX.gold100, 0.68);
    text(s, [{ text: 'بوابة المحامي ولوحة الإدارة: ', options: { bold: true, color: HEX.navy } }, { text: 'مساحة المراجعة والاعتماد، الإسناد والمهل، توثيق الأطباء، قاعدة المعرفة، الصلاحيات، والتقارير.' }], { x: M + 0.3, y: 5.3, w: CW - 1.4, h: 0.95, fontSize: 15, color: HEX.ink2, valign: 'middle' });
  }

  // ===== 16. Architecture =====
  {
    const s = content('معمارية آمنة وقابلة للتوسع', 'المكونات التقنية', S3);
    const L = [['الواجهات', 'تطبيق Flutter للطبيب · بوابة المحامي (ويب) · لوحة الإدارة (ويب)', HEX.teal50], ['Laravel 12 API', 'Sanctum · Policies (RBAC) · آلة حالات القضية · المستندات المشفّرة · الإصدارات · التدقيق · Horizon', 'FFFFFF'], ['خدمة AI', 'OCR عربي · إخفاء الهوية · استرجاع هجين (pgvector + نصي) · صياغة بمخرجات منظمة · تحقق من الاستشهادات', HEX.violet50], ['البيانات', 'PostgreSQL 17 + pgvector · Redis · تخزين كائنات مشفّر · نسخ احتياطي — استضافة داخل المملكة', HEX.blue50], ['التكاملات', 'نموذج لغوي مؤسسي عبر API · بوابة دفع محلية · SMS و Push · النفاذ الوطني (اختياري)', 'FFFFFF']];
    L.forEach(([l, d, fill], i) => {
      const y = 1.9 + i * 0.92;
      s.addShape(pres.shapes.ROUNDED_RECTANGLE, { x: W - M - 2.3, y, w: 2.3, h: 0.78, rectRadius: 0.12, fill: { color: HEX.navy }, line: { color: HEX.navy, width: 0 }, objectName: nm('layer') });
      text(s, l, { x: W - M - 2.2, y, w: 2.1, h: 0.78, fontSize: 15, bold: true, color: 'FFFFFF', valign: 'middle' });
      box(s, M, y, CW - 2.45, 0.78, fill, HEX.line, 0.12);
      text(s, d, { x: M + 0.2, y, w: CW - 2.85, h: 0.78, fontSize: 14, color: HEX.ink2, valign: 'middle' });
    });
  }

  // ===== 17. Cost structure =====
  {
    const s = content('هيكل التكلفة — تُستكمل الأسعار بعد اعتماد المرحلة الأولى', 'التكلفة التقديرية', S3);
    const rows = [['تطبيق Flutter — شاشات الطبيب، التكامل، الأمان، النشر', 'كبير', 'يُحدد'], ['Laravel و API — الصلاحيات، القضايا، المستندات، الاعتماد، التدقيق', 'كبير', 'يُحدد'], ['AI / RAG — OCR، الفهرسة، الاسترجاع، الصياغة، التحقق، التقييم', 'كبير جدًا', 'يُحدد'], ['لوحة الإدارة وبوابة المحامي', 'متوسط–كبير', 'يُحدد'], ['الاختبارات والأمان والنشر', 'متوسط', 'يُحدد'], ['التكاليف التشغيلية المتكررة — استضافة، AI، OCR، SMS، دفع، متاجر', 'شهري', 'حسب الاستخدام'], ['الدعم الفني والصيانة — ضمان + باقات شهرية اختيارية', 'اختياري', 'يُحدد']];
    const hdr = (t) => ({ text: runs(t, {}), options: { bold: true, fill: { color: HEX.bg }, color: HEX.ink2, align: 'right', rtlMode: true } });
    const cell = (t, o = {}) => ({ text: runs(t, {}), options: { align: 'right', rtlMode: true, color: HEX.ink2, ...o } });
    const data = [[hdr('التكلفة'), hdr('حجم الجهد'), hdr('البند')], ...rows.map(([a, b, c]) => [cell(c, { color: HEX.faint }), cell(b, { bold: true, color: C.accent1 }), cell(a, { color: '10242C' })])];
    s.addTable(data, { x: M, y: 1.9, w: CW, colW: [2.2, 2.0, CW - 4.2], fontSize: 14, rowH: 0.52, border: { type: 'solid', color: HEX.line, pt: 0.75 }, valign: 'middle', margin: [0, 0.15, 0, 0.15], objectName: 'cost-table' });
    text(s, 'كل مرحلة تُسعّر كتعاقد مستقل، ولا تُدمج أسعار المرحلتين في إجمالي إلزامي واحد. أي تغيير خارج النطاق المعتمد يُوثّق ويُسعّر منفصلًا.', { x: M, y: 6.25, w: CW, h: 0.55, fontSize: 13, color: HEX.muted });
  }

  // ===== 18. Timeline =====
  {
    const s = content('3 أسابيع حتى التسليم — بثلاث مراحل استلام', 'الجدول الزمني المقترح', S3);
    const DAYS = 15; // working days (Sun–Thu) over 3 weeks
    const rows = [['الانطلاق: المواصفات والبيئات', 1, 2, HEX.navy800, 1], ['خلفية Laravel و API', 1, 7, HEX.teal500], ['تطبيق Flutter', 2, 12, HEX.teal500], ['محرك الذكاء الاصطناعي (RAG)', 3, 10, '7D5CD1', 10], ['بوابة المحامي ولوحة الإدارة', 5, 11, HEX.gold], ['التكاملات', 7, 11, HEX.navy800], ['الاختبارات والأمان والأداء', 9, 13, HEX.gray], ['اختبار القبول (UAT)', 12, 14, HEX.gray], ['الإطلاق والتسليم', 14, 15, HEX.navy800, 15]];
    const labelW = 3.0, gx0 = M, gx1 = W - M - labelW - 0.1, colW = (gx1 - gx0) / DAYS, y0 = 1.85, rh = 0.44;
    s.addShape(pres.shapes.RECTANGLE, { x: M, y: y0 - 0.38, w: CW, h: 0.78, fill: { color: HEX.bg }, line: { color: HEX.bg, width: 0 }, objectName: 'gantt-head' });
    ['الأسبوع 1', 'الأسبوع 2', 'الأسبوع 3'].forEach((wk, i) => text(s, wk, { x: gx1 - (i + 1) * 5 * colW, y: y0 - 0.36, w: 5 * colW, h: 0.34, fontSize: 12, bold: true, color: HEX.ink2, align: 'center', valign: 'middle' }));
    text(s, 'المسار / يوم العمل', { x: W - M - labelW, y: y0, w: labelW - 0.1, h: 0.4, fontSize: 13, bold: true, color: HEX.ink2, valign: 'middle' });
    for (let k = 1; k <= DAYS; k++) text(s, String(k), { x: gx1 - k * colW, y: y0, w: colW, h: 0.4, fontSize: 10, color: HEX.muted, align: 'center', valign: 'middle', rtlMode: false });
    rows.forEach(([n, a, b, c, ms], i) => {
      const y = y0 + 0.45 + i * rh;
      s.addShape(pres.shapes.LINE, { x: M, y: y + rh, w: CW, h: 0, line: { color: HEX.line, width: 0.75 }, objectName: nm('grid') });
      text(s, n, { x: W - M - labelW, y, w: labelW - 0.1, h: rh, fontSize: 12.5, bold: true, color: C.text1, valign: 'middle' });
      s.addShape(pres.shapes.ROUNDED_RECTANGLE, { x: gx1 - b * colW, y: y + 0.1, w: (b - a + 1) * colW, h: rh - 0.2, rectRadius: 0.08, fill: { color: c }, line: { color: c, width: 0 }, objectName: nm('bar') });
      if (ms) s.addShape(pres.shapes.DIAMOND, { x: gx1 - ms * colW + colW / 2 - 0.11, y: y + rh / 2 - 0.11, w: 0.22, h: 0.22, fill: { color: HEX.gold }, line: { color: 'FFFFFF', width: 1 }, objectName: nm('milestone') });
    });
    text(s, '◆ مرحلة استلام مرتبطة بدفعة · 15 يوم عمل من توقيع الاتفاق · مراجعة المتاجر خارج المدة وتخضع لـ Apple و Google.', { x: M, y: y0 + 0.5 + rows.length * rh, w: CW, h: 0.4, fontSize: 12, color: HEX.muted });
  }

  // ===== 19. Payments =====
  {
    const s = content('دفعات مرتبطة بالإنجاز والاستلام — لا بالتواريخ فقط', 'خطة الدفعات', S3);
    const rows = [['التعاقد والانطلاق — M0', 'اليوم 1', 'توقيع الاتفاق واعتماد خطة التنفيذ ووثيقة المواصفات', '40%'], ['دورة القضية كاملة — M1', 'نهاية الأسبوع 2', 'التسجيل والقضايا والمستندات والتحليل والمسودة بالاستشهادات ومراجعة المحامي والاعتماد تعمل من البداية للنهاية، مع تحقيق حد دقة الاستشهادات', '30%'], ['القبول والتسليم — M2', 'نهاية الأسبوع 3', 'لا عيوب حرجة أو عالية، توقيع اختبار القبول، النشر والرفع للمتاجر، وتسليم الكود والوثائق والتدريب', '30%']];
    const hdr = (t) => ({ text: runs(t, {}), options: { bold: true, fill: { color: HEX.bg }, color: HEX.ink2, align: 'right', rtlMode: true } });
    const cell = (t, o = {}) => ({ text: runs(t, {}), options: { align: 'right', rtlMode: true, color: HEX.ink2, ...o } });
    const stage = (name, when) => ({ text: runs([{ text: name, options: { bold: true, color: '10242C', breakLine: true } }, { text: when, options: { fontSize: 11.5, color: HEX.muted } }], {}), options: { align: 'right', rtlMode: true } });
    const data = [[hdr('النسبة'), hdr('معيار الاستلام'), hdr('المرحلة')], ...rows.map(([a, w, b, c]) => [({ text: c, options: { bold: true, color: C.accent1, align: 'right', rtlMode: false } }), cell(b), stage(a, w)])];
    s.addTable(data, { x: M, y: 1.9, w: CW, colW: [1.3, CW - 5.4, 4.1], fontSize: 14.5, rowH: 1.0, border: { type: 'solid', color: HEX.line, pt: 0.75 }, valign: 'middle', margin: [0, 0.15, 0, 0.15], objectName: 'payments-table' });
  }

  // ===== 20. Support & opex =====
  {
    const s = content('تشغيل مستقر بعد الإطلاق', 'الدعم والتكاليف التشغيلية', S3);
    const w = (CW - 0.35) / 2;
    const xr = W - M - w, xl = M;
    box(s, xr, 1.9, w, 4.6);
    text(s, 'التكاليف التشغيلية المتكررة', { x: xr + 0.3, y: 2.1, w: w - 0.6, h: 0.45, fontSize: 18, bold: true, color: C.text1 });
    const ops = ['استضافة سحابية داخل المملكة', 'استهلاك النموذج اللغوي والتضمين لكل قضية', 'OCR حسب عدد الصفحات', 'الرسائل النصية والإشعارات', 'رسوم بوابة الدفع وحسابات المتاجر', 'المراقبة والنطاق والشهادات'];
    text(s, ops.map((t, i) => ({ text: t, options: { bullet: true, breakLine: i < ops.length - 1 } })), { x: xr + 0.3, y: 2.7, w: w - 0.6, h: 2.9, fontSize: 15, color: HEX.ink2, paraSpaceAfter: 6 });
    text(s, 'تُدفع لمزودي الخدمات باسم العميل، وتُقدَّر بعد تحديد حجم الاستخدام.', { x: xr + 0.3, y: 5.75, w: w - 0.6, h: 0.5, fontSize: 13, color: HEX.muted });
    box(s, xl, 1.9, w, 4.6);
    text(s, 'الدعم الفني والصيانة', { x: xl + 0.3, y: 2.1, w: w - 0.6, h: 0.45, fontSize: 18, bold: true, color: C.text1 });
    text(s, [{ text: 'فترة ضمان ', options: { bold: true } }, { text: 'لإصلاح العيوب بعد الإطلاق، ثم باقات اختيارية:' }], { x: xl + 0.3, y: 2.7, w: w - 0.6, h: 0.5, fontSize: 15, color: HEX.ink2 });
    const tiers = [['أساسية', 'إصلاح الأعطال والتحديثات الأمنية', HEX.bg, HEX.ink2], ['قياسية', '+ توافق إصدارات iOS/Android ومراقبة استباقية', HEX.teal50, HEX.teal], ['متقدمة', '+ ساعات تطوير وتحديث قاعدة المعرفة', HEX.violet50, HEX.violet]];
    tiers.forEach(([t, d, bg, c], i) => {
      const y = 3.45 + i * 0.85;
      s.addShape(pres.shapes.ROUNDED_RECTANGLE, { x: xl + w - 0.3 - 1.25, y, w: 1.25, h: 0.42, rectRadius: 0.21, fill: { color: bg }, line: { color: bg, width: 0 }, objectName: nm('tier') });
      text(s, t, { x: xl + w - 0.3 - 1.25, y, w: 1.25, h: 0.42, fontSize: 13, bold: true, color: c, align: 'center', valign: 'middle' });
      text(s, d, { x: xl + 0.3, y, w: w - 1.85, h: 0.42, fontSize: 14, color: HEX.ink2, valign: 'middle' });
    });
  }

  // ===== 21. Next steps =====
  {
    const s = pres.addSlide({ masterName: 'DL_DARK', sectionTitle: S3 });
    text(s, 'الخطوات التالية', { x: M, y: 0.8, w: CW, h: 0.4, fontSize: 16, bold: true, color: HEX.mint });
    text(s, 'من النموذج إلى المنتج', { x: M, y: 1.25, w: CW, h: 0.9, fontSize: 38, bold: true, color: 'FFFFFF', valign: 'middle' });
    const st = [['التجربة والملاحظات', 'تجربة النموذج ومشاركته مع المحامين وتجميع الملاحظات.'], ['جولتا تعديل', 'تنفيذ الملاحظات على التصميمات والمسارات والوثائق.'], ['اعتماد المرحلة الأولى', 'توقيع نموذج الاعتماد وتثبيت النطاق.'], ['اتفاق المرحلة الثانية', 'السعر النهائي والدفعات، ثم التنفيذ والتسليم خلال 3 أسابيع.']];
    const w = (CW - 3 * 0.25) / 4;
    st.forEach(([t, b], i) => {
      const x = fromRight(i, w, 0.25);
      box(s, x, 2.6, w, 2.7, HEX.navy800, '2A5563');
      text(s, String(i + 1), { x: x + 0.25, y: 2.8, w: w - 0.5, h: 0.8, fontSize: 40, bold: true, color: HEX.mint });
      text(s, t, { x: x + 0.25, y: 3.65, w: w - 0.5, h: 0.45, fontSize: 17, bold: true, color: 'FFFFFF' });
      text(s, b, { x: x + 0.25, y: 4.15, w: w - 0.5, h: 1.0, fontSize: 14, color: HEX.paleText });
    });
    text(s, `${CFG.provider}   ·   ${CFG.providerContact}`, { x: M, y: 5.9, w: CW, h: 0.45, fontSize: 15, color: HEX.mint, rtlMode: false });
  }

  fs.mkdirSync(path.dirname(OUT), { recursive: true });
  await pres.writeFile({ fileName: OUT });
  await applyTheme(OUT, THEME);
  await dedupeParagraphProps(OUT);
  console.log('saved', OUT);
})();
