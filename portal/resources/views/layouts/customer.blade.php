<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#246cf0">
    <title>@yield('title', 'حسابي') | مدد</title>
    <link rel="stylesheet" href="{{ asset('fonts/neo-sans-arabic.css') }}?v=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/customer-portal.css') }}?v=1">
</head>
<body class="cp-body">
<div class="cp-shell">
    <header class="cp-top">
        <div class="cp-top__inner">
            <a href="{{ route('web.customer.dashboard') }}" aria-label="مدد — حسابي">
                <x-brand-logo class="cp-logo" eager />
            </a>
            <div class="cp-top__user">
                <div>{{ auth()->user()->name }}</div>
                <form method="post" action="{{ route('web.logout') }}" class="m-0" style="display:inline">
                    @csrf
                    <button type="submit" class="cp-btn cp-btn--ghost" style="padding:0.25rem 0.55rem;font-size:0.78rem;margin-top:0.25rem">خروج</button>
                </form>
            </div>
        </div>
        <nav class="cp-nav" aria-label="قائمة المشترك">
            <a href="{{ route('web.customer.dashboard') }}" class="{{ request()->routeIs('web.customer.dashboard') ? 'is-active' : '' }}">حسابي</a>
            <a href="{{ route('web.customer.invoices') }}" class="{{ request()->routeIs('web.customer.invoices') ? 'is-active' : '' }}">الفواتير</a>
            <a href="{{ route('web.customer.usage') }}" class="{{ request()->routeIs('web.customer.usage') ? 'is-active' : '' }}">الاستهلاك</a>
            <a href="{{ route('web.customer.plans') }}" class="{{ request()->routeIs('web.customer.plans') ? 'is-active' : '' }}">الباقات</a>
        </nav>
    </header>

    <main class="cp-main">
        @if(session('status'))
            <div class="cp-alert cp-alert--ok" role="status">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="cp-alert cp-alert--err" role="alert">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="cp-foot">© {{ date('Y') }} مدد — بوابة المشترك</footer>
</div>
</body>
</html>
