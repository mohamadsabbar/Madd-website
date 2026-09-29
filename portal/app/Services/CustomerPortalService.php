<?php

namespace App\Services;

use App\Models\CustomerRenewalRequest;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscriber;
use App\Models\SubscriberTrafficSnapshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerPortalService
{
    public function subscriberFor(User $user): ?Subscriber
    {
        return $user->subscriberRecord;
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $user): array
    {
        $subscriber = $this->subscriberFor($user);

        if (! $subscriber) {
            return [
                'ok' => false,
                'message' => 'لا يوجد اشتراك مرتبط بهذا الحساب. تواصل مع الدعم.',
            ];
        }

        $subscriber->load(['plan', 'router']);

        return [
            'ok' => true,
            'account' => $this->accountPayload($subscriber),
            'service' => $this->currentServicePayload($subscriber),
            'invoices' => $this->recentInvoices($subscriber, 3),
            'usage' => $this->usageByMonth($subscriber, 6),
            'available_plans' => $this->availablePlans($subscriber),
            'renewal' => $this->renewalPayload($subscriber),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function accountPayload(Subscriber $subscriber): array
    {
        $today = Carbon::today();
        $end = $subscriber->end_date ? Carbon::parse($subscriber->end_date)->startOfDay() : null;
        $isExpired = $end ? $end->lt($today) : true;
        $daysRemaining = ($end && $end->gte($today)) ? (int) $today->diffInDays($end) : 0;

        return [
            'id' => $subscriber->id,
            'full_name' => $subscriber->full_name,
            'phone' => $subscriber->phone,
            'address' => $subscriber->address,
            'pppoe_username' => $subscriber->pppoe_username,
            'status' => $subscriber->status,
            'status_label' => $this->statusLabel($subscriber->status, $isExpired),
            'is_online' => (bool) $subscriber->is_online,
            'start_date' => $subscriber->start_date?->toDateString(),
            'end_date' => $subscriber->end_date?->toDateString(),
            'suspended_at' => $subscriber->suspended_at?->toIso8601String(),
            'is_expired' => $isExpired,
            'days_remaining' => max(0, $daysRemaining),
            'router' => $subscriber->router ? [
                'id' => $subscriber->router->id,
                'name' => $subscriber->router->name,
            ] : null,
        ];
    }

    /**
     * الخدمة الحالية (الباقة المشترك فيها).
     *
     * @return array<string, mixed>|null
     */
    public function currentServicePayload(Subscriber $subscriber): ?array
    {
        $plan = $subscriber->plan;
        if (! $plan) {
            return null;
        }

        $price = $subscriber->monthly_price !== null
            ? (string) $subscriber->monthly_price
            : (string) $plan->price;

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'speed_mbps' => $plan->speed_mbps,
            'price' => $price,
            'catalog_price' => (string) $plan->price,
            'duration_days' => $plan->duration_days,
            'mikrotik_profile' => $plan->mikrotik_profile,
            'is_current' => true,
        ];
    }

    /**
     * آخر الفواتير.
     *
     * @return list<array<string, mixed>>
     */
    public function recentInvoices(Subscriber $subscriber, int $limit = 3): array
    {
        return $subscriber->invoices()
            ->with('plan')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Invoice $invoice) => $this->invoicePayload($invoice))
            ->values()
            ->all();
    }

    /**
     * كل الفواتير (للصفحة / API).
     *
     * @return list<array<string, mixed>>
     */
    public function allInvoices(Subscriber $subscriber, int $limit = 50): array
    {
        return $subscriber->invoices()
            ->with('plan')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Invoice $invoice) => $this->invoicePayload($invoice))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function invoicePayload(Invoice $invoice): array
    {
        $paid = $invoice->paidAmount();
        $remaining = $invoice->remainingBalance();

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => (string) $invoice->amount,
            'paid_amount' => number_format($paid, 2, '.', ''),
            'remaining' => number_format($remaining, 2, '.', ''),
            'status' => $invoice->status,
            'status_label' => match ($invoice->status) {
                'paid' => 'مدفوعة',
                'partial' => 'مدفوعة جزئياً',
                default => 'غير مدفوعة',
            },
            'due_date' => $invoice->due_date?->toDateString(),
            'paid_at' => $invoice->paid_at?->toDateString(),
            'plan_name' => $invoice->plan?->name,
            'notes' => $invoice->notes,
            'created_at' => $invoice->created_at?->toDateString(),
        ];
    }

    /**
     * استهلاك شهري مجمّع من لقطات الترافيك.
     *
     * @return array{months: list<array<string, mixed>>, has_data: bool, unit: string}
     */
    public function usageByMonth(Subscriber $subscriber, int $months = 6): array
    {
        $from = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $rows = SubscriberTrafficSnapshot::query()
            ->where(function ($q) use ($subscriber) {
                $q->where('subscriber_id', $subscriber->id);
                if ($subscriber->pppoe_username) {
                    $q->orWhere('ppp_username', $subscriber->pppoe_username);
                }
            })
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at')
            ->get(['delta_rx', 'delta_tx', 'recorded_at']);

        $byMonth = [];
        for ($i = 0; $i < $months; $i++) {
            $key = $from->copy()->addMonths($i)->format('Y-m');
            $byMonth[$key] = [
                'month' => $key,
                'label' => $from->copy()->addMonths($i)->locale('ar')->translatedFormat('F Y'),
                'download_bytes' => 0,
                'upload_bytes' => 0,
                'total_bytes' => 0,
            ];
        }

        foreach ($rows as $row) {
            $key = Carbon::parse($row->recorded_at)->format('Y-m');
            if (! isset($byMonth[$key])) {
                continue;
            }
            $byMonth[$key]['download_bytes'] += (int) $row->delta_rx;
            $byMonth[$key]['upload_bytes'] += (int) $row->delta_tx;
            $byMonth[$key]['total_bytes'] =
                $byMonth[$key]['download_bytes'] + $byMonth[$key]['upload_bytes'];
        }

        $monthsList = array_values(array_map(function (array $m) {
            $m['download'] = $this->formatBytes($m['download_bytes']);
            $m['upload'] = $this->formatBytes($m['upload_bytes']);
            $m['total'] = $this->formatBytes($m['total_bytes']);

            return $m;
        }, $byMonth));

        return [
            'months' => $monthsList,
            'has_data' => $rows->isNotEmpty(),
            'unit' => 'bytes',
            'note' => $rows->isEmpty()
                ? 'سيظهر الاستهلاك هنا بعد مزامنة الراوتر (MikroTik).'
                : null,
        ];
    }

    /**
     * الباقات المتاحة للاشتراك / الترقية (النشطة غير الحالية).
     *
     * @return list<array<string, mixed>>
     */
    public function availablePlans(Subscriber $subscriber): array
    {
        $currentId = (int) $subscriber->plan_id;

        return Plan::query()
            ->where('is_active', true)
            ->orderBy('speed_mbps')
            ->get()
            ->map(function (Plan $plan) use ($currentId) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'speed_mbps' => $plan->speed_mbps,
                    'price' => (string) $plan->price,
                    'duration_days' => $plan->duration_days,
                    'is_current' => (int) $plan->id === $currentId,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * أيام التمديد الذاتي بالترتيب (أول مرة، ثاني مرة…).
     *
     * @return list<int>
     */
    public function selfExtendSchedule(): array
    {
        $days = config('subscriber_portal.self_extend_days', [3, 2]);

        return array_values(array_filter(array_map('intval', (array) $days), fn (int $d) => $d > 0));
    }

    public function isSubscriptionExpired(Subscriber $subscriber): bool
    {
        if (! $subscriber->end_date) {
            return true;
        }

        return Carbon::parse($subscriber->end_date)->startOfDay()->lt(Carbon::today());
    }

    /**
     * حالة التمديد الذاتي: يظهر فقط بعد انتهاء الاشتراك.
     *
     * @return array<string, mixed>
     */
    public function selfExtendState(Subscriber $subscriber): array
    {
        $schedule = $this->selfExtendSchedule();
        $used = max(0, (int) ($subscriber->self_extend_count ?? 0));
        $max = count($schedule);
        $expired = $this->isSubscriptionExpired($subscriber);
        $nextDays = $schedule[$used] ?? null;
        $canSelfExtend = $expired && $nextDays !== null;

        $message = null;
        if (! $expired) {
            $message = 'التمديد الذاتي يظهر بعد انتهاء الاشتراك.';
        } elseif ($canSelfExtend) {
            $attempt = $used + 1;
            $message = $attempt === 1
                ? "يمكنك تمديد الاشتراك الآن لمدة {$nextDays} أيام تلقائياً."
                : "يمكنك التمديد مرة أخيرة لمدة {$nextDays} أيام تلقائياً.";
        } else {
            $message = 'استنفدت مرات التمديد الذاتي. يرجى التواصل مع الشركة أو نقطة الدفع للتجديد.';
        }

        return [
            'can_self_extend' => $canSelfExtend,
            'next_days' => $canSelfExtend ? (int) $nextDays : null,
            'used_count' => $used,
            'max_count' => $max,
            'remaining_count' => max(0, $max - $used),
            'schedule' => $schedule,
            'is_expired' => $expired,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function renewalPayload(Subscriber $subscriber): array
    {
        $expired = $this->isSubscriptionExpired($subscriber);
        $self = $this->selfExtendState($subscriber);
        $pending = CustomerRenewalRequest::query()
            ->where('subscriber_id', $subscriber->id)
            ->where('status', 'pending')
            ->exists();

        return [
            // زر التمديد الذاتي فقط عند الانتهاء وما زال هناك محاولات
            'show_extend_cta' => $self['can_self_extend'],
            'show_company_request_cta' => $expired && ! $self['can_self_extend'],
            'needs_attention' => $expired || $subscriber->status === 'suspended',
            'pending_request' => $pending,
            'self_extend' => $self,
        ];
    }

    /**
     * تمديد ذاتي فوري بدون موافقة الشركة.
     *
     * @return array{subscriber: Subscriber, days: int, new_end_date: string, mikrotik: string|null}
     */
    public function performSelfExtend(User $user, Subscriber $subscriber, ?MikrotikService $mikrotik = null): array
    {
        $state = $this->selfExtendState($subscriber);

        if (! $state['can_self_extend'] || ! $state['next_days']) {
            throw new \RuntimeException($state['message'] ?? 'لا يمكن التمديد الذاتي الآن.');
        }

        $days = (int) $state['next_days'];
        $today = Carbon::today();

        $result = DB::transaction(function () use ($subscriber, $days, $today, $user) {
            /** @var Subscriber $locked */
            $locked = Subscriber::query()->whereKey($subscriber->id)->lockForUpdate()->firstOrFail();

            $freshState = $this->selfExtendState($locked);
            if (! $freshState['can_self_extend'] || ! $freshState['next_days']) {
                throw new \RuntimeException($freshState['message'] ?? 'لا يمكن التمديد الذاتي الآن.');
            }

            $daysLocked = (int) $freshState['next_days'];
            $currentEnd = $locked->end_date
                ? Carbon::parse($locked->end_date)->startOfDay()
                : $today->copy();
            $base = $currentEnd->gt($today) ? $currentEnd : $today->copy();
            $newEnd = $base->copy()->addDays($daysLocked);

            $wasSuspended = $locked->status === 'suspended' || $this->isSubscriptionExpired($locked);

            $locked->end_date = $newEnd->toDateString();
            $locked->status = 'active';
            $locked->suspended_at = null;
            $locked->self_extend_count = (int) $locked->self_extend_count + 1;
            $locked->save();

            CustomerRenewalRequest::query()->create([
                'subscriber_id' => $locked->id,
                'user_id' => $user->id,
                'status' => 'auto_extended',
                'note' => "تمديد ذاتي #{$locked->self_extend_count}: {$daysLocked} أيام حتى {$newEnd->toDateString()}",
            ]);

            return [
                'subscriber' => $locked->fresh(['plan', 'router']),
                'days' => $daysLocked,
                'new_end_date' => $newEnd->toDateString(),
                'was_suspended' => $wasSuspended,
            ];
        });

        $mtMsg = null;
        if ($result['was_suspended']) {
            try {
                $service = $mikrotik ?? app(MikrotikService::class);
                $service->enablePppoeUser($result['subscriber']);
                $mtMsg = 'تم تفعيل الخط على الراوتر.';
            } catch (\Throwable $e) {
                $mtMsg = 'تنبيه: فشل تفعيل الراوتر — '.$e->getMessage();
            }
        }

        ActivityLogger::success(
            'subscriber.self_extended',
            "تمديد ذاتي للمشترك {$result['subscriber']->full_name}: {$result['days']} أيام حتى {$result['new_end_date']}.",
            [
                'subject' => $result['subscriber'],
                'phone' => $result['subscriber']->phone,
                'meta' => [
                    'days' => $result['days'],
                    'new_end_date' => $result['new_end_date'],
                    'self_extend_count' => $result['subscriber']->self_extend_count,
                    'mikrotik' => $mtMsg,
                ],
            ]
        );

        return [
            'subscriber' => $result['subscriber'],
            'days' => $result['days'],
            'new_end_date' => $result['new_end_date'],
            'mikrotik' => $mtMsg,
        ];
    }

    public function resetSelfExtendCount(Subscriber $subscriber): void
    {
        if ((int) $subscriber->self_extend_count === 0) {
            return;
        }

        $subscriber->self_extend_count = 0;
        $subscriber->save();
    }

    public function createRenewalRequest(User $user, Subscriber $subscriber, ?string $note = null, ?int $planId = null): CustomerRenewalRequest
    {
        $state = $this->selfExtendState($subscriber);

        // إن أمكن التمديد الذاتي لطلب تجديد عام — وجّه للتمديد الفوري
        if ($state['can_self_extend'] && $planId === null) {
            throw new \RuntimeException(
                'يمكنك التمديد ذاتياً لمدة '.$state['next_days'].' أيام دون انتظار موافقة الشركة.'
            );
        }

        $exists = CustomerRenewalRequest::query()
            ->where('subscriber_id', $subscriber->id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            throw new \RuntimeException('يوجد طلب قيد المعالجة بالفعل.');
        }

        if ($planId) {
            $plan = Plan::query()->where('is_active', true)->find($planId);
            if (! $plan) {
                throw new \InvalidArgumentException('الباقة غير متاحة.');
            }
            $note = trim(($note ? $note."\n" : '').'طلب باقة: '.$plan->name.' (#'.$plan->id.')');
        }

        return CustomerRenewalRequest::query()->create([
            'subscriber_id' => $subscriber->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'note' => $note,
        ]);
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 MB';
        }
        $gb = $bytes / (1024 ** 3);
        if ($gb >= 1) {
            return round($gb, 2).' GB';
        }

        return round($bytes / (1024 ** 2), 1).' MB';
    }

    private function statusLabel(string $status, bool $isExpired): string
    {
        if ($status === 'suspended') {
            return 'موقوف';
        }
        if ($isExpired) {
            return 'منتهي';
        }

        return 'نشط';
    }
}
