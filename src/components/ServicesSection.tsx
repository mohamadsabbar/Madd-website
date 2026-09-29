import { Link } from 'react-router-dom'
import type { BenefitConfig } from '../config/defaultSite'
import { Reveal } from './Reveal'
import { ConfigurableIcon } from './ConfigurableIcon'

type Props = {
  title: string
  items: BenefitConfig[]
}

function isExternal(href: string) {
  return /^https?:\/\//i.test(href) || href.startsWith('//')
}

export function ServicesSection({ title, items }: Props) {
  if (!items.length) return null

  return (
    <section className="section services-rail">
      <div className="container">
        <Reveal>
          <h2 className="services-rail__title">{title}</h2>
        </Reveal>

        <div className="services-rail__track">
          {items.map((item, i) => {
            const href = item.link?.trim() || '#'
            const inner = (
              <>
                <span className="service-card__icon">
                  <ConfigurableIcon
                    icon={item.icon}
                    iconDataUrl={item.iconDataUrl}
                    imgClassName="service-card__icon-img"
                    alt=""
                  />
                </span>
                <span className="service-card__label">{item.title}</span>
                <span className="service-card__chevron" aria-hidden="true">
                  ‹
                </span>
              </>
            )

            if (isExternal(href)) {
              return (
                <a key={`${item.title}-${i}`} className="service-card" href={href} target="_blank" rel="noreferrer">
                  {inner}
                </a>
              )
            }

            return (
              <Link key={`${item.title}-${i}`} className="service-card" to={href}>
                {inner}
              </Link>
            )
          })}
        </div>
      </div>
    </section>
  )
}
