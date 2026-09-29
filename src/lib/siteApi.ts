const API_BASE = (import.meta.env.VITE_API_BASE as string | undefined) || '/api'

async function siteRequest<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers || {})
  if (!headers.has('Content-Type') && init.body) {
    headers.set('Content-Type', 'application/json')
  }
  headers.set('Accept', 'application/json')

  const res = await fetch(`${API_BASE}${path}`, { ...init, headers })
  const data = (await res.json().catch(() => ({}))) as T & { message?: string; ok?: boolean }
  if (!res.ok) {
    throw new Error(data.message || `خطأ ${res.status}`)
  }
  return data
}

export type SiteConfigApiResponse = {
  ok: boolean
  updated_at?: string | null
  config: Record<string, unknown>
}

export type SiteLeadPayload = {
  type: 'fiber' | 'programming' | 'business_join'
  name?: string
  phone?: string
  email?: string
  city?: string
  area?: string
  plan_id?: string
  plan_name?: string
  message?: string
  meta?: Record<string, unknown>
  source?: string
}

export function fetchSiteConfig() {
  return siteRequest<SiteConfigApiResponse>('/site/config')
}

export function saveSiteConfig(config: unknown, adminPassword: string) {
  return siteRequest<SiteConfigApiResponse>('/site/config', {
    method: 'PUT',
    headers: { 'X-Site-Admin-Password': adminPassword },
    body: JSON.stringify({ config }),
  })
}

export function submitSiteLead(payload: SiteLeadPayload) {
  return siteRequest<{ ok: boolean; message: string; lead: { id: number } }>('/site/leads', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function fetchSiteLeads(adminPassword: string) {
  return siteRequest<{ ok: boolean; leads: Array<Record<string, unknown>> }>('/site/leads', {
    method: 'GET',
    headers: { 'X-Site-Admin-Password': adminPassword },
  })
}

export function updateSiteLeadStatus(id: number, status: string, adminPassword: string) {
  return siteRequest<{ ok: boolean }>(`/site/leads/${id}`, {
    method: 'PATCH',
    headers: { 'X-Site-Admin-Password': adminPassword },
    body: JSON.stringify({ status }),
  })
}
