// Arabic labels for API enums (kept in one place so screens stay consistent).
import type { CaseType, ExtractionMethod, FeedbackStatus, RatingTarget, RiskLevel, SourceVersionStatus, UserStatus } from '~/types/api'

export const RISK_LABELS: Record<RiskLevel, { label: string; cls: string }> = {
  low: { label: 'منخفضة', cls: 'b-green' },
  medium: { label: 'متوسطة', cls: 'b-amber' },
  high: { label: 'مرتفعة', cls: 'b-red' },
}

export const CASE_TYPE_LABELS: Record<CaseType, string> = {
  complaint: 'شكوى من مريض',
  official_notice: 'استدعاء أو إشعار رسمي',
  sharia_board: 'دعوى أمام الهيئة الصحية الشرعية',
  preventive: 'استشارة وقائية',
}

export const USER_STATUS: Record<UserStatus, { label: string; cls: string }> = {
  invited: { label: 'مدعو', cls: 'b-blue' },
  active: { label: 'نشط', cls: 'b-green' },
  disabled: { label: 'موقوف', cls: 'b-gray' },
}

export const VERSION_STATUS: Record<SourceVersionStatus, { label: string; cls: string }> = {
  uploaded: { label: 'مرفوع', cls: 'b-gray' },
  processing: { label: 'قيد الفهرسة', cls: 'b-violet' },
  review: { label: 'بانتظار النشر', cls: 'b-amber' },
  active: { label: 'ساري', cls: 'b-green' },
  archived: { label: 'مؤرشف', cls: 'b-gray' },
  failed: { label: 'فشل', cls: 'b-red' },
}

export const EXTRACTION_LABELS: Record<ExtractionMethod, string> = {
  auto: 'تلقائي',
  text_layer: 'طبقة النص',
  ocr: 'تعرّف ضوئي (OCR)',
  llm_transcription: 'نسخ آلي للصفحات',
}

export const RATING_TARGET_LABELS: Record<RatingTarget, string> = {
  consultation_message: 'إجابة المستشار',
  analysis: 'تحليل قضية',
  draft_version: 'مسودة رد',
}

export const FEEDBACK_STATUS: Record<FeedbackStatus, { label: string; cls: string }> = {
  new: { label: 'جديدة', cls: 'b-amber' },
  seen: { label: 'تمت المشاهدة', cls: 'b-blue' },
  done: { label: 'تمت المعالجة', cls: 'b-green' },
}

export const FEEDBACK_CATEGORY: Record<'bug' | 'suggestion' | 'other', { label: string; cls: string }> = {
  bug: { label: 'مشكلة', cls: 'b-red' },
  suggestion: { label: 'اقتراح', cls: 'b-teal' },
  other: { label: 'أخرى', cls: 'b-gray' },
}

export function percent(value: number, digits = 0): string {
  // Left-to-right isolate so «7%» doesn't render as «%7» inside Arabic sentences.
  return `\u2066${(value * 100).toFixed(digits)}%\u2069`
}

/** Arabic number agreement: 1 → واحد، 2 → مثنى، 3–10 → جمع، 11+ → مفرد منصوب. */
export function pluralAr(n: number, forms: { one: string; two: string; few: (n: number) => string; many: (n: number) => string }): string {
  if (n === 1) return forms.one
  if (n === 2) return forms.two
  return n >= 3 && n <= 10 ? forms.few(n) : forms.many(n)
}

export function unsupportedParagraphs(n: number): string {
  return pluralAr(n, {
    one: 'فقرة قانونية واحدة غير مسندة',
    two: 'فقرتان قانونيتان غير مسندتين',
    few: (k) => `${k} فقرات قانونية غير مسندة`,
    many: (k) => `${k} فقرة قانونية غير مسندة`,
  })
}
