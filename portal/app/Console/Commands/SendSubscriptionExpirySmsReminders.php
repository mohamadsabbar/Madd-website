<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\BillingSmsService;
use App\Services\FcmPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendSubscriptionExpirySmsReminders extends Command
{
    protected $signature = 'billing:send-expiry-reminders';

    protected $description = 'إرسال تذكيرات SMS وPush للمشتركين قبل انتهاء اشتراكهم بناءً على end_date';

    public function handle(BillingSmsService $sms, FcmPushService $fcm): int
    {
        $today = now()->startOfDay();
        $reminderDays = array_values(array_unique(array_map(
            'intval',
            (array) config('billing.expiry_reminder_days_before', [1])
        )));

        $smsLive = $sms->isLive();
        $fcmLive = $fcm->isConfigured();

        if (! $smsLive && ! $fcmLive) {
            $this->warn('SMS وFCM غير مفعّلين — تخطّي تذكيرات انتهاء الاشتراك.');

            return self::SUCCESS;
        }

        $template = (string) config('billing.sms_messages.expiry_reminder', '');
        $company = (string) config('billing.company_name', '');

        $allCandidates = Subscriber::query()
            ->with('user')
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->get();

        $sentSms = 0;
        $sentPush = 0;
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

            $title = 'تذكير انتهاء الاشتراك';
            $anyOk = false;

            if ($smsLive && $subscriber->phone) {
                if ($sms->send((string) $subscriber->phone, $message)) {
                    $sentSms++;
                    $anyOk = true;
                } else {
                    $failed++;
                    $failures[] = [
                        'subscriber_id' => $subscriber->id,
                        'channel' => 'sms',
                        'error' => $sms->getLastError(),
                    ];
                }
            }

            if ($fcmLive && $subscriber->user) {
                $n = $fcm->sendToUser(
                    $subscriber->user,
                    $title,
                    $message,
                    [
                        'type' => 'expiry',
                        'days' => (string) $daysUntilExpiry,
                        'end_date' => (string) $subscriber->end_date->toDateString(),
                    ]
                );
                if ($n > 0) {
                    $sentPush += $n;
                    $anyOk = true;
                }
            }

            if ($anyOk) {
                Cache::put($cacheKey, true, now()->addDays(2));
            }
        }

        ActivityLogger::log(
            'sms.expiry_reminder_batch',
            "تذكيرات انتهاء الاشتراك: SMS {$sentSms}، Push {$sentPush}، فشل {$failed}.",
            [
                'severity' => $failed === 0
                    ? \App\Models\ActivityLog::SEVERITY_SUCCESS
                    : ($sentSms === 0 && $sentPush === 0 ? \App\Models\ActivityLog::SEVERITY_ERROR : \App\Models\ActivityLog::SEVERITY_WARNING),
                'meta' => [
                    'days_before' => $reminderDays,
                    'sms_success' => $sentSms,
                    'push_success' => $sentPush,
                    'fail' => $failed,
                    'eligible_today' => $eligible,
                    'candidate_count' => $allCandidates->count(),
                    'failures' => array_slice($failures, 0, 50),
                ],
            ]
        );

        $this->info("تذكيرات انتهاء: SMS={$sentSms} Push={$sentPush} (مستحقون اليوم: {$eligible}).");

        return self::SUCCESS;
    }
}
