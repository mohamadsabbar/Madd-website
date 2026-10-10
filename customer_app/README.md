# تطبيق زبائن مدد (Flutter)

تطبيق جوال لزبائن مدد على Android و iOS — يعتمد على API بوابة المشترك:

`https://my.madd.ps/api`

## الميزات

- تسجيل الدخول (جوال / PPPoE) عبر Sanctum
- حسابي: الحالة، أونلاين، الباقة، التواريخ
- الفواتير (عرض فقط — بدون دفع إلكتروني)
- الاستهلاك الشهري
- طلب تجديد / تمديد ذاتي / تغيير باقة
- تغيير كلمة المرور + استعادة عبر OTP SMS
- تذاكر الدعم
- إشعارات FCM لانتهاء الاشتراك / تذكير الفواتير

## المتطلبات

- Flutter 3.22+ (`flutter doctor`)
- حساب Firebase للإنتاج (اختياري محلياً — التطبيق يعمل بدون FCM)
- على السيرفر بعد السحب: `php artisan migrate` (جداول التذاكر / OTP / device_tokens)

## الإعداد لأول مرة

```bash
cd customer_app
# أو: ./tool/bootstrap_platforms.sh
flutter create . --org ps.madd --project-name madd_customer --platforms=android,ios
flutter pub get
```

ضع شعار الشركة في:

`assets/brand/logo.png`

لبناء الإنتاج مع عنوان API:

```bash
flutter run \
  --dart-define=API_BASE=https://my.madd.ps/api
```

### Firebase (للإشعارات)

1. أنشئ مشروع Firebase واربط Android/iOS
2. أضف `android/app/google-services.json` و `ios/Runner/GoogleService-Info.plist`
3. على السيرفر في `portal/.env`:

```env
FCM_SERVER_KEY=your_legacy_server_key
```

4. شغّل أوامر التذكير (cron):

```bash
php artisan billing:send-expiry-reminders
php artisan billing:send-reminders
```

## هيكل المشروع

```
lib/
  core/           # API, auth, theme, push
  features/
    auth/
    dashboard/
    invoices/
    usage/
    plans/
    tickets/
    settings/
    shell/
```

## Backend المرتبط

انظر مجلد `portal/`:

- `POST /api/login`
- `GET /api/customer/*`
- `POST /api/customer/change-password`
- `POST /api/customer/forgot-password` / `reset-password`
- تذاكر الدعم وإدارة الأدمن في `/admin/tickets` و `/admin/renewals`
