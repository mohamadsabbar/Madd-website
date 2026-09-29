import { Link } from 'react-router-dom'
import type { BusinessOffering } from '../config/defaultSite'

export function BusinessOfferingCard({ offering }: { offering: BusinessOffering }) {
  const href = offering.link
  const isExternal = /^https?:\/\//i.test(href)

  const inner = (
    <>
      <span className="biz-offering__subtitle">{offering.subtitle}</span>
      <h3 className="biz-offering__title">{offering.title}</h3>
      <p className="biz-offering__text">{offering.text}</p>
      <ul className="biz-offering__list">
        {offering.bullets.map((b) => (
          <li key={b}>{b}</li>
        ))}
      </ul>
      <span className="biz-offering__cta">
        اطلب الخدمة ‹
      </span>
    </>
  )

  if (isExternal) {
    return (
      <a
        href={href}
        className={`biz-offering biz-offering--${offering.tone}`}
        target="_blank"
        rel="noreferrer"
      >
        {inner}
      </a>
    )
  }

  return (
    <Link to={href} className={`biz-offering biz-offering--${offering.tone}`}>
      {inner}
    </Link>
  )
}
