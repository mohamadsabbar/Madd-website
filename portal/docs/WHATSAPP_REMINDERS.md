# تذكيرات WhatsApp لاشتراكات مدد

## لماذا مسار مباشر إلى Meta؟

المشروع لا يضم Chatwoot. الإرسال عبر **WhatsApp Cloud API** مباشرة لتفادي مسار غير موجود وإرسال مزدوج.

## تسلسل الرسائل

| الترتيب | متى | المرحلة | المصدر | زر التمديد |
|--------|-----|---------|--------|------------|
| 1 | قبل 3 أيام من `end_date` | `before_3` | جدولة يومية | لا |
| 2 | قبل يوم واحد | `before_1` | جدولة يومية | نعم — مدد خطك 3 أيام |
| 3 | عند فصل/تعليق الخدمة | `suspend` | أوامر التعليق التلقائي | نعم — مدد خطك 3 أيام |
| 4 | عند تسجيل دفعة | `paid` | تحصيل وكيل / دفع فاتورة أدمن | لا |

فشل WhatsApp **لا** يوقف الفوترة أو MikroTik.

## زر «مدد خطك 3 أيام»

### الوضع الموصى به (زر ثابت — متوافق مع Chatwoot)

- رابط الزر في Meta: `https://my.madd.ps/wa/extend` (**بدون** `{{1}}`)
- في `.env`: `WHATSAPP_CTA_URL_DYNAMIC=false`
- عند الضغط: صفحة تطلب رقم الجوال ثم تنفّذ التمديد إن كان متاحاً

### وضع التوكن الديناميكي (اختياري)

- رابط الزر: `https://my.madd.ps/wa/extend/{{1}}`
- في `.env`: `WHATSAPP_CTA_URL_DYNAMIC=true`
- النظام يرسل لاحقة التوكن مع رسالة واتساب

إذا ظهر خطأ Meta `Button at index 0 of type Url does not require parameters` فالزر ثابت — اترك `WHATSAPP_CTA_URL_DYNAMIC=false`.

### إعداد القالب في Meta

1. أنشئ/حدّث `madd_reminder_before_1` و`madd_suspend` (Utility / Arabic).
2. Body: المتغيرات `{{1}}` الاسم، `{{2}}` الفاتورة، `{{3}}` المبلغ، `{{4}}` التاريخ.
3. Buttons → **Visit website**
   - نص الزر: `مدد خطك 3 أيام`
   - الرابط الثابت: `https://my.madd.ps/wa/extend`
4. اعتمد القالب ثم ضع الأسماء في `.env`.

## الأمر (تذكيرات قبل الانتهاء فقط)

```bash
php artisan billing:send-whatsapp-reminders --dry-run
php artisan billing:send-whatsapp-reminders
```

مجدول يومياً في `routes/console.php` على `WHATSAPP_REMINDER_TIME` (افتراضي 10:30).

## متغيرات `.env`

```env
WHATSAPP_ENABLED=false
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_API_VERSION=v21.0
WHATSAPP_TEMPLATE_REMINDER_BEFORE=madd_reminder_before
WHATSAPP_TEMPLATE_REMINDER_BEFORE_3=
WHATSAPP_TEMPLATE_REMINDER_BEFORE_1=madd_reminder_before_1
WHATSAPP_TEMPLATE_SUSPEND=madd_suspend
WHATSAPP_TEMPLATE_PAID=madd_paid
WHATSAPP_TEMPLATE_LANGUAGE=ar
WHATSAPP_TEMPLATE_HEADER_TYPE=none
WHATSAPP_TEMPLATE_HEADER_IMAGE_URL=
WHATSAPP_TEMPLATE_HEADER_TEXT=
WHATSAPP_CTA_EXTEND_STAGES=before_1,suspend
WHATSAPP_CTA_URL_DYNAMIC=false
WHATSAPP_EXTEND_TOKEN_TTL_HOURS=72
WHATSAPP_BEFORE_DAYS=3,1
WHATSAPP_REMINDER_TIME=10:30
```

## قوالب Meta (متغيرات الجسم بالترتيب)

1. اسم العميل  
2. رقم الفاتورة  
3. المبلغ (مستحق للتذكير/الفصل، أو المدفوع لرسالة الدفع) بالشيكل  
4. تاريخ انتهاء الاشتراك  
