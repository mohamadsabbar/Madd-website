<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\BillingSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendSubscriptionExpirySmsReminders extends Command
{
    protected $signature = 'billing:send-expiry-reminders';

    protected $description = 'إرسال تذكيرات SMS للمشتركين قبل انتهاء اشتراكهم بناءً على end_date';

    public function handle(BillingSmsService $sms): int
    {
        $today = now()->startOfDay();
        $reminderDays = array_values(array_unique(array_map(
            'intval',
            (array) config('billing.expiry_reminder_days_before', [1])
        )));

        if (! $sms->isLive()) {
            $this->warn('SMS غير مفعّل أو ناقص الإعدادات — تخطّي تذكيرات انتهاء الاشتراك.');

            return self::SUCCESS;
        }

        $template = (string) config('billing.sms_messages.expiry_reminder', '');
        $company = (string) config('billing.company_name', '');

        $allCandidates = Subscriber::query()
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        $sent = 0;
        $failed = 0;
        $eligible = 0;
        $failures = [];

        foreach ($allCandidates as $subscriber) {
            if (! $subscriber->end_date) {
                continue;
            }

            $daysUntilExpiry = (int) $today->diffInDays($subscriber->end_date->copy()->startOfDay(), false);
            if (! in_array($daysUntilExpiry, $reminderDays, false)) {
                continue;
            }

            $eligible++;

            $cacheKey = 'billing_sms_expiry_reminder:'.$subscriber->id.':'.$today->format('Y-m-d').':'.$daysUntilExpiry;
            if (Cache::has($cacheKey)) {
                continue;
            }

            $expiryFormatted = $subscriber->end_date->copy()->locale('ar')->translatedFormat('j F Y');
            $message = str_replace(
                ['{name}', '{days}', '{end_date}', '{company}'],
                [$subscriber->full_name, (string) $daysUntilExpiry, $expiryFormatted, $company],
                $template !== '' ? $template : 'تذكير ينتهي اشتراكك بعد {days} يوم بتاريخ {end_date}.'
            );

            if ($sms->send((string) $subscriber->phone, $message)) {
                Cache::put($cacheKey, true, now()->addDays(2));
                $sent++;
            } else {
                $failed++;
                $failures[] = [
                    'subscriber_id' => $subscriber->id,
                    'name' => $subscriber->full_name,
                    'phone' => $subscriber->phone,
                    'days_until_expiry' => $daysUntilExpiry,
                    'end_date' => optional($subscriber->end_date)->toDateString(),
                    'error' => $sms->getLastError(),
                ];
            }
        }

        ActivityLogger::log(
            'sms.expiry_reminder_batch',
            "تذكيرات انتهاء الاشتراك: نجح {$sent}، فشل {$failed}.",
            [
                'severity' => $failed === 0
                    ? \App\Models\ActivityLog::SEVERITY_SUCCESS
                    : ($sent === 0 ? \App\Models\ActivityLog::SEVERITY_ERROR : \App\Models\ActivityLog::SEVERITY_WARNING),
                'meta' => [
                    'days_before' => $reminderDays,
                    'success' => $sent,
                    'fail' => $failed,
                    'eligible_today' => $eligible,
                    'candidate_count' => $allCandidates->count(),
                    'failures' => array_slice($failures, 0, 50),
                ],
            ]
        );

        $this->info("تمت محاولة إرسال {$sent} تذكير انتهاء (من أصل {$eligible} مشتركاً مستحقاً اليوم).");

        return self::SUCCESS;
    }
}
