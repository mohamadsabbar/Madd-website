<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    protected $fillable = [
        'name',
        'host',
        'api_port',
        'username',
        'password',
        'location',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'encrypted',
    ];

    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class);
    }

    public function mikrotikPppSecrets(): HasMany
    {
        return $this->hasMany(MikrotikPppSecret::class);
    }

    public function trafficSnapshots(): HasMany
    {
        return $this->hasMany(SubscriberTrafficSnapshot::class);
    }
}
