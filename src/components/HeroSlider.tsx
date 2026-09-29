import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import type { HeroSlide } from '../config/defaultSite'
import { useSiteConfig } from '../context/SiteConfigContext'

type Audience = 'individuals' | 'business'

export function HeroSlider({ audience = 'individuals' }: { audience?: Audience }) {
  const { config } = useSiteConfig()
  const source = audience === 'business' ? config.businessHome.slides : config.home.slides
  const slides = (source || []).filter((s) => s.imageDataUrl)

  const [index, setIndex] = useState(0)
  const count = slides.length
  const autoplay = config.home.sliderAutoplay !== false
  const intervalMs = Math.max(3, config.home.sliderIntervalSec || 6) * 1000
  const showArrows = config.home.showSliderArrows !== false
  const showDots = config.home.showSliderDots !== false

  const go = useCallback(
    (next: number) => {
      if (count <= 0) return
      setIndex(((next % count) + count) % count)
    },
    [count],
  )

  useEffect(() => {
    setIndex(0)
  }, [count, audience])

  useEffect(() => {
    if (!autoplay || count <= 1) return
    const id = window.setInterval(() => go(index + 1), intervalMs)
    return () => window.clearInterval(id)
  }, [autoplay, count, intervalMs, index, go])

  const emptyHint =
    audience === 'business'
      ? 'ارفع صور سلايدر الأعمال من لوحة الإدارة ← السلايدر ← أعمال'
      : 'ارفع صور السلايدر من لوحة الإدارة ← السلايدر ← أفراد'

  if (!count) {
    return (
      <section className="hero-block">
        <section className="hero hero--images hero--empty" aria-label="سلايدر الصور">
          <div className="hero__placeholder">
            <p>{emptyHint}</p>
          </div>
        </section>
      </section>
    )
  }

  return (
    <section className="hero-block">
      <section className="hero hero--images" aria-label="سلايدر الصور" aria-roledescription="carousel">
        <div className="hero__slides">
          {slides.map((slide, i) => (
            <HeroImageSlide key={slide.id} slide={slide} active={i === index} />
          ))}
        </div>

        {showArrows && count > 1 ? (
          <>
            <button
              type="button"
              className="hero__arrow hero__arrow--prev"
              aria-label="الشريحة السابقة"
              onClick={() => go(index - 1)}
            >
              ‹
            </button>
            <button
              type="button"
              className="hero__arrow hero__arrow--next"
              aria-label="الشريحة التالية"
              onClick={() => go(index + 1)}
            >
              ›
            </button>
          </>
        ) : null}
      </section>

      {showDots && count > 1 ? (
        <div className="hero__dots hero__dots--below" role="tablist" aria-label="شرائح العرض">
          {slides.map((s, i) => (
            <button
              key={s.id}
              type="button"
              role="tab"
              aria-selected={i === index}
              className={`hero__dot${i === index ? ' is-active' : ''}`}
              onClick={() => go(i)}
              aria-label={`الشريحة ${i + 1}`}
            />
          ))}
        </div>
      ) : null}
    </section>
  )
}

function HeroImageSlide({ slide, active }: { slide: HeroSlide; active: boolean }) {
  const img = (
    <img className="hero__img" src={slide.imageDataUrl!} alt={slide.alt || ''} draggable={false} />
  )

  return (
    <article className={`hero__slide${active ? ' is-active' : ''}`} aria-hidden={!active}>
      {slide.link ? (
        <Link to={slide.link} className="hero__img-link" tabIndex={active ? 0 : -1}>
          {img}
        </Link>
      ) : (
        img
      )}
    </article>
  )
}
