/* Dentist mobile app screens. Each returns { html, guide, nav, dark } */
const UI = { casesTab: 'all', caseTab: 'overview', draftVer: null, tplCat: 'الكل', libQ: '', procStep: 0, procTimer: null, caseQ: '', queueTab: 'review', docFilter: 'الكل', kbTest: null, auditRole: 'الكل', sideOpen: false, editMode: false };

function appHeader(title, o = {}) {
  const back = o.back ? `<button class="icon-btn flat" data-go="${o.back}" aria-label="رجوع">${icon('chevR')}</button>` : '';
  return `<div class="app-header ${o.white ? 'white' : ''}">${back}<div class="title ${o.back ? '' : 'start'}">${title}${o.sub ? `<div class="tiny muted" style="font-weight:500">${o.sub}</div>` : ''}</div>${o.actions || (o.back ? '<span style="width:38px"></span>' : '')}</div>`;
}
function tabbar(active) {
  const unread = S.notifications.filter((n) => !n.read).length;
  const it = (k, ic, lbl, go) => `<button class="${active === k ? 'on' : ''}" data-go="${go}">${icon(ic, 'lg')}<span>${lbl}</span></button>`;
  return `<nav class="tabbar">${it('home', 'home', 'الرئيسية', '#/dentist/home')}${it('cases', 'briefcase', 'القضايا', '#/dentist/cases')}
    <button data-act="new-case" data-tour="fab" aria-label="قضية جديدة"><span class="fab">${icon('plus')}</span></button>
    ${it('consult', 'chat', 'الاستشارات', '#/dentist/consult')}${it('profile', 'user', 'حسابي', '#/dentist/profile')}</nav>`;
}
const G = (t, p, li = []) => ({ t, p, li });

const DENTIST = {
  onboarding() {
    return { dark: true, html: `<div class="app" style="background:linear-gradient(170deg,var(--navy-900),var(--navy-800) 70%,var(--teal-700));color:#fff">
      <div class="app-body no-nav" style="display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;gap:18px;padding:24px 26px">
        ${logo('lg')}
        <div><div style="font-size:32px;font-weight:700;letter-spacing:.3px">DentLegal</div><div style="color:#b7cfd5">الحماية القانونية لأطباء الأسنان</div></div>
        <div class="stack" style="text-align:right;width:100%;margin-top:14px">
          ${[['sparkles', 'تحليل ذكي لقضيتك', 'مبني على الأنظمة واللوائح الصحية السعودية مع ذكر المصدر'], ['scale', 'اعتماد من محامٍ مرخّص', 'لا يُعتمد أي رد قبل مراجعته قانونيًا'], ['lock', 'خصوصية وتشفير كامل', 'بيانات المرضى مرمّزة ومحمية وفق نظام حماية البيانات']].map(([i, t, s]) => `
          <div class="row top" style="gap:12px"><span class="logo" style="background:rgba(255,255,255,.1)">${icon(i)}</span><div><div class="bold">${t}</div><div class="small" style="color:#a9c3ca">${s}</div></div></div>`).join('')}
        </div>
      </div>
      <div class="stack" style="padding:16px 22px 30px">
        <button class="btn btn-primary btn-lg btn-block" data-go="#/dentist/login">تسجيل الدخول</button>
        <button class="btn btn-lg btn-block" style="background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.2)" data-go="#/dentist/register">إنشاء حساب جديد</button>
      </div></div>`,
      guide: G('شاشة الترحيب', 'أول ما يراه طبيب الأسنان عند فتح التطبيق: هوية المنتج وقيمته في ثلاث رسائل واضحة.', ['تسجيل دخول أو إنشاء حساب جديد', 'تصميم عربي كامل (RTL) بهوية DentLegal']) };
  },

  login() {
    return { html: `<div class="app">${appHeader('', { back: '#/dentist/onboarding' })}
      <div class="app-body no-nav"><div class="stack lg" style="padding-top:8px">
        <div><h1 class="h1">أهلًا بعودتك</h1><p class="muted" style="margin:4px 0 0">سجّل الدخول برقم جوالك أو عبر النفاذ الوطني الموحد.</p></div>
        <div class="field"><label>رقم الجوال</label><div class="row" style="direction:ltr"><span class="input" style="width:auto;flex:none;background:var(--bg)">+966</span><input class="input" value="55 123 4417" inputmode="tel" style="direction:ltr;text-align:left"></div></div>
        <button class="btn btn-primary btn-lg btn-block" data-go="#/dentist/otp">إرسال رمز التحقق</button>
        <div class="row" style="gap:12px"><div class="divider grow"></div><span class="muted small">أو</span><div class="divider grow"></div></div>
        <button class="btn btn-secondary btn-lg btn-block" data-act="nafath">${icon('scanFace')} الدخول عبر النفاذ الوطني الموحد</button>
        <button class="btn btn-ghost btn-block" data-act="biometric">${icon('key')} الدخول بالبصمة / Face ID</button>
        <p class="tiny muted center" style="margin-top:20px">بتسجيل الدخول فإنك توافق على <a>الشروط والأحكام</a> و<a>سياسة الخصوصية</a>.</p>
      </div></div></div>`,
      guide: G('تسجيل الدخول', 'دخول آمن متعدد الخيارات يناسب الممارس الصحي في السعودية.', ['رمز تحقق لمرة واحدة (OTP) عبر الجوال', 'خيار النفاذ الوطني الموحد (تكامل اختياري في المرحلة الثانية)', 'الدخول الحيوي بالبصمة أو بصمة الوجه بعد أول دخول']) };
  },

  otp() {
    return { html: `<div class="app">${appHeader('', { back: '#/dentist/login' })}
      <div class="app-body no-nav"><div class="stack lg" style="padding-top:8px">
        <div><h1 class="h1">رمز التحقق</h1><p class="muted" style="margin:4px 0 0">أرسلنا رمزًا من 4 أرقام إلى <span class="ltr">+966 55 *** 4417</span></p></div>
        <div class="otp">${[4, 7, 1, 9].map((n) => `<input value="${n}" maxlength="1" inputmode="numeric">`).join('')}</div>
        <p class="center small muted">إعادة الإرسال خلال <span class="ltr">00:42</span></p>
        <button class="btn btn-primary btn-lg btn-block" data-act="login-ok">تحقق ودخول</button>
      </div></div></div>`,
      guide: G('التحقق برمز OTP', 'رمز صالح لمدة محدودة مع حماية من المحاولات المتكررة.', ['تسجيل كل عملية دخول في سجل التدقيق', 'إدارة الجلسات النشطة من صفحة الحساب']) };
  },

  register() {
    const upl = (t, s, done) => `<div class="upl ${done ? 'done' : ''} req"><span class="ic">${icon(done ? 'fileCheck' : 'upload')}</span><div class="grow"><div class="t">${t}</div><div class="s">${s}</div></div>${done ? `<span class="badge b-green">${icon('check')}تم</span>` : '<button class="btn btn-secondary btn-sm">رفع</button>'}</div>`;
    return { html: `<div class="app">${appHeader('إنشاء حساب', { back: '#/dentist/onboarding', white: true })}
      <div class="app-body no-nav"><div class="stack lg" style="padding-top:14px">
        <div class="stack"><div class="h3">البيانات الشخصية</div>
          <div class="field"><label>الاسم الكامل</label><input class="input" value="${ME.name}"></div>
          <div class="field"><label>البريد الإلكتروني</label><input class="input" value="${ME.email}" style="direction:ltr;text-align:left"></div></div>
        <div class="stack"><div class="h3">البيانات المهنية</div>
          <div class="field"><label>رقم التسجيل المهني (الهيئة السعودية للتخصصات الصحية)</label><input class="input" value="${ME.license}" style="direction:ltr;text-align:left"></div>
          <div class="grid2"><div class="field"><label>التخصص</label><select class="select"><option>جراحة وزراعة الأسنان</option><option>طب أسنان عام</option><option>تقويم الأسنان</option><option>علاج الجذور</option><option>طب أسنان الأطفال</option></select></div>
          <div class="field"><label>المدينة</label><select class="select"><option>جدة</option><option>الرياض</option><option>الدمام</option><option>مكة المكرمة</option><option>المدينة المنورة</option></select></div></div>
          <div class="field"><label>المنشأة الصحية</label><input class="input" value="${ME.clinic}"></div></div>
        <div class="stack"><div class="h3">المستندات</div>
          ${upl('بطاقة التصنيف المهني', 'PDF أو صورة — سارية', true)}${upl('وثيقة تأمين الأخطاء الطبية', 'إلزامية وفق المادة 41', false)}</div>
        <label class="check"><input type="checkbox" checked><span>أوافق على الشروط والأحكام وسياسة الخصوصية، وعلى معالجة بياناتي وفق نظام حماية البيانات الشخصية.</span></label>
        <button class="btn btn-primary btn-lg btn-block" data-go="#/dentist/register-done">إرسال طلب التسجيل</button>
      </div></div></div>`,
      guide: G('إنشاء حساب الطبيب', 'تسجيل مخصص للممارسين الصحيين فقط مع التحقق من الترخيص المهني قبل التفعيل.', ['التحقق من رقم التسجيل المهني (يدويًا من الإدارة، أو تكامل مستقبلي)', 'رفع وثيقة التأمين الإلزامي ضد الأخطاء الطبية', 'موافقة صريحة على معالجة البيانات (PDPL)']) };
  },

  'register-done'() {
    return { html: `<div class="app">${appHeader('', { back: '#/dentist/register' })}
      <div class="app-body no-nav"><div class="stack lg center" style="padding-top:20px;align-items:center">
        <span class="logo lg" style="background:var(--teal-50)"><svg viewBox="0 0 24 24" style="stroke:var(--teal-600)">${ICONS.badge}</svg></span>
        <div><h1 class="h1">تم استلام طلبك</h1><p class="muted" style="margin:6px 0 0">نراجع بياناتك المهنية للتأكد من سلامة الحسابات على المنصة.</p></div>
        <div class="card" style="width:100%;text-align:right"><div class="timeline">
          <div class="tl-item"><div class="t">استلام طلب التسجيل</div><div class="s">${fmtDate(DEMO_TODAY)}</div></div>
          <div class="tl-item ai"><div class="t">التحقق من الترخيص المهني</div><div class="s">جارٍ — عادة خلال يوم عمل</div></div>
          <div class="tl-item gray"><div class="t">تفعيل الحساب والاشتراك</div><div class="s">سنرسل لك إشعارًا فور التفعيل</div></div>
        </div></div>
        <button class="btn btn-primary btn-lg btn-block" data-go="#/dentist/subscription">اختيار الاشتراك</button>
        <button class="btn btn-ghost btn-block" data-go="#/dentist/home">متابعة العرض (تخطي التحقق)</button>
      </div></div></div>`,
      guide: G('حالة التحقق', 'شفافية كاملة للطبيب حول حالة حسابه.', ['تعتمد الإدارة الحساب من لوحة التحكم', 'إشعارات فورية عند كل تغيير في الحالة']) };
  },

  subscription() {
    return { html: `<div class="app">${appHeader('الاشتراك', { back: '#/dentist/profile', white: true })}
      <div class="app-body no-nav"><div class="stack lg" style="padding-top:14px">
        <div class="card dark pad-lg"><div class="row between"><span class="badge b-gold">الأكثر طلبًا</span>${icon('shield', 'lg')}</div>
          <div class="h2" style="margin-top:10px">الاشتراك الاحترافي</div>
          <div style="margin:6px 0 2px"><b style="font-size:30px">299</b> <span class="muted">ر.س / شهريًا</span></div>
          <div class="tiny muted">سعر توضيحي مأخوذ من تصور العميل — يُعتمد لاحقًا</div>
          <div class="stack sm" style="margin-top:14px">${['قضايا ومتابعة غير محدودة', 'تحليل ذكي للمستندات والقضايا', 'مراجعة واعتماد الردود من محامٍ مرخّص', 'مستشار قانوني ذكي على مدار الساعة', 'مكتبة النماذج والإقرارات المحدّثة', 'تنبيهات المواعيد والمهل النظامية'].map((f) => `<div class="row">${icon('checkCircle', 'sm')}<span>${f}</span></div>`).join('')}</div></div>
        <div class="stack sm"><div class="h3">طريقة الدفع</div>
          ${[['card', 'مدى', true], ['phone', 'Apple Pay', false], ['card', 'بطاقة فيزا / ماستركارد', false]].map(([i, t, on]) => `<div class="radio-card ${on ? 'on' : ''}" data-act="radio"><span class="ic">${icon(i)}</span><b class="grow">${t}</b>${on ? icon('checkCircle') : ''}</div>`).join('')}</div>
        <button class="btn btn-primary btn-lg btn-block" data-act="pay">الاشتراك والدفع</button>
        <p class="tiny muted center">الدفع عبر بوابة دفع سعودية معتمدة (مثل Moyasar أو HyperPay أو Tap) — في المرحلة الثانية.</p>
      </div></div></div>`,
      guide: G('الاشتراك والدفع', 'نموذج اشتراك شهري (SaaS) مع وسائل دفع محلية.', ['مدى وApple Pay والبطاقات الائتمانية', 'فواتير إلكترونية متوافقة مع متطلبات الفوترة', 'تجديد تلقائي مع تذكير مسبق']) };
  },

  home() {
    const mine = myCases();
    const active = mine.find((c) => c.status !== 'closed');
    const upcoming = mine.filter((c) => c.hearing && c.status !== 'closed' && daysUntil(c.hearing.date) >= 0).sort((a, b) => a.hearing.date.localeCompare(b.hearing.date))[0];
    const unread = S.notifications.filter((n) => !n.read);
    const insDays = daysUntil(ME.insurance.to);
    const svc = [['plus', 'قضية جديدة', 'new-case', ''], ['chat', 'استشارة قانونية', '', '#/dentist/consult'], ['scanText', 'تحليل مستند', 'analyze-doc', ''], ['clipboard', 'النماذج والإقرارات', '', '#/dentist/templates'], ['book', 'مكتبة الأنظمة', '', '#/dentist/library'], ['briefcase', 'متابعة القضايا', '', '#/dentist/cases']];
    return { nav: 'home', html: `<div class="app"><div class="app-body" style="padding-top:8px">
      <div class="hero-greet"><div class="avatar">${ME.initials}</div><div class="grow"><div class="hi">مرحبًا بك،</div><div class="nm">${ME.short} الغامدي</div><div class="tiny muted">${ME.specialty}</div></div>
        <button class="icon-btn" data-go="#/dentist/notifications" aria-label="التنبيهات">${icon('bell')}${unread.length ? `<span class="dot-badge">${unread.length}</span>` : ''}</button></div>
      ${upcoming ? `<div class="countdown click" data-go="#/dentist/case/${upcoming.id}" style="cursor:pointer">
        <div class="box"><div class="n">${daysUntil(upcoming.hearing.date)}</div><div class="u">${daysUntil(upcoming.hearing.date) <= 10 ? 'أيام' : 'يومًا'}</div></div>
        <div class="grow"><div class="tiny" style="color:#8fe3d3;font-weight:600">${icon('clock', 'sm')} أقرب موعد نظامي</div><div class="bold">${upcoming.hearing.label}</div><div class="tiny" style="color:#a9c3ca">${fmtDate(upcoming.hearing.date)} · ${upcoming.hearing.time} · <span class="ltr">${upcoming.id}</span></div></div>${icon('chevL')}</div>` : ''}
      ${active ? `<div class="sec-title"><h3>قضيتي النشطة</h3><a data-go="#/dentist/cases">عرض الكل</a></div>
      <div class="card click" data-go="#/dentist/case/${active.id}"><div class="row between"><span class="tiny muted ltr">${active.id}</span>${statusBadge(active.status)}</div>
        <div class="bold" style="margin:4px 0 12px">${esc(active.title)}</div>${stepper(active.stage)}</div>` : ''}
      <div class="sec-title"><h3>الخدمات السريعة</h3></div>
      <div class="svc-grid">${svc.map(([i, t, act, go]) => `<div class="svc" ${act ? `data-act="${act}"` : `data-go="${go}"`}><span class="ic">${icon(i, 'lg')}</span>${t}</div>`).join('')}</div>
      <div class="sec-title"><h3>آخر التنبيهات</h3><a data-go="#/dentist/notifications">عرض الكل</a></div>
      <div class="card" style="padding:4px 12px"><div class="list">${S.notifications.slice(0, 3).map(notifItem).join('')}</div></div>
      <div class="card" style="margin-top:12px"><div class="row"><span class="list-item" style="padding:0;border:0"><span class="ic gold">${icon('shield')}</span></span><div class="grow"><div class="bold small">وثيقة تأمين الأخطاء الطبية سارية</div><div class="tiny muted">حتى ${fmtDate(ME.insurance.to)} · متبقٍ ${insDays} يومًا</div></div></div>
        <div class="progress" style="margin-top:10px"><i style="width:${Math.round((insDays / 365) * 100)}%"></i></div></div>
      <div class="alert teal" style="margin-top:12px"><span class="ic">${icon('info')}</span><div><b>هل تعلم؟</b> يجوز لك الإفصاح عن معلومات المريض بالقدر اللازم للدفاع عن نفسك في شكوى مقدّمة ضدك ${cite('م21')}</div></div>
    </div>${tabbar('home')}</div>`,
      guide: G('الرئيسية', 'لوحة الطبيب اليومية: أقرب موعد نظامي، حالة القضية النشطة، والخدمات بنقرة واحدة.', ['عدّ تنازلي للمواعيد والمهل النظامية', 'مسار القضية من الشكوى حتى صدور القرار', 'تنبيه بصلاحية وثيقة التأمين الإلزامي', 'معلومة قانونية مع مصدرها النظامي']) };
  },

  cases() {
    const all = myCases();
    const q = UI.caseQ.trim();
    const groups = {
      all: all, active: all.filter((c) => !['closed'].includes(c.status)),
      action: all.filter((c) => ['ai_ready', 'needs_info', 'approved'].includes(c.status)), closed: all.filter((c) => c.status === 'closed'),
    };
    const list = groups[UI.casesTab].filter((c) => !q || c.title.includes(q) || c.id.includes(q));
    const tab = (k, l) => `<button class="${UI.casesTab === k ? 'on' : ''}" data-act="tab" data-k="casesTab" data-v="${k}">${l}<span class="count">${groups[k].length}</span></button>`;
    return { nav: 'cases', html: `<div class="app">${appHeader('قضاياي', { actions: `<button class="icon-btn" data-act="new-case">${icon('plus')}</button>` })}
      <div class="app-body"><div class="search">${icon('search')}<input placeholder="ابحث برقم القضية أو العنوان" value="${esc(UI.caseQ)}" data-model="caseQ"></div>
      <div class="tabs" style="margin:10px 0 12px">${tab('all', 'الكل')}${tab('active', 'نشطة')}${tab('action', 'تحتاج إجراء')}${tab('closed', 'مغلقة')}</div>
      <div class="stack">${list.length ? list.map(caseCard).join('') : `<div class="card center muted" style="padding:30px">${icon('folder', 'xl')}<div style="margin-top:8px">لا توجد قضايا في هذا التصنيف</div></div>`}</div>
      </div>${tabbar('cases')}</div>`,
      guide: G('متابعة القضايا', 'كل قضايا الطبيب في مكان واحد مع حالتها النظامية وحالتها داخل المنصة.', ['تصنيف: نشطة / تحتاج إجراء / مغلقة', 'بحث برقم القضية أو العنوان', 'المرحلة الحالية والموعد القادم لكل قضية']) };
  },

  case(id) {
    const c = caseById(id);
    if (!c) return DENTIST.home();
    const law = c.lawyer ? lawyerById(c.lawyer) : null;
    const isDemo = id === DEMO_CASE_ID;
    const tab = (k, l) => `<button class="${UI.caseTab === k ? 'on' : ''}" data-act="tab" data-k="caseTab" data-v="${k}">${l}</button>`;
    let body = '';
    if (UI.caseTab === 'docs') {
      const docs = isDemo ? CASE_DOCS : CASE_DOCS.slice(0, c.docs || 3);
      body = `<div class="stack sm">${docs.map((d) => `<div class="doc-tile"><span class="ic ${d.type === 'img' ? 'img' : ''}">${icon(d.type === 'img' ? 'image' : 'file')}</span><div class="grow"><div class="t">${d.name}</div><div class="s"><bdi>${d.file}</bdi> · ${d.size} · ${icon('lock', 'sm')} مشفّر</div></div>${icon('eye')}</div>`).join('')}
        <button class="btn btn-secondary btn-block" data-act="toast" data-msg="سيتم فتح منتقي الملفات أو الكاميرا (في التطبيق الفعلي)">${icon('upload')} رفع مستند إضافي</button></div>`;
    } else if (UI.caseTab === 'log') {
      body = `<div class="card">${timeline(c.events)}</div>`;
    } else {
      body = `<div class="stack">
        ${c.summary ? `<div class="card"><div class="h3" style="margin-bottom:6px">ملخص الشكوى</div><p style="margin:0" class="small">${esc(c.summary)}</p></div>` : ''}
        ${c.hearing ? `<div class="card"><div class="row"><span class="list-item" style="padding:0;border:0"><span class="ic amber">${icon('calendar')}</span></span><div class="grow"><div class="bold small">${c.hearing.label}</div><div class="tiny muted">${fmtDate(c.hearing.date)} (${fmtHijri(c.hearing.date)}) · ${c.hearing.time}</div><div class="tiny muted">${c.hearing.place}</div></div><span class="badge b-amber">${relDays(c.hearing.date)}</span></div></div>` : ''}
        <div class="card" style="padding:4px 14px"><div class="list">
          ${[['landmark', 'الجهة', c.authority], ['file', 'رقم الشكوى', c.complaintNo], ['stethoscope', 'الإجراء الطبي', c.procedure], ['calendar', 'تاريخ الإجراء', fmtDate(c.procedureDate)], ['lock', 'رمز المريض', `${c.patientCode} (مرمّز لحماية الخصوصية)`], ['scale', 'المحامي المسؤول', law ? `${law.name} — ${law.firm}` : 'يُسند عند الإرسال للمراجعة']].map(([i, l, v]) => `<div class="list-item" style="cursor:default"><span class="ic gray">${icon(i)}</span><div class="grow"><div class="s">${l}</div><div class="t">${esc(v)}</div></div></div>`).join('')}
        </div></div></div>`;
    }
    return { nav: 'cases', html: `<div class="app">${appHeader(`<span class="ltr">${c.id}</span>`, { back: '#/dentist/cases', actions: `<button class="icon-btn flat" data-act="toast" data-msg="خيارات: مشاركة مع المحامي، أرشفة، تصدير">${icon('more')}</button>` })}
      <div class="app-body">
        <div class="card"><div class="row between wrap"><span class="badge b-gray">${esc(c.kind)}</span>${c.risk ? riskBadge(c.risk) : ''}</div>
          <div class="h2" style="margin:8px 0 2px">${esc(c.title)}</div><div class="small muted">${esc(c.authority)}</div>
          <div class="divider"></div><div class="tiny muted bold" style="margin-bottom:10px">المسار النظامي للقضية</div>${stepper(c.stage)}</div>
        <div style="margin-top:12px">${caseActionCard(c)}</div>
        <div class="tabs" style="margin:14px 0 12px">${tab('overview', 'نظرة عامة')}${tab('docs', 'المستندات')}${tab('log', 'السجل الزمني')}</div>
        ${body}
      </div>${tabbar('cases')}</div>`,
      guide: G('تفاصيل القضية', 'كل ما يخص القضية: المسار النظامي، الإجراء المطلوب الآن، المستندات المشفّرة والسجل الزمني.', ['المراحل الست من تقديم الشكوى حتى صدور القرار', 'بطاقة إجراء ذكية تتغير حسب حالة القضية', 'المريض يظهر برمز فقط — لا أسماء', 'التاريخ الهجري والميلادي معًا']) };
  },

  analysis(id) {
    const c = caseById(id) || ensureAnalyzed();
    const A = id === DEMO_CASE_ID ? ANALYSIS : genericAnalysis(c);
    const item = (x, ic, cls) => `<div class="list-item" style="cursor:default;align-items:flex-start"><span class="ic ${cls}">${icon(ic)}</span><div class="grow"><div class="t" style="font-weight:500">${x.t}</div><div class="row wrap" style="gap:4px;margin-top:6px">${(x.cites || []).map(cite).join('')}${(x.docs || []).map(docRef).join('')}${x.level ? `<span class="badge ${x.level === 'مرتفع' ? 'b-red' : 'b-amber'}">${x.level}</span>` : ''}${x.due ? `<span class="badge b-gray">${icon('clock')}${x.due}</span>` : ''}</div></div></div>`;
    return { html: `<div class="app">${appHeader('التحليل الذكي', { back: `#/dentist/case/${c.id}`, sub: `<span class="ltr">${c.id}</span>` })}
      <div class="app-body no-nav">
        <div class="card"><div class="row" style="gap:14px">${gauge(A.score)}<div class="stack sm grow">${riskBadge(A.risk)}<div class="small muted">استنادًا إلى ${c.docs || 6} مستندات و${A.sources.length} مواد نظامية مسترجعة</div></div></div>
          <p class="small" style="margin:12px 0 0">${A.summary}</p></div>
        <div class="alert violet" style="margin-top:12px"><span class="ic">${icon('sparkles')}</span><div>تحليل آلي إرشادي. لا يُستخدم أي رد قبل مراجعته واعتماده من محامٍ مرخّص.</div></div>
        <div class="sec-title"><h3>${icon('checkCircle', 'sm')} نقاط القوة</h3><span class="badge b-green">${A.strengths.length}</span></div>
        <div class="card" style="padding:2px 12px"><div class="list">${A.strengths.map((x) => item(x, 'check', '')).join('')}</div></div>
        <div class="sec-title"><h3>${icon('alert', 'sm')} المخاطر ونقاط الضعف</h3><span class="badge b-amber">${A.risks.length}</span></div>
        <div class="card" style="padding:2px 12px"><div class="list">${A.risks.map((x) => item(x, 'alert', 'amber')).join('')}</div></div>
        <div class="sec-title"><h3>${icon('listChecks', 'sm')} الإجراءات الموصى بها</h3></div>
        <div class="card" style="padding:2px 12px"><div class="list">${A.actions.map((x) => item(x, 'arrowL', 'blue')).join('')}</div></div>
        <div class="sec-title"><h3>${icon('book', 'sm')} المصادر النظامية المسترجعة</h3></div>
        <div class="card"><div class="stack sm">${A.sources.map(([k, s]) => `<div class="row" data-act="source" data-key="${k}" style="cursor:pointer"><span style="width:84px">${cite(k)}</span><div class="grow"><div class="tiny">${SOURCES[k].title}</div><div class="progress" style="height:5px;margin-top:3px"><i style="width:${s * 100}%"></i></div></div><span class="tiny muted num">${Math.round(s * 100)}%</span></div>`).join('')}</div></div>
      </div>
      <div class="app-foot"><button class="btn btn-primary btn-block btn-lg" data-go="#/dentist/case/${c.id}/draft" data-tour="view-draft">${icon('file')} عرض مسودة الرد</button></div></div>`,
      guide: G('نتيجة التحليل الذكي', 'محرك RAG يقرأ مستندات القضية ويسترجع المواد النظامية ذات الصلة ليقدّم تقييمًا قابلًا للتحقق.', ['مؤشر قوة الموقف ومستوى المخاطر', 'كل نقطة مربوطة بمادة نظامية أو مستند من القضية', 'إجراءات عملية مع مواعيدها', 'درجة الصلة لكل مصدر مسترجع']) };
  },

  draft(id) {
    const c = caseById(id) || ensureAnalyzed();
    const isDemo = id === DEMO_CASE_ID;
    const hasV2 = (c.versions || 1) >= 2;
    const ver = UI.draftVer && (UI.draftVer === 1 || hasV2) ? UI.draftVer : hasV2 ? 2 : 1;
    const text = isDemo ? (ver === 2 ? DRAFT_V2 : DRAFT_V1) : genericDraft(c, ver);
    const approved = ['approved', 'submitted', 'closed'].includes(c.status) && ver === 2;
    const law = lawyerById(c.lawyer || 'L-07');
    let banner = '';
    if (approved) banner = `<div class="seal"><span class="ring">${icon('stamp', 'lg')}</span><div><div class="bold">معتمد قانونيًا</div><div class="tiny">${law.name} · ${law.license}</div><div class="tiny">رمز التحقق: <span class="ltr">DL-VRF-7Q4K-2291</span></div></div></div>`;
    else if (c.status === 'lawyer_review') banner = `<div class="alert blue"><span class="ic">${icon('hourglass')}</span><div>المسودة لدى <b>${law.name}</b> للمراجعة القانونية — متوقع خلال ${Math.max(slaLeft(c) ?? 24, 1)} ساعة. سنُشعرك فور الاعتماد.</div></div>`;
    else banner = `<div class="alert violet"><span class="ic">${icon('sparkles')}</span><div><b>مسودة أنشأها الذكاء الاصطناعي — غير معتمدة.</b> راجعها ثم أرسلها للمحامي. لا تُقدَّم للجهة قبل الاعتماد.</div></div>`;
    let foot = '';
    if (c.status === 'ai_ready') foot = `<button class="btn btn-secondary" data-act="ai-revise">${icon('sparkles')} طلب تعديل</button><button class="btn btn-primary grow" data-act="send-review" data-tour="send-review">${icon('send')} إرسال للمراجعة القانونية</button>`;
    else if (c.status === 'lawyer_review') foot = `<button class="btn btn-secondary btn-block" disabled>${icon('hourglass')} بانتظار اعتماد المحامي</button>`;
    else if (c.status === 'approved') foot = `<button class="btn btn-secondary" data-act="download">${icon('download')} PDF</button><button class="btn btn-primary grow" data-act="mark-submitted" data-tour="mark-submitted">${icon('checkCircle')} تأكيد التقديم للجهة</button>`;
    else foot = `<button class="btn btn-secondary btn-block" data-act="download">${icon('download')} تنزيل الرد المعتمد (PDF)</button>`;
    return { html: `<div class="app">${appHeader(approved ? 'الرد المعتمد' : 'مسودة الرد', { back: `#/dentist/case/${c.id}`, sub: `<span class="ltr">${c.id}</span>` })}
      <div class="app-body no-nav"><div class="stack">
        ${hasV2 ? `<div class="seg" style="align-self:stretch;display:flex"><button class="grow ${ver === 1 ? 'on' : ''}" data-act="ver" data-v="1">${icon('sparkles', 'sm')} الإصدار 1 · AI</button><button class="grow ${ver === 2 ? 'on' : ''}" data-act="ver" data-v="2">${icon('scale', 'sm')} الإصدار 2 · المحامي</button></div>` : ''}
        ${banner}
        ${ver === 2 && isDemo ? `<div class="legend"><span><i style="background:#bfe8cd"></i>إضافة المحامي</span><span><i style="background:#f6c9c9"></i>حذف</span><span><i style="background:var(--teal-100)"></i>استشهاد نظامي</span></div>` : '<div class="legend"><span><i style="background:var(--teal-100)"></i>استشهاد نظامي — اضغط لعرض النص</span><span><i style="background:#d6e2fb"></i>مستند من القضية</span></div>'}
        <div class="paper">${renderDraft(text)}</div>
      </div></div>
      <div class="app-foot">${foot}</div></div>`,
      guide: G(approved ? 'الرد المعتمد' : 'مسودة الرد مع الاستشهادات', approved ? 'نسخة نهائية موقّعة من المحامي مع رمز تحقق، وسجل كامل بالتعديلات بين الإصدارات.' : 'رد رسمي بصياغة قانونية سعودية، كل ادعاء فيه مربوط بمادة نظامية أو مستند — قابل للتحقق بنقرة.', ['استشهادات تفاعلية تعرض نص المادة', 'إدارة الإصدارات: AI ثم تعديلات المحامي', 'إرسال للمراجعة مع موافقة على الاطلاع', 'تنزيل PDF بعد الاعتماد فقط']) };
  },

  processing(id) {
    const c = caseById(id) || ensureCaseCreated();
    const steps = [['lock', 'استلام المستندات وتشفيرها', '6 مستندات · AES-256'], ['scanText', 'استخراج النصوص (OCR)', 'عربي وإنجليزي · خط يد وطباعة'], ['shield', 'إخفاء البيانات التعريفية للمريض', 'استبدال الاسم والهوية برمز P-7731'], ['layers', 'تصنيف القضية وتحديد المرحلة', 'شكوى + مطالبة بحق خاص · التحقيق الأولي'], ['database', 'استرجاع المواد النظامية (RAG)', 'بحث دلالي + نصي · 7 مصادر'], ['scale', 'تحليل نقاط القوة والضعف', 'مقارنة الوقائع بالمستندات والمواد'], ['file', 'صياغة مسودة الإفادة', 'صياغة قانونية سعودية رسمية'], ['badge', 'التحقق من الاستشهادات', 'كل ادعاء مرتبط بمصدر']];
    const done = statusIdx(c.status) >= statusIdx('ai_ready');
    const cur = done ? steps.length : UI.procStep;
    if (!done && !UI.procTimer) runProcessing(c.id, steps.length);
    return { html: `<div class="app">${appHeader('التحليل الذكي', { back: `#/dentist/case/${c.id}`, sub: `<span class="ltr">${c.id}</span>` })}
      <div class="app-body no-nav"><div class="stack">
        <div class="card dark center" style="padding:20px"><div style="display:inline-grid;place-items:center;width:58px;height:58px;border-radius:50%;background:rgba(127,209,194,.15);color:#8fe3d3">${done ? icon('checkCircle', 'xl') : `<span class="spin" style="display:inline-grid">${icon('refresh', 'xl')}</span>`}</div>
          <div class="h2" style="margin-top:10px">${done ? 'اكتمل التحليل' : 'نحلّل قضيتك الآن'}</div><div class="small muted">${done ? 'مسودة الرد والتحليل جاهزان للمراجعة' : 'يستغرق عادة أقل من دقيقتين (مختصر للعرض)'}</div>
          <div class="progress" style="margin-top:14px;background:rgba(255,255,255,.12)"><i style="width:${Math.round((cur / steps.length) * 100)}%"></i></div></div>
        <div class="pipe">${steps.map(([ic, t, s], i) => { const st = i < cur ? 'ok' : i === cur ? 'run' : 'wait'; return `<div class="p ${st}"><span class="ic">${st === 'ok' ? icon('check') : st === 'run' ? `<span class="spin" style="display:inline-grid">${icon('refresh', 'sm')}</span>` : icon(ic)}</span><div class="grow"><div class="t">${t}</div><div class="s">${s}</div></div>${st === 'ok' ? '<span class="badge b-green">تم</span>' : ''}</div>`; }).join('')}</div>
      </div></div>
      <div class="app-foot"><button class="btn btn-primary btn-block btn-lg" ${done ? '' : 'disabled'} data-go="#/dentist/case/${c.id}/analysis" data-tour="view-analysis">${icon('sparkles')} عرض نتيجة التحليل</button></div></div>`,
      guide: G('محرك المعالجة الذكية', 'محاكاة لخط معالجة الذكاء الاصطناعي الفعلي في المرحلة الثانية.', ['OCR للمستندات العربية والصور', 'إخفاء هوية المريض قبل أي معالجة بالنموذج اللغوي', 'استرجاع هجين (دلالي + نصي) من الأنظمة المفهرسة', 'تحقق آلي من أن كل استشهاد موجود فعلًا في المصادر']) };
  },

  new() {
    const w = S.wizard;
    const n = w.step;
    let body = '';
    if (n === 1) {
      const kinds = [['complaint', 'file', 'شكوى من مريض', 'وصلني إشعار بشكوى عبر الجهة الصحية'], ['notice', 'landmark', 'استدعاء أو إشعار رسمي', 'طلب إفادة أو حضور جلسة تحقيق'], ['board', 'gavel', 'دعوى أمام الهيئة الصحية الشرعية', 'قضية محالة للنظر أو صدر فيها قرار'], ['preventive', 'shield', 'استشارة وقائية', 'موقف محتمل قبل وصول شكوى']];
      body = `<div class="stack"><div class="h3">ما نوع القضية؟</div>
        ${kinds.map(([k, i, t, s]) => `<div class="radio-card ${w.kind === k ? 'on' : ''}" data-act="wkind" data-v="${k}"><span class="ic">${icon(i)}</span><div class="grow"><div class="bold small">${t}</div><div class="tiny muted">${s}</div></div>${w.kind === k ? icon('checkCircle') : ''}</div>`).join('')}
        <div class="field"><label>الجهة</label><select class="select"><option>${w.authority}</option><option>الهيئة الصحية الشرعية</option><option>لجنة النظر في مخالفات نظام مزاولة المهن الصحية</option></select></div>
        <div class="grid2"><div class="field"><label>رقم الشكوى</label><input class="input" value="${esc(w.complaintNo)}" data-model="wizard.complaintNo"></div><div class="field"><label>تاريخ الاستلام</label><input class="input" type="date" value="${w.receivedDate}"></div></div></div>`;
    } else if (n === 2) {
      body = `<div class="stack"><div class="h3">تفاصيل الواقعة</div>
        <div class="field"><label>عنوان مختصر للقضية</label><input class="input" value="${esc(w.title)}" data-model="wizard.title"></div>
        <div class="field"><label>الإجراء الطبي محل الشكوى</label><input class="input" value="${esc(w.procedure)}" data-model="wizard.procedure"></div>
        <div class="grid2"><div class="field"><label>تاريخ الإجراء</label><input class="input" type="date" value="${w.procedureDate}"></div><div class="field"><label>رمز المريض</label><input class="input" value="${w.patientCode}" style="direction:ltr;text-align:left"></div></div>
        <div class="alert blue"><span class="ic">${icon('lock')}</span><div>لا تُدخل اسم المريض أو هويته — يكفي رمز داخلي من ملفك. نحمي الخصوصية من البداية.</div></div>
        <div class="field"><label>ملخص ما ورد في الشكوى</label><textarea class="textarea" data-model="wizard.summary">${esc(w.summary)}</textarea></div>
        <div class="field"><label>نوع المطالبة</label><div class="row wrap">${['تعويض مالي (حق خاص)', 'مساءلة تأديبية', 'غير محدد'].map((x, i) => `<span class="chip ${i === 0 ? 'on' : ''}">${x}</span>`).join('')}</div></div></div>`;
    } else if (n === 3) {
      const up = w.uploaded;
      const cnt = Object.values(up).filter((v) => v === 'done').length;
      body = `<div class="stack"><div class="row between"><div class="h3">المستندات</div><span class="badge b-teal">${cnt}/${CASE_DOCS.length}</span></div>
        <div class="row"><button class="btn btn-secondary btn-sm grow" data-act="upload-all" data-tour="upload-all">${icon('upload')} رفع الكل (عرض)</button><button class="btn btn-secondary btn-sm grow" data-act="toast" data-msg="سيُفتح الماسح الضوئي بالكاميرا مع قص وتحسين تلقائي (في التطبيق الفعلي)">${icon('camera')} مسح بالكاميرا</button></div>
        ${CASE_DOCS.map((d) => { const st = up[d.key]; return `<div class="upl ${st === 'done' ? 'done' : ''} ${d.req ? 'req' : ''}" data-act="upload" data-key="${d.key}" style="cursor:pointer"><span class="ic">${st === 'loading' ? `<span class="spin" style="display:inline-grid">${icon('refresh')}</span>` : icon(st === 'done' ? 'fileCheck' : d.type === 'img' ? 'image' : 'upload')}</span><div class="grow"><div class="t">${d.name}</div><div class="s">${st === 'done' ? `<bdi>${d.file}</bdi> · ${d.size}` : d.hint}</div></div>${st === 'done' ? `<span class="badge b-green">${icon('lock')}مشفّر</span>` : '<span class="badge b-gray">رفع</span>'}</div>`; }).join('')}
        ${w.ocr ? `<div class="card tint"><div class="row" style="margin-bottom:8px">${icon('scanText')}<b class="small">استخرجنا هذه البيانات من خطاب الشكوى تلقائيًا (OCR)</b></div>
          ${OCR_FIELDS.map(([k, v]) => `<div class="row between small" style="padding:4px 0;border-bottom:1px dashed var(--teal-100)"><span class="muted">${k}</span><b>${v}</b></div>`).join('')}
          <div class="tiny muted" style="margin-top:8px">${icon('calendar', 'sm')} أُضيف موعد الإفادة إلى التقويم والتنبيهات تلقائيًا.</div></div>` : ''}
        <p class="tiny muted">المستندات المعلّمة بـ * مطلوبة. ترفع بالقدر اللازم للدفاع وفق ${cite('م21')}</p></div>`;
    } else {
      const cnt = Object.values(w.uploaded).filter((v) => v === 'done').length;
      body = `<div class="stack"><div class="h3">المراجعة والإرسال</div>
        <div class="card" style="padding:4px 14px"><div class="list">${[['file', 'النوع', 'شكوى من مريض — مطالبة بالتعويض'], ['landmark', 'الجهة', w.authority], ['briefcase', 'العنوان', w.title], ['stethoscope', 'الإجراء', w.procedure], ['clip', 'المستندات', `${cnt} مستندات مرفوعة ومشفّرة`], ['calendar', 'موعد الإفادة', `${fmtDate(w.hearing.date)} · ${w.hearing.time}`]].map(([i, l, v]) => `<div class="list-item" style="cursor:default"><span class="ic gray">${icon(i)}</span><div class="grow"><div class="s">${l}</div><div class="t">${esc(v)}</div></div></div>`).join('')}</div></div>
        <label class="check"><input type="checkbox" ${w.agree1 ? 'checked' : ''}><span>أقر بصحة المعلومات المدخلة وأن المستندات صادرة عن منشأتي.</span></label>
        <label class="check"><input type="checkbox" ${w.agree2 ? 'checked' : ''}><span>أوافق على المعالجة الآلية للمستندات، واطلاع المحامي المُسند عليها عند الإرسال للمراجعة.</span></label>
        <div class="alert violet"><span class="ic">${icon('sparkles')}</span><div>بعد الإنشاء يبدأ التحليل الذكي فورًا، وتصلك مسودة الرد مع المصادر خلال دقائق.</div></div></div>`;
    }
    const last = n === 4;
    const hdr = `<div class="app-header"><button class="icon-btn flat" ${n > 1 ? 'data-act="wiz-prev"' : 'data-go="#/dentist/home"'} aria-label="رجوع">${icon('chevR')}</button><div class="title">قضية جديدة<div class="tiny muted" style="font-weight:500">الخطوة ${n} من 4</div></div><span style="width:38px"></span></div>`;
    return { html: `<div class="app">${hdr}
      <div class="app-body no-nav"><div class="wiz-steps">${[1, 2, 3, 4].map((i) => `<i class="${i <= n ? 'on' : ''}"></i>`).join('')}</div>${body}</div>
      <div class="app-foot">${n > 1 ? `<button class="btn btn-secondary" data-act="wiz-prev">${icon('chevR')} السابق</button>` : ''}
        <button class="btn btn-primary grow" data-act="${last ? 'create-case' : 'wiz-next'}" data-tour="${last ? 'create-case' : 'wiz-next'}">${last ? `${icon('sparkles')} إنشاء القضية وبدء التحليل` : `التالي ${icon('chevL')}`}</button></div></div>`,
      guide: G(['', 'نوع القضية والجهة', 'تفاصيل الواقعة', 'رفع المستندات', 'المراجعة والإرسال'][n], 'معالج من 4 خطوات قصيرة لتسجيل القضية في أقل من 3 دقائق — البيانات معبأة مسبقًا لأغراض العرض.', n === 3 ? ['رفع PDF والصور أو المسح بالكاميرا', 'تشفير فوري لكل مستند', 'استخراج تلقائي للبيانات (OCR) وتحديث المواعيد'] : ['تصنيف القضية حسب الجهة والمرحلة', 'رمز للمريض بدل الاسم لحماية الخصوصية', 'موافقات صريحة قبل المعالجة الآلية']) };
  },

  consult() {
    const msgs = S.chat;
    const bubble = (m) => m.who === 'me' ? `<div class="msg me">${esc(m.text)}</div>` : m.typing ? `<div class="msg bot"><span class="typing"><i></i><i></i><i></i></span></div>`
      : `<div class="msg bot">${m.text}${m.cites?.length ? `<div class="src">${icon('book', 'sm')} المصادر: ${m.cites.map(cite).join('')}</div>` : ''}<div class="row" style="margin-top:8px;gap:6px"><button class="btn btn-ghost btn-sm" data-act="escalate" data-q="${esc(m.q || '')}">${icon('scale', 'sm')} تحويل لمحامٍ</button><button class="btn btn-ghost btn-sm" data-act="toast" data-msg="شكرًا! تقييمك يحسّن جودة الإجابات">${icon('check', 'sm')} مفيدة</button></div></div>`;
    return { html: `<div class="app">${appHeader('المستشار القانوني الذكي', { back: '#/dentist/home', white: true, sub: '<span class="badge b-teal" style="margin-top:2px">مدعوم بالمصادر النظامية</span>' })}
      <div class="app-body no-nav" id="chat-body" style="padding-top:12px"><div class="chat">
        <div class="msg bot">مرحبًا ${ME.short}، أنا مساعدك القانوني الذكي. أجيب استنادًا إلى الأنظمة واللوائح الصحية السعودية المفهرسة مع ذكر المصدر. وللحالات المعقدة يمكنك تحويل سؤالك إلى محامٍ مرخّص.</div>
        ${!msgs.length ? `<div class="stack sm"><div class="tiny muted bold">أسئلة مقترحة</div>${CONSULT_QA.map((x) => `<button class="chip" style="white-space:normal;text-align:right;justify-content:space-between;border-radius:12px" data-act="ask" data-q="${esc(x.q)}">${esc(x.q)} ${icon('chevL', 'sm')}</button>`).join('')}</div>` : ''}
        ${msgs.map(bubble).join('')}
        <div class="tiny faint center">الإجابات إرشادية ولا تُغني عن الاستشارة القانونية المعتمدة</div>
      </div></div>
      <div class="composer"><input id="ask-input" placeholder="اكتب سؤالك القانوني…" data-enter="ask-input"><button class="send" data-act="ask-input" aria-label="إرسال">${icon('send')}</button></div></div>`,
      guide: G('المستشار القانوني الذكي', 'إجابات فورية مبنية على الأنظمة السعودية — وليست إجابات عامة من الإنترنت.', ['كل إجابة تعرض مصادرها النظامية', 'يرفض التخمين عند ضعف المصادر ويقترح التحويل لمحامٍ', 'تحويل السؤال لمحامٍ مرخّص بنقرة', 'تقييم الإجابات لتحسين الجودة']) };
  },

  templates() {
    const cats = ['الكل', ...new Set(TEMPLATES.map((t) => t.cat))];
    const list = TEMPLATES.filter((t) => UI.tplCat === 'الكل' || t.cat === UI.tplCat);
    return { html: `<div class="app">${appHeader('النماذج والإقرارات', { back: '#/dentist/home' })}
      <div class="app-body no-nav"><div class="search">${icon('search')}<input placeholder="ابحث عن نموذج أو إقرار"></div>
        <div class="row" style="overflow-x:auto;margin:10px 0;padding-bottom:4px">${cats.map((c) => `<span class="chip ${UI.tplCat === c ? 'on' : ''}" data-act="tab" data-k="tplCat" data-v="${c}">${c}</span>`).join('')}</div>
        <div class="card" style="padding:2px 12px"><div class="list">${list.map((t) => `<div class="list-item" data-go="#/dentist/template/${t.id}"><span class="ic ${t.cat === 'خطابات قانونية' ? 'blue' : ''}">${icon(t.icon)}</span><div class="grow"><div class="t">${t.name}</div><div class="s">${t.cat} · ${t.upd} · استُخدم ${t.uses} مرة</div></div><span class="chev">${icon('chevL')}</span></div>`).join('')}</div></div>
        <div class="alert gold" style="margin-top:12px"><span class="ic">${icon('scale')}</span><div>جميع النماذج مراجعة من فريق المحامين ومحدّثة وفق آخر تعديلات الأنظمة.</div></div>
      </div></div>`,
      guide: G('النماذج والإقرارات', 'وقاية قبل العلاج: إقرارات موافقة مستنيرة مصاغة قانونيًا لكل إجراء أسنان شائع.', ['مراجعة قانونية دورية لكل نموذج', 'تعبئة سريعة وإرسال للتوقيع الإلكتروني (المرحلة الثانية)', 'خطابات جاهزة: إبلاغ التأمين، طلب خبرة، تظلم']) };
  },

  template(id) {
    const t = TEMPLATES.find((x) => x.id === id) || TEMPLATES[0];
    const risks = ['ألم وتورم مؤقت بعد الإجراء', 'نزيف أو التهاب في موضع الإجراء', 'تنميل مؤقت أو دائم في الشفة أو الذقن أو اللسان', 'فشل الالتحام والحاجة لإجراء إضافي', 'الحاجة لتعديل الخطة العلاجية أثناء الإجراء'];
    return { html: `<div class="app">${appHeader('معاينة النموذج', { back: '#/dentist/templates' })}
      <div class="app-body no-nav"><div class="paper">
        <div class="center"><b style="font-size:15px">${t.name}</b><div class="tiny muted">${ME.clinic}</div></div><div class="divider"></div>
        <p><b>رمز / اسم المريض:</b> ………………… &nbsp; <b>رقم الملف:</b> …………</p>
        <p><b>الإجراء المقترح:</b> شرح الطبيب المعالج طبيعة الإجراء وأهدافه والبدائل العلاجية المتاحة، بما في ذلك خيار عدم العلاج.</p>
        <h4>المخاطر والمضاعفات المحتملة</h4><ol>${risks.map((r) => `<li>${r}</li>`).join('')}</ol>
        <h4>إقرار المريض</h4><p>أقر بأنني فهمت ما شُرح لي، وأتيحت لي فرصة طرح الأسئلة، وأوافق بإرادتي على إجراء العمل الطبي المذكور.</p>
        <p class="row between"><span>توقيع المريض: ………</span><span>توقيع الطبيب: ………</span></p>
        <div class="tiny muted">صيغ هذا النموذج وفق ${cite('م19')} — آخر مراجعة قانونية: أكتوبر 2026</div>
      </div></div>
      <div class="app-foot"><button class="btn btn-secondary" data-act="download">${icon('download')} PDF</button><button class="btn btn-primary grow" data-act="toast" data-msg="سيُرسل رابط توقيع إلكتروني للمريض (المرحلة الثانية)">${icon('send')} إرسال للتوقيع الإلكتروني</button></div></div>`,
      guide: G('معاينة الإقرار', 'نموذج جاهز للطباعة أو التوقيع الإلكتروني مع مرجعه النظامي.', ['حقول قابلة للتعبئة', 'نسخة مؤرشفة تلقائيًا في ملف المريض', 'يُستخدم لاحقًا كدليل قوي عند أي شكوى']) };
  },

  library() {
    const q = UI.libQ.trim();
    const keys = Object.keys(SOURCES).filter((k) => !q || SOURCES[k].title.includes(q) || SOURCES[k].text.includes(q) || SOURCES[k].law.includes(q));
    const byLaw = {};
    keys.forEach((k) => (byLaw[SOURCES[k].law] = byLaw[SOURCES[k].law] || []).push(k));
    return { html: `<div class="app">${appHeader('مكتبة الأنظمة', { back: '#/dentist/home' })}
      <div class="app-body no-nav"><div class="search">${icon('search')}<input placeholder="ابحث في الأنظمة: موافقة، تأمين، تظلم…" value="${esc(UI.libQ)}" data-model="libQ" data-rerender="1"></div>
        <div class="row wrap small muted" style="margin:10px 2px">${icon('database', 'sm')} 4 مصادر مفهرسة · 947 مقطعًا · آخر تحديث ${fmtDate('2026-10-02')}</div>
        <div class="stack">${Object.entries(byLaw).map(([law, ks]) => `<div class="card" style="padding:10px 12px"><div class="row" style="margin-bottom:4px"><span class="badge b-teal">${icon('book')}${law}</span></div><div class="list">${ks.map((k) => `<div class="list-item" data-act="source" data-key="${k}"><span style="width:86px;flex:none">${cite(k)}</span><div class="grow"><div class="t">${SOURCES[k].title}</div><div class="s">${SOURCES[k].ref}</div></div><span class="chev">${icon('chevL')}</span></div>`).join('')}</div></div>`).join('') || '<div class="card center muted">لا توجد نتائج</div>'}</div>
      </div></div>`,
      guide: G('مكتبة الأنظمة', 'المصادر نفسها التي يعتمد عليها محرك الذكاء الاصطناعي — متاحة للطبيب للقراءة والبحث.', ['نظام مزاولة المهن الصحية ولائحته التنفيذية', 'اللائحة التنظيمية لخدمات طب الأسنان', 'إدارة الإصدارات: النسخة السارية والسابقة', 'تُدار من لوحة الإدارة']) };
  },

  notifications() {
    return { html: `<div class="app">${appHeader('التنبيهات', { back: '#/dentist/home', actions: `<button class="btn btn-ghost btn-sm" data-act="read-all">تحديد الكل كمقروء</button>` })}
      <div class="app-body no-nav"><div class="card" style="padding:2px 12px"><div class="list">${S.notifications.map(notifItem).join('')}</div></div></div></div>`,
      guide: G('التنبيهات', 'إشعارات فورية (Push) لكل حدث مهم في القضية وللمواعيد والمهل النظامية.', ['اكتمال التحليل واعتماد المحامي', 'تذكير الجلسات قبلها بأيام', 'انتهاء وثيقة التأمين والاشتراك']) };
  },

  profile() {
    const insDays = daysUntil(ME.insurance.to);
    const row = (ic, t, s, go, extra = '') => `<div class="list-item" ${go ? (go.startsWith('#') ? `data-go="${go}"` : `data-act="${go}"`) : 'style="cursor:default"'}><span class="ic gray">${icon(ic)}</span><div class="grow"><div class="t">${t}</div>${s ? `<div class="s">${s}</div>` : ''}</div>${extra || (go ? `<span class="chev">${icon('chevL')}</span>` : '')}</div>`;
    return { nav: 'profile', html: `<div class="app">${appHeader('حسابي')}
      <div class="app-body"><div class="card center" style="padding:20px"><div class="avatar lg" style="margin:0 auto">${ME.initials}</div><div class="h2" style="margin-top:10px">${ME.name}</div><div class="small muted">${ME.specialty} · ${ME.city}</div>
        <div class="row" style="justify-content:center;margin-top:8px"><span class="badge b-green">${icon('badge')}ترخيص موثّق</span><span class="badge b-gold">${ME.plan}</span></div></div>
        <div class="sec-title"><h3>المعلومات المهنية</h3></div><div class="card" style="padding:2px 12px"><div class="list">${row('badge', 'رقم التسجيل المهني', `<span class="ltr">${ME.license}</span>`)}${row('building', 'المنشأة الصحية', ME.clinic)}</div></div>
        <div class="sec-title"><h3>وثيقة تأمين الأخطاء الطبية</h3></div>
        <div class="card"><div class="row between"><div><div class="bold small">${ME.insurance.company}</div><div class="tiny muted ltr">${ME.insurance.policy}</div></div><span class="badge b-green">سارية</span></div>
          <div class="tiny muted" style="margin:8px 0 6px">من ${fmtDate(ME.insurance.from)} إلى ${fmtDate(ME.insurance.to)} · متبقٍ ${insDays} يومًا</div><div class="progress"><i style="width:${Math.round((insDays / 365) * 100)}%"></i></div></div>
        <div class="sec-title"><h3>الاشتراك</h3></div><div class="card" style="padding:2px 12px"><div class="list">${row('card', ME.plan, `يتجدد في ${fmtDate(ME.planRenew)}`, '#/dentist/subscription')}</div></div>
        <div class="sec-title"><h3>الأمان والخصوصية</h3></div><div class="card" style="padding:2px 12px"><div class="list">
          ${row('key', 'الدخول بالبصمة / Face ID', '', '', '<span class="switch on" data-act="switch"></span>')}${row('monitor', 'الأجهزة والجلسات النشطة', 'جهازان نشطان', 'sessions')}${row('download', 'تنزيل نسخة من بياناتي', 'حق الوصول وفق نظام حماية البيانات الشخصية', 'export-data')}${row('lock', 'سياسة الخصوصية', '', 'toast-privacy')}</div></div>
        <div class="sec-title"><h3>الدعم</h3></div><div class="card" style="padding:2px 12px"><div class="list">${row('help', 'مركز المساعدة', '', 'toast-help')}${row('logout', 'تسجيل الخروج', '', 'logout')}</div></div>
      </div>${tabbar('profile')}</div>`,
      guide: G('الحساب والإعدادات', 'إدارة الملف المهني ووثيقة التأمين والاشتراك وضوابط الأمان.', ['متابعة صلاحية التأمين الإلزامي (المادة 41)', 'إدارة الجلسات والأجهزة النشطة', 'حق تنزيل البيانات وفق PDPL']) };
  },
};

function notifItem(n) {
  return `<div class="list-item" data-act="open-notif" data-id="${n.id}"><span class="ic ${n.cls || ''}">${icon(n.icon)}</span><div class="grow"><div class="t" style="${n.read ? 'font-weight:500' : ''}">${n.t}</div><div class="s">${n.s}</div><div class="s faint">${fmtDT(n.at)}</div></div>${n.read ? '' : '<span style="width:8px;height:8px;border-radius:50%;background:var(--teal-500)"></span>'}</div>`;
}
function caseCard(c) {
  const next = c.hearing && c.status !== 'closed' ? `<div class="row small muted">${icon('calendar', 'sm')} ${c.hearing.label}: ${fmtDate(c.hearing.date)} <span class="badge b-amber">${relDays(c.hearing.date)}</span></div>` : c.outcome ? `<div class="row small muted">${icon('gavel', 'sm')} ${c.outcome}</div>` : '';
  return `<div class="card click" data-go="#/dentist/case/${c.id}"><div class="row between"><span class="tiny muted ltr">${c.id}</span>${statusBadge(c.status)}</div>
    <div class="bold" style="margin:4px 0 6px">${esc(c.title)}</div><div class="row small muted">${icon('landmark', 'sm')} المرحلة: ${STAGES[c.stage]}</div>${next}
    <div class="progress" style="margin-top:10px;height:6px"><i style="width:${Math.round(((c.stage + 1) / 6) * 100)}%"></i></div></div>`;
}
function caseActionCard(c) {
  const id = c.id;
  const law = lawyerById(c.lawyer || 'L-07');
  switch (c.status) {
    case 'analyzing': return `<div class="card tint click" data-go="#/dentist/processing/${id}"><div class="row"><span class="spin" style="display:inline-grid">${icon('refresh')}</span><div class="grow"><b>التحليل الذكي جارٍ</b><div class="tiny muted">اضغط لمتابعة خطوات المعالجة</div></div>${icon('chevL')}</div></div>`;
    case 'ai_ready': return `<div class="card" style="border-color:#d9cdf5;background:var(--violet-50)"><div class="row top"><span style="color:var(--violet-600)">${icon('sparkles', 'lg')}</span><div class="grow"><b>مسودة الرد جاهزة</b><div class="tiny muted">راجع التحليل والمسودة ثم أرسلها للمراجعة القانونية</div></div></div>
      <div class="row" style="margin-top:10px"><button class="btn btn-secondary btn-sm grow" data-go="#/dentist/case/${id}/analysis">التحليل (${c.score}%)</button><button class="btn btn-primary btn-sm grow" data-go="#/dentist/case/${id}/draft">مسودة الرد</button></div></div>`;
    case 'lawyer_review': return `<div class="card" style="border-color:#d6e2fb;background:var(--blue-50)"><div class="row top"><div class="avatar sm gold">${law.initials}</div><div class="grow"><b>لدى ${law.name} للمراجعة</b><div class="tiny muted">${law.firm} · متوقع خلال ${Math.max(slaLeft(c) ?? 24, 1)} ساعة</div></div></div>
      <div class="progress" style="margin-top:10px"><i style="width:35%"></i></div><button class="btn btn-ghost btn-sm" style="margin-top:6px" data-go="#/dentist/case/${id}/draft">عرض المسودة المرسلة</button></div>`;
    case 'needs_info': return `<div class="alert amber"><span class="ic">${icon('alert')}</span><div class="grow"><b>طلب المحامي معلومات إضافية</b><br>يرجى إرفاق ملاحظة الزيارة وملاحظات المتابعة، ثم إعادة الإرسال.<div class="row" style="margin-top:8px"><button class="btn btn-secondary btn-sm" data-act="toast" data-msg="سيُفتح منتقي الملفات (في التطبيق الفعلي)">${icon('upload', 'sm')} إرفاق</button><button class="btn btn-primary btn-sm" data-act="resubmit" data-id="${id}">إعادة الإرسال</button></div></div></div>`;
    case 'approved': return `<div class="card" style="border-color:var(--gold-500);background:var(--gold-100)"><div class="row top"><span style="color:var(--gold-600)">${icon('stamp', 'lg')}</span><div class="grow"><b>تم اعتماد الرد قانونيًا</b><div class="tiny" style="color:#5d4419">اعتمدته ${law.name} — جاهز للتنزيل والتقديم للجهة</div></div></div>
      <div class="row" style="margin-top:10px"><button class="btn btn-secondary btn-sm grow" data-act="download">${icon('download')} PDF</button><button class="btn btn-gold btn-sm grow" data-go="#/dentist/case/${id}/draft">عرض الرد المعتمد</button></div></div>`;
    case 'submitted': return `<div class="card"><div class="row top"><span style="color:var(--green-600)">${icon('checkCircle', 'lg')}</span><div class="grow"><b>تم تقديم الرد للجهة</b><div class="tiny muted">الإصدار المعتمد ${c.versions || 2} · ${law.name}</div></div><button class="btn btn-secondary btn-sm" data-go="#/dentist/case/${id}/draft">عرض</button></div></div>`;
    case 'closed': return `<div class="card"><div class="row top"><span style="color:var(--teal-600)">${icon('gavel', 'lg')}</span><div class="grow"><b>القضية مغلقة</b><div class="tiny muted">${c.outcome || ''}</div></div></div></div>`;
    default: return '';
  }
}

function genericAnalysis(c) {
  return {
    score: c.score || 70, risk: c.risk || 'متوسط',
    summary: `تحليل محفوظ للقضية "${c.title}". يعتمد الموقف على اكتمال التوثيق في الملف الطبي ووجود موافقة مستنيرة موقّعة.`,
    strengths: [{ t: 'توثيق الإجراء في الملف الطبي بتاريخ الإجراء', cites: ['م27'] }, { t: 'وجود إقرار موافقة موقّع من المريض', cites: ['م19'] }],
    risks: [{ t: 'ادعاء المريض بنقص المتابعة بعد الإجراء', cites: ['م27'], level: c.risk === 'مرتفع' ? 'مرتفع' : 'متوسط' }],
    actions: [{ t: 'إبلاغ شركة التأمين بالشكوى والاحتفاظ بالإثبات', cites: ['ل40-2'], due: 'عاجل' }],
    sources: [['م27', 0.93], ['م19', 0.88], ['ل40-1', 0.81], ['ل40-2', 0.72]],
  };
}
function genericDraft(c, ver) {
  return `<div class="meta">${ver === 2 ? 'إفادة الممارس الصحي — الإصدار 2 (مراجعة المحامي)' : 'مسودة إفادة — الإصدار 1 (AI)'} · الشكوى رقم ${esc(c.complaintNo || '')}</div>
<p><b>سعادة مدير الشؤون القانونية — ${esc(c.authority)}</b> المحترم،</p><p>السلام عليكم ورحمة الله وبركاته، وبعد:</p>
<p>أتقدم أنا ${esc(c.ownerName || ME.name)} بإفادتي بشأن الشكوى المقدمة من المريض (الرمز ${esc(c.patientCode)}) المتعلقة بـ${esc(c.procedure)}، وأوضح ما يلي:</p>
<h4>أولًا: الوقائع</h4><ol><li>أُجري الإجراء بتاريخ ${fmtDate(c.procedureDate)} وفق الأصول المهنية بعد الحصول على موافقة المريض {{c:م19}}.</li><li>وُثّقت الزيارات اللاحقة وتعليمات ما بعد الإجراء في الملف الطبي.</li></ol>
<h4>ثانيًا: الدفوع</h4><ol><li>انتفاء الخطأ المهني الصحي بصوره المحددة نظامًا، وعدم ثبوت علاقة السببية بين الإجراء والضرر المدّعى {{c:م27}}.</li></ol>
<h4>ثالثًا: الطلبات</h4><p>ألتمس حفظ الشكوى لعدم ثبوت الخطأ المهني الصحي {{c:ل40-1}}.</p><p>وتقبلوا وافر التحية والتقدير،،،</p>`;
}

function runProcessing(id, total) {
  clearTimeout(UI.procTimer);
  UI.procStep = 0;
  const tick = () => {
    UI.procStep++;
    if (UI.procStep >= total) { UI.procTimer = null; ensureAnalyzed(); if (location.hash.includes('/processing/')) render(); return; }
    if (location.hash.includes('/processing/')) { render(); UI.procTimer = setTimeout(tick, 650); } else { UI.procTimer = null; ensureAnalyzed(); }
  };
  UI.procTimer = setTimeout(tick, 650);
}
