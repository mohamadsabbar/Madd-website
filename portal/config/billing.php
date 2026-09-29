<?php

return [

    /*
    |--------------------------------------------------------------------------
    | يوم الفوترة وقطع الخدمة (من كل شهر)
    |--------------------------------------------------------------------------
    */
    'billing_day' => max(1, min(28, (int) env('BILLING_DAY', 5))),

    /*
    |--------------------------------------------------------------------------
    | تذكيرات SMS قبل يوم الفوترة (بالأيام)
    |--------------------------------------------------------------------------
    | مثال: [3, 1] = قبل 3 أيام وقبل يوم واحد (مع يوم الفوترة 5 → 2 و 4 من الشهر)
    */
    'reminder_days_before' => (function (): array {
        $raw = explode(',', (string) env('BILLING_REMINDER_DAYS', '3,1'));
        $days = array_values(array_filter(array_map('intval', $raw), fn (int $d) => $d > 0));

        return $days !== [] ? $days : [3, 1];
    })(),

    /*
    |--------------------------------------------------------------------------
    | وقت إرسال التذكيرات اليومي (يُتحقق من مطابقة «أيام قبل الاستحقاق»)
    |--------------------------------------------------------------------------
    */
    'reminder_time' => env('BILLING_REMINDER_TIME', '09:00'),

    /*
    |--------------------------------------------------------------------------
    | تذكير قرب انتهاء الاشتراك حسب end_date (قبلها بأيام)
    |--------------------------------------------------------------------------
    | مثال [3,1] = قبل 3 أيام وقبل يوم من تاريخ الانتهاء.
    */
    'expiry_reminder_days_before' => (function (): array {
        $raw = explode(',', (string) env('BILLING_EXPIRY_REMINDER_DAYS', '1'));
        $days = array_values(array_filter(array_map('intval', $raw), fn (int $d) => $d >= 0));

        return $days !== [] ? $days : [1];
    })(),

    /*
    |--------------------------------------------------------------------------
    | وقت إرسال تذكير قرب انتهاء الاشتراك اليومي
    |--------------------------------------------------------------------------
    */
    'expiry_reminder_time' => env('BILLING_EXPIRY_REMINDER_TIME', '10:00'),

    /*
    |--------------------------------------------------------------------------
    | وقت تنفيذ قطع الخدمة لمن لهم مستحقات (يوم الفوترة)
    |--------------------------------------------------------------------------
    */
    'suspend_unpaid_time' => env('BILLING_SUSPEND_TIME', '00:05'),

    /*
    |--------------------------------------------------------------------------
    | رسائل SMS (يمكنك تعديل النصوص)
    |--------------------------------------------------------------------------
    | المتغيرات: {name} {amount} {due_date} {company}
    */
    'sms_messages' => [
        'reminder' => env(
            'BILLING_SMS_TEMPLATE_REMINDER',
            '📢 نذكركم بوجود فاتورة مستحقة، يرجى الدفع في أقرب وقت.'
        ),
        /** متغيرات: {name} {days} {end_date} {company} */
        'extend' => env(
            'BILLING_SMS_TEMPLATE_EXTEND',
            'تم تمديد خطك {days} أيام حتى {end_date}.'
        ),
        /** بعد دفع/تجديد من واجهة الوكيل — متغيرات: {name} {amount} {end_date} {company} */
        'agent_payment' => env(
            'BILLING_SMS_TEMPLATE_AGENT_PAYMENT',
            'تم الدفع بنجاح 🎉 يسعدنا خدمتك معنا'
        ),
        /** متغيرات: {name} {days} {end_date} {company} */
        'expiry_reminder' => env(
            'BILLING_SMS_TEMPLATE_EXPIRY_REMINDER',
            'تذكير {company}: ينتهي اشتراكك بعد {days} يوم بتاريخ {end_date}.'
        ),
    ],

    'company_name' => env('BILLING_COMPANY_NAME', 'أوليفيا'),

    /*
    |--------------------------------------------------------------------------
    | إعدادات HTTP لـ SMS API
    |--------------------------------------------------------------------------
    | مزوّد HTD (فلسطين): GET
    | http://sms.htd.ps/API/SendSMS.aspx?id=…&sender=…&to=970…&msg=…
    |
    | BILLING_SMS_API_KEY = قيمة id | BILLING_SMS_SENDER = اسم المرسل المعتمد
    | BILLING_SMS_PHONE_FORMAT=ps970 يحوّل 059… إلى 97059… (12 رقماً: 970 + 9 أرقام جوال)
    | لا تستخدم في الاختبار الرمز النصي 970xxxxxx — استخدم رقماً حقيقياً وإلا H006 Invalid DESTINATION
    */
    'sms' => [
        'enabled' => filter_var(env('BILLING_SMS_ENABLED', false), FILTER_VALIDATE_BOOL),

        'url' => env('BILLING_SMS_API_URL', 'http://sms.htd.ps/API/SendSMS.aspx'),

        'method' => strtoupper((string) env('BILLING_SMS_HTTP_METHOD', 'GET')),

        'timeout' => (int) env('BILLING_SMS_TIMEOUT', 20),
        'verify_ssl' => filter_var(env('BILLING_SMS_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),

        'headers' => array_merge(
            ['Accept' => 'text/html,application/json,*/*'],
            array_filter([
                'Authorization' => env('BILLING_SMS_AUTH_HEADER'),
            ], fn ($v) => $v !== null && $v !== '')
        ),

        /** يُمرَّر كمعامل id في واجهة HTD */
        'api_key' => env('BILLING_SMS_API_KEY'),

        'sender' => env('BILLING_SMS_SENDER'),

        /** json | form — يُستخدم مع POST فقط */
        'body_style' => env('BILLING_SMS_BODY_STYLE', 'json'),

        /**
         * معاملات الاستعلام (GET) أو الـ body (POST).
         * HTD: id, sender, to, msg — القيم: @phone @message @sender @api_key
         */
        'body' => (function (): array {
            $raw = env('BILLING_SMS_BODY_TEMPLATE');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);

                return is_array($decoded) ? $decoded : [
                    'id' => '@api_key',
                    'sender' => '@sender',
                    'to' => '@phone',
                    'msg' => '@message',
                ];
            }

            return [
                'id' => '@api_key',
                'sender' => '@sender',
                'to' => '@phone',
                'msg' => '@message',
            ];
        })(),

        /** digits = كما هو | ps970 = تحويل أرقام محلية إلى صيغة 970… */
        'phone_format' => env('BILLING_SMS_PHONE_FORMAT', 'ps970'),

        /** تأخير بين كل رسالة والتالية (ملّي ثانية) عند الإرسال الجماعي من لوحة التحكم */
        'between_messages_ms' => max(0, (int) env('BILLING_SMS_BETWEEN_MS', 150)),

        /** إجبار الإرسال عبر POST (نفس معاملات GET) — مفيد إذا فشل GET أو الرسالة طويلة */
        'force_post' => filter_var(env('BILLING_SMS_FORCE_POST', false), FILTER_VALIDATE_BOOL),

        /** إذا كان رابط GET أطول من هذا (حروف)، يُستخدم POST تلقائياً لتفادي قطع الرابط أو رفض الخادم */
        'max_get_url_length' => max(500, (int) env('BILLING_SMS_MAX_GET_URL_LENGTH', 1800)),
    ],

];
