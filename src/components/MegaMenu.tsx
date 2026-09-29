import { useEffect, useRef, useState } from 'react'
import { Link, NavLink, useLocation } from 'react-router-dom'
import type { MenuEntry } from '../config/menus'
import { DEFAULT_MENUS } from '../config/menus'
import { useSiteConfig } from '../context/SiteConfigContext'

export function MegaMenu({ mode }: { mode: 'individuals' | 'business' }) {
  const { config } = useSiteConfig()
  const [open, setOpen] = useState<string | null>(null)
  const rootRef = useRef<HTMLElement>(null)
  const location = useLocation()

  const rawMenu = config.menus?.[mode] || DEFAULT_MENUS[mode]
  const menu = rawMenu.filter((e) => {
    if (e.enabled === false) return false
    if (e.type === 'link') {
      if (e.to === '/offers' && !config.visibility.showOffers) return false
      if (e.to === '/coverage' && !config.visibility.showCoverage) return false
      if (e.to === '/programming' && !config.visibility.showProgramming) return false
      if (e.to === '/business/pos' && !config.visibility.showBusinessPos) return false
      if (e.to === '/business/hr' && !config.visibility.showBusinessHr) return false
    }
    return true
  })

  useEffect(() => {
    setOpen(null)
  }, [location.pathname])

  useEffect(() => {
    const onDoc = (e: MouseEvent) => {
      if (!rootRef.current?.contains(e.target as Node)) setOpen(null)
    }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [])

  return (
    <nav className="nav mega-nav" ref={rootRef} aria-label="القائمة الرئيسية">
      {menu.map((entry) => {
        if (entry.type === 'link') {
          return (
            <NavLink
              key={entry.id}
              to={entry.to}
              className={({ isActive }) => `nav__link${isActive ? ' is-active' : ''}`}
            >
              {entry.label}
            </NavLink>
          )
        }

        const isOpen = open === entry.id
        return (
          <div key={entry.id} className={`mega-item${isOpen ? ' is-open' : ''}`}>
            <button
              type="button"
              className="nav__link mega-trigger"
              aria-expanded={isOpen}
              onClick={() => setOpen(isOpen ? null : entry.id)}
              onMouseEnter={() => {
                if (window.matchMedia('(min-width: 1025px)').matches) setOpen(entry.id)
              }}
            >
              {entry.label}
            </button>
            <div
              className="mega-panel"
              onMouseLeave={() => {
                if (window.matchMedia('(min-width: 1025px)').matches) setOpen(null)
              }}
            >
              <MegaPanelBody entry={entry} onNavigate={() => setOpen(null)} />
            </div>
          </div>
        )
      })}
    </nav>
  )
}

function MegaPanelBody({ entry, onNavigate }: { entry: Extract<MenuEntry, { type: 'mega' }>; onNavigate: () => void }) {
  const featured = entry.featured?.enabled !== false ? entry.featured : null
  return (
    <div className={`mega-panel__inner${featured ? '' : ' mega-panel__inner--no-featured'}`}>
      <div className="mega-panel__content container">
        <div className={`mega-columns mega-columns--${Math.min(4, Math.max(1, entry.columns.length))}`}>
          {entry.columns.map((col) => (
            <div key={col.id} className="mega-col">
              <h4>{col.title}</h4>
              <ul>
                {col.items.map((item) => (
                  <li key={item.id}>
                    <Link to={item.to} onClick={onNavigate}>
                      <strong>{item.label}</strong>
                      {item.desc ? <span>{item.desc}</span> : null}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
        {featured ? (
          <aside className="mega-featured">
            <p className="mega-featured__title">{featured.title}</p>
            <p className="mega-featured__text">{featured.text}</p>
            <Link to={featured.to} className="btn btn--primary" onClick={onNavigate}>
              {featured.cta}
            </Link>
          </aside>
        ) : null}
      </div>
    </div>
  )
}
