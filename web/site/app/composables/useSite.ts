/** Contact details and store links from the build-time config; empty values fall back to neutral text. */
export function useSite() {
  const c = useRuntimeConfig().public
  const whatsappUrl = c.whatsapp ? `https://wa.me/${String(c.whatsapp).replace(/\D/g, '')}` : ''
  return {
    siteUrl: String(c.siteUrl).replace(/\/$/, ''),
    email: String(c.contactEmail),
    whatsappUrl,
    company: String(c.companyName),
    appStoreUrl: String(c.appStoreUrl),
    playStoreUrl: String(c.playStoreUrl),
    legalApproved: Boolean(c.legalApproved),
  }
}

/** Arabic title + description, Open Graph and canonical URL for one page. */
export function usePageMeta(title: string, description: string, path: string) {
  const { siteUrl } = useSite()
  const full = `${title} | DentLegal`
  useSeoMeta({
    title: full,
    description,
    ogTitle: full,
    ogDescription: description,
    ogType: 'website',
    ogLocale: 'ar_SA',
    ogImage: siteUrl ? `${siteUrl}/og.png` : `${useRuntimeConfig().app.baseURL}og.png`,
    twitterCard: 'summary_large_image',
  })
  useHead({ link: siteUrl ? [{ rel: 'canonical', href: `${siteUrl}${path}` }] : [] })
}
