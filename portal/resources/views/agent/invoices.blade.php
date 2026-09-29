<x-layouts.agent-app :title="'الفواتير'">
    <h2 class="agent-page-title">الفواتير المرتبطة بك</h2>
    <p class="agent-page-lead">فواتير أنشأتها أو تم تحصيلها بواسطتك (دفعة مسجّلة باسمك).</p>

    <div class="agent-card">
        <div class="agent-card-header">قائمة الفواتير</div>
        <div class="table-responsive">
            <table class="table mb-0 agent-table">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>المشترك</th>
                        <th>الباقة</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>الاستحقاق</th>
                        <th>الدفع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        @php
                            $status = $inv->status ?? 'unpaid';
                            $statusLabel = match ($status) {
                                'paid' => 'مدفوعة',
                                'unpaid' => 'غير مدفوعة',
                                'partial' => 'جزئي',
                                default => $status,
                            };
                        @endphp
                        <tr>
                            <td><code>{{ $inv->invoice_number }}</code></td>
                            <td class="fw-semibold">{{ $inv->subscriber?->full_name ?? '—' }}</td>
                            <td>{{ $inv->plan?->name ?? '—' }}</td>
                            <td>{{ number_format((float) $inv->amount, 2) }} شيكل</td>
                            <td>
                                @if($status === 'paid')
                                    <span class="badge text-bg-success">{{ $statusLabel }}</span>
                                @elseif($status === 'partial')
                                    <span class="badge text-bg-warning text-dark">{{ $statusLabel }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ $statusLabel }}</span>
                                @endif
                            </td>
                            <td>{{ optional($inv->due_date)->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ optional($inv->paid_at)->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted fw-semibold">لا توجد فواتير بعد</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer bg-white border-top py-3">{{ $invoices->links() }}</div>
        @endif
    </div>
</x-layouts.agent-app>
