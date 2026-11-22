// Route guard: sign-in required everywhere except the auth pages; /lawyer and /admin are role-restricted.
// The API enforces the same rules with Laravel policies — this guard only keeps the UI tidy.
const PUBLIC_PREFIXES = ['/login', '/two-factor', '/invite']

export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore()
  await auth.load()

  const isPublic = PUBLIC_PREFIXES.some((prefix) => to.path.startsWith(prefix))
  if (isPublic) return auth.user && !to.path.startsWith('/invite') ? navigateTo(auth.homePath) : undefined
  if (!auth.user) return navigateTo('/login')

  if (to.path === '/') return navigateTo(auth.homePath)
  if (to.path.startsWith('/admin') && auth.role !== 'admin') return navigateTo(auth.homePath)
  if (to.path.startsWith('/lawyer') && auth.role !== 'lawyer') return navigateTo(auth.homePath)
})
