<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappChatSession extends Model
{
    public const STATE_IDLE = 'idle';

    public const STATE_AWAIT_SUBSCRIPTION_NUMBER = 'await_subscription_number';

    protected $fillable = [
        'phone',
        'state',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
