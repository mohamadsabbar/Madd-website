<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatwootAgentBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ChatwootAgentBotController extends Controller
{
    public function __construct(private ChatwootAgentBotService $bots) {}

    public function handle(Request $request): Response
    {
        if (! $this->tokenValid($request)) {
            Log::warning('Chatwoot agent bot rejected: bad token');

            return response('Forbidden', 403);
        }

        $payload = $request->all();
        if (! is_array($payload)) {
            return response('ok', 200);
        }

        try {
            $this->bots->handle($payload);
        } catch (\Throwable $e) {
            Log::warning('Chatwoot agent bot handler failed', ['error' => $e->getMessage()]);
        }

        return response('ok', 200);
    }

    private function tokenValid(Request $request): bool
    {
        $expected = trim((string) config('services.chatwoot.bot_webhook_token', ''));
        if ($expected === '') {
            // If unset, accept in non-production only
            return ! app()->environment('production');
        }

        $provided = (string) (
            $request->header('X-Madd-Bot-Token')
            ?: $request->query('token', '')
        );

        // Also allow token as path-style query for easy Chatwoot bot URL:
        // https://my.madd.ps/api/whatsapp/chatwoot-bot?token=SECRET
        return $provided !== '' && hash_equals($expected, $provided);
    }
}
