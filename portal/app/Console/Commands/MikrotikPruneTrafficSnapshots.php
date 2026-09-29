<?php

namespace App\Console\Commands;

use App\Models\SubscriberTrafficSnapshot;
use Illuminate\Console\Command;

class MikrotikPruneTrafficSnapshots extends Command
{
    protected $signature = 'mikrotik:prune-traffic-snapshots';

    protected $description = 'حذف لقطات الاستهلاك الأقدم من فترة الاحتفاظ (MIKROTIK_TRAFFIC_RETENTION_DAYS)';

    public function handle(): int
    {
        $days = (int) config('mikrotik.traffic_snapshots_retention_days', 90);
        $cutoff = now()->subDays($days);

        $deleted = SubscriberTrafficSnapshot::query()->where('recorded_at', '<', $cutoff)->delete();

        $this->info("حُذف {$deleted} سجل أقدم من {$days} يوماً.");

        return self::SUCCESS;
    }
}
