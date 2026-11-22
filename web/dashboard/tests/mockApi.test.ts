import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMockApi } from '~/mocks/api'
import type { DashboardApi } from '~/types/client'

describe('fake API', () => {
  let api: DashboardApi

  beforeEach(async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    api = createMockApi()
  })

  async function signIn(email: string) {
    await api.auth.login(email, 'Demo@12345')
    return api.auth.twoFactorChallenge({ code: '123456' })
  }

  it('requires the second factor and rejects a wrong code', async () => {
    await api.auth.login('sara@dentlegal.test', 'Demo@12345')
    await expect(api.auth.twoFactorChallenge({ code: '000000' })).rejects.toMatchObject({ code: 'invalid_code' })
    await expect(api.auth.twoFactorChallenge({ code: '123456' })).resolves.toMatchObject({ role: 'lawyer' })
  })

  it('lets a lawyer claim, edit, save a version and approve', async () => {
    await signIn('sara@dentlegal.test')
    const [unclaimed] = await api.lawyer.listReviews('unclaimed')
    expect(unclaimed).toBeDefined()
    await api.lawyer.claim(unclaimed!.draftId)
    await expect(api.lawyer.claim(unclaimed!.draftId)).rejects.toMatchObject({ code: 'already_claimed' })

    const review = await api.lawyer.getReview(unclaimed!.draftId)
    const content = structuredClone(review.versions[0]!.content)
    content.sections[0]!.paragraphs[0]!.text = 'نص معدل'
    const saved = await api.lawyer.saveVersion(review.draftId, content, 'تعديل')
    expect(saved.versions).toHaveLength(2)
    expect(saved.versions[1]).toMatchObject({ authorType: 'lawyer', no: 2 })

    const approved = await api.lawyer.approve(review.draftId)
    expect(approved.status).toBe('approved')
    expect(approved.versions.at(-1)!.locked).toBe(true)
    await expect(api.lawyer.saveVersion(review.draftId, content, '')).rejects.toMatchObject({ code: 'invalid_state' })
  })

  it('publishing a reviewed version archives the previous active one', async () => {
    await signIn('noura@dentlegal.test')
    const version = await api.admin.uploadVersion({
      sourceId: 'src-hppl', newSourceTitle: '', edition: 'الإصدار الرابع', effectiveFrom: null, fileName: 'law.pdf', extractionMethod: 'auto',
    })
    expect(version.status).toBe('processing')
    await vi.advanceTimersByTimeAsync(6500)
    await api.admin.publishVersion(version.id)
    const source = (await api.admin.listSources()).find((s) => s.id === 'src-hppl')!
    expect(source.versions.filter((v) => v.status === 'active').map((v) => v.id)).toEqual([version.id])
    expect(source.versions.find((v) => v.id === 'ver-hppl-3')!.status).toBe('archived')
  })
})
