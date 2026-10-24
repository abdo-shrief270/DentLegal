/* Lawyer web portal + shared web shell */
function webShell(role, active, title, crumb, content, actions = '') {
  const nav = role === 'lawyer' ? lawyerNav() : adminNav();
  const who = role === 'lawyer' ? { n: LAWYERS[0].name, r: 'محامية مراجِعة · ' + LAWYERS[0].license, i: LAWYERS[0].initials, c: 'gold' } : { n: ADMIN.name, r: ADMIN.role, i: ADMIN.initials, c: 'violet' };
  return `<div class="web"><aside class="side ${UI.sideOpen ? 'open' : ''}"><div class="brand">${logo()} DentLegal <span class="badge b-teal" style="margin-right:auto">${role === 'lawyer' ? 'بوابة المحامي' : 'الإدارة'}</span></div>
      <div class="who"><div class="avatar sm ${who.c}">${who.i}</div><div><div class="n">${who.n}</div><div class="r">${who.r}</div></div></div>
      ${nav.map((x) => x.grp ? `<div class="grp">${x.grp}</div>` : `<div class="nav ${active === x.k ? 'on' : ''}" data-go="${x.go}">${icon(x.ic)}<span>${x.l}</span>${x.b ? `<span class="badge ${x.bc || 'b-teal'}">${x.b}</span>` : ''}</div>`).join('')}
      <div class="sp"></div><div class="nav" data-go="#/">${icon('logout')}<span>الخروج إلى شاشة العرض</span></div></aside>
    <main class="main"><div class="topbar"><button class="icon-btn menu-btn" data-act="side">${icon('menu')}</button><div class="grow" style="min-width:0"><div class="crumb">${crumb}</div><div class="ttl">${title}</div></div><div class="tb-actions row">${actions}</div></div>
      <div class="content">${content}</div></main></div>`;
}
function lawyerNav() {
  const mine = S.cases.filter((c) => c.lawyer === 'L-07');
  const pending = mine.filter((c) => c.status === 'lawyer_review').length;
  const consults = S.consults.filter((c) => c.status === 'جديدة').length;
  return [
    { k: 'dashboard', ic: 'grid', l: 'لوحة المراجعة', go: '#/lawyer/dashboard' },
    { k: 'queue', ic: 'listChecks', l: 'قائمة المراجعة', go: '#/lawyer/queue', b: pending || '', bc: 'b-amber' },
    { k: 'consults', ic: 'chats', l: 'الاستشارات المحوّلة', go: '#/lawyer/consults', b: consults || '' },
    { k: 'library', ic: 'book', l: 'المصادر النظامية', go: '#/lawyer/library' },
  ];
}
function kpi(ic, v, l, d = '', dc = 'up') { return `<div class="kpi"><span class="ic">${icon(ic, 'lg')}</span><div class="grow"><div class="v num">${v}</div><div class="l">${l}</div>${d ? `<div class="d ${dc}">${d}</div>` : ''}</div></div>`; }
function slaBadge(c) {
  if (c.status !== 'lawyer_review') return '<span class="muted small">—</span>';
  const h = slaLeft(c) ?? 24;
  const cls = h <= 4 ? 'b-red' : h <= 10 ? 'b-amber' : 'b-green';
  return `<span class="badge ${cls}">${icon('clock')}${h > 0 ? `${h} ساعة` : 'متجاوزة'}</span>`;
}
function queueRows(list) {
  return list.map((c) => `<tr class="click" data-go="#/lawyer/review/${c.id}"><td class="mono">${c.id}</td><td><b>${esc(c.title)}</b><div class="tiny muted">${esc(c.kind)}</div></td><td>${esc(c.ownerName || ME.name)}</td><td class="small">${STAGES[c.stage]}</td><td>${c.risk ? riskBadge(c.risk) : '—'}</td><td>${slaBadge(c)}</td><td>${statusBadge(c.status)}</td><td><button class="btn btn-secondary btn-sm">${c.status === 'lawyer_review' ? 'مراجعة' : 'عرض'}</button></td></tr>`).join('');
}
function demoMissingRow() {
  if (caseById(DEMO_CASE_ID) && statusIdx(caseById(DEMO_CASE_ID).status) >= statusIdx('lawyer_review')) return '';
  return `<div class="alert blue"><span class="ic">${icon('info')}</span><div class="grow">لم يُرسل د. أحمد الغامدي قضية الجولة (${DEMO_CASE_ID}) للمراجعة بعد. يمكنك إنشاؤها من تطبيق الطبيب، أو تجهيزها مباشرة لتجربة المراجعة.</div><button class="btn btn-primary btn-sm" data-act="demo-send">تجهيز الطلب التجريبي</button></div>`;
}

const LAWYER = {
  dashboard() {
    const mine = S.cases.filter((c) => c.lawyer === 'L-07');
    const pending = mine.filter((c) => c.status === 'lawyer_review').sort((a, b) => (slaLeft(a) ?? 99) - (slaLeft(b) ?? 99));
    const days = [['الأحد', 4], ['الإثنين', 6], ['الثلاثاء', 3], ['الأربعاء', 5], ['الخميس', 2]];
    return webShell('lawyer', 'dashboard', 'لوحة المراجعة', 'بوابة المحامي', `
      ${demoMissingRow()}
      <div class="kpis">${kpi('hourglass', pending.length, 'بانتظار مراجعتك', 'الأقرب انتهاءً خلال ' + (pending[0] ? (slaLeft(pending[0]) ?? 24) + ' ساعة' : '—'), 'down')}${kpi('clock', '9.5 س', 'متوسط زمن المراجعة', '▼ 1.2 س عن الشهر الماضي')}${kpi('badge', 18 + mine.filter((c) => c.status === 'approved' && c.approvedAt).length, 'ردود معتمدة هذا الشهر', '▲ 22%')}${kpi('shield', '96%', 'الالتزام بمهلة الخدمة (SLA)', 'الهدف 95%')}</div>
      <div class="card pad-lg"><div class="card-head"><h3>طلبات بانتظار المراجعة</h3><a class="link" data-go="#/lawyer/queue">قائمة المراجعة كاملة</a></div>
        <div class="table-wrap"><table class="table"><thead><tr><th>رقم القضية</th><th>الموضوع</th><th>الطبيب</th><th>المرحلة</th><th>المخاطر</th><th>المهلة</th><th>الحالة</th><th></th></tr></thead><tbody>${queueRows(pending) || '<tr><td colspan="8" class="center muted">لا توجد طلبات معلّقة</td></tr>'}</tbody></table></div></div>
      <div class="cols eq"><div class="card pad-lg"><div class="card-head"><h3>الاستشارات المحوّلة</h3><a class="link" data-go="#/lawyer/consults">الكل</a></div><div class="list">${S.consults.slice(0, 3).map((q) => `<div class="list-item" data-go="#/lawyer/consults"><span class="ic blue">${icon('chats')}</span><div class="grow"><div class="t">${esc(q.q)}</div><div class="s">${esc(q.by)} · ${fmtDT(q.at)}</div></div><span class="badge ${q.status === 'جديدة' ? 'b-amber' : 'b-green'}">${q.status}</span></div>`).join('')}</div></div>
        <div class="card pad-lg"><div class="card-head"><h3>المراجعات هذا الأسبوع</h3></div><div class="bars">${days.map(([d, v]) => `<div class="b"><b>${v}</b><i style="height:${v * 22}px"></i><span>${d}</span></div>`).join('')}</div></div></div>
      <div class="alert teal"><span class="ic">${icon('sparkles')}</span><div>تعديلاتك على المسودات تُحفظ كإصدارات مستقلة، وتُستخدم (بعد إخفاء الهوية) لقياس جودة محرك الصياغة وتحسينه.</div></div>`,
      `<span class="badge b-green">${icon('checkCircle')}متاحة</span>`);
  },

  queue() {
    const mine = S.cases.filter((c) => c.lawyer === 'L-07');
    const tabs = { review: mine.filter((c) => c.status === 'lawyer_review'), info: mine.filter((c) => c.status === 'needs_info'), done: mine.filter((c) => ['approved', 'submitted', 'closed'].includes(c.status)) };
    const t = (k, l) => `<button class="${UI.queueTab === k ? 'on' : ''}" data-act="tab" data-k="queueTab" data-v="${k}">${l}<span class="count">${tabs[k].length}</span></button>`;
    return webShell('lawyer', 'queue', 'قائمة المراجعة', 'بوابة المحامي', `
      ${demoMissingRow()}
      <div class="card pad-lg"><div class="row between wrap" style="margin-bottom:12px"><div class="tabs" style="border:0">${t('review', 'بانتظار المراجعة')}${t('info', 'مطلوب معلومات')}${t('done', 'معتمدة')}</div>
        <div class="search" style="width:260px">${icon('search')}<input placeholder="بحث برقم القضية أو الطبيب"></div></div>
        <div class="table-wrap"><table class="table"><thead><tr><th>رقم القضية</th><th>الموضوع</th><th>الطبيب</th><th>المرحلة</th><th>المخاطر</th><th>المهلة المتبقية</th><th>الحالة</th><th></th></tr></thead><tbody>${queueRows(tabs[UI.queueTab]) || '<tr><td colspan="8" class="center muted" style="padding:30px">لا توجد قضايا في هذا التصنيف</td></tr>'}</tbody></table></div></div>
      <div class="alert blue"><span class="ic">${icon('info')}</span><div>تُسند الطلبات تلقائيًا حسب التخصص والحِمل الحالي، وتُصعّد للمشرف إذا اقتربت المهلة من الانتهاء دون مراجعة.</div></div>`);
  },

  review(id) {
    const c = caseById(id);
    if (!c) return LAWYER.queue();
    const isDemo = id === DEMO_CASE_ID;
    const edited = !!S.edits[id];
    const A = isDemo ? ANALYSIS : genericAnalysis(c);
    const approved = ['approved', 'submitted', 'closed'].includes(c.status);
    const ver = UI.draftVer && (UI.draftVer === 1 || edited || approved) ? UI.draftVer : edited || approved ? 2 : 1;
    const text = isDemo ? (ver === 2 ? DRAFT_V2 : DRAFT_V1) : genericDraft(c, ver);
    const docs = isDemo ? CASE_DOCS : CASE_DOCS.slice(0, c.docs || 3);
    const checks = AI_CHECKS.map((x) => ({ ...x, ok: x.ok || edited || approved, t: !x.ok && (edited || approved) ? x.t.replace(/^.*?:\s*/, 'عولجت: ') : x.t }));
    const content = `
      <div class="review">
        <div class="pane sticky"><div class="pane-h">${icon('folder')} ملف القضية ${c.risk ? riskBadge(c.risk) : ''}</div><div class="pane-b stack">
          <div class="row"><div class="avatar sm">${(c.ownerName || ME.name).replace('د. ', '').slice(0, 2)}</div><div><div class="bold small">${esc(c.ownerName || ME.name)}</div><div class="tiny muted">طبيب أسنان · ترخيص موثّق</div></div></div>
          <div class="row" style="gap:12px">${gauge(c.score || A.score)}<div class="small muted">تقييم الذكاء الاصطناعي لقوة الموقف — للاسترشاد فقط</div></div>
          <div class="stack sm small">${[['الجهة', c.authority], ['رقم الشكوى', c.complaintNo], ['الإجراء', c.procedure], ['تاريخ الإجراء', fmtDate(c.procedureDate)], ['رمز المريض', c.patientCode], ['المرحلة', STAGES[c.stage]]].map(([l, v]) => `<div><span class="muted">${l}: </span><b>${esc(v)}</b></div>`).join('')}</div>
          ${c.hearing ? `<div class="alert amber"><span class="ic">${icon('calendar')}</span><div><b>${c.hearing.label}</b><br>${fmtDate(c.hearing.date)} · ${c.hearing.time}</div></div>` : ''}
          <div class="tiny muted bold">المستندات (${docs.length})</div>
          ${docs.map((d) => `<div class="doc-tile" data-act="view-doc" data-name="${esc(d.name)}"><span class="ic ${d.type === 'img' ? 'img' : ''}">${icon(d.type === 'img' ? 'image' : 'file')}</span><div class="grow"><div class="t">${d.name}</div><div class="s">${d.size} · مشفّر</div></div>${icon('eye', 'sm')}</div>`).join('')}
        </div></div>

        <div class="pane"><div class="pane-h"><div class="seg"><button class="${ver === 1 ? 'on' : ''}" data-act="ver" data-v="1">${icon('sparkles', 'sm')}<span>الإصدار 1 · AI</span></button>${edited || approved ? `<button class="${ver === 2 ? 'on' : ''}" data-act="ver" data-v="2">${icon('scale', 'sm')}<span>الإصدار 2 · تعديلاتي</span></button>` : ''}</div>
          <div class="row">${!approved ? `<button class="btn btn-sm ${UI.editMode ? 'btn-dark' : 'btn-secondary'}" data-act="edit-mode">${icon('pen', 'sm')} ${UI.editMode ? 'إنهاء التحرير' : 'تحرير مباشر'}</button>` : ''}${!edited && !approved && isDemo ? `<button class="btn btn-primary btn-sm" data-act="apply-edits" data-tour="apply-edits">${icon('pen', 'sm')} تطبيق تعديلاتي (عرض)</button>` : ''}</div></div>
          <div class="pane-b" style="background:var(--bg)">
            ${approved ? `<div class="seal" style="margin-bottom:12px"><span class="ring">${icon('stamp', 'lg')}</span><div><div class="bold">تم الاعتماد والتوقيع</div><div class="tiny">${LAWYERS[0].name} · ${fmtDT(c.approvedAt || DEMO_TODAY + 'T10:00')} · رمز التحقق <span class="ltr">DL-VRF-7Q4K-2291</span></div></div></div>` : ''}
            ${ver === 2 && isDemo ? '<div class="legend" style="margin-bottom:10px"><span><i style="background:#bfe8cd"></i>إضافة</span><span><i style="background:#f6c9c9"></i>حذف</span><span><i style="background:var(--teal-100)"></i>استشهاد</span><span><i style="background:#d6e2fb"></i>مستند</span></div>' : ''}
            <div class="paper wide" ${UI.editMode && !approved ? 'contenteditable="true"' : ''}>${renderDraft(text)}</div></div>
          <div class="pane-b row wrap" style="border-top:1px solid var(--line);justify-content:space-between">
            ${approved ? `<span class="muted small">${icon('lock', 'sm')} الإصدار المعتمد مقفل ولا يمكن تعديله — أي تعديل لاحق يُنشئ إصدارًا جديدًا يتطلب اعتمادًا.</span><button class="btn btn-secondary btn-sm" data-act="download">${icon('download')} PDF الموقّع</button>`
              : `<div class="row"><button class="btn btn-secondary" data-act="request-info">${icon('chats')} طلب معلومات من الطبيب</button><button class="btn btn-secondary" data-act="save-version">${icon('copy')} حفظ كإصدار</button></div><button class="btn btn-gold btn-lg" data-act="approve" data-tour="approve">${icon('stamp')} اعتماد الرد وتوقيعه</button>`}
          </div></div>

        <div class="stack sticky"><div class="pane"><div class="pane-h">${icon('shield')} فحوصات الجودة الآلية</div><div class="pane-b" style="padding-top:4px;padding-bottom:4px">${checks.map((x) => `<div class="check-row"><span class="${x.ok ? 'ok' : 'warn'}">${icon(x.ok ? 'checkCircle' : 'alert', 'sm')}</span><span>${x.t}</span></div>`).join('')}</div></div>
          <div class="pane"><div class="pane-h">${icon('database')} المصادر المسترجعة <span class="badge b-gray">${A.sources.length}</span></div><div class="pane-b stack sm">${A.sources.slice(0, 5).map(([k, s]) => `<div class="src-card" data-act="source" data-key="${k}" style="cursor:pointer"><div class="h"><span>${citeLabel(k)} — ${SOURCES[k].title}</span><span class="score num">${Math.round(s * 100)}%</span></div><div class="tiny muted">${SOURCES[k].text.slice(0, 92)}…</div></div>`).join('')}</div></div>
          <div class="pane"><div class="pane-h">${icon('chats')} ملاحظات داخلية</div><div class="pane-b stack sm"><div class="comment"><b>أ. سارة:</b> التحقق من وجود ملاحظة زيارة تثبت تعليمات ما بعد العملية قبل الاعتماد.</div><textarea class="textarea" style="min-height:60px" placeholder="أضف ملاحظة (لا تظهر للطبيب)…"></textarea></div></div>
        </div>
      </div>`;
    return webShell('lawyer', 'queue', esc(c.title), `قائمة المراجعة / <span class="ltr">${c.id}</span>`, content,
      `${statusBadge(c.status)}${c.status === 'lawyer_review' ? slaBadge(c) : ''}<button class="btn btn-secondary btn-sm" data-go="#/lawyer/queue">${icon('chevR', 'sm')} القائمة</button>`);
  },

  consults() {
    return webShell('lawyer', 'consults', 'الاستشارات المحوّلة', 'بوابة المحامي', `
      <div class="card pad-lg"><div class="table-wrap"><table class="table"><thead><tr><th>السؤال</th><th>الطبيب</th><th>الوقت</th><th>الحالة</th><th></th></tr></thead><tbody>
      ${S.consults.map((q) => `<tr class="click" data-act="answer-consult" data-id="${q.id}"><td><b>${esc(q.q)}</b></td><td>${esc(q.by)}</td><td class="small">${fmtDT(q.at)}</td><td><span class="badge ${q.status === 'جديدة' ? 'b-amber' : 'b-green'}">${q.status}</span></td><td><button class="btn btn-secondary btn-sm">${q.status === 'جديدة' ? 'الرد' : 'عرض'}</button></td></tr>`).join('')}
      </tbody></table></div></div>
      <div class="alert teal"><span class="ic">${icon('sparkles')}</span><div>يقترح النظام مسودة إجابة مع المصادر لكل استشارة، ويعدّلها المحامي ويعتمدها قبل وصولها للطبيب.</div></div>`);
  },

  library() {
    return webShell('lawyer', 'library', 'المصادر النظامية', 'بوابة المحامي', `
      <div class="cols eq">${Object.entries(SOURCES).map(([k, s]) => `<div class="card click" data-act="source" data-key="${k}"><div class="row between"><span class="badge b-teal">${citeLabel(k)}</span><span class="tiny muted">${s.law}</span></div><div class="bold" style="margin:8px 0 4px">${s.title}</div><div class="small muted">${s.text.slice(0, 140)}…</div></div>`).join('')}</div>`);
  },
};
