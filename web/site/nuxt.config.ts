import tailwindcss from '@tailwindcss/vite'

// The preview serves the site under /site/ (NUXT_APP_BASE_URL); production at the domain root.
const base = process.env.NUXT_APP_BASE_URL ?? '/'

// DentLegal marketing site (PROJECT1_PLAN.md §7.3, S9.7): every route pre-rendered to static HTML (`nuxi generate`),
// served by Caddy at <domain>. No tracking, no cookies, no forms that collect personal data.
export default defineNuxtConfig({
  extends: ['../base'],
  compatibilityDate: '2026-11-01',
  devtools: { enabled: false },
  css: ['~/assets/css/main.css'],
  vite: { plugins: [tailwindcss()] },
  features: { inlineStyles: true },
  experimental: { payloadExtraction: false },
  nitro: {
    prerender: { routes: ['/', '/privacy', '/terms', '/support', '/sitemap.xml'], crawlLinks: true, failOnError: true },
  },
  app: {
    head: {
      htmlAttrs: { lang: 'ar', dir: 'rtl' },
      link: [{ rel: 'icon', type: 'image/svg+xml', href: `${base}favicon.svg` }],
      meta: [{ name: 'theme-color', content: '#0a2e3b' }],
    },
  },
  runtimeConfig: {
    public: {
      // Set at build time (NUXT_PUBLIC_*). Empty values hide the link and show a neutral fallback.
      siteUrl: '',
      contactEmail: '',
      whatsapp: '', // international digits only, e.g. 9665XXXXXXXX
      companyName: 'DentLegal',
      appStoreUrl: '', // badges appear once the apps are live (§7.3)
      playStoreUrl: '',
      // false until the client's lawyer approves the privacy notice and terms (S9.2); shows a notice on those pages.
      legalApproved: false,
    },
  },
  typescript: { strict: true },
})
