import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react'
import { apiLogin, apiLogout, apiMe, apiRegister, type AuthUser } from '../lib/api'

const TOKEN_KEY = 'madd-auth-token'

type AuthCtx = {
  user: AuthUser | null
  token: string | null
  loading: boolean
  login: (email: string, password: string) => Promise<string | null>
  register: (payload: { name: string; email: string; phone?: string; password: string }) => Promise<string | null>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthCtx | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_KEY))
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    async function boot() {
      if (!token) {
        setLoading(false)
        return
      }
      const res = await apiMe(token)
      if (cancelled) return
      if (res.ok && res.user) {
        setUser(res.user)
      } else {
        localStorage.removeItem(TOKEN_KEY)
        setToken(null)
        setUser(null)
      }
      setLoading(false)
    }
    void boot()
    return () => {
      cancelled = true
    }
  }, [token])

  const login = useCallback(async (email: string, password: string) => {
    const res = await apiLogin(email, password)
    if (!res.ok || !res.token || !res.user) return res.error || 'فشل تسجيل الدخول'
    localStorage.setItem(TOKEN_KEY, res.token)
    setToken(res.token)
    setUser(res.user)
    return null
  }, [])

  const register = useCallback(
    async (payload: { name: string; email: string; phone?: string; password: string }) => {
      const res = await apiRegister(payload)
      if (!res.ok || !res.token || !res.user) return res.error || 'فشل إنشاء الحساب'
      localStorage.setItem(TOKEN_KEY, res.token)
      setToken(res.token)
      setUser(res.user)
      return null
    },
    [],
  )

  const logout = useCallback(async () => {
    if (token) await apiLogout(token)
    localStorage.removeItem(TOKEN_KEY)
    setToken(null)
    setUser(null)
  }, [token])

  const value = useMemo(
    () => ({ user, token, loading, login, register, logout }),
    [user, token, loading, login, register, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
