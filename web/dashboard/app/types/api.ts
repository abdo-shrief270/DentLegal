// Shapes of the lawyer/admin API (PROJECT1_PLAN.md §3.2 and §4.2).
// Hand-written while the backend does not exist; later generated from the OpenAPI spec (openapi-typescript).

export type Role = 'lawyer' | 'admin'
export type RiskLevel = 'low' | 'medium' | 'high'
export type CaseType = 'complaint' | 'official_notice' | 'sharia_board' | 'preventive'
export type ClaimType = 'compensation' | 'disciplinary' | 'unknown'
export type CaseStatus = 'draft' | 'analyzing' | 'ai_ready' | 'lawyer_review' | 'approved' | 'failed'

export interface SessionUser {
  id: string
  name: string
  email: string
  role: Role
  initials: string
  subtitle: string
}

// ---------- lawyer ----------

export type ReviewTab = 'unclaimed' | 'mine' | 'approved'

export interface ReviewListItem {
  draftId: string
  caseCode: string
  title: string
  caseType: CaseType
  dentistName: string
  risk: RiskLevel
  positionStrength: number
  submittedAt: string
  claimedBy: string | null
  claimedAt: string | null
  approvedAt: string | null
  status: 'in_review' | 'approved'
  flagsCount: number
}

export interface CitationRef {
  ref: string // S1..Sn (knowledge source) or D1..Dn (case document)
  quote: string
  verified: boolean
  reason?: 'ref_not_in_context' | 'quote_mismatch' | 'article_mismatch'
}

export interface DraftParagraph {
  id: string
  text: string
  legalClaim: boolean
  citations: CitationRef[]
}

export interface DraftSection {
  kind: 'facts' | 'defenses' | 'requests'
  heading: string
  paragraphs: DraftParagraph[]
}

export interface DraftContent {
  title: string
  addressee: string
  salutation: string
  intro: DraftParagraph
  sections: DraftSection[]
  closing: string
  attachments: string[]
}

export interface DraftVersion {
  id: string
  no: number
  authorType: 'ai' | 'lawyer'
  authorName: string
  createdAt: string
  note: string | null
  locked: boolean
  content: DraftContent
}

export interface RetrievedSource {
  ref: string // S1..
  chunkId: number
  law: string
  label: string // «المادة 27» / «اللائحة 40/1»
  title: string
  text: string
  score: number // reranker score 0..1
}

export interface CaseDocumentRef {
  ref: string // D1..
  documentId: string
  name: string
  kind: string
  pages: number
  sizeLabel: string
  lowQuality: boolean
  maskedText: string
}

export interface QualityCheck {
  ok: boolean
  text: string
}

export interface InternalNote {
  id: string
  author: string
  createdAt: string
  text: string
}

export interface ReviewDetail {
  draftId: string
  caseCode: string
  title: string
  caseType: CaseType
  claimType: ClaimType
  authority: string
  complaintNo: string
  procedure: string
  procedureDate: string
  patientCode: string
  factsMasked: string
  dentist: { name: string; specialty: string; city: string }
  risk: RiskLevel
  positionStrength: number
  status: 'in_review' | 'approved'
  claimedBy: string | null
  approvedAt: string | null
  approvedBy: string | null
  versions: DraftVersion[]
  sources: RetrievedSource[]
  documents: CaseDocumentRef[]
  checks: QualityCheck[]
  notes: InternalNote[]
}

export interface CitationSample {
  id: string
  caseCode: string
  route: 'consult' | 'analysis' | 'draft'
  claim: string
  quote: string
  sourceLabel: string
  sourceText: string
  verdict: 'correct' | 'incorrect' | null
  note: string
}

// ---------- admin ----------

export type UserStatus = 'invited' | 'active' | 'disabled'

export interface DentistRow {
  id: string
  name: string
  phone: string
  specialty: string
  city: string
  status: UserStatus
  joinedAt: string
  lastActiveAt: string | null
  cases: number
  consultations: number
}

export interface LawyerRow {
  id: string
  name: string
  initials: string
  email: string
  firm: string
  licenseNo: string
  status: UserStatus
  inReview: number
  approvedThisMonth: number
  avgReviewHours: number | null
}

export type SourceVersionStatus = 'uploaded' | 'processing' | 'review' | 'active' | 'archived' | 'failed'
export type ExtractionMethod = 'auto' | 'text_layer' | 'ocr' | 'llm_transcription'

export interface SourceVersion {
  id: string
  sourceId: string
  versionLabel: string
  edition: string
  effectiveFrom: string | null
  fileName: string
  extractionMethod: ExtractionMethod
  status: SourceVersionStatus
  chunkCount: number
  publishedAt: string | null
  updatedAt: string
}

export interface KnowledgeSource {
  id: string
  code: string
  title: string
  type: 'law' | 'regulation' | 'job_description' | 'guideline'
  versions: SourceVersion[]
}

export interface KnowledgeChunk {
  id: number
  chunkKey: string
  chunkType: 'article' | 'regulation' | 'definition' | 'section' | 'form'
  articleNo: number | null
  paragraphRef: string | null
  pages: string
  tokens: number
  text: string
}

export type RatingTarget = 'consultation_message' | 'analysis' | 'draft_version'

export interface RatingRow {
  id: string
  createdAt: string
  dentistName: string
  targetType: RatingTarget
  targetLabel: string
  targetExcerpt: string
  useful: boolean
  comment: string | null
}

export type FeedbackStatus = 'new' | 'seen' | 'done'

export interface FeedbackRow {
  id: string
  createdAt: string
  dentistName: string
  category: 'bug' | 'suggestion' | 'other'
  message: string
  screen: string
  appVersion: string
  platform: 'ios' | 'android'
  status: FeedbackStatus
}

export interface StatsOverview {
  range: '7d' | '30d'
  activeDentists: number
  invitedDentists: number
  questionsAnswered: number
  refusalRate: number
  casesCreated: number
  draftsGenerated: number
  draftsApproved: number
  usefulRate: { overall: number; consult: number; analysis: number; draft: number }
  uploadToDraftMinutes: { p50: number; p90: number }
  citationValidity: number
  aiCostUsd: number
  daily: { day: string; questions: number; cases: number }[]
}

/** One Claude call from `ai_runs` (metadata only; no prompt or output text is stored). */
export interface AiRunRow {
  id: string
  createdAt: string
  runType: string
  status: string
  caseCode: string | null
  subjectType: string | null
  promptVersion: string | null
  modelRequested: string | null
  modelServed: string | null
  effort: string | null
  inferenceGeo: string | null
  fallbackUsed: boolean
  stopReason: string | null
  refusalCategory: string | null
  errorCode: string | null
  inputTokens: number
  outputTokens: number
  cacheReadTokens: number
  costUsd: number
  latencyMs: number | null
  citationValidity: number | null
  repaired: boolean
  sources: number
  requestId: string | null
}

export interface AiRunSummary {
  runs: number
  shown: number
  costUsd: number
  inputTokens: number
  outputTokens: number
  cacheReadTokens: number
  latencyP50Ms: number | null
  latencyP90Ms: number | null
  citationValidity: number | null
  fallbacks: number
  errors: number
  runTypes: string[]
}

export interface AiRunFilters { runType?: string; problems?: boolean; from?: string; to?: string }
