<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    /**
     * @var list<string>
     */
    protected $hidden = [
        'pppoe_password',
    ];

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'address',
        'pppoe_username',
        'pppoe_password',
        'plan_id',
        'monthly_price',
        'router_id',
        'start_date',
        'end_date',
        'status',
        'is_online',
        'suspended_at',
        'self_extend_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_online' => 'boolean',
        'suspended_at' => 'datetime',
        'monthly_price' => 'decimal:2',
        'self_extend_count' => 'integer',
    ];

    /**
     * المبلغ المستخدم في الفواتير لباقة معيّنة: السعر المتفق للمشترك إن وُجد وكانت الباقة هي الحالية، وإلا سعر الباقة في الكتالوج.
     */
    public function amountForInvoicedPlan(Plan $plan): float
    {
        $this->loadMissing('plan');

        if ((int) $this->plan_id !== (int) $plan->id) {
            return round((float) $plan->price, 2);
        }

        if ($this->monthly_price !== null) {
            return round((float) $this->monthly_price, 2);
        }

        return round((float) $plan->price, 2);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function renewalRequests(): HasMany
    {
        return $this->hasMany(CustomerRenewalRequest::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function trafficSnapshots(): HasMany
    {
        return $this->hasMany(SubscriberTrafficSnapshot::class);
    }

    /**
     * يوم الشهر المحدد في الإعدادات (افتراضي 5) لانتهاء الدورة.
     */
    public static function subscriptionCycleEndDay(): int
    {
        return max(1, min(31, (int) config('mikrotik.subscription_cycle_end_day', 5)));
    }

    /**
     * تاريخ انتهاء الاشتراك: اليوم المحدد من الشهر التالي للشهر الذي يقع فيه `$date`.
     * مثال: 2026-03-05 → 2026-04-05 عندما يكون اليوم 5.
     */
    public static function cycleEndDateAfterDate(Carbon|string $date): Carbon
    {
        $day = static::subscriptionCycleEndDay();

        return Carbon::parse($date)
            ->startOfDay()
            ->startOfMonth()
            ->addMonth()
            ->day($day);
    }
}
