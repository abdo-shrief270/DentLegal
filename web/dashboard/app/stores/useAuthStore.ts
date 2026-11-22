import { defineStore } from 'pinia'
import type { Role, SessionUser } from '~/types/api'

/** Session state for the dashboard. The session itself is an httpOnly cookie (Sanctum); this store only mirrors the user. */
export const useAuthStore = defineStore('auth', () => {
  const api = useApi()
  const user = ref<SessionUser | null>(null)
  const loaded = ref(false)

  const role = computed<Role | null>(() => user.value?.role ?? null)
  const homePath = computed(() => (role.value === 'admin' ? '/admin' : '/lawyer'))

  async function load() {
    if (loaded.value) return
    user.value = await api.auth.currentUser()
    loaded.value = true
  }

  async function login(email: string, password: string) {
    return api.auth.login(email, password)
  }

  async function verifyTwoFactor(input: { code?: string; recoveryCode?: string }) {
    user.value = await api.auth.twoFactorChallenge(input)
    loaded.value = true
  }

  function setUser(value: SessionUser) {
    user.value = value
    loaded.value = true
  }

  async function logout() {
    await api.auth.logout()
    user.value = null
  }

  return { user, loaded, role, homePath, load, login, verifyTwoFactor, setUser, logout }
})
