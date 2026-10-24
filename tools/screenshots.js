// Captures the official product screenshots from the prototype into site/screenshots/.
// Usage: (serve the site first: node serve.js 5173) then: node screenshots.js
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = (process.env.BASE_URL || 'http://localhost:5173') + '/prototype/index.html';
const OUT = path.resolve(__dirname, '..', 'site', 'screenshots');

const HIDE_CHROME = `document.head.insertAdjacentHTML('beforeend','<style id="shot">.demobar,.tour{display:none!important}.phone{height:100vh!important}.side,.topbar{top:0!important}.web{min-height:100vh!important}.side{height:100vh!important}</style>')`;
const MOBILE = { w: 390, h: 844, mobile: true, scale: 2 };
const WEB = { w: 1440, h: 900, scale: 1.5 };

// Ordered: later shots depend on state created by earlier ones (the demo case lifecycle).
const SHOTS = [
  { file: 'm01-onboarding', title: 'شاشة الترحيب', role: 'dentist', hash: '#/dentist/onboarding', ...MOBILE, reset: true },
  { file: 'm02-login', title: 'تسجيل الدخول', role: 'dentist', hash: '#/dentist/login', ...MOBILE },
  { file: 'm03-home', title: 'الرئيسية', role: 'dentist', hash: '#/dentist/home', ...MOBILE },
  { file: 'm04-cases', title: 'متابعة القضايا', role: 'dentist', hash: '#/dentist/cases', ...MOBILE },
  { file: 'm05-case', title: 'تفاصيل القضية', role: 'dentist', hash: '#/dentist/case/DL-2026-0118', ...MOBILE },
  { file: 'm06-new-case', title: 'قضية جديدة — نوع القضية', role: 'dentist', hash: '#/dentist/new', ...MOBILE },
  { file: 'm07-upload', title: 'رفع المستندات و OCR', role: 'dentist', hash: '#/dentist/new', ...MOBILE,
    actions: [{ eval: 'S.wizard.step=3;render()' }, { click: '[data-act=upload-all]', wait: 2600 }, { eval: "document.querySelector('.app-body').scrollTop=9999", wait: 300 }] },
  { file: 'm08-processing', title: 'التحليل الذكي', role: 'dentist', hash: '#/dentist/new', ...MOBILE,
    actions: [{ eval: 'S.wizard.step=4;render()' }, { click: '[data-act=create-case]', wait: 2400 }] },
  { file: 'm09-analysis', title: 'نتيجة التحليل', role: 'dentist', hash: '#/dentist/case/DL-2026-0152/analysis', ...MOBILE, actions: [{ eval: 'ensureAnalyzed();render()', wait: 300 }] },
  { file: 'm10-draft', title: 'مسودة الرد مع الاستشهادات', role: 'dentist', hash: '#/dentist/case/DL-2026-0152/draft', ...MOBILE },
  { file: 'm11-source', title: 'نص المادة النظامية', role: 'dentist', hash: '#/dentist/case/DL-2026-0152/draft', ...MOBILE, actions: [{ eval: "showSource('م19')", wait: 400 }] },
  { file: 'm12-send', title: 'الإرسال للمراجعة القانونية', role: 'dentist', hash: '#/dentist/case/DL-2026-0152/draft', ...MOBILE, actions: [{ click: '[data-act=send-review]', wait: 500 }] },
  { file: 'w01-launcher', title: 'بوابة النموذج التفاعلي', role: 'web', hash: '#/', ...WEB, keepChrome: true },
  { file: 'w02-lawyer-dashboard', title: 'لوحة المحامي', role: 'lawyer', hash: '#/lawyer/dashboard', ...WEB, actions: [{ eval: 'ensureSent();render()', wait: 300 }] },
  { file: 'w03-lawyer-queue', title: 'قائمة المراجعة', role: 'lawyer', hash: '#/lawyer/queue', ...WEB },
  { file: 'w04-lawyer-review', title: 'مساحة المراجعة والتعديل', role: 'lawyer', hash: '#/lawyer/review/DL-2026-0152', ...WEB, actions: [{ eval: 'ensureEdited();UI.draftVer=2;render()', wait: 400 }] },
  { file: 'w05-lawyer-approve', title: 'الاعتماد والتوقيع', role: 'lawyer', hash: '#/lawyer/review/DL-2026-0152', ...WEB, actions: [{ click: '[data-act=approve]', wait: 500 }] },
  { file: 'm13-approved', title: 'الرد المعتمد', role: 'dentist', hash: '#/dentist/case/DL-2026-0152/draft', ...MOBILE, actions: [{ eval: 'ensureApproved();UI.draftVer=2;render()', wait: 400 }] },
  { file: 'm14-case-approved', title: 'إشعار الاعتماد في القضية', role: 'dentist', hash: '#/dentist/case/DL-2026-0152', ...MOBILE },
  { file: 'm15-consult', title: 'المستشار القانوني الذكي', role: 'dentist', hash: '#/dentist/consult', ...MOBILE, actions: [{ eval: 'S.chat=[];render()' }, { click: '[data-act=ask]', wait: 1700 }] },
  { file: 'm16-templates', title: 'النماذج والإقرارات', role: 'dentist', hash: '#/dentist/templates', ...MOBILE },
  { file: 'm17-template', title: 'معاينة إقرار الموافقة', role: 'dentist', hash: '#/dentist/template/t1', ...MOBILE },
  { file: 'm18-library', title: 'مكتبة الأنظمة', role: 'dentist', hash: '#/dentist/library', ...MOBILE },
  { file: 'm19-notifications', title: 'التنبيهات', role: 'dentist', hash: '#/dentist/notifications', ...MOBILE },
  { file: 'm20-profile', title: 'الحساب والتأمين', role: 'dentist', hash: '#/dentist/profile', ...MOBILE },
  { file: 'w06-admin-overview', title: 'لوحة الإدارة', role: 'admin', hash: '#/admin/overview', ...WEB },
  { file: 'w07-admin-doctors', title: 'الأطباء والتوثيق', role: 'admin', hash: '#/admin/doctors', ...WEB },
  { file: 'w08-admin-kb', title: 'قاعدة المعرفة (RAG)', role: 'admin', hash: '#/admin/kb', ...WEB, actions: [{ click: '[data-act=kb-test]', wait: 400 }] },
  { file: 'w09-admin-ai', title: 'إعدادات الذكاء الاصطناعي', role: 'admin', hash: '#/admin/ai', ...WEB },
  { file: 'w10-admin-roles', title: 'الأدوار والصلاحيات', role: 'admin', hash: '#/admin/roles', ...WEB },
  { file: 'w11-admin-audit', title: 'سجل التدقيق', role: 'admin', hash: '#/admin/audit', ...WEB },
  { file: 'w12-dentist-desktop', title: 'تجربة الطبيب على الكمبيوتر', role: 'web', hash: '#/dentist/home', ...WEB, keepChrome: true },
];

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ['--no-sandbox', '--font-render-hinting=none'] });
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  const manifest = [];
  for (const s of SHOTS) {
    await page.setViewport({ width: s.w, height: s.h, deviceScaleFactor: s.scale, isMobile: !!s.mobile, hasTouch: !!s.mobile });
    if (s.reset) {
      await page.goto(BASE + '#/', { waitUntil: 'networkidle0' });
      await page.evaluate(() => localStorage.clear());
    }
    await page.goto(BASE + s.hash, { waitUntil: 'networkidle0' });
    await page.reload({ waitUntil: 'networkidle0' });
    await page.evaluate(() => document.fonts.ready);
    await page.evaluate(() => { S.tour.on = false; UI.sideOpen = false; render(); });
    for (const a of s.actions || []) {
      if (a.eval) await page.evaluate(a.eval);
      if (a.click) await page.click(a.click);
      await new Promise((r) => setTimeout(r, a.wait || 250));
    }
    if (!s.keepChrome) await page.evaluate(HIDE_CHROME);
    await new Promise((r) => setTimeout(r, 300));
    await page.screenshot({ path: path.join(OUT, s.file + '.png') });
    manifest.push({ file: s.file + '.png', title: s.title, role: s.role, mobile: !!s.mobile });
    console.log('saved', s.file);
  }
  fs.writeFileSync(path.join(OUT, 'manifest.json'), JSON.stringify(manifest, null, 2));
  fs.writeFileSync(path.join(OUT, 'manifest.js'), 'window.DL_SHOTS = ' + JSON.stringify(manifest) + ';\n');
  if (errors.length) console.log('PAGE ERRORS:\n' + errors.join('\n'));
  await browser.close();
})();
