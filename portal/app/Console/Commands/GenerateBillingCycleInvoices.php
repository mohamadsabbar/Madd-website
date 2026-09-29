<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\SubscriberBillingSyncService;
use Illuminate\Console\Command;

class GenerateBillingCycleInvoices extends Command
{
    protected $signature = 'billing:generate-cycle-invoices';

    protected $description = 'إنشاء فواتير الدورات غير المدفوعة لكل مشترك (مستحقات سابقة + دورة الشهر الحالي عند يوم الفوترة)';

    public function handle(SubscriberBillingSyncService $billingSync): int
    {
        $billingDay = (int) config('billing.billing_day', 5);
        $today = now()->format('Y-m-d');
        $createdTotal = 0;
        $subscribersProcessed = 0;

        Subscriber::query()
            ->whereNotNull('plan_id')
            ->whereNotNull('start_date')
            ->orderBy('id')
            ->chunkById(100, function ($subscribers) use ($billingSync, &$createdTotal, &$subscribersProcessed) {
                foreach ($subscribers as $subscriber) {
                    $createdTotal += $billingSync->syncMissingCycleInvoices($subscriber);
                    $subscribersProcessed++;
                }
            });

        if ($createdTotal > 0) {
            ActivityLogger::log(
                'system.billing_cycle_invoices',
                "مهمة الفوترة التلقائية ({$today}): أُنشئت {$createdTotal} فاتورة دورة لـ {$subscribersProcessed} مشتركاً.",
                ['meta' => [
                    'created_invoices' => $createdTotal,
                    'subscribers_processed' => $subscribersProcessed,
                    'billing_day' => $billingDay,
                ]]
            );
        }

        $this->info("تمت المعالجة: {$subscribersProcessed} مشترك — أُنشئت {$createdTotal} فاتورة جديدة.");

        return self::SUCCESS;
    }
}
