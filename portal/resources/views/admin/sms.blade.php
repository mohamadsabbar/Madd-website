<x-layouts.glass-app :title="'إرسال رسائل SMS'">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('error') }}</div>
    @endif

    <div class="dg-greet mb-4">
        <h1>إرسال رسائل SMS</h1>
        <p class="mb-0 text-muted">إرسال عبر مزوّد الرسائل المعرّف في الإعدادات (<code>BILLING_SMS_*</code>). يمكن تضمين <code>{name}</code> و<code>{phone}</code> و<code>{id}</code> في النص ليُستبدل لكل مشترك.</p>
    </div>

    @if(!($smsConfigured ?? false))
        <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-radius:14px;">
            <strong>الإرسال الفعلي غير جاهز.</strong>
            يجب أن يكون: <code>BILLING_SMS_ENABLED=true</code> و<code>BILLING_SMS_API_URL</code> و<code>BILLING_SMS_API_KEY</code> (قيمة <code>id</code> من HTD) و<code>BILLING_SMS_SENDER</code> (مثل مدد)، ثم <code>php artisan config:clear</code>. بدون ذلك لن يُرسل شيء.
        </div>
    @endif

    <div class="dg-card mb-4" style="border-radius:var(--dg-radius-sm);max-width:720px;">
        <form method="post" action="{{ route('web.admin.sms.send') }}" id="sms-form">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">المستلمون</label>
                <select name="audience" id="audience" class="form-select" style="border-radius:12px;" required>
                    <option value="all">كل المشتركين الذين لديهم رقم هاتف</option>
                    <option value="subscriber">مشترك محدد</option>
                    <option value="router">كل مشتركي راوتر معيّن</option>
                </select>
            </div>

            <div class="mb-3 d-none" id="wrap-subscriber">
                <label class="form-label">المشترك</label>
                <select name="subscriber_id" class="form-select" style="border-radius:12px;">
                    <option value="">— اختر —</option>
                    @foreach($subscribers as $s)
                        <option value="{{ $s->id }}">{{ $s->full_name }} — {{ $s->phone ?: 'بدون رقم' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3 d-none" id="wrap-router">
                <label class="form-label">الراوتر</label>
                <select name="router_id" class="form-select" style="border-radius:12px;">
                    <option value="">— اختر —</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">نص الرسالة</label>
                <textarea name="message" class="form-control" rows="5" required placeholder="مثال: مرحباً {name}، تذكير من شركة مدد…" style="border-radius:12px;"></textarea>
                <div class="form-text">الحد الأقصى 1000 حرفاً. الرسائل العربية قد تُحسب كعدة أجزاء عند المزوّد.</div>
            </div>

            <button type="submit" class="btn btn-primary" style="border-radius:12px;">
                <i class="bi bi-send-fill ms-1"></i> إرسال
            </button>
        </form>
    </div>

    <script>
(function () {
    var aud = document.getElementById('audience');
    var ws = document.getElementById('wrap-subscriber');
    var wr = document.getElementById('wrap-router');
    function sync() {
        var v = aud.value;
        ws.classList.toggle('d-none', v !== 'subscriber');
        wr.classList.toggle('d-none', v !== 'router');
        ws.querySelector('select').required = (v === 'subscriber');
        wr.querySelector('select').required = (v === 'router');
    }
    aud.addEventListener('change', sync);
    sync();
})();
    </script>
</x-layouts.glass-app>
