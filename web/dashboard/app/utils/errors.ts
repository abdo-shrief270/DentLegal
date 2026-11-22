import { ApiError } from '~/types/client'

/** User-facing Arabic message for any thrown error. */
export function errorMessage(error: unknown): string {
  return error instanceof ApiError ? error.message : 'حدث خطأ غير متوقع. حاول مرة أخرى.'
}
