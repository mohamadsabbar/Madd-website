<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscriber;
use App\Models\WhatsappReminderLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp reminders keyed to subscription end_date + open unpaid invoices.
 * Failures never throw into MikroTik / billing suspend flows.
 */
class WhatsAppSubscriptionReminderService
{
    public function __construct(
        private WhatsAppCloudService $whatsapp,
        private WhatsAppExtendLinkService $extendLinks,
    ) {
    }

    /**
     * @return array{
     *   dry_run: bool,
     *   candidates: int,
     *   sent: int,
     *   failed: int,
     *   skipped: int,
     *   rows: list<array<string, mixed>>
     * }
     */
    public function process(bool $dryRun = false): array
    {
        $today = Carbon::today();
        // تذكيرات قبل الانتهاء فقط (3 أيام / يوم). رسالة الفصل والدفع من أحداث منفصلة.
        $beforeDays = array_values(array_unique(array_map(
            'intval',
            (array) config('services.whatsapp.before_days', [3, 1])
        )));

        $stats = [
            'dry_run' => $dryRun,
            'candidates' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'rows' => [],
        ];

        $subscribers = Subscriber::query()
            ->whereNotNull('end_date')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereHas('invoices', fn ($q) => $q->open())
            ->with(['invoices' => fn ($q) => $q->open()->orderBy('due_date')->orderBy('id')])
            ->get();

        foreach ($subscribers as $subscriber) {
            $invoice = $this->pickOpenInvoice($subscriber);
            if (! $invoice) {
                continue;
            }

            $daysUntil = (int) $today->diffInDays($subscriber->end_date->copy()->startOfDay(), false);

            if (! in_array($daysUntil, $beforeDays, true) || $daysUntil <= 0) {
                continue;
            }

            $stage = $daysUntil === 1
                ? WhatsappReminderLog::STAGE_BEFORE_1
                : ($daysUntil === 3 ? WhatsappReminderLog::STAGE_BEFORE_3 : 'before_'.$daysUntil);
            $periodKey = $subscriber->end_date->toDateString();

            $stats['candidates']++;
            $result = $this->attemptSend($subscriber, $invoice, $stage, $periodKey, $dryRun);
            $stats[$result['bucket']]++;
            $stats['rows'][] = $result['row'];
        }

        return $stats;
    }

    private function pickOpenInvoice(Subscriber $subscriber): ?Invoice
    {
        foreach ($subscriber->invoices as $invoice) {
            if (in_array($invoice->status, ['cancelled'], true)) {
                continue;
            }
            if ($invoice->remainingBalance() > 0.009) {
                return $invoice;
            }
        }

        return null;
    }

    /**
     * @return array{bucket: string, row: array<string, mixed>}
     */
    private function attemptSend(
        Subscriber $subscriber,
        Invoice $invoice,
        string $stage,
        string $periodKey,
        bool $dryRun
    ): array {
        $phone = (string) $subscriber->phone;
        $normalized = $this->whatsapp->normalizePhone($phone);
        if ($normalized === '') {
            return [
                'bucket' => 'skipped',
                'row' => $this->row($subscriber, $invoice, $stage, 'skipped', 'رقم هاتف غير صالح'),
            ];
        }

        $template = $this->templateForStage($stage);
        if ($template === '') {
            return [
                'bucket' => 'skipped',
                'row' => $this->row($subscriber, $invoice, $stage, 'skipped', 'اسم القالب غير مضبوط في .env'),
            ];
        }

        $params = [
            (string) $subscriber->full_name,
            (string) ($invoice->invoice_number ?: $invoice->id),
            number_format((float) $invoice->remainingBalance(), 2, '.', ''),
            (string) ($subscriber->end_date?->toDateString() ?: $invoice->due_date?->toDateString() ?: ''),
        ];

        $existing = WhatsappReminderLog::query()
            ->where('subscriber_id', $subscriber->id)
            ->where('stage', $stage)
            ->where('period_key', $periodKey)
            ->first();

        if ($existing && in_array($existing->status, ['sent', 'skipped'], true)) {
            return [
                'bucket' => 'skipped',
                'row' => $this->row($subscriber, $invoice, $stage, 'skipped', 'أُرسل سابقاً لهذه المرحلة'),
            ];
        }

        // Re-check balance (payment may have landed)
        $invoice->refresh();
        if ($invoice->status === 'paid' || $invoice->status === 'cancelled' || $invoice->remainingBalance() <= 0.009) {
            return [
                'bucket' => 'skipped',
                'row' => $this->row($subscriber, $invoice, $stage, 'skipped', 'الفاتورة لم تعد مستحقة'),
            ];
        }

        if ($dryRun) {
            $note = 'كان سيُرسل قالب '.$template;
            if ($this->extendLinks->stageSupportsCta($stage)) {
                $note .= $this->extendLinks->usesDynamicUrlButton()
                    ? ' + زر تمديد (ديناميكي)'
                    : ' + زر تمديد (ثابت في القالب)';
            }

            return [
                'bucket' => 'skipped',
                'row' => $this->row($subscriber, $invoice, $stage, 'dry-run', $note, $params),
            ];
        }

        if (! $this->whatsapp->isLive()) {
            return [
                'bucket' => 'skipped',
                'row' => $this->row($subscriber, $invoice, $stage, 'skipped', 'WhatsApp غير مفعّل'),
            ];
        }

        try {
            $log = $existing ?? WhatsappReminderLog::query()->create([
                'subscriber_id' => $subscriber->id,
                'invoice_id' => $invoice->id,
                'stage' => $stage,
                'period_key' => $periodKey,
                'status' => 'queued',
                'phone' => $normalized,
                'template_name' => $template,
            ]);
        } catch (\Throwable $e) {
            return [
                'bucket' => 'skipped',
                'row' => $this->row($subscriber, $invoice, $stage, 'skipped', 'تكرار متزامن / موجود مسبقاً'),
            ];
        }

        // Claim row under lock to reduce double-send
        $claimed = false;
        DB::transaction(function () use ($log, &$claimed) {
            $fresh = WhatsappReminderLog::query()->whereKey($log->id)->lockForUpdate()->first();
            if (! $fresh || in_array($fresh->status, ['sent', 'skipped'], true)) {
                return;
            }
            $fresh->status = 'queued';
            $fresh->attempted_at = now();
            $fresh->save();
            $claimed = true;
        });

        if (! $claimed) {
            return [
                'bucket' => 'skipped',
                'row' => $this->row($subscriber, $invoice, $stage, 'skipped', 'أُرسل سابقاً لهذه المرحلة'),
            ];
        }

        $buttonSuffix = $this->extendLinks->buttonSuffix($subscriber, $stage);

        try {
            $send = $this->whatsapp->sendTemplate($normalized, $template, $params, null, $buttonSuffix);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp reminder isolated failure', ['error' => $e->getMessage()]);
            $log->update([
                'status' => 'failed',
                'error_summary' => mb_substr($e->getMessage(), 0, 500),
                'attempted_at' => now(),
            ]);

            return [
                'bucket' => 'failed',
                'row' => $this->row($subscriber, $invoice, $stage, 'failed', $e->getMessage()),
            ];
        }

        if ($send['ok']) {
            $log->update([
                'status' => 'sent',
                'provider_message_id' => $send['message_id'],
                'template_name' => $template,
                'phone' => $normalized,
                'error_summary' => null,
                'attempted_at' => now(),
            ]);

            return [
                'bucket' => 'sent',
                'row' => $this->row($subscriber, $invoice, $stage, 'sent', null, $params, $send['message_id']),
            ];
        }

        $log->update([
            'status' => 'failed',
            'error_summary' => mb_substr((string) $this->whatsapp->getLastError(), 0, 500),
            'attempted_at' => now(),
        ]);

        return [
            'bucket' => 'failed',
            'row' => $this->row($subscriber, $invoice, $stage, 'failed', $this->whatsapp->getLastError()),
        ];
    }

    private function templateForStage(string $stage): string
    {
        if ($stage === WhatsappReminderLog::STAGE_BEFORE_1) {
            $specific = trim((string) config('services.whatsapp.template_before_1', ''));
            if ($specific !== '') {
                return $specific;
            }
        }

        if ($stage === WhatsappReminderLog::STAGE_BEFORE_3) {
            $specific = trim((string) config('services.whatsapp.template_before_3', ''));
            if ($specific !== '') {
                return $specific;
            }
        }

        return trim((string) config('services.whatsapp.template_before', ''));
    }

    /**
     * @param  list<string>|null  $params
     * @return array<string, mixed>
     */
    private function row(
        Subscriber $subscriber,
        Invoice $invoice,
        string $stage,
        string $status,
        ?string $reason = null,
        ?array $params = null,
        ?string $messageId = null
    ): array {
        return [
            'subscriber_id' => $subscriber->id,
            'name' => $subscriber->full_name,
            'phone' => $subscriber->phone,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'remaining' => $invoice->remainingBalance(),
            'end_date' => optional($subscriber->end_date)->toDateString(),
            'stage' => $stage,
            'status' => $status,
            'reason' => $reason,
            'params' => $params,
            'message_id' => $messageId,
        ];
    }
}
