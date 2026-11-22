import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/widgets/tone_badge.dart';
import '../domain/case_models.dart';

/// Arabic labels for case enums (same wording as the prototype and the dashboard).
extension CaseStatusLabel on CaseStatus {
  String get label => switch (this) {
    CaseStatus.draft => 'مسودة',
    CaseStatus.analyzing => 'قيد التحليل الذكي',
    CaseStatus.aiReady => 'مسودة AI جاهزة',
    CaseStatus.lawyerReview => 'لدى المحامي للمراجعة',
    CaseStatus.approved => 'معتمد من المحامي',
    CaseStatus.failed => 'تعذّر التحليل',
  };

  Tone get tone => switch (this) {
    CaseStatus.draft => Tone.gray,
    CaseStatus.analyzing || CaseStatus.aiReady => Tone.violet,
    CaseStatus.lawyerReview => Tone.blue,
    CaseStatus.approved => Tone.gold,
    CaseStatus.failed => Tone.red,
  };

  IconData get icon => switch (this) {
    CaseStatus.draft => LucideIcons.pen,
    CaseStatus.analyzing || CaseStatus.aiReady => LucideIcons.sparkles,
    CaseStatus.lawyerReview => LucideIcons.scale,
    CaseStatus.approved => LucideIcons.badgeCheck,
    CaseStatus.failed => LucideIcons.triangleAlert,
  };

  bool get needsAction => this == CaseStatus.aiReady || this == CaseStatus.approved || this == CaseStatus.failed;
}

extension CaseTypeLabel on CaseType {
  String get label => switch (this) {
    CaseType.complaint => 'شكوى من مريض',
    CaseType.officialNotice => 'استدعاء أو إشعار رسمي',
    CaseType.shariaBoard => 'دعوى أمام الهيئة الصحية الشرعية',
    CaseType.preventive => 'استشارة وقائية',
  };

  String get hint => switch (this) {
    CaseType.complaint => 'وصلني إشعار بشكوى عبر الجهة الصحية',
    CaseType.officialNotice => 'طلب إفادة أو حضور جلسة تحقيق',
    CaseType.shariaBoard => 'قضية محالة للنظر أو صدر فيها قرار',
    CaseType.preventive => 'موقف محتمل قبل وصول شكوى',
  };

  IconData get icon => switch (this) {
    CaseType.complaint => LucideIcons.fileText,
    CaseType.officialNotice => LucideIcons.landmark,
    CaseType.shariaBoard => LucideIcons.gavel,
    CaseType.preventive => LucideIcons.shieldCheck,
  };
}

extension ClaimTypeLabel on ClaimType {
  String get label => switch (this) {
    ClaimType.compensation => 'تعويض مالي (حق خاص)',
    ClaimType.disciplinary => 'مساءلة تأديبية',
    ClaimType.unknown => 'غير محدد',
  };
}

extension RiskLabel on RiskLevel {
  String get label => switch (this) {
    RiskLevel.low => 'مخاطر منخفضة',
    RiskLevel.medium => 'مخاطر متوسطة',
    RiskLevel.high => 'مخاطر مرتفعة',
  };

  Tone get tone => switch (this) {
    RiskLevel.low => Tone.green,
    RiskLevel.medium => Tone.amber,
    RiskLevel.high => Tone.red,
  };
}

extension UrgencyLabel on Urgency {
  String get label => switch (this) {
    Urgency.urgent => 'عاجل',
    Urgency.beforeHearing => 'قبل الإفادة',
    Urgency.normal => 'عند الإمكان',
  };
}

extension PipelineStepLabel on PipelineStep {
  (String title, String detail, IconData icon) get info => switch (this) {
    PipelineStep.ocr => ('استخراج النصوص (OCR)', 'عربي وإنجليزي · طباعة ومسح ضوئي', LucideIcons.scanText),
    PipelineStep.masking => ('إخفاء البيانات التعريفية للمريض', 'قبل أي معالجة بالذكاء الاصطناعي', LucideIcons.shieldCheck),
    PipelineStep.retrieval => ('استرجاع المواد النظامية', 'بحث دلالي ونصي في الأنظمة المفهرسة', LucideIcons.bookOpen),
    PipelineStep.analysis => ('تحليل نقاط القوة والضعف', 'مقارنة الوقائع بالمستندات والمواد', LucideIcons.scale),
    PipelineStep.drafting => ('صياغة مسودة الإفادة', 'صياغة قانونية سعودية رسمية', LucideIcons.fileText),
    PipelineStep.verification => ('التحقق من الاستشهادات', 'كل ادعاء مرتبط بمصدر', LucideIcons.badgeCheck),
  };
}

extension DocumentKindLabel on DocumentKind {
  String get label => switch (this) {
    DocumentKind.complaintLetter => 'خطاب الإبلاغ بالشكوى',
    DocumentKind.medicalRecord => 'الملف الطبي',
    DocumentKind.consentForm => 'إقرار الموافقة',
    DocumentKind.radiology => 'تقرير أشعة',
    DocumentKind.referral => 'خطاب إحالة',
    DocumentKind.insurance => 'وثيقة التأمين',
    DocumentKind.other => 'مستند آخر',
  };
}
