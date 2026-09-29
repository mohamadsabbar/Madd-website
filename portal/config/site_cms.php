<?php

return [
    /** مفتاح بديل اختياري من .env لتجاوز كلمة لوحة الموقع */
    'secret' => env('SITE_CMS_SECRET', ''),
    'default_admin_password' => env('SITE_CMS_DEFAULT_PASSWORD', 'maddadmin'),
];
