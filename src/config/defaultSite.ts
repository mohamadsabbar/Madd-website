import type { MenusConfig } from './menus'
import { DEFAULT_MENUS } from './menus'
import {
  DEFAULT_BUSINESS_HOME_SECTIONS,
  normalizeBusinessHomeSections,
  type BusinessHomeSectionId,
} from './order'

export type PlanConfig = {
  id: string
  name: string
  meta: string
  price: number
  speed: number
  features: string[]
  featured?: boolean
  accent: 'blue' | 'teal' | 'orange' | 'rose'
}

export type OfferConfig = {
  id: string
  tag: string
  title: string
  link: string
  tone: '1' | '2' | '3'
}

export type FaqConfig = { q: string; a: string }
export type BenefitConfig = {
  title: string
  text: string
  link?: string
  icon?: string
  iconDataUrl?: string | null
}
export type HomeSectionId = 'benefits' | 'plans' | 'programming'
export type { BusinessHomeSectionId }

export type {
  MenuColumn,
  MenuEntry,
  MenuFeatured,
  MenuLinkItem,
  MenusConfig,
} from './menus'

export type HeroSlide = {
  id: string
  imageDataUrl: string | null
  link: string
  alt: string
  brandEn?: string
  title?: string
  subtitle?: string
  ctaPrimary?: string
  ctaPrimaryLink?: string
  ctaSecondary?: string
  ctaSecondaryLink?: string
  showSecondary?: boolean
  tone?: 'teal' | 'dark' | 'accent'
}

export type PageHeroConfig = {
  kicker: string
  title: string
  lead: string
  cta?: string
  ctaLink?: string
  seoTitle?: string
  seoDescription?: string
}

export type CoverageArea = {
  id: string
  name: string
  city: string
  status: 'available' | 'check' | 'unavailable'
}

export type PosSectionConfig = {
  id: string
  title: string
  text: string
  items: string[]
  imageDataUrl?: string | null
}

export type PosStatConfig = { id: string; value: string; label: string }
export type Testimonial = { id: string; name: string; text: string; role: string }
export type TrustStat = { id: string; value: string; label: string }
export type ValueCard = { id: string; title: string; text: string }
export type BizPillarCard = {
  id: string
  title: string
  text: string
  link: string
  linkLabel?: string
  imageDataUrl: string | null
}
export type BizCard = {
  id: string
  title: string
  text: string
  link: string
  tone?: string
  icon?: string
  iconDataUrl?: string | null
}

export type BusinessOffering = {
  id: string
  title: string
  subtitle: string
  text: string
  bullets: string[]
  link: string
  tone: 'teal' | 'blue' | 'orange' | 'rose' | 'violet' | 'mint' | 'gold' | 'slate' | 'coral'
}

export type SiteConfig = {
  seo: {
    title: string
    description: string
  }
  brand: {
    nameAr: string
    nameEn: string
    logoDataUrl: string | null
    faviconDataUrl: string | null
    primaryColor: string
    secondaryColor: string
    /** Accent for /business — copper / professional */
    businessSecondaryColor: string
    /** Soft sky highlight on business surfaces */
    businessHighlightColor: string
    tagline: string
    ctaIndividuals: string
    ctaBusiness: string
  }
  visibility: {
    showChat: boolean
    showOffers: boolean
    showProgramming: boolean
    showCoverage: boolean
    showBusinessPos: boolean
    showBusinessHr: boolean
  }
  layout: {
    homeSections: HomeSectionId[]
    businessHomeSections: BusinessHomeSectionId[]
  }
  menus: MenusConfig
  admin: {
    password: string
  }
  home: {
    heroTitle: string
    heroSubtitle: string
    heroCta: string
    heroCtaLink: string
    heroCtaSecondary: string
    heroCtaSecondaryLink: string
    plansTitle: string
    plansKicker: string
    plansCta: string
    slides: HeroSlide[]
    sliderAutoplay: boolean
    sliderIntervalSec: number
    showSliderArrows: boolean
    showSliderDots: boolean
    benefitsTitle: string
    benefitsLead: string
    benefits: BenefitConfig[]
  }
  pages: {
    plans: PageHeroConfig
    offers: PageHeroConfig
    coverage: PageHeroConfig
    support: PageHeroConfig
    about: PageHeroConfig
    order: PageHeroConfig
    programming: PageHeroConfig
    business: PageHeroConfig
    businessPlans: PageHeroConfig
    businessPos: PageHeroConfig
    businessHr: PageHeroConfig
    businessProgramming: PageHeroConfig
    businessJoin: PageHeroConfig
    businessWeb: PageHeroConfig
  }
  coverage: {
    areas: CoverageArea[]
    formTitle: string
    formLead: string
    resultAvailable: string
    resultCheck: string
    resultUnavailable: string
  }
  about: {
    values: ValueCard[]
  }
  business: {
    quickLinks: BizCard[]
    pillars: BizPillarCard[]
    connectTitle: string
    connectLead: string
    digitalTitle: string
    digitalLead: string
    digitalOfferings: BusinessOffering[]
    bundleTitle: string
    bundleText: string
    bundleItems: string[]
    pillarsTitle: string
  }
  plansUi: {
    currency: string
    period: string
    featuredBadge: string
    orderCta: string
    compareTitle: string
    legalNote: string
  }
  trust: {
    title: string
    lead: string
    stats: TrustStat[]
    testimonials: Testimonial[]
  }
  support: {
    quickLinks: BizCard[]
  }
  businessHome: {
    heroTitle: string
    heroSubtitle: string
    heroCta: string
    slides: HeroSlide[]
  }
  offers: OfferConfig[]
  faq: FaqConfig[]
  plans: PlanConfig[]
  businessPlans: PlanConfig[]
  programming: {
    intro: string
    pageTitle: string
    pageSubtitle: string
    ctaTitle: string
    ctaButton: string
    items: { id: string; title: string; text: string }[]
  }
  digital: {
    posTitle: string
    posText: string
    posFeatures: string[]
    posHeroImage: string | null
    posStats: PosStatConfig[]
    posSections: PosSectionConfig[]
    posAppUrl: string
    hrTitle: string
    hrText: string
    hrFeatures: string[]
  }
  footer: {
    tagline: string
    about: string
  }
  contact: {
    phone: string
    email: string
    whatsapp: string
    address: string
    facebook: string
    instagram: string
    chatLabel: string
  }
  labels: {
    individuals: string
    business: string
    about: string
  }
}

const page = (
  kicker: string,
  title: string,
  lead: string,
  extra: Partial<PageHeroConfig> = {},
): PageHeroConfig => ({ kicker, title, lead, ...extra })

export const DEFAULT_SITE: SiteConfig = {
  seo: {
    title: 'مدد للاتصالات | إنترنت واي فايبر وحلول رقمية',
    description:
      'مدد للاتصالات — إنترنت واي فايبر منزلي وتجاري، أنظمة نقاط بيع وإدارة موظفين، وبرمجة مواقع وتطبيقات بفريق دعم محلي.',
  },
  brand: {
    nameAr: 'مدد',
    nameEn: 'مدد للاتصالات',
    logoDataUrl: null,
    faviconDataUrl: null,
    primaryColor: '#2b5d66',
    secondaryColor: '#ed875e',
    businessSecondaryColor: '#c4784a',
    businessHighlightColor: '#3b8ea5',
    tagline: 'اتصال موثوق… وحلول تكمّل عملك.',
    ctaIndividuals: 'حسابي',
    ctaBusiness: 'بوابة الأعمال',
  },
  visibility: {
    showChat: true,
    showOffers: true,
    showProgramming: true,
    showCoverage: true,
    showBusinessPos: true,
    showBusinessHr: true,
  },
  layout: {
    homeSections: ['benefits', 'plans', 'programming'],
    businessHomeSections: [...DEFAULT_BUSINESS_HOME_SECTIONS],
  },
  menus: structuredClone(DEFAULT_MENUS),
  admin: {
    password: 'maddadmin',
  },
  home: {
    heroTitle: 'إنترنت واي فايبر\nأسرع… وأقرب إليك',
    heroSubtitle:
      'مدد للاتصالات تقدّم اتصالاً منزلياً وتجارياً بسرعات ثابتة، تركيباً منظّماً، ودعماً فنياً يتابعك بعد التشغيل.',
    heroCta: 'استكشف الباقات',
    heroCtaLink: '/plans',
    heroCtaSecondary: 'تحقق من التغطية',
    heroCtaSecondaryLink: '/coverage',
    plansTitle: 'باقات واي فايبر للمنزل',
    plansKicker: 'Wi-Fiber',
    plansCta: 'مقارنة كل الباقات',
    slides: [
      { id: 'slide-1', imageDataUrl: null, link: '/plans', alt: 'مدد للاتصالات — باقات واي فايبر' },
      { id: 'slide-2', imageDataUrl: null, link: '/coverage', alt: 'تغطية مدد في منطقتك' },
      { id: 'slide-3', imageDataUrl: null, link: '/business', alt: 'حلول مدد للأعمال' },
    ],
    sliderAutoplay: true,
    sliderIntervalSec: 6,
    showSliderArrows: true,
    showSliderDots: true,
    benefitsTitle: 'أفضل الخدمات خصيصاً لك',
    benefitsLead: '',
    benefits: [
      { title: 'واي فايبر', text: '', link: '/plans', icon: 'fiber' },
      { title: 'خدمات الأعمال', text: '', link: '/business', icon: 'business' },
      { title: 'البرمجة', text: '', link: '/programming', icon: 'code' },
      { title: 'التغطية', text: '', link: '/coverage', icon: 'coverage' },
      { title: 'العروض', text: '', link: '/offers', icon: 'offers' },
      { title: 'الدعم', text: '', link: '/support', icon: 'support' },
      { title: 'بوابة المشترك', text: '', link: 'https://my.madd.ps/my/login', icon: 'portal' },
      { title: 'اطلب الآن', text: '', link: '/order', icon: 'sim' },
    ],
  },
  pages: {
    plans: page('الباقات', 'باقات واي فايبر من مدد', 'اختر السرعة التي تناسب أفراد عائلتك أو عملك — راوتر مشمول في الباقات.', {
      cta: 'اطلب التركيب',
      ctaLink: '/order',
      seoTitle: 'باقات واي فايبر | مدد للاتصالات',
      seoDescription: 'قارن باقات مدد للاتصالات واطلب تركيب واي فايبر في منطقتك.',
    }),
    offers: page('العروض', 'عروض مدد للاتصالات', 'عروض اشتراك وتركيب وبرمجة لفترة محدودة — راجع التفاصيل واحجز مبكراً.', {
      seoTitle: 'عروض مدد للاتصالات',
      seoDescription: 'أحدث عروض الإنترنت والتركيب والبرمجة من مدد.',
    }),
    coverage: page(
      'التغطية',
      'هل مدد في منطقتك؟',
      'تحقق من توفر واي فايبر مدد قبل الاشتراك — بخطوة واحدة واضحة.',
      {
        seoTitle: 'تغطية مدد للاتصالات | فحص المناطق',
        seoDescription: 'تحقق من توفر واي فايبر مدد في مدينتك ومنطقتك قبل تقديم الطلب.',
      },
    ),
    support: page('الدعم', 'مركز مساعدة مدد', 'أسئلة شائعة، تواصل سريع، ورابط بوابة المشترك لمتابعة حسابك.', {
      seoTitle: 'دعم مدد للاتصالات',
      seoDescription: 'مساعدة مشتركي مدد: تغطية، طلبات، وبوابة الحساب.',
    }),
    about: page(
      'من نحن',
      'مدد للاتصالات',
      'نوفّر اتصالاً يعتمد عليه، ونبني حوله أنظمة وبرمجة تساعد المنزل والعمل على الاستمرار بثقة.',
      {
        seoTitle: 'عن مدد للاتصالات',
        seoDescription: 'تعرّف على مدد للاتصالات: إنترنت واي فايبر وحلول رقمية محلية.',
      },
    ),
    order: page('الطلب', 'اطلب واي فايبر من مدد', 'عبّئ بياناتك وسنتواصل لتأكيد الموعد والتركيب في أقرب وقت ممكن.', {
      seoTitle: 'طلب واي فايبر | مدد للاتصالات',
      seoDescription: 'قدّم طلب اشتراك واي فايبر مع مدد للاتصالات.',
    }),
    programming: page(
      'برمجة',
      'برمجة مواقع وتطبيقات',
      'فريق مدد يطوّر مواقع ومتاجر وتطبيقات وأنظمة تشغيل مخصصة للأفراد والمنشآت.',
      {
        cta: 'اطلب مشروعاً',
        ctaLink: '/order?service=programming',
        seoTitle: 'برمجة مواقع وتطبيقات | مدد',
      },
    ),
    business: page(
      'أعمال',
      'حلول اتصال وتشغيل للأعمال',
      'اتصال موثوق، أنظمة تشغيل يومية، وتطوير رقمي — في عرض واحد واضح.',
      {
        cta: 'اطلب عرضاً',
        ctaLink: '/business/join',
        seoTitle: 'حلول أعمال | مدد',
        seoDescription: 'حلول اتصال وأنظمة تشغيل وتطوير رقمي للشركات والمؤسسات.',
      },
    ),
    businessPlans: page(
      'أعمال',
      'باقات الاتصال للأعمال',
      'سرعات مناسبة للمكاتب والفروع، مع أولوية دعم وخيار عنوان IP ثابت.',
      {
        cta: 'اطلب عرضاً',
        ctaLink: '/business/join',
        seoTitle: 'باقات أعمال | مدد',
      },
    ),
    businessPos: page(
      'POS',
      'نظام نقاط البيع من مدد',
      'مبيعات، مخزون، تقارير، وتشغيل يومي للمحل — محلياً أو عبر السحابة حسب احتياجك.',
      {
        cta: 'اطلب عرضاً',
        ctaLink: '/business/join?service=pos',
        seoTitle: 'نقاط البيع POS | مدد',
        seoDescription: 'نظام نقاط بيع لإدارة المبيعات والمخزون والتقارير مع دعم مدد.',
      },
    ),
    businessHr: page(
      'HR',
      'إدارة الحضور والموظفين',
      'حضور، إجازات، وورديات — بتقارير واضحة للإدارة.',
      {
        cta: 'اطلب النظام',
        ctaLink: '/business/join?service=hr',
      },
    ),
    businessProgramming: page(
      'برمجة',
      'تطوير رقمي للأعمال',
      'مواقع، تطبيقات، وأنظمة مخصصة تدعم عملياتك اليومية.',
      {
        cta: 'ابدأ مشروعاً',
        ctaLink: '/business/join?service=programming',
      },
    ),
    businessJoin: page(
      'تواصل',
      'اطلب عرضاً لمنشأتك',
      'أرسل بياناتك الأساسية وسيتواصل معك فريق المبيعات خلال وقت قصير.',
      {
        seoTitle: 'اطلب عرض أعمال | مدد',
      },
    ),
    businessWeb: page(
      'حضور رقمي',
      'Domain · Hosting · E-Card والمزيد',
      'سجّل نطاقك، استضف موقعك، وقدّم شركتك ببطاقة رقمية — مع دعم مدد من خطوة واحدة.',
      {
        cta: 'اطلب عرضاً',
        ctaLink: '/business/join',
        seoTitle: 'Domain واستضافة وبطاقة رقمية | مدد أعمال',
      },
    ),
  },
  coverage: {
    areas: [
      { id: 'a1', name: 'وسط المدينة', city: 'نابلس', status: 'available' },
      { id: 'a2', name: 'رفيديا', city: 'نابلس', status: 'available' },
      { id: 'a3', name: 'المخفية', city: 'نابلس', status: 'available' },
      { id: 'a4', name: 'عصيرة الشمالية', city: 'نابلس', status: 'check' },
      { id: 'a5', name: 'بيت فوريك', city: 'نابلس', status: 'check' },
      { id: 'a6', name: 'حوارة', city: 'نابلس', status: 'check' },
      { id: 'a7', name: 'مناطق قيد التوسع', city: 'المحافظات', status: 'unavailable' },
    ],
    formTitle: 'افحص تغطية مدد',
    formLead: 'اختر المدينة ثم المنطقة — تظهر الحالة فوراً: متوفر، قيد التأكيد، أو غير متوفر.',
    resultAvailable: 'ممتاز — خدمة مدد متوفرة في هذه المنطقة. يمكنك تقديم طلب التركيب الآن.',
    resultCheck: 'التغطية قيد التأكيد هنا. سيتواصل فريق مدد معك لتأكيد الإمكانية وموعد الزيارة.',
    resultUnavailable: 'الخدمة غير متاحة حالياً في هذه المنطقة. سجّل طلبك وسنبلّغك عند فتح التغطية.',
  },
  about: {
    values: [
      {
        id: 'v1',
        title: 'اتصال أولاً',
        text: 'نبني شبكة واي فايبر عملية: سرعة مناسبة، استقرار يومي، وتركيب واضح الخطوات.',
      },
      {
        id: 'v2',
        title: 'حلول تكمل الخط',
        text: 'نقاط بيع وحضور موظفين وبرمجة — حتى لا تضطر للتعامل مع عشر جهات مختلفة.',
      },
      {
        id: 'v3',
        title: 'قربون من المشترك',
        text: 'دعم محلي، بوابة حساب، وتمديد ذاتي عند الحاجة — بشفافية في المواعيد والفواتير.',
      },
    ],
  },
  business: {
    connectTitle: 'اتصال وأنظمة تشغيل',
    connectLead: 'خط أعمال مستقر، نقاط بيع، موارد بشرية، وتطوير — لفرق تعتمد على التشغيل اليومي.',
    digitalTitle: 'حضورك الرقمي مع مدد',
    digitalLead:
      'من اسم النطاق إلى الاستضافة والبطاقة الإلكترونية — نساعدك على الظهور باحتراف أمام عملائك، مع خيارات تناسب الشركات الناشئة والمؤسسات.',
    bundleTitle: 'باقة واحدة، جهات اتصال أقل',
    bundleText:
      'يمكنك الجمع بين الاتصال، الاستضافة، البريد المؤسسي، وأنظمة التشغيل في عرض موحّد — فريق مدد يتابع التفعيل والدعم.',
    bundleItems: [
      'Domain + Hosting + SSL في مسار واحد',
      'بريد مؤسسي على نطاقك',
      'E-Card مرتبطة بموقعك ووسائل التواصل',
      'ربط اختياري مع POS أو CRM',
    ],
    quickLinks: [
      {
        id: 'q1',
        title: 'باقات الاتصال',
        text: 'سرعات ودعم أولوية',
        link: '/business/plans',
        tone: 'teal',
        icon: 'fiber',
      },
      {
        id: 'q2',
        title: 'نقاط البيع',
        text: 'مبيعات ومخزون وتقارير',
        link: '/business/pos',
        tone: 'blue',
        icon: 'store',
      },
      {
        id: 'q3',
        title: 'الموارد البشرية',
        text: 'حضور وإجازات',
        link: '/business/hr',
        tone: 'violet',
        icon: 'users',
      },
      {
        id: 'q4',
        title: 'التطوير الرقمي',
        text: 'مواقع وتطبيقات',
        link: '/business/programming',
        tone: 'rose',
        icon: 'code',
      },
      {
        id: 'q5',
        title: 'Domain',
        text: 'حجز ونقل النطاقات',
        link: '/business/web#domain',
        tone: 'coral',
        icon: 'globe',
      },
      {
        id: 'q6',
        title: 'Web Hosting',
        text: 'استضافة مواقع آمنة',
        link: '/business/web#hosting',
        tone: 'mint',
        icon: 'server',
      },
      {
        id: 'q7',
        title: 'E-Card',
        text: 'بطاقة عمل رقمية',
        link: '/business/web#ecard',
        tone: 'gold',
        icon: 'card',
      },
      {
        id: 'q8',
        title: 'حلول رقمية',
        text: 'عرض كامل للخدمات',
        link: '/business/web',
        tone: 'slate',
        icon: 'business',
      },
    ],
    digitalOfferings: [
      {
        id: 'domain',
        title: 'Domain Names',
        subtitle: 'نطاقات',
        text: 'احجز أو انقل اسم نطاق يعبّر عن علامتك — مع إعداد DNS وربط البريد والموقع.',
        bullets: ['حجز ونقل Domain', 'إدارة DNS', 'حماية WHOIS', 'تجديد وتذكير قبل الانتهاء'],
        link: '/business/join?service=domain',
        tone: 'violet',
      },
      {
        id: 'hosting',
        title: 'Web Hosting',
        subtitle: 'استضافة',
        text: 'استضافة مواقع للشركات: سرعة، نسخ احتياطي، وSSL — مناسبة للمواقع التعريفية والمتاجر.',
        bullets: ['مساحة ونطاق تردد', 'SSL مجاني أو مدفوع', 'نسخ احتياطي دوري', 'لوحة تحكم واضحة'],
        link: '/business/join?service=hosting',
        tone: 'blue',
      },
      {
        id: 'ecard',
        title: 'E-Card',
        subtitle: 'بطاقة رقمية',
        text: 'بطاقة عمل إلكترونية لفرق المبيعات والإدارة — مشاركة فورية عبر QR أو الرابط.',
        bullets: ['ملف شخصي للشركة', 'QR وروابط مباشرة', 'تحديث بدون إعادة طباعة', 'تصميم متوافق مع الهوية'],
        link: '/business/join?service=ecard',
        tone: 'coral',
      },
      {
        id: 'email',
        title: 'Business Email',
        subtitle: 'بريد مؤسسي',
        text: 'عناوين بريد على نطاقك (info@company.com) لتعزيز الثقة في التواصل مع العملاء.',
        bullets: ['صناديق للموظفين', 'ربط مع Domain', 'حماية SPAM أساسية', 'دعم الإعداد الأولي'],
        link: '/business/join?service=email',
        tone: 'mint',
      },
      {
        id: 'ssl',
        title: 'SSL & Security',
        subtitle: 'أمان',
        text: 'شهادات SSL وتوجيه آمن لموقعك — ضرورية للمتاجر والنماذج وبوابات الدفع.',
        bullets: ['HTTPS للموقع', 'تجديد الشهادات', 'مراقبة انتهاء الصلاحية', 'إرشاد للمتاجر الإلكترونية'],
        link: '/business/join?service=ssl',
        tone: 'slate',
      },
      {
        id: 'dns',
        title: 'DNS & Cloud',
        subtitle: 'إعدادات متقدمة',
        text: 'سجلات A/CNAME/MX، ربط Subdomains (مثل pos.madd.ps)، وتوجيه للخدمات السحابية.',
        bullets: ['Subdomains للأنظمة', 'ربط API وPOS', 'توجيه البريد', 'دعم فني للإعداد'],
        link: '/business/join?service=dns',
        tone: 'gold',
      },
    ],
    pillarsTitle: 'لماذا مدد للأعمال؟',
    pillars: [
      {
        id: 'p1',
        title: 'اتصال يعتمد عليه',
        text: '',
        link: '/business/plans',
        linkLabel: '',
        imageDataUrl: null,
      },
      {
        id: 'p2',
        title: 'حضور رقمي متكامل',
        text: '',
        link: '/business/web',
        linkLabel: '',
        imageDataUrl: null,
      },
      {
        id: 'p3',
        title: 'تشغيل أبسط',
        text: '',
        link: '/business/pos',
        linkLabel: '',
        imageDataUrl: null,
      },
    ],
  },
  plansUi: {
    currency: 'شيكل',
    period: '/ شهر',
    featuredBadge: 'الأكثر طلباً',
    orderCta: 'اطلب هذه الباقة',
    compareTitle: 'مقارنة سريعة بين الباقات',
    legalNote:
      'الأسعار قد تشمل الضرائب حسب العرض النهائي. السرعات نظرية وتعتمد على التغطية والاستخدام داخل المنزل أو المنشأة.',
  },
  trust: {
    title: 'ثقة مشتركي مدد',
    lead: 'أرقام تقريبية من شبكة مدد للاتصالات في مناطق التغطية الحالية.',
    stats: [
      { id: 's1', value: '+2,500', label: 'اشتراك منزلي' },
      { id: 's2', value: '45+', label: 'منطقة وخدمة' },
      { id: 's3', value: '24/7', label: 'قنوات دعم' },
      { id: 's4', value: '98%', label: 'رضا بعد التركيب' },
    ],
    testimonials: [
      {
        id: 't1',
        name: 'أحمد خ.',
        role: 'مشترك منزلي — نابلس',
        text: 'التركيب تم في الموعد والسرعة ثابتة للبث والعمل من البيت مع العائلة.',
      },
      {
        id: 't2',
        name: 'سارة م.',
        role: 'صاحبة محل',
        text: 'خط مدد مع نقطة البيع ما انقطع معنا في أوقات الزحمة — وهذا أهم شيء للمحل.',
      },
      {
        id: 't3',
        name: 'خالد ر.',
        role: 'مكتب خدمات',
        text: 'الدعم يرد بسرعة، وبوابة المشترك تسهّل متابعة الفاتورة بدون لفّ ودوران.',
      },
    ],
  },
  support: {
    quickLinks: [
      { id: 'sq1', title: 'واتساب مدد', text: 'تواصل مباشر مع فريق الدعم', link: 'whatsapp', tone: 'teal' },
      { id: 'sq2', title: 'فحص التغطية', text: 'هل خدمتنا وصلت منطقتك؟', link: '/coverage', tone: 'blue' },
      { id: 'sq3', title: 'بوابة المشترك', text: 'فواتير، استهلاك، وتمديد', link: 'portal', tone: 'orange' },
    ],
  },
  businessHome: {
    heroTitle: 'حلول أعمال أوضح… وأبسط',
    heroSubtitle: 'اتصال موثوق، أنظمة تشغيل يومية، وتطوير رقمي — بتجربة احترافية تناسب نمو منشأتك.',
    heroCta: 'اطلب عرضاً',
    slides: [
      { id: 'biz-slide-1', imageDataUrl: null, link: '/business/plans', alt: 'مدد أعمال — باقات الاتصال' },
      { id: 'biz-slide-2', imageDataUrl: null, link: '/business/pos', alt: 'أنظمة نقاط البيع' },
      { id: 'biz-slide-3', imageDataUrl: null, link: '/business/join', alt: 'اطلب عرضاً لمنشأتك' },
    ],
  },
  offers: [
    {
      id: 'o1',
      tag: 'عرض الشهر',
      title: 'اشترك بباقة بلس واحصل على أول شهر مخفّض',
      link: '/order?plan=plus',
      tone: '1',
    },
    {
      id: 'o2',
      tag: 'تركيب',
      title: 'تركيب منزلي مجاني على باقات بلس فما فوق',
      link: '/order',
      tone: '2',
    },
    {
      id: 'o3',
      tag: 'أعمال',
      title: 'خصم على أول مشروع رقمي مع باقة أعمال',
      link: '/business/join?service=programming',
      tone: '3',
    },
  ],
  faq: [
    {
      q: 'ما هو واي فايبر من مدد؟',
      a: 'إنترنت لاسلكي ثابت عبر شبكة مدد للاتصالات، مصمّم للاستخدام المنزلي والتجاري بسرعات متعددة وراوتر مشمول حسب الباقة.',
    },
    {
      q: 'كيف أعرف إن التغطية وصلت منطقتي؟',
      a: 'من صفحة التغطية اختر المدينة والمنطقة. إن كانت الحالة «متوفر» يمكنك الطلب فوراً، وإن كانت «قيد التأكيد» سيتواصل الفريق معك.',
    },
    {
      q: 'هل يمكنني متابعة فاتورتي إلكترونياً؟',
      a: 'نعم — عبر بوابة المشترك يمكنك الاطلاع على الفواتير والاستهلاك وطلب التمديد حسب سياسة الحساب.',
    },
    {
      q: 'هل تقدّمون أنظمة للمحال والشركات؟',
      a: 'نعم. نقدّم أنظمة نقاط بيع، إدارة حضور وموظفين، وتطوير مواقع وتطبيقات يمكن ربطها بخدمات الاتصال.',
    },
    {
      q: 'كيف أقدّم طلباً؟',
      a: 'من صفحة تقديم الطلب أو عبر واتساب. لحلول الأعمال استخدم نموذج طلب العرض.',
    },
  ],
  plans: [
    {
      id: 'basic',
      name: 'أساسي',
      meta: 'تصفح ودراسة يومية',
      price: 99,
      speed: 50,
      accent: 'blue',
      features: ['حتى 50 ميجا تحميل', 'راوتر Wi‑Fi مشمول', 'دعم فني عبر القنوات الرسمية'],
    },
    {
      id: 'plus',
      name: 'بلس',
      meta: 'عائلات وبث وترفيه',
      price: 149,
      speed: 150,
      accent: 'teal',
      featured: true,
      features: ['حتى 150 ميجا تحميل', 'راوتر ثنائي النطاق', 'تركيب منزلي مجاني لأول مرة'],
    },
    {
      id: 'pro',
      name: 'برو',
      meta: 'عمل من المنزل واجتماعات',
      price: 199,
      speed: 300,
      accent: 'orange',
      features: ['حتى 300 ميجا تحميل', 'أولوية في الدعم', 'IP ثابت اختياري'],
    },
    {
      id: 'max',
      name: 'ماكس',
      meta: 'أقصى أداء للمنزل الذكي',
      price: 249,
      speed: 500,
      accent: 'rose',
      features: ['حتى 500 ميجا تحميل', 'راوتر Wi‑Fi 6', 'متابعة جودة بعد التشغيل'],
    },
  ],
  businessPlans: [
    {
      id: 'biz-start',
      name: 'ستارت',
      meta: 'مكاتب وفروع صغيرة',
      price: 199,
      speed: 150,
      accent: 'blue',
      features: ['حتى 150 ميجا', 'عنوان IP ثابت', 'دعم أولوية'],
    },
    {
      id: 'biz-plus',
      name: 'بلس',
      meta: 'فرق ونقاط بيع',
      price: 299,
      speed: 300,
      accent: 'teal',
      featured: true,
      features: ['حتى 300 ميجا', 'اتفاقية مستوى خدمة', 'راوتر أعمال'],
    },
    {
      id: 'biz-pro',
      name: 'برو',
      meta: 'عمليات يومية مكثفة',
      price: 399,
      speed: 500,
      accent: 'orange',
      features: ['حتى 500 ميجا', 'SLA متقدم', 'مدير حساب مخصص'],
    },
    {
      id: 'biz-ultra',
      name: 'ألترا',
      meta: 'مواقع حساسة للانقطاع',
      price: 549,
      speed: 500,
      accent: 'rose',
      features: ['متابعة أداء مستمرة', 'SLA مؤسسي', 'صيانة مجدولة'],
    },
  ],
  programming: {
    intro: 'نصمّم مواقع وتطبيقات وأنظمة تشغيل واضحة وقابلة للتوسع — مع تركيز على سهولة الاستخدام واستقرار الخدمة.',
    pageTitle: 'تطوير رقمي للأعمال',
    pageSubtitle: 'من موقع تعريفي إلى نظام تشغيل متكامل — بخطوات واضحة وتسليم منظم.',
    ctaTitle: 'هل لديك مشروع؟ نساعدك على تحويله إلى منتج رقمي جاهز للعمل.',
    ctaButton: 'اطلب استشارة',
    items: [
      {
        id: 'websites',
        title: 'مواقع ومتاجر',
        text: 'مواقع شركات، صفحات هبوط، ومتاجر إلكترونية سريعة ومتجاوبة.',
      },
      {
        id: 'apps',
        title: 'تطبيقات الجوال',
        text: 'تطبيقات خدمة عملاء أو إدارة ميدانية على iOS وAndroid.',
      },
      {
        id: 'custom',
        title: 'أنظمة مخصصة',
        text: 'حجوزات، لوحات إدارة، وتكامل مع نقاط البيع والموارد البشرية.',
      },
      {
        id: 'support',
        title: 'دعم بعد الإطلاق',
        text: 'تحديثات، استضافة، ومتابعة أعطال للحفاظ على استمرارية العمل.',
      },
    ],
  },
  digital: {
    posTitle: 'نظام نقاط البيع',
    posText:
      'منصة واحدة لإدارة المبيعات والمخزون والفواتير والتقارير — تناسب المحال والمطاعم والصيدليات والمكاتب.',
    posFeatures: [
      'بيع سريع وإيصالات',
      'مخزون وتتبع حركة الأصناف',
      'تقارير يومية للإدارة',
      'صلاحيات كاشير / مدير',
      'عمل محلي مع خيار سحابة',
      'دعم أجهزة الطباعة والماسح',
    ],
    posAppUrl: 'https://pos.madd.ps',
    posHeroImage: null,
    posStats: [
      { id: 'ps1', value: 'محلي', label: 'تشغيل على جهاز المحل' },
      { id: 'ps2', value: 'سحابة', label: 'وصول اختياري عبر المتصفح' },
      { id: 'ps3', value: '24/7', label: 'دعم تشغيل من مدد' },
      { id: 'ps4', value: 'POS', label: 'مبيعات ومخزون وتقارير' },
    ],
    posSections: [
      {
        id: 'sales',
        title: 'المبيعات والفوترة',
        text: 'إتمام البيع بسرعة مع إيصالات واضحة وتتبع للمدفوعات.',
        items: ['نقطة بيع سريعة', 'فواتير وإيصالات', 'مرتجعات واستبدالات', 'طرق دفع متعددة'],
      },
      {
        id: 'stock',
        title: 'المخزون',
        text: 'معرفة الكمية المتاحة وحركة الأصناف دون تعقيد.',
        items: ['إدارة الأصناف', 'حركة مخزون مؤرخة', 'تنبيهات نقص الكمية', 'جرد وتسويات'],
      },
      {
        id: 'reports',
        title: 'التقارير والرقابة',
        text: 'لوحة يومية للمبيعات والمشتريات والمرتجعات لاتخاذ قرار أسرع.',
        items: ['ملخص يومي', 'تقارير مبيعات', 'مشتريات ومرتجعات', 'صلاحيات حسب الدور'],
      },
      {
        id: 'sectors',
        title: 'مناسب لقطاعات مختلفة',
        text: 'إعدادات عملية حسب طبيعة النشاط.',
        items: ['محال وتجزئة', 'مطاعم وكافيهات', 'صيدليات', 'فواتير ومكاتب خدمات'],
      },
      {
        id: 'deploy',
        title: 'تشغيل مرن',
        text: 'ابدأ محلياً على جهاز المحل، وأضف الوصول السحابي عند الحاجة.',
        items: ['تشغيل محلي أولًا', 'وصول عبر المتصفح اختياري', 'نسخ احتياطي', 'تعدد نقاط البيع'],
      },
      {
        id: 'hardware',
        title: 'الأجهزة والدعم',
        text: 'توافق مع مسارات الطباعة والماسحات الشائعة مع دعم مدد للتركيب والمتابعة.',
        items: ['طابعات إيصالات', 'ماسحات باركود', 'أدراج نقدية', 'تركيب وتدريب من مدد'],
      },
    ],
    hrTitle: 'إدارة الحضور والموظفين',
    hrText: 'حضور وإجازات وورديات — بصلاحيات وتقارير تناسب الإدارة.',
    hrFeatures: ['حضور لحظي', 'إجازات وورديات', 'تقارير شهرية', 'صلاحيات متعددة'],
  },
  footer: {
    tagline: 'مدد للاتصالات — اتصال وحلول رقمية في مكان واحد.',
    about:
      'مدد تقدّم حلول اتصال وأنظمة تشغيل وتطوير رقمي للأفراد والأعمال — بتجربة واضحة ودعم مستمر بعد الإطلاق.',
  },
  contact: {
    phone: '0590000000',
    email: 'info@madd.ps',
    whatsapp: 'https://wa.me/970590000000',
    address: 'نابلس — فلسطين',
    facebook: 'https://facebook.com/',
    instagram: 'https://instagram.com/',
    chatLabel: 'محادثة مدد',
  },
  labels: {
    individuals: 'أفراد',
    business: 'أعمال',
    about: 'من نحن',
  },
}

export const STORAGE_KEY = 'madd-site-config-v5'
export const AUTH_KEY = 'madd-admin-auth'
export const ADMIN_PASSWORD_SESSION = 'madd-admin-password'

export function normalizeSiteConfig(raw: Partial<SiteConfig> | null | undefined): SiteConfig {
  const merged = deepMergeSite(structuredClone(DEFAULT_SITE), raw || {})
  merged.layout = {
    homeSections: normalizeHomeSectionsSafe(merged.layout?.homeSections),
    businessHomeSections: normalizeBusinessHomeSections(merged.layout?.businessHomeSections),
  }
  if (!merged.home.slides?.length) {
    merged.home.slides = structuredClone(DEFAULT_SITE.home.slides)
  } else {
    merged.home.slides = merged.home.slides.map((s, i) => ({
      id: s.id || `slide-${i}`,
      imageDataUrl: s.imageDataUrl ?? null,
      link: s.link ?? s.ctaPrimaryLink ?? '',
      alt: s.alt || s.title?.split('\n')[0] || `شريحة ${i + 1}`,
    }))
  }
  if (!merged.businessHome.slides?.length) {
    merged.businessHome.slides = structuredClone(DEFAULT_SITE.businessHome.slides)
  } else {
    merged.businessHome.slides = merged.businessHome.slides.map((s, i) => ({
      id: s.id || `biz-slide-${i}`,
      imageDataUrl: s.imageDataUrl ?? null,
      link: s.link ?? s.ctaPrimaryLink ?? '',
      alt: s.alt || s.title?.split('\n')[0] || `شريحة أعمال ${i + 1}`,
    }))
  }
  if (!merged.menus?.individuals?.length) {
    merged.menus = { ...merged.menus, individuals: structuredClone(DEFAULT_MENUS.individuals) }
  }
  if (!merged.menus?.business?.length) {
    merged.menus = { ...merged.menus, business: structuredClone(DEFAULT_MENUS.business) }
  }
  const legacyBusiness =
    merged.businessHome?.heroTitle?.includes('اتصال وتشغيل في خط واحد') ||
    merged.business?.pillars?.some((p) => p.title.includes('الكاشير'))
  if (legacyBusiness) {
    merged.businessHome = structuredClone(DEFAULT_SITE.businessHome)
    merged.business = structuredClone(DEFAULT_SITE.business)
    merged.businessPlans = structuredClone(DEFAULT_SITE.businessPlans)
    merged.digital = structuredClone(DEFAULT_SITE.digital)
    merged.pages.business = structuredClone(DEFAULT_SITE.pages.business)
    merged.pages.businessPlans = structuredClone(DEFAULT_SITE.pages.businessPlans)
    merged.pages.businessPos = structuredClone(DEFAULT_SITE.pages.businessPos)
    merged.pages.businessHr = structuredClone(DEFAULT_SITE.pages.businessHr)
    merged.pages.businessProgramming = structuredClone(DEFAULT_SITE.pages.businessProgramming)
    merged.pages.businessJoin = structuredClone(DEFAULT_SITE.pages.businessJoin)
    merged.pages.businessWeb = structuredClone(DEFAULT_SITE.pages.businessWeb)
    merged.programming = structuredClone(DEFAULT_SITE.programming)
    merged.brand.ctaBusiness = DEFAULT_SITE.brand.ctaBusiness
    merged.menus.business = structuredClone(DEFAULT_MENUS.business)
  }
  if (!merged.business.digitalOfferings?.length) {
    merged.business = {
      ...merged.business,
      connectTitle: DEFAULT_SITE.business.connectTitle,
      connectLead: DEFAULT_SITE.business.connectLead,
      digitalTitle: DEFAULT_SITE.business.digitalTitle,
      digitalLead: DEFAULT_SITE.business.digitalLead,
      digitalOfferings: structuredClone(DEFAULT_SITE.business.digitalOfferings),
      bundleTitle: DEFAULT_SITE.business.bundleTitle,
      bundleText: DEFAULT_SITE.business.bundleText,
      bundleItems: structuredClone(DEFAULT_SITE.business.bundleItems),
    }
  }
  const defaultPillarById = Object.fromEntries(DEFAULT_SITE.business.pillars.map((p) => [p.id, p]))
  merged.business.pillars = (merged.business.pillars?.length
    ? merged.business.pillars
    : DEFAULT_SITE.business.pillars
  )
    .slice(0, 3)
    .map((p) => {
      const def = defaultPillarById[p.id] || DEFAULT_SITE.business.pillars[0]
      const ext = p as BizPillarCard
      return {
        id: p.id || def.id,
        title: p.title || def.title,
        text: p.text || '',
        link: ext.link || def.link,
        linkLabel: ext.linkLabel || '',
        imageDataUrl: ext.imageDataUrl ?? null,
      }
    })
  while (merged.business.pillars.length < 3) {
    const def = DEFAULT_SITE.business.pillars[merged.business.pillars.length]
    merged.business.pillars.push(structuredClone(def))
  }
  if (!merged.business.pillarsTitle) {
    merged.business.pillarsTitle = DEFAULT_SITE.business.pillarsTitle
  }

  if ((merged.business.quickLinks?.length || 0) < 6) {
    merged.business.quickLinks = structuredClone(DEFAULT_SITE.business.quickLinks)
  } else {
    const defaultQuickById = Object.fromEntries(
      DEFAULT_SITE.business.quickLinks.map((q) => [q.id, q]),
    )
    merged.business.quickLinks = merged.business.quickLinks.map((q) => ({
      ...q,
      icon: q.icon || defaultQuickById[q.id]?.icon || 'globe',
      iconDataUrl: q.iconDataUrl ?? null,
    }))
  }
  if (!merged.pages.businessWeb?.title) {
    merged.pages.businessWeb = structuredClone(DEFAULT_SITE.pages.businessWeb)
  }
  if (!merged.digital.posSections?.length) {
    merged.digital.posSections = structuredClone(DEFAULT_SITE.digital.posSections)
  } else {
    merged.digital.posSections = merged.digital.posSections.map((s, i) => ({
      id: s.id || `pos-${i}`,
      title: s.title || '',
      text: s.text || '',
      items: s.items?.length ? s.items : [],
      imageDataUrl: s.imageDataUrl ?? null,
    }))
  }
  if (!merged.digital.posStats?.length) {
    merged.digital.posStats = structuredClone(DEFAULT_SITE.digital.posStats)
  }
  if (merged.digital.posHeroImage === undefined) {
    merged.digital.posHeroImage = DEFAULT_SITE.digital.posHeroImage
  }
  if (!merged.digital.posAppUrl) {
    merged.digital.posAppUrl = DEFAULT_SITE.digital.posAppUrl
  }
  merged.brand = { ...merged.brand }
  if (merged.brand.secondaryColor === '#3a7580') {
    merged.brand.secondaryColor = DEFAULT_SITE.brand.secondaryColor
  }
  if (!merged.brand.businessSecondaryColor) {
    merged.brand.businessSecondaryColor = DEFAULT_SITE.brand.businessSecondaryColor
  }
  if (!merged.brand.businessHighlightColor) {
    merged.brand.businessHighlightColor = DEFAULT_SITE.brand.businessHighlightColor
  }
  const benefits = merged.home.benefits || []
  const legacyBenefits =
    !benefits.length ||
    benefits.every((b) => !b.link) ||
    merged.home.benefitsTitle.includes('لماذا مشتركون')
  if (legacyBenefits) {
    merged.home.benefitsTitle = DEFAULT_SITE.home.benefitsTitle
    merged.home.benefitsLead = DEFAULT_SITE.home.benefitsLead
    merged.home.benefits = structuredClone(DEFAULT_SITE.home.benefits)
  } else {
    merged.home.benefits = benefits.map((b) => ({
      title: b.title || 'خدمة',
      text: b.text || '',
      link: b.link || '/',
      icon: b.icon || 'globe',
      iconDataUrl: b.iconDataUrl ?? null,
    }))
  }
  return merged
}

function normalizeHomeSectionsSafe(sections: HomeSectionId[] | undefined): HomeSectionId[] {
  const allowed: HomeSectionId[] = ['benefits', 'plans', 'programming']
  const list = (sections || []).filter((s): s is HomeSectionId => allowed.includes(s as HomeSectionId))
  return list.length ? list : [...DEFAULT_SITE.layout.homeSections]
}

export function deepMergeSite(base: SiteConfig, patch: Partial<SiteConfig>): SiteConfig {
  return {
    ...base,
    ...patch,
    seo: { ...base.seo, ...patch.seo },
    brand: { ...base.brand, ...patch.brand },
    visibility: { ...base.visibility, ...patch.visibility },
    layout: {
      ...base.layout,
      ...patch.layout,
      homeSections: patch.layout?.homeSections ?? base.layout.homeSections,
      businessHomeSections: patch.layout?.businessHomeSections ?? base.layout.businessHomeSections,
    },
    menus: {
      individuals: patch.menus?.individuals ?? base.menus.individuals,
      business: patch.menus?.business ?? base.menus.business,
    },
    admin: { ...base.admin, ...patch.admin },
    home: {
      ...base.home,
      ...patch.home,
      benefits: patch.home?.benefits ?? base.home.benefits,
      slides: patch.home?.slides ?? base.home.slides,
    },
    pages: {
      ...base.pages,
      ...patch.pages,
      plans: { ...base.pages.plans, ...patch.pages?.plans },
      offers: { ...base.pages.offers, ...patch.pages?.offers },
      coverage: { ...base.pages.coverage, ...patch.pages?.coverage },
      support: { ...base.pages.support, ...patch.pages?.support },
      about: { ...base.pages.about, ...patch.pages?.about },
      order: { ...base.pages.order, ...patch.pages?.order },
      programming: { ...base.pages.programming, ...patch.pages?.programming },
      business: { ...base.pages.business, ...patch.pages?.business },
      businessPlans: { ...base.pages.businessPlans, ...patch.pages?.businessPlans },
      businessPos: { ...base.pages.businessPos, ...patch.pages?.businessPos },
      businessHr: { ...base.pages.businessHr, ...patch.pages?.businessHr },
      businessProgramming: { ...base.pages.businessProgramming, ...patch.pages?.businessProgramming },
      businessJoin: { ...base.pages.businessJoin, ...patch.pages?.businessJoin },
      businessWeb: { ...base.pages.businessWeb, ...patch.pages?.businessWeb },
    },
    coverage: {
      ...base.coverage,
      ...patch.coverage,
      areas: patch.coverage?.areas ?? base.coverage.areas,
    },
    about: {
      ...base.about,
      ...patch.about,
      values: patch.about?.values ?? base.about.values,
    },
    business: {
      ...base.business,
      ...patch.business,
      quickLinks: patch.business?.quickLinks ?? base.business.quickLinks,
      pillars: patch.business?.pillars ?? base.business.pillars,
      digitalOfferings: patch.business?.digitalOfferings ?? base.business.digitalOfferings,
      bundleItems: patch.business?.bundleItems ?? base.business.bundleItems,
    },
    plansUi: { ...base.plansUi, ...patch.plansUi },
    trust: {
      ...base.trust,
      ...patch.trust,
      stats: patch.trust?.stats ?? base.trust.stats,
      testimonials: patch.trust?.testimonials ?? base.trust.testimonials,
    },
    support: {
      ...base.support,
      ...patch.support,
      quickLinks: patch.support?.quickLinks ?? base.support.quickLinks,
    },
    businessHome: {
      ...base.businessHome,
      ...patch.businessHome,
      slides: patch.businessHome?.slides ?? base.businessHome.slides,
    },
    offers: patch.offers ?? base.offers,
    faq: patch.faq ?? base.faq,
    plans: patch.plans ?? base.plans,
    businessPlans: patch.businessPlans ?? base.businessPlans,
    programming: {
      ...base.programming,
      ...patch.programming,
      items: patch.programming?.items ?? base.programming.items,
    },
    digital: {
      ...base.digital,
      ...patch.digital,
      posFeatures: patch.digital?.posFeatures ?? base.digital.posFeatures,
      posSections: patch.digital?.posSections ?? base.digital.posSections,
      posStats: patch.digital?.posStats ?? base.digital.posStats,
      posHeroImage: patch.digital?.posHeroImage ?? base.digital.posHeroImage,
      hrFeatures: patch.digital?.hrFeatures ?? base.digital.hrFeatures,
    },
    footer: { ...base.footer, ...patch.footer },
    contact: { ...base.contact, ...patch.contact },
    labels: { ...base.labels, ...patch.labels },
  }
}
