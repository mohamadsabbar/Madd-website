# مدد | MADD — موقع متعدد الصفحات + بوابة MikroTik

- **الواجهة:** React + TypeScript + Vite  
- **بوابة المشتركين / الإدارة:** Laravel داخل مجلد `portal/` (من حزمة MikroTik)

## التشغيل السريع

### 1) الموقع (Vite)

```bash
npm install
npm run dev
```

الموقع: http://127.0.0.1:5173/  
زر **تسجيل الدخول** يفتح بوابة Laravel على المنفذ 8000.

اختياري — انسخ `.env.example` إلى `.env` وعدّل:

```
VITE_PORTAL_URL=http://127.0.0.1:8000
```

### 2) البوابة (Laravel + MySQL)

قاعدة البيانات المحلية: `madd_portal` (مستوردة من `olivptvp_main.sql`).

```bash
mysql -e "CREATE DATABASE IF NOT EXISTS madd_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql madd_portal < /path/to/olivptvp_main.sql

cd portal
# عدّل DB_* في .env (المستخدم المحلي بدون كلمة مرور غالباً)
php artisan config:clear
php artisan serve --host=127.0.0.1 --port=8000
```

الدخول: http://127.0.0.1:8000/login

### حسابات الدخول (محلي)

| الدور | البريد | كلمة المرور |
|--------|--------|-------------|
| إدارة | `admin@mikro.local` | `admin12345` |
| وكيل | `agent@mikro.local` | `agent12345` |

عدّل `portal/.env` لضبط MikroTik وبيانات الشركة (LANDING_* / BILLING_*).

## محتوى الموقع (CMS على السيرفر)

- القراءة العامة: `GET /api/site/config`
- الحفظ من `/admin` (كلمة المرور في الهيدر `X-Site-Admin-Password`)
- الطلبات: `POST /api/site/leads` — تظهر في `/admin/leads`
- Vite يوجّه `/api` إلى Laravel على المنفذ 8000

لوحة التحكم: http://127.0.0.1:5173/admin (الافتراضي `maddadmin`)

## بوابة المشترك (ويب + API للتطبيق)

- ويب: http://127.0.0.1:8000/my/login  
- بعد الدخول: حسابي، الفواتير، الاستهلاك، الباقات، طلب تمديد  
- API (Sanctum) لتطبيق الجوال لاحقاً:

| Method | Path |
|--------|------|
| POST | `/api/login` — `phone` أو `login` أو `username` + `password` |
| GET | `/api/customer/dashboard` — كل شيء دفعة واحدة |
| GET | `/api/customer/invoices` |
| GET | `/api/customer/usage` |
| GET | `/api/customer/plans` |
| POST | `/api/customer/renewal-request` — تمديد ذاتي إن أمكن، أو طلب شركة |
| POST | `/api/customer/self-extend` — تمديد ذاتي صريح |

**تمديد ذاتي (بدون موافقة):** يظهر فقط بعد انتهاء الاشتراك — أول مرة **3 أيام**، ثاني مرة **يومين**. بعدها يلزم التجديد عبر الشركة/الوكيل. يُصفَّر العداد عند تجديد الإدارة أو الوكيل.

كلمة المرور الافتراضية للمشتركين: `100200300` (من `SUBSCRIBER_PORTAL_DEFAULT_PASSWORD`).

زر **تسجيل الدخول** في موقع Vite يوجّه إلى `/my/login`.
دخول الموظفين/الوكلاء يبقى على `/login`.

---

## النشر على سيرفر مدد (إنتاج)

انظر الدليل الكامل: [`deploy/DEPLOYMENT.md`](deploy/DEPLOYMENT.md)

- التطبيق يستمع على **`127.0.0.1:8080` فقط**
- Nginx على السيرفر يملك **80/443** ويمرّر `madd.ps` عبر Cloudflare
- لا تلمس UISP / GenieACS / شبكات Docker الحالية

---

## الصفحات (الواجهة)

### أفراد
- `/` الرئيسية
- `/login` → تحويل إلى بوابة Laravel
- `/plans` الباقات
- `/programming` البرمجة
- `/offers` العروض
- `/coverage` التغطية
- `/support` الدعم
- `/order` تقديم طلب واي فايبر

### أعمال
- `/business` وما يتفرع عنها

### إدارة محتوى الموقع (رابط سري)
- `/admin` — كلمة المرور الافتراضية: `maddadmin`  
  (شعار، سلايدر، قوائم…) يُحفظ في `localStorage`

## ملاحظة عن مجلد `api/`

مجلد `api/` PHP بسيط كان للتجربة. **تسجيل الدخول الفعلي** يتم عبر `portal/` (Sanctum + جلسات الويب).
