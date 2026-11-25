// Serves the generated SPA (.output/public) for the smoke test: static files, and 200.html for any other path.
import { createServer } from 'node:http'
import { readFile, stat } from 'node:fs/promises'
import { extname, join, normalize } from 'node:path'

const root = join(import.meta.dirname, '..', '.output', 'public')
const port = Number(process.env.PORT ?? 4173)
const types = { '.html': 'text/html; charset=utf-8', '.js': 'text/javascript', '.css': 'text/css', '.svg': 'image/svg+xml', '.png': 'image/png', '.woff2': 'font/woff2', '.json': 'application/json', '.ico': 'image/x-icon' }

async function file(path) {
  try {
    const s = await stat(path)
    return s.isFile() ? path : null
  } catch {
    return null
  }
}

createServer(async (req, res) => {
  const url = decodeURIComponent(new URL(req.url ?? '/', 'http://localhost').pathname)
  const target = normalize(join(root, url))
  const found = target.startsWith(root) ? await file(target) : null
  const path = found ?? (await file(join(root, '200.html'))) ?? join(root, 'index.html')
  res.writeHead(200, { 'content-type': types[extname(path)] ?? 'application/octet-stream' })
  res.end(await readFile(path))
}).listen(port, '127.0.0.1', () => console.log(`serving ${root} on http://127.0.0.1:${port}`))
