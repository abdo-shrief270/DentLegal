import type { DashboardApi } from '~/types/client'
import { createMockApi } from '~/mocks/api'
import { createHttpApi } from '~/services/http'

let instance: DashboardApi | null = null

/** The backend client: the fake API while `mockApi` is on, the Laravel API otherwise. One instance per app. */
export function useApi(): DashboardApi {
  if (!instance) {
    const config = useRuntimeConfig()
    instance = config.public.mockApi ? createMockApi() : createHttpApi(config.public.apiBase as string)
  }
  return instance
}
