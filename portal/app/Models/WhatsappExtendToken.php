<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappExtendToken extends Model
{
    protected $fillable = [
        'token',
        'subscriber_id',
        'stage',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
