<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\MikrotikService;
use Illuminate\Console\Command;

class SuspendUnpaidOnBillingDay extends Command
{
    protected $signature = 'billing:suspend-unpaid';

    protected $description = 'يوم الفوترة: قطع خدمة من لديهم فواتير غير مدفوعة (تعطيل PPPoE على المايكروتيك)';

    public function handle(MikrotikService $mikrotik): int
    {
        $billingDay = (int) config('billing.billing_day', 5);
        $today = now();

        if ((int) $today->format('j') !== $billingDay) {
            $this->info('ليوم الفوترة — لا إجراء.');

            return self::SUCCESS;
        }

        $subscribers = Subscriber::query()
            ->where('status', '!=', 'suspended')
            ->whereHas('invoices', fn ($q) => $q->where('status', 'unpaid'))
            ->withSum(['invoices' => fn ($q) => $q->where('status', 'unpaid')], 'amount')
            ->get();

        $count = 0;
        foreach ($subscribers as $subscriber) {
            $amount = (float) ($subscriber->invoices_sum_amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $subscriber->update([
                'status' => 'suspended',
                'suspended_at' => now(),
            ]);

            try {
                $mikrotik->disablePppoeUser($subscriber);
                $mtNote = 'تم تعطيل PPPoE.';
            } catch (\Throwable $e) {
                $mtNote = 'تعذّر تعطيل PPPoE: '.$e->getMessage();
            }
            $count++;

            ActivityLogger::warning(
                'subscriber.auto_suspended_unpaid',
                "قطع خدمة (مستحقات غير مدفوعة) في يوم الفوترة: {$subscriber->full_name} — المبلغ ".number_format($amount, 2)." شيكل. {$mtNote}",
                [
                    'subject' => $subscriber,
                    'phone' => $subscriber->phone,
                    'amount' => $amount,
                    'meta' => [
                        'reason' => 'unpaid_billing_day',
                        'unpaid_amount' => $amount,
                    ],
                ]
            );
        }

        if ($count > 0) {
            ActivityLogger::log(
                'system.auto_suspend_unpaid',
                "تشغيل المهمة المجدولة (يوم الفوترة): تم قطع {$count} مشتركاً لوجود مستحقات.",
                ['meta' => ['count' => $count]]
            );
        }

        $this->info("تم تعليق {$count} مشتركاً لوجود مستحقات غير مدفوعة (يوم الفوترة).");

        return self::SUCCESS;
    }
}
