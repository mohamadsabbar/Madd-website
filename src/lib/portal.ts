/** رابط بوابة المشتركين / الإدارة (Laravel على my.madd.ps) */
export const PORTAL_BASE =
  (import.meta.env.VITE_PORTAL_URL as string | undefined)?.replace(/\/$/, '') ||
  'http://127.0.0.1:8000'

export const PORTAL_LOGIN_URL = `${PORTAL_BASE}/my/login`
export const PORTAL_STAFF_LOGIN_URL = `${PORTAL_BASE}/login`
export const PORTAL_API_BASE = `${PORTAL_BASE}/api`
