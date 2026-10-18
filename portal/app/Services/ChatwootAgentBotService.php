<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Chatwoot Agent Bot webhook adapter → WhatsAppConversationService.
 * Keep Meta WhatsApp webhook pointing at Chatwoot; bot URL points here.
 */
class ChatwootAgentBotService
{
    public function __construct(
        private WhatsAppConversationService $conversations,
        private WhatsAppCloudService $whatsapp,
        private ChatwootApiService $chatwoot,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $event = (string) ($payload['event'] ?? '');
        if ($event !== '' && ! in_array($event, ['message_created', 'message_updated'], true)) {
            return;
        }

        if (! $this->isIncomingCustomerMessage($payload)) {
            return;
        }

        $text = $this->extractContent($payload);
        if ($text === '') {
            return;
        }

        $phone = $this->extractPhone($payload);
        if ($phone === '') {
            Log::info('Chatwoot bot: no phone on payload, skip');

            return;
        }

        // Chatwoot messages API uses display_id in the URL path.
        $conversationId = (int) (
            data_get($payload, 'conversation.display_id')
            ?: data_get($payload, 'conversation.id')
            ?: 0
        );

        $this->conversations->handleInboundText(
            $phone,
            $text,
            function (string $reply) use ($conversationId, $phone): void {
                if ($conversationId > 0 && $this->chatwoot->isConfigured()) {
                    $this->chatwoot->sendConversationMessage($conversationId, $reply);

                    return;
                }

                // Fallback: reply via Meta Cloud API if Chatwoot API not configured
                $this->whatsapp->sendText($phone, $reply);
            },
            $payload
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isIncomingCustomerMessage(array $payload): bool
    {
        $type = $payload['message_type'] ?? data_get($payload, 'message.message_type');

        if (is_numeric($type)) {
            return (int) $type === 0; // incoming
        }

        $type = strtolower((string) $type);

        return $type === '' || $type === 'incoming';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractContent(array $payload): string
    {
        $content = trim((string) (
            $payload['content']
            ?? data_get($payload, 'message.content')
            ?? ''
        ));

        if ($content !== '') {
            return $content;
        }

        // Some WhatsApp button payloads land in content_attributes
        return trim((string) (
            data_get($payload, 'content_attributes.payload')
            ?: data_get($payload, 'content_attributes.submitted_values.0.value')
            ?: ''
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractPhone(array $payload): string
    {
        $candidates = [
            data_get($payload, 'sender.phone_number'),
            data_get($payload, 'contact.phone_number'),
            data_get($payload, 'conversation.meta.sender.phone_number'),
            data_get($payload, 'conversation.contact_inbox.source_id'),
            data_get($payload, 'source_id'),
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->whatsapp->normalizePhone((string) $candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }
}
