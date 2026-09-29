@props([
    'title' => 'لوحة الوكيل',
])
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2b5d66">
    <title>{{ $title }} | مدد — بوابة الوكيل</title>
    <link rel="preconnect" href="https://db.onlinewebfonts.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/din-next-lt-arabic.css') }}?v=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/olivia-panel.css') }}?v=25">
    <link rel="stylesheet" href="{{ asset('css/agent-panel.css') }}?v=25">
</head>
<body class="olivia-panel agent-panel">
<div class="olivia-shell">
    <aside class="olivia-sidebar">
        <div class="olivia-sidebar-brand text-center">
            <x-brand-logo class="olivia-brand-logo" />
        </div>
        <div class="olivia-nav-label">القائمة</div>
        <nav class="olivia-sidebar-nav nav flex-column">
            <a class="nav-link {{ request()->routeIs('web.agent.dashboard') ? 'active' : '' }}" href="{{ route('web.agent.dashboard') }}">
                <i class="bi bi-house-door-fill"></i><span>الرئيسية</span>
            </a>
            <a class="nav-link {{ request()->routeIs('web.agent.search') ? 'active' : '' }}" href="{{ route('web.agent.search') }}">
                <i class="bi bi-search"></i><span>بحث مشترك</span>
            </a>
            <a class="nav-link {{ request()->routeIs('web.agent.invoices') ? 'active' : '' }}" href="{{ route('web.agent.invoices') }}">
                <i class="bi bi-file-earmark-text"></i><span>الفواتير</span>
            </a>
            <a class="nav-link {{ request()->routeIs('web.agent.payments') ? 'active' : '' }}" href="{{ route('web.agent.payments') }}">
                <i class="bi bi-cash-stack"></i><span>المدفوعات</span>
            </a>
        </nav>
        <div class="olivia-sidebar-footer">
            <div class="olivia-footer-text">
                بوابة وكيل التحصيل<br>
                <span style="opacity:.75;">مدد © {{ date('Y') }}</span>
            </div>
        </div>
    </aside>
    <main class="olivia-main">
        <header class="olivia-topbar">
            <div>
                <h1 class="olivia-topbar-title">{{ $title }}</h1>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <div class="d-flex align-items-center gap-2 flex-wrap agent-topbar-user">
                    <span class="olivia-user-pill">{{ auth()->user()->name ?? '' }}</span>
                    @if(auth()->check() && auth()->user()->commission_percent !== null)
                        <span class="agent-hero-badge agent-topbar-commission" title="نسبة العمولة المعتمدة">
                            <i class="bi bi-percent" aria-hidden="true"></i>
                            عمولة {{ number_format((float) auth()->user()->commission_percent, 1) }}٪
                        </span>
                    @endif
                </div>
                @auth
                    <form method="post" action="{{ route('web.logout') }}" class="m-0">
                        @csrf
                        <button class="btn olivia-btn-logout" type="submit" aria-label="تسجيل الخروج من الحساب"><i class="bi bi-box-arrow-left ms-1" aria-hidden="true"></i> تسجيل الخروج</button>
                    </form>
                @endauth
            </div>
        </header>
        <div class="olivia-content">
            <div class="agent-zone">
                {{ $slot }}
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="{{ asset('js/agent-renew.js') }}?v=1" defer></script>
{{ $scripts ?? '' }}
@stack('scripts')
</body>
</html>
