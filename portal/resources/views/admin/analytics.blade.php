@php
    $fmtBytes = function (?float $b): string {
        $b = (float) ($b ?? 0);
        if ($b < 1024) {
            return round($b).' بايت';
        }
        if ($b < 1048576) {
            return round($b / 1024, 2).' ك.ب';
        }
        if ($b < 1073741824) {
            return round($b / 1048576, 2).' م.ب';
        }

        return round($b / 1073741824, 2).' ج.ب';
    };
    $fmtMbps = function (?int $bps): string {
        if ($bps === null || $bps <= 0) {
            return '—';
        }

        return number_format($bps / 1_000_000, 2).' Mbps';
    };
@endphp

<x-layouts.glass-app :title="'تحليلات الاستهلاك'" :autoRefreshSeconds="(int) config('mikrotik.admin_ui_refresh_seconds', 0)">
    @if(($snapshotsTotalInDb ?? 0) === 0)
        <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-radius:14px;">
            <strong class="d-block mb-2">لا توجد أي لقطات في قاعدة البيانات بعد — لذلك تبدو الصفحة فارغة.</strong>
            <p class="mb-2 small mb-0">نفّذ على الخادم (مسار المشروع):</p>
            <ol class="small mb-2">
                <li><code>php artisan migrate --force</code> — لتشغيل جدول اللقطات.</li>
                <li>تشغيل المزامنة: من لوحة «الراوترات» اضغط <strong>مزامنة</strong>، أو من الطرفية: <code>php artisan mikrotik:sync-sessions --secrets</code></li>
                <li>تفعيل الجدولة (مهم): في crontab شغّل كل دقيقة: <code>php artisan schedule:run</code> من مجلد المشروع — المزامنة مع المايكروتيك تُشغَّل تلقائياً حسب <code>MIKROTIK_SYNC_INTERVAL_MINUTES</code> (افتراضي كل دقيقة).</li>
                <li>يجب أن يكون هناك <strong>جلسات PPPoE نشطة</strong> على المايكروتيك وقت اللقطة (مشتركون متصلون).</li>
            </ol>
            <p class="small text-muted mb-0">إن ظهرت صفحة بيضاء بالكامل فتحقق من <code>storage/logs/laravel.log</code> ومن أن <code>APP_URL</code> يطابق رابط الموقع، ثم نفّذ <code>php artisan config:clear</code> و<code>php artisan route:clear</code> و<code>php artisan view:clear</code>.</p>
        </div>
    @elseif(!($hasMeaningfulDelta ?? false) && ($snapshotsCount ?? 0) > 0)
        <div class="alert alert-info border-0 shadow-sm mb-4" style="border-radius:14px;">
            <strong>لا يظهر استهلاك في الفترة المختارة</strong> رغم وجود لقطات — الجداول تعتمد على <em>فرق</em> العدادات بين اللقطات؛ إذا لم يتحرك الاستهلاك (جلسات خاملة، أو فترة قصيرة جداً) يبقى المجموع صفراً وهذا طبيعي.
            <p class="mb-0 mt-2 small text-muted">
                جرّب زيادة «آخر ساعات»، أو انتظر أثناء استخدام فعلي للإنترنت ثم حدّث الصفحة أو نفّذ <strong>مزامنة</strong> من <a href="{{ route('web.admin.routers') }}" class="alert-link">الراوترات</a>.
                @if(!filter_var(config('mikrotik.traffic_double_sample', true), FILTER_VALIDATE_BOOL))
                    التقييم المزدوج معطّل (<code>MIKROTIK_TRAFFIC_DOUBLE_SAMPLE=false</code>) — قد تحتاج دورة مزامنة إضافية لمقارنة العدادات.
                @endif
            </p>
        </div>
    @endif

    <div class="dg-greet mb-4">
        <h1>تحليلات الاستهلاك</h1>
        <p class="mb-3">البيانات مأخوذة من لقطات دورية لجلسات PPP النشطة على المايكروتيك وتُخزَّن محلياً — تبقى متاحة حتى مع إيقاف الراوتر لاحقاً. تُحسب السرعة التقديرية بين لقطتين متتاليتين (أو من الحقول الفورية إن وفّرها الراوتر).</p>

        <form method="get" action="{{ route('web.admin.analytics') }}" class="row g-2 align-items-end flex-wrap">
            <div class="col-auto">
                <label class="form-label small text-muted mb-0">الراوتر</label>
                <select name="router_id" class="form-select form-select-sm" style="min-width:200px;border-radius:12px;">
                    <option value="">الكل</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}" @selected($routerId === $r->id)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small text-muted mb-0">الفترة (ساعات)</label>
                <input type="number" name="hours" class="form-control form-control-sm" min="1" max="168" value="{{ $hours }}" style="width:100px;border-radius:12px;">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-dark btn-sm" style="border-radius:12px;">تطبيق</button>
            </div>
        </form>

        <p class="small text-muted mt-2 mb-0">
            لقطات في الفترة: <strong>{{ number_format($snapshotsCount) }}</strong>
            — إجمالي اللقطات في قاعدة البيانات: <strong>{{ number_format($snapshotsTotalInDb ?? 0) }}</strong>
            @if($oldestInRange)
                — أقدم لقطة في الفترة: {{ \Illuminate\Support\Carbon::parse($oldestInRange)->locale('ar')->diffForHumans() }}
            @endif
            — الاحتفاظ: {{ config('mikrotik.traffic_snapshots_retention_days') }} يوماً (تنظيف تلقائي ليلاً).
        </p>
    </div>

    @if(count($hourlyChart) > 0)
        <div class="dg-card mb-4">
            <strong class="d-block mb-3">إجمالي الباندويث المُقدَّر بالساعة (تنزيل + رفع)</strong>
            <div style="max-height:320px;">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="dg-card h-100">
                <strong class="d-block mb-2">أكثر استهلاكاً للباندويث (مجموع)</strong>
                <div class="table-responsive">
                    <table class="dg-table mb-0">
                        <thead>
                            <tr>
                                <th>المشترك</th>
                                <th class="text-end">الحجم</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topVolume as $row)
                                <tr>
                                    <td>{{ $row->full_name }}</td>
                                    <td class="text-end"><code>{{ $fmtBytes((float) $row->total_bytes) }}</code></td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center py-3">لا بيانات كافية بعد — شغّل المزامنة الدورية أو انتظر لقطات جديدة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="dg-card h-100">
                <strong class="d-block mb-2">أعلى سرعة تنزيل تقديرية (ذروة)</strong>
                <div class="table-responsive">
                    <table class="dg-table mb-0">
                        <thead>
                            <tr>
                                <th>المشترك</th>
                                <th class="text-end">الذروة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topPeakRx as $row)
                                <tr>
                                    <td>{{ $row->full_name }}</td>
                                    <td class="text-end">{{ $fmtMbps((int) $row->peak_rx_bps) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center py-3">لا بيانات — تحتاج لقطتين على الأقل لكل جلسة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="dg-card h-100">
                <strong class="d-block mb-2">أكثر تنزيلاً (حجم)</strong>
                <div class="table-responsive">
                    <table class="dg-table mb-0">
                        <thead>
                            <tr><th>المشترك</th><th class="text-end">تنزيل</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topDownload as $row)
                                <tr>
                                    <td>{{ $row->full_name }}</td>
                                    <td class="text-end"><code>{{ $fmtBytes((float) $row->total_rx) }}</code></td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center py-3">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="dg-card h-100">
                <strong class="d-block mb-2">أكثر رفعاً (حجم)</strong>
                <div class="table-responsive">
                    <table class="dg-table mb-0">
                        <thead>
                            <tr><th>المشترك</th><th class="text-end">رفع</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topUpload as $row)
                                <tr>
                                    <td>{{ $row->full_name }}</td>
                                    <td class="text-end"><code>{{ $fmtBytes((float) $row->total_tx) }}</code></td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center py-3">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="dg-card h-100">
                <strong class="d-block mb-2">أعلى سرعة رفع تقديرية (ذروة)</strong>
                <div class="table-responsive">
                    <table class="dg-table mb-0">
                        <thead>
                            <tr><th>المشترك</th><th class="text-end">الذروة</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topPeakTx as $row)
                                <tr>
                                    <td>{{ $row->full_name }}</td>
                                    <td class="text-end">{{ $fmtMbps((int) $row->peak_tx_bps) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center py-3">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="dg-card h-100">
                <strong class="d-block mb-2">أسماء PPP غير مربوطة بالنظام (استهلاك)</strong>
                <div class="table-responsive">
                    <table class="dg-table mb-0">
                        <thead>
                            <tr><th>اسم PPP</th><th class="text-end">الحجم</th></tr>
                        </thead>
                        <tbody>
                            @forelse($unmapped as $row)
                                <tr>
                                    <td><code>{{ $row->ppp_username }}</code></td>
                                    <td class="text-end">{{ $fmtBytes((float) $row->total_bytes) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center py-3">لا يوجد أو كلها مربوطة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(count($hourlyChart) > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const ctx = document.getElementById('hourlyChart');
                if (!ctx) return;
                const labels = @json(collect($hourlyChart)->pluck('label')->values());
                const data = @json(collect($hourlyChart)->pluck('bytes')->map(fn ($b) => round($b / 1048576, 4))->values());
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'م.ب تقريبية / ساعة',
                            data: data,
                            fill: true,
                            borderColor: '#2b5d66',
                            backgroundColor: 'rgba(43, 93, 102, 0.12)',
                            tension: 0.2
                        }]
                    },
                    options: {
                        plugins: { legend: { display: true, rtl: true } },
                        scales: {
                            y: { beginAtZero: true },
                            x: { ticks: { maxRotation: 45, minRotation: 45 } }
                        },
                        maintainAspectRatio: false
                    }
                });
            })();
        </script>
    @endif
</x-layouts.glass-app>
