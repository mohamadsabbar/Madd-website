<x-layouts.glass-app :title="'تسوية نقد الوكيل: ' . $agent->name">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif

    <div class="dg-greet mb-4">
        <nav class="mb-2 small">
            <a href="{{ route('web.admin.agents') }}" class="text-decoration-none text-muted">الوكلاء</a>
            <span class="text-muted mx-1">/</span>
            <span class="text-secondary">تسوية نقد</span>
        </nav>
        <h1>تسوية مع الوكيل: {{ $agent->name }}</h1>
        <p class="mb-0">عند الضغط على «تسجيل التسوية الآن» سيتم تسوية كامل المتبقي تلقائياً وتصفير العدادات لبدء دورة جديدة.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
                <div class="card-body p-4">
                    <div class="small text-muted fw-semibold mb-1">محصّل من العملاء (سجل النظام)</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($totalCollected, 2) }} <span class="fs-6 text-muted">شيكل</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-white" style="border-radius:16px;">
                <div class="card-body p-4">
                    <div class="small text-secondary fw-semibold mb-1">تم تسليمه للشركة (مسجّل)</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($totalRemitted, 2) }} <span class="fs-6 text-muted">شيكل</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-white" style="border-radius:16px;">
                <div class="card-body p-4">
                    <div class="small text-secondary fw-semibold mb-1">متبقي للتسليم</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($pending, 2) }} <span class="fs-6 text-muted">شيكل</span></div>
                </div>
            </div>
        </div>
    </div>

    @if($pending > 0)
        <div class="dg-card mb-4 p-4" style="border-radius:16px;">
            <h2 class="h5 fw-bold mb-3">تسوية نقدية كاملة</h2>
            <form method="post" action="{{ route('web.admin.agents.settle.store', $agent) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-8">
                    <div class="form-label fw-semibold">المبلغ الذي سيتم تسويته الآن</div>
                    <div class="form-control form-control-lg bg-light" style="border-radius:12px;">{{ number_format($pending, 2) }} شيكل</div>
                    <div class="form-text">سيتم اعتماد هذا المبلغ بالكامل كتسوية نهائية للدورة الحالية.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">ملاحظات (اختياري)</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500" class="form-control" style="border-radius:12px;" placeholder="مثال: تسليم نقد يدوي — يوم …">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary w-100 btn-lg" style="border-radius:12px;">تسجيل التسوية الآن (تصفير العدادات)</button>
                </div>
            </form>
        </div>
    @else
        <div class="alert alert-info border-0 mb-4" style="border-radius:14px;">
            لا يوجد متبقي للتسليم حالياً (المحصّل يطابق ما تم تسليمه للشركة).
        </div>
    @endif

    <div class="dg-table-wrap">
        <div class="dg-greet mb-3">
            <h2 class="h5 fw-bold mb-1">سجل التسليمات</h2>
            <p class="small text-muted mb-0">آخر العمليات المسجّلة لهذا الوكيل.</p>
        </div>
        <div class="table-responsive">
            <table class="dg-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>المبلغ</th>
                        <th>سجّله</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settlements as $row)
                        <tr>
                            <td>{{ $row->created_at->format('Y-m-d H:i') }}</td>
                            <td class="fw-bold">{{ number_format((float) $row->amount, 2) }} شيكل</td>
                            <td>{{ $row->recorder?->name ?? '—' }}</td>
                            <td class="text-muted small">{{ $row->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">لا توجد تسليمات مسجّلة بعد</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.glass-app>
