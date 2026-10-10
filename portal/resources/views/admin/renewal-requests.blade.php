<x-layouts.glass-app :title="'طلبات التجديد'">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif

    <div class="dg-greet mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1>طلبات التجديد من التطبيق</h1>
            <p class="mb-0 text-muted">طلبات الزبائن لتمديد أو تغيير الباقة.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @foreach(['pending' => 'معلّق', 'done' => 'منجز', 'rejected' => 'مرفوض', 'all' => 'الكل'] as $key => $label)
                <a href="{{ route('web.admin.renewals', ['status' => $key]) }}"
                   class="btn btn-sm {{ ($status ?? 'pending') === $key ? 'btn-primary' : 'btn-outline-secondary' }}"
                   style="border-radius:10px;">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="dg-card" style="border-radius:var(--dg-radius-sm);">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>المشترك</th>
                    <th>الباقة الحالية</th>
                    <th>ملاحظة</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>تحديث</th>
                </tr>
                </thead>
                <tbody>
                @forelse($requests as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>
                            @if($r->subscriber)
                                <a href="{{ route('web.admin.subscribers.show', $r->subscriber) }}">{{ $r->subscriber->full_name }}</a>
                                <div class="small text-muted">{{ $r->subscriber->phone }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $r->subscriber?->plan?->name ?? '—' }}</td>
                        <td class="small" style="max-width:220px;white-space:pre-wrap;">{{ $r->note ?: '—' }}</td>
                        <td>
                            @php
                                $badge = match($r->status) {
                                    'pending' => 'warning',
                                    'done' => 'success',
                                    'rejected' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge text-bg-{{ $badge }}">{{ $r->status }}</span>
                        </td>
                        <td class="small">{{ optional($r->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                        <td>
                            <form method="post" action="{{ route('web.admin.renewals.update', $r) }}" class="d-flex flex-column gap-1" style="min-width:160px;">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="form-select form-select-sm" style="border-radius:8px;">
                                    <option value="pending" @selected($r->status === 'pending')>معلّق</option>
                                    <option value="done" @selected($r->status === 'done')>منجز</option>
                                    <option value="rejected" @selected($r->status === 'rejected')>مرفوض</option>
                                </select>
                                <input type="text" name="admin_note" class="form-control form-control-sm" placeholder="ملاحظة إدارية" style="border-radius:8px;">
                                <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px;">حفظ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">لا توجد طلبات.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $requests->links() }}</div>
    </div>
</x-layouts.glass-app>
