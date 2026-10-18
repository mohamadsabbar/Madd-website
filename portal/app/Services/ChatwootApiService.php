<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatwootApiService
{
    public function isConfigured(): bool
    {
        return trim((string) config('services.chatwoot.base_url', '')) !== ''
            && trim((string) config('services.chatwoot.bot_access_token', '')) !== ''
            && (int) config('services.chatwoot.account_id', 0) > 0;
    }

    public function sendConversationMessage(int $conversationId, string $content): bool
    {
        if (! $this->isConfigured() || $conversationId <= 0 || trim($content) === '') {
            return false;
        }

        $base = rtrim((string) config('services.chatwoot.base_url'), '/');
        $accountId = (int) config('services.chatwoot.account_id');
        $token = (string) config('services.chatwoot.bot_access_token');

        $url = "{$base}/api/v1/accounts/{$accountId}/conversations/{$conversationId}/messages";

        try {
            $response = Http::withHeaders([
                'api_access_token' => $token,
                'Content-Type' => 'application/json',
            ])
                ->acceptJson()
                ->timeout(25)
                ->post($url, [
                    'content' => $content,
                    'message_type' => 'outgoing',
                    'private' => false,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Chatwoot bot reply failed', [
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 400),
                'conversation_id' => $conversationId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Chatwoot bot reply exception', ['error' => $e->getMessage()]);
        }

        return false;
    }
}
