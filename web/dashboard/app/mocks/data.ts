// Synthetic demo data for the fake API. Fictional people and cases only — no real patient data.
// Cases, lawyers and regulation excerpts are taken from the approved prototype (site/prototype/js/data.js).
import type {
  CaseDocumentRef, CitationSample, DentistRow, DraftContent, DraftVersion, FeedbackRow, InternalNote,
  KnowledgeChunk, KnowledgeSource, LawyerRow, QualityCheck, RatingRow, RetrievedSource, ReviewDetail, SessionUser,
} from '~/types/api'

export const DEMO_NOW = '2026-12-20T09:30:00+03:00'

export const DEMO_USERS: SessionUser[] = [
  { id: 'L-07', name: 'أ. سارة العتيبي', email: 'sara@dentlegal.test', role: 'lawyer', initials: 'سع', subtitle: 'محامية مراجِعة · ترخيص 44/1203' },
  { id: 'A-01', name: 'م. نورة الشهري', email: 'noura@dentlegal.test', role: 'admin', initials: 'نش', subtitle: 'مديرة المنصة' },
]

export const DEMO_2FA_CODE = '123456'

export const DEMO_INVITES: Record<string, { name: string; email: string; role: 'lawyer' | 'admin' }> = {
  'demo-lawyer': { name: 'أ. عبدالله الدوسري', email: 'abdullah@dentlegal.test', role: 'lawyer' },
}

// ---------- regulation sources (retrieved for case DL-2026-0152) ----------

const HPPL = 'نظام مزاولة المهن الصحية'
const HPPL_REG = 'اللائحة التنفيذية لنظام مزاولة المهن الصحية'

export const SOURCES_0152: RetrievedSource[] = [
  { ref: 'S1', chunkId: 412, law: HPPL, label: 'المادة 27', title: 'الخطأ المهني الصحي والتعويض', score: 0.95,
    text: 'كل خطأ مهني صحي صدر من الممارس الصحي، وترتب عليه ضرر للمريض؛ يلتزم من ارتكبه بالتعويض… ويعد من قبيل الخطأ: (1) الخطأ في العلاج أو نقص المتابعة، (2) الجهل بأمور فنية يفترض الإلمام بها… (8) عدم استشارة من تستدعي حالة المريض الاستعانة به.' },
  { ref: 'S2', chunkId: 377, law: HPPL, label: 'المادة 19', title: 'رضا المريض قبل العمل الطبي', score: 0.92,
    text: 'يجب ألا يجرى أي عمل طبي لمريض إلا برضاه، أو بموافقة من يمثله أو ولي أمره إذا لم يعتد بإرادته هو… وتؤخذ موافقة المريض البالغ العاقل أو من يمثله قبل القيام بالعمل الطبي أو الجراحي.' },
  { ref: 'S3', chunkId: 455, law: HPPL_REG, label: 'اللائحة 40/1', title: 'إجراءات التحقيق قبل الإحالة', score: 0.88,
    text: 'تُؤخذ إفادات الممارسين الصحيين المعالجين للحالة على النموذج المخصص، ويُبلّغ المدعى عليه بموعد ومكان الإفادة قبلها بوقت كافٍ، ويُرفع التقرير المتعلق بالمطالبة بالحق الخاص إلى الهيئة الصحية الشرعية، وفيما عدا ذلك يجوز التوصية بالحفظ عند عدم كفاية الأدلة.' },
  { ref: 'S4', chunkId: 381, law: HPPL, label: 'المادة 21', title: 'سرية معلومات المريض وحالات الإفصاح', score: 0.81,
    text: 'يجب على الممارس الصحي أن يحافظ على الأسرار التي علم بها عن طريق مهنته، ولا يجوز له إفشاؤها إلا في أحوال منها: دفع الممارس لاتهام وجهه إليه المريض أو ذووه يتعلق بكفايته أو بكيفية ممارسته المهنة.' },
  { ref: 'S5', chunkId: 470, law: HPPL, label: 'المادة 41', title: 'التأمين الإلزامي ضد الأخطاء المهنية', score: 0.79,
    text: 'يكون الاشتراك في التأمين التعاوني ضد الأخطاء المهنية الطبية إلزاميًا على جميع الأطباء وأطباء الأسنان… وتشمل التغطية التعويضات الناشئة عن الأخطاء الواقعة أثناء فترة التغطية.' },
  { ref: 'S6', chunkId: 458, law: HPPL_REG, label: 'اللائحة 40/2', title: 'الاعتراض على تأجيل السفر', score: 0.77,
    text: 'للممارس الصحي المؤجل سفره حق الاعتراض مرفقًا: (أ) وثيقة تأمين سارية وقت الإجراء محل الشكوى، (ب) إثبات إبلاغ شركة التأمين بالشكوى المرفوعة ضده، (ت) كفالة غرم وأداء.' },
  { ref: 'S7', chunkId: 431, law: HPPL, label: 'المادة 34', title: 'اختصاص الهيئة الصحية الشرعية', score: 0.74,
    text: 'تختص الهيئة الصحية الشرعية بالنظر في الأخطاء المهنية الصحية التي ترفع بها مطالبة بالحق الخاص (دية – تعويض – أرش)، والأخطاء التي ينتج عنها وفاة أو تلف عضو أو فقد منفعته.' },
]

export const DOCUMENTS_0152: CaseDocumentRef[] = [
  { ref: 'D1', documentId: 'doc-1', name: 'خطاب الإبلاغ بالشكوى', kind: 'complaint_letter', pages: 2, sizeLabel: '412 ك.ب', lowQuality: false,
    maskedText: 'المديرية العامة للشؤون الصحية بمحافظة جدة — الشؤون القانونية\nالرقم: 02871/1448 · التاريخ: 15/10/2026م\n\nالمكرم الدكتور/ {{DENTIST_NAME}}\nنحيطكم علمًا بورود شكوى من المريض [PATIENT] (هوية [ID_1]، جوال [PHONE_1]) يدّعي فيها استمرار تنميل في الشفة السفلية والذقن (الجهة اليسرى) بعد إجراء زراعة سن، ويطالب بالتعويض عن الضرر وتكاليف العلاج.\nعليه نأمل حضوركم لأخذ إفادتكم يوم الخميس 29/10/2026م الساعة 10:00 صباحًا.' },
  { ref: 'D2', documentId: 'doc-2', name: 'الملف الطبي وملاحظات المتابعة', kind: 'medical_record', pages: 6, sizeLabel: '1.8 م.ب', lowQuality: false,
    maskedText: 'اسم المريض: [PATIENT] · رقم الملف: [MRN_1]\n21/08/2026: فحص وتخطيط، أشعة مقطعية، شرح المخاطر وتوقيع الإقرار.\n28/08/2026: زراعة في موضع (36) دون مضاعفات أثناء العملية.\n30/08/2026: بلاغ بتنميل في الشفة السفلية؛ فحص خلال 24 ساعة؛ إحالة لاستشاري الوجه والفكين.\n06/09/2026: متابعة، تحسن جزئي في الإحساس.' },
  { ref: 'D3', documentId: 'doc-3', name: 'إقرار الموافقة المستنيرة', kind: 'consent_form', pages: 1, sizeLabel: '640 ك.ب', lowQuality: false,
    maskedText: 'أقر أنا [PATIENT] بأنه شُرحت لي طبيعة إجراء زراعة الأسنان ومخاطره المحتملة، ومنها: الألم والتورم، والنزف، واحتمال التنميل المؤقت أو الدائم في الشفة أو اللسان، وأوافق على إجرائه.\nالتوقيع: [SIGNATURE] · التاريخ: 21/08/2026م' },
  { ref: 'D4', documentId: 'doc-4', name: 'تقرير الأشعة المقطعية (CBCT)', kind: 'radiology', pages: 1, sizeLabel: '2.4 م.ب', lowQuality: true,
    maskedText: 'CBCT Report — Patient: [PATIENT]\nSite 36: bone height adequate. Safety distance to the inferior alveolar canal: 4.2 mm.\n(جودة المسح منخفضة في الجزء السفلي من الصفحة)' },
  { ref: 'D5', documentId: 'doc-5', name: 'خطاب الإحالة لاستشاري الوجه والفكين', kind: 'referral', pages: 1, sizeLabel: '210 ك.ب', lowQuality: false,
    maskedText: 'إحالة المريض [PATIENT] إلى استشاري جراحة الوجه والفكين لتقييم تنميل الشفة السفلية بعد زراعة (36). تاريخ الإحالة: 31/08/2026م.' },
]

function p(id: string, text: string, legalClaim: boolean, citations: DraftContent['intro']['citations'] = []) {
  return { id, text, legalClaim, citations }
}

export const DRAFT_0152_V1: DraftContent = {
  title: 'إفادة الممارس الصحي بشأن الشكوى رقم 02871/1448',
  addressee: 'سعادة مدير الشؤون القانونية بالمديرية العامة للشؤون الصحية بمحافظة جدة المحترم',
  salutation: 'السلام عليكم ورحمة الله وبركاته، وبعد:',
  intro: p('intro', 'أتقدم أنا الدكتور/ أحمد بن سعد الغامدي، أخصائي جراحة وزراعة الأسنان، المسجل لدى الهيئة السعودية للتخصصات الصحية برقم (21-RD-0045871)، بإفادتي بشأن الشكوى المقدمة من المريض (الرمز P-7731) بتاريخ 15/10/2026م، وأوضح ما يلي:', false),
  sections: [
    { kind: 'facts', heading: 'أولًا: الوقائع', paragraphs: [
      p('f1', 'راجع المريض العيادة لتقييم فقد الضرس (36)، وأُجريت له أشعة مقطعية قبل الإجراء أظهرت مسافة أمان قدرها 4.2 ملم بين موضع الزرعة والقناة السنية السفلية.', false,
        [{ ref: 'D4', quote: 'Safety distance to the inferior alveolar canal: 4.2 mm', verified: true }]),
      p('f2', 'شُرحت للمريض طبيعة الإجراء ومخاطره المحتملة، ومنها احتمال التنميل المؤقت أو الدائم، ووقّع على إقرار الموافقة المستنيرة بتاريخ 21/08/2026م، وذلك التزامًا بما تقضي به المادة التاسعة عشرة من النظام.', true,
        [{ ref: 'D3', quote: 'واحتمال التنميل المؤقت أو الدائم في الشفة أو اللسان', verified: true },
         { ref: 'S2', quote: 'يجب ألا يجرى أي عمل طبي لمريض إلا برضاه', verified: true }]),
      p('f3', 'أُجري الإجراء بتاريخ 28/08/2026م وفق الأصول المهنية المتبعة ودون مضاعفات أثناء العملية، وزُوّد المريض بتعليمات ما بعد العملية شفهيًا.', false,
        [{ ref: 'D2', quote: 'زراعة في موضع (36) دون مضاعفات أثناء العملية', verified: true }]),
      p('f4', 'عند إبلاغ المريض بالتنميل بتاريخ 30/08/2026م، فُحص خلال 24 ساعة وأُحيل إلى استشاري جراحة الوجه والفكين، بما يتفق مع واجب استشارة من تستدعي حالة المريض الاستعانة به.', true,
        [{ ref: 'D5', quote: 'إحالة المريض [PATIENT] إلى استشاري جراحة الوجه والفكين', verified: true },
         { ref: 'S1', quote: 'عدم استشارة من تستدعي حالة المريض الاستعانة به', verified: true }]),
    ] },
    { kind: 'defenses', heading: 'ثانيًا: الدفوع', paragraphs: [
      p('d1', 'انتفاء الخطأ المهني؛ إذ إن التنميل من المضاعفات المعروفة والموثقة في إقرار الموافقة، ولم يثبت خطأ في العلاج أو نقص في المتابعة.', true,
        [{ ref: 'S1', quote: 'الخطأ في العلاج أو نقص المتابعة', verified: true }]),
      p('d2', 'بُذلت العناية الواجبة في التخطيط المسبق بالأشعة المقطعية، بما ينفي الجهل بالأمور الفنية المفترض الإلمام بها.', true,
        [{ ref: 'S1', quote: 'الجهل بأمور فنية يفترض الإلمام بها', verified: true }]),
      p('d3', 'يأتي الإفصاح عن المعلومات الطبية الواردة في هذه الإفادة بالقدر اللازم للدفاع عن الممارس.', true,
        [{ ref: 'S4', quote: 'دفع الممارس لاتهام وجهه إليه المريض أو ذووه', verified: true }]),
      p('d4', 'يحق للممارس طلب الاستعانة بخبير فني إذا أُحيلت الشكوى إلى الهيئة الصحية الشرعية.', true,
        [{ ref: 'S3', quote: 'تستعين الهيئة بخبير أو أكثر لإبداء الرأي', verified: false, reason: 'quote_mismatch' }]),
    ] },
    { kind: 'requests', heading: 'ثالثًا: الطلبات', paragraphs: [
      p('r1', 'أطلب حفظ الشكوى لعدم ثبوت الخطأ المهني الصحي، مع استعدادي التام لتقديم أي مستندات إضافية.', true,
        [{ ref: 'S3', quote: 'يجوز التوصية بالحفظ عند عدم كفاية الأدلة', verified: true }]),
    ] },
  ],
  closing: 'وتقبلوا وافر التحية والتقدير،،،',
  attachments: ['إقرار الموافقة المستنيرة', 'تقرير الأشعة المقطعية', 'الملف الطبي وملاحظات المتابعة', 'خطاب الإحالة'],
}

export const CHECKS_0152: QualityCheck[] = [
  { ok: true, text: 'جميع الاستشهادات (11) تشير إلى مصادر ومستندات موجودة في هذا التشغيل' },
  { ok: true, text: 'لا توجد بيانات تعريفية للمريض في النص (استُخدم الرمز P-7731)' },
  { ok: true, text: 'هيكل المسودة مطابق: الوقائع، الدفوع، الطلبات' },
  { ok: false, text: 'الفقرة (ثانيًا/4): الاقتباس لا يطابق نص المصدر S3 — غير مسند' },
  { ok: false, text: 'الفقرة (أولًا/3): لا يوجد مستند مكتوب يدعم تعليمات ما بعد العملية' },
]

export const NOTES_0152: InternalNote[] = [
  { id: 'n1', author: 'أ. سارة العتيبي', createdAt: '2026-12-20T08:55:00+03:00', text: 'التحقق من وجود ملاحظة زيارة تثبت تعليمات ما بعد العملية قبل الاعتماد.' },
]

// ---------- review cases ----------

interface CaseSeed {
  draftId: string
  caseCode: string
  title: string
  caseType: ReviewDetail['caseType']
  claimType: ReviewDetail['claimType']
  authority: string
  complaintNo: string
  procedure: string
  procedureDate: string
  patientCode: string
  dentist: ReviewDetail['dentist']
  risk: ReviewDetail['risk']
  positionStrength: number
  submittedAt: string
  claimedBy: string | null
  status: 'in_review' | 'approved'
  approvedAt: string | null
}

const CASES: CaseSeed[] = [
  { draftId: 'dr-0152', caseCode: 'DL-2026-0152', title: 'تنميل في الشفة السفلية بعد زراعة سن', caseType: 'complaint', claimType: 'compensation',
    authority: 'المديرية العامة للشؤون الصحية بمحافظة جدة — الشؤون القانونية', complaintNo: '02871/1448', procedure: 'زراعة سن في موضع الضرس الأول السفلي الأيسر (36)',
    procedureDate: '2026-08-28', patientCode: 'P-7731', dentist: { name: 'د. أحمد بن سعد الغامدي', specialty: 'جراحة وزراعة الأسنان', city: 'جدة' },
    risk: 'medium', positionStrength: 72, submittedAt: '2026-12-20T07:40:00+03:00', claimedBy: 'L-07', status: 'in_review', approvedAt: null },
  { draftId: 'dr-0144', caseCode: 'DL-2026-0144', title: 'ادعاء تلف عصب بعد علاج جذور', caseType: 'complaint', claimType: 'compensation',
    authority: 'المديرية العامة للشؤون الصحية بالرياض', complaintNo: '02650/1448', procedure: 'علاج عصب للضرس (46)', procedureDate: '2026-07-21', patientCode: 'P-8120',
    dentist: { name: 'د. ريم الحربي', specialty: 'علاج الجذور', city: 'الرياض' }, risk: 'medium', positionStrength: 58, submittedAt: '2026-12-20T08:20:00+03:00',
    claimedBy: null, status: 'in_review', approvedAt: null },
  { draftId: 'dr-0157', caseCode: 'DL-2026-0157', title: 'كسر أداة علاج داخل القناة', caseType: 'official_notice', claimType: 'disciplinary',
    authority: 'لجنة النظر في مخالفات نظام مزاولة المهن الصحية', complaintNo: '02917/1448', procedure: 'علاج عصب للضرس (37)', procedureDate: '2026-09-14', patientCode: 'P-8433',
    dentist: { name: 'د. ماجد السبيعي', specialty: 'اللثة', city: 'الرياض' }, risk: 'low', positionStrength: 81, submittedAt: '2026-12-19T16:05:00+03:00',
    claimedBy: null, status: 'in_review', approvedAt: null },
  { draftId: 'dr-0139', caseCode: 'DL-2026-0139', title: 'خلع سن مجاور بالخطأ', caseType: 'complaint', claimType: 'compensation',
    authority: 'المديرية العامة للشؤون الصحية بالمنطقة الشرقية', complaintNo: '02511/1448', procedure: 'خلع ضرس (27)', procedureDate: '2026-09-02', patientCode: 'P-7402',
    dentist: { name: 'د. خالد المطيري', specialty: 'طب أسنان عام', city: 'الدمام' }, risk: 'high', positionStrength: 41, submittedAt: '2026-12-18T13:40:00+03:00',
    claimedBy: 'L-07', status: 'in_review', approvedAt: null },
  { draftId: 'dr-0127', caseCode: 'DL-2026-0127', title: 'شكوى تسرب حشوة وألم مستمر', caseType: 'complaint', claimType: 'unknown',
    authority: 'المديرية العامة للشؤون الصحية بالمدينة المنورة', complaintNo: '02097/1448', procedure: 'حشوة تجميلية (24)', procedureDate: '2026-08-11', patientCode: 'P-6981',
    dentist: { name: 'د. هند العنزي', specialty: 'طب أسنان تجميلي', city: 'المدينة المنورة' }, risk: 'low', positionStrength: 77, submittedAt: '2026-12-12T10:00:00+03:00',
    claimedBy: 'L-07', status: 'approved', approvedAt: '2026-12-13T09:15:00+03:00' },
  { draftId: 'dr-0131', caseCode: 'DL-2026-0131', title: 'مضاعفات بعد خلع ضرس العقل السفلي', caseType: 'complaint', claimType: 'compensation',
    authority: 'المديرية العامة للشؤون الصحية بمحافظة جدة', complaintNo: '01942/1448', procedure: 'خلع جراحي لضرس العقل (38)', procedureDate: '2026-06-02', patientCode: 'P-6610',
    dentist: { name: 'د. أحمد بن سعد الغامدي', specialty: 'جراحة وزراعة الأسنان', city: 'جدة' }, risk: 'low', positionStrength: 81, submittedAt: '2026-12-09T11:20:00+03:00',
    claimedBy: 'L-07', status: 'approved', approvedAt: '2026-12-10T08:40:00+03:00' },
]

function genericDraft(c: CaseSeed): DraftContent {
  return {
    title: `إفادة الممارس الصحي بشأن الشكوى رقم ${c.complaintNo}`,
    addressee: `سعادة مدير الشؤون القانونية — ${c.authority} المحترم`,
    salutation: 'السلام عليكم ورحمة الله وبركاته، وبعد:',
    intro: p('intro', `أتقدم أنا ${c.dentist.name} بإفادتي بشأن الشكوى المقدمة من المريض (الرمز ${c.patientCode}) المتعلقة بـ${c.procedure}، وأوضح ما يلي:`, false),
    sections: [
      { kind: 'facts', heading: 'أولًا: الوقائع', paragraphs: [
        p('f1', `أُجري الإجراء بتاريخ ${c.procedureDate} وفق الأصول المهنية بعد الحصول على موافقة المريض.`, true, [{ ref: 'S2', quote: 'يجب ألا يجرى أي عمل طبي لمريض إلا برضاه', verified: true }]),
        p('f2', 'وُثّقت الزيارات اللاحقة وتعليمات ما بعد الإجراء في الملف الطبي.', false, [{ ref: 'D2', quote: 'متابعة', verified: true }]),
      ] },
      { kind: 'defenses', heading: 'ثانيًا: الدفوع', paragraphs: [
        p('d1', 'انتفاء الخطأ المهني الصحي بصوره المحددة نظامًا، وعدم ثبوت علاقة السببية بين الإجراء والضرر المدّعى.', true, [{ ref: 'S1', quote: 'الخطأ في العلاج أو نقص المتابعة', verified: true }]),
      ] },
      { kind: 'requests', heading: 'ثالثًا: الطلبات', paragraphs: [
        p('r1', 'ألتمس حفظ الشكوى لعدم ثبوت الخطأ المهني الصحي.', true, [{ ref: 'S3', quote: 'يجوز التوصية بالحفظ عند عدم كفاية الأدلة', verified: true }]),
      ] },
    ],
    closing: 'وتقبلوا وافر التحية والتقدير،،،',
    attachments: ['الملف الطبي', 'إقرار الموافقة'],
  }
}

function genericDocuments(c: CaseSeed): CaseDocumentRef[] {
  return [
    { ref: 'D1', documentId: `${c.draftId}-d1`, name: 'خطاب الإبلاغ بالشكوى', kind: 'complaint_letter', pages: 1, sizeLabel: '380 ك.ب', lowQuality: false,
      maskedText: `${c.authority}\nالرقم: ${c.complaintNo}\nورد إلينا شكوى من المريض [PATIENT] (هوية [ID_1]) بخصوص ${c.procedure}.` },
    { ref: 'D2', documentId: `${c.draftId}-d2`, name: 'الملف الطبي وملاحظات المتابعة', kind: 'medical_record', pages: 3, sizeLabel: '1.1 م.ب', lowQuality: false,
      maskedText: `اسم المريض: [PATIENT] · رقم الملف: [MRN_1]\n${c.procedureDate}: ${c.procedure}.\nمتابعة بعد أسبوع دون ملاحظات.` },
  ]
}

function aiVersion(content: DraftContent, createdAt: string): DraftVersion {
  return { id: 'v1', no: 1, authorType: 'ai', authorName: 'مسودة آلية', createdAt, note: null, locked: false, content }
}

export function buildReviews(): ReviewDetail[] {
  return CASES.map((c) => {
    const isMain = c.caseCode === 'DL-2026-0152'
    const v1 = aiVersion(isMain ? DRAFT_0152_V1 : genericDraft(c), c.submittedAt)
    const versions: DraftVersion[] = [v1]
    if (c.status === 'approved') {
      const v2content = structuredClone(v1.content)
      v2content.sections[0]!.paragraphs[1]!.text += ' (أرفق المحامي ملاحظات المتابعة المؤرخة).'
      versions.push({ id: 'v2', no: 2, authorType: 'lawyer', authorName: 'أ. سارة العتيبي', createdAt: c.approvedAt!, note: 'تعديل صياغة الوقائع', locked: true, content: v2content })
    }
    return {
      draftId: c.draftId, caseCode: c.caseCode, title: c.title, caseType: c.caseType, claimType: c.claimType, authority: c.authority,
      complaintNo: c.complaintNo, procedure: c.procedure, procedureDate: c.procedureDate, patientCode: c.patientCode,
      factsMasked: isMain
        ? 'يدّعي المريض [PATIENT] استمرار تنميل في الشفة السفلية والذقن (الجهة اليسرى) منذ إجراء الزراعة، ويطالب بالتعويض عن الضرر وتكاليف العلاج. موعد أخذ الإفادة 29 أكتوبر 2026.'
        : `شكوى من المريض [PATIENT] بخصوص ${c.procedure}.`,
      dentist: c.dentist, risk: c.risk, positionStrength: c.positionStrength, status: c.status, claimedBy: c.claimedBy,
      approvedAt: c.approvedAt, approvedBy: c.status === 'approved' ? 'أ. سارة العتيبي' : null,
      versions,
      sources: isMain ? SOURCES_0152 : SOURCES_0152.slice(0, 4),
      documents: isMain ? DOCUMENTS_0152 : genericDocuments(c),
      checks: isMain ? CHECKS_0152 : [{ ok: true, text: 'جميع الاستشهادات مرتبطة بمصادر مسترجعة' }, { ok: true, text: 'لا توجد بيانات تعريفية للمريض في النص' }],
      notes: isMain ? NOTES_0152 : [],
    }
  })
}

export const SUBMITTED_AT: Record<string, string> = Object.fromEntries(CASES.map((c) => [c.draftId, c.submittedAt]))

export const CITATION_SAMPLES: CitationSample[] = [
  { id: 'cs1', caseCode: 'DL-2026-0131', route: 'draft', claim: 'انتفاء الخطأ المهني لعدم ثبوت خطأ في العلاج أو نقص في المتابعة.', quote: 'الخطأ في العلاج أو نقص المتابعة', sourceLabel: 'المادة 27', sourceText: SOURCES_0152[0]!.text, verdict: 'correct', note: '' },
  { id: 'cs2', caseCode: 'استشارة', route: 'consult', claim: 'يجوز الإفصاح عن معلومات المريض بالقدر اللازم للدفاع عن الممارس.', quote: 'دفع الممارس لاتهام وجهه إليه المريض أو ذووه', sourceLabel: 'المادة 21', sourceText: SOURCES_0152[3]!.text, verdict: 'correct', note: '' },
  { id: 'cs3', caseCode: 'DL-2026-0127', route: 'analysis', claim: 'التأمين ضد الأخطاء المهنية إلزامي على طبيب الأسنان.', quote: 'إلزاميًا على جميع الأطباء وأطباء الأسنان', sourceLabel: 'المادة 41', sourceText: SOURCES_0152[4]!.text, verdict: null, note: '' },
  { id: 'cs4', caseCode: 'DL-2026-0127', route: 'draft', claim: 'تُؤخذ موافقة المريض قبل العمل الطبي.', quote: 'يجب ألا يجرى أي عمل طبي لمريض إلا برضاه', sourceLabel: 'المادة 19', sourceText: SOURCES_0152[1]!.text, verdict: null, note: '' },
  { id: 'cs5', caseCode: 'استشارة', route: 'consult', claim: 'يحق للممارس المؤجل سفره الاعتراض بإرفاق وثيقة التأمين وإثبات إبلاغ شركة التأمين.', quote: 'وثيقة تأمين سارية وقت الإجراء محل الشكوى', sourceLabel: 'اللائحة 40/2', sourceText: SOURCES_0152[5]!.text, verdict: null, note: '' },
  { id: 'cs6', caseCode: 'DL-2026-0131', route: 'analysis', claim: 'المطالبة بالتعويض ترفع إلى الهيئة الصحية الشرعية.', quote: 'مطالبة بالحق الخاص (دية – تعويض – أرش)', sourceLabel: 'المادة 34', sourceText: SOURCES_0152[6]!.text, verdict: null, note: '' },
]

// ---------- admin ----------

export const DENTISTS: DentistRow[] = [
  { id: 'D-1042', name: 'د. أحمد بن سعد الغامدي', phone: '+966551234417', specialty: 'جراحة وزراعة الأسنان', city: 'جدة', status: 'active', joinedAt: '2026-12-02', lastActiveAt: '2026-12-20T08:10:00+03:00', cases: 2, consultations: 9 },
  { id: 'D-1107', name: 'د. ريم الحربي', phone: '+966553318820', specialty: 'علاج الجذور', city: 'الرياض', status: 'active', joinedAt: '2026-12-02', lastActiveAt: '2026-12-20T07:55:00+03:00', cases: 1, consultations: 6 },
  { id: 'D-1088', name: 'د. خالد المطيري', phone: '+966554402213', specialty: 'طب أسنان عام', city: 'الدمام', status: 'active', joinedAt: '2026-12-03', lastActiveAt: '2026-12-18T13:20:00+03:00', cases: 1, consultations: 4 },
  { id: 'D-1063', name: 'د. هند العنزي', phone: '+966559087741', specialty: 'طب أسنان تجميلي', city: 'المدينة المنورة', status: 'active', joinedAt: '2026-12-03', lastActiveAt: '2026-12-17T21:05:00+03:00', cases: 1, consultations: 11 },
  { id: 'D-1094', name: 'د. ماجد السبيعي', phone: '+966556641109', specialty: 'اللثة', city: 'الرياض', status: 'active', joinedAt: '2026-12-04', lastActiveAt: '2026-12-19T15:40:00+03:00', cases: 1, consultations: 3 },
  { id: 'D-1131', name: 'د. سلطان الشهراني', phone: '+966557720931', specialty: 'تقويم الأسنان', city: 'أبها', status: 'active', joinedAt: '2026-12-06', lastActiveAt: '2026-12-16T10:12:00+03:00', cases: 0, consultations: 7 },
  { id: 'D-1129', name: 'د. لمى البقمي', phone: '+966552209176', specialty: 'أسنان الأطفال', city: 'الطائف', status: 'active', joinedAt: '2026-12-06', lastActiveAt: '2026-12-19T09:30:00+03:00', cases: 0, consultations: 5 },
  { id: 'D-1145', name: 'د. فهد القرني', phone: '+966558815530', specialty: 'جراحة الفم', city: 'خميس مشيط', status: 'invited', joinedAt: '2026-12-14', lastActiveAt: null, cases: 0, consultations: 0 },
  { id: 'D-1146', name: 'د. نوف الزهراني', phone: '+966551197402', specialty: 'طب أسنان عام', city: 'جدة', status: 'invited', joinedAt: '2026-12-15', lastActiveAt: null, cases: 0, consultations: 0 },
  { id: 'D-1120', name: 'د. أمل الرشيدي', phone: '+966554476623', specialty: 'طب أسنان عام', city: 'حائل', status: 'disabled', joinedAt: '2026-12-05', lastActiveAt: '2026-12-08T12:00:00+03:00', cases: 0, consultations: 1 },
]

export const LAWYERS: LawyerRow[] = [
  { id: 'L-07', name: 'أ. سارة العتيبي', initials: 'سع', email: 'sara@dentlegal.test', firm: 'مكتب العتيبي للمحاماة والاستشارات', licenseNo: '44/1203', status: 'active', inReview: 2, approvedThisMonth: 2, avgReviewHours: 9.5 },
  { id: 'L-09', name: 'أ. فيصل القحطاني', initials: 'فق', email: 'faisal@dentlegal.test', firm: 'القحطاني ومشاركوه', licenseNo: '41/0877', status: 'active', inReview: 0, approvedThisMonth: 0, avgReviewHours: null },
  { id: 'L-15', name: 'أ. عبدالله الدوسري', initials: 'عد', email: 'abdullah@dentlegal.test', firm: 'الدوسري للمحاماة', licenseNo: '43/1588', status: 'invited', inReview: 0, approvedThisMonth: 0, avgReviewHours: null },
]

export const KNOWLEDGE: KnowledgeSource[] = [
  { id: 'src-hppl', code: 'HPPL', title: 'نظام مزاولة المهن الصحية ولائحته التنفيذية', type: 'law', versions: [
    { id: 'ver-hppl-3', sourceId: 'src-hppl', versionLabel: 'v3', edition: 'الإصدار الثالث 1440هـ', effectiveFrom: '2019-01-01', fileName: 'Executive-Regulations-Health-Profession.pdf',
      extractionMethod: 'text_layer', status: 'active', chunkCount: 412, publishedAt: '2026-12-03T11:00:00+03:00', updatedAt: '2026-12-03T11:00:00+03:00' },
    { id: 'ver-hppl-2', sourceId: 'src-hppl', versionLabel: 'v2', edition: 'إصدار سابق', effectiveFrom: null, fileName: 'Executive-Regulations-Health-Profession-1.pdf',
      extractionMethod: 'text_layer', status: 'archived', chunkCount: 318, publishedAt: '2026-12-02T15:00:00+03:00', updatedAt: '2026-12-03T11:00:00+03:00' },
  ] },
  { id: 'src-dental', code: 'DENTAL_SVC_REG', title: 'اللائحة التنظيمية لخدمات طب الأسنان — وزارة الصحة', type: 'regulation', versions: [
    { id: 'ver-dental-1', sourceId: 'src-dental', versionLabel: 'v1', edition: '1425هـ / 2004م', effectiveFrom: '2004-01-01', fileName: 'اللائحة التنظيمية لخدمات طب الأسنان.pdf',
      extractionMethod: 'llm_transcription', status: 'active', chunkCount: 186, publishedAt: '2026-12-03T11:20:00+03:00', updatedAt: '2026-12-03T11:20:00+03:00' },
  ] },
  { id: 'src-gp', code: 'GP_JOB', title: 'الوصف الوظيفي — طبيب أسنان عام', type: 'job_description', versions: [
    { id: 'ver-gp-1', sourceId: 'src-gp', versionLabel: 'v1', edition: 'الإصدار 2', effectiveFrom: null, fileName: 'Dental-GP-Job.pdf',
      extractionMethod: 'text_layer', status: 'active', chunkCount: 31, publishedAt: '2026-12-03T11:25:00+03:00', updatedAt: '2026-12-03T11:25:00+03:00' },
  ] },
]

const ORDINALS = ['', 'الأولى', 'الثانية', 'الثالثة', 'الرابعة', 'الخامسة', 'السادسة', 'السابعة', 'الثامنة', 'التاسعة', 'العاشرة']

export function buildChunks(versionId: string): KnowledgeChunk[] {
  const known: Record<number, string> = { 19: SOURCES_0152[1]!.text, 21: SOURCES_0152[3]!.text, 27: SOURCES_0152[0]!.text, 34: SOURCES_0152[6]!.text, 41: SOURCES_0152[4]!.text }
  if (versionId.startsWith('ver-hppl')) {
    const out: KnowledgeChunk[] = []
    let id = versionId.endsWith('3') ? 360 : 40
    for (let a = 1; a <= 44; a++) {
      out.push({ id: id++, chunkKey: `HPPL:A${a}`, chunkType: a === 1 ? 'definition' : 'article', articleNo: a, paragraphRef: null, pages: `${Math.ceil(a * 0.7) + 9}`,
        tokens: 180 + ((a * 37) % 220), text: known[a] ?? `نص المادة ${a <= 10 ? ORDINALS[a] : a} من النظام (نص تجريبي للعرض — يُستبدل بالنص المستخرج من المصدر).` })
      if (a === 40) {
        out.push({ id: id++, chunkKey: 'HPPL:R40-1', chunkType: 'regulation', articleNo: 40, paragraphRef: '40/1', pages: '27–28', tokens: 260, text: SOURCES_0152[2]!.text })
        out.push({ id: id++, chunkKey: 'HPPL:R40-2', chunkType: 'regulation', articleNo: 40, paragraphRef: '40/2', pages: '28', tokens: 190, text: SOURCES_0152[5]!.text })
      }
    }
    return out
  }
  if (versionId.startsWith('ver-dental')) {
    return ['مقدمة', 'مستويات خدمات طب الأسنان', 'مدير إدارة طب الأسنان بالوزارة', 'رئيس قسم الأسنان بالمستشفى', 'طبيب الأسنان العام', 'أخصائي جراحة الفم', 'فني الأسنان'].map((h, i) => ({
      id: 900 + i, chunkKey: `DSR:S${i + 1}`, chunkType: 'section' as const, articleNo: null, paragraphRef: null, pages: `${i * 4 + 2}`, tokens: 240 + i * 12,
      text: i === 1 ? 'تُوزّع خدمات طب الأسنان على ثلاثة مستويات: الرعاية الأولية، والعيادات التخصصية بالمستشفيات، ومراكز طب الأسنان الاستشارية، ولكل مستوى نطاق خدمات ومهام محددة.' : `${h} — (نص تجريبي للعرض).`,
    }))
  }
  return ['General information', 'Required qualification', 'Competencies and skills', 'Main duties'].map((h, i) => ({
    id: 950 + i, chunkKey: `GPJ:S${i + 1}`, chunkType: 'section' as const, articleNo: null, paragraphRef: null, pages: `${i + 1}`, tokens: 150 + i * 20,
    text: i === 1 ? 'Accredited Bachelor Degree in Dental Surgery or Equivalent; 1-year internship program; valid registration from SCHS; valid Basic Life Support certification.' : `${h} (sample text).`,
  }))
}

export const RATINGS: RatingRow[] = [
  { id: 'r1', createdAt: '2026-12-20T08:12:00+03:00', dentistName: 'د. أحمد بن سعد الغامدي', targetType: 'analysis', targetLabel: 'تحليل DL-2026-0152', targetExcerpt: 'الموقف الدفاعي جيد بشكل عام: توجد موافقة مستنيرة موقّعة تذكر خطر التنميل…', useful: true, comment: 'واضح ومفيد، خاصة الإجراءات العاجلة.' },
  { id: 'r2', createdAt: '2026-12-20T07:58:00+03:00', dentistName: 'د. ريم الحربي', targetType: 'consultation_message', targetLabel: 'إجابة: مهلة التظلم', targetExcerpt: 'يجوز التظلم من قرار الهيئة الصحية الشرعية أمام ديوان المظالم خلال ستين يومًا…', useful: true, comment: null },
  { id: 'r3', createdAt: '2026-12-19T22:40:00+03:00', dentistName: 'د. هند العنزي', targetType: 'consultation_message', targetLabel: 'إجابة: الإعلان عن العيادة', targetExcerpt: 'لا تتوفر لديّ مصادر نظامية كافية للإجابة عن هذا السؤال بدقة…', useful: false, comment: 'كنت أتوقع إجابة عن ضوابط الإعلان.' },
  { id: 'r4', createdAt: '2026-12-19T16:20:00+03:00', dentistName: 'د. ماجد السبيعي', targetType: 'draft_version', targetLabel: 'مسودة DL-2026-0157', targetExcerpt: 'أتقدم أنا د. ماجد السبيعي بإفادتي بشأن الشكوى…', useful: true, comment: 'الصياغة رسمية ومناسبة.' },
  { id: 'r5', createdAt: '2026-12-19T09:35:00+03:00', dentistName: 'د. لمى البقمي', targetType: 'consultation_message', targetLabel: 'إجابة: موافقة ولي الأمر', targetExcerpt: 'يجب ألا يجرى أي عمل طبي لمريض إلا برضاه، أو بموافقة من يمثله أو ولي أمره…', useful: true, comment: null },
  { id: 'r6', createdAt: '2026-12-18T13:30:00+03:00', dentistName: 'د. خالد المطيري', targetType: 'analysis', targetLabel: 'تحليل DL-2026-0139', targetExcerpt: 'مستوى المخاطر مرتفع: لا يوجد توثيق لتأكيد رقم السن قبل الخلع…', useful: true, comment: 'صريح، وهذا ما أحتاجه.' },
  { id: 'r7', createdAt: '2026-12-17T21:10:00+03:00', dentistName: 'د. هند العنزي', targetType: 'draft_version', targetLabel: 'مسودة DL-2026-0127', targetExcerpt: 'أتقدم أنا د. هند العنزي بإفادتي…', useful: false, comment: 'المسودة طويلة، أفضّل صياغة أقصر.' },
  { id: 'r8', createdAt: '2026-12-16T10:20:00+03:00', dentistName: 'د. سلطان الشهراني', targetType: 'consultation_message', targetLabel: 'إجابة: التأمين الإلزامي', targetExcerpt: 'نعم. الاشتراك في التأمين التعاوني ضد الأخطاء المهنية الطبية إلزامي…', useful: true, comment: null },
]

export const FEEDBACK: FeedbackRow[] = [
  { id: 'fb1', createdAt: '2026-12-20T08:20:00+03:00', dentistName: 'د. أحمد بن سعد الغامدي', category: 'suggestion', message: 'أتمنى إمكانية إضافة ملاحظة صوتية مع القضية.', screen: 'قضية جديدة', appVersion: '1.0.0 (14)', platform: 'ios', status: 'new' },
  { id: 'fb2', createdAt: '2026-12-19T22:45:00+03:00', dentistName: 'د. هند العنزي', category: 'bug', message: 'زر رفع المستند لم يستجب في المرة الأولى.', screen: 'المستندات', appVersion: '1.0.0 (14)', platform: 'android', status: 'seen' },
  { id: 'fb3', createdAt: '2026-12-18T14:05:00+03:00', dentistName: 'د. خالد المطيري', category: 'other', message: 'التطبيق ممتاز، شكرًا لكم.', screen: 'حسابي', appVersion: '1.0.0 (13)', platform: 'ios', status: 'done' },
  { id: 'fb4', createdAt: '2026-12-17T09:10:00+03:00', dentistName: 'د. لمى البقمي', category: 'suggestion', message: 'إضافة أسئلة مقترحة عن طب أسنان الأطفال.', screen: 'المستشار القانوني الذكي', appVersion: '1.0.0 (13)', platform: 'android', status: 'new' },
]

export function buildDaily(days: number): { day: string; questions: number; cases: number }[] {
  const end = new Date(DEMO_NOW)
  return Array.from({ length: days }, (_, i) => {
    const d = new Date(end)
    d.setDate(end.getDate() - (days - 1 - i))
    const seed = (i * 7 + 3) % 11
    return { day: d.toISOString().slice(0, 10), questions: 4 + seed + Math.floor(i / 3), cases: seed % 3 === 0 ? 2 : seed % 4 === 0 ? 1 : 0 }
  })
}
