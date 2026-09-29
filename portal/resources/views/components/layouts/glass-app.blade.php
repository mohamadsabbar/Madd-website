@props([
    'title' => 'لوحة التحكم',
    /** إعادة تحميل الصفحة كل N ثانية؛ null = لا شيء */
    'autoRefreshSeconds' => null,
])
@php
    $dgRefreshSec = $autoRefreshSeconds !== null ? (int) $autoRefreshSeconds : 0;
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2b5d66">
    <title>{{ $title }} | مدد</title>
    <link rel="preconnect" href="https://db.onlinewebfonts.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/din-next-lt-arabic.css') }}?v=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard-glass.css') }}?v=25">
</head>
<body class="dg-panel">
<div class="dg-sidebar-backdrop" id="dg-sidebar-backdrop" hidden aria-hidden="true"></div>

<div class="dg-shell">
    <aside class="dg-sidebar" id="dg-sidebar" aria-label="التنقل الرئيسي">
        <div class="dg-sidebar-head">
            <a href="{{ route('web.admin.dashboard') }}" class="dg-sidebar-brand" title="مدد — الرئيسية">
                <x-brand-logo class="dg-sidebar-logo" eager />
            </a>
            <button type="button" class="dg-sidebar-close" id="dg-sidebar-close" aria-label="إغلاق القائمة">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="dg-sidebar-nav" aria-label="القائمة">
            <span class="dg-sidebar-section">القائمة</span>
            <a href="{{ route('web.admin.dashboard') }}" class="dg-nav-link {{ request()->routeIs('web.admin.dashboard') ? 'active' : '' }}">
                <span class="dg-nav-link__icon"><i class="bi bi-grid-1x2-fill"></i></span>
                <span class="dg-nav-link__text">الرئيسية</span>
            </a>
            <a href="{{ route('web.admin.analytics') }}" class="dg-nav-link {{ request()->routeIs('web.admin.analytics') ? 'active' : '' }}">
                <span class="dg-nav-link__icon"><i class="bi bi-graph-up-arrow"></i></span>
                <span class="dg-nav-link__text">تحليلات الاستهلاك</span>
            </a>
            <a href="{{ route('web.admin.activity') }}" class="dg-nav-link {{ request()->routeIs('web.admin.activity') ? 'active' : '' }}">
                <span class="dg-nav-link__icon"><i class="bi bi-activity"></i></span>
                <span class="dg-nav-link__text">سجل العمليات</span>
            </a>
            <a href="{{ route('web.admin.sms') }}" class="dg-nav-link {{ request()->routeIs('web.admin.sms') ? 'active' : '' }}">
                <span class="dg-nav-link__icon"><i class="bi bi-send-fill"></i></span>
                <span class="dg-nav-link__text">إرسال رسائل SMS</span>
            </a>
            <a href="{{ route('web.admin.subscribers') }}" class="dg-nav-link {{ request()->routeIs('web.admin.subscribers', 'web.admin.subscribers.*') ? 'active' : '' }}">
                <span class="dg-nav-link__icon"><i class="bi bi-people-fill"></i></span>
                <span class="dg-nav-link__text">المشتركين</span>
            </a>
            <a href="{{ route('web.admin.plans') }}" class="dg-nav-link {{ request()->routeIs('web.admin.plans') ? 'active' : '' }}">
                <span class="dg-nav-link__icon"><i class="bi bi-broadcast"></i></span>
                <span class="dg-nav-link__text">الباقات</span>
            </a>
            <a href="{{ route('web.admin.agents') }}" class="dg-nav-link {{ request()->routeIs('web.admin.agents') ? 'active' : '' }}">
                <span class="dg-nav-link__icon"><i class="bi bi-person-badge-fill"></i></span>
                <span class="dg-nav-link__text">الوكلاء</span>
            </a>
            <a href="{{ route('web.admin.routers') }}" class="dg-nav-link {{ request()->is('admin/routers*') ? 'active' : '' }}">
                <span class="dg-nav-link__icon"><i class="bi bi-hdd-network-fill"></i></span>
                <span class="dg-nav-link__text">راوترات MikroTik</span>
            </a>
        </nav>

        <div class="dg-sidebar-foot">
            <span class="dg-sidebar-foot-label">ISP</span>
        </div>
    </aside>

    <main class="dg-main">
        <header class="dg-topbar">
            <div class="dg-topbar-row">
                <button type="button" class="dg-menu-toggle" id="dg-menu-toggle" aria-label="فتح القائمة" aria-expanded="false" aria-controls="dg-sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <form action="{{ route('web.admin.subscribers') }}" method="get" class="dg-search-wrap">
                    <i class="bi bi-search dg-search-icon"></i>
                    <input type="search" class="dg-search" name="q" placeholder="بحث عن مشترك…" value="{{ request('q') }}" autocomplete="off">
                </form>
            </div>
            <div class="dg-topbar-end">
                <span class="dg-date-pill d-none d-sm-inline">{{ now()->locale('ar')->translatedFormat('l، j F Y') }}</span>
                <span class="dg-date-pill d-sm-none">{{ now()->locale('ar')->translatedFormat('j M') }}</span>
                <button type="button" class="dg-icon-btn d-none d-md-flex" aria-label="الإعدادات"><i class="bi bi-sliders"></i></button>
                <button type="button" class="dg-icon-btn d-none d-md-flex" aria-label="إشعارات"><i class="bi bi-bell"></i></button>
                <span class="dg-avatar" title="{{ auth()->user()->name ?? '' }}">{{ mb_substr(auth()->user()->name ?? '؟', 0, 1, 'UTF-8') }}</span>
                @auth
                    <form method="post" action="{{ route('web.logout') }}" class="dg-logout-form">
                        @csrf
                        <button type="submit" class="dg-logout-btn" aria-label="تسجيل الخروج من الحساب"><i class="bi bi-box-arrow-left ms-1" aria-hidden="true"></i> تسجيل الخروج</button>
                    </form>
                @endauth
            </div>
        </header>
        <div class="dg-content">
            {{ $slot }}
        </div>
    </main>
</div>
@if($dgRefreshSec > 0)
<script>
(function () {
    var sec = {{ $dgRefreshSec }};
    setInterval(function () { location.reload(); }, sec * 1000);
})();
</script>
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var body = document.body;
    var sidebar = document.getElementById('dg-sidebar');
    var backdrop = document.getElementById('dg-sidebar-backdrop');
    var toggle = document.getElementById('dg-menu-toggle');
    var closeBtn = document.getElementById('dg-sidebar-close');

    function openNav() {
        body.classList.add('dg-sidebar-open');
        backdrop.hidden = false;
        backdrop.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        body.style.overflow = 'hidden';
    }
    function closeNav() {
        body.classList.remove('dg-sidebar-open');
        backdrop.hidden = true;
        backdrop.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        body.style.overflow = '';
    }
    function mq() { return window.matchMedia('(max-width: 991.98px)').matches; }

    toggle.addEventListener('click', function () {
        if (body.classList.contains('dg-sidebar-open')) closeNav();
        else openNav();
    });
    backdrop.addEventListener('click', closeNav);
    closeBtn.addEventListener('click', closeNav);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeNav();
    });
    sidebar.querySelectorAll('a.dg-nav-link').forEach(function (a) {
        a.addEventListener('click', function () { if (mq()) closeNav(); });
    });
    window.addEventListener('resize', function () {
        if (!mq()) closeNav();
    });
})();
</script>
</body>
</html>
