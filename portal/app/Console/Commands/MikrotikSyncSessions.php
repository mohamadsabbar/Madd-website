<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Console\Command;

class MikrotikSyncSessions extends Command
{
    protected $signature = 'mikrotik:sync-sessions {router? : معرّف الراوتر أو اتركه لجميع الراوترات النشطة} {--secrets : جلب أسرار PPPoE (/ppp/secret) وحفظها للتحليل}';

    protected $description = 'مزامنة جلسات PPPoE النشطة من MikroTik وتحديث حالة «متصل» للمشتركين';

    public function handle(MikrotikService $mikrotik): int
    {
        $id = $this->argument('router');
        $query = Router::query()->where('is_active', true);
        if ($id !== null) {
            $query->where('id', $id);
        }

        $routers = $query->get();
        if ($routers->isEmpty()) {
            $this->warn('لا يوجد راوترات للمزامنة.');

            return self::SUCCESS;
        }

        foreach ($routers as $router) {
            try {
                if ($this->option('secrets')) {
                    $stats = $mikrotik->syncRouter($router);
                } else {
                    $stats = $mikrotik->syncActiveSessions($router);
                }
                $snap = $stats['snapshots_stored'] ?? 0;
                $this->info("راوتر {$router->name} ({$router->id}): نشط {$stats['active_rows']} — مطابق {$stats['matched']} — متصلون {$stats['online']} — لقطات {$snap}");
                if (! empty($stats['secrets']) && is_array($stats['secrets'])) {
                    $sec = $stats['secrets'];
                    $this->line("  أسرار PPP: محفوظ {$sec['count']} — حذف يتيم {$sec['deleted_orphans']}");
                } elseif (! empty($stats['secrets_error'])) {
                    $this->warn('  أسرار PPP: '.$stats['secrets_error']);
                }
            } catch (\Throwable $e) {
                $this->error("راوتر {$router->name}: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
