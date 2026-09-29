<x-layouts.glass-app :title="'الباقات'">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('error') }}</div>
    @endif

    <div class="dg-greet mb-4">
        <h1>الباقات</h1>
        <p class="mb-2">السرعات والأسعار ومدة الاشتراك. حقل <strong>ملف PPP</strong> يجب أن يطابق الاسم في <code>PPP → Profiles</code> على المايكروتيك (نفس القيمة في عمود profile لأسرار PPPoE).</p>
        @if($routers->isEmpty())
            <p class="small text-warning mb-0">لا يوجد راوتر نشط — أضف راوتراً من «MikroTik» لجلب أسماء الملفات.</p>
        @else
            <div class="d-flex flex-wrap align-items-end gap-2 mt-3">
                <div>
                    <label class="form-label small text-muted mb-1">راوتر لجلب أسماء الملفات</label>
                    <select id="profile-router" class="form-select form-select-sm" style="min-width:220px;border-radius:12px;">
                        @foreach($routers as $r)
                            <option
                                value="{{ $r->id }}"
                                data-fetch-url="{{ route('web.admin.routers.ppp-profiles', $r) }}"
                                data-create-url="{{ route('web.admin.routers.plans-from-profiles', $r) }}"
                            >{{ $r->name }} — {{ $r->host }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-fetch-profiles" style="border-radius:12px;">جلب أسماء الملفات</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-create-plans" style="border-radius:12px;">إنشاء باقات تلقائياً من الملفات</button>
            </div>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="auto-create-plans" checked>
                <label class="form-check-label small" for="auto-create-plans">بعد الجلب، إنشاء الباقات وربطها تلقائياً (نفس أسماء الملفات على الراوتر)</label>
            </div>
            <p id="profile-fetch-msg" class="small text-muted mt-2 mb-0"></p>
            <form method="post" id="form-plans-from-router" class="d-none" action="#">
                @csrf
            </form>
        @endif
    </div>

    <datalist id="mikrotik-profiles-datalist"></datalist>

    <div class="dg-table-wrap">
        <div class="table-responsive">
            <table class="dg-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>السرعة (Mbps)</th>
                        <th>السعر</th>
                        <th>المدة (يوم)</th>
                        <th>ملف PPP (profile)</th>
                        <th>نشط</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>
                                <form method="post" action="{{ route('web.admin.plans.update', $plan) }}" id="plan-form-{{ $plan->id }}"></form>
                                <input type="hidden" name="_token" form="plan-form-{{ $plan->id }}" value="{{ csrf_token() }}">
                                <input type="hidden" name="_method" form="plan-form-{{ $plan->id }}" value="PUT">
                                <input type="text" name="name" form="plan-form-{{ $plan->id }}" class="form-control form-control-sm" value="{{ old('name', $plan->name) }}" required style="border-radius:10px;">
                            </td>
                            <td>
                                <input type="number" name="speed_mbps" form="plan-form-{{ $plan->id }}" class="form-control form-control-sm" value="{{ old('speed_mbps', $plan->speed_mbps) }}" min="1" required style="border-radius:10px;">
                            </td>
                            <td>
                                <input type="number" name="price" form="plan-form-{{ $plan->id }}" class="form-control form-control-sm" value="{{ old('price', $plan->price) }}" min="0" step="0.01" required style="border-radius:10px;">
                            </td>
                            <td>
                                <input type="number" name="duration_days" form="plan-form-{{ $plan->id }}" class="form-control form-control-sm" value="{{ old('duration_days', $plan->duration_days) }}" min="1" required style="border-radius:10px;">
                            </td>
                            <td style="min-width:140px;">
                                <input type="text" name="mikrotik_profile" form="plan-form-{{ $plan->id }}" class="form-control form-control-sm" value="{{ old('mikrotik_profile', $plan->mikrotik_profile) }}" list="mikrotik-profiles-datalist" placeholder="نفس الاسم في الراوتر" style="border-radius:10px;" autocomplete="off">
                            </td>
                            <td>
                                <input class="form-check-input" type="checkbox" name="is_active" form="plan-form-{{ $plan->id }}" value="1" id="act{{ $plan->id }}" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                            </td>
                            <td>
                                <button type="submit" form="plan-form-{{ $plan->id }}" class="btn btn-sm btn-dark" style="border-radius:10px;">حفظ</button>
                            </td>
                            <td>
                                <form method="post" action="{{ route('web.admin.plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('حذف هذه الباقة؟ لا يمكن التراجع.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:10px;">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted fw-semibold">لا توجد باقات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @unless($routers->isEmpty())
        <script>
            (function () {
                const sel = document.getElementById('profile-router');
                const btnFetch = document.getElementById('btn-fetch-profiles');
                const btnCreate = document.getElementById('btn-create-plans');
                const autoCreate = document.getElementById('auto-create-plans');
                const datalist = document.getElementById('mikrotik-profiles-datalist');
                const msg = document.getElementById('profile-fetch-msg');
                const formCreate = document.getElementById('form-plans-from-router');
                if (!btnFetch || !sel || !datalist || !formCreate) return;

                function submitCreateFromSelected() {
                    const opt = sel.options[sel.selectedIndex];
                    const createUrl = opt && opt.getAttribute('data-create-url');
                    if (!createUrl) return;
                    formCreate.action = createUrl;
                    formCreate.submit();
                }

                btnCreate.addEventListener('click', function () {
                    msg.textContent = 'جاري إنشاء الباقات من الراوتر…';
                    submitCreateFromSelected();
                });

                btnFetch.addEventListener('click', async function () {
                    const opt = sel.options[sel.selectedIndex];
                    const url = opt && opt.getAttribute('data-fetch-url');
                    if (!url) return;
                    msg.textContent = 'جاري الجلب…';
                    try {
                        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || res.statusText);
                        datalist.innerHTML = '';
                        (data.profiles || []).forEach(function (name) {
                            const o = document.createElement('option');
                            o.value = name;
                            datalist.appendChild(o);
                        });
                        const n = (data.profiles || []).length;
                        if (autoCreate && autoCreate.checked) {
                            msg.textContent = 'تم جلب ' + n + ' ملفاً — جاري إنشاء الباقات تلقائياً…';
                            submitCreateFromSelected();
                            return;
                        }
                        msg.textContent = 'تم جلب ' + n + ' ملفاً — يمكنك تعديل الجدول يدوياً أو استخدام «إنشاء باقات تلقائياً من الملفات».';
                    } catch (e) {
                        msg.textContent = 'فشل الجلب: ' + (e.message || e);
                    }
                });
            })();
        </script>
    @endunless
</x-layouts.glass-app>
