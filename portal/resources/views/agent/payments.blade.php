<x-layouts.agent-app :title="'المدفوعات'">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:12px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:12px;">{{ session('error') }}</div>
    @endif

    <h2 class="agent-page-title">جميع المدفوعات</h2>
    <p class="agent-page-lead">كل عمليات التحصيل التي سجّلتها أنت كوكيل (نقدي أو غيره).</p>

    <div class="agent-summary-pill mb-4">
        <span class="small text-secondary fw-semibold">إجمالي ما تم تحصيله</span>
        <span class="agent-sum-xl">{{ number_format($totalCollected, 2) }}</span>
        <span class="fs-6 fw-semibold text-muted">شيكل</span>
    </div>

    <div class="agent-card">
        <div class="agent-card-header">سجل المدفوعات</div>
        <div class="table-responsive">
            <table class="table mb-0 agent-table">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>المشترك</th>
                        <th>المبلغ</th>
                        <th>الطريقة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                        <tr>
                            <td>
                                @if($p->invoice)
                                    <code>{{ $p->invoice->invoice_number }}</code>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $p->invoice?->subscriber?->full_name ?? '—' }}</td>
                            <td class="fw-bold">{{ number_format((float) $p->amount, 2) }} شيكل</td>
                            <td><span class="badge text-bg-light border">{{ $p->method ?? '—' }}</span></td>
                            <td>{{ optional($p->paid_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted fw-semibold">لا توجد مدفوعات مسجّلة بعد</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="card-footer bg-white border-top py-3">{{ $payments->links() }}</div>
        @endif
    </div>
</x-layouts.agent-app>
