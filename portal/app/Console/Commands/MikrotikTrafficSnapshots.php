<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Console\Command;

class MikrotikTrafficSnapshots extends Command
{
    protected $signature = 'mikrotik:traffic-snapshots {router? : معرّف الراوتر أو اتركه لجميع الراوترات النشطة}';

    protected $description = 'تسجيل لقطات استهلاك الباندويث من جلسات PPP النشطة فقط (بدون مزامنة كاملة)';

    public function handle(MikrotikService $mikrotik): int
    {
        $id = $this->argument('router');
        $query = Router::query()->where('is_active', true);
        if ($id !== null) {
            $query->where('id', $id);
        }

        $routers = $query->get();
        if ($routers->isEmpty()) {
            $this->warn('لا يوجد راوترات.');

            return self::SUCCESS;
        }

        foreach ($routers as $router) {
            try {
                $n = $mikrotik->recordTrafficSnapshots($router);
                $this->info("راوتر {$router->name} ({$router->id}): لقطات {$n}");
            } catch (\Throwable $e) {
                $this->error("راوتر {$router->name}: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
