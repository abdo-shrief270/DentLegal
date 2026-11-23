// The operations the dashboard needs from the backend (PROJECT1_PLAN.md §4.2).
// Two implementations: the in-browser fake API (app/mocks/api.ts) and the Laravel client (app/services/http.ts).
import type {
  CitationSample, DentistRow, DraftContent, FeedbackRow, FeedbackStatus, InternalNote, KnowledgeChunk, KnowledgeSource,
  LawyerRow, RatingRow, ReviewDetail, ReviewListItem, ReviewTab, Role, SessionUser, SourceVersion, StatsOverview,
} from './api'

export interface InviteInfo { name: string; email: string; role: Role }
export interface TwoFactorSetup { secret: string; otpauthUrl: string; recoveryCodes: string[] }

export interface DashboardApi {
  auth: {
    login(email: string, password: string): Promise<{ twoFactorRequired: boolean }>
    twoFactorChallenge(input: { code?: string; recoveryCode?: string }): Promise<SessionUser>
    currentUser(): Promise<SessionUser | null>
    logout(): Promise<void>
    getInvite(token: string): Promise<InviteInfo>
    acceptInvite(token: string, password: string): Promise<TwoFactorSetup>
    confirmTwoFactor(code: string): Promise<SessionUser>
  }
  lawyer: {
    listReviews(tab: ReviewTab): Promise<ReviewListItem[]>
    counts(): Promise<Record<ReviewTab, number>>
    claim(draftId: string): Promise<void>
    getReview(draftId: string): Promise<ReviewDetail>
    saveVersion(draftId: string, content: DraftContent, note: string): Promise<ReviewDetail>
    addNote(draftId: string, text: string): Promise<InternalNote>
    approve(draftId: string): Promise<ReviewDetail>
    documentViewUrl(documentId: string): Promise<{ url: string; expiresAt: string }>
    listCitationSamples(): Promise<CitationSample[]>
    setVerdict(id: string, verdict: 'correct' | 'incorrect', note: string): Promise<CitationSample>
  }
  admin: {
    stats(range: '7d' | '30d'): Promise<StatsOverview>
    listDentists(): Promise<DentistRow[]>
    inviteDentist(input: { name: string; phone: string; specialty: string; city: string }): Promise<DentistRow>
    listLawyers(): Promise<LawyerRow[]>
    inviteLawyer(input: { name: string; email: string; licenseNo: string; firm: string }): Promise<LawyerRow>
    resendInvite(userId: string): Promise<void>
    setActive(userId: string, active: boolean): Promise<void>
    listSources(): Promise<KnowledgeSource[]>
    getVersion(versionId: string): Promise<{ source: KnowledgeSource; version: SourceVersion }>
    listChunks(versionId: string): Promise<KnowledgeChunk[]>
    uploadVersion(input: { sourceId: string | null; newSourceTitle: string; edition: string; effectiveFrom: string | null; fileName: string; extractionMethod: SourceVersion['extractionMethod']; file?: File | null }): Promise<SourceVersion>
    publishVersion(versionId: string): Promise<void>
    archiveVersion(versionId: string): Promise<void>
    reindexVersion(versionId: string): Promise<void>
    listRatings(): Promise<RatingRow[]>
    listFeedback(): Promise<FeedbackRow[]>
    setFeedbackStatus(id: string, status: FeedbackStatus): Promise<void>
  }
}

export class ApiError extends Error {
  constructor(public readonly code: string, message: string) {
    super(message)
  }
}
