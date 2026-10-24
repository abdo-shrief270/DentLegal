/* State store + UI helpers */
const STORE_KEY = 'dentlegal-demo-v1';
const DEMO_CASE_ID = CASE_TEMPLATE.id;
const STATUS_ORDER = ['draft', 'analyzing', 'ai_ready', 'lawyer_review', 'needs_info', 'approved', 'submitted', 'closed'];

function freshState() {
  return {
    cases: seedCases(),
    notifications: seedNotifications(),
    audit: seedAudit(),
    wizard: freshWizard(),
    chat: [],
    consults: [
      { id: 'c1', q: 'هل يلزمني تسليم نسخة أصلية من الملف الطبي للجنة؟', by: 'د. هند العنزي', at: '2026-10-23T14:20', status: 'جديدة' },
      { id: 'c2', q: 'المريض يرفض توقيع إقرار الموافقة ويطلب العلاج، ما الإجراء؟', by: 'د. ماجد السبيعي', at: '2026-10-22T19:05', status: 'جديدة' },
    ],
    edits: {},
    verified: {},
    kbExtra: [],
    tour: { on: false, step: 0, min: false },
  };
}
function freshWizard() {
  return { step: 1, kind: 'complaint', uploaded: {}, ocr: false, agree1: true, agree2: true, ...JSON.parse(JSON.stringify(CASE_TEMPLATE)) };
}

let S = freshState();
try {
  const raw = localStorage.getItem(STORE_KEY);
  if (raw) S = Object.assign(freshState(), JSON.parse(raw));
} catch (e) { /* storage unavailable — keep in-memory state */ }

function save() { try { localStorage.setItem(STORE_KEY, JSON.stringify(S)); } catch (e) {} }
function resetDemo() { S = freshState(); save(); }

const caseById = (id) => S.cases.find((c) => c.id === id);
const myCases = () => S.cases.filter((c) => c.owner === ME.id).sort((a, b) => (b.updatedAt || '').localeCompare(a.updatedAt || ''));
const lawyerById = (id) => LAWYERS.find((l) => l.id === id);
const statusIdx = (s) => STATUS_ORDER.indexOf(s);

function nowStamp() {
  const d = new Date();
  return `${DEMO_TODAY}T${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}
function addAudit(actor, role, act, target) {
  const ip = role === 'نظام' ? '—' : role === 'إدارة' ? '10.0.4.21' : role === 'محامٍ' ? '94.97.***.***' : '188.48.***.***';
  S.audit.unshift({ at: nowStamp(), actor, role, act, target, ip });
}
function addNotif(n) { S.notifications.unshift({ id: 'n' + Date.now() + Math.random(), at: nowStamp(), read: false, ...n }); }
function addEvent(c, text, cls = 'teal') { (c.events = c.events || []).push([DEMO_TODAY, text, cls]); c.updatedAt = DEMO_TODAY; }

/* ---------- demo-case lifecycle (used by the wizard and the guided tour) ---------- */
function ensureCaseCreated() {
  let c = caseById(DEMO_CASE_ID);
  if (c) return c;
  const w = S.wizard;
  c = {
    ...JSON.parse(JSON.stringify(CASE_TEMPLATE)),
    title: w.title || CASE_TEMPLATE.title, summary: w.summary || CASE_TEMPLATE.summary, procedure: w.procedure || CASE_TEMPLATE.procedure,
    owner: ME.id, status: 'analyzing', createdAt: DEMO_TODAY, updatedAt: DEMO_TODAY, docs: CASE_DOCS.length, versions: 0, events: [],
  };
  S.cases.unshift(c);
  addEvent(c, `إنشاء القضية ورفع ${CASE_DOCS.length} مستندات (مشفّرة)`);
  addAudit(ME.name, 'طبيب', 'إنشاء قضية ورفع 6 مستندات', DEMO_CASE_ID);
  S.wizard = freshWizard();
  save();
  return c;
}
function resetDemoCase() {
  S.cases = S.cases.filter((c) => c.id !== DEMO_CASE_ID);
  S.notifications = S.notifications.filter((n) => !(n.go || '').includes(DEMO_CASE_ID));
  S.audit = S.audit.filter((a) => a.target !== DEMO_CASE_ID && a.target !== 'أ. سارة العتيبي');
  delete S.edits[DEMO_CASE_ID];
  S.wizard = freshWizard();
  save();
}
function ensureAnalyzed() {
  const c = ensureCaseCreated();
  if (statusIdx(c.status) < statusIdx('ai_ready')) {
    c.status = 'ai_ready'; c.score = ANALYSIS.score; c.risk = ANALYSIS.risk; c.versions = 1; c.stage = 1;
    addEvent(c, 'اكتمال التحليل الذكي وإنشاء مسودة الرد — الإصدار 1', 'ai');
    addAudit('النظام (AI)', 'نظام', 'تحليل القضية وإنشاء مسودة — الإصدار 1 (7 مصادر)', DEMO_CASE_ID);
    addNotif({ icon: 'sparkles', cls: 'violet', t: 'اكتمل التحليل الذكي لقضيتك', s: 'قوة الموقف 72% — مسودة الرد جاهزة للمراجعة', go: `#/dentist/case/${DEMO_CASE_ID}/analysis` });
    save();
  }
  return c;
}
function ensureSent() {
  const c = ensureAnalyzed();
  if (statusIdx(c.status) < statusIdx('lawyer_review')) {
    c.status = 'lawyer_review'; c.lawyer = 'L-07'; c.sentAt = nowStamp(); c.slaHours = 24; c.ownerName = ME.name;
    addEvent(c, 'إرسال المسودة للمراجعة القانونية — أ. سارة العتيبي');
    addAudit(ME.name, 'طبيب', 'إرسال للمراجعة القانونية', DEMO_CASE_ID);
    addAudit('النظام', 'نظام', 'إسناد تلقائي للمحامي حسب التخصص والحِمل', 'أ. سارة العتيبي');
    save();
  }
  return c;
}
function ensureEdited() {
  const c = ensureSent();
  if (!S.edits[c.id]) {
    S.edits[c.id] = true; c.versions = 2;
    addAudit('أ. سارة العتيبي', 'محامٍ', 'تعديل المسودة — الإصدار 2 (5 تعديلات)', c.id);
    save();
  }
  return c;
}
function ensureApproved() {
  const c = ensureEdited();
  if (statusIdx(c.status) < statusIdx('approved')) approveCase(c.id);
  return c;
}
function approveCase(id) {
  const c = caseById(id);
  if (!c) return;
  if (!S.edits[id] && id === DEMO_CASE_ID) { S.edits[id] = true; }
  c.status = 'approved'; c.approvedAt = nowStamp(); c.versions = Math.max(c.versions || 1, 2); c.lawyer = c.lawyer || 'L-07';
  addEvent(c, 'اعتماد الرد من أ. سارة العتيبي — الإصدار 2 (موقّع)', 'gold');
  addAudit('أ. سارة العتيبي', 'محامٍ', 'اعتماد الرد وتوقيعه — الإصدار 2', id);
  if (c.owner === ME.id) addNotif({ icon: 'badge', cls: 'gold', t: 'تم اعتماد الرد على قضيتك', s: `${id} — اعتمدته أ. سارة العتيبي، جاهز للتنزيل`, go: `#/dentist/case/${id}/draft` });
  save();
}

/* ---------- formatting ---------- */
function esc(s) { return String(s ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }
function toDate(iso) { const [y, m, d] = iso.slice(0, 10).split('-').map(Number); return new Date(y, m - 1, d, 12); }
function fmtDate(iso, withYear = true) {
  if (!iso || iso === '—') return '—';
  try { return new Intl.DateTimeFormat('ar-SA-u-ca-gregory-nu-latn', withYear ? { day: 'numeric', month: 'long', year: 'numeric' } : { day: 'numeric', month: 'long' }).format(toDate(iso)); }
  catch (e) { return iso.slice(0, 10); }
}
function fmtHijri(iso) {
  try { return new Intl.DateTimeFormat('ar-SA-u-ca-islamic-umalqura-nu-latn', { day: 'numeric', month: 'long', year: 'numeric' }).format(toDate(iso)); }
  catch (e) { return ''; }
}
function fmtTime(iso) { return iso && iso.length > 10 ? iso.slice(11, 16) : ''; }
function fmtDT(iso) { return `${fmtDate(iso, false)} · <span class="ltr">${fmtTime(iso)}</span>`; }
function daysUntil(iso) { return Math.round((toDate(iso) - toDate(DEMO_TODAY)) / 86400000); }
function relDays(iso) {
  const n = daysUntil(iso);
  if (n === 0) return 'اليوم';
  if (n === 1) return 'غدًا';
  if (n === -1) return 'أمس';
  if (n > 0) return n <= 10 ? `بعد ${n} أيام` : `بعد ${n} يومًا`;
  return Math.abs(n) <= 10 ? `منذ ${Math.abs(n)} أيام` : `منذ ${Math.abs(n)} يومًا`;
}
function slaLeft(c) {
  if (!c.sentAt) return null;
  const sent = new Date(c.sentAt.replace('T', ' ').replace(/-/g, '/'));
  const now = new Date(`${DEMO_TODAY.replace(/-/g, '/')} ${new Date().toTimeString().slice(0, 5)}`);
  const left = (c.slaHours || 24) - (now - sent) / 3600000;
  return Math.round(Math.min(c.slaHours || 24, left));
}

/* ---------- UI atoms ---------- */
function statusBadge(s) { const x = STATUS[s] || STATUS.draft; return `<span class="badge ${x.cls}">${icon(x.icon)}${x.label}</span>`; }
function riskBadge(r) { const cls = r === 'مرتفع' ? 'b-red' : r === 'متوسط' ? 'b-amber' : 'b-green'; return `<span class="badge ${cls}">مخاطر ${r}</span>`; }
function citeLabel(k) { if (k.startsWith('ل')) return 'لائحة ' + k.slice(1); if (k.startsWith('ط')) return 'لائحة الأسنان'; return 'م ' + k.slice(1); }
function cite(k) { return `<span class="cite" data-act="source" data-key="${k}" title="${esc(SOURCES[k]?.title || '')}">${icon('book', 'sm')}${citeLabel(k)}</span>`; }
function docRef(n) { return `<span class="cite doc" data-act="docref" data-name="${esc(n)}">${icon('clip', 'sm')}${esc(n)}</span>`; }
function renderDraft(html) { return html.replace(/\{\{c:([^}]+)\}\}/g, (_, k) => cite(k)).replace(/\{\{d:([^}]+)\}\}/g, (_, n) => docRef(n)); }

function stepper(stage, cls = '', dates = []) {
  return `<div class="stepper ${cls}">${STAGES.map((s, i) => {
    const st = i < stage ? 'done' : i === stage ? 'cur' : '';
    return `<div class="st ${st}"><div class="dot">${i < stage ? icon('check', 'sm') : i + 1}</div><div class="lbl">${s}</div>${dates[i] ? `<div class="dt">${dates[i]}</div>` : ''}</div>`;
  }).join('')}</div>`;
}
function timeline(events) {
  return `<div class="timeline">${(events || []).slice().reverse().map(([d, t, cls]) => `<div class="tl-item ${cls === 'teal' ? '' : cls}"><div class="t">${esc(t)}</div><div class="s">${fmtDate(d)}</div></div>`).join('')}</div>`;
}
function gauge(v, label = 'قوة الموقف') {
  const c = v >= 75 ? 'var(--teal-500)' : v >= 55 ? '#e0a21a' : 'var(--red-600)';
  return `<div class="gauge" style="--v:${v};--c:${c}"><div><b>${v}%</b><span>${label}</span></div></div>`;
}

/* ---------- overlays ---------- */
function overlayHost() { return document.querySelector('.phone .screen') || document.body; }
function openModal(title, body, foot = '', opts = {}) {
  closeModal();
  const host = opts.web ? document.body : overlayHost();
  const el = document.createElement('div');
  el.className = 'backdrop'; el.id = 'modal';
  el.innerHTML = `<div class="modal" role="dialog" aria-modal="true"><div class="modal-head"><h3>${title}</h3><button class="icon-btn flat" data-act="close-modal" aria-label="إغلاق">${icon('x')}</button></div><div class="modal-body">${body}</div>${foot ? `<div class="modal-foot">${foot}</div>` : ''}</div>`;
  el.addEventListener('click', (e) => { if (e.target === el) closeModal(); });
  host.appendChild(el);
}
function closeModal() { document.getElementById('modal')?.remove(); }
function toast(msg, ic = 'checkCircle') {
  let w = document.querySelector('.toast-wrap');
  if (!w) { w = document.createElement('div'); w.className = 'toast-wrap'; document.body.appendChild(w); }
  const t = document.createElement('div');
  t.className = 'toast'; t.innerHTML = `${icon(ic)}<span>${msg}</span>`;
  w.appendChild(t);
  setTimeout(() => t.remove(), 2800);
}
function showSource(k) {
  const s = SOURCES[k];
  if (!s) return;
  openModal(`${icon('book')} ${citeLabel(k)} — ${esc(s.title)}`,
    `<div class="stack"><div class="row wrap"><span class="badge b-teal">${esc(s.law)}</span><span class="badge b-gray">${esc(s.ref)}</span></div>
     <div class="src-card on">${esc(s.text)}</div>
     <div class="alert blue"><span class="ic">${icon('info')}</span><div>المقطع مسترجع من قاعدة المعرفة المفهرسة (RAG) ومرتبط بنسخته السارية. في النسخة الفعلية يُعرض النص الكامل مع رقم الصفحة في المصدر الأصلي.</div></div></div>`,
    `<button class="btn btn-primary btn-sm" data-act="close-modal">تم</button>`, { web: !document.querySelector('.phone') });
}
