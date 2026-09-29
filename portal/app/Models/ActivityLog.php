<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'type',
        'severity',
        'actor_id',
        'actor_name',
        'actor_role',
        'subject_type',
        'subject_id',
        'subject_label',
        'phone',
        'amount',
        'message',
        'meta',
        'ip',
    ];

    protected $casts = [
        'meta' => 'array',
        'amount' => 'decimal:2',
    ];

    public const SEVERITY_SUCCESS = 'success';
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeOfType(Builder $q, string|array $types): Builder
    {
        return $q->whereIn('type', (array) $types);
    }

    public function scopeOfSeverity(Builder $q, string|array $severities): Builder
    {
        return $q->whereIn('severity', (array) $severities);
    }

    /**
     * فئة عريضة للتصنيف في الواجهة (sms, payment, subscriber, system…)
     */
    public function getCategoryAttribute(): string
    {
        return explode('.', $this->type)[0] ?? $this->type;
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_SUCCESS => 'dg-badge dg-badge--done',
            self::SEVERITY_WARNING => 'dg-badge dg-badge--wait',
            self::SEVERITY_ERROR => 'dg-badge dg-badge--way',
            default => 'dg-badge',
        };
    }
}
