<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    public function isConfigured(): bool
    {
        return trim((string) config('services.fcm.server_key', '')) !== '';
    }

    /**
     * Send a notification to all device tokens for a user.
     *
     * @return int Number of successful deliveries
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        $tokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return 0;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * @param  list<string>  $tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): int
    {
        if (! $this->isConfigured() || $tokens === []) {
            return 0;
        }

        $serverKey = (string) config('services.fcm.server_key');
        $sent = 0;

        foreach ($tokens as $token) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key='.$serverKey,
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'sound' => 'default',
                    ],
                    'data' => array_map('strval', $data),
                    'priority' => 'high',
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if ((int) ($json['success'] ?? 0) > 0) {
                        $sent++;
                    } elseif (($json['results'][0]['error'] ?? null) === 'NotRegistered') {
                        DeviceToken::query()->where('token', $token)->delete();
                    }
                } else {
                    Log::warning('FCM send failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('FCM send exception: '.$e->getMessage());
            }
        }

        return $sent;
    }
}
