<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#246cf0">
    <title>تسجيل الدخول | مدد</title>
    <link rel="preconnect" href="https://db.onlinewebfonts.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('fonts/neo-sans-arabic.css') }}?v=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/olivia-panel.css') }}?v=25">
</head>
<body class="auth-login">
    <div class="auth-login-bg" aria-hidden="true"></div>

    <main class="auth-login-frame">
        <section class="auth-login-card" aria-labelledby="auth-login-heading">
            <div class="auth-login-card__brand">
                <x-brand-logo class="auth-login-card__logo" eager />
            </div>

            <div class="auth-login-card__body">
                <header class="auth-login-card__intro">
                    <h1 id="auth-login-heading" class="auth-login-card__title">تسجيل الدخول</h1>
                </header>

                @if(session('status'))
                    <div class="auth-login-alert auth-login-alert--success" role="status">{{ session('status') }}</div>
                @endif
                @if(session('error'))
                    <div class="auth-login-alert auth-login-alert--danger" role="alert">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="auth-login-alert auth-login-alert--danger" role="alert">
                        <ul class="auth-login-alert__list mb-0 ps-3">
                            @foreach($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('web.login.submit') }}" class="auth-login-form" novalidate>
                    @csrf

                    <div class="auth-login-field">
                        <label for="login-email" class="auth-login-label">البريد الإلكتروني</label>
                        <div class="auth-login-input-wrap">
                            <input id="login-email" type="email" name="email" class="auth-login-input" value="{{ old('email') }}" required autocomplete="username" inputmode="email" placeholder="you@company.com">
                            <span class="auth-login-input-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                        </div>
                    </div>

                    <div class="auth-login-field">
                        <label for="login-password" class="auth-login-label">كلمة المرور</label>
                        <div class="auth-login-input-wrap">
                            <input id="login-password" type="password" name="password" class="auth-login-input" required autocomplete="current-password" placeholder="أدخل كلمة المرور">
                            <span class="auth-login-input-icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                        </div>
                    </div>

                    <button type="submit" class="auth-login-submit">
                        <span>دخول إلى المنصة</span>
                        <i class="bi bi-arrow-left-short auth-login-submit__ico" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </section>

        <p class="auth-login-foot">© {{ date('Y') }} مدد — MADD</p>
    </main>
</body>
</html>
