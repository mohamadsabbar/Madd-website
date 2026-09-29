@php
    $typeLabels = [
        'subscriber.created' => 'إضافة مشترك',
        'subscriber.updated' => 'تعديل مشترك',
        'subscriber.extended' => 'تمديد اشتراك',
        'subscriber.deleted' => 'حذف مشترك',
        'subscriber.suspended' => 'تعليق يدوي',
        'subscriber.reconnected' => 'إعادة وصل',
        'subscriber.renewed' => 'تجديد (إدارة)',
        'subscriber.auto_suspended_expired' => 'تعليق تلقائي (انتهاء)',
        'subscriber.auto_suspended_unpaid' => 'قطع تلقائي (مستحقات)',
        'subscription.renewed_by_agent' => 'تجديد عبر وكيل',
        'payment.received' => 'دفعة',
        'sms.bulk' => 'SMS جماعي',
        'sms.reminder_batch' => 'تذكيرات SMS',
        'sms.expiry_reminder_batch' => 'تذكيرات قرب الانتهاء',
        'system.auto_suspend_expired' => 'مهمة: تعليق منتهين',
        'system.auto_suspend_unpaid' => 'مهمة: قطع غير مدفوعين',
    ];

    $categoryLabels = [
        'subscriber' => 'المشتركين',
        'subscription' => 'الاشتراكات',
        'payment' => 'الدفعات',
        'sms' => 'الرسائل',
        'system' => 'مهام مجدولة',
    ];

    $severityLabels = [
        'success' => 'نجاح',
        'info' => 'معلومات',
        'warning' => 'تنبيه',
        'error' => 'خطأ',
    ];

    $iconFor = fn (string $type) => [
        'subscriber.created' => 'bi-person-plus-fill',
        'subscriber.updated' => 'bi-pencil-square',
        'subscriber.extended' => 'bi-calendar-plus',
        'subscriber.deleted' => 'bi-trash3',
        'subscriber.suspended' => 'bi-pause-circle',
        'subscriber.reconnected' => 'bi-arrow-clockwise',
        'subscriber.renewed' => 'bi-arrow-repeat',
        'subscriber.auto_suspended_expired' => 'bi-clock-history',
        'subscriber.auto_suspended_unpaid' => 'bi-cash-stack',
        'subscription.renewed_by_agent' => 'bi-person-badge',
        'payment.received' => 'bi-cash-coin',
        'sms.bulk' => 'bi-send-fill',
        'sms.reminder_batch' => 'bi-bell-fill',
        'sms.expiry_reminder_batch' => 'bi-hourglass-split',
        'system.auto_suspend_expired' => 'bi-gear',
        'system.auto_suspend_unpaid' => 'bi-gear',
    ][$type] ?? 'bi-circle';
@endphp

<x-layouts.glass-app :title="'سجل العمليات'">
    <div class="dg-greet mb-3">
        <h1>سجل العمليات الداخلية</h1>
        <p class="mb-0 text-muted small">
            متابعة كل ما يحدث في النظام: الدفعات، التعليق، التمديد، الحذف، رسائل SMS (جماعية والتذكيرات التلقائية).
            يُسجَّل كل حدث مع وقته، صاحبه، والمشترك المتأثّر — للمراقبة والتدقيق.
        </p>
    </div>

    <div class="dg-status-row mb-3">
        <div class="dg-status-card dg-status-card--green">
            <div class="dg-status-title">دفعات اليوم</div>
            <div class="dg-status-val">{{ number_format($stats['today_payments'], 2) }} ش</div>
            <div class="small text-muted mt-1">{{ $stats['today_payment_count'] }} عملية</div>
        </div>
        <div class="dg-status-card dg-status-card--orange">
            <div class="dg-status-title">تعليقات اليوم</div>
            <div class="dg-status-val">{{ $stats['today_suspended'] }}</div>
            <div class="small text-muted mt-1">يدوي + تلقائي</div>
        </div>
        <div class="dg-status-card dg-status-card--blue">
            <div class="dg-status-title">SMS أُرسلت اليوم</div>
            <div class="dg-status-val">{{ $stats['today_sms_sent'] }}</div>
            <div class="small text-muted mt-1">
                @if($stats['today_sms_failed'] > 0)
                    <span class="text-danger">فشل: {{ $stats['today_sms_failed'] }}</span>
                @else
                    لا فشل
                @endif
            </div>
        </div>
        <div class="dg-status-card dg-status-card--yellow">
            <div class="dg-status-title">تمديدات اليوم</div>
            <div class="dg-status-val">{{ $stats['today_extended'] }}</div>
            <div class="small text-muted mt-1">إجمالي اليوم: {{ $stats['today_total'] }}</div>
        </div>
    </div>

    <div class="dg-card mb-3" style="border-radius:var(--dg-radius-sm);">
        <form method="get" action="{{ route('web.admin.activity') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">الفئة</label>
                <select name="category" class="form-select form-select-sm" style="border-radius:10px;">
                    <option value="">— الكل —</option>
                    @foreach($categoryLabels as $key => $label)
                        <option value="{{ $key }}" @selected($filters['category'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">النوع التفصيلي</label>
                <select name="type" class="form-select form-select-sm" style="border-radius:10px;">
                    <option value="">— الكل —</option>
                    @foreach($availableTypes as $t)
                        <option value="{{ $t }}" @selected($filters['type'] === $t)>{{ $typeLabels[$t] ?? $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الخطورة</label>
                <select name="severity" class="form-select form-select-sm" style="border-radius:10px;">
                    <option value="">— الكل —</option>
                    @foreach($severityLabels as $key => $label)
                        <option value="{{ $key }}" @selected($filters['severity'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">من تاريخ</label>
                <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm" style="border-radius:10px;">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm" style="border-radius:10px;">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">بحث (اسم/هاتف/نص)</label>
                <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" style="border-radius:10px;" placeholder="مثال: علي / 0599...">
            </div>
            <div class="col-12 d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-sm text-white fw-semibold border-0" style="background:var(--dg-accent-2);border-radius:10px;padding:0.4rem 1rem;">
                    <i class="bi bi-funnel ms-1"></i> تصفية
                </button>
                <a href="{{ route('web.admin.activity') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <div class="dg-card" style="border-radius:var(--dg-radius-sm);">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <h2 class="h6 fw-bold mb-0">آخر العمليات</h2>
            <span class="small text-muted">المجموع: {{ $logs->total() }}</span>
        </div>

        <div class="table-responsive">
            <table class="dg-table">
                <thead>
                    <tr>
                        <th style="width:140px;">الوقت</th>
                        <th style="width:160px;">النوع</th>
                        <th>التفاصيل</th>
                        <th style="width:140px;">المشترك</th>
                        <th style="width:120px;">المنفّذ</th>
                        <th style="width:100px;">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="small">
                                <div>{{ $log->created_at->locale('ar')->translatedFormat('Y-m-d') }}</div>
                                <div class="text-muted">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                            <td>
                                <span class="d-inline-flex align-items-center small">
                                    <i class="bi {{ $iconFor($log->type) }} ms-1"></i>
                                    {{ $typeLabels[$log->type] ?? $log->type }}
                                </span>
                            </td>
                            <td class="small">
                                <div>{{ $log->message }}</div>
                                @if($log->amount)
                                    <div class="small fw-semibold mt-1">المبلغ: {{ number_format((float) $log->amount, 2) }} شيكل</div>
                                @endif
                                @if(!empty($log->meta))
                                    <details class="mt-1">
                                        <summary class="small text-muted" style="cursor:pointer;">تفاصيل تقنية</summary>
                                        <pre class="small text-muted mb-0 mt-1" style="max-height:160px;overflow:auto;background:rgba(0,0,0,0.03);padding:6px;border-radius:6px;direction:ltr;text-align:left;">{{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @endif
                            </td>
                            <td class="small">
                                @if($log->subject_label)
                                    @if($log->subject_type === 'Subscriber' && $log->subject_id)
                                        <a href="{{ url('/admin/subscribers/'.$log->subject_id) }}" class="text-decoration-none" style="color:inherit;">
                                            {{ $log->subject_label }}
                                        </a>
                                    @else
                                        {{ $log->subject_label }}
                                    @endif
                                    @if($log->phone)
                                        <div class="text-muted">{{ $log->phone }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($log->actor_name)
                                    {{ $log->actor_name }}
                                    @if($log->actor_role)
                                        <div class="text-muted">{{ $log->actor_role }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">النظام</span>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $log->badge_class }}">{{ $severityLabels[$log->severity] ?? $log->severity }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">لا توجد عمليات مطابقة للفلاتر.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="dg-pagination mt-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</x-layouts.glass-app>
