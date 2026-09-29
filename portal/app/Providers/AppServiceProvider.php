<?php

namespace App\Providers;

use App\Services\SettingsService;
use App\View\Composers\StoreComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // MySQL القديم على cPanel (utf8mb4): مفتاح primary أقصى ~1000 بايت
        Schema::defaultStringLength(191);

        date_default_timezone_set(config('hpco.region.timezone', config('app.timezone', 'Asia/Jerusalem')));

        View::composer('layouts.store', StoreComposer::class);

        RateLimiter::for('assistant', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
