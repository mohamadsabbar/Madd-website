<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\BillingSmsService;
use App\Services\MikrotikService;
use App\Services\SubscriberBillingSyncService;
use App\Services\SubscriberManualDebtService;
use App\Services\SubscriberPortalUserService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSubscriberController extends Controller
{
    public function __construct(
        private readonly MikrotikService $mikrotikService,
        private readonly BillingSmsService $billingSmsService,
        private readonly SubscriberPortalUserService $subscriberPortalUserService,
        private readonly SubscriberBillingSyncService $billingSyncService,
        private readonly SubscriberManualDebtService $manualDebtService,
    ) {
    }

    /**
     * نموذج إضافة مشترك جديد (يُنشئ حساب PPPoE على المايكروتيك).
     */
    public function create(): View
    {
        return view('admin.subscriber-create', [
            'plans' => Plan::query()->where('is_active', true)->orderBy('name')->get(),
            'routers' => Router::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'pppoe_username' => ['required', 'string', 'max:100', 'unique:subscribers,pppoe_username'],
            'pppoe_password' => ['required', 'string', 'max:100'],
            'plan_id' => ['required', Rule::exists('plans', 'id')->where('is_active', true)],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'router_id' => ['required', Rule::exists('routers', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date'],
        ]);

        $data['monthly_price'] = $request->filled('monthly_price') ? round((float) $request->input('monthly_price'), 2) : null;

        Plan::findOrFail($data['plan_id']);
        $data['end_date'] = Subscriber::cycleEndDateAfterDate($data['start_date'])->toDateString();

        try {
            $subscriber = DB::transaction(function () use ($data) {
                $subscriber = Subscriber::create($data);
                $this->mikrotikService->createPppoeUser($subscriber);

                return $subscriber;
            });
            try {
                $this->subscriberPortalUserService->ensurePortalUser($subscriber->fresh());
            } catch (\Throwable $e) {
                return redirect()
                    ->route('web.admin.subscribers.show', $subscriber)
                    ->with(
                        'error',
                        'تم إضافة المشترك لكن تعذّر إنشاء حساب تطبيق العميل: '.$e->getMessage()
                    );
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with(
                'error',
                'تعذّر إكمال العملية: '.$e->getMessage()
            );
        }

        ActivityLogger::success('subscriber.created', "تم إضافة مشترك جديد: {$subscriber->full_name} ({$subscriber->pppoe_username}).", [
            'subject' => $subscriber,
            'phone' => $subscriber->phone,
            'meta' => [
                'plan_id' => $subscriber->plan_id,
                'router_id' => $subscriber->router_id,
                'start_date' => optional($subscriber->start_date)->toDateString(),
                'end_date' => optional($subscriber->end_date)->toDateString(),
            ],
        ]);

        return redirect()
            ->route('web.admin.subscribers.show', $subscriber)
            ->with('status', 'تم إضافة المشترك وربطه بالراوتر. دخول تطبيق العميل: رقم الهاتف وكلمة المرور الافتراضية.');
    }

    public function edit(Subscriber $subscriber): View
    {
        $subscriber->load(['plan', 'router']);

        return view('admin.subscriber-edit', [
            'subscriber' => $subscriber,
            'plans' => Plan::query()
                ->where(function ($q) use ($subscriber) {
                    $q->where('is_active', true)->orWhere('id', $subscriber->plan_id);
                })
                ->orderBy('name')
                ->get(),
            'routers' => Router::query()
                ->where(function ($q) use ($subscriber) {
                    $q->where('is_active', true)->orWhere('id', $subscriber->router_id);
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Subscriber $subscriber): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'pppoe_password' => ['nullable', 'string', 'max:100'],
            'plan_id' => [
                'required',
                Rule::exists('plans', 'id')->where(function ($q) use ($subscriber) {
                    $q->where('is_active', true)->orWhere('id', $subscriber->plan_id);
                }),
            ],
            'router_id' => [
                'required',
                Rule::exists('routers', 'id')->where(function ($q) use ($subscriber) {
                    $q->where('is_active', true)->orWhere('id', $subscriber->router_id);
                }),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'end_date.after_or_equal' => 'يجب أن يكون تاريخ الانتهاء بعد تاريخ البداية أو مساويًا له.',
        ]);

        if (empty($data['pppoe_password'])) {
            unset($data['pppoe_password']);
        }

        $data['monthly_price'] = $request->filled('monthly_price') ? round((float) $request->input('monthly_price'), 2) : null;

        $data['end_date'] = $request->filled('end_date')
            ? Carbon::parse($data['end_date'])->toDateString()
            : Subscriber::cycleEndDateAfterDate($data['start_date'])->toDateString();

        $wasSuspended = $subscriber->status === 'suspended';
        $today = Carbon::today();

        $subscriber->update($data);
        if (Carbon::parse($data['end_date'])->gte($today)) {
            $subscriber->status = 'active';
            $subscriber->suspended_at = null;
            $subscriber->self_extend_count = 0;
            $subscriber->save();
        }

        $subscriber->refresh()->load(['plan', 'router']);

        if ($wasSuspended && Carbon::parse($data['end_date'])->gte($today)) {
            try {
                $this->mikrotikService->enablePppoeUser($subscriber);
            } catch (\Throwable $e) {
                Log::warning('فشل تفعيل المايكروتيك بعد تعديل تاريخ الانتهاء', [
                    'subscriber' => $subscriber->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $this->subscriberPortalUserService->ensurePortalUser($subscriber);
        } catch (\Throwable $e) {
            return redirect()
                ->route('web.admin.subscribers.show', $subscriber)
                ->with('error', 'تم حفظ التعديلات لكن تعذّر تحديث حساب تطبيق العميل: '.$e->getMessage());
        }

        $mtMsg = '';
        try {
            $this->mikrotikService->createPppoeUser($subscriber);
            $mtMsg = ' وتم تحديث بيانات PPPoE على المايكروتيك.';
        } catch (\Throwable $e) {
            $mtMsg = ' تُنبيه: فشل تحديث المايكروتيك — '.$e->getMessage();
        }

        ActivityLogger::log('subscriber.updated', "تعديل بيانات المشترك: {$subscriber->full_name}.", [
            'subject' => $subscriber,
            'phone' => $subscriber->phone,
            'meta' => [
                'plan_id' => $subscriber->plan_id,
                'router_id' => $subscriber->router_id,
                'end_date' => optional($subscriber->end_date)->toDateString(),
            ],
        ]);

        return redirect()
            ->route('web.admin.subscribers.show', $subscriber)
            ->with('status', 'تم حفظ التعديلات.'.$mtMsg);
    }

    /**
     * تفاصيل المشترك: اشتراك، مستحقات، فواتير، دفعات.
     */
    public function show(Subscriber $subscriber): View
    {
        $subscriber->load(['plan', 'router']);

        $this->billingSyncService->syncMissingCycleInvoices($subscriber, auth()->user());

        $today = Carbon::today();

        $invoices = Invoice::query()
            ->where('subscriber_id', $subscriber->id)
            ->with(['plan', 'creator', 'payments.collector'])
            ->latest()
            ->get();

        $unpaidInvoices = $invoices->filter(fn (Invoice $inv) => in_array($inv->status, ['unpaid', 'partial'], true));
        $totalUnpaid = round((float) $unpaidInvoices->sum(fn (Invoice $inv) => $inv->remainingBalance()), 2);

        $overdueUnpaid = $unpaidInvoices->filter(fn (Invoice $inv) => $inv->status !== 'paid' && $inv->due_date && $inv->due_date->lt($today));
        $totalOverdue = round((float) $overdueUnpaid->sum(fn (Invoice $inv) => $inv->remainingBalance()), 2);

        $paidInvoices = $invoices->where('status', 'paid');
        $totalInvoicedPaid = (float) $paidInvoices->sum('amount');

        $cancelledInvoices = $invoices->where('status', 'cancelled');

        $payments = Payment::query()
            ->whereHas('invoice', fn ($q) => $q->where('subscriber_id', $subscriber->id))
            ->with(['invoice', 'collector'])
            ->latest('paid_at')
            ->get();

        $totalPaymentsRecorded = (float) $payments->sum('amount');

        $expired = $subscriber->end_date && $subscriber->end_date->isPast();

        return view('admin.subscriber-show', [
            'subscriber' => $subscriber,
            'expired' => $expired,
            'today' => $today,
            'invoices' => $invoices,
            'payments' => $payments,
            'summary' => [
                'invoice_count' => $invoices->count(),
                'unpaid_count' => $unpaidInvoices->count(),
                'overdue_count' => $overdueUnpaid->count(),
                'total_unpaid' => $totalUnpaid,
                'total_overdue' => $totalOverdue,
                'paid_invoice_count' => $paidInvoices->count(),
                'total_invoiced_paid' => $totalInvoicedPaid,
                'total_payments_recorded' => $totalPaymentsRecorded,
                'cancelled_count' => $cancelledInvoices->count(),
            ],
        ]);
    }

    /**
     * تمديد تاريخ انتهاء الاشتراك بعدد أيام (من أقصى بين تاريخ الانتهاء الحالي واليوم، ثم إضافة الأيام).
     * إن كان المشترك معلّقاً بسبب انتهاء المدة يُعاد تفعيله على النظام والمايكروتيك عند الحاجة.
     */
    public function extendSubscription(Request $request, Subscriber $subscriber): RedirectResponse
    {
        $data = $request->validate([
            'days' => ['required', 'integer', 'min:3', 'max:365'],
        ], [
            'days.min' => 'الحد الأدنى للتمديد 3 أيام.',
            'days.max' => 'الحد الأقصى 365 يوماً.',
        ]);

        $days = (int) $data['days'];
        $today = Carbon::today();

        $currentEnd = $subscriber->end_date
            ? $subscriber->end_date->copy()->startOfDay()
            : $today->copy();

        $base = $currentEnd->gt($today) ? $currentEnd : $today->copy();
        $newEnd = $base->copy()->addDays($days);

        $wasSuspended = $subscriber->status === 'suspended';

        $subscriber->end_date = $newEnd->toDateString();
        if ($newEnd->gte($today)) {
            $subscriber->status = 'active';
            $subscriber->suspended_at = null;
        }
        // أي تمديد من الإدارة يعيد عداد التمديد الذاتي
        $subscriber->self_extend_count = 0;
        $subscriber->save();

        $mtMsg = '';
        if ($wasSuspended) {
            try {
                $this->mikrotikService->enablePppoeUser($subscriber->fresh());
                $mtMsg = ' وتم تفعيل حساب PPPoE على المايكروتيك.';
            } catch (\Throwable $e) {
                $mtMsg = ' تُنبيه: فشل تفعيل المايكروتيك — '.$e->getMessage();
            }
        }

        $smsMsg = '';
        $phone = trim((string) $subscriber->phone);
        if ($phone !== '') {
            if ($this->billingSmsService->isLive()) {
                $template = (string) config('billing.sms_messages.extend', '');
                $company = (string) config('billing.company_name', 'أوليفيا');
                $text = str_replace(
                    ['{name}', '{days}', '{end_date}', '{company}'],
                    [$subscriber->full_name, (string) $days, $newEnd->format('Y-m-d'), $company],
                    $template !== '' ? $template : 'تم تمديد خطك {days} أيام حتى {end_date}.'
                );
                if ($this->billingSmsService->send($phone, $text)) {
                    $smsMsg = ' وتم إرسال رسالة SMS للمشترك.';
                } else {
                    $err = $this->billingSmsService->getLastError();
                    $smsMsg = ' تُنبيه: لم يُرسل SMS'.($err ? ' — '.$err : '').'.';
                }
            } else {
                $smsMsg = ' (SMS غير مفعّل أو ناقص الإعدادات — لم تُرسل رسالة.)';
            }
        } else {
            $smsMsg = ' (لا يوجد رقم هاتف للمشترك — لم تُرسل رسالة.)';
        }

        ActivityLogger::success('subscriber.extended', "تمديد اشتراك {$subscriber->full_name} لمدة {$days} يوماً — تاريخ الانتهاء الجديد {$newEnd->format('Y-m-d')}.", [
            'subject' => $subscriber,
            'phone' => $phone ?: null,
            'meta' => [
                'days' => $days,
                'new_end_date' => $newEnd->toDateString(),
                'sms_status' => trim($smsMsg) !== '' ? trim($smsMsg) : null,
                'mikrotik_status' => trim($mtMsg) !== '' ? trim($mtMsg) : null,
            ],
        ]);

        return redirect()
            ->route('web.admin.subscribers.show', $subscriber)
            ->with('status', "تم تمديد الاشتراك بـ {$days} أيام — تاريخ الانتهاء الجديد: {$newEnd->format('Y-m-d')}.{$mtMsg}{$smsMsg}");
    }

    /**
     * إضافة دين يدوي (فاتورة مفتوحة بمبلغ محدد) لملف المشترك.
     */
    public function storeManualDebt(Request $request, Subscriber $subscriber): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'amount.required' => 'يرجى إدخال مبلغ الدين.',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر.',
        ]);

        try {
            $invoice = $this->manualDebtService->addManualDebt(
                $subscriber,
                $request->user(),
                (float) $data['amount'],
                $data['notes'] ?? null
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('web.admin.subscribers.show', $subscriber)
                ->with('error', 'تعذّر إضافة الدين: '.$e->getMessage());
        }

        return redirect()
            ->route('web.admin.subscribers.show', $subscriber)
            ->with('status', 'تم تسجيل دين بقيمة '.number_format((float) $invoice->amount, 2).' شيكل (فاتورة '.$invoice->invoice_number.').');
    }

    /**
     * حذف مشترك:
     *   - mode=program  : حذف من البرنامج فقط (DB + حساب تطبيق العميل) ويبقى السر على المايكروتيك.
     *   - mode=all      : حذف من البرنامج + إزالة سر PPPoE وفصل الجلسة على المايكروتيك.
     *
     * تتطلب تأكيداً نصياً بكتابة اسم المستخدم PPPoE لتفادي الحذف بالخطأ.
     */
    public function destroy(Request $request, Subscriber $subscriber): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['program', 'all'])],
            'confirm_username' => ['required', 'string'],
        ], [
            'mode.required' => 'يرجى اختيار وضع الحذف.',
            'mode.in' => 'وضع الحذف غير صالح.',
            'confirm_username.required' => 'يرجى كتابة اسم PPPoE للمشترك للتأكيد.',
        ]);

        if (trim((string) $data['confirm_username']) !== trim((string) $subscriber->pppoe_username)) {
            return redirect()
                ->route('web.admin.subscribers.show', $subscriber)
                ->with('error', 'تأكيد الحذف غير مطابق لاسم PPPoE — لم يُحذف المشترك.');
        }

        $mode = $data['mode'];
        $subscriber->loadMissing('router', 'user');

        $mtMsg = '';
        if ($mode === 'all') {
            try {
                $this->mikrotikService->deletePppoeUser($subscriber);
                $mtMsg = ' وتمت إزالة سر PPPoE من المايكروتيك.';
            } catch (\Throwable $e) {
                return redirect()
                    ->route('web.admin.subscribers.show', $subscriber)
                    ->with('error', 'فشل حذف المستخدم من المايكروتيك — أُلغيت العملية بالكامل: '.$e->getMessage());
            }
        }

        $portalUser = $subscriber->user;
        $fullName = $subscriber->full_name;
        $username = $subscriber->pppoe_username;

        try {
            DB::transaction(function () use ($subscriber, $portalUser) {
                $subscriber->delete();

                if ($portalUser && $portalUser->role === 'subscriber') {
                    $portalUser->delete();
                }
            });
        } catch (\Throwable $e) {
            Log::error('فشل حذف المشترك من قاعدة البيانات', [
                'subscriber_id' => $subscriber->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('web.admin.subscribers')
                ->with('error', 'تعذّر حذف المشترك من قاعدة البيانات: '.$e->getMessage());
        }

        Log::info('تم حذف مشترك', [
            'subscriber_id' => $subscriber->id,
            'pppoe_username' => $username,
            'mode' => $mode,
            'by' => optional($request->user())->id,
        ]);

        $modeLabel = $mode === 'all' ? 'من البرنامج والمايكروتيك' : 'من البرنامج فقط';

        ActivityLogger::warning('subscriber.deleted', "حذف المشترك {$fullName} ({$username}) — {$modeLabel}.", [
            'subject_label' => $fullName,
            'meta' => [
                'pppoe_username' => $username,
                'mode' => $mode,
                'subscriber_id' => $subscriber->id,
            ],
        ]);

        return redirect()
            ->route('web.admin.subscribers')
            ->with('status', "تم حذف المشترك «{$fullName}» ({$username}) {$modeLabel}.{$mtMsg}");
    }
}
