<x-layouts.glass-app :title="'تذاكر الدعم'">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif

    <div class="dg-greet mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1>تذاكر الدعم</h1>
            <p class="mb-0 text-muted">طلبات الزبائن من تطبيق مدد.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @foreach(['' => 'الكل', 'open' => 'مفتوحة', 'answered' => 'تم الرد', 'closed' => 'مغلقة'] as $key => $label)
                <a href="{{ route('web.admin.tickets', $key !== '' ? ['status' => $key] : []) }}"
                   class="btn btn-sm {{ ($status ?? '') === $key || ($key === '' && empty($status)) ? 'btn-primary' : 'btn-outline-secondary' }}"
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
                    <th>الموضوع</th>
                    <th>المشترك</th>
                    <th>الحالة</th>
                    <th>الرسائل</th>
                    <th>آخر تحديث</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($tickets as $t)
                    <tr>
                        <td>{{ $t->id }}</td>
                        <td class="fw-semibold">{{ $t->subject }}</td>
                        <td>
                            {{ $t->subscriber?->full_name ?? '—' }}
                            <div class="small text-muted">{{ $t->subscriber?->phone }}</div>
                        </td>
                        <td>
                            @php
                                $badge = match($t->status) {
                                    'open' => 'warning',
                                    'answered' => 'info',
                                    'closed' => 'secondary',
                                    default => 'light',
                                };
                                $label = match($t->status) {
                                    'open' => 'مفتوحة',
                                    'answered' => 'تم الرد',
                                    'closed' => 'مغلقة',
                                    default => $t->status,
                                };
                            @endphp
                            <span class="badge text-bg-{{ $badge }}">{{ $label }}</span>
                        </td>
                        <td>{{ $t->messages_count }}</td>
                        <td class="small">{{ optional($t->updated_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('web.admin.tickets.show', $t) }}" class="btn btn-sm btn-outline-primary" style="border-radius:10px;">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">لا توجد تذاكر.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $tickets->links() }}</div>
    </div>
</x-layouts.glass-app>
