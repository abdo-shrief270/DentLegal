import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vitest/config'

// Unit tests for plain TypeScript modules (fake API, helpers). Component tests come later.
export default defineConfig({
  resolve: { alias: { '~': fileURLToPath(new URL('./app', import.meta.url)) } },
  test: { include: ['tests/**/*.test.ts'], environment: 'node' },
})
