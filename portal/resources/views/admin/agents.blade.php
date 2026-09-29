<x-layouts.glass-app :title="'الوكلاء'">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif

    <div class="dg-greet mb-4">
        <h1>الوكلاء</h1>
        <p>حسابات التحصيل والصلاحيات. عمود <strong>متبقي للتسليم</strong> يوضح النقد الذي حصله الوكيل من العملاء ولم يُسلَّم بعد للإدارة — استخدم <strong>تسوية نقد</strong> عند استلام المبلغ فعلياً.</p>
    </div>

    <div class="dg-table-wrap">
        <div class="table-responsive">
            <table class="dg-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>البريد</th>
                        <th>الهاتف</th>
                        <th class="text-end">محصّل (عملاء)</th>
                        <th class="text-end">مُسلَّم للشركة</th>
                        <th class="text-end">متبقي للتسليم</th>
                        <th>الحالة</th>
                        <th style="width:120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                        @php
                            $collected = (float) ($agent->collections_sum_amount ?? 0);
                            $remitted = (float) ($agent->agent_settlements_sum_amount ?? 0);
                            $pend = round(max(0, $collected - $remitted), 2);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $agent->name }}</td>
                            <td>{{ $agent->email }}</td>
                            <td>{{ $agent->phone ?? '—' }}</td>
                            <td class="text-end">{{ number_format($collected, 2) }}</td>
                            <td class="text-end text-success">{{ number_format($remitted, 2) }}</td>
                            <td class="text-end fw-bold {{ $pend > 0 ? 'text-warning' : 'text-muted' }}">{{ number_format($pend, 2) }}</td>
                            <td>
                                @if($agent->is_active)
                                    <span class="dg-badge dg-badge--done">مفعّل</span>
                                @else
                                    <span class="dg-badge dg-badge--wait">معطّل</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('web.admin.agents.settle', $agent) }}" class="btn btn-sm btn-outline-primary" style="border-radius:10px;">تسوية نقد</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted fw-semibold">لا يوجد وكلاء</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($agents->hasPages())
            <div class="dg-pagination">
                {{ $agents->links() }}
            </div>
        @endif
    </div>
</x-layouts.glass-app>
