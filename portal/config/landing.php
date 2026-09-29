<?php

return [

    /*
    |--------------------------------------------------------------------------
    | صفحة الشركة التعريفية على الرئيسية /
    |--------------------------------------------------------------------------
    | للزائر: يُعرض القالب public.company-home على https://نطاقك.com/
    | للوكلاء والإدارة: استخدم رابط الدخول https://نطاقك.com/login
    */
    'company_name' => env('LANDING_COMPANY_NAME', env('BILLING_COMPANY_NAME', 'اسم شركتك')),

    'headline' => env('LANDING_HEADLINE', 'خدمات إنترنت موثوقة لعملائنا'),

    'lead' => env('LANDING_LEAD', 'نقدّم لكم اتصالاً عالياً الجودة ودعماً فنياً متواصلاً. نسعد بخدمتكم في منطقة تغطيتنا.'),

    /** فقرة إضافية (اختياري) — نص عادي */
    'extra' => env('LANDING_EXTRA'),

    'contact_phone' => env('LANDING_PHONE'),
    'contact_email' => env('LANDING_EMAIL'),
    'contact_address' => env('LANDING_ADDRESS'),

    /** أسطر قصيرة (مفصولة بـ | في .env) */
    'features' => array_values(array_filter(array_map('trim', explode('|', (string) env(
        'LANDING_FEATURES',
        'سرعات مناسبة لجميع الاستخدامات|دعم فني متابع للشكاوى|تغطية مستقرة في منطقتنا|أسعار شفافة'
    ))))),
];
