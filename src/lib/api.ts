const API_BASE = import.meta.env.VITE_API_BASE || '/api'

export type AuthUser = {
  id: number
  name: string
  email: string
  phone: string | null
  role: string
}

type ApiResult<T> = T & { ok: boolean; error?: string; detail?: string }

async function request<T>(path: string, init: RequestInit = {}): Promise<ApiResult<T>> {
  const headers = new Headers(init.headers || {})
  if (!headers.has('Content-Type') && init.body) {
    headers.set('Content-Type', 'application/json')
  }

  const res = await fetch(`${API_BASE}${path}`, {
    ...init,
    headers,
  })

  const data = (await res.json().catch(() => ({ ok: false, error: 'استجابة غير صالحة' }))) as ApiResult<T>
  if (!res.ok && !data.error) {
    data.error = `خطأ ${res.status}`
  }
  return data
}

export function apiLogin(email: string, password: string) {
  return request<{ token: string; expires_at: string; user: AuthUser }>('/auth/login.php', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
}

export function apiRegister(payload: { name: string; email: string; phone?: string; password: string }) {
  return request<{ token: string; expires_at: string; user: AuthUser }>('/auth/register.php', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function apiMe(token: string) {
  return request<{ user: AuthUser }>('/auth/me.php', {
    method: 'GET',
    headers: { Authorization: `Bearer ${token}` },
  })
}

export function apiLogout(token: string) {
  return request<{ ok: boolean }>('/auth/logout.php', {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
  })
}
