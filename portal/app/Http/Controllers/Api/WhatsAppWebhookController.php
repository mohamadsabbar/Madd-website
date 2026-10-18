<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppConversationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private WhatsAppConversationService $conversations) {}

    /**
     * Meta webhook verification (hub.challenge).
     */
    public function verify(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        $expected = (string) config('services.whatsapp.webhook_verify_token', '');

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request): Response
    {
        if (! $this->signatureValid($request)) {
            Log::warning('WhatsApp webhook invalid signature');

            return response('Invalid signature', 403);
        }

        $payload = $request->all();
        if (! is_array($payload)) {
            return response('ok', 200);
        }

        // Acknowledge quickly; processing is lightweight enough inline for MVP.
        $this->conversations->handleWebhookPayload($payload);

        return response('EVENT_RECEIVED', 200);
    }

    private function signatureValid(Request $request): bool
    {
        $secret = (string) config('services.whatsapp.app_secret', '');
        if ($secret === '') {
            // Allow in local/dev if secret not set; production must set WHATSAPP_APP_SECRET.
            return ! app()->environment('production');
        }

        $header = (string) $request->header('X-Hub-Signature-256', '');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }
}
