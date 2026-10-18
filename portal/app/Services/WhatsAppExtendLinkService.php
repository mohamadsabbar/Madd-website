<?php

namespace App\Services;

use App\Models\Subscriber;
use App\Models\WhatsappExtendToken;
use Illuminate\Support\Str;

/**
 * One-time tokens for WhatsApp CTA "مدد خطك 3 أيام" → /wa/extend/{token}.
 */
class WhatsAppExtendLinkService
{
    /**
     * Stages that attach a URL button when the Meta template includes one.
     *
     * @return list<string>
     */
    public function ctaStages(): array
    {
        $raw = explode(',', (string) config('services.whatsapp.cta_extend_stages', 'before_1,suspend'));

        return array_values(array_filter(array_map(
            fn (string $s) => trim($s),
            $raw
        ), fn (string $s) => $s !== ''));
    }

    public function stageSupportsCta(string $stage): bool
    {
        return in_array($stage, $this->ctaStages(), true);
    }

    public function issueToken(Subscriber $subscriber, string $stage): WhatsappExtendToken
    {
        $hours = max(1, (int) config('services.whatsapp.extend_token_ttl_hours', 72));

        return WhatsappExtendToken::query()->create([
            'token' => Str::lower(Str::random(32)),
            'subscriber_id' => $subscriber->id,
            'stage' => $stage,
            'expires_at' => now()->addHours($hours),
        ]);
    }

    /**
     * Whether Meta URL button expects a dynamic {{1}} suffix parameter.
     * Static buttons (Chatwoot-style / fixed URL) must NOT send button parameters.
     */
    public function usesDynamicUrlButton(): bool
    {
        return filter_var(config('services.whatsapp.cta_url_dynamic', false), FILTER_VALIDATE_BOOL);
    }

    /**
     * Path suffix only (for Meta dynamic URL button variable).
     * Returns null for static URL buttons so Cloud API does not reject the send.
     */
    public function buttonSuffix(Subscriber $subscriber, string $stage): ?string
    {
        if (! $this->stageSupportsCta($stage) || ! $this->usesDynamicUrlButton()) {
            return null;
        }

        return $this->issueToken($subscriber, $stage)->token;
    }

    /**
     * Find subscriber by phone in common local / E.164 shapes.
     */
    public function findSubscriberByPhone(string $phone): ?Subscriber
    {
        $wa = app(WhatsAppCloudService::class);
        $normalized = $wa->normalizePhone($phone);
        if ($normalized === '') {
            return null;
        }

        $candidates = array_values(array_unique(array_filter([
            $normalized,
            $phone,
            preg_replace('/\D+/', '', $phone) ?: null,
            str_starts_with($normalized, '970') ? '0'.substr($normalized, 3) : null,
            str_starts_with($normalized, '972') ? '0'.substr($normalized, 3) : null,
            str_starts_with($normalized, '970') ? substr($normalized, 3) : null,
            str_starts_with($normalized, '972') ? substr($normalized, 3) : null,
        ])));

        return Subscriber::query()
            ->where(function ($q) use ($candidates) {
                foreach ($candidates as $candidate) {
                    $q->orWhere('phone', $candidate);
                }
            })
            ->orderByDesc('id')
            ->first();
    }

    public function findValid(string $token): ?WhatsappExtendToken
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 64) {
            return null;
        }

        /** @var WhatsappExtendToken|null $row */
        $row = WhatsappExtendToken::query()
            ->where('token', $token)
            ->with('subscriber')
            ->first();

        if (! $row || $row->isExpired()) {
            return null;
        }

        return $row;
    }

    public function markUsed(WhatsappExtendToken $row): void
    {
        if ($row->used_at) {
            return;
        }

        $row->used_at = now();
        $row->save();
    }

    public function absoluteUrl(string $token): string
    {
        return url('/wa/extend/'.$token);
    }
}
