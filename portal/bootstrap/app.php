<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare + host Nginx reverse proxy (X-Forwarded-Proto / CF-Connecting-IP)
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*') === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '*'))))),
        );

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserRole::class,
            'site.cms' => \App\Http\Middleware\VerifySiteCmsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
