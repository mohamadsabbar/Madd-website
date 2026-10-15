# تذكيرات WhatsApp لاشتراكات مدد

## لماذا مسار مباشر إلى Meta؟

المشروع لا يضم Chatwoot. الإرسال عبر **WhatsApp Cloud API** مباشرة لتفادي مسار غير موجود وإرسال مزدوج.

## تسلسل الرسائل

| الترتيب | متى | المرحلة | المصدر |
|--------|-----|---------|--------|
| 1 | قبل 3 أيام من `end_date` | `before_3` | جدولة يومية |
| 2 | قبل يوم واحد | `before_1` | جدولة يومية |
| 3 | عند فصل/تعليق الخدمة | `suspend` | أوامر التعليق التلقائي |
| 4 | عند تسجيل دفعة | `paid` | تحصيل وكيل / دفع فاتورة أدمن |

فشل WhatsApp **لا** يوقف الفوترة أو MikroTik.

## الأمر (تذكيرات قبل الانتهاء فقط)

```bash
# معاينة فقط (لا إرسال ولا تسجيل sent)
php artisan billing:send-whatsapp-reminders --dry-run

# إرسال فعلي (يتطلب WHATSAPP_ENABLED=true + قوالب معتمدة)
php artisan billing:send-whatsapp-reminders
```

مجدول يومياً في `routes/console.php` على `WHATSAPP_REMINDER_TIME` (افتراضي 10:30) مع `withoutOverlapping()`.

رسالة الفصل تُرسل من `billing:suspend-unpaid` و `app:suspend-expired-subscribers`.  
رسالة الدفع تُرسل عند التحصيل (وكيل أو أدمن).

## قواعد الاختيار (التذكيرات)

- فاتورة مفتوحة (`unpaid` / `partial`) و`remainingBalance() > 0`
- مراحل حسب `subscribers.end_date`: قبل 3 أيام، قبل يوم
- إيقاف تلقائي عند الدفع / الإلغاء
- منع التكرار عبر جدول `whatsapp_reminder_logs` (قيد فريد على subscriber + stage + period_key)

## متغيرات `.env`

```env
WHATSAPP_ENABLED=false
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_API_VERSION=v21.0
WHATSAPP_TEMPLATE_REMINDER_BEFORE=madd_reminder_before
WHATSAPP_TEMPLATE_REMINDER_BEFORE_3=
WHATSAPP_TEMPLATE_REMINDER_BEFORE_1=
WHATSAPP_TEMPLATE_SUSPEND=madd_suspend
WHATSAPP_TEMPLATE_PAID=madd_paid
WHATSAPP_TEMPLATE_LANGUAGE=ar
WHATSAPP_BEFORE_DAYS=3,1
WHATSAPP_REMINDER_TIME=10:30
```

إن تُركت `BEFORE_3` / `BEFORE_1` فارغتين يُستخدم `WHATSAPP_TEMPLATE_REMINDER_BEFORE` لكليهما.

## قوالب Meta (متغيرات الجسم بالترتيب)

1. اسم العميل  
2. رقم الفاتورة  
3. المبلغ (مستحق للتذكير/الفصل، أو المدفوع لرسالة الدفع) بالشيكل  
4. تاريخ انتهاء الاشتراك  
