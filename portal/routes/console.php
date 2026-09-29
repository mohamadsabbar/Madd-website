<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:suspend-expired-subscribers')->dailyAt('00:30');

Schedule::command('billing:send-reminders')->dailyAt(config('billing.reminder_time', '09:00'));
Schedule::command('billing:send-expiry-reminders')->dailyAt(config('billing.expiry_reminder_time', '10:00'));

Schedule::command('billing:generate-cycle-invoices')->dailyAt(config('billing.generate_invoices_time', '00:01'));
Schedule::command('billing:suspend-unpaid')->dailyAt(config('billing.suspend_unpaid_time', '00:05'));

// الفترة من MIKROTIK_SYNC_INTERVAL_MINUTES في .env → config/mikrotik.php (1 = كل دقيقة، 5 = */5 … من كل ساعة)
$mikrotikSyncMin = max(1, min(59, (int) config('mikrotik.sync_interval_minutes', 5)));
if ($mikrotikSyncMin === 1) {
    Schedule::command('mikrotik:sync-sessions --secrets')->everyMinute();
} else {
    Schedule::command('mikrotik:sync-sessions --secrets')->cron("*/{$mikrotikSyncMin} * * * *");
}

Schedule::command('mikrotik:prune-traffic-snapshots')->dailyAt('03:15');
