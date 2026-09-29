import { useEffect } from 'react'
import { Link } from 'react-router-dom'
import { PORTAL_LOGIN_URL } from '../lib/portal'

/** يوجّه إلى بوابة مدد (Laravel) لتسجيل الدخول */
export function LoginPage() {
  useEffect(() => {
    window.location.replace(PORTAL_LOGIN_URL)
  }, [])

  return (
    <div className="auth-page">
      <div className="auth-card">
        <p className="auth-card__brand">مدد</p>
        <h1>جاري التحويل…</h1>
        <p className="auth-card__lead">ننقلك إلى صفحة تسجيل الدخول في بوابة المشتركين.</p>
        <a className="btn btn--primary btn--block" href={PORTAL_LOGIN_URL}>
          متابعة إلى تسجيل الدخول
        </a>
        <Link to="/" className="auth-back">
          العودة للموقع
        </Link>
      </div>
    </div>
  )
}
