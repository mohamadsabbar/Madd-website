<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'is_active',
        'commission_percent',
        'monthly_collection_target',
        'commission_paid_total',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'commission_percent' => 'decimal:2',
            'monthly_collection_target' => 'decimal:2',
            'commission_paid_total' => 'decimal:2',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Payment::class, 'collected_by');
    }

    /**
     * تسليمات نقد الوكيل للشركة (تسوية مع الإدارة).
     */
    public function agentSettlements(): HasMany
    {
        return $this->hasMany(AgentSettlement::class, 'agent_id');
    }

    /**
     * سجل المشترك المرتبط بحساب بوابة العميل (دور subscriber).
     */
    public function subscriberRecord(): HasOne
    {
        return $this->hasOne(Subscriber::class);
    }
}

