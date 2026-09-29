import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react'
import {
  ADMIN_PASSWORD_SESSION,
  DEFAULT_SITE,
  STORAGE_KEY,
  deepMergeSite,
  normalizeSiteConfig,
  type SiteConfig,
} from '../config/defaultSite'
import { fetchSiteConfig, saveSiteConfig } from '../lib/siteApi'

type Ctx = {
  config: SiteConfig
  loading: boolean
  syncing: boolean
  syncError: string | null
  setConfig: (next: SiteConfig | ((prev: SiteConfig) => SiteConfig)) => void
  updateConfig: (patch: Partial<SiteConfig>) => void
  resetConfig: () => void
  exportJson: () => string
  importJson: (raw: string) => boolean
  persistToServer: () => Promise<boolean>
  refreshFromServer: () => Promise<void>
}

const SiteConfigContext = createContext<Ctx | null>(null)

function loadLocal(): SiteConfig {
  try {
    const raw =
      localStorage.getItem(STORAGE_KEY) ||
      localStorage.getItem('madd-site-config-v4') ||
      localStorage.getItem('madd-site-config-v3') ||
      localStorage.getItem('madd-site-config-v1')
    if (!raw) return structuredClone(DEFAULT_SITE)
    return normalizeSiteConfig(JSON.parse(raw) as Partial<SiteConfig>)
  } catch {
    return structuredClone(DEFAULT_SITE)
  }
}

function applyDocumentMeta(config: SiteConfig) {
  const primary = config.brand.primaryColor

  document.documentElement.style.setProperty('--teal', primary)
  document.documentElement.style.setProperty('--teal-deep', shade(primary, -25))

  document.title = config.seo.title
  const meta = document.querySelector('meta[name="description"]')
  if (meta) meta.setAttribute('content', config.seo.description)

  const favicon = document.querySelector('link[rel="icon"]') as HTMLLinkElement | null
  if (favicon) {
    favicon.href = config.brand.faviconDataUrl || config.brand.logoDataUrl || '/madd-logo.png'
  }
}

/** Switch accent palette: coral for individuals/offers, copper + sky for business. */
export function applyAudienceColors(
  config: SiteConfig,
  audience: 'individuals' | 'business',
) {
  const primary = config.brand.primaryColor
  const secondary =
    audience === 'business'
      ? config.brand.businessSecondaryColor || '#c4784a'
      : config.brand.secondaryColor || '#ed875e'
  const mid =
    audience === 'business'
      ? config.brand.businessHighlightColor || '#3b8ea5'
      : shade(primary, 12)

  document.documentElement.dataset.audience = audience
  document.documentElement.style.setProperty('--teal-mid', mid)
  document.documentElement.style.setProperty('--accent', secondary)
  document.documentElement.style.setProperty('--orange', secondary)
  document.documentElement.style.setProperty('--accent-soft', shade(secondary, 88))
  document.documentElement.style.setProperty(
    '--accent-strong',
    audience === 'business' ? shade(secondary, -10) : '#e07a52',
  )
}

export function SiteConfigProvider({ children }: { children: ReactNode }) {
  const [config, setConfigState] = useState<SiteConfig>(() => loadLocal())
  const [loading, setLoading] = useState(true)
  const [syncing, setSyncing] = useState(false)
  const [syncError, setSyncError] = useState<string | null>(null)
  const saveTimer = useRef<number | null>(null)
  const configRef = useRef(config)
  configRef.current = config

  const persistLocal = useCallback((next: SiteConfig) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(next))
  }, [])

  const queueServerSave = useCallback((next: SiteConfig) => {
    const password = sessionStorage.getItem(ADMIN_PASSWORD_SESSION) || ''
    if (!password) return
    if (saveTimer.current) window.clearTimeout(saveTimer.current)
    saveTimer.current = window.setTimeout(() => {
      setSyncing(true)
      saveSiteConfig(next, password)
        .then(() => setSyncError(null))
        .catch((err: Error) => setSyncError(err.message || 'تعذّر الحفظ على السيرفر'))
        .finally(() => setSyncing(false))
    }, 700)
  }, [])

  const refreshFromServer = useCallback(async () => {
    setLoading(true)
    try {
      const res = await fetchSiteConfig()
      const remote = res.config && Object.keys(res.config as object).length > 0 ? res.config : null
      if (remote) {
        const normalized = normalizeSiteConfig(remote as Partial<SiteConfig>)
        setConfigState(normalized)
        persistLocal(normalized)
      }
      setSyncError(null)
    } catch (err) {
      setSyncError(err instanceof Error ? err.message : 'تعذّر التحميل من السيرفر')
    } finally {
      setLoading(false)
    }
  }, [persistLocal])

  useEffect(() => {
    void refreshFromServer()
  }, [refreshFromServer])

  useEffect(() => {
    applyDocumentMeta(config)
  }, [config])

  const setConfig = useCallback(
    (next: SiteConfig | ((prev: SiteConfig) => SiteConfig)) => {
      setConfigState((prev) => {
        const resolved = typeof next === 'function' ? next(prev) : next
        const normalized = normalizeSiteConfig(resolved)
        persistLocal(normalized)
        queueServerSave(normalized)
        return normalized
      })
    },
    [persistLocal, queueServerSave],
  )

  const updateConfig = useCallback(
    (patch: Partial<SiteConfig>) => {
      setConfigState((prev) => {
        const merged = normalizeSiteConfig(deepMergeSite(prev, patch))
        persistLocal(merged)
        queueServerSave(merged)
        return merged
      })
    },
    [persistLocal, queueServerSave],
  )

  const resetConfig = useCallback(() => {
    const fresh = structuredClone(DEFAULT_SITE)
    setConfigState(fresh)
    persistLocal(fresh)
    queueServerSave(fresh)
  }, [persistLocal, queueServerSave])

  const exportJson = useCallback(() => JSON.stringify(config, null, 2), [config])

  const importJson = useCallback(
    (raw: string) => {
      try {
        const parsed = JSON.parse(raw) as Partial<SiteConfig>
        if (!parsed?.brand?.nameAr && !parsed?.seo?.title) return false
        const merged = normalizeSiteConfig(parsed)
        setConfigState(merged)
        persistLocal(merged)
        queueServerSave(merged)
        return true
      } catch {
        return false
      }
    },
    [persistLocal, queueServerSave],
  )

  const persistToServer = useCallback(async () => {
    const password = sessionStorage.getItem(ADMIN_PASSWORD_SESSION) || ''
    if (!password) {
      setSyncError('سجّل دخول لوحة التحكم أولاً لحفظ المحتوى على السيرفر')
      return false
    }
    setSyncing(true)
    try {
      await saveSiteConfig(configRef.current, password)
      setSyncError(null)
      return true
    } catch (err) {
      setSyncError(err instanceof Error ? err.message : 'تعذّر الحفظ')
      return false
    } finally {
      setSyncing(false)
    }
  }, [])

  const value = useMemo(
    () => ({
      config,
      loading,
      syncing,
      syncError,
      setConfig,
      updateConfig,
      resetConfig,
      exportJson,
      importJson,
      persistToServer,
      refreshFromServer,
    }),
    [
      config,
      loading,
      syncing,
      syncError,
      setConfig,
      updateConfig,
      resetConfig,
      exportJson,
      importJson,
      persistToServer,
      refreshFromServer,
    ],
  )

  return <SiteConfigContext.Provider value={value}>{children}</SiteConfigContext.Provider>
}

export function useSiteConfig() {
  const ctx = useContext(SiteConfigContext)
  if (!ctx) throw new Error('useSiteConfig must be used within SiteConfigProvider')
  return ctx
}

function shade(hex: string, percent: number) {
  const n = hex.replace('#', '')
  const num = parseInt(n.length === 3 ? n.split('').map((c) => c + c).join('') : n, 16)
  const r = Math.min(255, Math.max(0, (num >> 16) + Math.round((percent / 100) * 255)))
  const g = Math.min(255, Math.max(0, ((num >> 8) & 0xff) + Math.round((percent / 100) * 255)))
  const b = Math.min(255, Math.max(0, (num & 0xff) + Math.round((percent / 100) * 255)))
  return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`
}
