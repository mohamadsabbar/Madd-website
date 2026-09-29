<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'subscriber_id',
        'plan_id',
        'created_by',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'date',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }

    public function paidAmount(): float
    {
        return round((float) $this->payments()->sum('amount'), 2);
    }

    public function remainingBalance(): float
    {
        return max(0, round((float) $this->amount - $this->paidAmount(), 2));
    }

    public function refreshPaymentStatus(): void
    {
        $paid = $this->paidAmount();
        $total = (float) $this->amount;

        if ($paid <= 0) {
            $this->update(['status' => 'unpaid', 'paid_at' => null]);

            return;
        }

        if ($paid >= $total - 0.009) {
            $this->update(['status' => 'paid', 'paid_at' => now()->toDateString()]);

            return;
        }

        $this->update(['status' => 'partial', 'paid_at' => null]);
    }
}
