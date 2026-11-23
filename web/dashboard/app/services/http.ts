// Laravel API client (PROJECT1_PLAN.md §4.2). Sanctum SPA cookie mode: the session lives in an httpOnly
// cookie; $fetch sends it with `credentials: 'include'` and Laravel's XSRF token header.
// Auth and lawyer calls match the Laravel routes (backend/routes); the admin calls follow the plan's contract.
import type { CitationRef, InternalNote, ReviewDetail, ReviewListItem, Role, SessionUser } from '~/types/api'
import type { DashboardApi, TwoFactorSetup } from '~/types/client'
import { ApiError } from '~/types/client'

function xsrfToken(): string | undefined {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]!) : undefined
}

export function createHttpApi(baseURL: string): DashboardApi {
  const request = async <T>(path: string, options: { method?: 'GET' | 'POST' | 'PATCH' | 'DELETE'; body?: unknown; query?: Record<string, string> } = {}): Promise<T> => {
    try {
      const token = xsrfToken()
      return await $fetch<T>(path, {
        baseURL, credentials: 'include', method: options.method ?? 'GET', body: options.body as Record<string, unknown> | undefined, query: options.query,
        headers: { Accept: 'application/json', ...(token ? { 'X-XSRF-TOKEN': token } : {}) },
      })
    } catch (error: unknown) {
      const data = (error as { data?: { error?: { code?: string; message?: string } } }).data
      throw new ApiError(data?.error?.code ?? 'network_error', data?.error?.message ?? 'تعذّر الاتصال بالخادم. حاول مرة أخرى.')
    }
  }
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  // Shapes returned by the Laravel dashboard auth routes (backend/routes/web.php).
  interface ApiSessionUser { id: string; name: string | null; email: string; role: Role; two_factor: { enabled: boolean; passed: boolean } }
  interface ApiTwoFactorSetup { secret: string; otpauth_uri: string; recovery_codes: string[] }
  const toSessionUser = (u: ApiSessionUser): SessionUser => {
    const name = u.name ?? u.email
    const initials = name.replace(/^(أ|م|د)\.\s*/, '').split(/\s+/).map((w) => w.replace(/^ال/, '')[0] ?? '').join('').slice(0, 2)
    return { id: u.id, name, email: u.email, role: u.role, initials, subtitle: u.role === 'admin' ? 'إدارة المنصة' : 'محامٍ مراجِع' }
  }
  // Lawyer and admin payloads are snake_case (Laravel); the dashboard types are camelCase.
  const camel = (v: unknown): unknown => Array.isArray(v) ? v.map(camel)
    : v && typeof v === 'object' ? Object.fromEntries(Object.entries(v).map(([k, x]) => [k.replace(/_([a-z])/g, (_, c: string) => c.toUpperCase()), camel(x)]))
      : v
  const snake = (v: unknown): unknown => Array.isArray(v) ? v.map(snake)
    : v && typeof v === 'object' ? Object.fromEntries(Object.entries(v).map(([k, x]) => [k.replace(/[A-Z]/g, (c) => `_${c.toLowerCase()}`), snake(x)]))
      : v
  // A citation's check result sits under `verification` in the API; the editor reads `reason` on the citation.
  const withReasons = (detail: ReviewDetail): ReviewDetail => {
    for (const version of detail.versions) {
      for (const p of [version.content.intro, ...version.content.sections.flatMap((s) => s.paragraphs)]) {
        for (const c of p.citations as (CitationRef & { verification?: { reason?: CitationRef['reason'] | null } })[]) c.reason = c.verification?.reason ?? undefined
      }
    }
    return detail
  }
  const review = async (promise: Promise<{ data: unknown }>) => withReasons(camel(await unwrap(promise)) as ReviewDetail)
  const toSetup = (s: ApiTwoFactorSetup): TwoFactorSetup => ({
    secret: s.secret.replace(/(.{4})/g, '$1 ').trim(), otpauthUrl: s.otpauth_uri, recoveryCodes: s.recovery_codes,
  })

  return {
    auth: {
      async login(email, password) {
        await request('/sanctum/csrf-cookie')
        const res = await unwrap(request<{ data: { two_factor_required: boolean; enrolment_required: boolean } }>('/login', { method: 'POST', body: { email, password } }))
        return { twoFactorRequired: res.two_factor_required }
      },
      twoFactorChallenge: async (input) => toSessionUser(await unwrap(request<{ data: ApiSessionUser }>('/two-factor-challenge', { method: 'POST', body: { code: input.code, recovery_code: input.recoveryCode } }))),
      async currentUser() {
        try {
          return toSessionUser(await unwrap(request<{ data: ApiSessionUser }>('/user')))
        } catch (e) {
          if (e instanceof ApiError && e.code === 'unauthenticated') return null
          throw e
        }
      },
      logout: () => request('/logout', { method: 'POST' }),
      getInvite: (token) => unwrap(request(`/invitations/${token}`)),
      async acceptInvite(token, password) {
        await request('/sanctum/csrf-cookie')
        return toSetup(await unwrap(request<{ data: ApiTwoFactorSetup }>(`/invitations/${token}/accept`, { method: 'POST', body: { password } })))
      },
      confirmTwoFactor: async (code) => toSessionUser(await unwrap(request<{ data: ApiSessionUser }>('/user/confirmed-two-factor-authentication', { method: 'POST', body: { code } }))),
    },
    lawyer: {
      listReviews: async (tab) => camel(await unwrap(request<{ data: unknown }>('/api/v1/lawyer/reviews', { query: { tab } }))) as ReviewListItem[],
      counts: () => unwrap(request('/api/v1/lawyer/reviews/counts')),
      claim: async (id) => { await request(`/api/v1/lawyer/reviews/${id}/claim`, { method: 'POST' }) },
      getReview: (id) => review(request(`/api/v1/lawyer/reviews/${id}`)),
      saveVersion: (id, content, note) => review(request(`/api/v1/lawyer/reviews/${id}/versions`, { method: 'POST', body: { content: snake(content), note: note || null } })),
      addNote: async (id, text) => camel(await unwrap(request<{ data: unknown }>(`/api/v1/lawyer/reviews/${id}/notes`, { method: 'POST', body: { text } }))) as InternalNote,
      // The approve dialog has the lawyer tick «أؤكد…» with the quality checks in view, so warnings count as confirmed.
      approve: (id) => review(request(`/api/v1/lawyer/reviews/${id}/approve`, { method: 'POST', body: { confirm_warnings: true } })),
      documentViewUrl: (id) => unwrap(request(`/api/v1/lawyer/documents/${id}/view-url`)),
      listCitationSamples: () => unwrap(request('/api/v1/lawyer/citation-samples')),
      setVerdict: (id, verdict, note) => unwrap(request(`/api/v1/lawyer/citation-samples/${id}/verdict`, { method: 'POST', body: { verdict, note } })),
    },
    admin: {
      stats: (range) => unwrap(request('/api/v1/admin/stats/overview', { query: { range } })),
      listDentists: () => unwrap(request('/api/v1/admin/dentists')),
      inviteDentist: (input) => unwrap(request('/api/v1/admin/dentists', { method: 'POST', body: input })),
      listLawyers: () => unwrap(request('/api/v1/admin/lawyers')),
      inviteLawyer: (input) => unwrap(request('/api/v1/admin/lawyers', { method: 'POST', body: input })),
      resendInvite: (id) => request(`/api/v1/admin/users/${id}/resend-invite`, { method: 'POST' }),
      setActive: (id, active) => request(`/api/v1/admin/users/${id}/${active ? 'activate' : 'deactivate'}`, { method: 'POST' }),
      listSources: () => unwrap(request('/api/v1/admin/knowledge/sources')),
      getVersion: (id) => unwrap(request(`/api/v1/admin/knowledge/versions/${id}`)),
      listChunks: (id) => unwrap(request(`/api/v1/admin/knowledge/versions/${id}/chunks`)),
      uploadVersion: (input) => unwrap(request(`/api/v1/admin/knowledge/sources/${input.sourceId ?? 'new'}/versions`, { method: 'POST', body: input })),
      publishVersion: (id) => request(`/api/v1/admin/knowledge/versions/${id}/publish`, { method: 'POST' }),
      archiveVersion: (id) => request(`/api/v1/admin/knowledge/versions/${id}/archive`, { method: 'POST' }),
      reindexVersion: (id) => request(`/api/v1/admin/knowledge/versions/${id}/reindex`, { method: 'POST' }),
      listRatings: () => unwrap(request('/api/v1/admin/ratings')),
      listFeedback: () => unwrap(request('/api/v1/admin/feedback')),
      setFeedbackStatus: (id, status) => request(`/api/v1/admin/feedback/${id}`, { method: 'PATCH', body: { status } }),
    },
  }
}
