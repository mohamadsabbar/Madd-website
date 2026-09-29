<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2b5d66">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) config('landing.lead')), 160) }}">
    <title>{{ config('landing.company_name') }}</title>
    <link rel="preconnect" href="https://db.onlinewebfonts.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/din-next-lt-arabic.css') }}?v=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --ol-accent: #2b5d66; }
        body { font-family: 'DIN Next LT Arabic', 'Cairo', 'Segoe UI', Tahoma, sans-serif; background: linear-gradient(165deg, #0c171a 0%, #1a3f46 45%, #2b5d66 100%); min-height: 100vh; color: #eaf3f4; }
        .ol-wrap { padding: clamp(2rem, 5vw, 3.5rem) 0; }
        .ol-accent { color: #9fd0d8; }
        .ol-brand img { max-height: 96px; width: auto; background: #fff; border-radius: 12px; padding: 0.65rem 1rem; }
        .ol-card { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 1rem 1.15rem; height: 100%; }
        .ol-card i { color: #9fd0d8; font-size: 1.25rem; }
        .ol-contact { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 1.25rem 1.5rem; }
        .ol-contact a { color: #9fd0d8; text-decoration: none; }
        .ol-contact a:hover { text-decoration: underline; }
        .ol-footer { border-top: 1px solid rgba(255,255,255,0.1); padding: 1.25rem 0; margin-top: 2.5rem; color: rgba(234,243,244,0.55); font-size: 0.88rem; }
    </style>
</head>
<body>
    <div class="container ol-wrap">
        <header class="text-center mb-4 pb-4" style="border-bottom:1px solid rgba(255,255,255,0.1);">
            <div class="ol-brand mb-3">
                <x-brand-logo alt="{{ config('landing.company_name') }}" eager />
            </div>
        </header>

        <div class="row justify-content-center g-4">
            <div class="col-lg-9 text-center">
                <h2 class="fw-bold mb-3" style="font-size:clamp(1.35rem,3.5vw,2rem);">{{ config('landing.headline') }}</h2>
                <p class="lead mx-auto mb-3" style="max-width:40rem;color:rgba(232,234,239,0.82);">{{ config('landing.lead') }}</p>
                @if(filled(config('landing.extra')))
                    <p class="mx-auto mb-0" style="max-width:40rem;color:rgba(232,234,239,0.72);">{{ config('landing.extra') }}</p>
                @endif
                <div class="mt-4 d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ route('web.customer.login') }}" class="btn btn-light fw-bold px-4" style="border-radius:10px;">دخول المشترك</a>
                    <a href="{{ route('login') }}" class="btn btn-outline-light fw-bold px-4" style="border-radius:10px;">دخول الإدارة / الوكيل</a>
                </div>
            </div>
        </div>

        @if(count(config('landing.features', [])) > 0)
            <div class="row g-3 mt-2 justify-content-center" id="features">
                @foreach(config('landing.features', []) as $feature)
                    <div class="col-md-6 col-lg-4">
                        <div class="ol-card d-flex align-items-start gap-3 text-start">
                            <i class="bi bi-check-circle-fill flex-shrink-0 mt-1"></i>
                            <span>{{ $feature }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if(filled(config('landing.contact_phone')) || filled(config('landing.contact_email')) || filled(config('landing.contact_address')))
            <div class="row justify-content-center mt-4">
                <div class="col-lg-8">
                    <div class="ol-contact text-center">
                        <div class="fw-semibold mb-3 ol-accent">تواصل معنا</div>
                        <ul class="list-unstyled mb-0 small" style="line-height:2;">
                            @if(filled(config('landing.contact_phone')))
                                <li><i class="bi bi-telephone ms-2"></i>{{ config('landing.contact_phone') }}</li>
                            @endif
                            @if(filled(config('landing.contact_email')))
                                <li><i class="bi bi-envelope ms-2"></i><a href="mailto:{{ config('landing.contact_email') }}">{{ config('landing.contact_email') }}</a></li>
                            @endif
                            @if(filled(config('landing.contact_address')))
                                <li><i class="bi bi-geo-alt ms-2"></i>{{ config('landing.contact_address') }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <footer class="ol-footer text-center">
            {{ config('landing.company_name') }} — {{ now()->locale('ar')->translatedFormat('Y') }}
        </footer>
    </div>
</body>
</html>
