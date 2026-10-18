<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscriber;
use App\Models\WhatsappReminderLog;
use Illuminate\Support\Facades\Log;

/**
 * Event-driven WhatsApp templates: suspend / payment.
 * Never throws to callers (MikroTik / billing stay isolated).
 */
class WhatsAppLifecycleNotifier
{
    public function __construct(
        private WhatsAppCloudService $whatsapp,
        private WhatsAppExtendLinkService $extendLinks,
    ) {
    }

    public function notifySuspended(Subscriber $subscriber, ?Invoice $invoice = null, string $reason = 'suspended'): void
    {
        $invoice ??= $this->primaryOpenInvoice($subscriber);
        $amount = $invoice ? number_format($invoice->remainingBalance(), 2, '.', '') : '0';
        $invoiceNo = $invoice?->invoice_number ?: ($invoice?->id ? (string) $invoice->id : '—');
        $endDate = $subscriber->end_date?->toDateString() ?: now()->toDateString();

        $this->sendOnce(
            subscriber: $subscriber,
            invoice: $invoice,
            stage: WhatsappReminderLog::STAGE_SUSPEND,
            periodKey: now()->toDateString().':'.$reason,
            template: (string) config('services.whatsapp.template_suspend', ''),
            params: [
                (string) $subscriber->full_name,
                $invoiceNo,
                $amount,
                $endDate,
            ],
            withExtendButton: true,
        );
    }

    public function notifyPaid(Subscriber $subscriber, Invoice $invoice, float $amount): void
    {
        $this->sendOnce(
            subscriber: $subscriber,
            invoice: $invoice,
            stage: WhatsappReminderLog::STAGE_PAID,
            periodKey: (string) $invoice->id.':'.now()->toDateString(),
            template: (string) config('services.whatsapp.template_paid', ''),
            params: [
                (string) $subscriber->full_name,
                (string) ($invoice->invoice_number ?: $invoice->id),
                number_format($amount, 2, '.', ''),
                $subscriber->end_date?->toDateString() ?: now()->toDateString(),
            ]
        );
    }

    /**
     * @param  list<string>  $params
     */
    private function sendOnce(
        Subscriber $subscriber,
        ?Invoice $invoice,
        string $stage,
        string $periodKey,
        string $template,
        array $params,
        bool $withExtendButton = false,
    ): void {
        try {
            if (! $this->whatsapp->isLive()) {
                return;
            }

            $template = trim($template);
            if ($template === '') {
                return;
            }

            $phone = $this->whatsapp->normalizePhone((string) $subscriber->phone);
            if ($phone === '') {
                return;
            }

            $exists = WhatsappReminderLog::query()
                ->where('subscriber_id', $subscriber->id)
                ->where('stage', $stage)
                ->where('period_key', $periodKey)
                ->where('status', 'sent')
                ->exists();

            if ($exists) {
                return;
            }

            $log = WhatsappReminderLog::query()->firstOrCreate(
                [
                    'subscriber_id' => $subscriber->id,
                    'stage' => $stage,
                    'period_key' => $periodKey,
                ],
                [
                    'invoice_id' => $invoice?->id,
                    'status' => 'queued',
                    'phone' => $phone,
                    'template_name' => $template,
                ]
            );

            if ($log->status === 'sent') {
                return;
            }

            $urlButtonSuffix = $withExtendButton
                ? $this->extendLinks->buttonSuffix($subscriber, $stage)
                : null;

            $result = $this->whatsapp->sendTemplate($phone, $template, $params, null, $urlButtonSuffix);

            $log->update([
                'invoice_id' => $invoice?->id,
                'status' => $result['ok'] ? 'sent' : 'failed',
                'provider_message_id' => $result['message_id'],
                'template_name' => $template,
                'phone' => $phone,
                'error_summary' => $result['ok'] ? null : mb_substr((string) $this->whatsapp->getLastError(), 0, 500),
                'attempted_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp lifecycle notify failed (isolated)', [
                'stage' => $stage,
                'subscriber_id' => $subscriber->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function primaryOpenInvoice(Subscriber $subscriber): ?Invoice
    {
        $subscriber->loadMissing(['invoices' => fn ($q) => $q->open()->orderBy('due_date')->orderBy('id')]);

        foreach ($subscriber->invoices as $invoice) {
            if ($invoice->remainingBalance() > 0.009) {
                return $invoice;
            }
        }

        return $subscriber->invoices()->latest('id')->first();
    }
}
