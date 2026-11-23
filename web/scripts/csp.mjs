// Adds a strict Content-Security-Policy <meta> to every pre-rendered HTML page (PROJECT1_PLAN.md S9.3).
// Inline scripts (Nuxt's boot script, the import map) are allowed by their SHA-256 hash, so script-src needs no
// 'unsafe-inline'. Styles keep 'unsafe-inline' (PrimeVue injects component styles at run time).
// frame-ancestors cannot be set in a <meta>; Caddy sends it as a header.
//   node scripts/csp.mjs <dir> [extra connect-src origin]
import { createHash } from 'node:crypto'
import { readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'

const [dir, connect = ''] = process.argv.slice(2)
if (!dir) throw new Error('usage: node scripts/csp.mjs <dir> [connect-src origin]')

const htmlFiles = (d) =>
  readdirSync(d).flatMap((name) => {
    const p = join(d, name)
    return statSync(p).isDirectory() ? htmlFiles(p) : p.endsWith('.html') ? [p] : []
  })

let pages = 0
for (const file of htmlFiles(dir)) {
  let html = readFileSync(file, 'utf8').replace(/<meta http-equiv="Content-Security-Policy"[^>]*>/, '')
  const hashes = [...html.matchAll(/<script(?![^>]*\bsrc=)(?![^>]*type="application\/json")[^>]*>([\s\S]*?)<\/script>/g)]
    .map(([, body]) => `'sha256-${createHash('sha256').update(body, 'utf8').digest('base64')}'`)
  const policy = [
    "default-src 'self'",
    `script-src 'self' ${[...new Set(hashes)].join(' ')}`.trim(),
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data:",
    "font-src 'self' data:",
    `connect-src 'self'${connect ? ` ${connect}` : ''}`,
    "object-src 'none'",
    "base-uri 'self'",
    "form-action 'self'",
  ].join('; ')
  html = html.replace(/<head>/, `<head><meta http-equiv="Content-Security-Policy" content="${policy}">`)
  writeFileSync(file, html)
  pages++
}
console.log(`CSP added to ${pages} page(s) in ${dir}`)
