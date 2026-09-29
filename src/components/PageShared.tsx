import { Link } from 'react-router-dom'
import { Reveal } from '../components/Reveal'
import { useSiteConfig } from '../context/SiteConfigContext'
import type { PageHeroConfig, PlanConfig } from '../config/defaultSite'
import { useEffect } from 'react'

export function usePageSeo(page?: PageHeroConfig) {
  const { config } = useSiteConfig()
  useEffect(() => {
    if (!page) return
    if (page.seoTitle) document.title = page.seoTitle
    else document.title = config.seo.title
    const meta = document.querySelector('meta[name="description"]')
    if (meta && page.seoDescription) meta.setAttribute('content', page.seoDescription)
  }, [page, config.seo.title])
}

export function CmsPageHero({ page }: { page: PageHeroConfig }) {
  usePageSeo(page)
  const actions =
    page.cta && page.ctaLink
      ? [{ to: page.ctaLink, label: page.cta, primary: true as const }]
      : undefined
  return <PageHero kicker={page.kicker} title={page.title} text={page.lead} actions={actions} />
}

export function PageHero({
  kicker,
  title,
  text,
  actions,
}: {
  kicker?: string
  title: string
  text: string
  actions?: { to: string; label: string; primary?: boolean }[]
}) {
  return (
    <section className="page-hero">
      <div className="container page-hero__inner">
        {kicker ? (
          <p className="section__kicker" style={{ color: 'rgba(255,255,255,0.85)' }}>
            {kicker}
          </p>
        ) : null}
        <h1>{title}</h1>
        <p>{text}</p>
        {actions?.length ? (
          <div className="hero__actions">
            {actions.map((a) => (
              <Link key={a.to + a.label} to={a.to} className={a.primary === false ? 'btn btn--outline-light' : 'btn btn--light'}>
                {a.label}
              </Link>
            ))}
          </div>
        ) : null}
      </div>
    </section>
  )
}

export function PlansGrid({
  plans,
  orderTo,
  onPick,
  showCompare = false,
}: {
  plans: PlanConfig[]
  orderTo: string
  onPick?: (label: string) => void
  showCompare?: boolean
}) {
  const { config } = useSiteConfig()
  const ui = config.plansUi

  if (!plans.length) {
    return <p className="plans-empty">لا توجد باقات معروضة حالياً.</p>
  }

  const countClass =
    plans.length === 1
      ? 'plans-grid--1'
      : plans.length === 2
        ? 'plans-grid--2'
        : plans.length === 3
          ? 'plans-grid--3'
          : plans.length === 4
            ? 'plans-grid--4'
            : 'plans-grid--many'

  return (
    <>
      {showCompare && plans.length > 1 ? (
        <div className="plans-compare">
          <h3 className="plans-compare__title">{ui.compareTitle}</h3>
          <div className="plans-compare__table-wrap">
            <table className="plans-compare__table">
              <thead>
                <tr>
                  <th>الميزة</th>
                  {plans.map((p) => (
                    <th key={p.id}>{p.name}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>السرعة</td>
                  {plans.map((p) => (
                    <td key={p.id}>{p.speed} ميجا</td>
                  ))}
                </tr>
                <tr>
                  <td>السعر</td>
                  {plans.map((p) => (
                    <td key={p.id}>
                      {p.price} {ui.currency}
                    </td>
                  ))}
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      ) : null}

      <div className={`plans-grid ${countClass}`}>
        {plans.map((plan) => (
          <Reveal key={plan.id}>
            <article className={`plan plan--${plan.accent}${plan.featured ? ' plan--featured' : ''}`}>
              <header className="plan__head">
                <span className="plan__name">{plan.name}</span>
                {plan.featured ? <span className="plan__badge">{ui.featuredBadge}</span> : null}
              </header>
              <p className="plan__meta">{plan.meta}</p>
              <p className="plan__price">
                <strong>{plan.price}</strong>{' '}
                <span>
                  {ui.currency} {ui.period}
                </span>
              </p>
              <p className="plan__speed">
                <span>{plan.speed}</span> ميجا
              </p>
              <ul className="plan__features">
                {plan.features.map((f) => (
                  <li key={f}>{f}</li>
                ))}
              </ul>
              <Link
                to={`${orderTo}${orderTo.includes('?') ? '&' : '?'}plan=${plan.id}`}
                className="btn btn--primary btn--block"
                onClick={() => onPick?.(`واي فايبر ${plan.name} — ${plan.speed} ميجا`)}
              >
                {ui.orderCta}
              </Link>
            </article>
          </Reveal>
        ))}
      </div>
      {ui.legalNote ? <p className="plans-legal">{ui.legalNote}</p> : null}
    </>
  )
}

export function TrustSection() {
  const { config } = useSiteConfig()
  const t = config.trust
  return (
    <section className="section trust">
      <div className="container">
        <Reveal>
          <h2 className="section__title">{t.title}</h2>
          <p className="section__lead">{t.lead}</p>
        </Reveal>
        <div className="trust__stats">
          {t.stats.map((s) => (
            <Reveal key={s.id}>
              <article className="trust-stat">
                <strong>{s.value}</strong>
                <span>{s.label}</span>
              </article>
            </Reveal>
          ))}
        </div>
        {t.testimonials.length ? (
          <div className="trust__quotes">
            {t.testimonials.map((q) => (
              <Reveal key={q.id}>
                <blockquote className="trust-quote">
                  <p>{q.text}</p>
                  <footer>
                    <strong>{q.name}</strong> — {q.role}
                  </footer>
                </blockquote>
              </Reveal>
            ))}
          </div>
        ) : null}
      </div>
    </section>
  )
}

export function ProgrammingSection({
  orderPath,
  idPrefix = '',
}: {
  orderPath: string
  idPrefix?: string
}) {
  const { config } = useSiteConfig()
  return (
    <section className="section dev-services" id={`${idPrefix}programming`}>
      <div className="container">
        <Reveal>
          <div className="section__head">
            <div>
              <p className="section__kicker" lang="en">
                Software & Apps
              </p>
              <h2 className="section__title section__title--start">{config.programming.pageTitle}</h2>
              <p className="section__subtitle">{config.programming.intro}</p>
            </div>
            <Link to={orderPath} className="text-link">
              اطلب مشروع ‹
            </Link>
          </div>
        </Reveal>
        <div className="dev-services__grid">
          {config.programming.items.map((item) => (
            <Reveal key={item.id}>
              <article className="dev-card" id={item.id}>
                <h3>{item.title}</h3>
                <p>{item.text}</p>
              </article>
            </Reveal>
          ))}
        </div>
        <Reveal>
          <div className="dev-services__cta">
            <p>{config.programming.ctaTitle}</p>
            <Link to={orderPath} className="btn btn--primary">
              {config.programming.ctaButton}
            </Link>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
