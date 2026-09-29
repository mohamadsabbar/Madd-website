<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AgentRenewalService
{
    public function __construct(
        private readonly MikrotikService $mikrotikService,
        private readonly BillingSmsService $billingSmsService,
        private readonly SubscriberBillingSyncService $billingSyncService,
    ) {}

    /**
     * تحصيل من الوكيل — دفع كامل (تجديد) أو جزئي على الفواتير المفتوحة.
     */
    public function collectPayment(Subscriber $subscriber, User $agent, ?float $paymentAmount = null): Subscriber
    {
        $subscriber->loadMissing('plan');
        if (! $subscriber->plan) {
            throw new \RuntimeException('لا توجد باقة مرتبطة بالمشترك.');
        }

        $lock = Cache::lock('agent-renew:subscriber:'.$subscriber->id, 45);

        if (! $lock->get()) {
            throw new \RuntimeException('جاري تنفيذ عملية تحصيل لهذا المشترك — يرجى الانتظار.');
        }

        try {
            return DB::transaction(function () use ($subscriber, $agent, $paymentAmount) {
                $subscriber = Subscriber::query()
                    ->whereKey($subscriber->id)
                    ->with('plan')
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->billingSyncService->syncMissingCycleInvoices($subscriber, $agent);
                $summary = $this->billingSummaryForSubscriber($subscriber);

                $collectTotal = (float) $summary['collectTotal'];
                $openBalance = (float) $summary['unpaidTotal'];

                if ($collectTotal <= 0 && $openBalance <= 0) {
                    throw new \RuntimeException('لا توجد مستحقات للتحصيل حالياً.');
                }

                $amount = $paymentAmount !== null
                    ? round($paymentAmount, 2)
                    : $collectTotal;

                if ($amount <= 0) {
                    throw new \RuntimeException('مبلغ التحصيل يجب أن يكون أكبر من صفر.');
                }

                if ($amount > $collectTotal + 0.009) {
                    throw new \RuntimeException('المبلغ أكبر من الإجمالي المستحق ('.number_format($collectTotal, 2).' شيكل).');
                }

                $isFullSettlement = $amount >= round($collectTotal - 0.009, 2);

                if (! $isFullSettlement && $amount > $openBalance + 0.009) {
                    throw new \RuntimeException(
                        'لدفع جزء من المستحقات فقط، الحد الأقصى الآن '.number_format($openBalance, 2)
                        .' شيكل. لإتمام التجديد والتمديد ادفع الإجمالي '.number_format($collectTotal, 2).' شيكل.'
                    );
                }

                if ($isFullSettlement) {
                    return $this->processFullSettlement($subscriber, $agent, $summary);
                }

                return $this->processPartialPayment($subscriber, $agent, $amount);
            });
        } finally {
            $lock->release();
        }
    }

    /** @deprecated Use collectPayment() */
    public function renewSubscription(Subscriber $subscriber, User $agent): Subscriber
    {
        return $this->collectPayment($subscriber, $agent);
    }

    /**
     * @param  array{collectTotal: float, unpaidTotal: float, needsSeparateRenewal: bool, renewalCycleAmount: float}  $summary
     */
    private function processFullSettlement(Subscriber $subscriber, User $agent, array $summary): Subscriber
    {
        $renewalAmount = round((float) $subscriber->amountForInvoicedPlan($subscriber->plan), 2);

        $recentDuplicate = Invoice::query()
            ->where('subscriber_id', $subscriber->id)
            ->where('created_by', $agent->id)
            ->where('invoice_number', 'like', 'INV-AG-%')
            ->where('created_at', '>=', now()->subSeconds(45))
            ->exists();

        if ($recentDuplicate) {
            throw new \RuntimeException('تم تنفيذ التحصيل للتو — لا حاجة للضغط مرة أخرى.');
        }

        $openInvoices = $this->openInvoicesFor($subscriber);
        $settledUnpaidTotal = 0.0;
        $lastSettledDue = null;

        foreach ($openInvoices as $invoice) {
            $balance = $invoice->remainingBalance();
            if ($balance <= 0) {
                continue;
            }

            Payment::create([
                'invoice_id' => $invoice->id,
                'collected_by' => $agent->id,
                'amount' => $balance,
                'method' => 'cash',
                'paid_at' => now(),
                'notes' => 'تحصيل كامل مع تجديد وكيل',
            ]);
            $invoice->refreshPaymentStatus();
            $settledUnpaidTotal += $balance;

            if ($invoice->due_date) {
                $due = Carbon::parse($invoice->due_date);
                if ($lastSettledDue === null || $due->gt($lastSettledDue)) {
                    $lastSettledDue = $due;
                }
            }
        }

        $renewalInvoice = null;
        $renewalAmountCharged = 0.0;

        if ($summary['needsSeparateRenewal']) {
            $renewalInvoice = Invoice::create([
                'invoice_number' => 'INV-AG-'.now()->format('YmdHis').'-'.$subscriber->id,
                'subscriber_id' => $subscriber->id,
                'plan_id' => $subscriber->plan_id,
                'created_by' => $agent->id,
                'amount' => $renewalAmount,
                'due_date' => now()->toDateString(),
                'status' => 'paid',
                'paid_at' => now()->toDateString(),
            ]);

            Payment::create([
                'invoice_id' => $renewalInvoice->id,
                'collected_by' => $agent->id,
                'amount' => $renewalInvoice->amount,
                'method' => 'cash',
                'paid_at' => now(),
            ]);

            $renewalAmountCharged = $renewalAmount;
            $baseDate = Carbon::parse($subscriber->end_date)->isFuture()
                ? Carbon::parse($subscriber->end_date)
                : Carbon::today();
            $newEnd = Subscriber::cycleEndDateAfterDate($baseDate);
        } elseif ($openInvoices->isNotEmpty()) {
            $newEnd = Subscriber::cycleEndDateAfterDate($lastSettledDue ?? Carbon::today());
        } else {
            $baseDate = Carbon::parse($subscriber->end_date)->isFuture()
                ? Carbon::parse($subscriber->end_date)
                : Carbon::today();
            $newEnd = Subscriber::cycleEndDateAfterDate($baseDate);
        }

        $subscriber->update([
            'status' => 'active',
            'suspended_at' => null,
            'end_date' => $newEnd->toDateString(),
            'self_extend_count' => 0,
        ]);

        $this->mikrotikService->enablePppoeUser($subscriber->fresh());
        $subscriber->refresh();

        $totalCash = round($settledUnpaidTotal + $renewalAmountCharged, 2);

        if ($openInvoices->isNotEmpty() && $renewalAmountCharged <= 0) {
            $logMsg = "تحصيل كامل بواسطة الوكيل {$agent->name}: {$subscriber->full_name} — "
                .$openInvoices->count().' فاتورة، '.number_format($totalCash, 2).' شيكل.';
        } elseif ($renewalAmountCharged > 0) {
            $logMsg = "تجديد بواسطة الوكيل {$agent->name}: {$subscriber->full_name} — "
                .number_format($totalCash, 2).' شيكل.';
        } else {
            $logMsg = "تحصيل بواسطة الوكيل {$agent->name}: {$subscriber->full_name} — "
                .number_format($totalCash, 2).' شيكل.';
        }

        $this->logCollection($subscriber, $agent, $logMsg, $totalCash, [
            'renewal_invoice_id' => $renewalInvoice?->id,
            'renewal_invoice_number' => $renewalInvoice?->invoice_number,
            'renewal_amount' => $renewalAmountCharged,
            'settled_unpaid_total' => round($settledUnpaidTotal, 2),
            'unpaid_invoices_settled' => $openInvoices->count(),
            'full_settlement' => true,
            'partial' => false,
        ]);

        return $subscriber->load('plan');
    }

    private function processPartialPayment(Subscriber $subscriber, User $agent, float $amount): Subscriber
    {
        $openInvoices = $this->openInvoicesFor($subscriber);
        if ($openInvoices->isEmpty()) {
            throw new \RuntimeException('لا توجد فواتير مفتوحة للدفع الجزئي.');
        }

        $remaining = $amount;
        $applied = 0.0;
        $touched = 0;

        foreach ($openInvoices as $invoice) {
            if ($remaining <= 0) {
                break;
            }

            $balance = $invoice->remainingBalance();
            if ($balance <= 0) {
                continue;
            }

            $pay = round(min($remaining, $balance), 2);
            Payment::create([
                'invoice_id' => $invoice->id,
                'collected_by' => $agent->id,
                'amount' => $pay,
                'method' => 'cash',
                'paid_at' => now(),
                'notes' => 'دفع جزئي — وكيل',
            ]);
            $invoice->refreshPaymentStatus();

            $remaining -= $pay;
            $applied += $pay;
            $touched++;
        }

        $subscriber->refresh();
        $stillDue = round((float) $this->billingSummaryForSubscriber($subscriber)['unpaidTotal'], 2);

        $logMsg = "دفع جزئي بواسطة الوكيل {$agent->name}: {$subscriber->full_name} — "
            .number_format($applied, 2).' شيكل (متبقٍ '.number_format($stillDue, 2).' شيكل).';

        $this->logCollection($subscriber, $agent, $logMsg, $applied, [
            'partial' => true,
            'full_settlement' => false,
            'invoices_touched' => $touched,
            'remaining_due' => $stillDue,
        ], 'payment.partial_received');

        return $subscriber->load('plan');
    }

    /** @return Collection<int, Invoice> */
    private function openInvoicesFor(Subscriber $subscriber): Collection
    {
        return Invoice::query()
            ->where('subscriber_id', $subscriber->id)
            ->open()
            ->with('payments')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    /** @return array{collectTotal: float, unpaidTotal: float, needsSeparateRenewal: bool, renewalCycleAmount: float} */
    private function billingSummaryForSubscriber(Subscriber $subscriber): array
    {
        return $this->billingSyncService->billingSummary($subscriber);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function logCollection(
        Subscriber $subscriber,
        User $agent,
        string $logMsg,
        float $totalCash,
        array $meta,
        string $eventType = 'subscription.renewed_by_agent'
    ): void {
        $newEndYmd = optional($subscriber->end_date)->toDateString();
        $phone = trim((string) $subscriber->phone);
        $fullName = (string) $subscriber->full_name;
        $subscriberId = (int) $subscriber->id;

        $metaBase = array_merge($meta, [
            'plan_id' => $subscriber->plan_id,
            'new_end_date' => $newEndYmd,
        ]);

        DB::afterCommit(function () use (
            $subscriberId,
            $agent,
            $logMsg,
            $totalCash,
            $phone,
            $fullName,
            $metaBase,
            $newEndYmd,
            $eventType
        ): void {
            $sms = ($metaBase['partial'] ?? false)
                ? ['sent' => false, 'reason' => 'partial_payment']
                : $this->sendAgentPaymentSmsIfPossible($phone, $fullName, $totalCash, (string) $newEndYmd);

            $subject = Subscriber::query()->find($subscriberId);
            $logOpts = [
                'subject' => $subject,
                'actor' => $agent,
                'phone' => $phone !== '' ? $phone : null,
                'amount' => $totalCash,
                'meta' => array_merge($metaBase, ['sms' => $sms]),
            ];
            if ($subject === null) {
                $logOpts['subject_label'] = $fullName;
            }

            ActivityLogger::success($eventType, $logMsg, $logOpts);
        });
    }

    /**
     * @return array{sent: bool, reason: string|null}
     */
    private function sendAgentPaymentSmsIfPossible(string $phone, string $fullName, float $amount, string $endDateYmd): array
    {
        $phone = trim($phone);
        if ($phone === '') {
            return ['sent' => false, 'reason' => 'no_phone'];
        }
        if (! $this->billingSmsService->isLive()) {
            return ['sent' => false, 'reason' => 'sms_not_configured'];
        }

        $template = (string) config('billing.sms_messages.agent_payment', '');
        $company = (string) config('billing.company_name', 'أوليفيا');
        $amountStr = number_format(round($amount, 2), 2);
        $default = '{company}: تم تأكيد استلام الدفع بنجاح بمبلغ {amount} شيكل، {name}. اشتراكك ساري حتى {end_date}. شكراً لتعاملك معنا.';
        $text = str_replace(
            ['{name}', '{amount}', '{end_date}', '{company}'],
            [$fullName, $amountStr, $endDateYmd, $company],
            $template !== '' ? $template : $default
        );

        if ($this->billingSmsService->send($phone, $text)) {
            return ['sent' => true, 'reason' => null];
        }

        return [
            'sent' => false,
            'reason' => $this->billingSmsService->getLastError() ?: 'send_failed',
        ];
    }
}
