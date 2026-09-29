<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2b5d66">
    <title>تسجيل الخروج | مدد</title>
    <link rel="stylesheet" href="{{ asset('css/customer-portal.css') }}?v=1">
</head>
<body class="cp-body">
<div class="cp-login-page">
    <div class="cp-login-card">
        <x-brand-logo />
        <h1>تسجيل الخروج</h1>
        <p class="lead">هل تريد إنهاء الجلسة؟</p>
        <form method="post" action="{{ route('web.logout') }}">
            @csrf
            <button class="cp-btn cp-btn--primary cp-btn--block" type="submit">نعم، خروج</button>
        </form>
        <p class="cp-hint"><a href="{{ url()->previous() }}">إلغاء</a></p>
    </div>
</div>
</body>
</html>
