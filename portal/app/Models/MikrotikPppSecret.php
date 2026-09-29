<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikPppSecret extends Model
{
    protected $fillable = [
        'router_id',
        'mikrotik_internal_id',
        'ppp_name',
        'profile',
        'service',
        'disabled',
        'comment',
        'subscriber_id',
        'synced_at',
    ];

    protected $casts = [
        'disabled' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
