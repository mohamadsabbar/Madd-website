export type MenuLinkItem = {
  id: string
  label: string
  to: string
  desc: string
}

export type MenuColumn = {
  id: string
  title: string
  items: MenuLinkItem[]
}

export type MenuFeatured = {
  enabled: boolean
  title: string
  text: string
  to: string
  cta: string
}

export type MenuEntry =
  | {
      id: string
      type: 'link'
      enabled: boolean
      label: string
      to: string
    }
  | {
      id: string
      type: 'mega'
      enabled: boolean
      label: string
      columns: MenuColumn[]
      featured: MenuFeatured
    }

export type MenusConfig = {
  individuals: MenuEntry[]
  business: MenuEntry[]
}

export const DEFAULT_MENUS: MenusConfig = {
  individuals: [
    {
      id: 'ind-fiber',
      type: 'mega',
      enabled: true,
      label: 'واي فايبر',
      columns: [
        {
          id: 'col-ind-1',
          title: 'الأفراد',
          items: [
            { id: 'i1', label: 'الباقات والأسعار', to: '/plans', desc: 'من 50 إلى 500 ميجا' },
            { id: 'i2', label: 'لماذا واي فايبر', to: '/', desc: 'مزايا الخدمة' },
            { id: 'i3', label: 'فحص التغطية', to: '/coverage', desc: 'تحقق من منطقتك' },
          ],
        },
        {
          id: 'col-ind-2',
          title: 'الاشتراك',
          items: [
            { id: 'i4', label: 'تقديم طلب', to: '/order', desc: 'ابدأ الاشتراك' },
            { id: 'i5', label: 'العروض', to: '/offers', desc: 'عروض هذا الشهر' },
            { id: 'i6', label: 'الدعم', to: '/support', desc: 'أسئلة ومساعدة' },
          ],
        },
      ],
      featured: {
        enabled: true,
        title: 'واي فايبر بلس',
        text: '150 ميجا مع تركيب مجاني لأول مرة',
        to: '/plans',
        cta: 'عرض الباقات',
      },
    },
    {
      id: 'ind-prog',
      type: 'mega',
      enabled: true,
      label: 'البرمجة',
      columns: [
        {
          id: 'col-prog-1',
          title: 'خدمات البرمجة',
          items: [
            { id: 'p1', label: 'برمجة المواقع', to: '/programming#websites', desc: 'مواقع ومتاجر إلكترونية' },
            { id: 'p2', label: 'تطبيقات الجوال', to: '/programming#apps', desc: 'iOS و Android' },
            { id: 'p3', label: 'أنظمة مخصصة', to: '/programming#custom', desc: 'حسب احتياجك' },
          ],
        },
        {
          id: 'col-prog-2',
          title: 'ابدأ',
          items: [
            { id: 'p4', label: 'صفحة البرمجة', to: '/programming', desc: 'كل الخدمات' },
            { id: 'p5', label: 'اطلب مشروع', to: '/order?service=programming', desc: 'نموذج طلب' },
          ],
        },
      ],
      featured: {
        enabled: true,
        title: 'نبرمج لك',
        text: 'مواقع وتطبيقات وأنظمة — للأفراد والأعمال',
        to: '/programming',
        cta: 'استكشف',
      },
    },
    { id: 'ind-offers', type: 'link', enabled: true, label: 'العروض', to: '/offers' },
    { id: 'ind-support', type: 'link', enabled: true, label: 'الدعم', to: '/support' },
    { id: 'ind-order', type: 'link', enabled: true, label: 'تقديم طلب', to: '/order' },
  ],
  business: [
    {
      id: 'biz-services',
      type: 'mega',
      enabled: true,
      label: 'الحلول',
      columns: [
        {
          id: 'col-biz-1',
          title: 'الاتصال',
          items: [
            { id: 'b1', label: 'باقات الأعمال', to: '/business/plans', desc: 'سرعات ودعم أولوية' },
            { id: 'b2', label: 'نظرة عامة', to: '/business', desc: 'حلول مدد للأعمال' },
          ],
        },
        {
          id: 'col-biz-2',
          title: 'الحضور الرقمي',
          items: [
            { id: 'b3', label: 'Domain & Hosting', to: '/business/web', desc: 'نطاق واستضافة' },
            { id: 'b4', label: 'E-Card', to: '/business/web#ecard', desc: 'بطاقة رقمية' },
            { id: 'b4b', label: 'بريد مؤسسي', to: '/business/web#email', desc: 'Business Email' },
          ],
        },
        {
          id: 'col-biz-3',
          title: 'الأنظمة',
          items: [
            { id: 'b5', label: 'نقاط البيع', to: '/business/pos', desc: 'مبيعات ومخزون' },
            { id: 'b6', label: 'الموارد البشرية', to: '/business/hr', desc: 'حضور وإجازات' },
          ],
        },
      ],
      featured: {
        enabled: true,
        title: 'حلول متكاملة',
        text: 'اتصال + Domain + Hosting + E-Card في عرض واحد.',
        to: '/business/join',
        cta: 'اطلب عرضاً',
      },
    },
    {
      id: 'biz-prog',
      type: 'mega',
      enabled: true,
      label: 'التطوير',
      columns: [
        {
          id: 'col-biz-p1',
          title: 'الخدمات',
          items: [
            { id: 'bp1', label: 'المواقع والمتاجر', to: '/business/programming#websites', desc: 'حضور رقمي احترافي' },
            { id: 'bp2', label: 'تطبيقات الجوال', to: '/business/programming#apps', desc: 'iOS و Android' },
            { id: 'bp3', label: 'أنظمة مخصصة', to: '/business/programming#custom', desc: 'تكامل وتشغيل' },
          ],
        },
        {
          id: 'col-biz-p2',
          title: 'الطلب',
          items: [
            { id: 'bp4', label: 'خدمات التطوير', to: '/business/programming', desc: 'التفاصيل الكاملة' },
            { id: 'bp5', label: 'اطلب عرضاً', to: '/business/join?service=programming', desc: 'تواصل مع الفريق' },
          ],
        },
      ],
      featured: {
        enabled: true,
        title: 'تطوير للأعمال',
        text: 'مواقع وتطبيقات وأنظمة تدعم نمو منشأتك.',
        to: '/business/programming',
        cta: 'اعرف المزيد',
      },
    },
    { id: 'biz-plans', type: 'link', enabled: true, label: 'الباقات', to: '/business/plans' },
    { id: 'biz-join', type: 'link', enabled: true, label: 'اطلب عرضاً', to: '/business/join' },
  ],
}
