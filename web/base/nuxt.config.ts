// Shared Nuxt layer for the DentLegal web apps (dashboard now, landing page later):
// brand tokens from the approved prototype, the Arabic font and date helpers.
import { fileURLToPath } from 'node:url'

const here = (path: string) => fileURLToPath(new URL(path, import.meta.url))

export default defineNuxtConfig({
  css: [
    '@fontsource/ibm-plex-sans-arabic/400.css',
    '@fontsource/ibm-plex-sans-arabic/500.css',
    '@fontsource/ibm-plex-sans-arabic/600.css',
    '@fontsource/ibm-plex-sans-arabic/700.css',
    here('./app/assets/css/tokens.css'),
  ],
})
