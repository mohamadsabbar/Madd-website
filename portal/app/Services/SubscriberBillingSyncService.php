<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscriber;
use App\Models\User;
use App\Support\BillingCalendar;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SubscriberBillingSyncService
{
    /**
     * إنشاء فواتير غير مدفوعة لكل دورة فوترة فائتة لم تُغطَّ بفاتورة (مدفوعة أو مفتوحة).
     * يُستخدم قبل عرض المستحقات للوكيل أو قبل التجديد لضمان ظهور كل شهر متأخر.
     */
    public function syncMissingCycleInvoices(Subscriber $subscriber, ?User $actor = null): int
    {
        $subscriber->loadMissing('plan');
        if (! $subscriber->plan || ! $subscriber->start_date) {
            return 0;
        }

        $billingDay = (int) config('billing.billing_day', 5);
        $today = Carbon::today();
        $start = Carbon::parse($subscriber->start_date)->startOfDay();

        if ($start->gt($today)) {
            return 0;
        }

        $invoices = Invoice::query()
            ->where('subscriber_id', $subscriber->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('due_date')
            ->get();

        $amount = round((float) $subscriber->amountForInvoicedPlan($subscriber->plan), 2);
        if ($amount <= 0) {
            return 0;
        }

        $creatorId = $this->resolveCreatorId($actor);
        $created = 0;

        foreach (BillingCalendar::billingDatesThrough($start, $today, $billingDay) as $billingDate) {
            if ($this->hasInvoiceForBillingPeriod($invoices, $billingDate)) {
                continue;
            }

            $currentMonthBilling = BillingCalendar::billingDateForMonth($today, $billingDay);
            $isCurrentCycle = $billingDate->isSameDay($currentMonthBilling);
            $note = $isCurrentCycle
                ? 'فاتورة دورة '.$billingDate->format('Y-m').' — دورة جديدة (تلقائي)'
                : 'فاتورة دورة '.$billingDate->format('Y-m').' — مستحقات سابقة (تلقائي)';

            $invoice = Invoice::create([
                'invoice_number' => $this->uniqueInvoiceNumber($subscriber->id, $billingDate),
                'subscriber_id' => $subscriber->id,
                'plan_id' => $subscriber->plan_id,
                'created_by' => $creatorId,
                'amount' => $amount,
                'due_date' => $billingDate->toDateString(),
                'status' => 'unpaid',
                'notes' => $note,
            ]);

            $invoices->push($invoice);
            $created++;
        }

        return $created;
    }

    /**
     * مزامنة الفواتير ثم إرجاع ملخص التحصيل للوكيل.
     *
     * @return array{
     *     unpaidTotal: float,
     *     unpaidInvoicesCount: int,
     *     renewalCycleAmount: float,
     *     collectTotal: float,
     *     needsSeparateRenewal: bool
     * }
     */
    public function billingSummary(Subscriber $subscriber, ?User $actor = null): array
    {
        $this->syncMissingCycleInvoices($subscriber, $actor);

        $subscriber->loadMissing('plan');
        $cycleAmount = $subscriber->plan
            ? round((float) $subscriber->amountForInvoicedPlan($subscriber->plan), 2)
            : 0.0;

        $openInvoices = Invoice::query()
            ->where('subscriber_id', $subscriber->id)
            ->open()
            ->with('payments')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $unpaidTotal = round((float) $openInvoices->sum(fn (Invoice $invoice) => $invoice->remainingBalance()), 2);
        $unpaidInvoicesCount = $openInvoices->count();

        $needsSeparateRenewal = $unpaidTotal <= 0 && $cycleAmount > 0;
        $renewalCycleAmount = $needsSeparateRenewal ? $cycleAmount : 0.0;
        $collectTotal = round($unpaidTotal + $renewalCycleAmount, 2);

        return [
            'unpaidTotal' => $unpaidTotal,
            'unpaidInvoicesCount' => $unpaidInvoicesCount,
            'renewalCycleAmount' => $renewalCycleAmount,
            'collectTotal' => $collectTotal,
            'needsSeparateRenewal' => $needsSeparateRenewal,
        ];
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     */
    private function hasInvoiceForBillingPeriod(Collection $invoices, Carbon $billingDate): bool
    {
        $year = $billingDate->year;
        $month = $billingDate->month;

        return $invoices->contains(function (Invoice $invoice) use ($year, $month, $billingDate) {
            if ($invoice->due_date
                && (int) $invoice->due_date->year === $year
                && (int) $invoice->due_date->month === $month) {
                return true;
            }

            if ($invoice->paid_at) {
                $paidAt = Carbon::parse($invoice->paid_at)->startOfDay();
                if ((int) $paidAt->year === $year && (int) $paidAt->month === $month) {
                    return true;
                }
            }

            // فواتير التجديد من الوكيل: due_date = يوم الدفع — نربطها بشهر الفوترة إن وُجدت
            if ($invoice->due_date && $invoice->due_date->isSameDay($billingDate)) {
                return true;
            }

            return false;
        });
    }

    private function uniqueInvoiceNumber(int $subscriberId, Carbon $billingDate): string
    {
        return 'INV-CYC-'.$billingDate->format('Ym').'-'.$subscriberId.'-'.Str::lower(Str::random(4));
    }

    private function resolveCreatorId(?User $user): int
    {
        if ($user?->id) {
            return (int) $user->id;
        }

        static $adminId = null;
        if ($adminId === null) {
            $adminId = (int) User::query()->where('role', 'admin')->orderBy('id')->value('id');
            $adminId = $adminId ?: 1;
        }

        return $adminId;
    }
}
