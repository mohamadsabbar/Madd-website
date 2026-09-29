<x-layouts.agent-app :title="'بحث مشترك'">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:12px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:12px;">{{ session('error') }}</div>
    @endif

    <h2 class="agent-page-title">بحث عن مشترك</h2>

    <div class="agent-card mb-4">
        <div class="card-body p-4">
            <form method="get" action="{{ route('web.agent.search') }}" class="row g-3 align-items-end">
                <div class="col-md-10">
                    <label class="form-label small fw-semibold text-secondary mb-2">نص البحث</label>
                    <input class="form-control form-control-lg agent-search-input" type="text" name="q" value="{{ $query }}" placeholder="اكتب للبحث…">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 btn-lg agent-btn-primary" type="submit">بحث</button>
                </div>
            </form>
        </div>
    </div>

    <div class="agent-card">
        <div class="agent-card-header">نتائج البحث</div>
        <div class="table-responsive">
            <table class="table mb-0 agent-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الهاتف</th>
                        <th>الباقة</th>
                        <th>السعر</th>
                        <th>تاريخ الانتهاء</th>
                        <th class="text-nowrap agent-th-actions" style="min-width:240px;">
                            <span class="d-inline-flex align-items-center justify-content-center gap-2">
                                <span>إجراءات</span>
                                <i class="bi bi-lightning-charge-fill text-secondary" style="font-size:0.95rem;opacity:.75;" aria-hidden="true"></i>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $s)
                        <tr>
                            <td class="fw-semibold">{{ $s->full_name }}</td>
                            <td>{{ $s->phone }}</td>
                            <td>{{ $s->plan?->name }}</td>
                            <td>{{ $s->plan ? number_format($s->amountForInvoicedPlan($s->plan), 2) : '—' }} شيكل</td>
                            <td>{{ optional($s->end_date)->format('Y-m-d') }}</td>
                            <td class="agent-table-actions">
                                <div class="agent-action-group">
                                    @if($s->plan)
                                        @php
                                            $unpaidRow = (float) ($s->invoices_sum_amount ?? 0);
                                            $unpaidCountRow = (int) ($s->unpaid_invoices_count ?? 0);
                                            $renewRow = (float) ($s->renewal_cycle_amount ?? 0);
                                            if ($renewRow <= 0 && $s->plan) {
                                                $renewRow = round((float) $s->amountForInvoicedPlan($s->plan), 2);
                                            }
                                            $totalRow = (float) ($s->collect_total ?? round($unpaidRow + ($s->needs_separate_renewal ?? false ? $renewRow : 0), 2));
                                            $needsRenewRow = (bool) ($s->needs_separate_renewal ?? ($unpaidRow <= 0));
                                        @endphp
                                        <button
                                            type="button"
                                            class="agent-btn-renew js-agent-renew-open"
                                            data-action="{{ route('web.agent.subscribers.renew', $s) }}"
                                            data-name="{{ e($s->full_name) }}"
                                            data-plan="{{ e($s->plan?->name ?? '—') }}"
                                            data-price="{{ number_format($needsRenewRow ? $renewRow : 0, 2) }}"
                                            data-unpaid="{{ number_format($unpaidRow, 2) }}"
                                            data-unpaid-count="{{ $unpaidCountRow }}"
                                            data-total="{{ number_format($totalRow, 2) }}"
                                            data-needs-renew="{{ $needsRenewRow ? '1' : '0' }}"
                                        >
                                            <span class="agent-btn-renew__icon" aria-hidden="true"><i class="bi bi-arrow-repeat"></i></span>
                                            <span class="agent-btn-renew__text">تجديد</span>
                                        </button>
                                        <a href="{{ route('web.agent.subscribers.show', ['subscriber' => $s, 'back_q' => $query]) }}" class="agent-btn-detail">
                                            <span class="agent-btn-detail__icon" aria-hidden="true"><i class="bi bi-person-lines-fill"></i></span>
                                            <span class="agent-btn-detail__text">تفاصيل</span>
                                        </a>
                                    @else
                                        <span class="agent-pill-muted">لا باقة</span>
                                        <a href="{{ route('web.agent.subscribers.show', ['subscriber' => $s, 'back_q' => $query]) }}" class="agent-btn-detail">
                                            <span class="agent-btn-detail__icon" aria-hidden="true"><i class="bi bi-person-lines-fill"></i></span>
                                            <span class="agent-btn-detail__text">تفاصيل</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted fw-semibold">
                                {{ $query ? 'لا توجد نتائج' : 'أدخل كلمة بحث للعرض' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade agent-renew-modal" id="agentRenewModalSearch" tabindex="-1" aria-labelledby="agentRenewModalSearchLabel" aria-hidden="true">
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
                    <h2 class="modal-title" id="agentRenewModalSearchLabel">تأكيد التجديد والدفع</h2>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="agent-renew-modal__meta">
                        <div class="agent-renew-modal__name" id="jsRenewModalName">—</div>
                        <div class="agent-renew-modal__plan" id="jsRenewModalPlan">—</div>
                    </div>
                    <div class="agent-renew-modal__price-wrap">
                        <div id="jsRenewModalBreakdown" class="d-none small text-start mb-3" style="line-height:1.85;">
                            <div class="text-secondary mb-1">تفصيل التحصيل عند التأكيد</div>
                            <div id="jsRenewModalUnpaidLine">مستحقات سابقة (<span id="jsRenewModalUnpaidCount">0</span> فاتورة): <strong id="jsRenewModalUnpaid">0.00</strong> شيكل</div>
                            <div id="jsRenewModalRenewLine" class="d-none">دورة التجديد: <strong id="jsRenewModalRenew">0.00</strong> شيكل</div>
                            <div class="pt-2 mt-2 border-top text-center fs-5 fw-bold">الإجمالي: <strong class="text-primary fs-4" id="jsRenewModalTotal">0.00</strong> شيكل</div>
                        </div>
                        <div id="jsRenewModalSimpleWrap">
                            <div class="agent-renew-modal__price-label">مبلغ دورة التجديد</div>
                            <div class="agent-renew-modal__price-row">
                                <span class="agent-renew-modal__price-num" id="jsRenewModalPrice">0.00</span>
                                <span class="agent-renew-modal__price-currency">شيكل</span>
                            </div>
                        </div>
                        <p class="agent-renew-modal__hint mb-0" id="jsRenewModalHint">يمكن دفع جزء من المستحقات. لإتمام التجديد والتمديد يُدفع الإجمالي كاملاً.</p>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <label for="jsRenewPaymentAmountSearch" class="form-label small fw-semibold mb-1">مبلغ التحصيل اليوم (شيكل)</label>
                        <input type="number" name="payment_amount" id="jsRenewPaymentAmountSearch" class="form-control form-control-lg text-center fw-bold" min="0.01" step="0.01" required form="agentRenewSearchForm" style="border-radius:12px;">
                        <div class="form-text small text-center mt-2">مثال: مستحق 500 — يمكن دفع 100 الآن والباقي لاحقاً.</div>
                    </div>
                </div>
                <div class="modal-footer flex-column border-0 pt-0">
                    <form id="agentRenewSearchForm" method="post" action="" class="js-agent-renew-form" data-modal-id="agentRenewModalSearch">
                        @csrf
                        <input type="hidden" name="from_search" value="1">
                        <input type="hidden" name="return_q" value="{{ $query }}">
                    </form>
                    <div class="d-flex flex-wrap justify-content-center gap-2 w-100">
                        <button type="button" class="btn btn-agent-cancel" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-agent-confirm js-agent-renew-submit" form="agentRenewSearchForm">
                            <span class="spinner-border spinner-border-sm agent-renew-submit-spinner" role="status" aria-hidden="true"></span>
                            <span class="agent-renew-submit-label"><i class="bi bi-check2-circle ms-1"></i> <span id="jsRenewSubmitLabelSearch">تأكيد التحصيل</span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
        (function () {
            var modalEl = document.getElementById('agentRenewModalSearch');
            var form = document.getElementById('agentRenewSearchForm');
            if (!modalEl || !form) return;

            var bsModal = typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalEl) : null;

            document.querySelectorAll('.js-agent-renew-open').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    form.setAttribute('action', btn.getAttribute('data-action') || '');
                    var nameEl = document.getElementById('jsRenewModalName');
                    var planEl = document.getElementById('jsRenewModalPlan');
                    var priceEl = document.getElementById('jsRenewModalPrice');
                    var breakdown = document.getElementById('jsRenewModalBreakdown');
                    var simpleWrap = document.getElementById('jsRenewModalSimpleWrap');
                    var unpaidEl = document.getElementById('jsRenewModalUnpaid');
                    var unpaidCountEl = document.getElementById('jsRenewModalUnpaidCount');
                    var renewLine = document.getElementById('jsRenewModalRenewLine');
                    var renewEl = document.getElementById('jsRenewModalRenew');
                    var totalEl = document.getElementById('jsRenewModalTotal');
                    var payInput = document.getElementById('jsRenewPaymentAmountSearch');
                    var submitLabel = document.getElementById('jsRenewSubmitLabelSearch');
                    if (nameEl) nameEl.textContent = btn.getAttribute('data-name') || '—';
                    if (planEl) planEl.textContent = btn.getAttribute('data-plan') || '—';
                    var unpaid = parseFloat(String(btn.getAttribute('data-unpaid') || '0').replace(/,/g, '')) || 0;
                    var unpaidCount = parseInt(btn.getAttribute('data-unpaid-count') || '0', 10) || 0;
                    var needsRenew = btn.getAttribute('data-needs-renew') === '1';
                    var collectTotal = parseFloat(String(btn.getAttribute('data-total') || '0').replace(/,/g, '')) || 0;
                    if (payInput) {
                        payInput.value = collectTotal > 0 ? collectTotal.toFixed(2) : '';
                        payInput.max = collectTotal > 0 ? collectTotal.toFixed(2) : '';
                    }
                    function refreshSubmitLabel() {
                        if (!payInput || !submitLabel) return;
                        var val = parseFloat(payInput.value) || 0;
                        var full = collectTotal > 0 && val >= collectTotal - 0.009;
                        submitLabel.textContent = full ? 'تأكيد التحصيل والتجديد' : 'تسجيل دفع جزئي';
                    }
                    if (payInput) {
                        payInput.oninput = refreshSubmitLabel;
                        refreshSubmitLabel();
                    }
                    if (unpaid > 0 && breakdown && simpleWrap && unpaidEl && totalEl) {
                        breakdown.classList.remove('d-none');
                        simpleWrap.classList.add('d-none');
                        if (unpaidCountEl) unpaidCountEl.textContent = String(unpaidCount > 0 ? unpaidCount : 1);
                        unpaidEl.textContent = btn.getAttribute('data-unpaid') || '0.00';
                        if (renewLine && renewEl) {
                            if (needsRenew) {
                                renewLine.classList.remove('d-none');
                                renewEl.textContent = btn.getAttribute('data-price') || '0.00';
                            } else {
                                renewLine.classList.add('d-none');
                            }
                        }
                        if (renewEl && needsRenew) renewEl.textContent = btn.getAttribute('data-price') || '0.00';
                        totalEl.textContent = btn.getAttribute('data-total') || '0.00';
                    } else {
                        if (breakdown) breakdown.classList.add('d-none');
                        if (simpleWrap) simpleWrap.classList.remove('d-none');
                        if (priceEl) priceEl.textContent = btn.getAttribute('data-price') || '0.00';
                    }
                    if (bsModal) bsModal.show();
                });
            });
        })();
        </script>
    </x-slot>
</x-layouts.agent-app>
