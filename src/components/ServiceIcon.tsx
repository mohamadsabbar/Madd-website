type ServiceIconProps = {
  name?: string
  className?: string
}

const stroke = {
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 1.8,
  strokeLinecap: 'round' as const,
  strokeLinejoin: 'round' as const,
}

export function ServiceIcon({ name = 'globe', className }: ServiceIconProps) {
  switch (name) {
    case 'fiber':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <path {...stroke} d="M12 3v18M7 7l5-4 5 4M7 17l5 4 5-4" />
          <circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none" />
        </svg>
      )
    case 'business':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <circle {...stroke} cx="12" cy="12" r="8.5" />
          <path {...stroke} d="M3.5 12h17M12 3.5c2.4 2.6 2.4 14.4 0 17M12 3.5c-2.4 2.6-2.4 14.4 0 17" />
        </svg>
      )
    case 'sim':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <path {...stroke} d="M7 4h7l4 4v12H7z" />
          <rect x="9.2" y="10" width="2.2" height="2.2" rx="0.3" fill="currentColor" stroke="none" />
          <rect x="12.6" y="10" width="2.2" height="2.2" rx="0.3" fill="currentColor" stroke="none" />
          <rect x="9.2" y="13.4" width="2.2" height="2.2" rx="0.3" fill="currentColor" stroke="none" />
          <rect x="12.6" y="13.4" width="2.2" height="2.2" rx="0.3" fill="currentColor" stroke="none" />
        </svg>
      )
    case 'plane':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <path
            {...stroke}
            d="M10.5 12.5 4 10.2l1-1.7 5.2 1.2L14 4l1.8.7-1.5 6.5 4.8 1.8-1 1.6-5.2-1.1L10 20.8l-1.8-.7z"
          />
        </svg>
      )
    case 'support':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <path {...stroke} d="M6 11V9a6 6 0 0 1 12 0v2" />
          <path {...stroke} d="M5 12h2.2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H5.8A1.8 1.8 0 0 1 4 15.2V13.8A1.8 1.8 0 0 1 5.8 12Z" />
          <path {...stroke} d="M19 12h-2.2a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h1.2A1.8 1.8 0 0 0 20 15.2V13.8A1.8 1.8 0 0 0 18.2 12Z" />
          <path {...stroke} d="M16.5 18.5A4.5 4.5 0 0 1 12 21" />
        </svg>
      )
    case 'offers':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <path {...stroke} d="M12 3 14.2 8.2 20 9l-4.2 3.8L17 19l-5-2.8L7 19l1.2-6.2L4 9l5.8-.8z" />
        </svg>
      )
    case 'code':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <path {...stroke} d="m8 8-4 4 4 4M16 8l4 4-4 4M13 6l-2 12" />
        </svg>
      )
    case 'coverage':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <path {...stroke} d="M12 21s-6.5-5.2-6.5-10A6.5 6.5 0 0 1 12 4.5 6.5 6.5 0 0 1 18.5 11c0 4.8-6.5 10-6.5 10z" />
          <circle {...stroke} cx="12" cy="11" r="2.2" />
        </svg>
      )
    case 'portal':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <rect {...stroke} x="4" y="5" width="16" height="14" rx="2" />
          <path {...stroke} d="M8 15h3M13 15h3M8 11h8" />
        </svg>
      )
    case 'store':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <path {...stroke} d="M4 10 5.2 5.5h13.6L20 10M6 10v8.5h12V10" />
          <path {...stroke} d="M9 18.5V14h6v4.5" />
        </svg>
      )
    case 'users':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <circle {...stroke} cx="9" cy="9" r="3.2" />
          <path {...stroke} d="M3.5 19c.8-2.8 2.8-4.5 5.5-4.5s4.7 1.7 5.5 4.5" />
          <circle {...stroke} cx="16.5" cy="8.5" r="2.4" />
          <path {...stroke} d="M14.5 19c.5-1.8 1.8-3 3.5-3 1.2 0 2.2.5 2.9 1.5" />
        </svg>
      )
    case 'server':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <rect {...stroke} x="4" y="5" width="16" height="5" rx="1.2" />
          <rect {...stroke} x="4" y="12" width="16" height="5" rx="1.2" />
          <circle cx="7.5" cy="7.5" r="0.9" fill="currentColor" stroke="none" />
          <circle cx="7.5" cy="14.5" r="0.9" fill="currentColor" stroke="none" />
        </svg>
      )
    case 'card':
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <rect {...stroke} x="5" y="6" width="14" height="12" rx="2" />
          <path {...stroke} d="M5 10h14M8.5 14h3" />
        </svg>
      )
    case 'globe':
    default:
      return (
        <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
          <circle {...stroke} cx="12" cy="12" r="8.5" />
          <path {...stroke} d="M3.5 12h17M12 3.5c2.4 2.6 2.4 14.4 0 17M12 3.5c-2.4 2.6-2.4 14.4 0 17" />
        </svg>
      )
  }
}

export const SERVICE_ICON_OPTIONS = [
  { value: 'fiber', label: 'واي فايبر' },
  { value: 'business', label: 'أعمال / عالم' },
  { value: 'sim', label: 'شريحة / eSIM' },
  { value: 'plane', label: 'تجوال' },
  { value: 'support', label: 'دعم' },
  { value: 'offers', label: 'عروض' },
  { value: 'code', label: 'برمجة' },
  { value: 'coverage', label: 'تغطية' },
  { value: 'portal', label: 'بوابة' },
  { value: 'store', label: 'متجر / POS' },
  { value: 'users', label: 'موظفين / HR' },
  { value: 'server', label: 'استضافة / سيرفر' },
  { value: 'card', label: 'بطاقة / E-Card' },
  { value: 'globe', label: 'عام / Domain' },
] as const
