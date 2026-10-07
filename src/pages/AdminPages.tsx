import { useEffect, useMemo, useState, type FormEvent } from 'react'
import { Link, Navigate, Outlet, useNavigate } from 'react-router-dom'
import { AUTH_KEY, ADMIN_PASSWORD_SESSION, type HeroSlide, type OfferConfig } from '../config/defaultSite'
import { HOME_SECTION_LABELS, BUSINESS_HOME_SECTION_LABELS, moveItem, normalizeHomeSections, normalizeBusinessHomeSections, type HomeSectionId, type BusinessHomeSectionId } from '../config/order'
import { SERVICE_ICON_OPTIONS } from '../components/ServiceIcon'
import { useSiteConfig } from '../context/SiteConfigContext'
import { fetchSiteLeads, updateSiteLeadStatus } from '../lib/siteApi'

function isAuthed() {
  return sessionStorage.getItem(AUTH_KEY) === '1'
}

function ReorderButtons({
  index,
  total,
  onMove,
}: {
  index: number
  total: number
  onMove: (from: number, to: number) => void
}) {
  return (
    <div className="admin-reorder" role="group" aria-label="ترتيب">
      <button
        type="button"
        className="admin-reorder__btn"
        disabled={index === 0}
        onClick={() => onMove(index, index - 1)}
        title="تحريك لأعلى"
        aria-label="تحريك لأعلى"
      >
        ↑
      </button>
      <button
        type="button"
        className="admin-reorder__btn"
        disabled={index >= total - 1}
        onClick={() => onMove(index, index + 1)}
        title="تحريك لأسفل"
        aria-label="تحريك لأسفل"
      >
        ↓
      </button>
    </div>
  )
}

export function AdminLoginPage() {
  const { config } = useSiteConfig()
  const nav = useNavigate()
  const [pass, setPass] = useState('')
  const [err, setErr] = useState('')

  useEffect(() => {
    if (isAuthed()) nav('/admin/dashboard', { replace: true })
  }, [nav])

  const onSubmit = (e: FormEvent) => {
    e.preventDefault()
    if (pass === config.admin.password) {
      sessionStorage.setItem(AUTH_KEY, '1')
      sessionStorage.setItem(ADMIN_PASSWORD_SESSION, pass)
      nav('/admin/dashboard')
    } else setErr('كلمة المرور غير صحيحة')
  }

  return (
    <div className="admin-login">
      <form className="admin-login__card" onSubmit={onSubmit}>
        <h1>مدد</h1>
        <p>أدخل كلمة المرور للمتابعة</p>
        <input
          type="password"
          value={pass}
          onChange={(e) => setPass(e.target.value)}
          placeholder="كلمة المرور"
          autoFocus
        />
        {err ? <p className="admin-error">{err}</p> : null}
        <button className="btn btn--primary btn--block" type="submit">
          دخول
        </button>
        <p className="admin-hint">الافتراضي: maddadmin</p>
        <Link to="/">العودة للموقع</Link>
      </form>
    </div>
  )
}

export function AdminGuard() {
  if (!isAuthed()) return <Navigate to="/admin" replace />
  return <Outlet />
}

export function AdminLayout() {
  const nav = useNavigate()
  const { syncing, syncError, persistToServer } = useSiteConfig()
  const logout = () => {
    sessionStorage.removeItem(AUTH_KEY)
    sessionStorage.removeItem(ADMIN_PASSWORD_SESSION)
    nav('/admin')
  }

  return (
    <div className="admin-shell">
      <aside className="admin-side">
        <div className="admin-side__brand">مدد</div>
        <nav>
          <Link to="/admin/dashboard">نظرة عامة</Link>
          <Link to="/admin/branding">الشعار والهوية</Link>
          <Link to="/admin/content">المحتوى</Link>
          <Link to="/admin/pages">صفحات الموقع</Link>
          <Link to="/admin/coverage">التغطية</Link>
          <Link to="/admin/leads">الطلبات</Link>
          <Link to="/admin/slider">السلايدر</Link>
          <Link to="/admin/menu">القائمة</Link>
          <Link to="/admin/order">ترتيب الأقسام</Link>
          <Link to="/admin/offers">العروض</Link>
          <Link to="/admin/faq">الأسئلة الشائعة</Link>
          <Link to="/admin/plans">الباقات</Link>
          <Link to="/admin/programming">البرمجة</Link>
          <Link to="/admin/digital">POS والموظفين</Link>
          <Link to="/admin/visibility">الظهور والقوائم</Link>
          <Link to="/admin/seo">SEO</Link>
          <Link to="/admin/settings">الإعدادات</Link>
        </nav>
        <p className="admin-sync">
          {syncing ? 'جاري الحفظ على السيرفر…' : syncError ? `تحذير: ${syncError}` : 'متزامن مع السيرفر'}
        </p>
        <button type="button" className="btn btn--ghost" onClick={() => void persistToServer()}>
          حفظ الآن
        </button>
        <button type="button" className="btn btn--ghost" onClick={logout}>
          تسجيل الخروج
        </button>
        <Link to="/" className="admin-side__site">
          عرض الموقع ↗
        </Link>
      </aside>
      <div className="admin-main">
        <Outlet />
      </div>
    </div>
  )
}

export function AdminDashboard() {
  const { config, syncing, syncError } = useSiteConfig()
  return (
    <div className="admin-page">
      <h1>نظرة عامة</h1>
      <p className="admin-lead">
        المحتوى يُحفظ على السيرفر (Laravel) ويظهر لجميع الزوار.
        {syncing ? ' جاري المزامنة…' : syncError ? ` خطأ مزامنة: ${syncError}` : ' الحالة: متزامن.'}
      </p>
      <div className="admin-cards">
        <div className="admin-stat">
          <strong>{config.brand.nameAr}</strong>
          <span>اسم العلامة</span>
        </div>
        <div className="admin-stat">
          <strong>{config.plans.length}</strong>
          <span>باقات أفراد</span>
        </div>
        <div className="admin-stat">
          <strong>{config.coverage.areas.length}</strong>
          <span>مناطق تغطية</span>
        </div>
        <div className="admin-stat">
          <strong>{config.faq.length}</strong>
          <span>أسئلة شائعة</span>
        </div>
      </div>
      <div className="admin-quick-links">
        <Link to="/admin/pages" className="btn btn--primary">
          صفحات الموقع
        </Link>
        <Link to="/admin/leads" className="btn btn--ghost">
          الطلبات
        </Link>
        <Link to="/admin/coverage" className="btn btn--ghost">
          التغطية
        </Link>
        <Link to="/admin/branding" className="btn btn--ghost">
          الهوية
        </Link>
      </div>
    </div>
  )
}

export function AdminBranding() {
  const { config, updateConfig, setConfig } = useSiteConfig()
  const brand = config.brand

  const onImage = (field: 'logoDataUrl' | 'faviconDataUrl', file: File | null) => {
    if (!file) return
    const reader = new FileReader()
    reader.onload = () => {
      setConfig({
        ...config,
        brand: { ...brand, [field]: String(reader.result) },
      })
    }
    reader.readAsDataURL(file)
  }

  return (
    <div className="admin-page">
      <h1>الشعار والهوية</h1>
      <div className="admin-form">
        <label>
          الاسم بالعربي
          <input
            value={brand.nameAr}
            onChange={(e) => updateConfig({ brand: { ...brand, nameAr: e.target.value } })}
          />
        </label>
        <label>
          الاسم بالإنجليزي
          <input
            value={brand.nameEn}
            onChange={(e) => updateConfig({ brand: { ...brand, nameEn: e.target.value } })}
          />
        </label>
        <label>
          اللون الأساسي
          <input
            type="color"
            value={brand.primaryColor}
            onChange={(e) => updateConfig({ brand: { ...brand, primaryColor: e.target.value } })}
          />
        </label>
        <label>
          اللون الثانوي — أفراد وعروض (مرجان)
          <input
            type="color"
            value={brand.secondaryColor}
            onChange={(e) => updateConfig({ brand: { ...brand, secondaryColor: e.target.value } })}
          />
        </label>
        <p className="admin-hint">الافتراضي المقترح: #ed875e — مع تمييز أقوى #e07a52 للأزرار المميزة.</p>
        <label>
          اللون الثانوي — أعمال (نحاسي)
          <input
            type="color"
            value={brand.businessSecondaryColor || '#0a4bb8'}
            onChange={(e) =>
              updateConfig({ brand: { ...brand, businessSecondaryColor: e.target.value } })
            }
          />
        </label>
        <label>
          تمييز أعمال (سماوي)
          <input
            type="color"
            value={brand.businessHighlightColor || '#38bdf8'}
            onChange={(e) =>
              updateConfig({ brand: { ...brand, businessHighlightColor: e.target.value } })
            }
          />
        </label>
        <p className="admin-hint">
          عند فتح /business يتحول التمييز إلى النحاس والسماوي تلقائياً. على الرئيسية والعروض يبقى المرجان.
        </p>
        <label>
          الشعار (صورة)
          <input type="file" accept="image/*" onChange={(e) => onImage('logoDataUrl', e.target.files?.[0] || null)} />
        </label>
        {brand.logoDataUrl ? (
          <div className="admin-logo-preview">
            <img src={brand.logoDataUrl} alt="logo" />
            <button
              type="button"
              className="btn btn--ghost"
              onClick={() => updateConfig({ brand: { ...brand, logoDataUrl: null } })}
            >
              حذف الشعار المرفوع
            </button>
          </div>
        ) : (
          <p className="admin-hint">لم يُرفع شعار — يُستخدم الرمز الافتراضي.</p>
        )}
        <label>
          أيقونة المتصفح (Favicon)
          <input
            type="file"
            accept="image/*"
            onChange={(e) => onImage('faviconDataUrl', e.target.files?.[0] || null)}
          />
        </label>
        <label>
          شعار التذييل / الوصف القصير
          <input
            value={brand.tagline}
            onChange={(e) => updateConfig({ brand: { ...brand, tagline: e.target.value } })}
          />
        </label>
        <label>
          نص زر الأفراد
          <input
            value={brand.ctaIndividuals}
            onChange={(e) => updateConfig({ brand: { ...brand, ctaIndividuals: e.target.value } })}
          />
        </label>
        <label>
          نص زر الأعمال
          <input
            value={brand.ctaBusiness}
            onChange={(e) => updateConfig({ brand: { ...brand, ctaBusiness: e.target.value } })}
          />
        </label>
      </div>
    </div>
  )
}

export function AdminContent() {
  const { config, updateConfig, setConfig } = useSiteConfig()
  return (
    <div className="admin-page">
      <h1>المحتوى</h1>
      <div className="admin-form">
        <p className="admin-hint">
          بطاقات الخدمات في الرئيسية: عدّل العنوان والبطاقات (الاسم، الرابط، أيقونة جاهزة أو رفع PNG/SVG مخصص) من القسم أدناه.
          لصور السلايدر ومؤشر الشرائح افتح{' '}
          <Link to="/admin/slider">السلايدر</Link>.
        </p>
        <h2>الصفحة الرئيسية — الخدمات</h2>
        <label>
          عنوان قسم الخدمات
          <input
            value={config.home.benefitsTitle}
            onChange={(e) => updateConfig({ home: { ...config.home, benefitsTitle: e.target.value } })}
          />
        </label>
        <h2>بطاقات الخدمات</h2>
        {config.home.benefits.map((b, i) => (
          <div key={i} className="admin-plan-card">
            <div className="admin-plan-card__top">
              <strong>خدمة {i + 1}</strong>
              <div className="admin-plan-card__actions">
                <ReorderButtons
                  index={i}
                  total={config.home.benefits.length}
                  onMove={(from, to) =>
                    setConfig((prev) => ({
                      ...prev,
                      home: { ...prev.home, benefits: moveItem(prev.home.benefits, from, to) },
                    }))
                  }
                />
                <button
                  type="button"
                  className="btn btn--danger"
                  onClick={() => {
                    setConfig((prev) => ({
                      ...prev,
                      home: {
                        ...prev.home,
                        benefits: prev.home.benefits.filter((_, j) => j !== i),
                      },
                    }))
                  }}
                >
                  حذف
                </button>
              </div>
            </div>
            <input
              value={b.title}
              placeholder="اسم الخدمة"
              onChange={(e) => {
                setConfig((prev) => {
                  const benefits = [...prev.home.benefits]
                  benefits[i] = { ...benefits[i], title: e.target.value }
                  return { ...prev, home: { ...prev.home, benefits } }
                })
              }}
            />
            <input
              value={b.link || ''}
              placeholder="الرابط مثل /plans"
              onChange={(e) => {
                setConfig((prev) => {
                  const benefits = [...prev.home.benefits]
                  benefits[i] = { ...benefits[i], link: e.target.value }
                  return { ...prev, home: { ...prev.home, benefits } }
                })
              }}
            />
            <label>
              الأيقونة الجاهزة (احتياطي)
              <select
                value={b.icon || 'globe'}
                onChange={(e) => {
                  setConfig((prev) => {
                    const benefits = [...prev.home.benefits]
                    benefits[i] = { ...benefits[i], icon: e.target.value }
                    return { ...prev, home: { ...prev.home, benefits } }
                  })
                }}
              >
                {SERVICE_ICON_OPTIONS.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </label>
            <label>
              أيقونة مخصصة (PNG / SVG / WebP)
              <input
                type="file"
                accept="image/png,image/jpeg,image/webp,image/svg+xml,image/*"
                onChange={(e) => {
                  const file = e.target.files?.[0]
                  if (!file) return
                  const reader = new FileReader()
                  reader.onload = () => {
                    setConfig((prev) => {
                      const benefits = [...prev.home.benefits]
                      benefits[i] = { ...benefits[i], iconDataUrl: String(reader.result) }
                      return { ...prev, home: { ...prev.home, benefits } }
                    })
                  }
                  reader.readAsDataURL(file)
                  e.target.value = ''
                }}
              />
            </label>
            {b.iconDataUrl ? (
              <div className="admin-thumb">
                <img src={b.iconDataUrl} alt="" />
                <button
                  type="button"
                  className="btn btn--ghost"
                  onClick={() => {
                    setConfig((prev) => {
                      const benefits = [...prev.home.benefits]
                      benefits[i] = { ...benefits[i], iconDataUrl: null }
                      return { ...prev, home: { ...prev.home, benefits } }
                    })
                  }}
                >
                  إزالة الأيقونة المخصصة
                </button>
              </div>
            ) : null}
          </div>
        ))}
        <button
          type="button"
          className="btn btn--ghost"
          onClick={() =>
            setConfig((prev) => ({
              ...prev,
              home: {
                ...prev.home,
                benefits: [
                  ...prev.home.benefits,
                  { title: 'خدمة جديدة', text: '', link: '/', icon: 'globe', iconDataUrl: null },
                ],
              },
            }))
          }
        >
          + إضافة خدمة
        </button>

        <h2>الصفحة الرئيسية — أعمال</h2>
        <label>
          عنوان الهيرو
          <input
            value={config.businessHome.heroTitle}
            onChange={(e) =>
              updateConfig({ businessHome: { ...config.businessHome, heroTitle: e.target.value } })
            }
          />
        </label>
        <label>
          وصف الهيرو
          <textarea
            rows={3}
            value={config.businessHome.heroSubtitle}
            onChange={(e) =>
              updateConfig({ businessHome: { ...config.businessHome, heroSubtitle: e.target.value } })
            }
          />
        </label>
        <label>
          نص الزر
          <input
            value={config.businessHome.heroCta}
            onChange={(e) =>
              updateConfig({ businessHome: { ...config.businessHome, heroCta: e.target.value } })
            }
          />
        </label>

        <h2>صفحة الأعمال — روابط سريعة</h2>
        <p className="admin-hint">
          البطاقات في أقسام «اتصال وأنظمة تشغيل» و«حضورك الرقمي» — العنوان، الوصف، الرابط، أيقونة جاهزة أو رفع PNG/SVG مخصص.
        </p>
        <label>
          عنوان قسم الاتصال
          <input
            value={config.business.connectTitle}
            onChange={(e) =>
              updateConfig({ business: { ...config.business, connectTitle: e.target.value } })
            }
          />
        </label>
        <label>
          وصف قسم الاتصال
          <textarea
            rows={2}
            value={config.business.connectLead}
            onChange={(e) =>
              updateConfig({ business: { ...config.business, connectLead: e.target.value } })
            }
          />
        </label>
        <label>
          عنوان قسم الحضور الرقمي
          <input
            value={config.business.digitalTitle}
            onChange={(e) =>
              updateConfig({ business: { ...config.business, digitalTitle: e.target.value } })
            }
          />
        </label>
        <label>
          وصف قسم الحضور الرقمي
          <textarea
            rows={2}
            value={config.business.digitalLead}
            onChange={(e) =>
              updateConfig({ business: { ...config.business, digitalLead: e.target.value } })
            }
          />
        </label>
        {config.business.quickLinks.map((q, i) => (
          <div key={q.id} className="admin-plan-card">
            <div className="admin-plan-card__top">
              <strong>رابط {i + 1}</strong>
              <div className="admin-plan-card__actions">
                <ReorderButtons
                  index={i}
                  total={config.business.quickLinks.length}
                  onMove={(from, to) =>
                    setConfig((prev) => ({
                      ...prev,
                      business: {
                        ...prev.business,
                        quickLinks: moveItem(prev.business.quickLinks, from, to),
                      },
                    }))
                  }
                />
              </div>
            </div>
            <input
              value={q.title}
              placeholder="العنوان"
              onChange={(e) => {
                setConfig((prev) => {
                  const quickLinks = [...prev.business.quickLinks]
                  quickLinks[i] = { ...quickLinks[i], title: e.target.value }
                  return { ...prev, business: { ...prev.business, quickLinks } }
                })
              }}
            />
            <input
              value={q.text}
              placeholder="سطر فرعي"
              onChange={(e) => {
                setConfig((prev) => {
                  const quickLinks = [...prev.business.quickLinks]
                  quickLinks[i] = { ...quickLinks[i], text: e.target.value }
                  return { ...prev, business: { ...prev.business, quickLinks } }
                })
              }}
            />
            <input
              value={q.link}
              placeholder="الرابط"
              onChange={(e) => {
                setConfig((prev) => {
                  const quickLinks = [...prev.business.quickLinks]
                  quickLinks[i] = { ...quickLinks[i], link: e.target.value }
                  return { ...prev, business: { ...prev.business, quickLinks } }
                })
              }}
            />
            <label>
              الأيقونة الجاهزة (احتياطي)
              <select
                value={q.icon || 'globe'}
                onChange={(e) => {
                  setConfig((prev) => {
                    const quickLinks = [...prev.business.quickLinks]
                    quickLinks[i] = { ...quickLinks[i], icon: e.target.value }
                    return { ...prev, business: { ...prev.business, quickLinks } }
                  })
                }}
              >
                {SERVICE_ICON_OPTIONS.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </label>
            <label>
              أيقونة مخصصة (PNG / SVG / WebP)
              <input
                type="file"
                accept="image/png,image/jpeg,image/webp,image/svg+xml,image/*"
                onChange={(e) => {
                  const file = e.target.files?.[0]
                  if (!file) return
                  const reader = new FileReader()
                  reader.onload = () => {
                    setConfig((prev) => {
                      const quickLinks = [...prev.business.quickLinks]
                      quickLinks[i] = { ...quickLinks[i], iconDataUrl: String(reader.result) }
                      return { ...prev, business: { ...prev.business, quickLinks } }
                    })
                  }
                  reader.readAsDataURL(file)
                  e.target.value = ''
                }}
              />
            </label>
            {q.iconDataUrl ? (
              <div className="admin-thumb">
                <img src={q.iconDataUrl} alt="" />
                <button
                  type="button"
                  className="btn btn--ghost"
                  onClick={() => {
                    setConfig((prev) => {
                      const quickLinks = [...prev.business.quickLinks]
                      quickLinks[i] = { ...quickLinks[i], iconDataUrl: null }
                      return { ...prev, business: { ...prev.business, quickLinks } }
                    })
                  }}
                >
                  إزالة الأيقونة المخصصة
                </button>
              </div>
            ) : null}
          </div>
        ))}

        <h2>صفحة الأعمال — بطاقات الصور</h2>
        <p className="admin-hint">
          3 بطاقات صورة فقط بجانب بعض — ارفع الصورة وحدّد الرابط. لا يظهر نص على الموقع.
        </p>
        <label>
          عنوان القسم
          <input
            value={config.business.pillarsTitle}
            onChange={(e) =>
              updateConfig({ business: { ...config.business, pillarsTitle: e.target.value } })
            }
          />
        </label>
        {config.business.pillars.slice(0, 3).map((p, i) => (
          <div key={p.id} className="admin-plan-card">
            <div className="admin-plan-card__top">
              <strong>بطاقة {i + 1}</strong>
              <div className="admin-plan-card__actions">
                <ReorderButtons
                  index={i}
                  total={Math.min(3, config.business.pillars.length)}
                  onMove={(from, to) =>
                    setConfig((prev) => ({
                      ...prev,
                      business: {
                        ...prev.business,
                        pillars: moveItem(prev.business.pillars.slice(0, 3), from, to),
                      },
                    }))
                  }
                />
              </div>
            </div>
            <label>
              صورة البطاقة
              <input
                type="file"
                accept="image/*"
                onChange={(e) => {
                  const file = e.target.files?.[0]
                  if (!file) return
                  const reader = new FileReader()
                  reader.onload = () => {
                    setConfig((prev) => {
                      const pillars = [...prev.business.pillars].slice(0, 3)
                      pillars[i] = { ...pillars[i], imageDataUrl: String(reader.result) }
                      return { ...prev, business: { ...prev.business, pillars } }
                    })
                  }
                  reader.readAsDataURL(file)
                  e.target.value = ''
                }}
              />
            </label>
            {p.imageDataUrl ? (
              <div className="admin-thumb">
                <img src={p.imageDataUrl} alt="" />
                <button
                  type="button"
                  className="btn btn--ghost"
                  onClick={() => {
                    setConfig((prev) => {
                      const pillars = [...prev.business.pillars].slice(0, 3)
                      pillars[i] = { ...pillars[i], imageDataUrl: null }
                      return { ...prev, business: { ...prev.business, pillars } }
                    })
                  }}
                >
                  إزالة الصورة
                </button>
              </div>
            ) : null}
            <input
              value={p.title}
              placeholder="اسم داخلي (للوصولية فقط)"
              onChange={(e) => {
                setConfig((prev) => {
                  const pillars = [...prev.business.pillars].slice(0, 3)
                  pillars[i] = { ...pillars[i], title: e.target.value }
                  return { ...prev, business: { ...prev.business, pillars } }
                })
              }}
            />
            <input
              value={p.link}
              placeholder="الرابط مثل /business/plans"
              onChange={(e) => {
                setConfig((prev) => {
                  const pillars = [...prev.business.pillars].slice(0, 3)
                  pillars[i] = { ...pillars[i], link: e.target.value }
                  return { ...prev, business: { ...prev.business, pillars } }
                })
              }}
            />
          </div>
        ))}

        <h2>تسميات الشريط العلوي</h2>
        <label>
          أفراد
          <input
            value={config.labels.individuals}
            onChange={(e) => updateConfig({ labels: { ...config.labels, individuals: e.target.value } })}
          />
        </label>
        <label>
          أعمال
          <input
            value={config.labels.business}
            onChange={(e) => updateConfig({ labels: { ...config.labels, business: e.target.value } })}
          />
        </label>
        <label>
          من نحن
          <input
            value={config.labels.about}
            onChange={(e) => updateConfig({ labels: { ...config.labels, about: e.target.value } })}
          />
        </label>

        <h2>من نحن / التذييل</h2>
        <label>
          نص من نحن
          <textarea
            rows={4}
            value={config.footer.about}
            onChange={(e) => updateConfig({ footer: { ...config.footer, about: e.target.value } })}
          />
        </label>
        <label>
          سطر التذييل
          <input
            value={config.footer.tagline}
            onChange={(e) => updateConfig({ footer: { ...config.footer, tagline: e.target.value } })}
          />
        </label>

        <h2>التواصل</h2>
        <label>
          الهاتف
          <input
            value={config.contact.phone}
            onChange={(e) => updateConfig({ contact: { ...config.contact, phone: e.target.value } })}
          />
        </label>
        <label>
          البريد
          <input
            value={config.contact.email}
            onChange={(e) => updateConfig({ contact: { ...config.contact, email: e.target.value } })}
          />
        </label>
        <label>
          العنوان
          <input
            value={config.contact.address}
            onChange={(e) => updateConfig({ contact: { ...config.contact, address: e.target.value } })}
          />
        </label>
        <label>
          رابط واتساب
          <input
            value={config.contact.whatsapp}
            onChange={(e) => updateConfig({ contact: { ...config.contact, whatsapp: e.target.value } })}
          />
        </label>
        <label>
          نص زر الشات
          <input
            value={config.contact.chatLabel}
            onChange={(e) => updateConfig({ contact: { ...config.contact, chatLabel: e.target.value } })}
          />
        </label>
        <label>
          فيسبوك
          <input
            value={config.contact.facebook}
            onChange={(e) => updateConfig({ contact: { ...config.contact, facebook: e.target.value } })}
          />
        </label>
        <label>
          إنستغرام
          <input
            value={config.contact.instagram}
            onChange={(e) => updateConfig({ contact: { ...config.contact, instagram: e.target.value } })}
          />
        </label>
      </div>
    </div>
  )
}

export function AdminSlider() {
  const { config, setConfig } = useSiteConfig()
  const [tab, setTab] = useState<'individuals' | 'business'>('individuals')
  const isBiz = tab === 'business'
  const slides = (isBiz ? config.businessHome.slides : config.home.slides) || []

  const updateSlide = (i: number, patch: Partial<HeroSlide>) => {
    setConfig((prev) => {
      if (isBiz) {
        const next = [...(prev.businessHome.slides || [])]
        next[i] = { ...next[i], ...patch }
        return { ...prev, businessHome: { ...prev.businessHome, slides: next } }
      }
      const next = [...(prev.home.slides || [])]
      next[i] = { ...next[i], ...patch }
      return { ...prev, home: { ...prev.home, slides: next } }
    })
  }

  const remove = (i: number) => {
    if (!confirm('حذف هذه الصورة؟')) return
    setConfig((prev) => {
      if (isBiz) {
        return {
          ...prev,
          businessHome: {
            ...prev.businessHome,
            slides: (prev.businessHome.slides || []).filter((_, j) => j !== i),
          },
        }
      }
      return {
        ...prev,
        home: { ...prev.home, slides: prev.home.slides.filter((_, j) => j !== i) },
      }
    })
  }

  const add = () => {
    setConfig((prev) => {
      const list = isBiz ? prev.businessHome.slides || [] : prev.home.slides
      const slide = {
        id: `${isBiz ? 'biz-' : ''}slide-${Date.now()}`,
        imageDataUrl: null as string | null,
        link: isBiz ? '/business/plans' : '/plans',
        alt: isBiz ? `شريحة أعمال ${list.length + 1}` : `شريحة ${list.length + 1}`,
      }
      if (isBiz) {
        return {
          ...prev,
          businessHome: { ...prev.businessHome, slides: [...list, slide] },
        }
      }
      return {
        ...prev,
        home: { ...prev.home, slides: [...list, slide] },
      }
    })
  }

  const onImage = (i: number, file: File | null) => {
    if (!file) return
    const reader = new FileReader()
    reader.onload = () => updateSlide(i, { imageDataUrl: String(reader.result) })
    reader.readAsDataURL(file)
  }

  const reorder = (from: number, to: number) => {
    setConfig((prev) => {
      if (isBiz) {
        return {
          ...prev,
          businessHome: {
            ...prev.businessHome,
            slides: moveItem(prev.businessHome.slides || [], from, to),
          },
        }
      }
      return {
        ...prev,
        home: { ...prev.home, slides: moveItem(prev.home.slides, from, to) },
      }
    })
  }

  return (
    <div className="admin-page">
      <div className="admin-page__head">
        <div>
          <h1>السلايدر</h1>
          <p className="admin-lead">
            سلايدران منفصلان: <strong>أفراد</strong> للصفحة الرئيسية و<strong>أعمال</strong> لصفحة الأعمال. المقاس
            المفضّل <strong>1920×640</strong>.
          </p>
        </div>
        <button type="button" className="btn btn--primary" onClick={add}>
          + إضافة صورة
        </button>
      </div>

      <div className="admin-tabs">
        <button
          type="button"
          className={`admin-tab${tab === 'individuals' ? ' is-active' : ''}`}
          onClick={() => setTab('individuals')}
        >
          أفراد
        </button>
        <button
          type="button"
          className={`admin-tab${tab === 'business' ? ' is-active' : ''}`}
          onClick={() => setTab('business')}
        >
          أعمال
        </button>
      </div>

      <div className="admin-form">
        <h2>إعدادات التشغيل (مشتركة)</h2>
        <label className="admin-check">
          <input
            type="checkbox"
            checked={config.home.sliderAutoplay !== false}
            onChange={(e) =>
              setConfig((prev) => ({
                ...prev,
                home: { ...prev.home, sliderAutoplay: e.target.checked },
              }))
            }
          />
          تشغيل تلقائي
        </label>
        <label>
          مدة كل صورة (ثوانٍ)
          <input
            type="number"
            min={3}
            max={20}
            value={config.home.sliderIntervalSec || 6}
            onChange={(e) =>
              setConfig((prev) => ({
                ...prev,
                home: { ...prev.home, sliderIntervalSec: Math.max(3, Number(e.target.value) || 6) },
              }))
            }
          />
        </label>
        <label className="admin-check">
          <input
            type="checkbox"
            checked={config.home.showSliderArrows !== false}
            onChange={(e) =>
              setConfig((prev) => ({
                ...prev,
                home: { ...prev.home, showSliderArrows: e.target.checked },
              }))
            }
          />
          إظهار أسهم التنقل
        </label>
        <label className="admin-check">
          <input
            type="checkbox"
            checked={config.home.showSliderDots !== false}
            onChange={(e) =>
              setConfig((prev) => ({
                ...prev,
                home: { ...prev.home, showSliderDots: e.target.checked },
              }))
            }
          />
          إظهار نقاط التنقل
        </label>

        <h2>
          صور {isBiz ? 'الأعمال' : 'الأفراد'} ({slides.length})
        </h2>
        {slides.length === 0 ? (
          <p className="admin-empty">لا توجد صور. اضغط «إضافة صورة» ثم ارفع الملف.</p>
        ) : null}
        {slides.map((s, i) => (
          <div key={s.id} className="admin-plan-card">
            <div className="admin-plan-card__top">
              <strong>صورة {i + 1}</strong>
              <div className="admin-plan-card__actions">
                <ReorderButtons index={i} total={slides.length} onMove={reorder} />
                <button type="button" className="btn btn--danger" onClick={() => remove(i)}>
                  حذف
                </button>
              </div>
            </div>
            <label>
              رفع الصورة
              <input type="file" accept="image/*" onChange={(e) => onImage(i, e.target.files?.[0] || null)} />
            </label>
            {s.imageDataUrl ? (
              <div className="admin-slide-preview">
                <img src={s.imageDataUrl} alt={s.alt || ''} />
                <button
                  type="button"
                  className="btn btn--ghost"
                  onClick={() => updateSlide(i, { imageDataUrl: null })}
                >
                  إزالة الصورة
                </button>
              </div>
            ) : (
              <p className="admin-hint">لم تُرفع صورة بعد — لن تظهر هذه الشريحة في الموقع.</p>
            )}
            <label>
              وصف الصورة (alt)
              <input value={s.alt || ''} onChange={(e) => updateSlide(i, { alt: e.target.value })} />
            </label>
            <label>
              رابط عند النقر (اختياري)
              <input
                value={s.link || ''}
                placeholder={isBiz ? '/business/plans أو /business/join' : '/plans أو /offers'}
                onChange={(e) => updateSlide(i, { link: e.target.value })}
              />
            </label>
          </div>
        ))}
      </div>
    </div>
  )
}

export function AdminOffers() {
  const { config, setConfig } = useSiteConfig()

  const update = (i: number, patch: Partial<OfferConfig>) => {
    setConfig((prev) => {
      const offers = [...prev.offers]
      offers[i] = { ...offers[i], ...patch }
      return { ...prev, offers }
    })
  }

  const remove = (i: number) => {
    if (!confirm('حذف هذا العرض؟')) return
    setConfig((prev) => ({ ...prev, offers: prev.offers.filter((_, j) => j !== i) }))
  }

  const add = () => {
    setConfig((prev) => ({
      ...prev,
      offers: [
        ...prev.offers,
        {
          id: `o${Date.now()}`,
          tag: 'عرض جديد',
          title: 'عنوان العرض',
          link: '/order',
          tone: '1',
        },
      ],
    }))
  }

  return (
    <div className="admin-page">
      <div className="admin-page__head">
        <div>
          <h1>العروض</h1>
          <p className="admin-lead">احذف أي عرض أو أضف عروضاً جديدة في أي وقت — تظهر في صفحة العروض والقائمة.</p>
        </div>
        <button type="button" className="btn btn--primary" onClick={add}>
          + إضافة عرض
        </button>
      </div>
      <div className="admin-form">
        {config.offers.length === 0 ? (
          <p className="admin-empty">لا توجد عروض حالياً. اضغط «إضافة عرض» عند الجاهزية.</p>
        ) : null}
        {config.offers.map((o, i) => (
          <div key={o.id} className="admin-plan-card">
            <div className="admin-plan-card__top">
              <strong>عرض {i + 1}</strong>
              <div className="admin-plan-card__actions">
                <ReorderButtons
                  index={i}
                  total={config.offers.length}
                  onMove={(from, to) =>
                    setConfig((prev) => ({ ...prev, offers: moveItem(prev.offers, from, to) }))
                  }
                />
                <button type="button" className="btn btn--danger" onClick={() => remove(i)}>
                  حذف
                </button>
              </div>
            </div>
            <label>
              الوسم
              <input value={o.tag} onChange={(e) => update(i, { tag: e.target.value })} />
            </label>
            <label>
              العنوان
              <input value={o.title} onChange={(e) => update(i, { title: e.target.value })} />
            </label>
            <label>
              الرابط
              <input value={o.link} onChange={(e) => update(i, { link: e.target.value })} />
            </label>
            <label>
              اللون
              <select value={o.tone} onChange={(e) => update(i, { tone: e.target.value as OfferConfig['tone'] })}>
                <option value="1">أزرق</option>
                <option value="2">أخضر</option>
                <option value="3">برتقالي</option>
              </select>
            </label>
          </div>
        ))}
      </div>
    </div>
  )
}

export function AdminFaq() {
  const { config, setConfig } = useSiteConfig()
  return (
    <div className="admin-page">
      <h1>الأسئلة الشائعة</h1>
      <p className="admin-lead">رتّب الأسئلة بأزرار ↑ ↓ — الترتيب يظهر في صفحة الدعم.</p>
      <div className="admin-form">
        {config.faq.map((item, i) => (
          <div key={i} className="admin-plan-card">
            <div className="admin-plan-card__top">
              <strong>سؤال {i + 1}</strong>
              <div className="admin-plan-card__actions">
                <ReorderButtons
                  index={i}
                  total={config.faq.length}
                  onMove={(from, to) =>
                    setConfig((prev) => ({ ...prev, faq: moveItem(prev.faq, from, to) }))
                  }
                />
                <button
                  type="button"
                  className="btn btn--danger"
                  onClick={() => setConfig((prev) => ({ ...prev, faq: prev.faq.filter((_, j) => j !== i) }))}
                >
                  حذف
                </button>
              </div>
            </div>
            <input
              value={item.q}
              placeholder="السؤال"
              onChange={(e) => {
                setConfig((prev) => {
                  const faq = [...prev.faq]
                  faq[i] = { ...faq[i], q: e.target.value }
                  return { ...prev, faq }
                })
              }}
            />
            <textarea
              rows={2}
              value={item.a}
              placeholder="الإجابة"
              onChange={(e) => {
                setConfig((prev) => {
                  const faq = [...prev.faq]
                  faq[i] = { ...faq[i], a: e.target.value }
                  return { ...prev, faq }
                })
              }}
            />
          </div>
        ))}
        <button
          type="button"
          className="btn btn--primary"
          onClick={() =>
            setConfig((prev) => ({
              ...prev,
              faq: [...prev.faq, { q: 'سؤال جديد؟', a: 'الإجابة هنا.' }],
            }))
          }
        >
          + إضافة سؤال
        </button>
      </div>
    </div>
  )
}

function PlanEditor({
  listKey,
  title,
}: {
  listKey: 'plans' | 'businessPlans'
  title: string
}) {
  const { config, setConfig } = useSiteConfig()
  const plans = config[listKey]

  const edit = (index: number, field: string, value: string | number | boolean) => {
    setConfig((prev) => {
      const copy = structuredClone(prev[listKey])
      const plan = copy[index] as Record<string, unknown>
      if (field === 'features') {
        plan.features = String(value)
          .split('\n')
          .map((s) => s.trim())
          .filter(Boolean)
      } else if (field === 'price' || field === 'speed') {
        plan[field] = Number(value)
      } else if (field === 'featured') {
        plan.featured = Boolean(value)
      } else {
        plan[field] = value
      }
      return { ...prev, [listKey]: copy }
    })
  }

  const remove = (index: number) => {
    const name = plans[index]?.name || 'هذه الباقة'
    if (!confirm(`حذف باقة «${name}»؟`)) return
    setConfig((prev) => ({
      ...prev,
      [listKey]: prev[listKey].filter((_, j) => j !== index),
    }))
  }

  const add = () => {
    setConfig((prev) => ({
      ...prev,
      [listKey]: [
        ...prev[listKey],
        {
          id: `${listKey}-${Date.now()}`,
          name: 'باقة جديدة',
          meta: 'وصف قصير',
          price: 99,
          speed: 50,
          accent: 'blue' as const,
          features: ['ميزة 1', 'ميزة 2'],
        },
      ],
    }))
  }

  return (
    <section className="admin-section">
      <div className="admin-page__head">
        <h2>
          {title} <span className="admin-count">({plans.length})</span>
        </h2>
        <button type="button" className="btn btn--primary" onClick={add}>
          + إضافة باقة
        </button>
      </div>
      {plans.length === 0 ? (
        <p className="admin-empty">لا توجد باقات. يمكنك الإضافة لاحقاً متى شئت.</p>
      ) : null}
      {plans.map((p, i) => (
        <div key={p.id} className="admin-plan-card">
          <div className="admin-plan-card__top">
            <strong>باقة {i + 1}</strong>
            <div className="admin-plan-card__actions">
              <ReorderButtons
                index={i}
                total={plans.length}
                onMove={(from, to) =>
                  setConfig((prev) => ({
                    ...prev,
                    [listKey]: moveItem(prev[listKey], from, to),
                  }))
                }
              />
              <button type="button" className="btn btn--danger" onClick={() => remove(i)}>
                حذف
              </button>
            </div>
          </div>
          <label>
            الاسم
            <input value={p.name} onChange={(e) => edit(i, 'name', e.target.value)} />
          </label>
          <label>
            الوصف القصير
            <input value={p.meta} onChange={(e) => edit(i, 'meta', e.target.value)} />
          </label>
          <div className="admin-row">
            <label>
              السعر (شيكل)
              <input type="number" value={p.price} onChange={(e) => edit(i, 'price', e.target.value)} />
            </label>
            <label>
              السرعة (ميجا)
              <input type="number" value={p.speed} onChange={(e) => edit(i, 'speed', e.target.value)} />
            </label>
          </div>
          <label>
            اللون
            <select value={p.accent} onChange={(e) => edit(i, 'accent', e.target.value)}>
              <option value="blue">أزرق</option>
              <option value="teal">تركواز</option>
              <option value="orange">برتقالي</option>
              <option value="rose">وردي</option>
            </select>
          </label>
          <label className="admin-check">
            <input type="checkbox" checked={!!p.featured} onChange={(e) => edit(i, 'featured', e.target.checked)} />
            باقة مميزة
          </label>
          <label>
            المزايا (سطر لكل ميزة)
            <textarea
              rows={3}
              value={p.features.join('\n')}
              onChange={(e) => edit(i, 'features', e.target.value)}
            />
          </label>
        </div>
      ))}
    </section>
  )
}

export function AdminPlans() {
  return (
    <div className="admin-page">
      <h1>الباقات</h1>
      <p className="admin-lead">احذف أو أضف أو رتّب الباقات بأزرار ↑ ↓. العروض من صفحة «العروض».</p>
      <PlanEditor listKey="plans" title="أفراد" />
      <PlanEditor listKey="businessPlans" title="أعمال" />
    </div>
  )
}

export function AdminProgramming() {
  const { config, setConfig } = useSiteConfig()
  return (
    <div className="admin-page">
      <h1>البرمجة</h1>
      <p className="admin-lead">رتّب خدمات البرمجة بأزرار ↑ ↓.</p>
      <div className="admin-form">
        <label>
          عنوان الصفحة
          <input
            value={config.programming.pageTitle}
            onChange={(e) =>
              setConfig((prev) => ({
                ...prev,
                programming: { ...prev.programming, pageTitle: e.target.value },
              }))
            }
          />
        </label>
        <label>
          وصف الصفحة
          <textarea
            rows={2}
            value={config.programming.pageSubtitle}
            onChange={(e) =>
              setConfig((prev) => ({
                ...prev,
                programming: { ...prev.programming, pageSubtitle: e.target.value },
              }))
            }
          />
        </label>
        <label>
          المقدمة في القسم
          <textarea
            rows={3}
            value={config.programming.intro}
            onChange={(e) =>
              setConfig((prev) => ({
                ...prev,
                programming: { ...prev.programming, intro: e.target.value },
              }))
            }
          />
        </label>
        {config.programming.items.map((item, i) => (
          <div key={item.id} className="admin-plan-card">
            <div className="admin-plan-card__top">
              <strong>خدمة {i + 1}</strong>
              <div className="admin-plan-card__actions">
                <ReorderButtons
                  index={i}
                  total={config.programming.items.length}
                  onMove={(from, to) =>
                    setConfig((prev) => ({
                      ...prev,
                      programming: {
                        ...prev.programming,
                        items: moveItem(prev.programming.items, from, to),
                      },
                    }))
                  }
                />
                <button
                  type="button"
                  className="btn btn--danger"
                  onClick={() =>
                    setConfig((prev) => ({
                      ...prev,
                      programming: {
                        ...prev.programming,
                        items: prev.programming.items.filter((_, j) => j !== i),
                      },
                    }))
                  }
                >
                  حذف
                </button>
              </div>
            </div>
            <input
              value={item.title}
              onChange={(e) => {
                setConfig((prev) => {
                  const items = [...prev.programming.items]
                  items[i] = { ...items[i], title: e.target.value }
                  return { ...prev, programming: { ...prev.programming, items } }
                })
              }}
            />
            <textarea
              rows={2}
              value={item.text}
              onChange={(e) => {
                setConfig((prev) => {
                  const items = [...prev.programming.items]
                  items[i] = { ...items[i], text: e.target.value }
                  return { ...prev, programming: { ...prev.programming, items } }
                })
              }}
            />
          </div>
        ))}
        <button
          type="button"
          className="btn btn--primary"
          onClick={() =>
            setConfig((prev) => ({
              ...prev,
              programming: {
                ...prev.programming,
                items: [
                  ...prev.programming.items,
                  { id: `p${Date.now()}`, title: 'خدمة جديدة', text: 'وصف الخدمة' },
                ],
              },
            }))
          }
        >
          + إضافة خدمة
        </button>
      </div>
    </div>
  )
}

export function AdminDigital() {
  const { config, setConfig } = useSiteConfig()
  const d = config.digital

  const onPosHeroImage = (file: File | null) => {
    if (!file) return
    const reader = new FileReader()
    reader.onload = () =>
      setConfig({ ...config, digital: { ...d, posHeroImage: String(reader.result) } })
    reader.readAsDataURL(file)
  }

  const onSectionImage = (index: number, file: File | null) => {
    if (!file) return
    const reader = new FileReader()
    reader.onload = () => {
      setConfig((prev) => {
        const sections = [...(prev.digital.posSections || [])]
        sections[index] = { ...sections[index], imageDataUrl: String(reader.result) }
        return { ...prev, digital: { ...prev.digital, posSections: sections } }
      })
    }
    reader.readAsDataURL(file)
  }

  return (
    <div className="admin-page">
      <h1>POS والموظفين</h1>
      <div className="admin-form">
        <h2>صفحة نقاط البيع</h2>
        <label>
          عنوان POS
          <input value={d.posTitle} onChange={(e) => setConfig({ ...config, digital: { ...d, posTitle: e.target.value } })} />
        </label>
        <label>
          وصف POS
          <textarea rows={3} value={d.posText} onChange={(e) => setConfig({ ...config, digital: { ...d, posText: e.target.value } })} />
        </label>
        <label>
          رابط دخول POS
          <input
            value={d.posAppUrl || ''}
            onChange={(e) => setConfig({ ...config, digital: { ...d, posAppUrl: e.target.value } })}
            placeholder="https://pos.madd.ps"
          />
        </label>
        <label>
          صورة الهيرو
          <input type="file" accept="image/*" onChange={(e) => onPosHeroImage(e.target.files?.[0] || null)} />
        </label>
        {d.posHeroImage ? (
          <div className="admin-slide-preview">
            <img src={d.posHeroImage} alt="" />
            <button
              type="button"
              className="btn btn--ghost"
              onClick={() => setConfig({ ...config, digital: { ...d, posHeroImage: null } })}
            >
              إزالة صورة الهيرو
            </button>
          </div>
        ) : null}
        <label>
          مزايا مختصرة (سطر لكل ميزة)
          <textarea
            rows={4}
            value={d.posFeatures.join('\n')}
            onChange={(e) =>
              setConfig({
                ...config,
                digital: {
                  ...d,
                  posFeatures: e.target.value.split('\n').map((s) => s.trim()).filter(Boolean),
                },
              })
            }
          />
        </label>

        <h3>أقسام POS (نص + صورة)</h3>
        {(d.posSections || []).map((sec, i) => (
          <div key={sec.id} className="admin-plan-card">
            <strong>
              قسم {i + 1}: {sec.title}
            </strong>
            <label>
              عنوان
              <input
                value={sec.title}
                onChange={(e) => {
                  setConfig((prev) => {
                    const posSections = [...prev.digital.posSections]
                    posSections[i] = { ...posSections[i], title: e.target.value }
                    return { ...prev, digital: { ...prev.digital, posSections } }
                  })
                }}
              />
            </label>
            <label>
              وصف
              <textarea
                rows={2}
                value={sec.text}
                onChange={(e) => {
                  setConfig((prev) => {
                    const posSections = [...prev.digital.posSections]
                    posSections[i] = { ...posSections[i], text: e.target.value }
                    return { ...prev, digital: { ...prev.digital, posSections } }
                  })
                }}
              />
            </label>
            <label>
              نقاط (سطر لكل نقطة)
              <textarea
                rows={4}
                value={sec.items.join('\n')}
                onChange={(e) => {
                  setConfig((prev) => {
                    const posSections = [...prev.digital.posSections]
                    posSections[i] = {
                      ...posSections[i],
                      items: e.target.value.split('\n').map((s) => s.trim()).filter(Boolean),
                    }
                    return { ...prev, digital: { ...prev.digital, posSections } }
                  })
                }}
              />
            </label>
            <label>
              صورة القسم
              <input type="file" accept="image/*" onChange={(e) => onSectionImage(i, e.target.files?.[0] || null)} />
            </label>
            {sec.imageDataUrl ? (
              <div className="admin-slide-preview">
                <img src={sec.imageDataUrl} alt="" />
                <button
                  type="button"
                  className="btn btn--ghost"
                  onClick={() => {
                    setConfig((prev) => {
                      const posSections = [...prev.digital.posSections]
                      posSections[i] = { ...posSections[i], imageDataUrl: null }
                      return { ...prev, digital: { ...prev.digital, posSections } }
                    })
                  }}
                >
                  إزالة الصورة
                </button>
              </div>
            ) : null}
          </div>
        ))}

        <h2>الموظفين</h2>
        <label>
          عنوان الموظفين
          <input value={d.hrTitle} onChange={(e) => setConfig({ ...config, digital: { ...d, hrTitle: e.target.value } })} />
        </label>
        <label>
          وصف الموظفين
          <textarea rows={3} value={d.hrText} onChange={(e) => setConfig({ ...config, digital: { ...d, hrText: e.target.value } })} />
        </label>
        <label>
          مزايا الموظفين
          <textarea
            rows={4}
            value={d.hrFeatures.join('\n')}
            onChange={(e) =>
              setConfig({
                ...config,
                digital: {
                  ...d,
                  hrFeatures: e.target.value.split('\n').map((s) => s.trim()).filter(Boolean),
                },
              })
            }
          />
        </label>
      </div>
    </div>
  )
}

export function AdminOrder() {
  const { config, setConfig } = useSiteConfig()
  const [tab, setTab] = useState<'individuals' | 'business'>('individuals')
  const homeSections = normalizeHomeSections(config.layout?.homeSections)
  const businessSections = normalizeBusinessHomeSections(config.layout?.businessHomeSections)

  const moveHomeSection = (from: number, to: number) => {
    setConfig((prev) => ({
      ...prev,
      layout: {
        ...prev.layout,
        homeSections: moveItem(normalizeHomeSections(prev.layout?.homeSections), from, to),
      },
    }))
  }

  const moveBusinessSection = (from: number, to: number) => {
    setConfig((prev) => ({
      ...prev,
      layout: {
        ...prev.layout,
        businessHomeSections: moveItem(
          normalizeBusinessHomeSections(prev.layout?.businessHomeSections),
          from,
          to,
        ),
      },
    }))
  }

  return (
    <div className="admin-page">
      <h1>ترتيب الأقسام</h1>
      <p className="admin-lead">
        رتّب الأقسام بعد السلايدر. السلايدر ثابت في الأعلى. استخدم ↑ ↓ لتغيير الترتيب ثم احفظ من شريط الإدارة.
      </p>
      <div className="admin-tabs" role="tablist">
        <button
          type="button"
          className={`admin-tab${tab === 'individuals' ? ' is-active' : ''}`}
          onClick={() => setTab('individuals')}
        >
          أفراد
        </button>
        <button
          type="button"
          className={`admin-tab${tab === 'business' ? ' is-active' : ''}`}
          onClick={() => setTab('business')}
        >
          أعمال
        </button>
      </div>
      <div className="admin-form">
        {tab === 'individuals' ? (
          <>
            <h2>الصفحة الرئيسية (أفراد)</h2>
            {homeSections.map((id, i) => (
              <div key={id} className="admin-order-row">
                <span className="admin-order-row__num">{i + 1}</span>
                <strong className="admin-order-row__label">{HOME_SECTION_LABELS[id as HomeSectionId]}</strong>
                <ReorderButtons index={i} total={homeSections.length} onMove={moveHomeSection} />
              </div>
            ))}
          </>
        ) : (
          <>
            <h2>صفحة الأعمال /business</h2>
            {businessSections.map((id, i) => (
              <div key={id} className="admin-order-row">
                <span className="admin-order-row__num">{i + 1}</span>
                <strong className="admin-order-row__label">
                  {BUSINESS_HOME_SECTION_LABELS[id as BusinessHomeSectionId]}
                </strong>
                <ReorderButtons index={i} total={businessSections.length} onMove={moveBusinessSection} />
              </div>
            ))}
          </>
        )}
        <p className="admin-hint">
          محتوى كل قسم (عناوين، صور، روابط) من صفحات الإدارة الخاصة به — مثلاً «لماذا مدد» من قسم الأعمدة في صفحة
          الأعمال.
        </p>
      </div>
    </div>
  )
}

export function AdminVisibility() {
  const { config, updateConfig } = useSiteConfig()
  const v = config.visibility
  const toggle = (key: keyof typeof v) => updateConfig({ visibility: { ...v, [key]: !v[key] } })

  const rows = useMemo(
    () =>
      [
        ['showChat', 'زر الشات / واتساب'],
        ['showOffers', 'العروض في القائمة'],
        ['showProgramming', 'البرمجة في القائمة والتذييل'],
        ['showCoverage', 'فحص التغطية في القائمة'],
        ['showBusinessPos', 'POS للأعمال'],
        ['showBusinessHr', 'إدارة الموظفين للأعمال'],
      ] as const,
    [],
  )

  return (
    <div className="admin-page">
      <h1>الظهور والقوائم</h1>
      <p className="admin-lead">أخفِ أو أظهر أقساماً من الموقع دون حذف المحتوى.</p>
      <div className="admin-form">
        {rows.map(([key, label]) => (
          <label key={key} className="admin-check">
            <input type="checkbox" checked={v[key]} onChange={() => toggle(key)} />
            {label}
          </label>
        ))}
      </div>
    </div>
  )
}

export function AdminSeo() {
  const { config, updateConfig } = useSiteConfig()
  return (
    <div className="admin-page">
      <h1>SEO</h1>
      <div className="admin-form">
        <label>
          عنوان الصفحة (Title)
          <input
            value={config.seo.title}
            onChange={(e) => updateConfig({ seo: { ...config.seo, title: e.target.value } })}
          />
        </label>
        <label>
          وصف الميتا (Description)
          <textarea
            rows={3}
            value={config.seo.description}
            onChange={(e) => updateConfig({ seo: { ...config.seo, description: e.target.value } })}
          />
        </label>
      </div>
    </div>
  )
}

export function AdminSettings() {
  const { config, updateConfig, resetConfig, exportJson, importJson } = useSiteConfig()
  const [raw, setRaw] = useState('')

  return (
    <div className="admin-page">
      <h1>الإعدادات</h1>
      <div className="admin-form">
        <label>
          كلمة المرور
          <input
            type="text"
            value={config.admin.password}
            onChange={(e) => updateConfig({ admin: { password: e.target.value } })}
          />
        </label>
        <button
          type="button"
          className="btn btn--primary"
          onClick={() => {
            const blob = new Blob([exportJson()], { type: 'application/json' })
            const url = URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = 'madd-site-config.json'
            a.click()
          }}
        >
          تصدير المحتوى JSON
        </button>
        <label>
          استيراد JSON
          <textarea rows={6} value={raw} onChange={(e) => setRaw(e.target.value)} placeholder="الصق المحتوى هنا" />
        </label>
        <button
          type="button"
          className="btn btn--ghost"
          onClick={() => {
            if (importJson(raw)) alert('تم الاستيراد')
            else alert('ملف غير صالح')
          }}
        >
          استيراد
        </button>
        <button
          type="button"
          className="btn btn--ghost"
          onClick={() => {
            if (confirm('إعادة ضبط كل المحتوى للافتراضي؟')) resetConfig()
          }}
        >
          إعادة ضبط افتراضي
        </button>
      </div>
    </div>
  )
}

const PAGE_KEYS = [
  ['plans', 'الباقات'],
  ['offers', 'العروض'],
  ['coverage', 'التغطية'],
  ['support', 'الدعم'],
  ['about', 'من نحن'],
  ['order', 'الطلب'],
  ['programming', 'البرمجة'],
  ['business', 'أعمال'],
  ['businessPlans', 'باقات أعمال'],
  ['businessPos', 'POS'],
  ['businessHr', 'HR'],
  ['businessProgramming', 'برمجة أعمال'],
  ['businessJoin', 'انضمام أعمال'],
  ['businessWeb', 'حضور رقمي (Domain/Hosting)'],
] as const

export function AdminPagesEditor() {
  const { config, updateConfig } = useSiteConfig()
  const [key, setKey] = useState<(typeof PAGE_KEYS)[number][0]>('plans')
  const page = config.pages[key]

  return (
    <div className="admin-page">
      <h1>صفحات الموقع</h1>
      <p className="admin-lead">عناوين وأوصاف وSEO لكل صفحة عامة.</p>
      <div className="admin-form">
        <label>
          الصفحة
          <select value={key} onChange={(e) => setKey(e.target.value as typeof key)}>
            {PAGE_KEYS.map(([k, label]) => (
              <option key={k} value={k}>
                {label}
              </option>
            ))}
          </select>
        </label>
        <label>
          كيكَر
          <input
            value={page.kicker}
            onChange={(e) => updateConfig({ pages: { ...config.pages, [key]: { ...page, kicker: e.target.value } } })}
          />
        </label>
        <label>
          العنوان
          <input
            value={page.title}
            onChange={(e) => updateConfig({ pages: { ...config.pages, [key]: { ...page, title: e.target.value } } })}
          />
        </label>
        <label>
          الوصف
          <textarea
            rows={3}
            value={page.lead}
            onChange={(e) => updateConfig({ pages: { ...config.pages, [key]: { ...page, lead: e.target.value } } })}
          />
        </label>
        <label>
          نص الزر
          <input
            value={page.cta || ''}
            onChange={(e) => updateConfig({ pages: { ...config.pages, [key]: { ...page, cta: e.target.value } } })}
          />
        </label>
        <label>
          رابط الزر
          <input
            value={page.ctaLink || ''}
            onChange={(e) => updateConfig({ pages: { ...config.pages, [key]: { ...page, ctaLink: e.target.value } } })}
          />
        </label>
        <label>
          SEO عنوان
          <input
            value={page.seoTitle || ''}
            onChange={(e) => updateConfig({ pages: { ...config.pages, [key]: { ...page, seoTitle: e.target.value } } })}
          />
        </label>
        <label>
          SEO وصف
          <textarea
            rows={2}
            value={page.seoDescription || ''}
            onChange={(e) =>
              updateConfig({ pages: { ...config.pages, [key]: { ...page, seoDescription: e.target.value } } })
            }
          />
        </label>
      </div>

      <h2 style={{ marginTop: '2rem' }}>بطل الرئيسية</h2>
      <div className="admin-form">
        <label>
          عنوان البطل
          <textarea
            rows={2}
            value={config.home.heroTitle}
            onChange={(e) => updateConfig({ home: { ...config.home, heroTitle: e.target.value } })}
          />
        </label>
        <label>
          وصف البطل
          <textarea
            rows={2}
            value={config.home.heroSubtitle}
            onChange={(e) => updateConfig({ home: { ...config.home, heroSubtitle: e.target.value } })}
          />
        </label>
        <label>
          زر أساسي
          <input
            value={config.home.heroCta}
            onChange={(e) => updateConfig({ home: { ...config.home, heroCta: e.target.value } })}
          />
        </label>
        <label>
          رابط الزر الأساسي
          <input
            value={config.home.heroCtaLink}
            onChange={(e) => updateConfig({ home: { ...config.home, heroCtaLink: e.target.value } })}
          />
        </label>
        <label>
          زر ثانوي
          <input
            value={config.home.heroCtaSecondary}
            onChange={(e) => updateConfig({ home: { ...config.home, heroCtaSecondary: e.target.value } })}
          />
        </label>
        <label>
          رابط الزر الثانوي
          <input
            value={config.home.heroCtaSecondaryLink}
            onChange={(e) => updateConfig({ home: { ...config.home, heroCtaSecondaryLink: e.target.value } })}
          />
        </label>
      </div>

      <h2 style={{ marginTop: '2rem' }}>نصوص الباقات</h2>
      <div className="admin-form">
        {(['currency', 'period', 'featuredBadge', 'orderCta', 'compareTitle', 'legalNote'] as const).map((field) => (
          <label key={field}>
            {field}
            <input
              value={config.plansUi[field]}
              onChange={(e) => updateConfig({ plansUi: { ...config.plansUi, [field]: e.target.value } })}
            />
          </label>
        ))}
      </div>
    </div>
  )
}

export function AdminCoverage() {
  const { config, setConfig } = useSiteConfig()
  const areas = config.coverage.areas

  const updateArea = (id: string, patch: Partial<(typeof areas)[0]>) => {
    setConfig({
      ...config,
      coverage: {
        ...config.coverage,
        areas: areas.map((a) => (a.id === id ? { ...a, ...patch } : a)),
      },
    })
  }

  return (
    <div className="admin-page">
      <h1>التغطية</h1>
      <div className="admin-form">
        <label>
          عنوان النموذج
          <input
            value={config.coverage.formTitle}
            onChange={(e) => setConfig({ ...config, coverage: { ...config.coverage, formTitle: e.target.value } })}
          />
        </label>
        <label>
          وصف النموذج
          <textarea
            rows={2}
            value={config.coverage.formLead}
            onChange={(e) => setConfig({ ...config, coverage: { ...config.coverage, formLead: e.target.value } })}
          />
        </label>
        <label>
          رسالة متوفر
          <textarea
            rows={2}
            value={config.coverage.resultAvailable}
            onChange={(e) =>
              setConfig({ ...config, coverage: { ...config.coverage, resultAvailable: e.target.value } })
            }
          />
        </label>
        <label>
          رسالة قيد التأكيد
          <textarea
            rows={2}
            value={config.coverage.resultCheck}
            onChange={(e) => setConfig({ ...config, coverage: { ...config.coverage, resultCheck: e.target.value } })}
          />
        </label>
        <label>
          رسالة غير متوفر
          <textarea
            rows={2}
            value={config.coverage.resultUnavailable}
            onChange={(e) =>
              setConfig({ ...config, coverage: { ...config.coverage, resultUnavailable: e.target.value } })
            }
          />
        </label>
      </div>
      <h2>المناطق</h2>
      {areas.map((a) => (
        <div key={a.id} className="admin-form admin-card-block">
          <label>
            المدينة
            <input value={a.city} onChange={(e) => updateArea(a.id, { city: e.target.value })} />
          </label>
          <label>
            المنطقة
            <input value={a.name} onChange={(e) => updateArea(a.id, { name: e.target.value })} />
          </label>
          <label>
            الحالة
            <select
              value={a.status}
              onChange={(e) => updateArea(a.id, { status: e.target.value as typeof a.status })}
            >
              <option value="available">متوفر</option>
              <option value="check">قيد التأكيد</option>
              <option value="unavailable">غير متوفر</option>
            </select>
          </label>
          <button
            type="button"
            className="btn btn--ghost"
            onClick={() =>
              setConfig({
                ...config,
                coverage: { ...config.coverage, areas: areas.filter((x) => x.id !== a.id) },
              })
            }
          >
            حذف
          </button>
        </div>
      ))}
      <button
        type="button"
        className="btn btn--primary"
        onClick={() =>
          setConfig({
            ...config,
            coverage: {
              ...config.coverage,
              areas: [
                ...areas,
                { id: `a-${Date.now()}`, name: 'منطقة جديدة', city: 'نابلس', status: 'check' },
              ],
            },
          })
        }
      >
        إضافة منطقة
      </button>
    </div>
  )
}

export function AdminTrust() {
  const { config, setConfig } = useSiteConfig()
  return (
    <div className="admin-page">
      <h1>الثقة والآراء</h1>
      <div className="admin-form">
        <label>
          العنوان
          <input
            value={config.trust.title}
            onChange={(e) => setConfig({ ...config, trust: { ...config.trust, title: e.target.value } })}
          />
        </label>
        <label>
          الوصف
          <input
            value={config.trust.lead}
            onChange={(e) => setConfig({ ...config, trust: { ...config.trust, lead: e.target.value } })}
          />
        </label>
      </div>
      <h2>إحصاءات</h2>
      {config.trust.stats.map((s, i) => (
        <div key={s.id} className="admin-form admin-card-block">
          <label>
            القيمة
            <input
              value={s.value}
              onChange={(e) => {
                const stats = [...config.trust.stats]
                stats[i] = { ...s, value: e.target.value }
                setConfig({ ...config, trust: { ...config.trust, stats } })
              }}
            />
          </label>
          <label>
            التسمية
            <input
              value={s.label}
              onChange={(e) => {
                const stats = [...config.trust.stats]
                stats[i] = { ...s, label: e.target.value }
                setConfig({ ...config, trust: { ...config.trust, stats } })
              }}
            />
          </label>
        </div>
      ))}
      <h2>شهادات</h2>
      {config.trust.testimonials.map((t, i) => (
        <div key={t.id} className="admin-form admin-card-block">
          <label>
            الاسم
            <input
              value={t.name}
              onChange={(e) => {
                const testimonials = [...config.trust.testimonials]
                testimonials[i] = { ...t, name: e.target.value }
                setConfig({ ...config, trust: { ...config.trust, testimonials } })
              }}
            />
          </label>
          <label>
            الصفة
            <input
              value={t.role}
              onChange={(e) => {
                const testimonials = [...config.trust.testimonials]
                testimonials[i] = { ...t, role: e.target.value }
                setConfig({ ...config, trust: { ...config.trust, testimonials } })
              }}
            />
          </label>
          <label>
            النص
            <textarea
              rows={2}
              value={t.text}
              onChange={(e) => {
                const testimonials = [...config.trust.testimonials]
                testimonials[i] = { ...t, text: e.target.value }
                setConfig({ ...config, trust: { ...config.trust, testimonials } })
              }}
            />
          </label>
        </div>
      ))}
    </div>
  )
}

export function AdminLeads() {
  const { config } = useSiteConfig()
  const [leads, setLeads] = useState<Array<Record<string, unknown>>>([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  const load = async () => {
    setLoading(true)
    setError('')
    try {
      const password = sessionStorage.getItem(ADMIN_PASSWORD_SESSION) || config.admin.password
      const res = await fetchSiteLeads(password)
      setLeads(res.leads || [])
    } catch (err) {
      setError(err instanceof Error ? err.message : 'تعذّر التحميل')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [])

  return (
    <div className="admin-page">
      <h1>طلبات الموقع</h1>
      <p className="admin-lead">طلبات واي فايبر والبرمجة وانضمام الأعمال المحفوظة على السيرفر.</p>
      <button type="button" className="btn btn--ghost" onClick={() => void load()}>
        تحديث
      </button>
      {loading ? <p>جاري التحميل…</p> : null}
      {error ? <p className="admin-error">{error}</p> : null}
      <div className="admin-leads">
        {leads.length === 0 && !loading ? <p>لا توجد طلبات بعد.</p> : null}
        {leads.map((lead) => (
          <article key={String(lead.id)} className="admin-lead-card">
            <header>
              <strong>#{String(lead.id)}</strong>
              <span>{String(lead.type)}</span>
              <span>{String(lead.status)}</span>
            </header>
            <p>
              {String(lead.name || '—')} · {String(lead.phone || '—')}
            </p>
            <p>{String(lead.plan_name || lead.message || '')}</p>
            <p className="admin-lead-meta">{String(lead.created_at || '')}</p>
            <select
              value={String(lead.status)}
              onChange={(e) => {
                const password = sessionStorage.getItem(ADMIN_PASSWORD_SESSION) || config.admin.password
                void updateSiteLeadStatus(Number(lead.id), e.target.value, password).then(load)
              }}
            >
              <option value="new">جديد</option>
              <option value="contacted">تم التواصل</option>
              <option value="won">مكتمل</option>
              <option value="lost">ملغى</option>
              <option value="archived">مؤرشف</option>
            </select>
          </article>
        ))}
      </div>
    </div>
  )
}
