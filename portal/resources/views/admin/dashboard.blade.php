<x-layouts.glass-app :title="'لوحة التحكم'" :autoRefreshSeconds="(int) config('mikrotik.admin_ui_refresh_seconds', 0)">
    <div class="dg-greet mb-4">
        <h1>مرحباً، {{ auth()->user()->name ?? 'مدير' }} 👋</h1>
        <p>نظرة عامة على الإيرادات والمشتركين والباقات — لوحة مدد.</p>
    </div>

    <div class="dg-kpi-grid mb-3">
        <div class="dg-kpi dg-kpi--purple">
            <div class="dg-kpi-label">إيراد الشهر</div>
            <div class="dg-kpi-value">{{ number_format($monthIncome, 2) }} <small style="font-size:0.65em;font-weight:700;opacity:.9">شيكل</small></div>
            @if($monthGrowthPct !== null)
                <span class="dg-kpi-trend dg-kpi-trend--up">
                    {{ $monthGrowthPct >= 0 ? '+' : '' }}{{ $monthGrowthPct }}%
                </span>
            @endif
            <span class="dg-kpi-arrow"><i class="bi bi-graph-up-arrow"></i></span>
        </div>
        <div class="dg-kpi dg-kpi--white">
            <div class="dg-kpi-label">إجمالي المشتركين</div>
            <div class="dg-kpi-value">{{ $subscribersCount }}</div>
            <span class="dg-kpi-trend dg-kpi-trend--up">نشطون في النظام</span>
            <span class="dg-kpi-arrow" style="color:#64748b;"><i class="bi bi-people"></i></span>
        </div>
        <div class="dg-kpi dg-kpi--white">
            <div class="dg-kpi-label">متصلون الآن</div>
            <div class="dg-kpi-value">{{ $onlineSubscribers }}</div>
            <span class="dg-kpi-trend dg-kpi-trend--up">PPPoE</span>
            <span class="dg-kpi-arrow" style="color:#64748b;"><i class="bi bi-wifi"></i></span>
        </div>
        <div class="dg-kpi dg-kpi--white">
            <div class="dg-kpi-label">دخل اليوم</div>
            <div class="dg-kpi-value">{{ number_format($todayIncome, 2) }} <small style="font-size:0.55em;font-weight:700;color:#64748b">شيكل</small></div>
            <span class="dg-kpi-trend {{ $todayIncome > 0 ? 'dg-kpi-trend--up' : '' }}" style="{{ $todayIncome <= 0 ? 'background:#f1f5f9;color:#64748b;' : '' }}">فواتير مدفوعة</span>
            <span class="dg-kpi-arrow" style="color:#64748b;"><i class="bi bi-currency-exchange"></i></span>
        </div>
    </div>

    <div class="dg-chart-row">
        <div class="dg-card dg-chart-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong style="font-size:0.95rem;">الإيراد اليومي (آخر 7 أيام)</strong>
            </div>
            <canvas id="dgBarChart" height="120" aria-label="رسم إيراد"></canvas>
        </div>
        <div class="dg-mini-grid">
            <div class="dg-mini">
                <div class="dg-mini-sub">اشتراكات</div>
                <div class="dg-mini-stat">{{ $statActive }} <span style="font-size:0.75rem;color:#64748b;font-weight:600;">مشترك نشط</span></div>
            </div>
            <div class="dg-mini">
                <div class="dg-mini-sub">منتهي / يحتاج تجديد</div>
                <div class="dg-mini-stat">{{ $expiredSubscribers }}</div>
                <svg class="dg-wave" viewBox="0 0 120 32" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill="none" stroke="#2b5d66" stroke-width="2.5" d="M0,24 C20,8 40,28 60,14 S100,4 120,20"/>
                </svg>
            </div>
            <div class="dg-mini">
                <div class="dg-mini-sub">توزيع المشتركين حسب الباقة</div>
                <div class="dg-donut-wrap">
                    <canvas id="dgDonut" width="180" height="180" aria-label="رسم دائري"></canvas>
                    <div class="dg-legend" id="dgDonutLegend"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const incomeByDay = @json($incomeByDay);
        const labels = incomeByDay.map(function (r) { return r.label; });
        const values = incomeByDay.map(function (r) { return r.value; });

        const barCtx = document.getElementById('dgBarChart');
        if (barCtx && typeof Chart !== 'undefined') {
            new Chart(barCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'شيكل',
                        data: values,
                        backgroundColor: '#2b5d66',
                        borderRadius: 8,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (v) { return v.toLocaleString('ar'); },
                            },
                        },
                    },
                },
            });
        }

        const segments = @json($donutSegments);
        const colors = ['#1a3f46', '#2b5d66', '#3a7580', '#5a8f98', '#64748b', '#94a3b8'];
        const donutCtx = document.getElementById('dgDonut');
        const legendEl = document.getElementById('dgDonutLegend');

        if (donutCtx && typeof Chart !== 'undefined' && segments.length) {
            new Chart(donutCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: segments.map(function (s) { return s.label; }),
                    datasets: [{
                        data: segments.map(function (s) { return s.value; }),
                        backgroundColor: colors.slice(0, segments.length),
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    cutout: '62%',
                    plugins: { legend: { display: false } },
                },
            });
            segments.forEach(function (s, i) {
                const row = document.createElement('div');
                row.className = 'dg-legend-item';
                row.innerHTML = '<span class="dg-legend-dot" style="background:' + colors[i % colors.length] + '"></span><span>' + s.label + '</span>';
                legendEl.appendChild(row);
            });
        } else if (legendEl) {
            legendEl.innerHTML = '<span style="font-size:0.85rem;font-weight:600;color:#64748b;">لا بيانات بعد</span>';
        }
    })();
    </script>
</x-layouts.glass-app>
