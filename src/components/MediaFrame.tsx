type Props = {
  src?: string | null
  alt?: string
  label?: string
  hint?: string
  className?: string
}

export function MediaFrame({
  src,
  alt = '',
  label,
  hint = 'ارفع الصورة من لوحة التحكم ← POS والموظفين',
  className = '',
}: Props) {
  return (
    <div className={`media-frame${className ? ` ${className}` : ''}`}>
      {src ? (
        <img src={src} alt={alt || label || ''} className="media-frame__img" loading="lazy" decoding="async" />
      ) : (
        <div className="media-frame__placeholder">
          <div className="media-frame__screen">
            <span className="media-frame__bar" />
            <span className="media-frame__bar media-frame__bar--short" />
            <span className="media-frame__bar media-frame__bar--mid" />
          </div>
          {label ? <p className="media-frame__label">{label}</p> : null}
          <p className="media-frame__hint">{hint}</p>
        </div>
      )}
    </div>
  )
}
