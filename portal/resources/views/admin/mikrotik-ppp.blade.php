<x-layouts.glass-app :title="'PPPoE — '.$router->name">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('error') }}</div>
    @endif

    <div class="dg-greet mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="mb-1">تحليل أسرار PPPoE</h1>
            <p class="mb-0 text-muted">الراوتر: <strong>{{ $router->name }}</strong> — <code>{{ $router->host }}</code></p>
            <p class="small text-muted mb-0 mt-1">زر «مزامنة» في صفحة الراوترات يحدّث الجلسات ويملأ هذه الأرقام أيضاً. السرعة في النظام تأتي من الباقة المطابقة لحقل <strong>profile</strong> على المايكروتيك — عرّف في إدارة الباقات حقل «mikrotik_profile» بنفس الاسم الظاهر في الراوتر.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('web.admin.routers') }}" class="btn btn-outline-secondary" style="border-radius:12px;">← الراوترات</a>
            <form method="post" action="{{ route('web.admin.routers.ppp.sync-secrets', $router) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-dark" style="border-radius:12px;">جلب من الراوتر وحفظ</button>
            </form>
            <form method="post" action="{{ route('web.admin.routers.ppp.import-subscribers', $router) }}" class="d-inline" onsubmit="return confirm('إنشاء مشترك في النظام لكل سر PPP غير مربوط؟ (يُستورد الاسم من comment على المايكروتيك إن وُجد)');">
                @csrf
                <button type="submit" class="btn btn-primary" style="border-radius:12px;">استيراد كمشتركين</button>
            </form>
            <form method="post" action="{{ route('web.admin.routers.ppp.push-comments', $router) }}" class="d-inline" title="يضبط comment على حقل الاسم الكامل في النظام">
                @csrf
                <button type="submit" class="btn btn-outline-primary" style="border-radius:12px;">رفع الأسماء إلى المايكروتيك</button>
            </form>
            <form method="post" action="{{ route('web.admin.routers.sync-subscriber-plans', $router) }}" class="d-inline" title="تحديث plan_id لكل مشترك من profile السر على هذا الراوتر">
                @csrf
                <input type="hidden" name="return_ppp" value="1">
                <button type="submit" class="btn btn-success" style="border-radius:12px;">مزامنة باقات المشتركين</button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="dg-card h-100">
                <span class="small text-muted d-block">إجمالي الأسرار المحفوظة</span>
                <span class="fs-3 fw-bold">{{ number_format($stats['total']) }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dg-card h-100">
                <span class="small text-muted d-block">مرتبطة بمشترك في النظام</span>
                <span class="fs-3 fw-bold">{{ number_format($stats['linked']) }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dg-card h-100">
                <span class="small text-muted d-block">معطّلة على الراوتر</span>
                <span class="fs-3 fw-bold">{{ number_format($stats['disabled']) }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dg-card h-100">
                <span class="small text-muted d-block">آخر مزامنة للأسرار</span>
                <span class="fw-semibold">{{ $stats['last_sync'] ? \Illuminate\Support\Carbon::parse($stats['last_sync'])->locale('ar')->diffForHumans() : '—' }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="dg-card h-100">
                <strong class="d-block mb-3">توزيع حسب profile</strong>
                @if(count($chartLabels) > 0)
                    <div style="max-height:280px;">
                        <canvas id="profileChart" height="220"></canvas>
                    </div>
                @else
                    <p class="text-muted mb-0">لا توجد بيانات بعد — اضغط «جلب من الراوتر وحفظ».</p>
                @endif
            </div>
        </div>
        <div class="col-lg-7">
            <div class="dg-card h-100">
                <strong class="d-block mb-3">الأعداد حسب الملف الشخصي</strong>
                <div class="table-responsive">
                    <table class="dg-table mb-0">
                        <thead>
                            <tr>
                                <th>Profile</th>
                                <th class="text-end">العدد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byProfile as $row)
                                <tr>
                                    <td><code>{{ $row->profile ?? '—' }}</code></td>
                                    <td class="text-end fw-semibold">{{ number_format($row->c) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-muted text-center py-4">لا بيانات — نفّذ الجلب أولاً.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="dg-card">
        <strong class="d-block mb-3">كل الأسماء (من آخر جلب)</strong>
        <div class="table-responsive">
            <table class="dg-table">
                <thead>
                    <tr>
                        <th>اسم PPPoE</th>
                        <th>Profile</th>
                        <th>الخدمة</th>
                        <th>حساب الراوتر</th>
                        <th>متصل الآن</th>
                        <th>مشترك في النظام</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($secrets as $s)
                        <tr>
                            <td><code>{{ $s->ppp_name }}</code></td>
                            <td><code>{{ $s->profile ?? '—' }}</code></td>
                            <td>{{ $s->service ?? '—' }}</td>
                            <td>
                                @if($s->disabled)
                                    <span class="dg-badge dg-badge--wait" title="الحساب معطّل في PPP Secret">معطّل على الراوتر</span>
                                @else
                                    <span class="dg-badge dg-badge--done" title="الحساب غير معطّل — لا يعني أن الخط فعلياً يعمل">غير معطّل</span>
                                @endif
                            </td>
                            <td>
                                @if($s->subscriber)
                                    @if($s->subscriber->is_online)
                                        <span class="dg-badge dg-badge--ppp-online"><i class="bi bi-wifi ms-1"></i>نعم</span>
                                    @else
                                        <span class="dg-badge dg-badge--ppp-offline"><i class="bi bi-wifi-off ms-1"></i>لا</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($s->subscriber)
                                    <span class="small">{{ $s->subscriber->full_name }}</span>
                                    <span class="text-muted small d-block"><code>{{ $s->subscriber->pppoe_username }}</code></span>
                                    @if($s->subscriber->plan)
                                        <span class="text-muted small d-block">{{ $s->subscriber->plan->speed_mbps }} Mbps — {{ $s->subscriber->plan->name }}</span>
                                    @endif
                                @else
                                    <span class="text-muted small">غير مربوط — راجع الراوتر أو اسم المستخدم في النظام</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">لا صفوف — استخدم «جلب من الراوتر وحفظ».</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($secrets->hasPages())
            <div class="mt-3 d-flex justify-content-center">
                {{ $secrets->links() }}
            </div>
        @endif
    </div>

    @if(count($chartLabels) > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const ctx = document.getElementById('profileChart');
                if (!ctx) return;
                const labels = @json($chartLabels);
                const data = @json($chartData);
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: [
                                '#1a3f46', '#2b5d66', '#3a7580', '#5a8f98', '#64748b',
                                '#94a3b8', '#cbd5e1', '#e2e8f0', '#f1f5f9', '#1a3f46'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        plugins: { legend: { position: 'bottom', rtl: true } },
                        maintainAspectRatio: false
                    }
                });
            })();
        </script>
    @endif
</x-layouts.glass-app>
