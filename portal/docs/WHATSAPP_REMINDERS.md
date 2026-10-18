# تذكيرات WhatsApp لاشتراكات مدد

## تسلسل الرسائل

| الترتيب | متى | المرحلة | زر |
|--------|-----|---------|-----|
| 1 | قبل 3 أيام | `before_3` | لا |
| 2 | قبل يوم | `before_1` | Quick Reply: مدد خطك 3 أيام |
| 3 | فصل الخدمة | `suspend` | Quick Reply: مدد خطك 3 أيام |
| 4 | دفع | `paid` | لا |

## محادثة التمديد برقم الاشتراك

1. الزبون يضغط زر **مدد خطك 3 أيام** (Quick Reply في القالب).
2. البوت يرد: «أرسل رقم الاشتراك» (مثال `MADD-000003`).
3. الزبون يرسل الرقم → تمديد ذاتي إن كان مؤهلاً (منتهٍ أو مفصول + محاولات متبقية).

رقم الاشتراك فريد لكل مشترك (`subscribers.subscription_number`). الإرسال يبقى على رقم الجوال.

### المسار الموصى به مع Chatwoot (لديكم الآن)

1. **اترك** Callback URL في Meta على Chatwoot كما هو:
   `https://chat.madd.ps/webhooks/whatsapp/+970...`
2. في Chatwoot → Settings → Bots → Add Bot:
   - الاسم: `مدد تمديد`
   - Webhook URL:
     ```text
     https://my.madd.ps/api/whatsapp/chatwoot-bot?token=YOUR_SECRET
     ```
   - انسخ **Access Token** الخاص بالبوت → `CHATWOOT_BOT_ACCESS_TOKEN`
3. اربط البوت بـ Inbox واتساب.
4. في `portal/.env`:
   ```env
   CHATWOOT_BASE_URL=https://chat.madd.ps
   CHATWOOT_ACCOUNT_ID=1
   CHATWOOT_BOT_ACCESS_TOKEN=...
   CHATWOOT_BOT_WEBHOOK_TOKEN=YOUR_SECRET
   ```

بهذا تبقى رسائل الدعم في Chatwoot، ومدد يعالج فقط مسار التمديد.

### Webhook مباشر Meta (بديل — بدون Chatwoot على نفس الرقم)

- Callback URL: `https://my.madd.ps/api/whatsapp/webhook`
- Verify token: نفس `WHATSAPP_WEBHOOK_VERIFY_TOKEN`
- اشترك في `messages`

### قوالب Meta (before_1 / suspend)

- Body: `{{1}}`…`{{4}}` كالسابق
- Button: **Quick Reply** نصه `مدد خطك 3 أيام` (ليس Visit website)

## متغيرات `.env`

```env
WHATSAPP_ENABLED=true
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_APP_SECRET=
WHATSAPP_WEBHOOK_VERIFY_TOKEN=
WHATSAPP_TEMPLATE_REMINDER_BEFORE=madd_reminder_before
WHATSAPP_TEMPLATE_REMINDER_BEFORE_1=madd_reminder_before_1
WHATSAPP_TEMPLATE_SUSPEND=madd_suspend
WHATSAPP_TEMPLATE_PAID=madd_paid
WHATSAPP_TEMPLATE_LANGUAGE=ar
WHATSAPP_CTA_EXTEND_STAGES=before_1,suspend
WHATSAPP_CTA_URL_DYNAMIC=false
WHATSAPP_CHAT_SESSION_TTL_HOURS=6
```

## أوامر

```bash
php artisan billing:send-whatsapp-reminders --dry-run
php artisan billing:send-whatsapp-reminders
```
