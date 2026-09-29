<x-layouts.glass-app :title="'المشتركين'">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('error') }}</div>
    @endif

    <div class="dg-greet mb-4">
        <h1>المشتركين</h1>
        <p class="mb-2">إدارة الاشتراكات، البحث، والحالات. حالة <strong>«متصل PPPoE»</strong> تأتي من الراوتر (<code>/ppp/active</code>) بعد المزامنة — مجدول كل دقيقة أو يدوياً من <a href="{{ route('web.admin.routers') }}">الراوترات → مزامنة</a>. لتحديث الباقة من المايكروتيك: «مزامنة باقات المشتركين» من نفس الصفحة.</p>
    </div>

    @php
        $listQuery = fn (?string $filter = null) => array_filter([
            'q' => request('q'),
            'filter' => $filter,
        ], fn ($v) => $v !== null && $v !== '');
        $activeFilter = $activeFilter ?? null;
        $filterLabels = [
            'online' => 'متصل PPPoE',
            'offline' => 'غير متصل',
            'expired' => 'اشتراك منتهٍ',
        ];
    @endphp

    <div class="dg-status-row">
        <div class="dg-status-card dg-status-card--blue">
            <div class="dg-status-title">جدد (7 أيام)</div>
            <div class="dg-status-val">{{ $statNew }}</div>
        </div>
        <a href="{{ route('web.admin.subscribers', $listQuery('expired')) }}" class="dg-status-card dg-status-card--orange text-decoration-none @if($activeFilter === 'expired') ring-active @endif" style="color:inherit;">
            <div class="dg-status-title">منتهي / يحتاج تجديد</div>
            <div class="dg-status-val">{{ $statAwait }}</div>
        </a>
        <a href="{{ route('web.admin.subscribers', $listQuery('online')) }}" class="dg-status-card dg-status-card--yellow text-decoration-none @if($activeFilter === 'online') ring-active @endif" style="color:inherit;">
            <div class="dg-status-title">متصلون الآن</div>
            <div class="dg-status-val">{{ $statOnline }}</div>
        </a>
        <div class="dg-status-card dg-status-card--green">
            <div class="dg-status-title">اشتراك نشط</div>
            <div class="dg-status-val">{{ $statActive }}</div>
        </div>
    </div>

    <div class="dg-toolbar flex-wrap">
        <form action="{{ route('web.admin.subscribers') }}" method="get" class="dg-search-wrap" style="max-width:320px;">
            @if($activeFilter)
                <input type="hidden" name="filter" value="{{ $activeFilter }}">
            @endif
            <i class="bi bi-search dg-search-icon"></i>
            <input type="search" class="dg-search" name="q" placeholder="بحث بالاسم أو الهاتف أو PPPoE…" value="{{ request('q') }}">
        </form>
        <div class="dg-filters ms-auto d-flex flex-wrap align-items-center gap-2">
            <div class="dg-filter-pills btn-group" role="group" aria-label="فلترة المشتركين">
                <a href="{{ route('web.admin.subscribers', $listQuery(null)) }}" class="dg-btn text-decoration-none @if(!$activeFilter) dg-btn-primary @endif">الكل</a>
                <a href="{{ route('web.admin.subscribers', $listQuery('online')) }}" class="dg-btn text-decoration-none @if($activeFilter === 'online') dg-btn-primary @endif"><i class="bi bi-wifi ms-1"></i> متصل</a>
                <a href="{{ route('web.admin.subscribers', $listQuery('offline')) }}" class="dg-btn text-decoration-none @if($activeFilter === 'offline') dg-btn-primary @endif"><i class="bi bi-wifi-off ms-1"></i> غير متصل</a>
                <a href="{{ route('web.admin.subscribers', $listQuery('expired')) }}" class="dg-btn text-decoration-none @if($activeFilter === 'expired') dg-btn-primary @endif"><i class="bi bi-calendar-x ms-1"></i> منتهي</a>
            </div>
            <a href="{{ route('web.admin.subscribers.create') }}" class="dg-btn dg-btn-primary text-decoration-none"><i class="bi bi-person-plus-fill ms-1"></i> إضافة مشترك</a>
        </div>
    </div>

    @if(request('q') || $activeFilter)
        <div class="d-flex flex-wrap gap-2 mb-3">
            @if(request('q'))
                <span class="dg-chip">البحث: {{ request('q') }} <a href="{{ route('web.admin.subscribers', $listQuery($activeFilter)) }}" class="text-decoration-none text-secondary ms-1" aria-label="إزالة البحث">×</a></span>
            @endif
            @if($activeFilter)
                <span class="dg-chip">{{ $filterLabels[$activeFilter] ?? $activeFilter }} <a href="{{ route('web.admin.subscribers', $listQuery(null)) }}" class="text-decoration-none text-secondary ms-1" aria-label="إزالة الفلتر">×</a></span>
            @endif
        </div>
    @endif

    <div class="dg-table-wrap">
        <div class="table-responsive">
            <table class="dg-table">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" class="form-check-input" disabled aria-label="تحديد الكل"></th>
                        <th>رقم</th>
                        <th>المشترك</th>
                        <th>الباقة</th>
                        <th>السعر</th>
                        <th>الانتهاء</th>
                        <th>مستحقات غير مدفوعة</th>
                        <th>الحالة</th>
                        <th>PPPoE</th>
                        <th style="width:48px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $s)
                        @php
                            $expired = $s->end_date && $s->end_date->isPast();
                        @endphp
                        <tr>
                            <td><input type="checkbox" class="form-check-input" disabled></td>
                            <td class="fw-bold">#{{ $s->id }}</td>
                            <td>
                                <a href="{{ route('web.admin.subscribers.show', $s) }}" class="text-decoration-none fw-semibold" style="color:inherit;">{{ $s->full_name }}</a>
                                <div style="font-size:0.8rem;color:#64748b;">{{ $s->phone }}</div>
                            </td>
                            <td>{{ $s->plan?->name ?? '—' }}</td>
                            <td>{{ $s->plan ? number_format($s->plan->price, 2) . ' شيكل' : '—' }}</td>
                            <td>{{ optional($s->end_date)->format('Y-m-d') }}</td>
                            <td>
                                @php
                                    $due = (float) ($s->invoices_sum_amount ?? 0);
                                @endphp
                                @if($due > 0)
                                    <span class="fw-semibold text-dark">{{ number_format($due, 2) }}</span>
                                @else
                                    <span style="color:#64748b;font-size:0.85rem;">0</span>
                                @endif
                            </td>
                            <td>
                                @if($expired)
                                    <span class="dg-badge dg-badge--wait">منتهي</span>
                                @elseif($s->status === 'active')
                                    <span class="dg-badge dg-badge--done">نشط</span>
                                @else
                                    <span class="dg-badge dg-badge--way">معلق</span>
                                @endif
                            </td>
                            <td>
                                @if($s->is_online)
                                    <span class="dg-badge dg-badge--ppp-online" title="جلسة PPPoE نشطة على المايكروتيك (آخر مزامنة)"><i class="bi bi-wifi ms-1"></i>متصل</span>
                                @else
                                    <span class="dg-badge dg-badge--ppp-offline" title="لا توجد جلسة في آخر مزامنة"><i class="bi bi-wifi-off ms-1"></i>غير متصل</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('web.admin.subscribers.show', $s) }}" class="dg-icon-btn d-inline-flex align-items-center justify-content-center text-decoration-none" style="width:36px;height:36px;" title="تفاصيل وحساب" aria-label="تفاصيل"><i class="bi bi-person-lines-fill"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted fw-semibold">
                                @if($activeFilter || request('q'))
                                    لا يوجد مشتركون مطابقون للفلتر أو البحث الحالي.
                                @else
                                    لا يوجد مشتركون بعد.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subscribers->hasPages())
            <div class="dg-pagination">
                {{ $subscribers->links() }}
            </div>
        @endif
    </div>
</x-layouts.glass-app>
