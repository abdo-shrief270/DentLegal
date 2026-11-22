import tailwindcss from '@tailwindcss/vite'
import { DentLegalPreset } from './theme/preset'
import { arLocale } from './theme/locale-ar'

// DentLegal web dashboard: lawyer review + admin. SPA only (no SEO, no SSR server to run),
// built to static files and served from portal.<domain>.
export default defineNuxtConfig({
  extends: ['../base'],
  ssr: false,
  compatibilityDate: '2026-11-01',
  devtools: { enabled: false },
  modules: ['@primevue/nuxt-module', '@pinia/nuxt'],
  // Components are named by file name only (components/ui/AppIcon.vue → <AppIcon>).
  components: [{ path: '~/components', pathPrefix: false }],
  css: ['~/assets/css/main.css'],
  vite: { plugins: [tailwindcss()] },
  app: {
    head: {
      htmlAttrs: { lang: 'ar', dir: 'rtl' },
      title: 'DentLegal — لوحة التحكم',
      meta: [{ name: 'robots', content: 'noindex, nofollow' }],
      link: [{ rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' }],
    },
  },
  runtimeConfig: {
    public: {
      // Base URL of the Laravel API. Empty while the backend does not exist yet.
      apiBase: '',
      // true = use the in-browser fake API in app/mocks (synthetic data only).
      mockApi: true,
    },
  },
  primevue: {
    options: {
      ripple: false,
      locale: arLocale,
      theme: {
        preset: DentLegalPreset,
        options: {
          darkModeSelector: false,
          cssLayer: { name: 'primevue', order: 'theme, base, primevue' },
        },
      },
    },
  },
  typescript: {
    strict: true,
    // Fail the type check when a template uses a component that does not exist.
    tsConfig: { vueCompilerOptions: { checkUnknownComponents: true } },
  },
})
