@extends('layouts.customer')

@section('title', 'الباقات')

@section('content')
<section class="cp-hero">
    <h1>الباقات والخدمات</h1>
    <p>باقتك الحالية والباقات المتاحة للترقية أو التغيير</p>
</section>

@if($service)
<article class="cp-card" style="margin-bottom:1rem">
    <h2>باقتك الحالية</h2>
    <div class="cp-meta">
        <div class="cp-meta__row"><span class="cp-meta__label">الاسم</span><span class="cp-meta__value">{{ $service['name'] }}</span></div>
        <div class="cp-meta__row"><span class="cp-meta__label">السرعة</span><span class="cp-meta__value">{{ $service['speed_mbps'] }} Mbps</span></div>
        <div class="cp-meta__row"><span class="cp-meta__label">القيمة</span><span class="cp-meta__value">₪ {{ $service['price'] }}</span></div>
    </div>
</article>
@endif

<div class="cp-grid cp-grid--3">
    @foreach($plans as $plan)
        <article class="cp-card">
            <div class="cp-plan {{ $plan['is_current'] ? 'is-current' : '' }}" style="border:0;padding:0;background:transparent">
                <div class="cp-plan__name">{{ $plan['name'] }}</div>
                <div class="cp-plan__speed">{{ $plan['speed_mbps'] }} Mbps · {{ $plan['duration_days'] }} يوماً</div>
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
        </article>
    @endforeach
</div>
@endsection
