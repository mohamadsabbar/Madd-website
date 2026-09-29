<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AgentSettlement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\AgentRenewalService;
use App\Services\SubscriberBillingSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentDashboardController extends Controller
{
    public function __construct(
        private readonly AgentRenewalService $agentRenewalService,
        private readonly SubscriberBillingSyncService $billingSyncService,
    ) {}

    public function dashboard(Request $request): View
    {
        /** @var User $agent */
        $agent = $request->user();
        $agentId = $agent->id;
        $rate = (float) $agent->commission_percent / 100;

        $lastSettlementAt = AgentSettlement::where('agent_id', $agentId)->max('created_at');
        $paymentsBase = Payment::where('collected_by', $agentId);
        if ($lastSettlementAt) {
            $paymentsBase->where('created_at', '>', $lastSettlementAt);
        }

        $paidInvoicesTotal = (int) (clone $paymentsBase)
            ->selectRaw('COUNT(DISTINCT invoice_id) as c')
            ->value('c');

        $totalCollected = (float) (clone $paymentsBase)->sum('amount');
        $totalRemittedToCompany = 0.0;

        $commissionEarnedTotal = round($totalCollected * $rate, 2);
        $companyShareAfterCommission = round(max(0, $totalCollected - $commissionEarnedTotal), 2);
        $pendingRemittanceToCompany = round(max(0, $companyShareAfterCommission - $totalRemittedToCompany), 2);
        $commissionPaid = 0.0;
        $commissionRemaining = round(max(0, $commissionEarnedTotal - $commissionPaid), 2);

        $paymentsCountTotal = (clone $paymentsBase)->count();

        return view('agent.dashboard', [
            'agent' => $agent,
            'paidInvoicesTotal' => $paidInvoicesTotal,
            'paymentsCountTotal' => $paymentsCountTotal,
            'totalCollected' => $totalCollected,
            'totalRemittedToCompany' => $totalRemittedToCompany,
            'companyShareAfterCommission' => $companyShareAfterCommission,
            'pendingRemittanceToCompany' => $pendingRemittanceToCompany,
            'commissionEarnedTotal' => $commissionEarnedTotal,
            'commissionPaid' => $commissionPaid,
            'commissionRemaining' => $commissionRemaining,
        ]);
    }

    public function search(Request $request): View
    {
        $q = $request->query('q');
        $subscribers = collect();

        if ($q) {
            $subscribers = Subscriber::with('plan')
                ->where(function ($query) use ($q) {
                    $query->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('pppoe_username', 'like', "%{$q}%");
                })
                ->limit(50)
                ->get();

            $agent = $request->user();
            foreach ($subscribers as $subscriber) {
                $summary = $this->billingSyncService->billingSummary($subscriber, $agent);
                $subscriber->invoices_sum_amount = $summary['unpaidTotal'];
                $subscriber->unpaid_invoices_count = $summary['unpaidInvoicesCount'];
                $subscriber->renewal_cycle_amount = $summary['renewalCycleAmount'];
                $subscriber->collect_total = $summary['collectTotal'];
                $subscriber->needs_separate_renewal = $summary['needsSeparateRenewal'];
            }
        }

        return view('agent.search', [
            'subscribers' => $subscribers,
            'query' => $q,
        ]);
    }

    public function payments(Request $request): View
    {
        $agentId = $request->user()->id;

        $payments = Payment::with(['invoice.subscriber', 'invoice.plan'])
            ->where('collected_by', $agentId)
            ->latest('paid_at')
            ->paginate(20)
            ->withQueryString();

        return view('agent.payments', [
            'payments' => $payments,
            'totalCollected' => (float) Payment::where('collected_by', $agentId)->sum('amount'),
        ]);
    }

    public function invoices(Request $request): View
    {
        $agentId = $request->user()->id;

        $invoices = Invoice::query()
            ->with(['subscriber', 'plan', 'payments'])
            ->where(function ($q) use ($agentId) {
                $q->where('created_by', $agentId)
                    ->orWhereHas('payments', fn ($p) => $p->where('collected_by', $agentId));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('agent.invoices', [
            'invoices' => $invoices,
        ]);
    }

    public function showSubscriber(Request $request, Subscriber $subscriber): View
    {
        $subscriber->load(['plan', 'router']);

        $billing = $this->billingSyncService->billingSummary($subscriber, $request->user());

        $recentInvoices = Invoice::query()
            ->where('subscriber_id', $subscriber->id)
            ->with(['plan', 'payments'])
            ->latest()
            ->limit(20)
            ->get();

        return view('agent.subscriber-show', [
            'subscriber' => $subscriber,
            'recentInvoices' => $recentInvoices,
            'unpaidTotal' => $billing['unpaidTotal'],
            'unpaidInvoicesCount' => $billing['unpaidInvoicesCount'],
            'renewalCycleAmount' => $billing['renewalCycleAmount'],
            'collectTotal' => $billing['collectTotal'],
            'needsSeparateRenewal' => $billing['needsSeparateRenewal'],
        ]);
    }

    public function renew(Request $request, Subscriber $subscriber): RedirectResponse
    {
        $fromSearch = $request->boolean('from_search');
        $searchUrl = fn () => redirect()->route('web.agent.search', ['q' => $request->input('return_q', '')]);

        $data = $request->validate([
            'payment_amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $paymentAmount = $request->filled('payment_amount')
            ? round((float) $data['payment_amount'], 2)
            : null;

        try {
            $this->agentRenewalService->collectPayment($subscriber, $request->user(), $paymentAmount);

            $msg = $paymentAmount !== null
                ? 'تم تسجيل تحصيل '.number_format($paymentAmount, 2).' شيكل بنجاح.'
                : 'تم التجديد والتحصيل بنجاح.';

            if ($fromSearch) {
                return $searchUrl()->with('status', $msg);
            }

            return redirect()
                ->route('web.agent.subscribers.show', $subscriber)
                ->with('status', $msg);
        } catch (\Throwable $e) {
            if ($fromSearch) {
                return $searchUrl()->with('error', 'تعذّر التحصيل: '.$e->getMessage());
            }

            return redirect()
                ->route('web.agent.subscribers.show', $subscriber)
                ->with('error', 'تعذّر التحصيل: '.$e->getMessage());
        }
    }
}
