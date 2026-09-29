@extends('layouts.customer')

@section('title', 'الفواتير')

@section('content')
<section class="cp-hero">
    <h1>الفواتير</h1>
    <p>{{ $account['full_name'] }} — سجل الفواتير والمدفوعات</p>
</section>

<article class="cp-card">
    @if(count($invoices) === 0)
        <p style="color:var(--cp-muted);margin:0">لا توجد فواتير.</p>
    @else
        <div style="overflow-x:auto">
            <table class="cp-table">
                <thead>
                <tr>
                    <th>الرقم</th>
                    <th>الباقة</th>
                    <th>المبلغ</th>
                    <th>المدفوع</th>
                    <th>المتبقي</th>
                    <th>الاستحقاق</th>
                    <th>الحالة</th>
                </tr>
                </thead>
                <tbody>
                @foreach($invoices as $inv)
                    <tr>
                        <td>{{ $inv['invoice_number'] }}</td>
                        <td>{{ $inv['plan_name'] ?: '—' }}</td>
                        <td>₪ {{ $inv['amount'] }}</td>
                        <td>₪ {{ $inv['paid_amount'] }}</td>
                        <td>₪ {{ $inv['remaining'] }}</td>
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
@endsection
