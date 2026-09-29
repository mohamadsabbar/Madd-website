<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\MikrotikService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SuspendExpiredSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:suspend-expired-subscribers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suspend expired subscribers and disable them on MikroTik';

    /**
     * Execute the console command.
     */
    public function handle(MikrotikService $mikrotikService): int
    {
        $today = Carbon::today();
        $expiredSubscribers = Subscriber::where('status', '!=', 'suspended')
            ->whereDate('end_date', '<', $today)
            ->get();

        foreach ($expiredSubscribers as $subscriber) {
            $subscriber->update([
                'status' => 'suspended',
                'suspended_at' => now(),
            ]);

            try {
                $mikrotikService->disablePppoeUser($subscriber);
                $mtNote = 'تم تعطيل PPPoE.';
            } catch (\Throwable $e) {
                $mtNote = 'تعذّر تعطيل PPPoE: '.$e->getMessage();
            }

            ActivityLogger::warning(
                'subscriber.auto_suspended_expired',
                "تعليق تلقائي (انتهاء الاشتراك): {$subscriber->full_name} — تاريخ الانتهاء ".optional($subscriber->end_date)->format('Y-m-d').". {$mtNote}",
                [
                    'subject' => $subscriber,
                    'phone' => $subscriber->phone,
                    'meta' => [
                        'reason' => 'expired',
                        'end_date' => optional($subscriber->end_date)->toDateString(),
                    ],
                ]
            );
        }

        if ($expiredSubscribers->count() > 0) {
            ActivityLogger::log(
                'system.auto_suspend_expired',
                "تشغيل المهمة المجدولة: تم تعليق {$expiredSubscribers->count()} مشتركاً منتهي الاشتراك.",
                [
                    'meta' => ['count' => $expiredSubscribers->count()],
                ]
            );
        }

        $this->info("Suspended {$expiredSubscribers->count()} expired subscribers.");

        return self::SUCCESS;
    }
}
