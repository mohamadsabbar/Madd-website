import { Link } from 'react-router-dom'
import { useMemo, useState, type FormEvent } from 'react'
import { CmsPageHero, PageHero, PlansGrid, ProgrammingSection, usePageSeo } from '../components/PageShared'
import { Reveal } from '../components/Reveal'
import { useSiteConfig } from '../context/SiteConfigContext'
import { PORTAL_LOGIN_URL } from '../lib/portal'
export { OrderPage } from './FiberOnboarding'

export function PlansPage() {
  const { config } = useSiteConfig()
  return (
    <>
      <CmsPageHero page={config.pages.plans} />
      <section className="section plans">
        <div className="container">
          <PlansGrid plans={config.plans} orderTo="/order" showCompare />
        </div>
      </section>
      <div className="plans-sticky-cta">
        <Link to="/order" className="btn btn--primary">
          {config.plansUi.orderCta}
        </Link>
      </div>
    </>
  )
}

export function ProgrammingPage() {
  const { config } = useSiteConfig()
  return (
    <>
      <CmsPageHero page={config.pages.programming} />
      <ProgrammingSection orderPath="/order?service=programming" />
    </>
  )
}

export function OffersPage() {
  const { config } = useSiteConfig()
  if (!config.visibility.showOffers) {
    return (
      <>
        <CmsPageHero page={config.pages.offers} />
        <section className="section">
          <div className="container">
            <p className="plans-empty">العروض غير مفعّلة حالياً.</p>
          </div>
        </section>
      </>
    )
  }
  return (
    <>
      <CmsPageHero page={config.pages.offers} />
      <section className="section offers">
        <div className="container">
          <div className="offers__rail">
            {config.offers.length === 0 ? (
              <p className="plans-empty">لا توجد عروض حالياً.</p>
            ) : (
              config.offers.map((o) => (
                <Reveal key={o.id}>
                  <Link to={o.link} className={`offer-tile offer-tile--${o.tone}`}>
                    <span className="offer-tile__tag">{o.tag}</span>
                    <h3>{o.title}</h3>
                    <span className="text-link">اطلب العرض ‹</span>
                  </Link>
                </Reveal>
              ))
            )}
          </div>
        </div>
      </section>
    </>
  )
}

export function CoveragePage() {
  const { config } = useSiteConfig()
  const page = config.pages.coverage
  const c = config.coverage
  usePageSeo(page)

  const cities = useMemo(() => [...new Set(c.areas.map((a) => a.city))], [c.areas])
  const [city, setCity] = useState(cities[0] || '')
  const [areaId, setAreaId] = useState('')
  const [browseCity, setBrowseCity] = useState(cities[0] || '')
  const [result, setResult] = useState<{
    status: 'available' | 'check' | 'unavailable'
    message: string
    areaName: string
  } | null>(null)

  const areasInCity = c.areas.filter((a) => a.city === city)
  const browseAreas = c.areas.filter((a) => a.city === browseCity)

  const statusLabel = (status: string) =>
    status === 'available' ? 'متوفر' : status === 'check' ? 'قيد التأكيد' : 'غير متوفر'

  const onSubmit = (e: FormEvent) => {
    e.preventDefault()
    const area = c.areas.find((a) => a.id === areaId)
    if (!area) {
      setResult({ status: 'check', message: c.resultCheck, areaName: '' })
      return
    }
    const message =
      area.status === 'available'
        ? c.resultAvailable
        : area.status === 'unavailable'
          ? c.resultUnavailable
          : c.resultCheck
    setResult({ status: area.status, message, areaName: `${area.city} — ${area.name}` })
  }

  const scrollToCheck = () => {
    document.getElementById('coverage-check')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  return (
    <>
      <section className="cov-hero">
        <div className="cov-hero__signal" aria-hidden="true">
          <span className="cov-hero__ring cov-hero__ring--1" />
          <span className="cov-hero__ring cov-hero__ring--2" />
          <span className="cov-hero__ring cov-hero__ring--3" />
          <span className="cov-hero__pin" />
        </div>
        <div className="container cov-hero__stage">
          <div className="cov-hero__copy">
            <p className="cov-hero__brand">{config.brand.nameAr || 'مدد'}</p>
            <h1 className="cov-hero__title">{page.title}</h1>
            <p className="cov-hero__lead">{page.lead}</p>
            <div className="cov-hero__actions">
              <button type="button" className="btn btn--primary" onClick={scrollToCheck}>
                افحص التغطية
              </button>
              <Link to="/order" className="btn btn--ghost">
                تقديم طلب
              </Link>
            </div>
          </div>
        </div>
      </section>

      <section className="section cov-check" id="coverage-check">
        <div className="container cov-check__inner">
          <Reveal>
            <p className="section__kicker">فحص سريع</p>
            <h2 className="section__title section__title--start">{c.formTitle}</h2>
            <p className="section__lead cov-check__lead">{c.formLead}</p>
          </Reveal>

          <Reveal>
            <form className="cov-form" onSubmit={onSubmit}>
              <div className="cov-form__row">
                <label className="cov-field">
                  <span>المدينة</span>
                  <select
                    value={city}
                    onChange={(e) => {
                      setCity(e.target.value)
                      setAreaId('')
                      setResult(null)
                    }}
                    required
                  >
                    {cities.map((ct) => (
                      <option key={ct} value={ct}>
                        {ct}
                      </option>
                    ))}
                  </select>
                </label>
                <label className="cov-field">
                  <span>المنطقة</span>
                  <select value={areaId} onChange={(e) => setAreaId(e.target.value)} required>
                    <option value="">اختر المنطقة</option>
                    {areasInCity.map((a) => (
                      <option key={a.id} value={a.id}>
                        {a.name}
                      </option>
                    ))}
                  </select>
                </label>
              </div>
              <button className="btn btn--primary cov-form__submit" type="submit">
                فحص التغطية
              </button>
            </form>
          </Reveal>

          {result ? (
            <div className={`cov-result cov-result--${result.status}`} role="status">
              <div className="cov-result__badge">{statusLabel(result.status)}</div>
              {result.areaName ? <p className="cov-result__place">{result.areaName}</p> : null}
              <p className="cov-result__msg">{result.message}</p>
              <div className="cov-result__actions">
                {result.status === 'available' ? (
                  <Link to="/order" className="btn btn--primary">
                    تقديم طلب التركيب
                  </Link>
                ) : (
                  <a href={config.contact.whatsapp} className="btn btn--primary" target="_blank" rel="noreferrer">
                    تواصل عبر واتساب
                  </a>
                )}
                <Link to="/plans" className="btn btn--ghost">
                  استكشف الباقات
                </Link>
              </div>
            </div>
          ) : null}
        </div>
      </section>

      <section className="cov-legend" aria-label="دليل الحالات">
        <div className="container cov-legend__inner">
          <span className="cov-legend__item">
            <i className="cov-dot cov-dot--available" aria-hidden="true" />
            متوفر — يمكن الاشتراك الآن
          </span>
          <span className="cov-legend__item">
            <i className="cov-dot cov-dot--check" aria-hidden="true" />
            قيد التأكيد — نراجع إمكانية التغطية
          </span>
          <span className="cov-legend__item">
            <i className="cov-dot cov-dot--unavailable" aria-hidden="true" />
            غير متوفر — سنُعلمك عند التوسع
          </span>
        </div>
      </section>

      <section className="section cov-areas">
        <div className="container">
          <Reveal>
            <h2 className="section__title section__title--start">مناطق التغطية</h2>
            <p className="section__lead">تصفّح المناطق حسب المدينة. اضغط منطقة لملء نموذج الفحص مباشرة.</p>
          </Reveal>

          <div className="cov-tabs" role="tablist" aria-label="المدن">
            {cities.map((ct) => (
              <button
                key={ct}
                type="button"
                role="tab"
                aria-selected={browseCity === ct}
                className={`cov-tabs__btn${browseCity === ct ? ' is-active' : ''}`}
                onClick={() => setBrowseCity(ct)}
              >
                {ct}
              </button>
            ))}
          </div>

          <div className="cov-areas__grid">
            {browseAreas.map((a) => (
              <Reveal key={a.id}>
                <button
                  type="button"
                  className={`cov-area cov-area--${a.status}`}
                  onClick={() => {
                    setCity(a.city)
                    setAreaId(a.id)
                    setResult(null)
                    scrollToCheck()
                  }}
                >
                  <span className="cov-area__name">{a.name}</span>
                  <span className={`cov-area__status cov-area__status--${a.status}`}>
                    {statusLabel(a.status)}
                  </span>
                </button>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section className="cov-cta">
        <div className="container cov-cta__inner">
          <div>
            <h2>جاهز للاشتراك؟</h2>
            <p>إذا كانت منطقتك متوفرة، ابدأ الطلب الآن — أو تواصل معنا إن احتجت مساعدة في اختيار الباقة.</p>
          </div>
          <div className="cov-cta__actions">
            <Link to="/order" className="btn btn--primary">
              تقديم طلب
            </Link>
            <a href={config.contact.whatsapp} className="btn btn--ghost" target="_blank" rel="noreferrer">
              واتساب
            </a>
          </div>
        </div>
      </section>
    </>
  )
}

export function SupportPage() {
  const { config } = useSiteConfig()
  return (
    <>
      <CmsPageHero page={config.pages.support} />
      <section className="section support">
        <div className="container">
          <div className="faq">
            {config.faq.map((item, i) => (
              <details key={item.q} className="faq__item" open={i === 0}>
                <summary>{item.q}</summary>
                <p>{item.a}</p>
              </details>
            ))}
          </div>
          <div className="support__grid">
            {config.support.quickLinks.map((item) => {
              const to =
                item.link === 'whatsapp'
                  ? config.contact.whatsapp
                  : item.link === 'portal'
                    ? PORTAL_LOGIN_URL
                    : item.link
              const external = to.startsWith('http')
              if (external) {
                return (
                  <a key={item.id} href={to} className="support-item" target="_blank" rel="noreferrer">
                    <strong>{item.title}</strong>
                    <span>{item.text}</span>
                  </a>
                )
              }
              if (item.link === '/coverage' && !config.visibility.showCoverage) return null
              return (
                <Link key={item.id} to={to} className="support-item">
                  <strong>{item.title}</strong>
                  <span>{item.text}</span>
                </Link>
              )
            })}
          </div>
        </div>
      </section>
    </>
  )
}

export function AboutPage() {
  const { config } = useSiteConfig()
  return (
    <>
      <CmsPageHero page={config.pages.about} />
      <section className="section about">
        <div className="container about__inner">
          <div>
            <h2>قصتنا</h2>
            <p>{config.footer.about}</p>
          </div>
          <div className="about__values">
            {config.about.values.map((v) => (
              <Reveal key={v.id}>
                <article>
                  <h3>{v.title}</h3>
                  <p>{v.text}</p>
                </article>
              </Reveal>
            ))}
          </div>
        </div>
      </section>
    </>
  )
}

// re-export unused PageHero for legacy imports
export { PageHero }
