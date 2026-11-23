# DentLegal web

pnpm workspace for the web apps of Project 1 (see `PROJECT1_PLAN.md` §7).

| Package | What it is |
|---|---|
| `base/` | Shared Nuxt layer: brand tokens from the approved prototype, IBM Plex Sans Arabic, Hijri + Gregorian date helpers |
| `dashboard/` | Lawyer review + admin dashboard (Nuxt 4 in SPA mode, PrimeVue 4, Tailwind 4, Pinia), served at `portal.<domain>` |
| `site/` | Marketing site (S9.7): `/`, `/privacy`, `/terms`, `/support`, pre-rendered to static HTML, Arabic RTL, no tracking, served at `<domain>` |

## Run it

```bash
cd web
pnpm install
pnpm dev          # http://localhost:3000
```

```bash
pnpm typecheck    # vue-tsc; fails on unknown components
pnpm test         # vitest (date helpers, fake API flows)
pnpm build        # static site in dashboard/.output/public
```

```bash
pnpm site:dev     # marketing site, http://localhost:3000
pnpm site:build   # pre-rendered pages in site/.output/public (then `pnpm test` also checks them)
```

The marketing site reads its contact details and store links at build time; empty values hide the link:

| Variable | Use |
|---|---|
| `NUXT_PUBLIC_SITE_URL` | `https://<domain>`: canonical URLs, sitemap, absolute Open Graph image |
| `NUXT_PUBLIC_CONTACT_EMAIL`, `NUXT_PUBLIC_WHATSAPP` | «تواصل معنا» and «الدعم» |
| `NUXT_PUBLIC_COMPANY_NAME` | Legal entity in the footer and legal pages (default DentLegal) |
| `NUXT_PUBLIC_APP_STORE_URL`, `NUXT_PUBLIC_PLAY_STORE_URL` | Store badges, shown once the apps are live |
| `NUXT_PUBLIC_LEGAL_APPROVED` | `true` once the client's lawyer approves the privacy notice and terms; until then those pages say they are under legal review |

The privacy notice and terms are drafts written from what the system actually does (PROJECT1_PLAN.md §9): the client's lawyer finalises them (S9.2).

## Fake data mode

The Laravel API is not built yet, so the dashboard runs against an in-browser **fake API** with synthetic data
(`dashboard/app/mocks/`). It is switched on by `runtimeConfig.public.mockApi` (default `true`); every screen shows a
«بيانات تجريبية» badge while it is on. No real patient data is used anywhere.

Demo sign-in (fake mode only):

| Role | Email | Password | 2-step code |
|---|---|---|---|
| Lawyer | `sara@dentlegal.test` | any, 8+ characters | `123456` |
| Admin | `noura@dentlegal.test` | any, 8+ characters | `123456` |

Invitation flow demo: `/invite/demo-lawyer`.

When the API exists, set `NUXT_PUBLIC_MOCK_API=false` and `NUXT_PUBLIC_API_BASE=https://api.<domain>`; the same
screens then use `dashboard/app/services/http.ts` (Sanctum cookie session + Fortify two-factor).

## Structure (dashboard)

```
app/
  pages/        login, two-factor, invite/[token], lawyer/*, admin/*
  layouts/      auth.vue, panel.vue (sidebar from the prototype)
  components/   ui/ (icons, badges, dates, KPI cards), review/ (case pane, draft editor, sources pane)
  stores/       useAuthStore, useReviewStore
  composables/  useApi (fake or real backend)
  mocks/        fake API + synthetic data (adapted from site/prototype/js/data.js)
  services/     Laravel API client
  types/        API types (to be generated from OpenAPI later)
```
