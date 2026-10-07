<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#246cf0">
    <title>تسجيل الدخول | مدد — حساب المشترك</title>
    <link rel="stylesheet" href="{{ asset('fonts/neo-sans-arabic.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/customer-portal.css') }}?v=1">
</head>
<body class="cp-body">
<div class="cp-login-page">
    <div class="cp-login-card">
        <x-brand-logo eager />
        <h1>حساب المشترك</h1>
        <p class="lead">أدخل رقم الجوال أو اسم المستخدم وكلمة المرور لمتابعة فواتيرك واشتراكك.</p>

        @if($errors->any())
            <div class="cp-alert cp-alert--err" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('web.customer.login.submit') }}" novalidate>
            @csrf
            <div class="cp-field">
                <label for="login">رقم الجوال أو اسم المستخدم</label>
                <input id="login" name="login" value="{{ old('login') }}" required autocomplete="username" inputmode="tel" placeholder="05XXXXXXXX">
            </div>
            <div class="cp-field">
                <label for="password">كلمة المرور</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="كلمة المرور" minlength="1">
            </div>
            <button class="cp-btn cp-btn--primary cp-btn--block" type="submit">دخول</button>
        </form>

        <p class="cp-hint">إن لم تغيّر كلمة المرور من قبل، استخدم كلمة المرور الافتراضية التي زوّدتك بها الشركة.</p>
        <p class="cp-hint">للموظفين والوكلاء: <a href="{{ route('login') }}">دخول الإدارة</a></p>
    </div>
</div>
</body>
</html>
