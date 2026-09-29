<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteLead extends Model
{
    protected $fillable = [
        'type',
        'status',
        'name',
        'phone',
        'email',
        'city',
        'area',
        'plan_id',
        'plan_name',
        'message',
        'meta',
        'source',
        'ip',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
