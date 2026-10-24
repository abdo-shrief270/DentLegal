/* Guided tour: simulates the full case cycle from registration to lawyer approval */
const TOUR = [
  { who: 'الطبيب · تطبيق الجوال', t: 'مرحبًا بك في جولة دورة القضية', p: 'سنحاكي شكوى حقيقية الشكل ضد طبيب أسنان: من تسجيلها في التطبيق، مرورًا بالتحليل الذكي، وحتى اعتماد المحامي للرد. كل البيانات تجريبية.', go: '#/dentist/home', target: '[data-tour=fab]' },
  { who: 'الطبيب · تطبيق الجوال', t: '1. تسجيل قضية جديدة', p: 'معالج من 4 خطوات. البيانات معبأة مسبقًا — اضغط «التالي» للتنقل، أو استخدم زر الجولة.', go: '#/dentist/new', prep: () => { if (!caseById(DEMO_CASE_ID)) { S.wizard = freshWizard(); } }, target: '[data-tour=wiz-next]' },
  { who: 'الطبيب · تطبيق الجوال', t: '2. رفع المستندات واستخراج البيانات', p: 'اضغط «رفع الكل» أو أي مستند. يُشفَّر كل ملف فورًا، ويستخرج النظام بيانات خطاب الشكوى تلقائيًا (OCR) ويضيف موعد الإفادة إلى التنبيهات.', go: '#/dentist/new', prep: () => { if (!caseById(DEMO_CASE_ID)) S.wizard.step = 3; }, target: '[data-tour=upload-all]' },
  { who: 'النظام · محرك الذكاء الاصطناعي', t: '3. التحليل الذكي (RAG)', p: 'بعد الإرسال: OCR، إخفاء هوية المريض، استرجاع المواد النظامية ذات الصلة، تحليل نقاط القوة والضعف، ثم صياغة مسودة الإفادة مع الاستشهادات.', go: () => `#/dentist/processing/${ensureCaseCreated().id}` },
  { who: 'الطبيب · تطبيق الجوال', t: '4. نتيجة التحليل', p: 'مؤشر قوة الموقف، المخاطر، والإجراءات العاجلة — وكل نقطة مربوطة بمادة نظامية. اضغط أي استشهاد لعرض نص المادة.', go: () => `#/dentist/case/${ensureAnalyzed().id}/analysis`, target: '[data-tour=view-draft]' },
  { who: 'الطبيب · تطبيق الجوال', t: '5. مسودة الرد مع المصادر', p: 'إفادة رسمية بصياغة قانونية. الاستشهادات الخضراء مواد نظامية، والزرقاء مستندات من القضية. الخطوة التالية: الإرسال للمراجعة القانونية.', go: () => `#/dentist/case/${ensureAnalyzed().id}/draft`, target: '[data-tour=send-review]' },
  { who: 'المحامي · بوابة الويب', t: '6. استلام المحامي للطلب', p: 'أُسند الطلب تلقائيًا لمحامية متخصصة حسب التخصص والحِمل، وظهر في قائمة المراجعة مع المهلة المتبقية (SLA).', go: () => { ensureSent(); return '#/lawyer/queue'; } },
  { who: 'المحامي · بوابة الويب', t: '7. المراجعة والتعديل', p: 'ملف القضية والمستندات يمينًا، والمسودة في الوسط، وفحوصات الجودة والمصادر يسارًا. اضغط «تطبيق تعديلاتي» لمحاكاة تعديلات المحامي (أو حرّر النص مباشرة).', go: () => `#/lawyer/review/${ensureSent().id}`, target: '[data-tour=apply-edits]' },
  { who: 'المحامي · بوابة الويب', t: '8. اعتماد الرد وتوقيعه', p: 'الإصدار 2 يُظهر تعديلات المحامي (إضافات وحذف). بعد التحقق من المصادر يعتمد المحامي الرد ويوقّعه، فيُقفل الإصدار.', go: () => `#/lawyer/review/${ensureEdited().id}`, prep: () => { UI.draftVer = 2; }, target: '[data-tour=approve]' },
  { who: 'الطبيب · تطبيق الجوال', t: '9. استلام الرد المعتمد', p: 'وصل إشعار للطبيب. الرد مختوم باسم المحامي ورخصته ورمز تحقق، مع إمكانية المقارنة بين الإصدارين وتنزيل PDF ثم تأكيد التقديم للجهة.', go: () => `#/dentist/case/${ensureApproved().id}/draft`, prep: () => { UI.draftVer = 2; }, target: '[data-tour=mark-submitted]' },
  { who: 'الإدارة · لوحة التحكم', t: '10. الرقابة وسجل التدقيق', p: 'كل خطوة في الدورة موثّقة: من رفع المستندات، إلى إنشاء المسودة، إلى اعتماد المحامي — مع الوقت والدور وعنوان IP.', go: () => { ensureApproved(); return '#/admin/audit'; } },
];

function tourGo(i) {
  if (i < 0 || i >= TOUR.length) return;
  S.tour.step = i; S.tour.on = true; S.tour.min = false;
  const st = TOUR[i];
  clearTimeout(UI.procTimer); UI.procTimer = null;
  UI.draftVer = null; UI.editMode = false;
  st.prep && st.prep();
  const href = typeof st.go === 'function' ? st.go() : st.go;
  save();
  if (location.hash === href) render(); else location.hash = href;
}
function tourHtml() {
  if (!S.tour.on) return '';
  const i = S.tour.step, st = TOUR[i];
  const last = i === TOUR.length - 1;
  return `<div class="tour ${S.tour.min ? 'min' : ''}"><div class="tour-h">${icon('play', 'sm')}<span class="ttl">جولة دورة القضية · ${i === 0 ? 'مقدمة' : `${i} من ${TOUR.length - 1}`}</span>
      <button class="icon-btn" data-act="tour-min" aria-label="تصغير">${icon(S.tour.min ? 'chevD' : 'chevD')}</button><button class="icon-btn" data-act="tour-end" aria-label="إغلاق">${icon('x')}</button></div>
    <div class="tour-b"><div class="who">${icon('user', 'sm')}${st.who}</div><h4>${st.t}</h4><p>${st.p}</p><div class="tour-prog">${TOUR.slice(1).map((_, k) => `<i class="${k < i ? 'on' : ''}"></i>`).join('')}</div></div>
    <div class="tour-f">${i > 0 ? `<button class="btn btn-secondary btn-sm" data-act="tour-prev">${icon('chevR', 'sm')} السابق</button>` : ''}
      ${last ? `<button class="btn btn-primary btn-sm grow" data-act="tour-end">إنهاء الجولة والتجربة بحرية</button>` : `<button class="btn btn-primary btn-sm grow" data-act="tour-next">الخطوة التالية ${icon('chevL', 'sm')}</button>`}</div></div>`;
}
function tourHighlight() {
  document.querySelectorAll('.pulse-target').forEach((e) => e.classList.remove('pulse-target'));
  if (!S.tour.on || S.tour.min) return;
  const sel = TOUR[S.tour.step]?.target;
  const el = sel && document.querySelector(sel);
  if (el) el.classList.add('pulse-target');
}
