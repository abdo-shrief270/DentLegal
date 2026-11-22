// Arabic date display: Gregorian and Hijri (Umm al-Qura) together, as the PRD requires.
// Pure functions (no Vue state) so they can be unit-tested and reused by the landing page.

const gregorian = new Intl.DateTimeFormat('ar-SA-u-ca-gregory-nu-latn', { day: 'numeric', month: 'long', year: 'numeric' })
const gregorianShort = new Intl.DateTimeFormat('ar-SA-u-ca-gregory-nu-latn', { day: 'numeric', month: 'short', year: 'numeric' })
const hijri = new Intl.DateTimeFormat('ar-SA-u-ca-islamic-umalqura-nu-latn', { day: 'numeric', month: 'long', year: 'numeric' })
const time = new Intl.DateTimeFormat('ar-SA-u-nu-latn', { hour: 'numeric', minute: '2-digit' })

function toDate(value: string | Date): Date {
  return value instanceof Date ? value : new Date(value)
}

/** «29 أكتوبر 2026» */
export function formatGregorian(value: string | Date, short = false): string {
  return (short ? gregorianShort : gregorian).format(toDate(value))
}

/** «18 جمادى الأولى 1448 هـ» */
export function formatHijri(value: string | Date): string {
  return hijri.format(toDate(value))
}

/** «29 أكتوبر 2026 · 18 جمادى الأولى 1448 هـ» */
export function formatBoth(value: string | Date): string {
  return `${formatGregorian(value)} · ${formatHijri(value)}`
}

/** «10:00 ص» */
export function formatTime(value: string | Date): string {
  return time.format(toDate(value))
}

/** «منذ 3 ساعات», «منذ يومين», «اليوم» */
export function formatRelative(value: string | Date, now: Date = new Date()): string {
  const minutes = Math.round((now.getTime() - toDate(value).getTime()) / 60000)
  const rtf = new Intl.RelativeTimeFormat('ar', { numeric: 'auto' })
  if (Math.abs(minutes) < 60) return rtf.format(-minutes, 'minute')
  const hours = Math.round(minutes / 60)
  if (Math.abs(hours) < 24) return rtf.format(-hours, 'hour')
  return rtf.format(-Math.round(hours / 24), 'day')
}

export function useDates() {
  return { formatGregorian, formatHijri, formatBoth, formatTime, formatRelative }
}
