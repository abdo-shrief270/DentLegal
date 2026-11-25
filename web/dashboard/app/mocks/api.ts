// In-browser fake backend with synthetic data, used while the Laravel API is being built
// (runtimeConfig.public.mockApi = true). It keeps state in memory, so changes last until the page reloads.
import type { DashboardApi } from '~/types/client'
import { ApiError } from '~/types/client'
import type { AiRunRow, AiRunSummary, DentistRow, KnowledgeSource, LawyerRow, ReviewDetail, ReviewListItem, SessionUser, SourceVersion } from '~/types/api'
import {
  AI_RUNS, buildChunks, buildDaily, buildReviews, CITATION_SAMPLES, DEMO_2FA_CODE, DEMO_INVITES, DEMO_NOW, DEMO_USERS, DENTISTS, FEEDBACK,
  KNOWLEDGE, LAWYERS, RATINGS,
} from './data'

const SESSION_KEY = 'dl-mock-session'
const LATENCY_MS = 250

const wait = (ms = LATENCY_MS) => new Promise<void>((resolve) => setTimeout(resolve, ms))
const clone = <T>(value: T): T => structuredClone(value)

const ISO = /^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}(:\d{2}(\.\d+)?)?([+-]\d{2}:\d{2}|Z)?)?$/

/** The demo data is written around DEMO_NOW; shift every date so the demo always looks current. */
function shiftDates<T>(value: T, offsetMs: number): T {
  if (typeof value === 'string' && ISO.test(value)) {
    const shifted = new Date(new Date(value).getTime() + offsetMs).toISOString()
    return (value.length === 10 ? shifted.slice(0, 10) : shifted) as T
  }
  if (Array.isArray(value)) return value.map((v) => shiftDates(v, offsetMs)) as T
  if (value && typeof value === 'object') {
    return Object.fromEntries(Object.entries(value).map(([k, v]) => [k, shiftDates(v, offsetMs)])) as T
  }
  return value
}

function readSession(): { userId: string | null; pendingUserId: string | null } {
  try {
    return JSON.parse(sessionStorage.getItem(SESSION_KEY) ?? '') as { userId: string | null; pendingUserId: string | null }
  } catch {
    return { userId: null, pendingUserId: null }
  }
}

function writeSession(value: { userId: string | null; pendingUserId: string | null }) {
  try {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(value))
  } catch {
    // Private mode or blocked storage: the session simply won't survive a reload.
  }
}

export function createMockApi(): DashboardApi {
  const offset = Date.now() - new Date(DEMO_NOW).getTime()
  const users: SessionUser[] = clone(DEMO_USERS)
  const reviews: ReviewDetail[] = shiftDates(buildReviews(), offset)
  const samples = clone(CITATION_SAMPLES)
  const dentists: DentistRow[] = shiftDates(clone(DENTISTS), offset)
  const lawyers: LawyerRow[] = clone(LAWYERS)
  const sources: KnowledgeSource[] = shiftDates(clone(KNOWLEDGE), offset)
  const ratings = shiftDates(clone(RATINGS), offset)
  const feedback = shiftDates(clone(FEEDBACK), offset)
  let session = readSession()
  let inviteUser: SessionUser | null = null

  const me = (): SessionUser => {
    const user = users.find((u) => u.id === session.userId)
    if (!user) throw new ApiError('unauthenticated', 'انتهت الجلسة. سجّل الدخول من جديد.')
    return user
  }
  const findReview = (draftId: string): ReviewDetail => {
    const review = reviews.find((r) => r.draftId === draftId)
    if (!review) throw new ApiError('not_found', 'لم يتم العثور على المسودة.')
    return review
  }
  const toListItem = (r: ReviewDetail): ReviewListItem => ({
    draftId: r.draftId, caseCode: r.caseCode, title: r.title, caseType: r.caseType, dentistName: r.dentist.name, risk: r.risk,
    positionStrength: r.positionStrength, submittedAt: r.versions[0]!.createdAt, claimedBy: r.claimedBy,
    claimedAt: r.claimedBy ? r.versions[0]!.createdAt : null, approvedAt: r.approvedAt, status: r.status,
    flagsCount: r.checks.filter((c) => !c.ok).length,
  })
  const findVersion = (versionId: string): { source: KnowledgeSource; version: SourceVersion } => {
    for (const source of sources) {
      const version = source.versions.find((v) => v.id === versionId)
      if (version) return { source, version }
    }
    throw new ApiError('not_found', 'لم يتم العثور على الإصدار.')
  }
  const now = () => new Date().toISOString()

  return {
    auth: {
      async login(email, password) {
        await wait()
        const user = users.find((u) => u.email.toLowerCase() === email.trim().toLowerCase())
        if (!user || password.length < 8) throw new ApiError('invalid_credentials', 'البريد الإلكتروني أو كلمة المرور غير صحيحة.')
        session = { userId: null, pendingUserId: user.id }
        writeSession(session)
        return { twoFactorRequired: true }
      },
      async twoFactorChallenge({ code, recoveryCode }) {
        await wait()
        const user = users.find((u) => u.id === session.pendingUserId)
        if (!user) throw new ApiError('unauthenticated', 'ابدأ بتسجيل الدخول أولًا.')
        const ok = code ? code === DEMO_2FA_CODE : (recoveryCode ?? '').trim().length >= 10
        if (!ok) throw new ApiError('invalid_code', 'رمز التحقق غير صحيح.')
        session = { userId: user.id, pendingUserId: null }
        writeSession(session)
        return clone(user)
      },
      async currentUser() {
        await wait(50)
        return users.find((u) => u.id === session.userId) ?? null
      },
      async logout() {
        await wait(100)
        session = { userId: null, pendingUserId: null }
        writeSession(session)
      },
      async getInvite(token) {
        await wait()
        const invite = DEMO_INVITES[token]
        if (!invite) throw new ApiError('invitation_invalid', 'رابط الدعوة غير صالح أو منتهي الصلاحية.')
        return clone(invite)
      },
      async acceptInvite(token, password) {
        await wait()
        const invite = DEMO_INVITES[token]
        if (!invite) throw new ApiError('invitation_invalid', 'رابط الدعوة غير صالح أو منتهي الصلاحية.')
        if (password.length < 10) throw new ApiError('weak_password', 'كلمة المرور قصيرة.')
        inviteUser = { id: 'L-15', name: invite.name, email: invite.email, role: invite.role, initials: 'عد', subtitle: 'محامٍ مراجِع' }
        return {
          secret: 'JBSW Y3DP EHPK 3PXP',
          otpauthUrl: `otpauth://totp/DentLegal:${invite.email}?secret=JBSWY3DPEHPK3PXP&issuer=DentLegal`,
          recoveryCodes: ['7KQ2-M9XD-4TPA', 'H3VB-8NC2-QW6E', 'R5ZL-2YTU-9PFK', 'C8DM-6JHA-3XNQ', 'W4GE-7SRB-1KVZ', 'P2NT-5LQC-8MYD'],
        }
      },
      async confirmTwoFactor(code) {
        await wait()
        if (code !== DEMO_2FA_CODE || !inviteUser) throw new ApiError('invalid_code', 'رمز التحقق غير صحيح.')
        users.push(inviteUser)
        session = { userId: inviteUser.id, pendingUserId: null }
        writeSession(session)
        return clone(inviteUser)
      },
    },

    lawyer: {
      async listReviews(tab) {
        await wait()
        const userId = me().id
        const rows = reviews.filter((r) =>
          tab === 'unclaimed' ? r.status === 'in_review' && !r.claimedBy
            : tab === 'mine' ? r.status === 'in_review' && r.claimedBy === userId
              : r.status === 'approved')
        return rows.map(toListItem).sort((a, b) => a.submittedAt.localeCompare(b.submittedAt))
      },
      async counts() {
        await wait(80)
        const userId = me().id
        return {
          unclaimed: reviews.filter((r) => r.status === 'in_review' && !r.claimedBy).length,
          mine: reviews.filter((r) => r.status === 'in_review' && r.claimedBy === userId).length,
          approved: reviews.filter((r) => r.status === 'approved').length,
        }
      },
      async claim(draftId) {
        await wait()
        const review = findReview(draftId)
        if (review.claimedBy) throw new ApiError('already_claimed', 'استلم محامٍ آخر هذه القضية.')
        review.claimedBy = me().id
      },
      async getReview(draftId) {
        await wait()
        const review = findReview(draftId)
        if (review.claimedBy && review.claimedBy !== me().id) throw new ApiError('not_found', 'لم يتم العثور على المسودة.')
        return clone(review)
      },
      async saveVersion(draftId, content, note) {
        await wait()
        const review = findReview(draftId)
        if (review.status === 'approved') throw new ApiError('invalid_state', 'الإصدار المعتمد مقفل.')
        const no = review.versions.length + 1
        review.versions.push({ id: `v${no}`, no, authorType: 'lawyer', authorName: me().name, createdAt: now(), note: note || null, locked: false, content: clone(content) })
        return clone(review)
      },
      async addNote(draftId, text) {
        await wait()
        const note = { id: `n${Date.now()}`, author: me().name, createdAt: now(), text }
        findReview(draftId).notes.push(note)
        return clone(note)
      },
      async approve(draftId) {
        await wait(500)
        const review = findReview(draftId)
        review.status = 'approved'
        review.approvedAt = now()
        review.approvedBy = me().name
        review.versions[review.versions.length - 1]!.locked = true
        return clone(review)
      },
      async documentViewUrl(documentId) {
        await wait()
        return { url: `#document-${documentId}`, expiresAt: new Date(Date.now() + 5 * 60_000).toISOString() }
      },
      async listCitationSamples() {
        await wait()
        return clone(samples)
      },
      async setVerdict(id, verdict, note) {
        await wait(150)
        const sample = samples.find((s) => s.id === id)
        if (!sample) throw new ApiError('not_found', 'لم يتم العثور على العنصر.')
        sample.verdict = verdict
        sample.note = note
        return clone(sample)
      },
    },

    admin: {
      async stats(range) {
        await wait()
        const days = range === '7d' ? 7 : 30
        const daily = shiftDates(buildDaily(days), offset)
        const questions = daily.reduce((sum, d) => sum + d.questions, 0)
        const cases = daily.reduce((sum, d) => sum + d.cases, 0)
        return {
          range, activeDentists: range === '7d' ? 6 : 8, invitedDentists: dentists.length,
          questionsAnswered: questions, refusalRate: 0.07, casesCreated: cases, draftsGenerated: cases,
          draftsApproved: Math.max(0, cases - 2),
          usefulRate: { overall: 0.84, consult: 0.86, analysis: 0.83, draft: 0.79 },
          uploadToDraftMinutes: { p50: 4.6, p90: 7.8 }, citationValidity: 0.982, aiCostUsd: range === '7d' ? 11.4 : 41.8, daily,
        }
      },
      async listDentists() {
        await wait()
        return clone(dentists)
      },
      async inviteDentist(input) {
        await wait()
        if (dentists.some((d) => d.phone === input.phone)) throw new ApiError('duplicate', 'هذا الرقم مدعو مسبقًا.')
        const row: DentistRow = { id: `D-${1200 + dentists.length}`, ...input, status: 'invited', joinedAt: now().slice(0, 10), lastActiveAt: null, cases: 0, consultations: 0 }
        dentists.unshift(row)
        return clone(row)
      },
      async listLawyers() {
        await wait()
        return clone(lawyers)
      },
      async inviteLawyer(input) {
        await wait()
        const initials = input.name.replace(/^أ\.\s*/, '').split(' ').map((w) => w.replace(/^ال/, '')[0] ?? '').join('').slice(0, 2)
        const row: LawyerRow = { id: `L-${20 + lawyers.length}`, ...input, initials, status: 'invited', inReview: 0, approvedThisMonth: 0, avgReviewHours: null }
        lawyers.push(row)
        return clone(row)
      },
      async resendInvite() {
        await wait()
      },
      async setActive(userId, active) {
        await wait()
        const row = dentists.find((d) => d.id === userId) ?? lawyers.find((l) => l.id === userId)
        if (row) row.status = active ? 'active' : 'disabled'
      },
      async listSources() {
        await wait()
        return clone(sources)
      },
      async getVersion(versionId) {
        await wait()
        return clone(findVersion(versionId))
      },
      async listChunks(versionId) {
        await wait()
        findVersion(versionId)
        return buildChunks(versionId)
      },
      async uploadVersion(input) {
        await wait(400)
        let source = sources.find((s) => s.id === input.sourceId)
        if (!source) {
          source = { id: `src-${Date.now()}`, code: 'NEW', title: input.newSourceTitle, type: 'regulation', versions: [] }
          sources.push(source)
        }
        const version: SourceVersion = {
          id: `ver-${Date.now()}`, sourceId: source.id, versionLabel: `v${source.versions.length + 1}`, edition: input.edition,
          effectiveFrom: input.effectiveFrom, fileName: input.fileName, extractionMethod: input.extractionMethod, status: 'processing',
          chunkCount: 0, publishedAt: null, updatedAt: now(),
        }
        source.versions.unshift(version)
        // Simulate the ingestion job finishing a few seconds later.
        setTimeout(() => {
          version.status = 'review'
          version.chunkCount = 128
          version.updatedAt = now()
        }, 6000)
        return clone(version)
      },
      async publishVersion(versionId) {
        await wait()
        const { source, version } = findVersion(versionId)
        if (version.status !== 'review') throw new ApiError('invalid_state', 'يمكن نشر الإصدارات قيد المراجعة فقط.')
        for (const v of source.versions) if (v.status === 'active') { v.status = 'archived'; v.updatedAt = now() }
        version.status = 'active'
        version.publishedAt = now()
        version.updatedAt = now()
      },
      async archiveVersion(versionId) {
        await wait()
        const { version } = findVersion(versionId)
        version.status = 'archived'
        version.updatedAt = now()
      },
      async reindexVersion(versionId) {
        await wait()
        const { version } = findVersion(versionId)
        const previous = version.status
        version.status = 'processing'
        setTimeout(() => { version.status = previous; version.updatedAt = now() }, 4000)
      },
      async listRatings() {
        await wait()
        return clone(ratings)
      },
      async listFeedback() {
        await wait()
        return clone(feedback)
      },
      async setFeedbackStatus(id, status) {
        await wait(150)
        const row = feedback.find((f) => f.id === id)
        if (row) row.status = status
      },
      async listAiRuns(f) {
        await wait()
        const problem = (r: AiRunRow) => !!r.errorCode || !!r.refusalCategory || r.fallbackUsed || ['failed', 'refused', 'needs_attention'].includes(r.status)
        const rows = clone(AI_RUNS).filter((r) => (!f.runType || r.runType === f.runType) && (!f.problems || problem(r))
          && (!f.from || r.createdAt >= f.from) && (!f.to || r.createdAt.slice(0, 10) <= f.to))
        const lat = rows.map((r) => r.latencyMs).filter((v): v is number => v !== null).sort((a, b) => a - b)
        const pct = (q: number) => lat.length ? Math.round(lat[Math.min(lat.length - 1, Math.ceil(q * lat.length) - 1)]!) : null
        const cv = rows.map((r) => r.citationValidity).filter((v): v is number => v !== null)
        const sum = (k: 'costUsd' | 'inputTokens' | 'outputTokens' | 'cacheReadTokens') => rows.reduce((t, r) => t + r[k], 0)
        const summary: AiRunSummary = {
          runs: rows.length, shown: rows.length, costUsd: Math.round(sum('costUsd') * 10_000) / 10_000,
          inputTokens: sum('inputTokens'), outputTokens: sum('outputTokens'), cacheReadTokens: sum('cacheReadTokens'),
          latencyP50Ms: pct(0.5), latencyP90Ms: pct(0.9),
          citationValidity: cv.length ? Math.round(cv.reduce((a, b) => a + b, 0) / cv.length * 1000) / 1000 : null,
          fallbacks: rows.filter((r) => r.fallbackUsed).length, errors: rows.filter((r) => r.errorCode).length,
          runTypes: [...new Set(AI_RUNS.map((r) => r.runType))].sort(),
        }
        return { rows, summary }
      },
    },
  }
}

export { DEMO_NOW }
