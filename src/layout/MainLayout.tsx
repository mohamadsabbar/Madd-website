import { useEffect, useState } from 'react'
import { Link, Outlet, useLocation } from 'react-router-dom'
import { Brand } from '../components/Brand'
import { MegaMenu } from '../components/MegaMenu'
import { useAuth } from '../context/AuthContext'
import { applyAudienceColors, useSiteConfig } from '../context/SiteConfigContext'
import { PORTAL_LOGIN_URL } from '../lib/portal'

export function MainLayout() {
  const { pathname } = useLocation()
  const isBusiness = pathname.startsWith('/business')
  const mode = isBusiness ? 'business' : 'individuals'
  const [menuOpen, setMenuOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)
  const { config } = useSiteConfig()
  const { user, logout, loading } = useAuth()

  useEffect(() => {
    setMenuOpen(false)
    window.scrollTo(0, 0)
  }, [pathname])

  useEffect(() => {
    applyAudienceColors(config, isBusiness ? 'business' : 'individuals')
  }, [config, isBusiness])

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  return (
    <div id="top">
      <div className={`site-chrome${scrolled ? ' is-scrolled' : ''}${menuOpen ? ' is-open' : ''}`}>
        <div className="topbar">
          <div className="container topbar__inner">
            <nav className="topbar__segments" aria-label="شرائح العملاء">
              <Link to="/" className={`topbar__seg${!isBusiness ? ' is-active' : ''}`}>
                {config.labels.individuals}
              </Link>
              <Link to="/business" className={`topbar__seg${isBusiness ? ' is-active' : ''}`}>
                {config.labels.business}
              </Link>
            </nav>
            <div className="topbar__actions">
              <Link to="/about" className="topbar__link">
                {config.labels.about}
              </Link>
              <a href={`tel:${config.contact.phone}`} className="topbar__phone" dir="ltr">
                {config.contact.phone}
              </a>
            </div>
          </div>
        </div>

        <header className={`header${menuOpen ? ' is-open' : ''}`}>
          <div className="container header__inner">
            <div className="header__brand">
              <Brand to={isBusiness ? '/business' : '/'} />
            </div>

            <div className={`nav-wrap${menuOpen ? ' is-open' : ''}`}>
              <MegaMenu mode={mode} />
            </div>

            <div className="header__cta">
              {!loading && user ? (
                <>
                  <span className="header__user" title={user.email}>
                    {user.name}
                  </span>
                  <button type="button" className="btn btn--ghost btn--header" onClick={() => void logout()}>
                    خروج
                  </button>
                </>
              ) : (
                <a href={PORTAL_LOGIN_URL} className="btn btn--primary btn--header">
                  {isBusiness ? config.brand.ctaBusiness : config.brand.ctaIndividuals}
                </a>
              )}
              <button
                type="button"
                className="burger"
                aria-label={menuOpen ? 'إغلاق القائمة' : 'فتح القائمة'}
                aria-expanded={menuOpen}
                onClick={() => setMenuOpen((v) => !v)}
              >
                <span />
                <span />
                <span />
              </button>
            </div>
          </div>
        </header>
      </div>

      <main>
        <Outlet />
      </main>

      <footer className="footer">
        <div className="container">
          <div className="footer__grid">
            <div className="footer__brand">
              <Brand footer to={isBusiness ? '/business' : '/'} />
              <p className="footer__tagline">{config.footer.tagline}</p>
            </div>
            <div>
              <h3>{config.labels.individuals}</h3>
              <ul>
                <li>
                  <Link to="/plans">الباقات</Link>
                </li>
                {config.visibility.showProgramming ? (
                  <li>
                    <Link to="/programming">البرمجة</Link>
                  </li>
                ) : null}
                <li>
                  <Link to="/order">تقديم طلب</Link>
                </li>
              </ul>
            </div>
            <div>
              <h3>{config.labels.business}</h3>
              <ul>
                <li>
                  <Link to="/business/plans">الباقات</Link>
                </li>
                {config.visibility.showBusinessPos ? (
                  <li>
                    <Link to="/business/pos">نقاط البيع</Link>
                  </li>
                ) : null}
                {config.visibility.showBusinessHr ? (
                  <li>
                    <Link to="/business/hr">الموارد البشرية</Link>
                  </li>
                ) : null}
                {config.visibility.showProgramming ? (
                  <li>
                    <Link to="/business/programming">التطوير</Link>
                  </li>
                ) : null}
              </ul>
            </div>
            <div>
              <h3>تواصل</h3>
              <ul>
                <li>
                  <a href={`tel:${config.contact.phone}`}>{config.contact.phone}</a>
                </li>
                <li>
                  <a href={`mailto:${config.contact.email}`}>{config.contact.email}</a>
                </li>
                <li>
                  <Link to="/about">{config.labels.about}</Link>
                </li>
              </ul>
            </div>
          </div>
          <div className="footer__bottom">
            <p>
              © {new Date().getFullYear()} {config.brand.nameAr} — {config.brand.nameEn}. جميع الحقوق محفوظة.
            </p>
            <div className="social">
              <a href={config.contact.whatsapp} target="_blank" rel="noreferrer">
                WhatsApp
              </a>
              {config.contact.facebook && config.contact.facebook !== '#' ? (
                <a href={config.contact.facebook} target="_blank" rel="noreferrer">
                  Facebook
                </a>
              ) : null}
              {config.contact.instagram && config.contact.instagram !== '#' ? (
                <a href={config.contact.instagram} target="_blank" rel="noreferrer">
                  Instagram
                </a>
              ) : null}
            </div>
          </div>
        </div>
      </footer>

      {config.visibility.showChat ? (
        <a
          className="chat-fab"
          href={config.contact.whatsapp}
          target="_blank"
          rel="noreferrer"
          aria-label="واتساب"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
            <path d="M4 4h16a2 2 0 012 2v10a2 2 0 01-2 2H8l-4 4V6a2 2 0 012-2z" />
          </svg>
          <span>{config.contact.chatLabel}</span>
        </a>
      ) : null}
    </div>
  )
}
