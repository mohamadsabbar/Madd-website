import { Link } from 'react-router-dom'
import { Reveal } from '../components/Reveal'
import { HeroSlider } from '../components/HeroSlider'
import { PlansGrid, ProgrammingSection } from '../components/PageShared'
import { ServicesSection } from '../components/ServicesSection'
import { normalizeHomeSections, type HomeSectionId } from '../config/order'
import { useSiteConfig } from '../context/SiteConfigContext'

export function HomePage() {
  const { config } = useSiteConfig()
  const sections = normalizeHomeSections(config.layout?.homeSections)
  const h = config.home

  const renderSection = (id: HomeSectionId) => {
    switch (id) {
      case 'benefits':
        return <ServicesSection key="benefits" title={h.benefitsTitle} items={h.benefits} />
      case 'plans':
        return (
          <section key="plans" className="section plans">
            <div className="container">
              <Reveal>
                <div className="section__head">
                  <div>
                    <p className="section__kicker">{h.plansKicker}</p>
                    <h2 className="section__title section__title--start">{h.plansTitle}</h2>
                  </div>
                  <Link to="/plans" className="text-link">
                    {h.plansCta} ‹
                  </Link>
                </div>
              </Reveal>
              <PlansGrid plans={config.plans} orderTo="/order" />
            </div>
          </section>
        )
      case 'programming':
        return config.visibility.showProgramming ? (
          <ProgrammingSection key="programming" orderPath="/order?service=programming" />
        ) : null
      default:
        return null
    }
  }

  return (
    <>
      <HeroSlider />
      {sections.map((id) => renderSection(id))}
    </>
  )
}
