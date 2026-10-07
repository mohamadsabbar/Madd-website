import { Link } from 'react-router-dom'
import { useSiteConfig } from '../context/SiteConfigContext'

const DEFAULT_LOGO = '/brand/madd-logo.png'

export function Brand({ footer = false, to = '/' }: { footer?: boolean; to?: string }) {
  const { config } = useSiteConfig()
  const { nameAr, nameEn, logoDataUrl } = config.brand
  const src = logoDataUrl || DEFAULT_LOGO

  return (
    <Link
      to={to}
      className={`brand brand--full${footer ? ' brand--footer' : ''}`}
      aria-label={`${nameAr} — ${nameEn}`}
    >
      <img
        src={src}
        alt={`${nameAr} — ${nameEn}`}
        width={footer ? 140 : 168}
        height={footer ? 54 : 64}
        className="brand__full-logo"
        decoding="async"
      />
    </Link>
  )
}
