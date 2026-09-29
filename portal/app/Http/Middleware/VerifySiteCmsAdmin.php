<?php

namespace App\Http\Middleware;

use App\Models\SiteConfigRecord;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySiteCmsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) (
            $request->header('X-Site-Admin-Password')
            ?: $request->input('admin_password')
            ?: ''
        );

        $envSecret = (string) config('site_cms.secret', '');
        if ($envSecret !== '' && hash_equals($envSecret, $provided)) {
            return $next($request);
        }

        $record = SiteConfigRecord::query()->where('key', 'main')->first();
        $stored = (string) data_get($record?->payload, 'admin.password', '');
        $fallback = (string) config('site_cms.default_admin_password', 'maddadmin');
        $expected = $stored !== '' ? $stored : $fallback;

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['ok' => false, 'message' => 'غير مصرح.'], 401);
        }

        return $next($request);
    }
}
