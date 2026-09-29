/** رابط بوابة المشتركين / الإدارة (Laravel + MikroTik) */
export const PORTAL_BASE =
  (import.meta.env.VITE_PORTAL_URL as string | undefined)?.replace(/\/$/, '') ||
  'http://127.0.0.1:8000'

export const PORTAL_LOGIN_URL = `${PORTAL_BASE}/my/login`
