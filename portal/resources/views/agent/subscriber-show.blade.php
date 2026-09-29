@php
    $expired = $subscriber->end_date && $subscriber->end_date->isPast();
@endphp

<x-layouts.agent-app :title="'مشترك: ' . $subscriber->full_name">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:12px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:12px;">{{ session('error') }}</div>
    @endif

    <nav class="mb-3" style="font-size:0.9rem;">
        <a href="{{ route('web.agent.search', ['q' => request('back_q')]) }}" class="text-decoration-none text-secondary">← العودة للبحث</a>
    </nav>

    @if(($unpaidTotal ?? 0) > 0)
        <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-radius:12px;">
            <span class="fw-semibold"><i class="bi bi-exclamation-circle ms-1"></i> إجمالي المستحقات: {{ number_format((float) $unpaidTotal, 2) }} شيكل — {{ $unpaidInvoicesCount ?? 0 }} {{ ($unpaidInvoicesCount ?? 0) === 1 ? 'فاتورة' : 'فواتير' }} غير مدفوعة</span>
            <span class="small text-muted">كل شهر متأخر يظهر كفاتورة منفصلة في الجدول أدناه.</span>
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h2 class="agent-page-title mb-1">{{ $subscriber->full_name }}</h2>
            <p class="agent-page-lead mb-0">{{ $subscriber->phone }} @if($subscriber->pppoe_username) · <code>{{ $subscriber->pppoe_username }}</code>@endif</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($expired)
                <span class="badge rounded-pill text-bg-warning text-dark">اشتراك منتهٍ</span>
            @elseif($subscriber->status === 'active')
                <span class="badge rounded-pill text-bg-success">اشتراك نشط</span>
            @else
                <span class="badge rounded-pill text-bg-secondary">معلّق</span>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="agent-card h-100">
                <div class="card-body p-4">
                    <div class="small text-secondary fw-semibold mb-2">الباقة والتسعير</div>
                    <div class="fs-5 fw-bold">{{ $subscriber->plan?->name ?? '—' }}</div>
                    <div class="mt-2 text-muted">{{ $subscriber->plan ? number_format($subscriber->amountForInvoicedPlan($subscriber->plan), 2).' شيكل' : '—' }}</div>
                    <div class="small mt-3 text-muted">ينتهي الاشتراك: <strong class="text-dark">{{ optional($subscriber->end_date)->format('Y-m-d') ?? '—' }}</strong></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="agent-card h-100 border-primary border-2" style="border-style:dashed !important;">
                <div class="card-body p-4">
                    <div class="small text-secondary fw-semibold mb-2">تجديد الاشتراك</div>
                    <p class="small text-muted mb-3">يُنشئ فاتورة مدفوعة ودفعة باسمك، ويُمدّد تاريخ الانتهاء ويُفعّل المشترك على المايكروتيك.</p>
                    @if($subscriber->plan)
                        <form id="subscriberRenewForm" method="post" action="{{ route('web.agent.subscribers.renew', $subscriber) }}" class="d-none js-agent-renew-form" data-modal-id="agentRenewModalDetail" aria-hidden="true">
                            @csrf
                            <input type="hidden" name="payment_amount" id="subscriberRenewPaymentHidden" value="{{ number_format((float) $collectTotal, 2, '.', '') }}">
                        </form>
                        <button type="button" class="agent-btn-renew agent-btn-renew--lg w-100" data-bs-toggle="modal" data-bs-target="#agentRenewModalDetail">
                            <span class="agent-btn-renew__icon" aria-hidden="true"><i class="bi bi-arrow-repeat"></i></span>
                            <span class="agent-btn-renew__text">تجديد الآن</span>
                        </button>
                    @else
                        <div class="alert alert-warning mb-0 small">لا توجد باقة مرتبطة — لا يمكن التجديد من هنا.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="agent-card">
        <div class="agent-card-header">فواتير المشترك (آخر العمليات)</div>
        <div class="table-responsive">
            <table class="table mb-0 agent-table">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>الاستحقاق</th>
                        <th>الدفع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentInvoices as $inv)
                        @php
                            $st = $inv->status ?? 'unpaid';
                            $stLabel = match ($st) {
                                'paid' => 'مدفوعة',
                                'unpaid' => 'غير مدفوعة',
                                'partial' => 'جزئي',
                                default => $st,
                            };
                            $remaining = $inv->remainingBalance();
                        @endphp
                        <tr @class(['table-warning' => in_array($st, ['unpaid', 'partial'], true)])>
                            <td><code>{{ $inv->invoice_number }}</code></td>
                            <td class="fw-semibold">
                                {{ number_format((float) $inv->amount, 2) }} شيكل
                                @if($st === 'partial')
                                    <div class="small text-danger fw-normal">متبقٍ: {{ number_format($remaining, 2) }}</div>
                                @endif
                            </td>
                            <td>
                                @if($st === 'paid')
                                    <span class="badge text-bg-success">{{ $stLabel }}</span>
                                @elseif($st === 'partial')
                                    <span class="badge text-bg-warning text-dark">{{ $stLabel }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ $stLabel }}</span>
                                @endif
                            </td>
                            <td>{{ optional($inv->due_date)->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ optional($inv->paid_at)->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">لا توجد فواتير لهذا المشترك بعد</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($subscriber->plan)
        <div class="modal fade agent-renew-modal" id="agentRenewModalDetail" tabindex="-1" aria-labelledby="agentRenewModalDetailLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="agent-renew-modal__loading d-none" aria-hidden="true">
                        <div class="agent-renew-modal__loading-inner">
                            <div class="spinner-border" role="status" aria-hidden="true"></div>
                            <p class="agent-renew-modal__loading-text">جاري تنفيذ التجديد والدفع…</p>
                            <p class="small text-muted mb-0">يرجى عدم إغلاق الصفحة أو الضغط مرة أخرى</p>
                        </div>
                    </div>
                    <div class="modal-header">
                        <h2 class="modal-title" id="agentRenewModalDetailLabel">تأكيد التجديد والدفع</h2>
                        <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div class="agent-renew-modal__meta">
                            <div class="agent-renew-modal__name">{{ $subscriber->full_name }}</div>
                            <div class="agent-renew-modal__plan">{{ $subscriber->plan->name }}</div>
                        </div>
                        <div class="agent-renew-modal__price-wrap">
                            @if(($unpaidTotal ?? 0) > 0)
                                <div class="small text-secondary mb-2">تفصيل التحصيل عند التأكيد</div>
                                <ul class="list-unstyled small mb-3 text-start" style="line-height:1.85;">
                                    <li>فواتير الدورات غير المدفوعة ({{ $unpaidInvoicesCount ?? 0 }} {{ ($unpaidInvoicesCount ?? 0) === 1 ? 'فاتورة' : 'فواتير' }}): <strong class="text-dark">{{ number_format((float) $unpaidTotal, 2) }}</strong> شيكل</li>
                                    @if($needsSeparateRenewal ?? false)
                                        <li>دورة التجديد (الباقة الحالية): <strong class="text-dark">{{ number_format((float) $renewalCycleAmount, 2) }}</strong> شيكل</li>
                                    @endif
                                    <li class="pt-2 border-top mt-2">إجمالي يُسجَّل كمدفوع نقداً باسمك: <strong class="text-primary">{{ number_format((float) $collectTotal, 2) }}</strong> شيكل</li>
                                </ul>
                            @else
                                <div class="agent-renew-modal__price-label">مبلغ دورة التجديد</div>
                                <div class="agent-renew-modal__price-row">
                                    <span class="agent-renew-modal__price-num">{{ number_format((float) $renewalCycleAmount, 2) }}</span>
                                    <span class="agent-renew-modal__price-currency">شيكل</span>
                                </div>
                            @endif
                            <p class="agent-renew-modal__hint mb-0">
                                @if(($unpaidTotal ?? 0) > 0)
                                    يمكن دفع جزء من المستحقات. لإتمام التجديد والتمديد يُدفع الإجمالي كاملاً.
                                @else
                                    تُنشأ فاتورة التجديد كمدفوعة، وتُمدَّد فترة الاشتراك وفق الباقة الحالية.
                                @endif
                            </p>
                            <div class="mt-3 pt-3 border-top">
                                <label for="jsRenewPaymentAmountDetail" class="form-label small fw-semibold mb-1">مبلغ التحصيل اليوم (شيكل)</label>
                                <input type="number" id="jsRenewPaymentAmountDetail" class="form-control form-control-lg text-center fw-bold js-renew-payment-input" min="0.01" step="0.01" max="{{ number_format((float) $collectTotal, 2, '.', '') }}" value="{{ number_format((float) $collectTotal, 2, '.', '') }}" required style="border-radius:12px;" data-full-total="{{ number_format((float) $collectTotal, 2, '.', '') }}">
                                <div class="form-text small text-center mt-2">مثال: مستحق {{ number_format((float) $unpaidTotal, 2) }} — يمكن دفع 100 الآن.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-column border-0 pt-0">
                        <div class="d-flex flex-wrap justify-content-center gap-2 w-100">
                            <button type="button" class="btn btn-agent-cancel" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-agent-confirm js-agent-renew-submit" form="subscriberRenewForm" id="jsRenewSubmitDetail">
                                <span class="spinner-border spinner-border-sm agent-renew-submit-spinner" role="status" aria-hidden="true"></span>
                                <span class="agent-renew-submit-label"><i class="bi bi-check2-circle ms-1"></i> <span id="jsRenewSubmitLabelDetail">تأكيد التحصيل</span></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function () {
            var input = document.getElementById('jsRenewPaymentAmountDetail');
            var hidden = document.getElementById('subscriberRenewPaymentHidden');
            var label = document.getElementById('jsRenewSubmitLabelDetail');
            var form = document.getElementById('subscriberRenewForm');
            if (!input || !hidden || !form) return;
            var fullTotal = parseFloat(input.getAttribute('data-full-total') || '0') || 0;
            function sync() {
                hidden.value = input.value;
                if (label) {
                    var val = parseFloat(input.value) || 0;
                    label.textContent = (fullTotal > 0 && val >= fullTotal - 0.009) ? 'تأكيد التحصيل والتجديد' : 'تسجيل دفع جزئي';
                }
            }
            input.addEventListener('input', sync);
            form.addEventListener('submit', sync);
            sync();
        })();
        </script>
    @endif
</x-layouts.agent-app>
