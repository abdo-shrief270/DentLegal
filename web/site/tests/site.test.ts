// Checks the pre-rendered site (run `nuxi generate` first; CI does). PROJECT1_PLAN.md §7.3.
import { existsSync, readFileSync } from 'node:fs'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'

const out = join(__dirname, '..', '.output', 'public')
const page = (route: string) => readFileSync(join(out, route, 'index.html'), 'utf8')
const routes = ['', 'privacy', 'terms', 'support']

describe.skipIf(!existsSync(out))('pre-rendered site', () => {
  it.each(routes)('/%s is Arabic, right-to-left, titled and described', (route) => {
    const html = page(route)
    expect(html).toMatch(/<html[^>]*lang="ar"/)
    expect(html).toMatch(/<html[^>]*dir="rtl"/)
    expect(html).toMatch(/<title>[^<]+\| DentLegal<\/title>/)
    expect(html).toMatch(/<meta name="description" content="[^"]{30,}"/)
    expect(html).toContain('property="og:image"')
  })

  it('has no beta or pilot wording (App Store, §9.10)', () => {
    for (const route of routes) expect(page(route)).not.toMatch(/تجريبي|تجريبية|beta|pilot/i)
  })

  it('loads no tracking or analytics', () => {
    for (const route of routes) expect(page(route)).not.toMatch(/googletagmanager|google-analytics|gtag\(|facebook\.net|hotjar/i)
  })

  it('shows the legal-review notice until the texts are approved', () => {
    expect(page('privacy')).toContain('قيد المراجعة القانونية')
    expect(page('terms')).toContain('قيد المراجعة القانونية')
  })

  it('publishes a sitemap and robots.txt', () => {
    const sitemap = readFileSync(join(out, 'sitemap.xml'), 'utf8')
    for (const route of ['/privacy', '/terms', '/support']) expect(sitemap).toContain(`${route}</loc>`)
    expect(readFileSync(join(out, 'robots.txt'), 'utf8')).toContain('Sitemap:')
  })
})
