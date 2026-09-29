<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRenewalRequest extends Model
{
    protected $table = 'customer_renewal_requests';

    protected $fillable = [
        'subscriber_id',
        'user_id',
        'status',
        'note',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
