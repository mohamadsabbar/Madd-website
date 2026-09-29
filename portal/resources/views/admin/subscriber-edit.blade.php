<x-layouts.glass-app :title="'تعديل: ' . $subscriber->full_name">
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('error') }}</div>
    @endif

    <div class="dg-greet mb-4">
        <nav class="mb-2" style="font-size:0.9rem;">
            <a href="{{ route('web.admin.subscribers') }}" class="text-decoration-none" style="color:var(--dg-muted);">المشتركين</a>
            <span class="text-muted mx-1">/</span>
            <a href="{{ route('web.admin.subscribers.show', $subscriber) }}" class="text-decoration-none" style="color:var(--dg-muted);">تفاصيل</a>
            <span class="text-muted mx-1">/</span>
            <span class="text-muted">تعديل</span>
        </nav>
        <h1 class="mb-1">تعديل بيانات المشترك</h1>
        <p class="mb-0 text-muted small">اسم المستخدم PPPoE ثابت في النظام والراوتر؛ لتغييره عدّل على المايكروتيك ثم يدوياً في قاعدة البيانات إن لزم.</p>
    </div>

    <div class="dg-card" style="border-radius:var(--dg-radius-sm);max-width:640px;">
        <form method="post" action="{{ route('web.admin.subscribers.update', $subscriber) }}" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-12">
                <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" style="border-radius:12px;" value="{{ old('full_name', $subscriber->full_name) }}" required maxlength="255">
            </div>
            <div class="col-md-6">
                <label class="form-label">الهاتف <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" style="border-radius:12px;" value="{{ old('phone', $subscriber->phone) }}" required maxlength="50">
            </div>
            <div class="col-md-6">
                <label class="form-label">اسم مستخدم PPPoE</label>
                <input type="text" class="form-control" style="border-radius:12px;" value="{{ $subscriber->pppoe_username }}" disabled readonly>
            </div>
            <div class="col-12">
                <div class="rounded-3 border px-3 py-2 small" style="border-color: var(--bs-border-color-translucent) !important; background: rgba(0,0,0,.02);">
                    <strong>تطبيق العميل:</strong> يُنشأ تلقائياً بنفس <strong>رقم الهاتف</strong> أعلاه وكلمة المرور الافتراضية (انظر <code>config/subscriber_portal.php</code> أو <code>SUBSCRIBER_PORTAL_DEFAULT_PASSWORD</code> في <code>.env</code>).
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">العنوان</label>
                <input type="text" name="address" class="form-control" style="border-radius:12px;" value="{{ old('address', $subscriber->address) }}">
            </div>

            <div class="col-12">
                <label class="form-label">كلمة مرور PPPoE <span class="text-muted small">(اختياري)</span></label>
                <input type="text" name="pppoe_password" class="form-control" style="border-radius:12px;" value="" placeholder="اتركه فارغاً للإبقاء على كلمة المرور الحالية" autocomplete="new-password" maxlength="100">
            </div>

            <div class="col-md-6">
                <label class="form-label">الباقة <span class="text-danger">*</span></label>
                <select name="plan_id" class="form-select" style="border-radius:12px;" required>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" @selected(old('plan_id', $subscriber->plan_id) == $p->id)>{{ $p->name }} — {{ number_format($p->price, 2) }} شيكل</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">الراوتر <span class="text-danger">*</span></label>
                <select name="router_id" class="form-select" style="border-radius:12px;" required>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}" @selected(old('router_id', $subscriber->router_id) == $r->id)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">سعر الدورة للمشترك <span class="text-muted small">(اختياري)</span></label>
                <input type="number" name="monthly_price" class="form-control" style="border-radius:12px;" value="{{ old('monthly_price', $subscriber->monthly_price) }}" min="0" step="0.01" placeholder="فارغ = سعر الباقة من الكتالوج">
                <div class="form-text">عدّل القيمة متى شئت؛ اتركه فارغاً لاستخدام سعر الباقة الافتراضي.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">تاريخ بدء الاشتراك <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" style="border-radius:12px;" value="{{ old('start_date', $subscriber->start_date?->format('Y-m-d')) }}" required>
                @error('start_date')
                    <div class="small text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">
                    تاريخ انتهاء الاشتراك
                    <span class="text-muted small">(اختياري)</span>
                </label>
                <input type="date" name="end_date" class="form-control" style="border-radius:12px;" value="{{ old('end_date', $subscriber->end_date?->format('Y-m-d')) }}">
                <div class="form-text">
                    اتركه فارغاً لإعادة حسابه تلقائياً (اليوم {{ (int) config('mikrotik.subscription_cycle_end_day', 5) }} من الشهر التالي لشهر تاريخ البداية).
                    إذا كان التاريخ المعدّل في المستقبل وكان المشترك معلّقاً، يُعاد تفعيله تلقائياً على المايكروتيك.
                </div>
                @error('end_date')
                    <div class="small text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary" style="border-radius:12px;">حفظ التعديلات</button>
                <a href="{{ route('web.admin.subscribers.show', $subscriber) }}" class="btn btn-outline-secondary ms-2" style="border-radius:12px;">إلغاء</a>
            </div>
        </form>
    </div>
</x-layouts.glass-app>
