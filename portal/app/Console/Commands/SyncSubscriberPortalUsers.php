<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\SubscriberPortalUserService;
use Illuminate\Console\Command;

class SyncSubscriberPortalUsers extends Command
{
    protected $signature = 'subscribers:sync-portal-users';

    protected $description = 'إنشاء/ربط حسابات تطبيق العملاء (هاتف + كلمة مرور افتراضية) لكل المشتركين الذين لا يملكون ربطاً بعد';

    public function handle(SubscriberPortalUserService $portalUserService): int
    {
        $subs = Subscriber::query()->whereNull('user_id')->orderBy('id')->get();
        $ok = 0;
        $skip = 0;
        $err = 0;

        foreach ($subs as $sub) {
            try {
                $user = $portalUserService->ensurePortalUser($sub);
                if ($user === null) {
                    $skip++;
                    $this->line("تخطي #{$sub->id} (هاتف استيراد أو فارغ)");
                } else {
                    $ok++;
                    $this->info("تم ربط المشترك #{$sub->id} → مستخدم #{$user->id}");
                }
            } catch (\Throwable $e) {
                $err++;
                $this->error("#{$sub->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("مكتمل: مربوط {$ok}، متخطى {$skip}، أخطاء {$err}");

        return $err > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
