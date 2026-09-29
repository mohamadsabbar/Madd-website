import { ServiceIcon } from './ServiceIcon'

type Props = {
  icon?: string
  iconDataUrl?: string | null
  className?: string
  imgClassName?: string
  alt?: string
}

/** Preset SVG icon, or uploaded PNG/SVG when iconDataUrl is set. */
export function ConfigurableIcon({
  icon,
  iconDataUrl,
  className,
  imgClassName = 'configurable-icon__img',
  alt = '',
}: Props) {
  if (iconDataUrl) {
    return <img src={iconDataUrl} alt={alt} className={imgClassName} />
  }
  return <ServiceIcon name={icon} className={className} />
}
