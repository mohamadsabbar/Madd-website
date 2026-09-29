import { useState, type FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { BusinessOfferingCard } from '../components/BusinessOfferingCard'
import { HeroSlider } from '../components/HeroSlider'
import { MediaFrame } from '../components/MediaFrame'
import { CmsPageHero, PlansGrid, ProgrammingSection, usePageSeo } from '../components/PageShared'
import { Reveal } from '../components/Reveal'
import { ConfigurableIcon } from '../components/ConfigurableIcon'
import type { BizPillarCard } from '../config/defaultSite'
import { normalizeBusinessHomeSections, type BusinessHomeSectionId } from '../config/order'
import { useSiteConfig } from '../context/SiteConfigContext'
import { submitSiteLead } from '../lib/siteApi'

const PILLAR_PLACEHOLDER_TONES = ['teal', 'violet', 'coral', 'blue'] as const

function BizPillarLink({ pillar, tone }: { pillar: BizPillarCard; tone: string }) {
  const className = `biz-pillar biz-pillar--linked${
    pillar.imageDataUrl ? '' : ` biz-pillar--${tone}`
  }`
  const label = pillar.title || 'عرض التفاصيل'
  const inner = (
    <div className="biz-pillar__media">
      {pillar.imageDataUrl ? (
        <img src={pillar.imageDataUrl} alt={label} />
      ) : (
        <div className={`biz-pillar__placeholder biz-pillar__placeholder--${tone}`} aria-hidden="true" />
      )}
    </div>
  )
  if (pillar.link.startsWith('http')) {
    return (
      <a href={pillar.link} className={className} target="_blank" rel="noreferrer" aria-label={label}>
        {inner}
      </a>
    )
  }
  return (
    <Link to={pillar.link} className={className} aria-label={label}>
      {inner}
    </Link>
  )
}

export function BusinessHomePage() {
  const { config } = useSiteConfig()
  const b = config.business
  const sections = normalizeBusinessHomeSections(config.layout?.businessHomeSections)

  const quick = b.quickLinks.filter((q) => {
    if (q.link.includes('/pos')) return config.visibility.showBusinessPos
    if (q.link.includes('/hr')) return config.visibility.showBusinessHr
    if (q.link.includes('programming')) return config.visibility.showProgramming
    return true
  })

  const renderSection = (id: BusinessHomeSectionId) => {
    switch (id) {
      case 'pillars':
        return (
          <section key="pillars" className="section biz-pillars">
            <div className="container">
              {b.pillarsTitle ? (
                <Reveal>
                  <h2 className="section__title">{b.pillarsTitle}</h2>
                </Reveal>
              ) : null}
              <div className="biz-pillars__grid">
                {b.pillars.slice(0, 3).map((p, idx) => (
                  <Reveal key={p.id}>
                    <BizPillarLink
                      pillar={p}
                      tone={PILLAR_PLACEHOLDER_TONES[idx % PILLAR_PLACEHOLDER_TONES.length]}
                    />
                  </Reveal>
                ))}
              </div>
            </div>
          </section>
        )
      case 'intro':
        return (
          <section key="intro" className="section biz-intro">
            <div className="container biz-intro__inner">
              <Reveal>
                <p className="section__kicker">مدد للأعمال</p>
                <h2 className="section__title section__title--start">من الاتصال إلى Domain و Hosting و E-Card</h2>
                <p className="section__lead biz-intro__lead">
                  نقدّم للشركات خط اتصال، أنظمة تشغيل، وحضوراً رقمياً كاملاً — نطاق، استضافة، بريد مؤسسي، وبطاقة
                  إلكترونية، مع محتوى وصور قابلة للتوسع دون الاعتماد على لون واحد في كل الصفحة.
                </p>
              </Reveal>
            </div>
          </section>
        )
      case 'connect':
        return (
          <section key="connect" className="section biz-quick-section biz-quick-section--connect">
            <div className="container">
              <Reveal>
                <h2 className="section__title section__title--start">{b.connectTitle}</h2>
                <p className="section__lead">{b.connectLead}</p>
              </Reveal>
              <div className="biz-quick biz-quick--many">
                {quick.slice(0, 4).map((i) => (
                  <Reveal key={i.id}>
                    <Link to={i.link} className={`biz-quick__card biz-quick__card--${i.tone || 'teal'}`}>
                      <span className="biz-quick__thumb" aria-hidden="true">
                        <ConfigurableIcon
                          icon={i.icon}
                          iconDataUrl={i.iconDataUrl}
                          className="biz-quick__icon"
                          imgClassName="biz-quick__icon-img"
                          alt=""
                        />
                      </span>
                      <span className="biz-quick__text">
                        <strong>{i.title}</strong>
                        <span>{i.text}</span>
                      </span>
                      <span className="biz-quick__arrow">‹</span>
                    </Link>
                  </Reveal>
                ))}
              </div>
            </div>
          </section>
        )
      case 'digital':
        return (
          <section key="digital" className="section biz-digital">
            <div className="container">
              <Reveal>
                <h2 className="section__title section__title--start">{b.digitalTitle}</h2>
                <p className="section__lead">{b.digitalLead}</p>
              </Reveal>
              <div className="biz-quick biz-quick--many">
                {quick.slice(4).map((i) => (
                  <Reveal key={i.id}>
                    <Link to={i.link} className={`biz-quick__card biz-quick__card--${i.tone || 'teal'}`}>
                      <span className="biz-quick__thumb" aria-hidden="true">
                        <ConfigurableIcon
                          icon={i.icon}
                          iconDataUrl={i.iconDataUrl}
                          className="biz-quick__icon"
                          imgClassName="biz-quick__icon-img"
                          alt=""
                        />
                      </span>
                      <span className="biz-quick__text">
                        <strong>{i.title}</strong>
                        <span>{i.text}</span>
                      </span>
                      <span className="biz-quick__arrow">‹</span>
                    </Link>
                  </Reveal>
                ))}
              </div>
              <div className="biz-offerings-grid">
                {b.digitalOfferings.map((o) => (
                  <Reveal key={o.id}>
                    <div id={o.id}>
                      <BusinessOfferingCard offering={o} />
                    </div>
                  </Reveal>
                ))}
              </div>
              <p className="biz-digital__more">
                <Link to="/business/web" className="text-link">
                  صفحة الحضور الرقمي الكاملة ‹
                </Link>
              </p>
            </div>
          </section>
        )
      case 'bundle':
        return (
          <section key="bundle" className="section biz-bundle">
            <div className="container biz-bundle__inner">
              <Reveal>
                <h2 className="section__title section__title--start">{b.bundleTitle}</h2>
                <p className="section__lead">{b.bundleText}</p>
                <ul className="biz-bundle__list">
                  {b.bundleItems.map((item) => (
                    <li key={item}>{item}</li>
                  ))}
                </ul>
                <Link to="/business/join" className="btn btn--primary">
                  اطلب باقة أعمال
                </Link>
              </Reveal>
            </div>
          </section>
        )
      case 'cta':
        return (
          <section key="cta" className="biz-cta-band biz-cta-band--warm">
            <div className="container biz-cta-band__inner">
              <div>
                <h2>جاهز لعرض مخصّص؟</h2>
                <p>Domain، Hosting، E-Card، اتصال، أو POS — أخبرنا باحتياجك وسنبني العرض المناسب.</p>
              </div>
              <Link to="/business/join" className="btn btn--primary">
                اطلب عرضاً
              </Link>
            </div>
          </section>
        )
      default:
        return null
    }
  }

  return (
    <>
      <HeroSlider audience="business" />
      {sections.map((id) => renderSection(id))}
    </>
  )
}

export function BusinessWebPage() {
  const { config } = useSiteConfig()
  const b = config.business
  const page = config.pages.businessWeb

  return (
    <>
      <CmsPageHero page={page} />
      <section className="section biz-digital biz-digital--page">
        <div className="container">
          <Reveal>
            <p className="section__lead section__lead--center">{b.digitalLead}</p>
          </Reveal>
          <div className="biz-offerings-grid biz-offerings-grid--page">
            {b.digitalOfferings.map((o) => (
              <Reveal key={o.id}>
                <div id={o.id} className="biz-offering-anchor">
                  <BusinessOfferingCard offering={o} />
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>
      <section className="section biz-bundle biz-bundle--alt">
        <div className="container biz-bundle__inner">
          <Reveal>
            <h2 className="section__title">{b.bundleTitle}</h2>
            <ul className="biz-bundle__list">
              {b.bundleItems.map((item) => (
                <li key={item}>{item}</li>
              ))}
            </ul>
            <Link to="/business/join" className="btn btn--primary">
              تواصل مع المبيعات
            </Link>
          </Reveal>
        </div>
      </section>
    </>
  )
}

export function BusinessPlansPage() {
  const { config } = useSiteConfig()
  return (
    <>
      <CmsPageHero page={config.pages.businessPlans} />
      <section className="section plans">
        <div className="container">
          <PlansGrid plans={config.businessPlans} orderTo="/business/join" showCompare />
        </div>
      </section>
    </>
  )
}

export function BusinessPosPage() {
  const { config } = useSiteConfig()
  const d = config.digital
  const page = config.pages.businessPos
  const sections = d.posSections?.length
    ? d.posSections
    : [{ id: 'core', title: d.posTitle, text: d.posText, items: d.posFeatures, imageDataUrl: null }]
  const appUrl = d.posAppUrl?.trim() || ''
  const stats = d.posStats?.length ? d.posStats : []

  usePageSeo(page)

  return (
    <>
      <section className="page-hero page-hero--product">
        <div className="container page-hero__split">
          <div className="page-hero__copy">
            <p className="page-hero__kicker">{page.kicker}</p>
            <h1>{d.posTitle}</h1>
            <p>{d.posText}</p>
            <div className="hero__actions">
              <Link to="/business/join?service=pos" className="btn btn--light">
                اطلب عرضاً وتركيباً
              </Link>
              {appUrl ? (
                <a href={appUrl} className="btn btn--outline-light" target="_blank" rel="noreferrer">
                  دخول POS
                </a>
              ) : null}
            </div>
          </div>
          <Reveal>
            <MediaFrame src={d.posHeroImage} label="واجهة نظام نقاط البيع" className="media-frame--hero" />
          </Reveal>
        </div>
      </section>

      {stats.length ? (
        <section className="pos-stats" aria-label="ملخص سريع">
          <div className="container pos-stats__grid">
            {stats.map((s) => (
              <div key={s.id} className="pos-stat">
                <strong>{s.value}</strong>
                <span>{s.label}</span>
              </div>
            ))}
          </div>
        </section>
      ) : null}

      <section className="section pos-chips">
        <div className="container">
          <div className="pos-highlights">
            {d.posFeatures.map((f) => (
              <Reveal key={f}>
                <p className="pos-highlight">{f}</p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section className="section pos-features">
        <div className="container">
          <Reveal>
            <h2 className="section__title">ماذا يقدّم النظام؟</h2>
            <p className="section__lead section__lead--center">
              كل قسم أدناه يدعم صورة توضيحية — أضف لقطات شاشة حقيقية من لوحة التحكم لتقوية الصفحة.
            </p>
          </Reveal>
          <div className="pos-feature-list">
            {sections.map((s, i) => (
              <Reveal key={s.id}>
                <article className={`pos-feature-row${i % 2 === 1 ? ' pos-feature-row--reverse' : ''}`}>
                  <div className="pos-feature-row__content">
                    <p className="pos-feature-row__index">{String(i + 1).padStart(2, '0')}</p>
                    <h3>{s.title}</h3>
                    <p>{s.text}</p>
                    <ul className="pos-checklist">
                      {s.items.map((item) => (
                        <li key={item}>{item}</li>
                      ))}
                    </ul>
                  </div>
                  <MediaFrame src={s.imageDataUrl} label={s.title} className="media-frame--feature" />
                </article>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section className="biz-cta-band biz-cta-band--pos">
        <div className="container biz-cta-band__inner">
          <div>
            <h2>ابدأ بنظام نقاط بيع يناسب محلك</h2>
            <p>تركيب، تدريب، وربط مع خط مدد — من طلب واحد.</p>
          </div>
          <div className="biz-cta-band__actions">
            <Link to="/business/join?service=pos" className="btn btn--light">
              اطلب عرضاً
            </Link>
            {appUrl ? (
              <a href={appUrl} className="btn btn--outline-light" target="_blank" rel="noreferrer">
                تجربة الدخول
              </a>
            ) : null}
          </div>
        </div>
      </section>
    </>
  )
}

export function BusinessHrPage() {
  const { config } = useSiteConfig()
  const d = config.digital
  const page = { ...config.pages.businessHr, title: d.hrTitle, lead: d.hrText, cta: 'اطلب النظام', ctaLink: '/business/join?service=hr' }
  return (
    <>
      <CmsPageHero page={page} />
      <section className="section">
        <div className="container">
          <article className="digital-card digital-card--centered">
            <MediaFrame label="نظام الحضور والموظفين" className="media-frame--inline" />
            <ul>
              {d.hrFeatures.map((f) => (
                <li key={f}>{f}</li>
              ))}
            </ul>
            <Link to="/business/join?service=hr" className="btn btn--primary">
              اطلب عرضاً
            </Link>
          </article>
        </div>
      </section>
    </>
  )
}

export function BusinessProgrammingPage() {
  const { config } = useSiteConfig()
  return (
    <>
      <CmsPageHero page={config.pages.businessProgramming} />
      <ProgrammingSection orderPath="/business/join?service=programming" />
    </>
  )
}

export function BusinessJoinPage() {
  const [params] = useSearchParams()
  const map: Record<string, string> = {
    pos: 'نظام نقاط البيع',
    hr: 'إدارة الحضور والموظفين',
    programming: 'تطوير رقمي',
    domain: 'Domain — نطاق',
    hosting: 'Web Hosting — استضافة',
    ecard: 'E-Card — بطاقة رقمية',
    email: 'Business Email — بريد مؤسسي',
    ssl: 'SSL & Security',
    dns: 'DNS & Cloud',
  }
  const { config } = useSiteConfig()
  const [service, setService] = useState(map[params.get('service') || ''] || '')
  const [name, setName] = useState('')
  const [company, setCompany] = useState('')
  const [phone, setPhone] = useState('')
  const [email, setEmail] = useState('')
  const [address, setAddress] = useState('')
  const [ok, setOk] = useState(false)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  const options = [
    ...config.businessPlans.map((p) => `اتصال أعمال — ${p.name} (${p.speed} ميجا)`),
    'نظام نقاط البيع',
    'إدارة الحضور والموظفين',
    'تطوير رقمي',
    'Domain — نطاق',
    'Web Hosting — استضافة',
    'E-Card — بطاقة رقمية',
    'Business Email — بريد مؤسسي',
    'SSL & Security',
    'DNS & إعدادات سحابية',
    'استشارة مخصصة',
  ]

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setError('')
    try {
      await submitSiteLead({
        type: 'business_join',
        name,
        phone,
        email: email || undefined,
        area: address || undefined,
        plan_name: service || undefined,
        message: company ? `المنشأة: ${company}` : undefined,
        meta: { company, service },
        source: 'business-join',
      })
      setOk(true)
      setName('')
      setCompany('')
      setPhone('')
      setEmail('')
      setAddress('')
      setService('')
      setTimeout(() => setOk(false), 5000)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'تعذّر الإرسال')
    } finally {
      setBusy(false)
    }
  }

  return (
    <>
      <CmsPageHero page={config.pages.businessJoin} />
      <section className="section order">
        <div className="container">
          <form className="order__form" style={{ maxWidth: 680, marginInline: 'auto' }} onSubmit={onSubmit}>
            <div className="form-row">
              <div className="field">
                <label>الاسم الكامل</label>
                <input required value={name} onChange={(e) => setName(e.target.value)} />
              </div>
              <div className="field">
                <label>اسم الشركة</label>
                <input required value={company} onChange={(e) => setCompany(e.target.value)} />
              </div>
            </div>
            <div className="form-row">
              <div className="field">
                <label>رقم الهاتف</label>
                <input required value={phone} onChange={(e) => setPhone(e.target.value)} />
              </div>
              <div className="field">
                <label>البريد الإلكتروني</label>
                <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
              </div>
            </div>
            <div className="field">
              <label>الخدمة المطلوبة</label>
              <select value={service} onChange={(e) => setService(e.target.value)} required>
                <option value="">اختر الخدمة</option>
                {options.map((o) => (
                  <option key={o} value={o}>
                    {o}
                  </option>
                ))}
              </select>
            </div>
            <div className="field">
              <label>المدينة / الموقع</label>
              <input required value={address} onChange={(e) => setAddress(e.target.value)} />
            </div>
            <button className="btn btn--primary btn--block" type="submit" disabled={busy}>
              {busy ? 'جاري الإرسال...' : 'إرسال الطلب'}
            </button>
            {ok ? <p className="form-success">شكراً لك. تم استلام طلبك وسنتواصل قريباً.</p> : null}
            {error ? <p className="admin-error">{error}</p> : null}
          </form>
        </div>
      </section>
    </>
  )
}
