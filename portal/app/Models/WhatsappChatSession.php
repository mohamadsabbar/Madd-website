<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappChatSession extends Model
{
    public const STATE_IDLE = 'idle';

    public const STATE_MENU = 'menu';

    public const STATE_AWAIT_SUBSCRIPTION_NUMBER = 'await_subscription_number';

    /** انتظار رقم الاشتراك لفحص حالة الخط */
    public const STATE_AWAIT_CHECK_LINE = 'await_check_line';

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
