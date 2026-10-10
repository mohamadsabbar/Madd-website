<x-layouts.glass-app :title="'تذكرة #'.$ticket->id">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('error') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('web.admin.tickets') }}" class="text-decoration-none">← رجوع للتذاكر</a>
    </div>

    <div class="dg-greet mb-4">
        <h1>{{ $ticket->subject }}</h1>
        <p class="mb-1 text-muted">
            {{ $ticket->subscriber?->full_name }} — {{ $ticket->subscriber?->phone }}
            @if($ticket->subscriber)
                · <a href="{{ route('web.admin.subscribers.show', $ticket->subscriber) }}">ملف المشترك</a>
            @endif
        </p>
        <p class="mb-0">
            الحالة:
            <strong>{{ match($ticket->status) { 'open' => 'مفتوحة', 'answered' => 'تم الرد', 'closed' => 'مغلقة', default => $ticket->status } }}</strong>
        </p>
    </div>

    <div class="dg-card mb-4 p-3" style="border-radius:var(--dg-radius-sm);">
        @foreach($ticket->messages as $m)
            <div class="mb-3 p-3 {{ $m->sender === 'staff' ? 'bg-primary-subtle' : 'bg-light' }}" style="border-radius:12px;">
                <div class="small text-muted mb-1">
                    {{ $m->sender === 'staff' ? 'فريق الدعم' : 'الزبون' }}
                    · {{ optional($m->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                </div>
                <div style="white-space:pre-wrap;">{{ $m->body }}</div>
            </div>
        @endforeach
    </div>

    @if($ticket->status !== 'closed')
        <div class="dg-card p-3 mb-3" style="border-radius:var(--dg-radius-sm);max-width:720px;">
            <form method="post" action="{{ route('web.admin.tickets.reply', $ticket) }}">
                @csrf
                <label class="form-label fw-semibold">رد الإدارة</label>
                <textarea name="body" class="form-control mb-3" rows="4" required style="border-radius:12px;"></textarea>
                <button type="submit" class="btn btn-primary" style="border-radius:12px;">إرسال الرد</button>
            </form>
        </div>
        <form method="post" action="{{ route('web.admin.tickets.close', $ticket) }}" onsubmit="return confirm('إغلاق التذكرة؟');">
            @csrf
            <button type="submit" class="btn btn-outline-secondary" style="border-radius:12px;">إغلاق التذكرة</button>
        </form>
    @else
        <form method="post" action="{{ route('web.admin.tickets.reopen', $ticket) }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary" style="border-radius:12px;">إعادة فتح</button>
        </form>
    @endif
</x-layouts.glass-app>
