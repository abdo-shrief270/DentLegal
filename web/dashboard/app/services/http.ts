// Laravel API client (PROJECT1_PLAN.md §4.2). Sanctum SPA cookie mode: the session lives in an httpOnly
// cookie; $fetch sends it with `credentials: 'include'` and Laravel's XSRF token header.
// Not exercised yet: the backend is built later. Paths and shapes follow the plan's API contract.
import type { DashboardApi } from '~/types/client'
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

  return {
    auth: {
      async login(email, password) {
        await request('/sanctum/csrf-cookie')
        return request('/login', { method: 'POST', body: { email, password } })
      },
      twoFactorChallenge: (input) => request('/two-factor-challenge', { method: 'POST', body: { code: input.code, recovery_code: input.recoveryCode } }),
      currentUser: () => unwrap(request('/api/v1/me')),
      logout: () => request('/logout', { method: 'POST' }),
      getInvite: (token) => unwrap(request(`/api/v1/invitations/${token}`)),
      acceptInvite: (token, password) => unwrap(request(`/invitations/${token}/accept`, { method: 'POST', body: { password } })),
      confirmTwoFactor: (code) => unwrap(request('/user/confirmed-two-factor-authentication', { method: 'POST', body: { code } })),
    },
    lawyer: {
      listReviews: (tab) => unwrap(request('/api/v1/lawyer/reviews', { query: { tab } })),
      counts: () => unwrap(request('/api/v1/lawyer/reviews/counts')),
      claim: (id) => request(`/api/v1/lawyer/reviews/${id}/claim`, { method: 'POST' }),
      getReview: (id) => unwrap(request(`/api/v1/lawyer/reviews/${id}`)),
      saveVersion: (id, content, note) => unwrap(request(`/api/v1/lawyer/reviews/${id}/versions`, { method: 'POST', body: { content, note } })),
      addNote: (id, text) => unwrap(request(`/api/v1/lawyer/reviews/${id}/notes`, { method: 'POST', body: { text } })),
      approve: (id) => unwrap(request(`/api/v1/lawyer/reviews/${id}/approve`, { method: 'POST' })),
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
