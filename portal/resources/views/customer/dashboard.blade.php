@extends('layouts.customer')

@section('title', 'حسابي')

@section('content')
<section class="cp-hero">
    <h1>مرحباً، {{ $account['full_name'] }}</h1>
    <p>
        {{ $service['name'] ?? 'بدون باقة' }}
        @if(!empty($service['speed_mbps'])) — {{ $service['speed_mbps'] }} Mbps @endif
    </p>
    <span class="cp-badge">
        <i class="bi bi-circle-fill" style="font-size:0.45rem"></i>
        {{ $account['status_label'] }}
        @if($account['is_online']) · متصل الآن @endif
    </span>
</section>

<div class="cp-grid cp-grid--2">
    <article class="cp-card">
        <h2>تفاصيل الحساب</h2>
        <div class="cp-meta">
            <div class="cp-meta__row"><span class="cp-meta__label">الجوال</span><span class="cp-meta__value">{{ $account['phone'] }}</span></div>
            <div class="cp-meta__row"><span class="cp-meta__label">اسم المستخدم</span><span class="cp-meta__value">{{ $account['pppoe_username'] }}</span></div>
            <div class="cp-meta__row"><span class="cp-meta__label">العنوان</span><span class="cp-meta__value">{{ $account['address'] ?: '—' }}</span></div>
            <div class="cp-meta__row"><span class="cp-meta__label">بداية الاشتراك</span><span class="cp-meta__value">{{ $account['start_date'] ?: '—' }}</span></div>
            <div class="cp-meta__row"><span class="cp-meta__label">ينتهي في</span><span class="cp-meta__value">{{ $account['end_date'] ?: '—' }}</span></div>
            <div class="cp-meta__row"><span class="cp-meta__label">الأيام المتبقية</span><span class="cp-meta__value">{{ $account['days_remaining'] }}</span></div>
        </div>
    </article>

    <article class="cp-card">
        <h2>الخدمة الحالية</h2>
            @if($service)
            <div class="cp-meta">
                <div class="cp-meta__row"><span class="cp-meta__label">الباقة</span><span class="cp-meta__value">{{ $service['name'] }}</span></div>
                <div class="cp-meta__row"><span class="cp-meta__label">السرعة</span><span class="cp-meta__value">{{ $service['speed_mbps'] }} Mbps</span></div>
                <div class="cp-meta__row"><span class="cp-meta__label">القيمة الشهرية</span><span class="cp-meta__value">₪ {{ $service['price'] }}</span></div>
            </div>

            @php $self = $renewal['self_extend'] ?? []; @endphp

            @if($renewal['pending_request'] ?? false)
                <div class="cp-alert cp-alert--info" style="margin-top:1rem;margin-bottom:0">طلب تجديد قيد المعالجة لدى الشركة</div>
            @elseif($renewal['show_extend_cta'] ?? false)
                <div class="cp-alert cp-alert--info" style="margin-top:1rem">{{ $self['message'] ?? '' }}</div>
                <form method="post" action="{{ route('web.customer.renewal') }}">
                    @csrf
                    <input type="hidden" name="action" value="self_extend">
                    <button class="cp-btn cp-btn--primary cp-btn--block" type="submit">
                        تمديد الآن {{ $self['next_days'] ?? '' }} أيام
                    </button>
                </form>
                <p style="margin:0.65rem 0 0;font-size:0.8rem;color:var(--cp-muted)">
                    التمديد فوري بدون موافقة — المرة {{ ($self['used_count'] ?? 0) + 1 }} من {{ $self['max_count'] ?? 2 }}
                </p>
            @elseif($renewal['show_company_request_cta'] ?? false)
                <div class="cp-alert cp-alert--err" style="margin-top:1rem">{{ $self['message'] ?? 'يلزم التجديد عبر الشركة.' }}</div>
                <form method="post" action="{{ route('web.customer.renewal') }}">
                    @csrf
                    <input type="hidden" name="action" value="company_request">
                    <button class="cp-btn cp-btn--primary cp-btn--block" type="submit">طلب تجديد من الشركة</button>
                </form>
            @endif
        @else
            <p style="color:var(--cp-muted);margin:0">لا توجد باقة مرتبطة حالياً.</p>
        @endif
    </article>
</div>

<article class="cp-card" style="margin-top:1rem">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:0.5rem">
        <h2 style="margin:0">آخر الفواتير</h2>
        <a href="{{ route('web.customer.invoices') }}" class="cp-btn cp-btn--ghost" style="padding:0.35rem 0.7rem;font-size:0.8rem">الكل</a>
    </div>
    @if(count($invoices) === 0)
        <p style="color:var(--cp-muted);margin:0">لا توجد فواتير بعد.</p>
    @else
        <div style="overflow-x:auto">
            <table class="cp-table">
                <thead>
                <tr>
                    <th>الرقم</th>
                    <th>المبلغ</th>
                    <th>الاستحقاق</th>
                    <th>الحالة</th>
                </tr>
                </thead>
                <tbody>
                @foreach($invoices as $inv)
                    <tr>
                        <td>{{ $inv['invoice_number'] }}</td>
                        <td>₪ {{ $inv['amount'] }}</td>
                        <td>{{ $inv['due_date'] ?: '—' }}</td>
                        <td>
                            <span class="cp-pill cp-pill--{{ $inv['status'] === 'paid' ? 'paid' : ($inv['status'] === 'partial' ? 'partial' : 'unpaid') }}">
                                {{ $inv['status_label'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>

<article class="cp-card" style="margin-top:1rem">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:0.5rem">
        <h2 style="margin:0">الاستهلاك الشهري</h2>
        <a href="{{ route('web.customer.usage') }}" class="cp-btn cp-btn--ghost" style="padding:0.35rem 0.7rem;font-size:0.8rem">التفاصيل</a>
    </div>
    @if(!($usage['has_data'] ?? false))
        <div class="cp-alert cp-alert--info" style="margin:0">{{ $usage['note'] }}</div>
    @else
        @php $max = max(1, collect($usage['months'])->max('total_bytes')); @endphp
        <div class="cp-grid" style="gap:0.75rem">
            @foreach(array_reverse($usage['months']) as $m)
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem">
                        <strong>{{ $m['label'] }}</strong>
                        <span>{{ $m['total'] }}</span>
                    </div>
                    <div class="cp-usage-bar"><span style="width:{{ round(($m['total_bytes'] / $max) * 100) }}%"></span></div>
                </div>
            @endforeach
        </div>
    @endif
</article>

<article class="cp-card" style="margin-top:1rem">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:0.75rem">
        <h2 style="margin:0">باقات يمكن الاشتراك فيها</h2>
        <a href="{{ route('web.customer.plans') }}" class="cp-btn cp-btn--ghost" style="padding:0.35rem 0.7rem;font-size:0.8rem">عرض الكل</a>
    </div>
    <div class="cp-grid cp-grid--3">
        @foreach($available_plans as $plan)
            <div class="cp-plan {{ $plan['is_current'] ? 'is-current' : '' }}">
                <div class="cp-plan__name">{{ $plan['name'] }}</div>
                <div class="cp-plan__speed">{{ $plan['speed_mbps'] }} Mbps</div>
                <div class="cp-plan__price">₪ {{ $plan['price'] }} <span>/ شهر</span></div>
                @if($plan['is_current'])
                    <span class="cp-pill cp-pill--current">باقتك الحالية</span>
                @elseif($renewal['pending_request'] ?? false)
                    <span class="cp-pill cp-pill--partial">طلب قيد المعالجة</span>
                @else
                    <form method="post" action="{{ route('web.customer.renewal') }}">
                        @csrf
                        <input type="hidden" name="action" value="company_request">
                        <input type="hidden" name="plan_id" value="{{ $plan['id'] }}">
                        <button class="cp-btn cp-btn--primary cp-btn--block" type="submit">طلب الاشتراك</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</article>
@endsection
