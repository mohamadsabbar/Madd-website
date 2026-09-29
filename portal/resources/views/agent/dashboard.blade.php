<x-layouts.agent-app :title="'الرئيسية'">
    {{-- 1) ترحيب + نسبة العمولة + اختصارات --}}
    <section class="agent-hero mb-4">
        <div class="agent-hero-inner">
            <div class="row align-items-center g-3">
                <div class="col-md-7">
                    <h2 class="agent-hero-title mb-0">مرحباً، {{ $agent->name }}</h2>
                </div>
                <div class="col-md-5">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end agent-hero-actions">
                        <a href="{{ route('web.agent.search') }}" class="btn btn-light btn-sm"><i class="bi bi-search ms-1"></i> بحث مشترك</a>
                        <a href="{{ route('web.agent.invoices') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-file-earmark-text ms-1"></i> الفواتير</a>
                        <a href="{{ route('web.agent.payments') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-cash-stack ms-1"></i> المدفوعات</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2) ملخص إجمالي (كل الفترات) --}}
    <h3 class="h6 fw-bold text-secondary mb-3 px-1">الإجمالي</h3>
    <div class="agent-kpi-grid mb-4">
        <div class="agent-kpi agent-kpi--indigo">
            <div class="agent-kpi-label">فواتير مدفوعة <span class="opacity-75">(إجمالي)</span></div>
            <div class="agent-kpi-value">{{ number_format($paidInvoicesTotal) }}</div>
            <div class="agent-kpi-hint">عمليات التحصيل: {{ number_format($paymentsCountTotal) }}</div>
        </div>
        <div class="agent-kpi agent-kpi--teal">
            <div class="agent-kpi-label">ما تم تحصيله <span class="opacity-75">(من العملاء)</span></div>
            <div class="agent-kpi-value">{{ number_format($totalCollected, 2) }} <small style="font-size:0.72rem;font-weight:700;color:#64748b;">شيكل</small></div>
            <div class="agent-kpi-hint">سجل التحصيل في النظام</div>
        </div>
        <div class="agent-kpi agent-kpi--amber">
            <div class="agent-kpi-label">العمولة التقديرية <span class="opacity-75">(إجمالي)</span></div>
            <div class="agent-kpi-value">{{ number_format($commissionEarnedTotal, 2) }} <small style="font-size:0.72rem;font-weight:700;color:#64748b;">شيكل</small></div>
            <div class="agent-kpi-hint">وفق نسبة العمولة المعتمدة</div>
        </div>
        <div class="agent-kpi agent-kpi--rose">
            <div class="agent-kpi-label">متبقي عمولة <span class="opacity-75">(غير مسددة)</span></div>
            <div class="agent-kpi-value">{{ number_format($commissionRemaining, 2) }} <small style="font-size:0.72rem;font-weight:700;color:#64748b;">شيكل</small></div>
            <div class="agent-kpi-hint">مسدّد مسجّل: {{ number_format($commissionPaid, 2) }} شيكل</div>
        </div>
    </div>

    {{-- 3) التسوية النقدية مع الشركة --}}
    <h3 class="h6 fw-bold text-secondary mb-3 px-1">التسوية مع الإدارة</h3>
    <div class="card border-0 mb-2 overflow-hidden" style="border-radius:22px;box-shadow:0 8px 32px rgba(15,23,42,.08);">
        <div class="card-body p-0">
            <div class="px-4 py-3 border-bottom bg-white">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-bank2 fs-5 text-secondary" aria-hidden="true"></i>
                    <span class="fw-bold" style="font-family:var(--agent-font-display);">الوضع النقدي مع الشركة</span>
                </div>
                <p class="mb-0 small text-secondary">ما استلمته من العملاء يُسجّل كمحصّل؛ المتبقي للتسليم يُحسب تلقائياً بعد خصم عمولتك (صافي حصة الشركة).</p>
            </div>
            <div class="row g-0">
                <div class="col-md-4 p-4 border-bottom border-md-bottom-0 border-md-end bg-white">
                    <div class="small text-secondary fw-bold mb-1">تم تسليمه للشركة</div>
                    <div class="fs-2 fw-bold text-dark" style="font-family:var(--agent-font-display);">{{ number_format($totalRemittedToCompany, 2) }} <span class="fs-6 text-muted fw-semibold">شيكل</span></div>
                </div>
                <div class="col-md-4 p-4 border-bottom border-md-bottom-0 border-md-end bg-white">
                    <div class="small fw-bold mb-1 text-secondary">متبقي للتسليم للإدارة (بعد خصم العمولة)</div>
                    <div class="fs-2 fw-bold {{ $pendingRemittanceToCompany > 0 ? 'text-dark' : 'text-secondary' }}" style="font-family:var(--agent-font-display);">{{ number_format($pendingRemittanceToCompany, 2) }} <span class="fs-6 text-muted fw-semibold">شيكل</span></div>
                    @if($pendingRemittanceToCompany > 0)
                        <div class="small mt-2 fw-semibold text-secondary"><i class="bi bi-exclamation-circle ms-1"></i>سلّم هذا المبلغ للمسؤول عند الترتيب.</div>
                    @else
                        <div class="small mt-2 fw-semibold text-secondary"><i class="bi bi-check-circle ms-1"></i>لا يوجد متبقي مسجّل للتسليم.</div>
                    @endif
                </div>
                <div class="col-md-4 p-4 bg-light">
                    <div class="small text-secondary fw-bold mb-1">صافي حصة الشركة (مرجع)</div>
                    <div class="fs-4 fw-bold text-dark" style="font-family:var(--agent-font-display);">{{ number_format($companyShareAfterCommission, 2) }} <span class="fs-6 text-muted">شيكل</span></div>
                    <div class="small text-muted mt-1">= إجمالي المحصّل {{ number_format($totalCollected, 2) }} - عمولة الوكيل {{ number_format($commissionEarnedTotal, 2) }}.</div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.agent-app>
