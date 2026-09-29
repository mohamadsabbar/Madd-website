/** Move item in array by index. Returns same array if out of bounds. */
export function moveItem<T>(list: T[], from: number, to: number): T[] {
  if (to < 0 || to >= list.length || from === to) return list
  const next = [...list]
  const [item] = next.splice(from, 1)
  next.splice(to, 0, item)
  return next
}

export type HomeSectionId = 'benefits' | 'plans' | 'programming'

export const HOME_SECTION_LABELS: Record<HomeSectionId, string> = {
  benefits: 'الخدمات',
  plans: 'الباقات',
  programming: 'البرمجة',
}

export const DEFAULT_HOME_SECTIONS: HomeSectionId[] = ['benefits', 'plans', 'programming']

export function normalizeHomeSections(raw: unknown): HomeSectionId[] {
  const allowed: HomeSectionId[] = ['benefits', 'plans', 'programming']
  if (!Array.isArray(raw)) return [...DEFAULT_HOME_SECTIONS]
  const filtered = raw
    .filter((id): id is HomeSectionId => allowed.includes(id as HomeSectionId))
    .filter((id, i, arr) => arr.indexOf(id) === i)
  for (const id of allowed) {
    if (!filtered.includes(id)) filtered.push(id)
  }
  return filtered
}
