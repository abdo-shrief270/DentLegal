/* Router, shell and interactions */
let lastHash = null;

function demobar(role) {
  const r = (k, ic, l, go) => `<button class="${role === k ? 'on' : ''}" data-go="${go}" title="${l}">${icon(ic, 'sm')}<span>${l}</span></button>`;
  return `<header class="demobar"><div class="brand" data-go="#/">${logo()}<div>DentLegal<br><small>نموذج تفاعلي — المرحلة الأولى</small></div></div>
    <span class="mock-flag">بيانات تجريبية</span>
    <div class="seg">${r('dentist', 'stethoscope', 'الطبيب', '#/dentist/home')}${r('lawyer', 'scale', 'المحامي', '#/lawyer/dashboard')}${r('admin', 'settings', 'الإدارة', '#/admin/overview')}</div>
    <div class="sp"></div>
    ${S.tour.on ? '' : `<button class="btn btn-sm tour-btn" data-act="tour-start">${icon('play', 'sm')}<span class="hide-sm">جولة دورة القضية</span></button>`}
    <button class="btn btn-sm" data-act="reset" title="إعادة ضبط بيانات العرض">${icon('refresh', 'sm')}<span class="hide-sm">إعادة الضبط</span></button>
    <a class="btn btn-sm hide-sm" href="../index.html">${icon('external', 'sm')} ملف العرض</a></header>`;
}
function statusbar(dark) {
  return `<div class="statusbar" style="${dark ? 'background:var(--navy-900);color:#fff' : ''}"><span>9:41</span><span class="icons"><b></b><i></i></span></div>`;
}
function guideHtml(g) {
  if (!g) return '';
  const jump = [['onboarding', 'الترحيب'], ['login', 'الدخول'], ['register', 'التسجيل'], ['home', 'الرئيسية'], ['cases', 'القضايا'], ['new', 'قضية جديدة'], ['consult', 'المستشار الذكي'], ['templates', 'النماذج'], ['library', 'مكتبة الأنظمة'], ['notifications', 'التنبيهات'], ['profile', 'الحساب'], ['subscription', 'الاشتراك']];
  return `<div class="card"><div class="kicker">${icon('phone', 'sm')} تطبيق طبيب الأسنان · iOS و Android</div><h2>${g.t}</h2><p>${g.p}</p>${g.li.length ? `<ul>${g.li.map((x) => `<li>${x}</li>`).join('')}</ul>` : ''}</div>
    <div class="card" style="margin-top:12px"><div class="small bold" style="margin-bottom:8px">انتقال سريع بين الشاشات</div><div class="row wrap" style="gap:6px">${jump.map(([k, l]) => `<span class="chip" data-go="#/dentist/${k}">${l}</span>`).join('')}</div></div>`;
}
function launcher() {
  const steps = ['تسجيل القضية', 'رفع المستندات', 'OCR', 'التحليل الذكي', 'مسودة مع مصادر', 'مراجعة المحامي', 'الاعتماد والتوقيع', 'استلام الطبيب', 'سجل التدقيق'];
  return `<section class="launch"><div class="wrap">
    <span class="badge b-gold">${icon('sparkles')}النموذج التفاعلي — المرحلة الأولى (Prototype)</span>
    <h1>DentLegal — الحماية القانونية لأطباء الأسنان</h1>
    <p class="lead">منصة تساعد طبيب الأسنان في المملكة على إدارة الشكاوى والقضايا الطبية: تحليل ذكي مبني على الأنظمة السعودية، ومسودات ردود موثّقة بالمصادر، واعتماد نهائي من محامٍ مرخّص.</p>
    <div class="roles">
      ${[['stethoscope', 'طبيب الأسنان', 'تطبيق الجوال (iOS / Android)', 'تسجيل القضايا، رفع المستندات، التحليل الذكي، المستشار القانوني، النماذج، ومتابعة الجلسات.', '#/dentist/onboarding'],
        ['scale', 'المحامي المراجِع', 'بوابة ويب', 'قائمة مراجعة بمهل زمنية، مساحة عمل لتعديل المسودات مع المصادر، والاعتماد والتوقيع.', '#/lawyer/dashboard'],
        ['settings', 'مدير المنصة', 'لوحة تحكم ويب', 'المؤشرات، توثيق الأطباء، إسناد المحامين، قاعدة المعرفة، الصلاحيات وسجل التدقيق.', '#/admin/overview']].map(([i, t, tag, p, go]) => `
        <div class="role-card" data-go="${go}"><span class="ic">${icon(i, 'xl')}</span><span class="tag">${tag}</span><h3>${t}</h3><p>${p}</p><span class="row" style="color:#8fe3d3;font-weight:600;margin-top:auto">ابدأ التجربة ${icon('arrowL', 'sm')}</span></div>`).join('')}
    </div>
    <div class="tour-cta"><div class="grow"><div class="h2">جولة موجهة: دورة القضية كاملة في 10 خطوات</div><p style="margin:6px 0 0;color:#cfe0e4">من تسجيل الشكوى في تطبيق الطبيب حتى اعتماد المحامي للرد — مع التنقل التلقائي بين الأدوار الثلاثة.</p><div class="steps">${steps.map((s) => `<span>${s}</span>`).join('')}</div></div>
      <button class="btn btn-primary btn-lg" data-act="tour-start">${icon('play')} ابدأ الجولة</button></div>
    <div class="foot">${icon('lock', 'sm')} جميع البيانات في هذا النموذج تجريبية ولا تتضمن أي بيانات مرضى حقيقية · لا يوجد خادم أو ذكاء اصطناعي فعلي في هذه المرحلة
      <span class="sp" style="flex:1"></span><a class="btn btn-sm" style="background:rgba(255,255,255,.1);color:#fff" href="../index.html">${icon('arrowR', 'sm')} ملف العرض الكامل</a></div>
  </div></section>`;
}

function render() {
  const parts = location.hash.replace(/^#\/?/, '').split('/').filter(Boolean).map(decodeURIComponent);
  const role = parts[0] || '';
  const same = lastHash === location.hash;
  const prevScroll = same ? document.querySelector('.app-body')?.scrollTop || 0 : 0;
  const prevWin = same ? window.scrollY : 0;
  const ae = document.activeElement;
  const focusModel = ae && ae.dataset ? ae.dataset.model : null;
  const caret = focusModel ? ae.selectionStart : null;

  let html = demobar(role);
  if (role === 'dentist') {
    const sub = parts[1] || 'home';
    let res;
    if (sub === 'case' && parts[2]) res = parts[3] && DENTIST[parts[3]] ? DENTIST[parts[3]](parts[2]) : DENTIST.case(parts[2]);
    else if (sub === 'processing' || sub === 'template') res = DENTIST[sub](parts[2]);
    else res = (DENTIST[sub] || DENTIST.home)();
    html += `<div class="stage"><div class="phone"><div class="notch"></div><div class="screen">${statusbar(res.dark)}${res.html}</div></div><aside class="guide">${tourHtml()}${guideHtml(res.guide)}</aside></div>`;
  } else if (role === 'lawyer') {
    const sub = parts[1] || 'dashboard';
    html += sub === 'review' ? LAWYER.review(parts[2]) : (LAWYER[sub] || LAWYER.dashboard)();
  } else if (role === 'admin') {
    html += (ADMINV[parts[1]] || ADMINV.overview)();
  } else {
    html += launcher();
  }
  if (role !== 'dentist') html += tourHtml();
  document.getElementById('root').innerHTML = html;

  if (same) {
    const b = document.querySelector('.app-body');
    if (b) b.scrollTop = prevScroll;
    window.scrollTo(0, prevWin);
  } else {
    window.scrollTo(0, 0);
  }
  if (focusModel) {
    const el = document.querySelector(`[data-model="${focusModel}"]`);
    if (el) { el.focus(); try { el.setSelectionRange(caret, caret); } catch (e) {} }
  }
  const chat = document.getElementById('chat-body');
  if (chat) chat.scrollTop = chat.scrollHeight;
  tourHighlight();
  lastHash = location.hash;
}

/* ---------- consultation bot ---------- */
function askBot(q) {
  q = (q || '').trim();
  if (!q) return;
  S.chat.push({ who: 'me', text: q });
  S.chat.push({ who: 'bot', typing: true });
  render();
  setTimeout(() => {
    const hit = CONSULT_QA.find((x) => x.q === q) || CONSULT_QA.find((x) => x.keys.some((k) => q.includes(k)));
    S.chat.pop();
    S.chat.push(hit ? { who: 'bot', text: hit.a, cites: hit.cites, q }
      : { who: 'bot', q, cites: [], text: 'سؤال مهم. بحثت في المصادر المفهرسة ولم أجد نصًا صريحًا يكفي للإجابة بثقة عالية، ولا أقدّم إجابات دون سند نظامي. أقترح تحويل سؤالك إلى محامٍ مرخّص للرد خلال 24 ساعة.' });
    save();
    if (location.hash.includes('/consult')) render();
  }, 1100);
}

/* ---------- actions ---------- */
const ACTIONS = {
  'close-modal': () => closeModal(),
  source: (el) => showSource(el.dataset.key),
  docref: (el) => toast(`مستند من ملف القضية: ${el.dataset.name} — يُفتح في عارض آمن`, 'clip'),
  tab: (el) => { UI[el.dataset.k] = el.dataset.v; render(); },
  toast: (el) => toast(el.dataset.msg, 'info'),
  'toast-privacy': () => toast('سياسة الخصوصية متوافقة مع نظام حماية البيانات الشخصية', 'lock'),
  'toast-help': () => toast('مركز المساعدة: أسئلة شائعة ودعم عبر المحادثة', 'help'),
  switch: (el) => el.classList.toggle('on'),
  radio: (el) => { el.parentElement.querySelectorAll('.radio-card').forEach((x) => x.classList.remove('on')); el.classList.add('on'); },
  side: () => { UI.sideOpen = !UI.sideOpen; render(); },
  reset: () => openModal('إعادة ضبط بيانات العرض', '<p style="margin:0">ستُحذف القضايا والتعديلات التي أنشأتها أثناء التجربة، وتعود البيانات التجريبية لحالتها الأولى.</p>', `<button class="btn btn-danger" data-act="confirm-reset">${icon('refresh')} إعادة الضبط</button><button class="btn btn-secondary" data-act="close-modal">إلغاء</button>`, { web: true }),
  'confirm-reset': () => { resetDemo(); Object.assign(UI, { casesTab: 'all', caseTab: 'overview', draftVer: null, kbTest: null, editMode: false }); closeModal(); location.hash = '#/'; render(); toast('أُعيد ضبط بيانات العرض'); },

  /* auth */
  nafath: () => {
    openModal(`${icon('scanFace')} النفاذ الوطني الموحد`, `<div class="stack center" style="align-items:center"><p style="margin:0">افتح تطبيق نفاذ واختر الرقم التالي لتأكيد الدخول:</p>
      <div style="width:84px;height:84px;border-radius:50%;background:var(--teal-50);color:var(--teal-700);display:grid;place-items:center;font-size:34px;font-weight:700">38</div>
      <div class="row muted small"><span class="spin" style="display:inline-grid">${icon('refresh', 'sm')}</span> بانتظار التأكيد من تطبيق نفاذ…</div>
      <div class="tiny faint">محاكاة — التكامل الفعلي اختياري في المرحلة الثانية ويتطلب موافقة الجهة المشغّلة.</div></div>`);
    setTimeout(() => { if (!document.getElementById('modal')) return; closeModal(); addAudit(ME.name, 'طبيب', 'تسجيل دخول عبر نفاذ', 'جوال iPhone'); save(); location.hash = '#/dentist/home'; toast('تم التحقق من هويتك عبر نفاذ'); }, 2200);
  },
  biometric: () => { addAudit(ME.name, 'طبيب', 'تسجيل دخول حيوي', 'جوال iPhone'); save(); location.hash = '#/dentist/home'; toast('تم الدخول بالبصمة', 'key'); },
  'login-ok': () => { addAudit(ME.name, 'طبيب', 'تسجيل دخول (OTP)', 'جوال iPhone'); save(); location.hash = '#/dentist/home'; },
  logout: () => { location.hash = '#/dentist/onboarding'; },
  pay: () => {
    openModal('تم الاشتراك بنجاح', `<div class="stack center" style="align-items:center"><span style="color:var(--green-600)">${icon('checkCircle', 'xl')}</span><p style="margin:0">تم تفعيل <b>الاشتراك الاحترافي</b> (عملية دفع تجريبية). ستصلك الفاتورة الإلكترونية على بريدك.</p></div>`, `<button class="btn btn-primary btn-block" data-act="close-modal" data-go="#/dentist/home">الذهاب للرئيسية</button>`);
  },
  sessions: () => openModal('الأجهزة والجلسات النشطة', `<div class="list">${[['phone', 'iPhone 15 — تطبيق DentLegal', 'جدة · الآن (هذا الجهاز)', ''], ['monitor', 'متصفح Chrome — Windows', 'جدة · منذ يومين', '<button class="btn btn-danger btn-sm" data-act="toast" data-msg="تم إنهاء الجلسة">إنهاء</button>']].map(([i, t, s, b]) => `<div class="list-item" style="cursor:default"><span class="ic gray">${icon(i)}</span><div class="grow"><div class="t">${t}</div><div class="s">${s}</div></div>${b}</div>`).join('')}</div>`),
  'export-data': () => toast('سنجهّز نسخة من بياناتك ونرسلها خلال 24 ساعة (حق الوصول)', 'download'),

  /* dentist */
  'new-case': () => { if (!caseById(DEMO_CASE_ID)) S.wizard = freshWizard(); else S.wizard.step = 1; save(); location.hash = '#/dentist/new'; },
  wkind: (el) => { S.wizard.kind = el.dataset.v; save(); render(); },
  'wiz-next': () => {
    const w = S.wizard;
    if (w.step === 3 && CASE_DOCS.some((d) => d.req && w.uploaded[d.key] !== 'done')) { toast('ارفع المستندات المطلوبة (*) أولًا — أو اضغط «رفع الكل»', 'alert'); return; }
    w.step = Math.min(4, w.step + 1); save(); render(); document.querySelector('.app-body')?.scrollTo(0, 0);
  },
  'wiz-prev': () => { S.wizard.step = Math.max(1, S.wizard.step - 1); save(); render(); },
  upload: (el) => uploadDoc(el.dataset.key),
  'upload-all': () => CASE_DOCS.forEach((d, i) => setTimeout(() => uploadDoc(d.key), i * 250)),
  'create-case': () => {
    if (caseById(DEMO_CASE_ID)) { toast('القضية التجريبية مُنشأة مسبقًا — استخدم «إعادة الضبط» لتكرار التجربة', 'info'); location.hash = `#/dentist/case/${DEMO_CASE_ID}`; return; }
    const c = ensureCaseCreated(); UI.procStep = 0; location.hash = `#/dentist/processing/${c.id}`;
    if (S.tour.on && S.tour.step < 3) { S.tour.step = 3; save(); }
  },
  'analyze-doc': () => openModal(`${icon('scanText')} تحليل مستند منفرد`, `<div class="stack"><p class="small" style="margin:0">ارفع أي مستند (إقرار، خطاب، تقرير) ليقيّم النظام كفايته القانونية. مثال تجريبي:</p>
      <div class="doc-tile"><span class="ic">${icon('file')}</span><div class="grow"><div class="t">إقرار-تقويم-المريض-P5124.pdf</div><div class="s">صفحتان · تم الاستخراج</div></div><span class="badge b-green">تم</span></div>
      <div class="card tint"><div class="row between"><b class="small">نتيجة التقييم</b><span class="badge b-amber">يغطي 5 من 6 عناصر</span></div>
      <div class="stack sm small" style="margin-top:8px"><div class="row">${icon('check', 'sm')} وصف الإجراء ومدته</div><div class="row">${icon('check', 'sm')} المخاطر والمضاعفات</div><div class="row">${icon('check', 'sm')} توقيع المريض والتاريخ</div><div class="row" style="color:var(--amber-600)">${icon('alert', 'sm')} لا يذكر البدائل العلاجية وخيار عدم العلاج</div></div>
      <div class="tiny muted" style="margin-top:8px">المرجع: ${cite('م19')} · يُقترح استخدام النموذج المحدّث من مكتبة الإقرارات.</div></div></div>`, `<button class="btn btn-primary" data-act="close-modal" data-go="#/dentist/templates">فتح مكتبة الإقرارات</button>`),
  ver: (el) => { UI.draftVer = +el.dataset.v; render(); },
  'ai-revise': () => openModal(`${icon('sparkles')} طلب تعديل على المسودة`, `<div class="field"><label>ما الذي تريد تعديله؟</label><textarea class="textarea">أضف أن المريض راجع العيادة مرة ثانية بتاريخ 6 سبتمبر وكان التنميل في تحسن.</textarea></div><p class="tiny muted">يعيد المحرك الصياغة مع الحفاظ على الاستشهادات، ويُنشئ إصدارًا جديدًا يمكنك مقارنته.</p>`, `<button class="btn btn-primary" data-act="revise-ok">${icon('sparkles')} إعادة الصياغة</button><button class="btn btn-secondary" data-act="close-modal">إلغاء</button>`),
  'revise-ok': () => { closeModal(); toast('تمت إعادة الصياغة — راجع الفقرة المعدلة (محاكاة)', 'sparkles'); },
  'send-review': () => {
    const l = LAWYERS[0];
    openModal('إرسال للمراجعة القانونية', `<div class="stack"><div class="row"><div class="avatar gold">${l.initials}</div><div class="grow"><div class="bold">${l.name}</div><div class="tiny muted">${l.firm} · ${l.focus}</div></div><span class="badge b-green">متاحة</span></div>
      <div class="alert blue"><span class="ic">${icon('clock')}</span><div>أُسند الطلب تلقائيًا حسب التخصص والحِمل. المهلة المتوقعة: <b>خلال 24 ساعة</b> — قبل موعد الإفادة بوقت كافٍ.</div></div>
      <label class="check"><input type="checkbox" checked><span>أوافق على اطلاع المحامي المُسند على مستندات هذه القضية فقط لغرض المراجعة.</span></label>
      <div class="field"><label>ملاحظة للمحامي (اختياري)</label><textarea class="textarea" style="min-height:64px">الجلسة يوم 29 أكتوبر، أرجو الاعتماد قبلها.</textarea></div></div>`,
      `<button class="btn btn-primary grow" data-act="confirm-send">${icon('send')} تأكيد الإرسال</button>`);
  },
  'confirm-send': () => { ensureSent(); closeModal(); location.hash = `#/dentist/case/${DEMO_CASE_ID}`; toast('أُرسلت المسودة إلى أ. سارة العتيبي للمراجعة', 'send'); },
  download: () => toast('تم تجهيز ملف PDF موقّع إلكترونيًا مع رمز التحقق (محاكاة)', 'download'),
  'mark-submitted': () => {
    const c = caseById(DEMO_CASE_ID) || ensureApproved();
    c.status = 'submitted'; addEvent(c, 'تأكيد تقديم الإفادة المعتمدة للجهة'); addAudit(ME.name, 'طبيب', 'تأكيد تقديم الرد للجهة', c.id); save(); render();
    toast('سُجّل تقديم الرد — سنذكّرك بموعد الإفادة', 'checkCircle');
  },
  resubmit: (el) => { const c = caseById(el.dataset.id); if (!c) return; c.status = 'lawyer_review'; c.sentAt = nowStamp(); addEvent(c, 'إرفاق المعلومات المطلوبة وإعادة الإرسال'); addAudit(ME.name, 'طبيب', 'إعادة إرسال بعد استكمال المعلومات', c.id); save(); render(); toast('أُعيد الإرسال للمحامي'); },
  'open-notif': (el) => { const n = S.notifications.find((x) => x.id === el.dataset.id); if (!n) return; n.read = true; save(); if (n.go) location.hash = n.go; else render(); },
  'read-all': () => { S.notifications.forEach((n) => (n.read = true)); save(); render(); },
  ask: (el) => askBot(el.dataset.q),
  'ask-input': () => { const i = document.getElementById('ask-input'); if (i) askBot(i.value); },
  escalate: (el) => openModal(`${icon('scale')} تحويل السؤال إلى محامٍ`, `<div class="stack"><div class="field"><label>سؤالك</label><textarea class="textarea" id="esc-q">${esc(el.dataset.q || '')}</textarea></div><div class="alert blue"><span class="ic">${icon('clock')}</span><div>يرد محامٍ مرخّص خلال 24 ساعة، وتصلك الإجابة كإشعار.</div></div></div>`, `<button class="btn btn-primary grow" data-act="escalate-ok">${icon('send')} إرسال للمحامي</button>`),
  'escalate-ok': () => { const q = document.getElementById('esc-q')?.value || 'استشارة'; S.consults.unshift({ id: 'c' + Date.now(), q, by: ME.name, at: nowStamp(), status: 'جديدة' }); addAudit(ME.name, 'طبيب', 'تحويل استشارة لمحامٍ', 'الاستشارات'); save(); closeModal(); toast('أُرسل سؤالك للمحامي — ستصلك الإجابة خلال 24 ساعة', 'send'); },

  /* lawyer */
  'demo-send': () => { ensureSent(); render(); toast('جُهّز الطلب التجريبي في قائمة المراجعة'); },
  'edit-mode': () => { UI.editMode = !UI.editMode; render(); if (UI.editMode) toast('وضع التحرير: عدّل النص مباشرة داخل المسودة', 'pen'); },
  'apply-edits': () => { ensureEdited(); UI.draftVer = 2; render(); toast('طُبّقت 5 تعديلات — أُنشئ الإصدار 2', 'pen'); if (S.tour.on && S.tour.step === 7) { S.tour.step = 8; save(); render(); } },
  'save-version': () => toast('حُفظ الإصدار الحالي في سجل الإصدارات', 'copy'),
  'view-doc': (el) => { const id = location.hash.split('/')[3]; addAudit(LAWYERS[0].name, 'محامٍ', `عرض مستند: ${el.dataset.name}`, id || ''); save();
    openModal(`${icon('file')} ${esc(el.dataset.name)}`, `<div class="paper" style="min-height:260px"><div class="meta">معاينة آمنة — علامة مائية باسم المستخدم · التنزيل معطّل</div>${'<div style="height:10px;border-radius:6px;background:var(--bg-2);margin:10px 0;width:VAR%"></div>'.repeat(9).replace(/VAR/g, () => 55 + Math.round(Math.random() * 45))}<div class="tiny faint center" style="margin-top:20px">محتوى المستند غير معروض في النموذج التجريبي</div></div>`, '', { web: true }); },
  'request-info': () => openModal(`${icon('chats')} طلب معلومات من الطبيب`, `<div class="field"><label>الطلب</label><textarea class="textarea" id="ri-text">يرجى إرفاق ملاحظة الزيارة المؤرخة 28 أغسطس التي تثبت تقديم تعليمات ما بعد العملية، وملاحظة المتابعة بتاريخ 6 سبتمبر.</textarea></div><p class="tiny muted">تتوقف مهلة المراجعة حتى يرد الطبيب.</p>`, `<button class="btn btn-primary" data-act="request-ok">${icon('send')} إرسال الطلب</button><button class="btn btn-secondary" data-act="close-modal">إلغاء</button>`, { web: true }),
  'request-ok': () => {
    const id = location.hash.split('/')[3]; const c = caseById(id); if (!c) return;
    c.status = 'needs_info'; addEvent(c, 'طلب المحامي معلومات إضافية'); addAudit(LAWYERS[0].name, 'محامٍ', 'طلب معلومات إضافية من الطبيب', id);
    if (c.owner === ME.id) addNotif({ icon: 'alert', cls: 'amber', t: 'المحامي يطلب معلومات إضافية', s: `${id} — ملاحظات المتابعة وتعليمات ما بعد العملية`, go: `#/dentist/case/${id}` });
    save(); closeModal(); render(); toast('أُرسل الطلب للطبيب');
  },
  approve: () => {
    const id = location.hash.split('/')[3];
    openModal(`${icon('stamp')} اعتماد الرد وتوقيعه`, `<div class="stack"><div class="small">القضية <b class="ltr">${id}</b> — الإصدار ${S.edits[id] ? 2 : 1}</div>
      <label class="check"><input type="checkbox" checked><span>راجعت جميع الاستشهادات وتحققت من مطابقتها للمصادر النظامية.</span></label>
      <label class="check"><input type="checkbox" checked><span>راجعت الوقائع مقابل مستندات القضية.</span></label>
      <label class="check"><input type="checkbox" checked><span>أعتمد هذا الرد بصفتي محاميًا مرخّصًا، وأتحمل مسؤولية مضمونه المهني.</span></label>
      <div class="field"><label>رمز التوقيع (OTP المرسل لجوالك)</label><div class="otp">${[2, 9, 0, 6].map((n) => `<input value="${n}" maxlength="1">`).join('')}</div></div></div>`,
      `<button class="btn btn-gold grow" data-act="confirm-approve">${icon('stamp')} اعتماد وتوقيع</button><button class="btn btn-secondary" data-act="close-modal">إلغاء</button>`, { web: true });
  },
  'confirm-approve': () => { const id = location.hash.split('/')[3]; approveCase(id); UI.editMode = false; UI.draftVer = 2; closeModal(); render(); toast('تم اعتماد الرد وتوقيعه — أُشعر الطبيب', 'badge'); },
  'answer-consult': (el) => {
    const q = S.consults.find((x) => x.id === el.dataset.id); if (!q) return;
    const hit = CONSULT_QA.find((x) => x.keys.some((k) => q.q.includes(k)));
    openModal(`${icon('chats')} استشارة من ${esc(q.by)}`, `<div class="stack"><div class="card flat"><b>${esc(q.q)}</b></div><div class="field"><label>${icon('sparkles', 'sm')} مسودة إجابة مقترحة (قابلة للتعديل)</label><textarea class="textarea" style="min-height:130px">${hit ? hit.a.replace(/<[^>]+>/g, '') : 'الأصل وجوب الحصول على موافقة المريض قبل أي عمل طبي، ولا يجوز إجراء العلاج دون رضاه إلا في الحالات الطارئة وفق ضوابطها. يُوثَّق رفض المريض كتابيًا على نموذج رفض العلاج مع شرح العواقب.'}</textarea></div>
      <div class="row wrap">${(hit ? hit.cites : ['م19']).map(cite).join('')}</div></div>`,
      `<button class="btn btn-primary grow" data-act="consult-ok" data-id="${q.id}">${icon('send')} اعتماد الإجابة وإرسالها</button>`, { web: true });
  },
  'consult-ok': (el) => { const q = S.consults.find((x) => x.id === el.dataset.id); if (q) q.status = 'تم الرد'; addAudit(LAWYERS[0].name, 'محامٍ', 'اعتماد إجابة استشارة', q?.by || ''); save(); closeModal(); render(); toast('أُرسلت الإجابة المعتمدة للطبيب'); },

  /* admin */
  verify: (el) => {
    const d = DOCTORS.find((x) => x.id === el.dataset.id); if (!d) return;
    openModal(`${icon('userCheck')} توثيق حساب ${d.name}`, `<div class="stack"><div class="grid2 small">${[['التخصص', d.spec], ['المدينة', d.city], ['رقم التسجيل المهني', d.license], ['تاريخ الطلب', fmtDate(d.joined)]].map(([l, v]) => `<div><div class="muted tiny">${l}</div><b>${v}</b></div>`).join('')}</div>
      <div class="doc-tile"><span class="ic">${icon('file')}</span><div class="grow"><div class="t">بطاقة التصنيف المهني.pdf</div><div class="s">مرفوعة من الطبيب</div></div>${icon('eye', 'sm')}</div>
      <div class="stack sm small"><div class="row"><span class="yes">${icon('checkCircle', 'sm')}</span> تطابق الاسم مع بطاقة التصنيف</div><div class="row"><span class="yes">${icon('checkCircle', 'sm')}</span> صيغة رقم التسجيل صحيحة</div><div class="row"><span class="partial">${icon('alert', 'sm')}</span> التحقق من سريان الترخيص: يدوي حاليًا — تكامل مستقبلي مع الهيئة السعودية للتخصصات الصحية</div></div></div>`,
      `<button class="btn btn-primary" data-act="verify-ok" data-id="${d.id}" data-v="موثّق">${icon('check')} توثيق الحساب</button><button class="btn btn-danger" data-act="verify-ok" data-id="${d.id}" data-v="مرفوض">رفض</button>`, { web: true });
  },
  'verify-ok': (el) => { const d = DOCTORS.find((x) => x.id === el.dataset.id); S.verified[d.id] = el.dataset.v; addAudit(ADMIN.name, 'إدارة', el.dataset.v === 'موثّق' ? 'توثيق حساب طبيب' : 'رفض توثيق حساب', d.name); save(); closeModal(); render(); toast(el.dataset.v === 'موثّق' ? 'تم توثيق الحساب وإشعار الطبيب' : 'تم رفض الطلب مع إشعار الطبيب بالسبب'); },
  'kb-test': () => {
    const q = document.getElementById('kb-q')?.value || '';
    const words = q.split(/\s+/).filter((w) => w.length > 2).map((w) => w.replace(/^ال/, ''));
    const hits = Object.keys(SOURCES).map((k) => { const s = SOURCES[k]; const hay = s.title + ' ' + s.text; const m = words.filter((w) => hay.includes(w)).length; return [k, Math.min(0.97, 0.52 + m * 0.13 + (k === 'م19' && /موافق|رضا/.test(q) ? 0.2 : 0))]; })
      .sort((a, b) => b[1] - a[1]).slice(0, 4);
    UI.kbTest = { q, hits }; render();
  },
  'kb-upload': () => openModal(`${icon('upload')} رفع مصدر نظامي جديد`, `<div class="stack"><div class="field"><label>الملف</label><select class="select" id="kb-file"><option>نظام حماية البيانات الشخصية ولائحته التنفيذية.pdf</option><option>دليل إجراءات الهيئات الصحية الشرعية.pdf</option></select></div>
      <div class="grid2"><div class="field"><label>نوع المصدر</label><select class="select"><option>نظام / لائحة</option><option>تعميم</option><option>دليل إجرائي</option></select></div><div class="field"><label>تاريخ السريان</label><input class="input" type="date" value="${DEMO_TODAY}"></div></div>
      <label class="check"><input type="checkbox" checked><span>أرشفة النسخة السابقة تلقائيًا إن وجدت</span></label></div>`, `<button class="btn btn-primary" data-act="kb-upload-ok">${icon('database')} رفع وفهرسة</button>`, { web: true }),
  'kb-upload-ok': () => {
    const name = (document.getElementById('kb-file')?.value || '').replace('.pdf', '');
    const doc = { name, ed: 'إصدار تجريبي', pages: 0, chunks: 0, status: 'قيد الفهرسة', ver: 'v1', updated: DEMO_TODAY, src: name + '.pdf' };
    S.kbExtra.push(doc); addAudit(ADMIN.name, 'إدارة', 'رفع مصدر جديد للفهرسة', name); save(); closeModal(); render(); toast('بدأت الفهرسة: OCR ← تقسيم حسب المادة ← تضمين', 'database');
    setTimeout(() => { doc.status = 'مفهرس'; doc.pages = 31; doc.chunks = 154; save(); if (location.hash.includes('/admin/kb')) render(); toast(`اكتملت فهرسة «${name}» (154 مقطعًا)`); }, 2600);
  },

  /* tour */
  'tour-start': () => { resetDemoCase(); tourGo(0); },
  'tour-next': () => tourGo(S.tour.step + 1),
  'tour-prev': () => tourGo(S.tour.step - 1),
  'tour-end': () => { S.tour.on = false; save(); render(); },
  'tour-min': () => { S.tour.min = !S.tour.min; save(); render(); },
};

function uploadDoc(key) {
  const w = S.wizard;
  if (w.uploaded[key] === 'done') return;
  w.uploaded[key] = 'loading'; render();
  setTimeout(() => { w.uploaded[key] = 'done'; if (key === 'letter') w.ocr = true; save(); if (location.hash.startsWith('#/dentist/new')) render(); }, 700);
}

document.addEventListener('click', (e) => {
  const t = e.target.closest('[data-act],[data-go]');
  if (!t || t.closest('[contenteditable="true"]') && !t.dataset.act) return;
  if (t.dataset.act && ACTIONS[t.dataset.act]) { e.preventDefault(); ACTIONS[t.dataset.act](t, e); }
  if (t.dataset.go) { e.preventDefault(); closeModal(); UI.sideOpen = false; if (location.hash === t.dataset.go) render(); else location.hash = t.dataset.go; }
});
document.addEventListener('input', (e) => {
  const m = e.target.dataset && e.target.dataset.model;
  if (!m) return;
  if (m.startsWith('wizard.')) { S.wizard[m.slice(7)] = e.target.value; save(); }
  else { UI[m] = e.target.value; render(); }
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && e.target.dataset && e.target.dataset.enter) { e.preventDefault(); ACTIONS[e.target.dataset.enter]?.(e.target, e); }
  if (e.key === 'Escape') closeModal();
});
window.addEventListener('hashchange', () => { closeModal(); UI.editMode = false; UI.draftVer = null; render(); });
render();
