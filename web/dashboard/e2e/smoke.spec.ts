// Dashboard smoke test (S9.4): the lawyer's critical path and every admin page. Synthetic data only.
import { createHmac } from 'node:crypto'
import { expect, test, type Page } from '@playwright/test'

const external = !!process.env.E2E_BASE_URL
type Role = 'lawyer' | 'admin'

/** RFC 6238 code for a base32 secret (the staging test accounts' authenticator secret). */
function totp(secret: string, now = Date.now()): string {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'
  let bits = ''
  for (const ch of secret.replace(/=+$/, '').toUpperCase()) bits += alphabet.indexOf(ch).toString(2).padStart(5, '0')
  const key = Buffer.from(bits.match(/.{8}/g)!.map((b) => parseInt(b, 2)))
  const counter = Buffer.alloc(8)
  counter.writeBigUInt64BE(BigInt(Math.floor(now / 1000 / 30)))
  const h = createHmac('sha1', key).update(counter).digest()
  const o = h[h.length - 1]! & 0xf
  return String((h.readUInt32BE(o) & 0x7fffffff) % 1_000_000).padStart(6, '0')
}

function account(role: Role) {
  const env = (k: string) => {
    const v = process.env[`E2E_${role.toUpperCase()}_${k}`]
    if (!v) throw new Error(`E2E_${role.toUpperCase()}_${k} is required when E2E_BASE_URL is set`)
    return v
  }
  return { email: env('EMAIL'), password: env('PASSWORD'), code: () => totp(env('TOTP_SECRET')) }
}

function watchErrors(page: Page): string[] {
  const errors: string[] = []
  page.on('pageerror', (e) => errors.push(e.message))
  // The session check before sign-in answers 401 by design; the browser logs it as a failed resource.
  page.on('console', (m) => { if (m.type() === 'error' && !/status of 401/.test(m.text())) errors.push(m.text()) })
  return errors
}

async function signIn(page: Page, role: Role) {
  await page.goto('/login')
  let code = '123456'
  if (external) {
    const a = account(role)
    await page.getByLabel('البريد الإلكتروني').fill(a.email)
    await page.locator('#password').fill(a.password)
    code = a.code()
  } else {
    await page.getByRole('button', { name: role === 'lawyer' ? 'محامية: أ. سارة' : 'الإدارة: م. نورة' }).click()
  }
  await page.getByRole('button', { name: 'دخول', exact: true }).click()
  await page.waitForURL('**/two-factor')
  await page.locator('input').first().click()
  await page.keyboard.type(code)
  await page.waitForURL(role === 'lawyer' ? '**/lawyer' : '**/admin')
}

test('lawyer: claim → edit → save a version → approve', async ({ page }) => {
  const errors = watchErrors(page)
  await signIn(page, 'lawyer')

  // Claim the first case waiting in the queue.
  await page.getByRole('tab', { name: /^بانتظار الاستلام/ }).click()
  const row = page.locator('tbody tr').filter({ has: page.getByRole('button', { name: 'استلام' }) }).first()
  await expect(row, 'the queue needs one unclaimed case (staging: run the smoke-test seeder)').toBeVisible()
  const caseCode = (await row.locator('.mono').first().textContent())?.trim() ?? ''
  expect(caseCode).toMatch(/^DL-\d{4}-\d{4}$/)
  await row.getByRole('button', { name: 'استلام' }).click()
  await page.getByRole('alertdialog').getByRole('button', { name: 'استلام' }).click()
  await page.waitForURL('**/lawyer/reviews/**')
  await expect(page.getByText('قيد المراجعة').first()).toBeVisible()

  // Edit the intro and save it as a new version.
  await page.getByRole('button', { name: 'تحرير المسودة' }).click()
  const intro = page.getByRole('textbox', { name: 'المقدمة' })
  const added = ' (تعديل اختبار آلي)'
  await intro.fill(`${await intro.inputValue()}${added}`)
  await page.getByRole('button', { name: 'حفظ كإصدار' }).click()
  await page.getByRole('dialog', { name: 'حفظ كإصدار جديد' }).getByRole('textbox').fill('اختبار الدخان')
  await page.getByRole('dialog', { name: 'حفظ كإصدار جديد' }).getByRole('button', { name: 'حفظ', exact: true }).click()
  await expect(page.getByRole('button', { name: 'تحرير المسودة' })).toBeVisible()
  await expect(page.getByText(added.trim()).first()).toBeVisible()

  // Approve: the checkbox is required, then the version is locked.
  await page.getByRole('button', { name: 'اعتماد الرد' }).click()
  const dialog = page.getByRole('dialog', { name: 'اعتماد الرد' })
  await expect(dialog.getByRole('button', { name: 'اعتماد وإرسال للطبيب' })).toBeDisabled()
  await dialog.getByText('راجعت المسودة ومصادرها وأعتمدها.').click()
  const warning = dialog.locator('.alert')
  if (await warning.isVisible()) await expect(warning).toContainText('غير')
  await dialog.getByRole('button', { name: 'اعتماد وإرسال للطبيب' }).click()
  await expect(page.getByText('الإصدار المعتمد مقفل', { exact: false })).toBeVisible()
  await expect(page.getByText('معتمد', { exact: true }).first()).toBeVisible()

  // The case moves to the approved tab.
  await page.getByRole('button', { name: 'القائمة' }).click()
  await page.getByRole('tab', { name: /^معتمدة/ }).click()
  await expect(page.locator('tbody').getByText(caseCode)).toBeVisible()

  expect(errors).toEqual([])
})

test('admin: every page loads', async ({ page }) => {
  const errors = watchErrors(page)
  await signIn(page, 'admin')
  const pages: [string, string][] = [
    ['الأطباء', '/admin/dentists'],
    ['المحامون', '/admin/lawyers'],
    ['قاعدة المعرفة', '/admin/knowledge'],
    ['التقييمات', '/admin/ratings'],
    ['الملاحظات', '/admin/feedback'],
    ['سجل الذكاء الاصطناعي', '/admin/ai-runs'],
    ['نظرة عامة', '/admin'],
  ]
  for (const [label, path] of pages) {
    await page.getByRole('link', { name: label }).click()
    await page.waitForURL(`**${path}`)
    await expect(page.locator('h1, .topbar h1, header').first()).toBeVisible()
    await expect(page.getByRole('alert').filter({ hasText: /تعذّر|خطأ/ })).toHaveCount(0)
  }
  await page.goto('/lawyer')
  await expect(page).not.toHaveURL(/\/lawyer$/) // an admin is sent back to the admin area
  expect(errors).toEqual([])
})

test('unauthenticated visitors are sent to the login page', async ({ page }) => {
  await page.goto('/admin/ai-runs')
  await page.waitForURL('**/login**')
  await expect(page.getByRole('heading', { name: 'تسجيل الدخول' })).toBeVisible()
})
