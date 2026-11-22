import { describe, expect, it } from 'vitest'
import { formatBoth, formatGregorian, formatHijri, formatRelative } from '../app/composables/useDates'

describe('useDates', () => {
  const day = '2026-10-29T12:00:00Z'

  it('formats Gregorian dates in Arabic with Latin digits', () => {
    expect(formatGregorian(day)).toBe('29 أكتوبر 2026')
  })

  it('formats Hijri dates with the Umm al-Qura calendar', () => {
    expect(formatHijri(day)).toContain('18 جمادى الأولى 1448')
  })

  it('joins both calendars', () => {
    expect(formatBoth(day)).toMatch(/^29 أكتوبر 2026 · 18 جمادى الأولى 1448/)
  })

  it('formats relative times', () => {
    const now = new Date('2026-10-29T15:00:00Z')
    expect(formatRelative('2026-10-29T12:00:00Z', now)).toContain('3')
  })
})
