<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\BillingSmsService;
use App\Support\BillingCalendar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendBillingSmsReminders extends Command
{
    protected $signature = 'billing:send-reminders';

    protected $description = 'إرسال تذكيرات SMS قبل يوم الفوترة لمن لديهم مستحقات غير مدفوعة';

    public function handle(BillingSmsService $sms): int
    {
        $billingDay = (int) config('billing.billing_day', 5);
        $today = now()->startOfDay();
        $daysUntil = BillingCalendar::daysUntilNextBilling($today, $billingDay);
        $reminderDays = config('billing.reminder_days_before', [3, 1]);

        if (! in_array($daysUntil, $reminderDays, true)) {
            $this->info("لا إرسال اليوم (أيام متبقية حتى الفوترة: {$daysUntil}).");

            return self::SUCCESS;
        }

        if (! $sms->isLive()) {
            $this->warn('SMS غير مفعّل أو ناقص الإعدادات — تخطّي التذكيرات. راجع BILLING_SMS_* في .env');

            return self::SUCCESS;
        }

        $due = BillingCalendar::nextBillingDate($today->copy(), $billingDay);
        $dueFormatted = $due->locale('ar')->translatedFormat('j F Y');

        $template = (string) config('billing.sms_messages.reminder');
        $company = (string) config('billing.company_name', '');

        $subscribers = Subscriber::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereHas('invoices', fn ($q) => $q->where('status', 'unpaid'))
            ->withSum(['invoices' => fn ($q) => $q->where('status', 'unpaid')], 'amount')
            ->get();

        $sent = 0;
        $failed = 0;
        $failures = [];
        foreach ($subscribers as $subscriber) {
            $amount = (float) ($subscriber->invoices_sum_amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $cacheKey = 'billing_sms_reminder:'.$subscriber->id.':'.$today->format('Y-m-d').':'.$daysUntil;
            if (Cache::has($cacheKey)) {
                continue;
            }

            $message = str_replace(
                ['{name}', '{amount}', '{due_date}', '{company}'],
                [$subscriber->full_name, number_format($amount, 2), $dueFormatted, $company],
                $template
            );

            if ($sms->send($subscriber->phone, $message)) {
                Cache::put($cacheKey, true, now()->addDays(2));
                $sent++;
            } else {
                $failed++;
                $failures[] = [
                    'subscriber_id' => $subscriber->id,
                    'name' => $subscriber->full_name,
                    'phone' => $subscriber->phone,
                    'error' => $sms->getLastError(),
                ];
            }
        }

        ActivityLogger::log(
            'sms.reminder_batch',
            "تذكيرات الفوترة (قبل {$daysUntil} يوم): نجح {$sent}، فشل {$failed}.",
            [
                'severity' => $failed === 0
                    ? \App\Models\ActivityLog::SEVERITY_SUCCESS
                    : ($sent === 0 ? \App\Models\ActivityLog::SEVERITY_ERROR : \App\Models\ActivityLog::SEVERITY_WARNING),
                'meta' => [
                    'days_until_billing' => $daysUntil,
                    'due_date' => $due->toDateString(),
                    'success' => $sent,
                    'fail' => $failed,
                    'total_with_unpaid' => $subscribers->count(),
                    'failures' => array_slice($failures, 0, 50),
                ],
            ]
        );

        $this->info("تمت محاولة إرسال {$sent} تذكيراً (من أصل {$subscribers->count()} مشتركاً بمستحقات).");

        return self::SUCCESS;
    }
}
