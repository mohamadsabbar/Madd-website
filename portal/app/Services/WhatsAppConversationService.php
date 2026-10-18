<?php

namespace App\Services;

use App\Models\Subscriber;
use App\Models\WhatsappChatSession;
use Illuminate\Support\Facades\Log;

/**
 * Chatbot flow (Chatwoot bot or Meta webhook):
 * - first message → template button_start_message
 * - الدعم الفني / افحص خطك → check-line template + ask subscription number → status
 * - مدد خطك → ask subscription number → self-extend
 * - المبيعات → sales text
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

        $session = WhatsappChatSession::query()->where('phone', $phone)->first();

        if ($session && ! $session->isExpired()) {
            if ($session->state === WhatsappChatSession::STATE_AWAIT_SUBSCRIPTION_NUMBER) {
                $this->completeExtendWithSubscriptionNumber($phone, $session, $text, $reply);

                return;
            }

            if ($session->state === WhatsappChatSession::STATE_AWAIT_CHECK_LINE) {
                $this->completeCheckLine($phone, $session, $text, $reply);

                return;
            }
        } elseif ($session && $session->isExpired()) {
            $session->update(['state' => WhatsappChatSession::STATE_IDLE, 'expires_at' => null]);
        }

        if ($this->isExtendIntent($text, $raw)) {
            $this->beginAwaitSubscriptionNumber($phone, $reply);

            return;
        }

        if ($this->isSupportIntent($text)) {
            $this->beginCheckLine($phone, $reply);

            return;
        }

        if ($this->isSalesIntent($text)) {
            $reply((string) config(
                'services.whatsapp.sales_reply_text',
                "للمبيعات والتجديد تواصل معنا:\nالهاتف: 0598624583\nأو عبر فريق المبيعات في هذه المحادثة."
            ));
            $this->touchSession($phone, WhatsappChatSession::STATE_MENU);

            return;
        }

        if ($this->isThanksOrBye($text)) {
            $reply('العفو! نحن بخدمتك دائماً. إذا احتجت شيئاً اكتب: الدعم الفني أو المبيعات.');
            $this->touchSession($phone, WhatsappChatSession::STATE_MENU);

            return;
        }

        // قائمة البداية فقط لأول تواصل أو بعد انتهاء الجلسة — لا تُعاد مع كل رسالة
        $inActiveMenu = $session
            && ! $session->isExpired()
            && $session->state === WhatsappChatSession::STATE_MENU;

        if ($inActiveMenu) {
            $reply('كيف نقدر نساعدك؟ اكتب: الدعم الفني أو المبيعات أو مدد خطك 3 أيام');

            return;
        }

        $this->sendStartMenu($phone, $reply);
    }

    private function sendStartMenu(string $phone, callable $reply): void
    {
        // نص جلسة مجاني أولاً (نافذة 24 ساعة بعد رسالة الزبون)، ثم القالب بالأزرار.
        $reply('أهلاً وسهلاً! شو حابب تعمل؟ اختار من الخيارات التالية:');

        $template = trim((string) config('services.whatsapp.template_start', 'button_start_message'));

        if ($template !== '' && $this->whatsapp->isLive()) {
            $result = $this->whatsapp->sendTemplate($phone, $template, []);
            if (! ($result['ok'] ?? false)) {
                Log::warning('Start menu template failed', [
                    'error' => $this->whatsapp->getLastError(),
                    'template' => $template,
                ]);
                $reply(
                    "اختر:\n"
                    ."• الدعم الفني\n"
                    ."• المبيعات\n"
                    ."• مدد خطك 3 أيام"
                );
            }
        }

        $this->touchSession($phone, WhatsappChatSession::STATE_MENU);
    }

    /**
     * @param  callable(string): void  $reply
     */
    private function beginCheckLine(string $phone, callable $reply): void
    {
        $template = trim((string) config('services.whatsapp.template_check_line', 'afhas_khatak'));
        $sent = false;

        if ($template !== '' && $this->whatsapp->isLive()) {
            $result = $this->whatsapp->sendTemplate($phone, $template, []);
            $sent = (bool) ($result['ok'] ?? false);
            if (! $sent) {
                Log::warning('Check-line template failed', [
                    'error' => $this->whatsapp->getLastError(),
                    'template' => $template,
                ]);
            }
        }

        $this->touchSession($phone, WhatsappChatSession::STATE_AWAIT_CHECK_LINE);

        if (! $sent) {
            $reply("لفحص خطك، أرسل رقم الاشتراك الآن.\nمثال: MADD-000003");
        }
    }

    /**
     * @param  callable(string): void  $reply
     */
    private function completeCheckLine(
        string $phone,
        WhatsappChatSession $session,
        string $rawNumber,
        callable $reply
    ): void {
        $normalized = Subscriber::normalizeSubscriptionNumber($rawNumber);
        $subscriber = Subscriber::query()
            ->where('subscription_number', $normalized)
            ->with(['plan', 'router'])
            ->first();

        if (! $subscriber) {
            $reply('رقم الاشتراك غير موجود. أعد الإرسال بالشكل الصحيح (مثال: MADD-000003).');

            return;
        }

        $session->update(['state' => WhatsappChatSession::STATE_MENU, 'expires_at' => $this->sessionExpiry()]);

        $expired = $this->portal->isSubscriptionExpired($subscriber);
        $status = $expired ? 'منتهٍ' : ($subscriber->status === 'suspended' ? 'معلّق / مفصول' : 'نشط');
        $online = $subscriber->is_online ? 'نعم' : 'لا';
        $plan = $subscriber->plan?->name ?? '—';
        $end = $subscriber->end_date?->toDateString() ?? '—';

        $reply(
            "نتيجة فحص الخط:\n"
            ."رقم الاشتراك: {$subscriber->subscription_number}\n"
            ."الاسم: {$subscriber->full_name}\n"
            ."الباقة: {$plan}\n"
            ."الحالة: {$status}\n"
            ."تاريخ الانتهاء: {$end}\n"
            ."متصل الآن: {$online}\n\n"
            .'للتمديد المجاني اكتب: مدد خطك 3 أيام'
        );
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

        if (str_contains($normalized, 'مدد خطك')) {
            return true;
        }

        $payload = (string) (
            data_get($raw, 'button.payload')
            ?: data_get($raw, 'interactive.button_reply.id')
            ?: data_get($raw, 'content_attributes.payload')
            ?: ''
        );

        return str_contains(mb_strtolower($payload), 'extend');
    }

    private function isSupportIntent(string $text): bool
    {
        $t = mb_strtolower(preg_replace('/\s+/u', ' ', $text) ?? $text);

        return str_contains($t, 'الدعم الفني')
            || str_contains($t, 'افحص خطك')
            || str_contains($t, 'فحص الخط')
            || str_contains($t, 'دعم فني');
    }

    private function isSalesIntent(string $text): bool
    {
        $t = mb_strtolower(preg_replace('/\s+/u', ' ', $text) ?? $text);

        return str_contains($t, 'المبيعات')
            || str_contains($t, 'مبيعات');
    }

    private function isThanksOrBye(string $text): bool
    {
        $t = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? $text));
        $t = preg_replace('/[!!.،,]+$/u', '', $t) ?? $t;

        foreach (['شكرا', 'شكراً', 'مشكور', 'يعطيك العافية', 'تسلم', '拜拜', 'مع السلامة', 'thanks', 'thank you', 'ok', 'تمام', 'اوك'] as $word) {
            if ($t === $word || str_starts_with($t, $word.' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  callable(string): void  $reply
     */
    private function beginAwaitSubscriptionNumber(string $phone, callable $reply): void
    {
        $this->touchSession($phone, WhatsappChatSession::STATE_AWAIT_SUBSCRIPTION_NUMBER);
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
            $session->update(['state' => WhatsappChatSession::STATE_MENU, 'expires_at' => $this->sessionExpiry()]);
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
            $session->update(['state' => WhatsappChatSession::STATE_MENU, 'expires_at' => $this->sessionExpiry()]);

            $reply(
                "تم تمديد الخط بنجاح لمدة {$result['days']} أيام.\n"
                ."رقم الاشتراك: {$result['subscriber']->subscription_number}\n"
                ."ساري حتى: {$result['new_end_date']}"
            );
        } catch (\Throwable $e) {
            $reply('تعذّر التمديد: '.($e->getMessage() ?: 'حاول لاحقاً أو تواصل مع الشركة.'));
        }
    }

    private function touchSession(string $phone, string $state): void
    {
        WhatsappChatSession::query()->updateOrCreate(
            ['phone' => $phone],
            [
                'state' => $state,
                'expires_at' => $this->sessionExpiry(),
            ]
        );
    }

    private function sessionExpiry(): \Carbon\CarbonInterface
    {
        $hours = max(1, (int) config('services.whatsapp.chat_session_ttl_hours', 6));

        return now()->addHours($hours);
    }
}
