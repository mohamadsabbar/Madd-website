<?php

namespace App\Services;

use App\Models\Subscriber;
use App\Models\WhatsappChatSession;
use Illuminate\Support\Facades\Log;

/**
 * Inbound WhatsApp chat flow: Quick Reply "مدد خطك" → ask subscription number → self-extend.
 * Replies go through a callable so Meta Cloud API or Chatwoot Agent Bot can deliver them.
 */
class WhatsAppConversationService
{
    public function __construct(
        private WhatsAppCloudService $whatsapp,
        private CustomerPortalService $portal,
        private SubscriberPortalUserService $portalUsers,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  Meta webhook body
     */
    public function handleWebhookPayload(array $payload): void
    {
        $entries = $payload['entry'] ?? [];
        if (! is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            if (! is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                if (! is_array($value)) {
                    continue;
                }

                $messages = $value['messages'] ?? [];
                if (! is_array($messages)) {
                    continue;
                }

                foreach ($messages as $message) {
                    if (! is_array($message)) {
                        continue;
                    }

                    try {
                        $from = $this->whatsapp->normalizePhone((string) ($message['from'] ?? ''));
                        if ($from === '') {
                            continue;
                        }

                        $type = (string) ($message['type'] ?? '');
                        $text = $this->extractMetaInboundText($message, $type);
                        if ($text === '') {
                            continue;
                        }

                        $this->handleInboundText($from, $text, function (string $reply) use ($from): void {
                            $this->whatsapp->sendText($from, $reply);
                        }, $message);
                    } catch (\Throwable $e) {
                        Log::warning('WhatsApp inbound message handling failed', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Shared flow for Meta webhook or Chatwoot agent bot.
     *
     * @param  callable(string): void  $reply
     * @param  array<string, mixed>  $raw
     */
    public function handleInboundText(string $phone, string $text, callable $reply, array $raw = []): void
    {
        $phone = $this->whatsapp->normalizePhone($phone);
        $text = trim($text);
        if ($phone === '' || $text === '') {
            return;
        }

        if ($this->isExtendIntent($text, $raw)) {
            $this->beginAwaitSubscriptionNumber($phone, $reply);

            return;
        }

        $session = WhatsappChatSession::query()->where('phone', $phone)->first();
        if (! $session || $session->state !== WhatsappChatSession::STATE_AWAIT_SUBSCRIPTION_NUMBER) {
            return;
        }

        if ($session->isExpired()) {
            $session->update(['state' => WhatsappChatSession::STATE_IDLE, 'expires_at' => null]);
            $reply('انتهت مهلة طلب التمديد. اضغط زر «مدد خطك 3 أيام» من جديد إذا احتجت.');

            return;
        }

        $this->completeExtendWithSubscriptionNumber($phone, $session, $text, $reply);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function extractMetaInboundText(array $message, string $type): string
    {
        return match ($type) {
            'text' => trim((string) data_get($message, 'text.body', '')),
            'button' => trim((string) (
                data_get($message, 'button.text')
                ?: data_get($message, 'button.payload')
                ?: ''
            )),
            'interactive' => trim((string) (
                data_get($message, 'interactive.button_reply.title')
                ?: data_get($message, 'interactive.button_reply.id')
                ?: data_get($message, 'interactive.list_reply.title')
                ?: ''
            )),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public function isExtendIntent(string $text, array $raw = []): bool
    {
        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if (str_contains($normalized, 'مدد خطك') || str_contains($normalized, 'تمديد')) {
            return true;
        }

        $payload = (string) (
            data_get($raw, 'button.payload')
            ?: data_get($raw, 'interactive.button_reply.id')
            ?: data_get($raw, 'content_attributes.payload')
            ?: ''
        );

        return str_contains(mb_strtolower($payload), 'extend')
            || str_contains($payload, 'مدد');
    }

    /**
     * @param  callable(string): void  $reply
     */
    private function beginAwaitSubscriptionNumber(string $phone, callable $reply): void
    {
        $hours = max(1, (int) config('services.whatsapp.chat_session_ttl_hours', 6));

        WhatsappChatSession::query()->updateOrCreate(
            ['phone' => $phone],
            [
                'state' => WhatsappChatSession::STATE_AWAIT_SUBSCRIPTION_NUMBER,
                'expires_at' => now()->addHours($hours),
            ]
        );

        $reply("لإتمام التمديد المجاني، أرسل رقم الاشتراك الآن.\nمثال: MADD-000003");
    }

    /**
     * @param  callable(string): void  $reply
     */
    private function completeExtendWithSubscriptionNumber(
        string $phone,
        WhatsappChatSession $session,
        string $rawNumber,
        callable $reply
    ): void {
        $normalized = Subscriber::normalizeSubscriptionNumber($rawNumber);

        /** @var Subscriber|null $subscriber */
        $subscriber = Subscriber::query()
            ->where('subscription_number', $normalized)
            ->first();

        if (! $subscriber) {
            $reply('رقم الاشتراك غير موجود. تأكد من الرقم (مثال: MADD-000003) وأعد الإرسال، أو اضغط زر التمديد من جديد.');

            return;
        }

        $state = $this->portal->selfExtendState($subscriber);
        if (! ($state['can_self_extend'] ?? false)) {
            $session->update(['state' => WhatsappChatSession::STATE_IDLE, 'expires_at' => null]);
            $reply((string) ($state['message'] ?? 'لا يمكن تفعيل فترة السماح حالياً.'));

            return;
        }

        try {
            $user = $this->portalUsers->ensurePortalUser($subscriber);
            if (! $user) {
                $reply('تعذّر ربط حساب البوابة بهذا الاشتراك. تواصل مع الشركة.');

                return;
            }

            $result = $this->portal->performSelfExtend($user, $subscriber->fresh());
            $session->update(['state' => WhatsappChatSession::STATE_IDLE, 'expires_at' => null]);

            $reply(
                "تم تمديد الخط بنجاح لمدة {$result['days']} أيام.\n"
                ."رقم الاشتراك: {$result['subscriber']->subscription_number}\n"
                ."ساري حتى: {$result['new_end_date']}"
            );
        } catch (\Throwable $e) {
            $reply('تعذّر التمديد: '.($e->getMessage() ?: 'حاول لاحقاً أو تواصل مع الشركة.'));
        }
    }
}
