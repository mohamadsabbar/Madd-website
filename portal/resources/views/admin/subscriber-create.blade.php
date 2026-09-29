<x-layouts.glass-app :title="'إضافة مشترك'">
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('error') }}</div>
    @endif

    <div class="dg-greet mb-4">
        <nav class="mb-2" style="font-size:0.9rem;">
            <a href="{{ route('web.admin.subscribers') }}" class="text-decoration-none" style="color:var(--dg-muted);">المشتركين</a>
            <span class="text-muted mx-1">/</span>
            <span class="text-muted">إضافة</span>
        </nav>
        <h1 class="mb-1">إضافة مشترك جديد</h1>
        <p class="mb-0 text-muted small">يُحفظ المشترك في النظام ويُنشأ له سر PPPoE على الراوتر المختار (حقل الباقة <code>mikrotik_profile</code> يجب أن يطابق الـ profile على المايكروتيك).</p>
    </div>

    @if($plans->isEmpty() || $routers->isEmpty())
        <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-radius:14px;">
            @if($plans->isEmpty())
                <strong>لا توجد باقات نشطة.</strong> أضف باقة من «الباقات» وفعّلها.
            @endif
            @if($routers->isEmpty())
                <strong>لا يوجد راوتر نشط.</strong> أضف راوتراً من «الراوترات» وفعّل الاتصال.
            @endif
        </div>
    @endif

    <div class="dg-card" style="border-radius:var(--dg-radius-sm);max-width:640px;">
        <form method="post" action="{{ route('web.admin.subscribers.store') }}" class="row g-3">
            @csrf

            <div class="col-12">
                <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" style="border-radius:12px;" value="{{ old('full_name') }}" required maxlength="255">
            </div>
            <div class="col-md-6">
                <label class="form-label">الهاتف <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" style="border-radius:12px;" value="{{ old('phone') }}" required maxlength="50">
            </div>
            <div class="col-md-6">
                <label class="form-label">تاريخ بدء الاشتراك <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" style="border-radius:12px;" value="{{ old('start_date', now()->toDateString()) }}" required>
                <div class="form-text">يُحسب تاريخ الانتهاء تلقائياً: اليوم {{ (int) config('mikrotik.subscription_cycle_end_day', 5) }} من الشهر التالي لشهر تاريخ البداية (مثال: بداية 5/3 → انتهاء 5/4).</div>
            </div>
            <div class="col-12">
                <label class="form-label">العنوان</label>
                <input type="text" name="address" class="form-control" style="border-radius:12px;" value="{{ old('address') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">اسم مستخدم PPPoE <span class="text-danger">*</span></label>
                <input type="text" name="pppoe_username" class="form-control" style="border-radius:12px;" value="{{ old('pppoe_username') }}" required autocomplete="off" maxlength="100">
                <div class="form-text">فريد في النظام، يُطابق اسم السر على المايكروتيك.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">كلمة مرور PPPoE <span class="text-danger">*</span></label>
                <input type="text" name="pppoe_password" class="form-control" style="border-radius:12px;" value="{{ old('pppoe_password') }}" required autocomplete="new-password" maxlength="100">
            </div>

            <div class="col-md-6">
                <label class="form-label">الباقة <span class="text-danger">*</span></label>
                <select name="plan_id" class="form-select" style="border-radius:12px;" required>
                    <option value="">— اختر —</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" @selected(old('plan_id') == $p->id)>{{ $p->name }} — {{ number_format($p->price, 2) }} شيكل</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">الراوتر <span class="text-danger">*</span></label>
                <select name="router_id" class="form-select" style="border-radius:12px;" required>
                    <option value="">— اختر —</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}" @selected(old('router_id') == $r->id)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">سعر الدورة للمشترك <span class="text-muted small">(اختياري)</span></label>
                <input type="number" name="monthly_price" class="form-control" style="border-radius:12px;" value="{{ old('monthly_price') }}" min="0" step="0.01" placeholder="فارغ = سعر الباقة من الكتالوج">
                <div class="form-text">يمكنك تغييره لاحقاً من تعديل المشترك. إن تركته فارغاً يُستخدم سعر الباقة الافتراضي.</div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary" style="border-radius:12px;" @disabled($plans->isEmpty() || $routers->isEmpty())>حفظ وإنشاء على المايكروتيك</button>
                <a href="{{ route('web.admin.subscribers') }}" class="btn btn-outline-secondary ms-2" style="border-radius:12px;">إلغاء</a>
            </div>
        </form>
    </div>
</x-layouts.glass-app>
