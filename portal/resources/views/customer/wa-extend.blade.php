<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#246cf0">
    <title>{{ $title ?? 'تمديد الخط' }} | مدد</title>
    <link rel="stylesheet" href="{{ asset('fonts/neo-sans-arabic.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/customer-portal.css') }}?v=1">
</head>
<body class="cp-body">
<div class="cp-login-page">
    <div class="cp-login-card">
        <x-brand-logo eager />
        <h1>{{ $title ?? 'تمديد الخط' }}</h1>
        <p class="lead">{{ $message }}</p>

        @if(!empty($ok) && !empty($new_end_date))
            <p class="cp-hint">الاشتراك ساري حتى <strong>{{ $new_end_date }}</strong>
                @if(!empty($days)) ({{ $days }} أيام) @endif
            </p>
            @if(!empty($mikrotik))
                <p class="cp-hint">{{ $mikrotik }}</p>
            @endif
        @endif

        @if(!empty($show_form) && empty($ok))
            <form method="post" action="{{ route('web.whatsapp.extend.submit') }}" novalidate style="margin-top:1.25rem;">
                @csrf
                <div class="cp-field">
                    <label for="phone">رقم الجوال</label>
                    <input id="phone" name="phone" value="{{ old('phone', $old_phone ?? '') }}" required
                           autocomplete="tel" inputmode="tel" placeholder="05XXXXXXXX أو 972...">
                </div>
                @error('phone')
                    <div class="cp-alert cp-alert--err" role="alert">{{ $message }}</div>
                @enderror
                <button class="cp-btn cp-btn--primary cp-btn--block" type="submit">تفعيل التمديد</button>
            </form>
        @endif

        <p class="cp-hint" style="margin-top:1.25rem;">
            <a href="{{ route('web.customer.login') }}">دخول حساب المشترك</a>
        </p>
    </div>
</div>
</body>
</html>
