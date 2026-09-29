<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Str;

class SubscriberManualDebtService
{
    public function addManualDebt(Subscriber $subscriber, User $admin, float $amount, ?string $notes = null): Invoice
    {
        $subscriber->loadMissing('plan');
        if (! $subscriber->plan) {
            throw new \RuntimeException('لا توجد باقة مرتبطة — أضف باقة للمشترك أولاً.');
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \RuntimeException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        $invoice = Invoice::create([
            'invoice_number' => 'INV-DEBT-'.now()->format('YmdHis').'-'.$subscriber->id.'-'.Str::lower(Str::random(4)),
            'subscriber_id' => $subscriber->id,
            'plan_id' => $subscriber->plan_id,
            'created_by' => $admin->id,
            'amount' => $amount,
            'due_date' => now()->toDateString(),
            'status' => 'unpaid',
            'notes' => $notes !== null && trim($notes) !== ''
                ? trim($notes)
                : 'دين يدوي — مُسجَّل من الإدارة',
        ]);

        ActivityLogger::success('subscriber.manual_debt', "إضافة دين يدوي للمشترك {$subscriber->full_name}: ".number_format($amount, 2).' شيكل.', [
            'subject' => $subscriber,
            'phone' => $subscriber->phone,
            'amount' => $amount,
            'meta' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'notes' => $invoice->notes,
            ],
        ]);

        return $invoice;
    }
}
