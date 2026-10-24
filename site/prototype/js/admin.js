/* Platform admin dashboard */
function adminNav() {
  const pendingDocs = DOCTORS.filter((d) => (S.verified[d.id] || d.ver) === 'بانتظار التحقق').length;
  return [
    { grp: 'التشغيل' },
    { k: 'overview', ic: 'grid', l: 'نظرة عامة', go: '#/admin/overview' },
    { k: 'cases', ic: 'briefcase', l: 'القضايا', go: '#/admin/cases' },
    { k: 'doctors', ic: 'users', l: 'الأطباء والتوثيق', go: '#/admin/doctors', b: pendingDocs || '', bc: 'b-amber' },
    { k: 'lawyers', ic: 'scale', l: 'المحامون والإسناد', go: '#/admin/lawyers' },
    { grp: 'المحتوى والذكاء الاصطناعي' },
    { k: 'kb', ic: 'database', l: 'قاعدة المعرفة (RAG)', go: '#/admin/kb' },
    { k: 'templates', ic: 'clipboard', l: 'النماذج والإقرارات', go: '#/admin/templates' },
    { k: 'ai', ic: 'cpu', l: 'إعدادات الذكاء الاصطناعي', go: '#/admin/ai' },
    { grp: 'الحوكمة والأمان' },
    { k: 'roles', ic: 'key', l: 'الأدوار والصلاحيات', go: '#/admin/roles' },
    { k: 'audit', ic: 'history', l: 'سجل التدقيق', go: '#/admin/audit' },
  ];
}
const docVer = (d) => S.verified[d.id] || d.ver;
const verBadge = (v) => `<span class="badge ${v === 'موثّق' ? 'b-green' : v === 'مرفوض' ? 'b-red' : 'b-amber'}">${v}</span>`;
const mockNote = '<div class="tiny faint">جميع الأرقام والأسماء في لوحة الإدارة بيانات تجريبية لأغراض العرض.</div>';

const ADMINV = {
  overview() {
    const months = [['مايو', 14], ['يونيو', 19], ['يوليو', 22], ['أغسطس', 27], ['سبتمبر', 31], ['أكتوبر', 38]];
    const byStage = [9, 12, 7, 5, 3, 1];
    const pending = DOCTORS.filter((d) => docVer(d) === 'بانتظار التحقق');
    return webShell('admin', 'overview', 'نظرة عامة على المنصة', 'لوحة الإدارة', `
      <div class="kpis">${kpi('users', '248', 'طبيب مشترك', '▲ 18 هذا الشهر')}${kpi('briefcase', '37', 'قضية نشطة', '▲ 9 عن الشهر الماضي')}${kpi('clock', '10.6 س', 'متوسط زمن الاعتماد القانوني', '▼ 14%')}${kpi('badge', '98.7%', 'دقة الاستشهادات (تدقيق المحامين)', 'الهدف ≥ 97%')}</div>
      <div class="cols"><div class="card pad-lg"><div class="card-head"><h3>القضايا الجديدة شهريًا — 2026</h3><span class="legend"><span><i style="background:var(--teal-500)"></i>قضايا</span></span></div>
        <div class="bars" style="height:200px">${months.map(([m, v]) => `<div class="b"><b>${v}</b><i style="height:${v * 4}px"></i><span>${m}</span></div>`).join('')}</div></div>
        <div class="card pad-lg"><div class="card-head"><h3>القضايا النشطة حسب المرحلة</h3></div>${STAGES.map((s, i) => `<div class="hbar"><span>${s}</span><div class="track"><i style="width:${(byStage[i] / 12) * 100}%"></i></div><b class="num">${byStage[i]}</b></div>`).join('')}</div></div>
      <div class="cols eq"><div class="card pad-lg"><div class="card-head"><h3>طلبات توثيق بانتظار الإجراء</h3><a class="link" data-go="#/admin/doctors">إدارة الأطباء</a></div>
        <div class="list">${pending.length ? pending.map((d) => `<div class="list-item" data-act="verify" data-id="${d.id}"><div class="avatar sm">${d.name.replace('د. ', '').slice(0, 2)}</div><div class="grow"><div class="t">${d.name}</div><div class="s">${d.spec} · ${d.city} · انضم ${fmtDate(d.joined)}</div></div><button class="btn btn-primary btn-sm">مراجعة</button></div>`).join('') : '<div class="muted small">لا توجد طلبات معلّقة</div>'}</div></div>
        <div class="card pad-lg"><div class="card-head"><h3>صحة النظام والذكاء الاصطناعي</h3><span class="badge b-green">${icon('activity')}كل الخدمات تعمل</span></div>
          <div class="list">${[['cpu', 'خدمة التحليل والصياغة', 'متوسط زمن الاستجابة 4.2 ثانية', 'b-green', 'تعمل'], ['scanText', 'قائمة معالجة OCR', 'مهمتان قيد التنفيذ', 'b-green', 'تعمل'], ['database', 'فهرس المتجهات', '947 مقطعًا · محدّث', 'b-green', 'محدّث'], ['pen', 'نسبة المسودات المعدّلة من المحامين', '31% — تُراقب لتحسين الصياغة', 'b-blue', 'مؤشر'], ['refresh', 'النسخ الاحتياطي', 'آخر نسخة مشفّرة 03:00 اليوم', 'b-green', 'ناجح']].map(([i, t, s, b, l]) => `<div class="list-item" style="cursor:default"><span class="ic gray">${icon(i)}</span><div class="grow"><div class="t">${t}</div><div class="s">${s}</div></div><span class="badge ${b}">${l}</span></div>`).join('')}</div></div></div>
      ${mockNote}`, `<span class="badge b-gray">${icon('calendar')}${fmtDate(DEMO_TODAY)}</span>`);
  },

  cases() {
    return webShell('admin', 'cases', 'جميع القضايا', 'لوحة الإدارة', `
      <div class="card pad-lg"><div class="table-wrap"><table class="table"><thead><tr><th>رقم القضية</th><th>الموضوع</th><th>الطبيب</th><th>المحامي</th><th>المرحلة</th><th>الحالة</th><th>المخاطر</th><th>آخر تحديث</th></tr></thead><tbody>
      ${S.cases.map((c) => `<tr><td class="mono">${c.id}</td><td><b>${esc(c.title)}</b></td><td>${esc(c.ownerName || ME.name)}</td><td>${c.lawyer ? lawyerById(c.lawyer).name : '—'}</td><td class="small">${STAGES[c.stage]}</td><td>${statusBadge(c.status)}</td><td>${c.risk ? riskBadge(c.risk) : '—'}</td><td class="small">${fmtDate(c.updatedAt || c.createdAt)}</td></tr>`).join('')}
      </tbody></table></div></div>
      <div class="alert blue"><span class="ic">${icon('lock')}</span><div>تعرض الإدارة البيانات الوصفية للقضايا فقط. الاطلاع على المستندات يتطلب مبررًا ويُسجّل في سجل التدقيق.</div></div>`);
  },

  doctors() {
    const f = UI.docFilter;
    const list = DOCTORS.filter((d) => f === 'الكل' || docVer(d) === f);
    return webShell('admin', 'doctors', 'الأطباء والتوثيق', 'لوحة الإدارة', `
      <div class="card pad-lg"><div class="row between wrap" style="margin-bottom:12px"><div class="seg">${['الكل', 'موثّق', 'بانتظار التحقق', 'مرفوض'].map((x) => `<button class="${f === x ? 'on' : ''}" data-act="tab" data-k="docFilter" data-v="${x}">${x}</button>`).join('')}</div><div class="search" style="width:260px">${icon('search')}<input placeholder="بحث بالاسم أو رقم الترخيص"></div></div>
      <div class="table-wrap"><table class="table"><thead><tr><th>الطبيب</th><th>التخصص</th><th>المدينة</th><th>رقم التسجيل المهني</th><th>التوثيق</th><th>الاشتراك</th><th>القضايا</th><th>الانضمام</th><th></th></tr></thead><tbody>
      ${list.map((d) => `<tr><td><b>${d.name}</b></td><td>${d.spec}</td><td>${d.city}</td><td class="mono">${d.license}</td><td>${verBadge(docVer(d))}</td><td>${d.plan}</td><td class="num">${d.cases}</td><td class="small">${fmtDate(d.joined)}</td><td>${docVer(d) === 'بانتظار التحقق' ? `<button class="btn btn-primary btn-sm" data-act="verify" data-id="${d.id}">تحقق</button>` : `<button class="btn btn-ghost btn-sm" data-act="toast" data-msg="ملف الطبيب: الاشتراك، القضايا، سجل الدخول">عرض</button>`}</td></tr>`).join('')}
      </tbody></table></div></div>${mockNote}`);
  },

  lawyers() {
    return webShell('admin', 'lawyers', 'المحامون والإسناد', 'لوحة الإدارة', `
      <div class="cols three" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">${LAWYERS.map((l) => `<div class="card pad-lg"><div class="row"><div class="avatar gold">${l.initials}</div><div class="grow"><div class="bold">${l.name}</div><div class="tiny muted">${l.firm}</div></div><span class="badge ${l.status === 'متاح' ? 'b-green' : l.status === 'مشغول' ? 'b-amber' : 'b-gray'}">${l.status}</span></div>
        <div class="stack sm small" style="margin-top:12px"><div class="row between"><span class="muted">التخصص</span><b>${l.focus}</b></div><div class="row between"><span class="muted">الترخيص</span><b>${l.license}</b></div><div class="row between"><span class="muted">متوسط زمن المراجعة</span><b>${l.avgHours} ساعة</b></div><div class="row between"><span class="muted">التقييم</span><b>${l.rating} / 5</b></div>
        <div class="row between"><span class="muted">الحِمل الحالي</span><b>${l.inReview} / 5</b></div><div class="progress"><i style="width:${l.inReview * 20}%"></i></div></div></div>`).join('')}</div>
      <div class="card pad-lg"><div class="card-head"><h3>قواعد الإسناد التلقائي</h3><span class="badge b-teal">مفعّلة</span></div>
        <div class="list">${[['المطابقة حسب تخصص المحامي ونوع القضية', true], ['التوزيع حسب الحِمل الحالي (حد أقصى 5 قضايا متزامنة)', true], ['مهلة المراجعة القانونية: 24 ساعة', true], ['التصعيد لمشرف المحامين عند تجاوز 20 ساعة', true], ['السماح للطبيب باختيار محامٍ مفضل', false]].map(([t, on]) => `<div class="list-item" style="cursor:default"><span class="ic gray">${icon('settings')}</span><div class="grow t" style="font-weight:500">${t}</div><span class="switch ${on ? 'on' : ''}" data-act="switch"></span></div>`).join('')}</div></div>`,
      `<button class="btn btn-primary btn-sm" data-act="toast" data-msg="نموذج دعوة محامٍ جديد مع التحقق من الترخيص">${icon('plus')} دعوة محامٍ</button>`);
  },

  kb() {
    const docs = [...KB_DOCS, ...S.kbExtra];
    const stBadge = (s) => `<span class="badge ${s === 'مفهرس' ? 'b-green' : s === 'مؤرشف' ? 'b-gray' : s === 'قيد الفهرسة' ? 'b-violet' : 'b-blue'}">${s}</span>`;
    const res = UI.kbTest;
    return webShell('admin', 'kb', 'قاعدة المعرفة (RAG)', 'لوحة الإدارة / المحتوى', `
      <div class="kpis">${kpi('book', docs.filter((d) => d.status === 'مفهرس').length, 'مصادر سارية مفهرسة')}${kpi('layers', '947', 'مقطعًا (مقسّمة حسب المادة)')}${kpi('history', '1', 'إصدار مؤرشف محفوظ')}${kpi('zap', '0.31 ث', 'متوسط زمن الاسترجاع')}</div>
      <div class="card pad-lg"><div class="card-head"><h3>المصادر</h3><span class="tiny muted">المصادر الحالية مأخوذة من الملفات المقدمة من العميل</span></div><div class="table-wrap"><table class="table"><thead><tr><th>المصدر</th><th>الإصدار</th><th>الصفحات</th><th>المقاطع</th><th>النسخة</th><th>الحالة</th><th>آخر تحديث</th><th>الملف</th></tr></thead><tbody>
      ${docs.map((d) => `<tr><td><b>${d.name}</b></td><td class="small">${d.ed}</td><td class="num">${d.pages || '—'}</td><td class="num">${d.chunks || '—'}</td><td class="small">${d.ver}</td><td>${stBadge(d.status)}</td><td class="small">${d.updated === '—' ? '—' : fmtDate(d.updated)}</td><td class="tiny muted" style="max-width:180px">${d.src}</td></tr>`).join('')}
      </tbody></table></div></div>
      <div class="cols eq"><div class="card pad-lg"><div class="card-head"><h3>${icon('search')} اختبار الاسترجاع</h3></div>
        <div class="row"><input class="input" id="kb-q" value="${esc(res?.q || 'موافقة المريض قبل الزراعة')}" placeholder="اكتب سؤالًا لاختبار ما يسترجعه المحرك"><button class="btn btn-primary" data-act="kb-test">اختبار</button></div>
        ${res ? `<div class="stack sm" style="margin-top:12px">${res.hits.map(([k, s]) => `<div class="src-card" data-act="source" data-key="${k}" style="cursor:pointer"><div class="h"><span>${citeLabel(k)} — ${SOURCES[k].title}</span><span class="score num">${s.toFixed(2)}</span></div><div class="tiny muted">${SOURCES[k].text.slice(0, 120)}…</div></div>`).join('')}</div>` : '<p class="small muted">يساعد على التأكد من أن المحرك يسترجع المواد الصحيحة قبل نشر أي تحديث.</p>'}</div>
        <div class="card pad-lg"><div class="card-head"><h3>${icon('settings')} إعدادات الفهرسة</h3></div><div class="list">${[['التقسيم', 'حسب المادة والفقرة مع الاحتفاظ برقم المادة والصفحة'], ['التضمين (Embeddings)', 'نموذج متعدد اللغات يدعم العربية'], ['البحث', 'هجين: دلالي (pgvector) + نصي (BM25) مع إعادة ترتيب'], ['الإصدارات', 'النسخة السارية فقط في الاسترجاع، والسابقة للمرجعية التاريخية']].map(([t, s]) => `<div class="list-item" style="cursor:default"><span class="ic gray">${icon('layers')}</span><div class="grow"><div class="t">${t}</div><div class="s">${s}</div></div></div>`).join('')}</div></div></div>`,
      `<button class="btn btn-primary btn-sm" data-act="kb-upload">${icon('upload')} رفع مصدر جديد</button>`);
  },

  templates() {
    return webShell('admin', 'templates', 'النماذج والإقرارات', 'لوحة الإدارة / المحتوى', `
      <div class="card pad-lg"><div class="table-wrap"><table class="table"><thead><tr><th>النموذج</th><th>التصنيف</th><th>مرات الاستخدام</th><th>آخر مراجعة</th><th>المراجِع</th><th>الحالة</th><th></th></tr></thead><tbody>
      ${TEMPLATES.map((t, i) => `<tr><td><b>${t.name}</b></td><td>${t.cat}</td><td class="num">${t.uses}</td><td class="small">${t.upd}</td><td>${LAWYERS[i % 3].name}</td><td><span class="badge b-green">منشور</span></td><td><button class="btn btn-ghost btn-sm" data-act="toast" data-msg="محرر النماذج: الحقول، المتغيرات، سجل الإصدارات">${icon('pen', 'sm')} تحرير</button></td></tr>`).join('')}
      </tbody></table></div></div>`, `<button class="btn btn-primary btn-sm" data-act="toast" data-msg="إنشاء نموذج جديد يتطلب مراجعة محامٍ قبل النشر">${icon('plus')} نموذج جديد</button>`);
  },

  ai() {
    const row = (t, s, ctrl) => `<div class="list-item" style="cursor:default"><div class="grow"><div class="t">${t}</div><div class="s">${s}</div></div>${ctrl}</div>`;
    const sw = (on, locked) => `<span class="row">${locked ? `<span class="badge b-gray">${icon('lock')}إلزامي</span>` : ''}<span class="switch ${on ? 'on' : ''}" ${locked ? '' : 'data-act="switch"'}></span></span>`;
    return webShell('admin', 'ai', 'إعدادات الذكاء الاصطناعي', 'لوحة الإدارة / المحتوى', `
      <div class="cols eq"><div class="card pad-lg"><div class="card-head"><h3>ضوابط الحوكمة</h3></div><div class="list">
        ${row('اعتماد المحامي قبل أي استخدام للرد', 'لا يمكن تنزيل أو تقديم رد غير معتمد', sw(true, true))}
        ${row('إخفاء البيانات التعريفية قبل المعالجة', 'استبدال الأسماء والهويات برموز قبل إرسال النص للنموذج اللغوي', sw(true, true))}
        ${row('إلزام الاستشهاد لكل ادعاء قانوني', 'تُعلَّم الفقرات غير المسندة للمراجعة', sw(true, false))}
        ${row('رفض الإجابة عند ضعف المصادر', 'اقتراح التحويل لمحامٍ بدل التخمين', sw(true, false))}
        ${row('استخدام تعديلات المحامين لتحسين الجودة', 'بعد إخفاء الهوية وبموافقة الطرفين', sw(false, false))}
      </div></div>
      <div class="card pad-lg"><div class="card-head"><h3>معاملات المحرك</h3></div><div class="list">
        ${row('الحد الأدنى لدرجة الصلة', 'المقاطع الأقل لا تُستخدم في الصياغة', '<b class="num">0.70</b>')}
        ${row('عدد المقاطع المسترجعة', 'قبل إعادة الترتيب / بعده', '<b class="num">20 / 7</b>')}
        ${row('النموذج اللغوي', 'عبر API بسياسة عدم الاحتفاظ بالبيانات — يُحسم في المرحلة الثانية', '<span class="badge b-violet">LLM مؤسسي</span>')}
        ${row('مهلة المراجعة القانونية', 'يتلقى الطبيب تقديرًا للوقت بناءً عليها', '<b>24 ساعة</b>')}
        ${row('موقع معالجة البيانات وتخزينها', 'داخل المملكة وفق متطلبات حماية البيانات', '<span class="badge b-green">KSA</span>')}
      </div></div></div>`);
  },

  roles() {
    const Y = `<span class="yes">${icon('checkCircle')}</span>`, N = `<span class="no">${icon('x')}</span>`;
    const P = (t) => `<span class="badge b-amber">${t}</span>`;
    const rows = [
      ['إنشاء قضية ورفع المستندات', Y, N, N], ['عرض مستندات القضية', P('قضاياه فقط'), P('المُسندة له فقط'), P('بمبرر + تسجيل')],
      ['تشغيل التحليل الذكي', Y, Y, N], ['تعديل مسودة الرد', P('طلب تعديل'), Y, N], ['اعتماد الرد وتوقيعه', N, Y, N], ['تأكيد تقديم الرد للجهة', Y, N, N],
      ['المستشار القانوني الذكي', Y, Y, N], ['الرد على الاستشارات المحوّلة', N, Y, N], ['توثيق حسابات الأطباء', N, N, Y], ['إدارة المحامين وقواعد الإسناد', N, N, Y],
      ['إدارة قاعدة المعرفة والنماذج', N, P('اقتراح ومراجعة'), Y], ['إعدادات الذكاء الاصطناعي', N, N, Y], ['عرض سجل التدقيق', P('نشاطه فقط'), P('نشاطه فقط'), Y],
    ];
    return webShell('admin', 'roles', 'الأدوار والصلاحيات', 'لوحة الإدارة / الحوكمة', `
      <div class="card pad-lg"><div class="table-wrap"><table class="table perm"><thead><tr><th>الصلاحية</th><th>${icon('stethoscope', 'sm')} طبيب الأسنان</th><th>${icon('scale', 'sm')} المحامي المراجِع</th><th>${icon('settings', 'sm')} مدير المنصة</th></tr></thead><tbody>
      ${rows.map((r) => `<tr><td><b>${r[0]}</b></td><td>${r[1]}</td><td>${r[2]}</td><td>${r[3]}</td></tr>`).join('')}</tbody></table></div></div>
      <div class="alert blue"><span class="ic">${icon('info')}</span><div>نموذج صلاحيات قائم على الأدوار (RBAC) مع مبدأ الحد الأدنى من الصلاحيات. يمكن إضافة أدوار فرعية للإدارة (مشرف محتوى، دعم فني) في المرحلة الثانية.</div></div>`);
  },

  audit() {
    const f = UI.auditRole;
    const list = S.audit.filter((a) => f === 'الكل' || a.role === f);
    return webShell('admin', 'audit', 'سجل التدقيق', 'لوحة الإدارة / الحوكمة', `
      <div class="card pad-lg"><div class="row between wrap" style="margin-bottom:12px"><div class="seg">${['الكل', 'طبيب', 'محامٍ', 'نظام', 'إدارة'].map((x) => `<button class="${f === x ? 'on' : ''}" data-act="tab" data-k="auditRole" data-v="${x}">${x}</button>`).join('')}</div><button class="btn btn-secondary btn-sm" data-act="toast" data-msg="تصدير السجل (CSV) — يُسجَّل التصدير نفسه في السجل">${icon('download')} تصدير</button></div>
      <div class="table-wrap"><table class="table"><thead><tr><th>الوقت</th><th>المستخدم</th><th>الدور</th><th>الإجراء</th><th>الهدف</th><th>عنوان IP</th></tr></thead><tbody>
      ${list.map((a) => `<tr><td class="small">${fmtDT(a.at)}</td><td><b>${esc(a.actor)}</b></td><td><span class="badge ${a.role === 'طبيب' ? 'b-teal' : a.role === 'محامٍ' ? 'b-gold' : a.role === 'نظام' ? 'b-violet' : 'b-gray'}">${a.role}</span></td><td>${esc(a.act)}</td><td class="${/^[A-Z0-9-]+$/.test(a.target) ? 'mono' : 'small'}">${esc(a.target)}</td><td class="mono">${a.ip}</td></tr>`).join('')}
      </tbody></table></div></div>
      <div class="alert teal"><span class="ic">${icon('lock')}</span><div>سجل إلحاقي غير قابل للتعديل (append-only) يوثّق كل اطلاع على مستند وكل تعديل واعتماد — أساس للمساءلة والامتثال لنظام حماية البيانات الشخصية.</div></div>`);
  },
};
