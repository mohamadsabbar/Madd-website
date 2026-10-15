<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappReminderLog extends Model
{
    public const STAGE_BEFORE_3 = 'before_3';

    public const STAGE_BEFORE_1 = 'before_1';

    /** @deprecated use STAGE_SUSPEND — kept for older log rows */
    public const STAGE_AFTER = 'after';

    public const STAGE_SUSPEND = 'suspend';

    public const STAGE_PAID = 'paid';

    protected $fillable = [
        'subscriber_id',
        'invoice_id',
        'stage',
        'period_key',
        'status',
        'phone',
        'template_name',
        'provider_message_id',
        'error_summary',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
