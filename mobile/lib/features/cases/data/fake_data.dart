// Synthetic demo data for the fake repositories. Fictional people and cases only — no real patient data.
// Cases and regulation excerpts are adapted from the approved prototype (site/prototype/js/data.js).
import '../domain/case_models.dart';
import '../../../core/utils/bidi.dart';

const _hppl = 'نظام مزاولة المهن الصحية';
const _hpplReg = 'اللائحة التنفيذية لنظام مزاولة المهن الصحية';

const fakeSources = <RegulationSource>[
  RegulationSource(
    ref: 'S1',
    law: _hppl,
    label: 'المادة 27',
    title: 'الخطأ المهني الصحي والتعويض',
    score: 0.95,
    text: 'كل خطأ مهني صحي صدر من الممارس الصحي، وترتب عليه ضرر للمريض؛ يلتزم من ارتكبه بالتعويض… ويعد من قبيل الخطأ: (1) الخطأ في العلاج أو نقص المتابعة، (2) الجهل بأمور فنية يفترض الإلمام بها… (8) عدم استشارة من تستدعي حالة المريض الاستعانة به.',
  ),
  RegulationSource(
    ref: 'S2',
    law: _hppl,
    label: 'المادة 19',
    title: 'رضا المريض قبل العمل الطبي',
    score: 0.92,
    text: 'يجب ألا يجرى أي عمل طبي لمريض إلا برضاه، أو بموافقة من يمثله أو ولي أمره إذا لم يعتد بإرادته هو… وتؤخذ موافقة المريض البالغ العاقل أو من يمثله قبل القيام بالعمل الطبي أو الجراحي.',
  ),
  RegulationSource(
    ref: 'S3',
    law: _hpplReg,
    label: 'اللائحة 40/1',
    title: 'إجراءات التحقيق قبل الإحالة',
    score: 0.88,
    text: 'تُؤخذ إفادات الممارسين الصحيين المعالجين للحالة على النموذج المخصص، ويُبلّغ المدعى عليه بموعد ومكان الإفادة قبلها بوقت كافٍ، ويُرفع التقرير المتعلق بالمطالبة بالحق الخاص إلى الهيئة الصحية الشرعية، وفيما عدا ذلك يجوز التوصية بالحفظ عند عدم كفاية الأدلة.',
  ),
  RegulationSource(
    ref: 'S4',
    law: _hppl,
    label: 'المادة 21',
    title: 'سرية معلومات المريض وحالات الإفصاح',
    score: 0.81,
    text: 'يجب على الممارس الصحي أن يحافظ على الأسرار التي علم بها عن طريق مهنته، ولا يجوز له إفشاؤها إلا في أحوال منها: دفع الممارس لاتهام وجهه إليه المريض أو ذووه يتعلق بكفايته أو بكيفية ممارسته المهنة.',
  ),
  RegulationSource(
    ref: 'S5',
    law: _hppl,
    label: 'المادة 41',
    title: 'التأمين الإلزامي ضد الأخطاء المهنية',
    score: 0.79,
    text: 'يكون الاشتراك في التأمين التعاوني ضد الأخطاء المهنية الطبية إلزاميًا على جميع الأطباء وأطباء الأسنان… وتشمل التغطية التعويضات الناشئة عن الأخطاء الواقعة أثناء فترة التغطية.',
  ),
  RegulationSource(
    ref: 'S6',
    law: _hpplReg,
    label: 'اللائحة 40/2',
    title: 'الاعتراض على تأجيل السفر',
    score: 0.77,
    text: 'للممارس الصحي المؤجل سفره حق الاعتراض مرفقًا: (أ) وثيقة تأمين سارية وقت الإجراء محل الشكوى، (ب) إثبات إبلاغ شركة التأمين بالشكوى المرفوعة ضده، (ت) كفالة غرم وأداء.',
  ),
  RegulationSource(
    ref: 'S7',
    law: _hppl,
    label: 'المادة 34',
    title: 'اختصاص الهيئة الصحية الشرعية',
    score: 0.74,
    text: 'تختص الهيئة الصحية الشرعية بالنظر في الأخطاء المهنية الصحية التي ترفع بها مطالبة بالحق الخاص (دية – تعويض – أرش)، والأخطاء التي ينتج عنها وفاة أو تلف عضو أو فقد منفعته.',
  ),
  RegulationSource(
    ref: 'S8',
    law: _hppl,
    label: 'المادة 35',
    title: 'التظلم من قرارات الهيئة',
    score: 0.9,
    text: 'تصدر الهيئة الصحية الشرعية قراراتها بالأغلبية على أن يكون من بينهم القاضي، ويجوز التظلم منها أمام ديوان المظالم خلال ستين يومًا من تاريخ إبلاغ القرار.',
  ),
];

RegulationSource sourceByRef(String ref) => fakeSources.firstWhere((s) => s.ref == ref);

Citation cite(String ref, {bool verified = true}) =>
    Citation(ref: ref, label: ref.startsWith('S') ? sourceByRef(ref).label : _docLabels[ref] ?? ref, verified: verified);

const _docLabels = {'D1': 'خطاب الشكوى', 'D2': 'الملف الطبي', 'D3': 'إقرار الموافقة', 'D4': 'تقرير CBCT', 'D5': 'خطاب الإحالة'};

const fakeDocuments = <CaseDocument>[
  CaseDocument(id: 'doc-1', ref: 'D1', name: 'خطاب الإبلاغ بالشكوى', kind: DocumentKind.complaintLetter, pages: 2, sizeLabel: '412 ك.ب'),
  CaseDocument(id: 'doc-2', ref: 'D2', name: 'الملف الطبي وملاحظات المتابعة', kind: DocumentKind.medicalRecord, pages: 6, sizeLabel: '1.8 م.ب'),
  CaseDocument(id: 'doc-3', ref: 'D3', name: 'إقرار الموافقة المستنيرة', kind: DocumentKind.consentForm, pages: 1, sizeLabel: '640 ك.ب'),
  CaseDocument(
    id: 'doc-4',
    ref: 'D4',
    name: 'تقرير الأشعة المقطعية (CBCT)',
    kind: DocumentKind.radiology,
    pages: 1,
    sizeLabel: '2.4 م.ب',
    isImage: true,
    lowQuality: true,
  ),
  CaseDocument(id: 'doc-5', ref: 'D5', name: 'خطاب الإحالة لاستشاري الوجه والفكين', kind: DocumentKind.referral, pages: 1, sizeLabel: '210 ك.ب'),
];

/// Seed cases relative to [now] so the demo always looks current.
List<DentalCase> seedCases(DateTime now) => [
  DentalCase(
    id: 'c-0152',
    code: 'DL-2026-0152',
    title: 'تنميل في الشفة السفلية بعد زراعة سن',
    caseType: CaseType.complaint,
    claimType: ClaimType.compensation,
    authority: 'المديرية العامة للشؤون الصحية بمحافظة جدة — الشؤون القانونية',
    complaintNo: '02871/1448',
    receivedOn: now.subtract(const Duration(days: 3)),
    procedure: 'زراعة سن في موضع الضرس الأول السفلي الأيسر (36)',
    procedureDate: now.subtract(const Duration(days: 55)),
    patientCode: 'P-7731',
    facts: 'يدّعي المريض استمرار تنميل في الشفة السفلية والذقن (الجهة اليسرى) منذ إجراء الزراعة، ويطالب بالتعويض عن الضرر وتكاليف العلاج.',
    status: CaseStatus.aiReady,
    createdAt: now.subtract(const Duration(hours: 2)),
    updatedAt: now.subtract(const Duration(hours: 1, minutes: 52)),
    documents: fakeDocuments,
    risk: RiskLevel.medium,
    positionStrength: 72,
  ),
  DentalCase(
    id: 'c-0144',
    code: 'DL-2026-0144',
    title: 'ادعاء تلف عصب بعد علاج جذور',
    caseType: CaseType.complaint,
    claimType: ClaimType.compensation,
    authority: 'المديرية العامة للشؤون الصحية بالرياض',
    complaintNo: '02650/1448',
    receivedOn: now.subtract(const Duration(days: 6)),
    procedure: 'علاج عصب للضرس (46)',
    procedureDate: now.subtract(const Duration(days: 120)),
    patientCode: 'P-8120',
    facts: 'يدّعي المريض فقدان الإحساس في جزء من اللسان بعد علاج العصب.',
    status: CaseStatus.lawyerReview,
    createdAt: now.subtract(const Duration(days: 2)),
    updatedAt: now.subtract(const Duration(hours: 20)),
    documents: fakeDocuments.take(3).toList(),
    risk: RiskLevel.medium,
    positionStrength: 58,
    lawyerName: 'أ. سارة العتيبي',
    submittedAt: now.subtract(const Duration(hours: 20)),
  ),
  DentalCase(
    id: 'c-0131',
    code: 'DL-2026-0131',
    title: 'مضاعفات بعد خلع ضرس العقل السفلي',
    caseType: CaseType.complaint,
    claimType: ClaimType.compensation,
    authority: 'المديرية العامة للشؤون الصحية بمحافظة جدة',
    complaintNo: '01942/1448',
    receivedOn: now.subtract(const Duration(days: 14)),
    procedure: 'خلع جراحي لضرس العقل (38)',
    procedureDate: now.subtract(const Duration(days: 170)),
    patientCode: 'P-6610',
    facts: 'يشكو المريض من التهاب في موضع الخلع وألم استمر أسبوعين.',
    status: CaseStatus.approved,
    createdAt: now.subtract(const Duration(days: 12)),
    updatedAt: now.subtract(const Duration(days: 10)),
    documents: fakeDocuments.take(4).toList(),
    risk: RiskLevel.low,
    positionStrength: 81,
    lawyerName: 'أ. سارة العتيبي',
    submittedAt: now.subtract(const Duration(days: 11)),
    approvedAt: now.subtract(const Duration(days: 10)),
  ),
];

CaseAnalysis analysisFor(DentalCase c) => CaseAnalysis(
  caseId: c.id,
  positionStrength: c.positionStrength ?? 70,
  risk: c.risk ?? RiskLevel.medium,
  summary: 'الموقف الدفاعي جيد بشكل عام: توجد موافقة مستنيرة موقّعة تذكر خطر التنميل، وتخطيط بالأشعة المقطعية قبل الإجراء، ومتابعة وإحالة سريعة. نقطة الضعف الرئيسية هي غياب تعليمات ما بعد العملية الموقعة. ولأن الشكوى تتضمن مطالبة بالحق الخاص، يُرجَّح رفعها إلى الهيئة الصحية الشرعية.',
  strengths: [
    AnalysisPoint(text: 'إقرار موافقة مستنيرة موقّع يذكر احتمال التنميل المؤقت أو الدائم', citations: [cite('S2'), cite('D3')]),
    AnalysisPoint(text: 'أشعة مقطعية قبل الإجراء توثّق مسافة أمان 4.2 ملم عن القناة السنية السفلية', citations: [cite('S1'), cite('D4')]),
    AnalysisPoint(text: 'فحص المريض خلال 24 ساعة من البلاغ وإحالته لاستشاري جراحة الوجه والفكين', citations: [cite('S1'), cite('D5')]),
  ],
  weaknesses: [
    AnalysisPoint(
      text: 'لا توجد تعليمات مكتوبة لما بعد العملية موقّعة من المريض — قد يُحتج بنقص المتابعة',
      citations: [cite('S1')],
      severity: RiskLevel.medium,
    ),
    AnalysisPoint(
      text: 'المطالبة بالتعويض حق خاص؛ يُرجّح رفع التقرير إلى الهيئة الصحية الشرعية',
      citations: [cite('S3'), cite('S7')],
      severity: RiskLevel.high,
    ),
  ],
  actions: [
    AnalysisPoint(text: 'إبلاغ شركة التأمين بالشكوى كتابيًا والاحتفاظ بإثبات الإبلاغ', citations: [cite('S6'), cite('S5')], urgency: Urgency.urgent),
    AnalysisPoint(text: 'حضور جلسة الإفادة في موعدها مع نسخة من الملف الطبي', citations: [cite('S4'), cite('S3')], urgency: Urgency.beforeHearing),
    AnalysisPoint(text: 'إرفاق ملاحظات المتابعة المؤرخة', urgency: Urgency.normal),
  ],
  sources: fakeSources.take(7).toList(),
);

CaseDraft draftFor(DentalCase c, {required String dentistName, required String? licenseNo}) {
  final approved = c.status == CaseStatus.approved;
  final license = licenseNo == null ? '' : '، المسجل لدى الهيئة السعودية للتخصصات الصحية برقم (${ltr(licenseNo)})';
  return CaseDraft(
    caseId: c.id,
    versionNo: approved ? 2 : 1,
    byLawyer: approved,
    title: 'إفادة الممارس الصحي بشأن الشكوى رقم ${c.complaintNo}',
    addressee: 'سعادة مدير الشؤون القانونية — ${c.authority} المحترم',
    salutation: 'السلام عليكم ورحمة الله وبركاته، وبعد:',
    intro: DraftParagraph(text: 'أتقدم أنا $dentistName$license، بإفادتي بشأن الشكوى المقدمة من المريض (الرمز ${c.patientCode})، وأوضح ما يلي:'),
    sections: [
      DraftSection(
        heading: 'أولًا: الوقائع',
        paragraphs: [
          DraftParagraph(
            text:
                'راجع المريض العيادة لتقييم الحالة، وأُجريت له أشعة مقطعية قبل الإجراء أظهرت مسافة أمان قدرها 4.2 ملم بين موضع الزرعة والقناة السنية السفلية.',
            citations: [cite('D4')],
          ),
          DraftParagraph(
            text: 'شُرحت للمريض طبيعة الإجراء ومخاطره المحتملة، ومنها احتمال التنميل المؤقت أو الدائم، ووقّع على إقرار الموافقة المستنيرة، التزامًا بما تقضي به المادة التاسعة عشرة من النظام.',
            legalClaim: true,
            citations: [cite('D3'), cite('S2')],
          ),
          DraftParagraph(
            text: 'عند إبلاغ المريض بالتنميل فُحص خلال 24 ساعة وأُحيل إلى استشاري جراحة الوجه والفكين، بما يتفق مع واجب استشارة من تستدعي حالة المريض الاستعانة به.',
            legalClaim: true,
            citations: [cite('D5'), cite('S1')],
          ),
        ],
      ),
      DraftSection(
        heading: 'ثانيًا: الدفوع',
        paragraphs: [
          DraftParagraph(
            text: 'انتفاء الخطأ المهني؛ إذ إن التنميل من المضاعفات المعروفة والموثقة في إقرار الموافقة، ولم يثبت خطأ في العلاج أو نقص في المتابعة.',
            legalClaim: true,
            citations: [cite('S1')],
          ),
          DraftParagraph(
            text: 'يأتي الإفصاح عن المعلومات الطبية الواردة في هذه الإفادة بالقدر اللازم للدفاع عن الممارس.',
            legalClaim: true,
            citations: [cite('S4')],
          ),
          DraftParagraph(
            text: 'يحق للممارس طلب الاستعانة بخبير فني إذا أُحيلت الشكوى إلى الهيئة الصحية الشرعية.',
            legalClaim: true,
            citations: [approved ? cite('S7') : cite('S3', verified: false)],
          ),
        ],
      ),
      DraftSection(
        heading: 'ثالثًا: الطلبات',
        paragraphs: [
          DraftParagraph(
            text: 'أطلب حفظ الشكوى لعدم ثبوت الخطأ المهني الصحي، مع استعدادي التام لتقديم أي مستندات إضافية.',
            legalClaim: true,
            citations: [cite('S3')],
          ),
        ],
      ),
    ],
    closing: 'وتقبلوا وافر التحية والتقدير،،،',
    attachments: c.documents.map((d) => d.name).toList(),
    approvedBy: approved ? c.lawyerName : null,
    approvedAt: approved ? c.approvedAt : null,
  );
}
