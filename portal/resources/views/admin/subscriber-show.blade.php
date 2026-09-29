@php
    $fmtMoney = fn (float $n): string => number_format($n, 2) . ' شيكل';
@endphp

<x-layouts.glass-app :title="'مشترك: ' . $subscriber->full_name">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('error') }}</div>
    @endif
    <div class="dg-greet mb-3">
        <nav class="mb-2" style="font-size:0.9rem;">
            <a href="{{ route('web.admin.subscribers') }}" class="text-decoration-none" style="color:var(--dg-muted);">المشتركين</a>
            <span class="text-muted mx-1">/</span>
            <span class="text-muted">تفاصيل</span>
        </nav>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h1 class="mb-1">{{ $subscriber->full_name }}</h1>
                <p class="mb-0 text-muted small">
                    <span class="me-3"><i class="bi bi-telephone ms-1"></i>{{ $subscriber->phone }}</span>
                    @if($subscriber->address)
                        <span><i class="bi bi-geo-alt ms-1"></i>{{ $subscriber->address }}</span>
                    @endif
                </p>
            </div>
            <div class="text-start d-flex flex-wrap gap-2 align-items-center justify-content-end">
                <a href="{{ route('web.admin.subscribers.edit', $subscriber) }}" class="btn btn-sm btn-outline-primary" style="border-radius:12px;"><i class="bi bi-pencil-square ms-1"></i> تعديل البيانات</a>
                <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:12px;" data-bs-toggle="modal" data-bs-target="#deleteSubscriberModal"><i class="bi bi-trash3 ms-1"></i> حذف المشترك</button>
                @if($expired)
                    <span class="dg-badge dg-badge--wait">اشتراك منتهٍ</span>
                @elseif($subscriber->status === 'active')
                    <span class="dg-badge dg-badge--done">نشط</span>
                @else
                    <span class="dg-badge dg-badge--way">معلّق</span>
                @endif
                @if($subscriber->is_online)
                    <span class="dg-badge dg-badge--ppp-online"><i class="bi bi-wifi ms-1"></i>متصل PPPoE</span>
                @else
                    <span class="dg-badge dg-badge--ppp-offline"><i class="bi bi-wifi-off ms-1"></i>غير متصل PPPoE</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="dg-card h-100" style="border-radius:var(--dg-radius-sm);">
                <div class="small text-muted mb-1">الباقة والراوتر</div>
                <div class="fw-semibold">{{ $subscriber->plan?->name ?? '—' }}</div>
                @if($subscriber->plan)
                    <div class="small mt-2 text-muted">سعر الدورة: {{ $fmtMoney((float) $subscriber->amountForInvoicedPlan($subscriber->plan)) }}</div>
                    @if($subscriber->monthly_price !== null)
                        <div class="small mt-1">سعر الكتالوج للباقة: {{ $fmtMoney((float) $subscriber->plan->price) }}</div>
                    @endif
                @else
                    <div class="small mt-2 text-muted">السعر: —</div>
                @endif
                <div class="small mt-1">الراوتر: {{ $subscriber->router?->name ?? '—' }}</div>
                <div class="small mt-1">PPPoE: <code>{{ $subscriber->pppoe_username }}</code></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="dg-card h-100" style="border-radius:var(--dg-radius-sm);">
                <div class="small text-muted mb-1">فترة الاشتراك</div>
                <div class="small">البداية: {{ optional($subscriber->start_date)->format('Y-m-d') ?? '—' }}</div>
                <div class="small mt-1">الانتهاء: {{ optional($subscriber->end_date)->format('Y-m-d') ?? '—' }}</div>
                @if($subscriber->suspended_at)
                    <div class="small mt-2 text-danger">تعليق: {{ $subscriber->suspended_at->locale('ar')->translatedFormat('Y-m-d H:i') }}</div>
                @endif
                <div class="mt-3 pt-3 border-top" style="border-color:rgba(0,0,0,0.06)!important;">
                    <div class="small fw-semibold text-secondary mb-2">تمديد الاشتراك</div>
                    <form method="post" action="{{ route('web.admin.subscribers.extend', $subscriber) }}" class="d-flex flex-wrap align-items-end gap-2">
                        @csrf
                        <div>
                            <label for="extend-days" class="form-label small mb-1">عدد الأيام (من 3 إلى 365)</label>
                            <input id="extend-days" type="number" name="days" min="3" max="365" value="{{ old('days', 3) }}" required class="form-control form-control-sm" style="width:5.5rem;border-radius:10px;">
                        </div>
                        <button type="submit" class="btn btn-sm text-white fw-semibold border-0" style="background:var(--dg-accent-2);border-radius:10px;padding:0.4rem 0.85rem;">
                            <i class="bi bi-calendar-plus ms-1"></i> تمديد
                        </button>
                    </form>
                    @error('days')
                        <div class="small text-danger mt-2 mb-0">{{ $message }}</div>
                    @enderror
                    <p class="small text-muted mt-2 mb-0" style="font-size:0.72rem;line-height:1.4;">يُحسب من آخر تاريخ انتهاء (إن كان في المستقبل) أو من اليوم إن كان الاشتراك منتهيًا، ثم تُضاف الأيام. عند وجود رقم هاتف وإعدادات SMS، تُرسل للمشترك رسالة بأن الخط مُمدَّد بعدد الأيام المختار.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="dg-card h-100" style="border-radius:var(--dg-radius-sm); border-inline-start:4px solid var(--dg-danger);">
                <div class="small text-muted mb-1">إجمالي المستحقات (غير المدفوع)</div>
                <div class="fs-4 fw-bold" style="color:var(--dg-danger);">{{ $fmtMoney($summary['total_unpaid']) }}</div>
                <div class="small mt-2">{{ $summary['unpaid_count'] }} فاتورة مفتوحة</div>
                <div class="mt-3 pt-3 border-top" style="border-color:rgba(0,0,0,0.06)!important;">
                    <div class="small fw-semibold text-secondary mb-2">إضافة دين يدوي</div>
                    <form method="post" action="{{ route('web.admin.subscribers.debt.store', $subscriber) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-5">
                            <label for="debt-amount" class="form-label small mb-1">المبلغ (شيكل)</label>
                            <input id="debt-amount" type="number" name="amount" min="0.01" step="0.01" required class="form-control form-control-sm" style="border-radius:10px;" placeholder="500.00" value="{{ old('amount') }}">
                        </div>
                        <div class="col-7">
                            <label for="debt-notes" class="form-label small mb-1">ملاحظة (اختياري)</label>
                            <input id="debt-notes" type="text" name="notes" maxlength="500" class="form-control form-control-sm" style="border-radius:10px;" placeholder="مثال: ترحيل من النظام القديم" value="{{ old('notes') }}">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100 fw-semibold" style="border-radius:10px;">
                                <i class="bi bi-plus-circle ms-1"></i> تسجيل الدين كفاتورة
                            </button>
                        </div>
                    </form>
                    @error('amount')
                        <div class="small text-danger mt-2 mb-0">{{ $message }}</div>
                    @enderror
                    <p class="small text-muted mt-2 mb-0" style="font-size:0.72rem;line-height:1.4;">يُنشئ فاتورة غير مدفوعة بالمبلغ المحدد — يراها الوكيل عند التحصيل ويمكن سدادها كاملة أو جزئياً.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="dg-status-row mb-4">
        <div class="dg-status-card dg-status-card--orange">
            <div class="dg-status-title">دين متأخر (بعد الاستحقاق)</div>
            <div class="dg-status-val" style="font-size:1.25rem;">{{ $fmtMoney($summary['total_overdue']) }}</div>
            <div class="small text-muted mt-1">{{ $summary['overdue_count'] }} فاتورة</div>
        </div>
        <div class="dg-status-card dg-status-card--green">
            <div class="dg-status-title">إجمالي الدفعات المسجّلة</div>
            <div class="dg-status-val" style="font-size:1.25rem;">{{ $fmtMoney($summary['total_payments_recorded']) }}</div>
            <div class="small text-muted mt-1">{{ $payments->count() }} عملية دفع</div>
        </div>
        <div class="dg-status-card dg-status-card--blue">
            <div class="dg-status-title">فواتير مدفوعة (مجموع المبالغ)</div>
            <div class="dg-status-val" style="font-size:1.25rem;">{{ $fmtMoney($summary['total_invoiced_paid']) }}</div>
            <div class="small text-muted mt-1">{{ $summary['paid_invoice_count'] }} فاتورة</div>
        </div>
        <div class="dg-status-card dg-status-card--yellow">
            <div class="dg-status-title">كل الفواتير</div>
            <div class="dg-status-val">{{ $summary['invoice_count'] }}</div>
            @if($summary['cancelled_count'] > 0)
                <div class="small text-muted mt-1">منها ملغاة: {{ $summary['cancelled_count'] }}</div>
            @endif
        </div>
    </div>

    <div class="dg-card mb-4" style="border-radius:var(--dg-radius-sm);">
        <h2 class="h6 fw-bold mb-3">الفواتير</h2>
        <div class="table-responsive">
            <table class="dg-table">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>الباقة</th>
                        <th>المبلغ</th>
                        <th>الاستحقاق</th>
                        <th>الحالة</th>
                        <th>تاريخ الدفع</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        @php
                            $isOverdue = in_array($inv->status, ['unpaid', 'partial'], true) && $inv->due_date && $inv->due_date->lt($today);
                            $remaining = $inv->remainingBalance();
                        @endphp
                        <tr @class(['table-warning' => $isOverdue])>
                            <td><code class="small">{{ $inv->invoice_number }}</code></td>
                            <td>{{ $inv->plan?->name ?? '—' }}</td>
                            <td class="fw-semibold">
                                {{ $fmtMoney((float) $inv->amount) }}
                                @if($inv->status === 'partial')
                                    <div class="small text-danger fw-normal">متبقٍ: {{ $fmtMoney($remaining) }}</div>
                                @endif
                            </td>
                            <td>{{ optional($inv->due_date)->format('Y-m-d') }}</td>
                            <td>
                                @if($inv->status === 'paid')
                                    <span class="dg-badge dg-badge--done">مدفوعة</span>
                                @elseif($inv->status === 'cancelled')
                                    <span class="dg-badge dg-badge--way">ملغاة</span>
                                @elseif($inv->status === 'partial')
                                    <span class="dg-badge dg-badge--way">جزئي</span>
                                    @if($isOverdue)
                                        <span class="small text-danger d-block mt-1">متأخرة</span>
                                    @endif
                                @else
                                    <span class="dg-badge dg-badge--wait">غير مدفوعة</span>
                                    @if($isOverdue)
                                        <span class="small text-danger d-block mt-1">متأخرة</span>
                                    @endif
                                @endif
                            </td>
                            <td>{{ optional($inv->paid_at)->format('Y-m-d') ?? '—' }}</td>
                            <td class="small text-muted">{{ Str::limit($inv->notes ?? '—', 40) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">لا توجد فواتير لهذا المشترك بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="dg-card mb-4" style="border-radius:var(--dg-radius-sm);">
        <h2 class="h6 fw-bold mb-3">الدفعات السابقة</h2>
        <div class="table-responsive">
            <table class="dg-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>المبلغ</th>
                        <th>الطريقة</th>
                        <th>الفاتورة</th>
                        <th>المحصّل</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                        <tr>
                            <td>{{ $p->paid_at?->locale('ar')->translatedFormat('Y-m-d H:i') ?? '—' }}</td>
                            <td class="fw-semibold">{{ $fmtMoney((float) $p->amount) }}</td>
                            <td>{{ $p->method ?? '—' }}</td>
                            <td><code class="small">{{ $p->invoice?->invoice_number ?? '—' }}</code></td>
                            <td>{{ $p->collector?->name ?? '—' }}</td>
                            <td class="small text-muted">{{ Str::limit($p->notes ?? '—', 36) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">لا توجد دفعات مسجّلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="small text-muted mb-0">
        إنشاء فواتير أو تسجيل دفع من واجهة الـ API أو من تطبيق الوكيل حسب إعداداتك. هذه الصفحة للعرض والمتابعة فقط.
    </p>

    <div class="modal fade" id="deleteSubscriberModal" tabindex="-1" aria-labelledby="deleteSubscriberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="{{ route('web.admin.subscribers.destroy', $subscriber) }}" class="modal-content" style="border-radius:16px; border:0; box-shadow:0 12px 40px rgba(0,0,0,0.18);">
                @csrf
                @method('DELETE')
                <div class="modal-header border-0 pb-0">
                    <h2 class="h5 fw-bold mb-0" id="deleteSubscriberModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-danger ms-1"></i>
                        حذف المشترك
                    </h2>
                    <button type="button" class="btn-close ms-auto me-0" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        أنت على وشك حذف المشترك
                        <strong>{{ $subscriber->full_name }}</strong>
                        (PPPoE: <code>{{ $subscriber->pppoe_username }}</code>).
                        هذا الإجراء <span class="text-danger fw-bold">لا يمكن التراجع عنه</span>،
                        وسيؤدي إلى حذف فواتيره ودفعاته وحساب تطبيق العميل المرتبط به تلقائياً.
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-2">اختر وضع الحذف:</label>

                        <div class="form-check p-3 border rounded mb-2" style="border-radius:12px !important;">
                            <input class="form-check-input" type="radio" name="mode" id="mode_program" value="program" checked>
                            <label class="form-check-label w-100" for="mode_program">
                                <span class="fw-semibold d-block">حذف من البرنامج فقط</span>
                                <span class="small text-muted">
                                    يُحذف المشترك وفواتيره وحسابه من قاعدة البيانات،
                                    لكن يبقى سر PPPoE موجوداً على المايكروتيك (يمكن للعميل الاتصال).
                                </span>
                            </label>
                        </div>

                        <div class="form-check p-3 border rounded" style="border-color:rgba(220,53,69,0.35) !important; background:rgba(220,53,69,0.04); border-radius:12px !important;">
                            <input class="form-check-input" type="radio" name="mode" id="mode_all" value="all">
                            <label class="form-check-label w-100" for="mode_all">
                                <span class="fw-semibold d-block text-danger">حذف من البرنامج والمايكروتيك</span>
                                <span class="small text-muted">
                                    حذف كامل: يُفصل من الجلسة النشطة (إن وُجدت)
                                    ويُزال سر PPPoE من الراوتر،
                                    ثم يُحذف من قاعدة البيانات.
                                </span>
                            </label>
                        </div>

                        @error('mode')
                            <div class="small text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label for="confirm_username" class="form-label small fw-semibold mb-1">
                            للتأكيد، اكتب اسم PPPoE: <code>{{ $subscriber->pppoe_username }}</code>
                        </label>
                        <input type="text" name="confirm_username" id="confirm_username" class="form-control" autocomplete="off" required dir="ltr" style="border-radius:10px;">
                        @error('confirm_username')
                            <div class="small text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;">إلغاء</button>
                    <button type="submit" class="btn btn-danger fw-semibold" style="border-radius:10px;">
                        <i class="bi bi-trash3 ms-1"></i> تأكيد الحذف
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.glass-app>
