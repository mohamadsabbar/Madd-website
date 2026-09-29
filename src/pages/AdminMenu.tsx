import { useState } from 'react'
import type { MenuEntry, MenuLinkItem } from '../config/menus'
import { DEFAULT_MENUS } from '../config/menus'
import { moveItem } from '../config/order'
import { useSiteConfig } from '../context/SiteConfigContext'

type MenuKey = 'individuals' | 'business'

function uid(prefix: string) {
  return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`
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
      >
        ↑
      </button>
      <button
        type="button"
        className="admin-reorder__btn"
        disabled={index >= total - 1}
        onClick={() => onMove(index, index + 1)}
        title="تحريك لأسفل"
      >
        ↓
      </button>
    </div>
  )
}

export function AdminMenu() {
  const { config, setConfig } = useSiteConfig()
  const [tab, setTab] = useState<MenuKey>('individuals')
  const entries = config.menus?.[tab] || DEFAULT_MENUS[tab]

  const setEntries = (next: MenuEntry[]) => {
    setConfig((prev) => ({
      ...prev,
      menus: {
        individuals: prev.menus?.individuals || DEFAULT_MENUS.individuals,
        business: prev.menus?.business || DEFAULT_MENUS.business,
        [tab]: next,
      },
    }))
  }

  const updateEntry = (i: number, patch: Partial<MenuEntry>) => {
    const next = [...entries]
    next[i] = { ...next[i], ...patch } as MenuEntry
    setEntries(next)
  }

  const removeEntry = (i: number) => {
    if (!confirm('حذف عنصر القائمة؟')) return
    setEntries(entries.filter((_, j) => j !== i))
  }

  const addLink = () => {
    setEntries([
      ...entries,
      { id: uid('link'), type: 'link', enabled: true, label: 'رابط جديد', to: '/' },
    ])
  }

  const addMega = () => {
    setEntries([
      ...entries,
      {
        id: uid('mega'),
        type: 'mega',
        enabled: true,
        label: 'قائمة منسدلة',
        columns: [
          {
            id: uid('col'),
            title: 'عمود',
            items: [{ id: uid('item'), label: 'رابط', to: '/', desc: '' }],
          },
        ],
        featured: {
          enabled: true,
          title: 'عنوان مميز',
          text: 'وصف قصير',
          to: '/',
          cta: 'اعرف أكثر',
        },
      },
    ])
  }

  const resetTab = () => {
    if (!confirm('إعادة القائمة الافتراضية لهذه الشريحة؟')) return
    setEntries(structuredClone(DEFAULT_MENUS[tab]))
  }

  return (
    <div className="admin-page">
      <div className="admin-page__head">
        <div>
          <h1>القائمة الرئيسية</h1>
          <p className="admin-lead">تحكم بعناصر الـ Mega Menu بعرض الصفحة بالكامل — أفراد وأعمال.</p>
        </div>
        <div className="admin-plan-card__actions">
          <button type="button" className="btn btn--ghost" onClick={addLink}>
            + رابط
          </button>
          <button type="button" className="btn btn--primary" onClick={addMega}>
            + قائمة منسدلة
          </button>
        </div>
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
        <button type="button" className="btn btn--ghost" onClick={resetTab}>
          افتراضي
        </button>
      </div>

      <div className="admin-form">
        {entries.map((entry, i) => (
          <div key={entry.id} className="admin-plan-card">
            <div className="admin-plan-card__top">
              <strong>
                {entry.type === 'mega' ? 'قائمة منسدلة' : 'رابط'} — {i + 1}
              </strong>
              <div className="admin-plan-card__actions">
                <ReorderButtons
                  index={i}
                  total={entries.length}
                  onMove={(from, to) => setEntries(moveItem(entries, from, to))}
                />
                <button type="button" className="btn btn--danger" onClick={() => removeEntry(i)}>
                  حذف
                </button>
              </div>
            </div>

            <label className="admin-check">
              <input
                type="checkbox"
                checked={entry.enabled !== false}
                onChange={(e) => updateEntry(i, { enabled: e.target.checked })}
              />
              ظاهر في القائمة
            </label>

            <label>
              النص في الشريط
              <input value={entry.label} onChange={(e) => updateEntry(i, { label: e.target.value })} />
            </label>

            {entry.type === 'link' ? (
              <label>
                الرابط
                <input value={entry.to} onChange={(e) => updateEntry(i, { to: e.target.value })} />
              </label>
            ) : (
              <MegaEditor
                entry={entry}
                onChange={(next) => {
                  const list = [...entries]
                  list[i] = next
                  setEntries(list)
                }}
              />
            )}
          </div>
        ))}
      </div>
    </div>
  )
}

function MegaEditor({
  entry,
  onChange,
}: {
  entry: Extract<MenuEntry, { type: 'mega' }>
  onChange: (next: Extract<MenuEntry, { type: 'mega' }>) => void
}) {
  const updateColumn = (ci: number, title: string) => {
    const columns = [...entry.columns]
    columns[ci] = { ...columns[ci], title }
    onChange({ ...entry, columns })
  }

  const updateItem = (ci: number, ii: number, patch: Partial<MenuLinkItem>) => {
    const columns = [...entry.columns]
    const items = [...columns[ci].items]
    items[ii] = { ...items[ii], ...patch }
    columns[ci] = { ...columns[ci], items }
    onChange({ ...entry, columns })
  }

  const addColumn = () => {
    onChange({
      ...entry,
      columns: [
        ...entry.columns,
        {
          id: uid('col'),
          title: 'عمود جديد',
          items: [{ id: uid('item'), label: 'رابط', to: '/', desc: '' }],
        },
      ],
    })
  }

  const addItem = (ci: number) => {
    const columns = [...entry.columns]
    columns[ci] = {
      ...columns[ci],
      items: [...columns[ci].items, { id: uid('item'), label: 'رابط جديد', to: '/', desc: '' }],
    }
    onChange({ ...entry, columns })
  }

  return (
    <>
      <h3 className="admin-subhead">الأعمدة</h3>
      {entry.columns.map((col, ci) => (
        <div key={col.id} className="admin-nested">
          <div className="admin-plan-card__top">
            <strong>عمود {ci + 1}</strong>
            <div className="admin-plan-card__actions">
              <ReorderButtons
                index={ci}
                total={entry.columns.length}
                onMove={(from, to) => onChange({ ...entry, columns: moveItem(entry.columns, from, to) })}
              />
              <button
                type="button"
                className="btn btn--danger"
                onClick={() =>
                  onChange({ ...entry, columns: entry.columns.filter((_, j) => j !== ci) })
                }
              >
                حذف العمود
              </button>
            </div>
          </div>
          <label>
            عنوان العمود
            <input value={col.title} onChange={(e) => updateColumn(ci, e.target.value)} />
          </label>
          {col.items.map((item, ii) => (
            <div key={item.id} className="admin-nested__item">
              <div className="admin-plan-card__top">
                <span>رابط {ii + 1}</span>
                <div className="admin-plan-card__actions">
                  <ReorderButtons
                    index={ii}
                    total={col.items.length}
                    onMove={(from, to) => {
                      const columns = [...entry.columns]
                      columns[ci] = { ...columns[ci], items: moveItem(col.items, from, to) }
                      onChange({ ...entry, columns })
                    }}
                  />
                  <button
                    type="button"
                    className="btn btn--danger"
                    onClick={() => {
                      const columns = [...entry.columns]
                      columns[ci] = {
                        ...columns[ci],
                        items: col.items.filter((_, j) => j !== ii),
                      }
                      onChange({ ...entry, columns })
                    }}
                  >
                    حذف
                  </button>
                </div>
              </div>
              <label>
                الاسم
                <input value={item.label} onChange={(e) => updateItem(ci, ii, { label: e.target.value })} />
              </label>
              <label>
                الرابط
                <input value={item.to} onChange={(e) => updateItem(ci, ii, { to: e.target.value })} />
              </label>
              <label>
                وصف قصير
                <input value={item.desc || ''} onChange={(e) => updateItem(ci, ii, { desc: e.target.value })} />
              </label>
            </div>
          ))}
          <button type="button" className="btn btn--ghost" onClick={() => addItem(ci)}>
            + رابط في العمود
          </button>
        </div>
      ))}
      <button type="button" className="btn btn--ghost" onClick={addColumn}>
        + عمود
      </button>

      <h3 className="admin-subhead">البطاقة المميزة</h3>
      <label className="admin-check">
        <input
          type="checkbox"
          checked={entry.featured.enabled !== false}
          onChange={(e) =>
            onChange({ ...entry, featured: { ...entry.featured, enabled: e.target.checked } })
          }
        />
        إظهار البطاقة المميزة
      </label>
      <label>
        العنوان
        <input
          value={entry.featured.title}
          onChange={(e) => onChange({ ...entry, featured: { ...entry.featured, title: e.target.value } })}
        />
      </label>
      <label>
        النص
        <textarea
          rows={2}
          value={entry.featured.text}
          onChange={(e) => onChange({ ...entry, featured: { ...entry.featured, text: e.target.value } })}
        />
      </label>
      <div className="admin-row">
        <label>
          نص الزر
          <input
            value={entry.featured.cta}
            onChange={(e) => onChange({ ...entry, featured: { ...entry.featured, cta: e.target.value } })}
          />
        </label>
        <label>
          رابط الزر
          <input
            value={entry.featured.to}
            onChange={(e) => onChange({ ...entry, featured: { ...entry.featured, to: e.target.value } })}
          />
        </label>
      </div>
    </>
  )
}
