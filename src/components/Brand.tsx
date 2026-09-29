import { Link } from 'react-router-dom'
import { useSiteConfig } from '../context/SiteConfigContext'

const DEFAULT_LOGO = '/madd-logo.png'

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
        width={footer ? 140 : 180}
        height={footer ? 56 : 72}
        className="brand__full-logo"
        decoding="async"
      />
      <span className="brand__text brand__text--beside-logo">
        <span className="brand__ar">{nameAr}</span>
        <span className="brand__en">{nameEn}</span>
      </span>
    </Link>
  )
}
