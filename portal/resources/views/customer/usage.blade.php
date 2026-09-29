@extends('layouts.customer')

@section('title', 'الاستهلاك')

@section('content')
<section class="cp-hero">
    <h1>الاستهلاك الشهري</h1>
    <p>ملخص التحميل والرفع خلال الأشهر الأخيرة</p>
</section>

<article class="cp-card">
    @if(!($usage['has_data'] ?? false))
        <div class="cp-alert cp-alert--info" style="margin:0">{{ $usage['note'] }}</div>
    @else
        @php $max = max(1, collect($usage['months'])->max('total_bytes')); @endphp
        <div class="cp-grid" style="gap:1rem">
            @foreach(array_reverse($usage['months']) as $m)
                <div>
                    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                        <strong>{{ $m['label'] }}</strong>
                        <span style="color:var(--cp-muted);font-size:0.9rem">
                            تنزيل {{ $m['download'] }} · رفع {{ $m['upload'] }} · الإجمالي {{ $m['total'] }}
                        </span>
                    </div>
                    <div class="cp-usage-bar"><span style="width:{{ round(($m['total_bytes'] / $max) * 100) }}%"></span></div>
                </div>
            @endforeach
        </div>
    @endif
</article>
@endsection
