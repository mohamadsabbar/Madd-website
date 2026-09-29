<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriberTrafficSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'router_id',
        'subscriber_id',
        'ppp_username',
        'rx_bytes_total',
        'tx_bytes_total',
        'delta_rx',
        'delta_tx',
        'rx_rate_bps',
        'tx_rate_bps',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }
}
