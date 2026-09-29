<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteConfigRecord extends Model
{
    protected $table = 'site_configs';

    protected $fillable = [
        'key',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
