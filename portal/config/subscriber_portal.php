<?php

return [

    /*
    | كلمة مرور تطبيق العملاء الافتراضية عند إنشاء الحساب تلقائياً مع المشترك.
    | يمكن تجاوزها من .env: SUBSCRIBER_PORTAL_DEFAULT_PASSWORD
    */
    'default_password' => env('SUBSCRIBER_PORTAL_DEFAULT_PASSWORD', '100200300'),

    /*
    | تمديد ذاتي عند انتهاء الاشتراك (بدون موافقة الشركة).
    | الترتيب: أول تمديد = 3 أيام، ثاني تمديد = يومان، ثم يلزم التواصل مع الشركة.
    */
    'self_extend_days' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('SUBSCRIBER_SELF_EXTEND_DAYS', '3,2'))
    ), fn (int $d) => $d > 0)),

];
