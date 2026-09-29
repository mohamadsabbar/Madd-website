import { useEffect, useState, type FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useSiteConfig } from '../context/SiteConfigContext'
import { submitSiteLead } from '../lib/siteApi'

const ACCENTS = ['blue', 'orange', 'rose', 'teal'] as const

export function OrderPage() {
  const [params] = useSearchParams()
  const { config } = useSiteConfig()
  if (params.get('service') === 'programming') {
    return <ProgrammingOrderFallback />
  }
  return (
    <>
      <div className="order-page-hero-wrap">
        {/* lightweight page context from CMS */}
      </div>
      <FiberOnboarding pageTitle={config.pages.order.title} pageLead={config.pages.order.lead} />
    </>
  )
}

function FiberOnboarding({ pageTitle, pageLead }: { pageTitle: string; pageLead: string }) {
  const { config } = useSiteConfig()
  const [params] = useSearchParams()
  const plans = config.plans
  const planFromQuery = params.get('plan')
  const initialPlan =
    plans.find((p) => p.id === planFromQuery)?.id || plans.find((p) => p.featured)?.id || plans[0]?.id || ''

  const [tab, setTab] = useState<'new' | 'existing'>('new')
  const [planId, setPlanId] = useState(initialPlan)
  const [name, setName] = useState('')
  const [phone, setPhone] = useState('')
  const [email, setEmail] = useState('')
  const [account, setAccount] = useState('')
  const [location, setLocation] = useState('')
  const [locStatus, setLocStatus] = useState('')
  const [ok, setOk] = useState(false)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (planFromQuery && plans.some((p) => p.id === planFromQuery)) {
      setPlanId(planFromQuery)
    }
  }, [planFromQuery, plans])

  const useMyLocation = () => {
    if (!navigator.geolocation) {
      setLocStatus('المتصفح لا يدعم تحديد الموقع.')
      return
    }
    setLocStatus('جاري تحديد موقعك...')
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const { latitude, longitude } = pos.coords
        const coords = `${latitude.toFixed(5)}, ${longitude.toFixed(5)}`
        setLocation((prev) => (prev ? `${prev} — ${coords}` : coords))
        setLocStatus('تم تحديد الموقع.')
      },
      () => setLocStatus('تعذّر الوصول للموقع. اكتب العنوان يدوياً.'),
      { enableHighAccuracy: true, timeout: 10000 },
    )
  }

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setError('')
    const plan = plans.find((p) => p.id === planId)
    try {
      await submitSiteLead({
        type: 'fiber',
        name,
        phone,
        email: email || undefined,
        area: location || undefined,
        plan_id: planId || undefined,
        plan_name: plan ? `${plan.name} — ${plan.speed} ميجا` : undefined,
        message: tab === 'existing' ? `مشترك حالي — رقم الحساب: ${account}` : undefined,
        meta: { tab, account: account || null, locStatus },
        source: 'order-fiber',
      })
      setOk(true)
      setName('')
      setPhone('')
      setEmail('')
      setAccount('')
      setLocation('')
      setLocStatus('')
      window.scrollTo({ top: 0, behavior: 'smooth' })
      setTimeout(() => setOk(false), 6000)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'تعذّر إرسال الطلب')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="fiber-onboard">
      <div className="fiber-onboard__crumb">
        <div className="container">
          <Link to="/">الرئيسية</Link>
          <span aria-hidden="true"> / </span>
          <span>طلب واي فايبر</span>
        </div>
      </div>

      <div className="container fiber-onboard__body">
        <header className="fiber-onboard__head">
          <h1>{pageTitle}</h1>
          <p>{pageLead}</p>
        </header>

        {ok ? (
          <div className="fiber-onboard__success" role="status">
            تم استلام طلبك بنجاح. سيتواصل معك فريق مدد قريباً.
          </div>
        ) : null}
        {error ? <div className="fiber-onboard__error" role="alert">{error}</div> : null}

        <div className="fiber-tabs" role="tablist" aria-label="نوع الاشتراك">
          <button
            type="button"
            role="tab"
            aria-selected={tab === 'new'}
            className={`fiber-tab${tab === 'new' ? ' is-active' : ''}`}
            onClick={() => setTab('new')}
          >
            مشترك جديد
          </button>
          <button
            type="button"
            role="tab"
            aria-selected={tab === 'existing'}
            className={`fiber-tab${tab === 'existing' ? ' is-active' : ''}`}
            onClick={() => setTab('existing')}
          >
            مشترك حالي
          </button>
        </div>

        <form className="fiber-form" onSubmit={onSubmit}>
          {tab === 'new' ? (
            <section className="fiber-section">
              <h2>السرعة المطلوبة</h2>
              {plans.length === 0 ? (
                <p className="fiber-hint">لا توجد باقات حالياً. أضفها من لوحة الإدارة.</p>
              ) : (
                <div className={`fiber-speeds fiber-speeds--${Math.min(4, plans.length)}`}>
                  {plans.map((plan, i) => {
                    const accent = plan.accent || ACCENTS[i % ACCENTS.length]
                    const selected = planId === plan.id
                    return (
                      <button
                        key={plan.id}
                        type="button"
                        className={`fiber-speed fiber-speed--${accent}${selected ? ' is-selected' : ''}`}
                        onClick={() => setPlanId(plan.id)}
                        aria-pressed={selected}
                      >
                        <span className="fiber-speed__num">{plan.speed}</span>
                        <span className="fiber-speed__label">{plan.speed} ميجابت بالثانية</span>
                        <span className="fiber-speed__meta">
                          {plan.name} — {plan.price} شيكل / شهر
                        </span>
                      </button>
                    )
                  })}
                </div>
              )}
            </section>
          ) : (
            <section className="fiber-section">
              <h2>بيانات الاشتراك الحالي</h2>
              <label className="fiber-field">
                <span className="fiber-field__label">
                  رقم الاشتراك / الحساب <em>*</em>
                </span>
                <input
                  value={account}
                  onChange={(e) => setAccount(e.target.value)}
                  placeholder="رقم الاشتراك / الحساب"
                  required={tab === 'existing'}
                />
              </label>
            </section>
          )}

          <section className="fiber-section">
            <h2>معلومات التواصل</h2>
            <label className="fiber-field">
              <span className="fiber-field__label">
                الاسم <em>*</em>
              </span>
              <input
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="الاسم"
                required
                autoComplete="name"
              />
            </label>
            <label className="fiber-field">
              <span className="fiber-field__label">
                رقم الجوال <em>*</em>
              </span>
              <input
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="رقم الجوال"
                required
                inputMode="tel"
                autoComplete="tel"
              />
            </label>
            <label className="fiber-field">
              <span className="fiber-field__label">البريد الالكتروني</span>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="البريد الالكتروني"
                autoComplete="email"
              />
            </label>
            <p className="fiber-note">* تدل على جميع الحقول المطلوبة</p>
          </section>

          <section className="fiber-section">
            <h2>عنوان التركيب</h2>
            <div className="fiber-location">
              <label className="fiber-field fiber-location__input">
                <span className="fiber-field__label">
                  موقع التركيب <em>*</em>
                </span>
                <input
                  value={location}
                  onChange={(e) => setLocation(e.target.value)}
                  placeholder="المدينة، الحي، الشارع..."
                  required
                />
              </label>
              <button type="button" className="fiber-location__btn" onClick={useMyLocation}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <circle cx="12" cy="12" r="3" stroke="currentColor" strokeWidth="2" />
                  <path
                    d="M12 2v3M12 19v3M2 12h3M19 12h3"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                  />
                  <circle cx="12" cy="12" r="8" stroke="currentColor" strokeWidth="2" />
                </svg>
                استخدم موقعي الحالي
              </button>
            </div>
            {locStatus ? <p className="fiber-hint">{locStatus}</p> : null}
          </section>

          <button className="btn btn--primary fiber-submit" type="submit" disabled={busy}>
            {busy ? 'جاري الإرسال...' : 'إرسال الطلب'}
          </button>
        </form>
      </div>
    </div>
  )
}

function ProgrammingOrderFallback() {
  const { config } = useSiteConfig()
  const [ok, setOk] = useState(false)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const [name, setName] = useState('')
  const [phone, setPhone] = useState('')
  const [notes, setNotes] = useState('')

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setError('')
    try {
      await submitSiteLead({
        type: 'programming',
        name,
        phone,
        message: notes || undefined,
        source: 'order-programming',
      })
      setOk(true)
      setName('')
      setPhone('')
      setNotes('')
      setTimeout(() => setOk(false), 5000)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'تعذّر إرسال الطلب')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="fiber-onboard">
      <div className="fiber-onboard__crumb">
        <div className="container">
          <Link to="/">الرئيسية</Link>
          <span aria-hidden="true"> / </span>
          <Link to="/programming">البرمجة</Link>
          <span aria-hidden="true"> / </span>
          <span>طلب مشروع</span>
        </div>
      </div>
      <div className="container fiber-onboard__body">
        <header className="fiber-onboard__head">
          <h1>{config.pages.programming.title}</h1>
          <p>{config.pages.programming.lead}</p>
        </header>
        <form className="fiber-form" onSubmit={onSubmit}>
          <label className="fiber-field">
            <span className="fiber-field__label">
              الاسم <em>*</em>
            </span>
            <input value={name} onChange={(e) => setName(e.target.value)} required />
          </label>
          <label className="fiber-field">
            <span className="fiber-field__label">
              رقم الجوال <em>*</em>
            </span>
            <input value={phone} onChange={(e) => setPhone(e.target.value)} required />
          </label>
          <label className="fiber-field">
            <span className="fiber-field__label">وصف المشروع</span>
            <textarea value={notes} onChange={(e) => setNotes(e.target.value)} rows={4} />
          </label>
          <button className="btn btn--primary fiber-submit" type="submit" disabled={busy}>
            {busy ? 'جاري الإرسال...' : 'إرسال الطلب'}
          </button>
          {ok ? <p className="fiber-onboard__success">تم استلام طلبك.</p> : null}
          {error ? <p className="fiber-onboard__error">{error}</p> : null}
        </form>
      </div>
    </div>
  )
}
