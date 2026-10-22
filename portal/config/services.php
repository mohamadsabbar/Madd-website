<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fcm' => [
        /** Legacy FCM server key (Firebase Cloud Messaging) */
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    /*
    | Meta WhatsApp Cloud API — subscription/invoice reminders.
    | Keep WHATSAPP_ENABLED=false until templates are approved and tested.
    | Direct Meta API (no Chatwoot in this project).
    */
    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        /** قالب افتراضي لتذكير قبل الانتهاء (إن لم تُحدد قوالب 3/1 منفصلة) */
        'template_before' => env('WHATSAPP_TEMPLATE_REMINDER_BEFORE'),
        'template_before_3' => env('WHATSAPP_TEMPLATE_REMINDER_BEFORE_3'),
        'template_before_1' => env('WHATSAPP_TEMPLATE_REMINDER_BEFORE_1'),
        /** رسالة فصل الخدمة */
        'template_suspend' => env('WHATSAPP_TEMPLATE_SUSPEND'),
        /** رسالة تأكيد الدفع */
        'template_paid' => env('WHATSAPP_TEMPLATE_PAID'),
        /** قائمة البداية عند أول رسالة */
        'template_start' => env('WHATSAPP_TEMPLATE_START', 'button_start_message'),
        /** بعد اختيار الدعم الفني / افحص خطك */
        'template_check_line' => env('WHATSAPP_TEMPLATE_CHECK_LINE', 'afhas_khatak'),
        'sales_reply_text' => env('WHATSAPP_SALES_REPLY_TEXT'),
        /** قديم — غير مستخدم في الجدولة الحالية */
        'template_after' => env('WHATSAPP_TEMPLATE_REMINDER_AFTER'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'ar'),
        /** none | image | text — يجب أن يطابق نوع المقدمة في قالب Meta */
        'header_type' => env('WHATSAPP_TEMPLATE_HEADER_TYPE', 'none'),
        'header_image_url' => env('WHATSAPP_TEMPLATE_HEADER_IMAGE_URL'),
        'header_text' => env('WHATSAPP_TEMPLATE_HEADER_TEXT'),
        /** مراحل تُرفق بزر URL «مدد خطك 3 أيام» — before_1,suspend */
        'cta_extend_stages' => env('WHATSAPP_CTA_EXTEND_STAGES', 'before_1,suspend'),
        /**
         * Legacy URL-button suffix. Keep false — extend CTA is Quick Reply + chat webhook.
         */
        'cta_url_dynamic' => env('WHATSAPP_CTA_URL_DYNAMIC', false),
        'extend_token_ttl_hours' => (int) env('WHATSAPP_EXTEND_TOKEN_TTL_HOURS', 72),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'chat_session_ttl_hours' => (int) env('WHATSAPP_CHAT_SESSION_TTL_HOURS', 6),
        'before_days' => (function (): array {
            $raw = explode(',', (string) env('WHATSAPP_BEFORE_DAYS', '3,1'));
            $days = array_values(array_filter(array_map('intval', $raw), fn (int $d) => $d > 0));

            return $days !== [] ? $days : [3, 1];
        })(),
        'schedule_time' => env('WHATSAPP_REMINDER_TIME', '10:30'),
    ],

    /*
    | Chatwoot Agent Bot (keep Meta WhatsApp webhook on Chatwoot).
    | Bot webhook URL: https://my.madd.ps/api/whatsapp/chatwoot-bot?token=...
    */
    'chatwoot' => [
        'base_url' => env('CHATWOOT_BASE_URL', 'https://chat.madd.ps'),
        'account_id' => (int) env('CHATWOOT_ACCOUNT_ID', 1),
        'bot_access_token' => env('CHATWOOT_BOT_ACCESS_TOKEN'),
        'bot_webhook_token' => env('CHATWOOT_BOT_WEBHOOK_TOKEN'),
    ],

    /*
    | GenieACS NBI — إدارة راوتر الزبون المنزلي (SSID / كلمة المرور / الأجهزة).
    | الافتراضي على السيرفر: http://127.0.0.1:7557  (لا تلمس منافذ GenieACS).
    | من Docker استخدم host.docker.internal:7557
    */
    'genieacs' => [
        'nbi_url' => env('GENIEACS_NBI_URL', ''),
        'username' => env('GENIEACS_NBI_USERNAME', ''),
        'password' => env('GENIEACS_NBI_PASSWORD', ''),
        'timeout' => (int) env('GENIEACS_NBI_TIMEOUT', 20),
    ],

];
