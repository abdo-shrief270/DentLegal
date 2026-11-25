// Dashboard smoke test (PROJECT1_PLAN.md §7.0, S9.4): login + two-factor → claim → edit → save version → approve.
// Without E2E_BASE_URL it runs against the generated build with the in-browser fake API (CI, every change).
// With E2E_BASE_URL it runs against a real deployment; see e2e/README.md for the accounts it needs.
import { defineConfig, devices } from '@playwright/test'

const external = process.env.E2E_BASE_URL

export default defineConfig({
  testDir: 'e2e',
  timeout: 60_000,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
  use: {
    baseURL: external ?? 'http://127.0.0.1:4173',
    locale: 'ar-SA',
    timezoneId: 'Asia/Riyadh',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } },
  ],
  webServer: external ? undefined : { command: 'node e2e/serve.mjs', url: 'http://127.0.0.1:4173', reuseExistingServer: !process.env.CI },
})
