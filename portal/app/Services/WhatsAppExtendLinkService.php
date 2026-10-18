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
     * Path suffix only (for Meta dynamic URL button variable).
     */
    public function buttonSuffix(Subscriber $subscriber, string $stage): ?string
    {
        if (! $this->stageSupportsCta($stage)) {
            return null;
        }

        return $this->issueToken($subscriber, $stage)->token;
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
