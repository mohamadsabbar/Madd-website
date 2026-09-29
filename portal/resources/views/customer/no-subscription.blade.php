@extends('layouts.customer')

@section('title', 'لا يوجد اشتراك')

@section('content')
<article class="cp-card">
    <h2>تعذّر تحميل الحساب</h2>
    <p style="color:var(--cp-muted)">{{ $message }}</p>
</article>
@endsection
