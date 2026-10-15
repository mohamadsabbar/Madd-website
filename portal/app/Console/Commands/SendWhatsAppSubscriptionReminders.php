<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use App\Services\WhatsAppSubscriptionReminderService;
use Illuminate\Console\Command;

class SendWhatsAppSubscriptionReminders extends Command
{
    protected $signature = 'billing:send-whatsapp-reminders
                            {--dry-run : اعرض المرشحين دون إرسال فعلي}';

    protected $description = 'تذكيرات WhatsApp لاشتراكات بفاتورة غير مدفوعة (قبل 3 أيام / يوم / بعد الانتهاء)';

    public function handle(WhatsAppSubscriptionReminderService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $result = $service->process($dryRun);
        } catch (\Throwable $e) {
            // Isolate from billing/MikroTik — never rethrow into scheduler chain
            $this->error('WhatsApp reminders failed in isolation: '.$e->getMessage());
            ActivityLogger::log(
                'whatsapp.reminder_batch',
                'فشل دفعة تذكيرات WhatsApp (معزول).',
                [
                    'severity' => \App\Models\ActivityLog::SEVERITY_ERROR,
                    'meta' => ['error' => $e->getMessage(), 'dry_run' => $dryRun],
                ]
            );

            return self::SUCCESS;
        }

        foreach (array_slice($result['rows'], 0, 100) as $row) {
            $this->line(sprintf(
                '[%s] #%s %s | فاتورة %s | %s | %s',
                $row['stage'],
                $row['subscriber_id'],
                $row['name'],
                $row['invoice_number'] ?? $row['invoice_id'],
                $row['status'],
                $row['reason'] ?? ''
            ));
        }

        $this->info(sprintf(
            'WhatsApp reminders%s: candidates=%d sent=%d failed=%d skipped=%d',
            $dryRun ? ' (dry-run)' : '',
            $result['candidates'],
            $result['sent'],
            $result['failed'],
            $result['skipped']
        ));

        ActivityLogger::log(
            'whatsapp.reminder_batch',
            sprintf(
                'تذكيرات WhatsApp%s: أُرسل %d، فشل %d، تخطي %d (مرشحون %d).',
                $dryRun ? ' dry-run' : '',
                $result['sent'],
                $result['failed'],
                $result['skipped'],
                $result['candidates']
            ),
            [
                'severity' => $result['failed'] === 0
                    ? \App\Models\ActivityLog::SEVERITY_SUCCESS
                    : ($result['sent'] === 0 ? \App\Models\ActivityLog::SEVERITY_WARNING : \App\Models\ActivityLog::SEVERITY_WARNING),
                'meta' => [
                    'dry_run' => $dryRun,
                    'candidates' => $result['candidates'],
                    'sent' => $result['sent'],
                    'failed' => $result['failed'],
                    'skipped' => $result['skipped'],
                    'sample' => array_slice($result['rows'], 0, 30),
                ],
            ]
        );

        return self::SUCCESS;
    }
}
