<?php

namespace App\Console\Commands;

use App\Services\GenieAcsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncGenieAcsWifiProvision extends Command
{
    protected $signature = 'acs:sync-wifi-provision';

    protected $description = 'يثبّت في GenieACS سكربت سحب اسم الشبكة وكلمة المرور والأجهزة المتصلة (Hikvision DS-3WR12C وباقي الراوترات)';

    public function handle(GenieAcsService $acs): int
    {
        if (! $acs->isConfigured()) {
            $this->warn('GenieACS غير مضبوط (GENIEACS_NBI_URL).');

            return self::SUCCESS;
        }

        $acs->syncWifiProvision();
        Cache::forget('genieacs.wifi_provision_synced');
        Cache::put('genieacs.wifi_provision_synced', true, 21600);
        $this->info('تم تثبيت preset/provision: wifi-params');

        return self::SUCCESS;
    }
}
