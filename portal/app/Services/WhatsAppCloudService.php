<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Meta WhatsApp Cloud API — template messages only (outside 24h session window).
 * Secrets stay in env; never log access tokens.
 */
class WhatsAppCloudService
{
    protected ?string $lastError = null;

    public function isConfigured(): bool
    {
        return trim((string) config('services.whatsapp.access_token', '')) !== ''
            && trim((string) config('services.whatsapp.phone_number_id', '')) !== '';
    }

    public function isLive(): bool
    {
        return filter_var(config('services.whatsapp.enabled', false), FILTER_VALIDATE_BOOL)
            && $this->isConfigured();
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Palestine-friendly E.164 digits without +.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        // Palestine / Israel country codes (already E.164 digits)
        if (str_starts_with($digits, '970') || str_starts_with($digits, '972')) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) >= 9) {
            return '970'.substr($digits, 1);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            return '970'.$digits;
        }

        return $digits;
    }

    /**
     * @param  list<string>  $bodyParams  Template body variables in order {{1}}…
     * @param  string|null  $urlButtonSuffix  Dynamic path suffix for Visit website button (index 0)
     * @return array{ok: bool, message_id: ?string, dry_run?: bool}
     */
    public function sendTemplate(
        string $phone,
        string $templateName,
        array $bodyParams,
        ?string $language = null,
        ?string $urlButtonSuffix = null
    ): array {
        $this->lastError = null;
        $to = $this->normalizePhone($phone);

        if ($to === '' || strlen($to) < 10) {
            $this->lastError = 'رقم هاتف غير صالح.';

            return ['ok' => false, 'message_id' => null];
        }

        $language = $language ?: (string) config('services.whatsapp.template_language', 'ar');
        $version = trim((string) config('services.whatsapp.api_version', 'v21.0'), '/');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');

        if (! $this->isLive()) {
            $this->lastError = 'WhatsApp معطّل أو ناقص الإعدادات (WHATSAPP_ENABLED / TOKEN / PHONE_NUMBER_ID).';

            Log::info('WhatsApp template skipped (not live)', [
                'to' => $to,
                'template' => $templateName,
            ]);

            return ['ok' => false, 'message_id' => null];
        }

        $bodyParameters = [];
        foreach ($bodyParams as $text) {
            $bodyParameters[] = [
                'type' => 'text',
                'text' => (string) $text,
            ];
        }

        $components = [];

        // Header (مقدمة القالب في Meta): image عبر رابط عام، أو نص بمتغير واحد
        $headerComponent = $this->headerComponent();
        if ($headerComponent !== null) {
            $components[] = $headerComponent;
        }

        // Templates without body variables must omit the body component.
        if ($bodyParameters !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParameters,
            ];
        }

        $suffix = trim((string) $urlButtonSuffix);
        if ($suffix !== '') {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => $suffix,
                    ],
                ],
            ];
        }

        $template = [
            'name' => $templateName,
            'language' => ['code' => $language],
        ];
        if ($components !== []) {
            $template['components'] = $components;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => $template,
        ];

        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        try {
            $response = Http::withToken((string) config('services.whatsapp.access_token'))
                ->acceptJson()
                ->asJson()
                ->timeout(25)
                ->post($url, $payload);

            if ($response->successful()) {
                $messageId = data_get($response->json(), 'messages.0.id');

                Log::info('WhatsApp template sent', [
                    'to' => $to,
                    'template' => $templateName,
                    'message_id' => $messageId,
                ]);

                return ['ok' => true, 'message_id' => is_string($messageId) ? $messageId : null];
            }

            $status = $response->status();
            $body = Str::limit((string) $response->body(), 400);
            $this->lastError = "HTTP {$status}: {$body}";

            // Permanent-ish client errors — caller should not hammer retries
            if (in_array($status, [400, 401, 403, 404, 422], true)) {
                Log::warning('WhatsApp template rejected', [
                    'to' => $to,
                    'template' => $templateName,
                    'status' => $status,
                ]);
            } else {
                Log::warning('WhatsApp template temporary failure', [
                    'to' => $to,
                    'status' => $status,
                ]);
            }

            return ['ok' => false, 'message_id' => null];
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('WhatsApp template exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message_id' => null];
        }
    }

    /**
     * Session text message (allowed inside 24h customer care window after user reply).
     *
     * @return array{ok: bool, message_id: ?string}
     */
    public function sendText(string $phone, string $body): array
    {
        $this->lastError = null;
        $to = $this->normalizePhone($phone);
        $body = trim($body);

        if ($to === '' || strlen($to) < 10) {
            $this->lastError = 'رقم هاتف غير صالح.';

            return ['ok' => false, 'message_id' => null];
        }

        if ($body === '') {
            $this->lastError = 'نص الرسالة فارغ.';

            return ['ok' => false, 'message_id' => null];
        }

        if (! $this->isLive()) {
            $this->lastError = 'WhatsApp معطّل أو ناقص الإعدادات.';

            return ['ok' => false, 'message_id' => null];
        }

        $version = trim((string) config('services.whatsapp.api_version', 'v21.0'), '/');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ];

        try {
            $response = Http::withToken((string) config('services.whatsapp.access_token'))
                ->acceptJson()
                ->asJson()
                ->timeout(25)
                ->post($url, $payload);

            if ($response->successful()) {
                $messageId = data_get($response->json(), 'messages.0.id');

                return ['ok' => true, 'message_id' => is_string($messageId) ? $messageId : null];
            }

            $this->lastError = 'HTTP '.$response->status().': '.Str::limit((string) $response->body(), 400);

            return ['ok' => false, 'message_id' => null];
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();

            return ['ok' => false, 'message_id' => null];
        }
    }

    /**
     * Build optional template header component from env.
     * - none / empty: omit (Meta static text header needs no API params)
     * - image: WHATSAPP_TEMPLATE_HEADER_IMAGE_URL (public HTTPS)
     * - text: WHATSAPP_TEMPLATE_HEADER_TEXT (only if header has {{1}})
     *
     * @return array<string, mixed>|null
     */
    private function headerComponent(): ?array
    {
        $type = strtolower(trim((string) config('services.whatsapp.header_type', 'none')));

        if ($type === '' || $type === 'none') {
            return null;
        }

        if ($type === 'image') {
            $url = trim((string) config('services.whatsapp.header_image_url', ''));
            if ($url === '') {
                return null;
            }

            return [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => ['link' => $url],
                    ],
                ],
            ];
        }

        if ($type === 'text') {
            $text = trim((string) config('services.whatsapp.header_text', ''));
            if ($text === '') {
                return null;
            }

            return [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => $text,
                    ],
                ],
            ];
        }

        return null;
    }
}
