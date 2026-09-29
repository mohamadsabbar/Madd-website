<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AgentSettlement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscriber;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    private function stats(): array
    {
        $today = Carbon::today();

        return [
            'subscribersCount' => Subscriber::count(),
            'onlineSubscribers' => Subscriber::where('is_online', true)->count(),
            'expiredSubscribers' => Subscriber::whereDate('end_date', '<', $today)->count(),
            'todayIncome' => Invoice::whereDate('paid_at', $today)->sum('amount'),
            'monthIncome' => Invoice::whereYear('paid_at', $today->year)
                ->whereMonth('paid_at', $today->month)
                ->sum('amount'),
        ];
    }

    private function statusSummary(): array
    {
        $today = Carbon::today();

        return [
            'statNew' => Subscriber::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'statAwait' => Subscriber::whereDate('end_date', '<', $today)->count(),
            'statOnline' => Subscriber::where('is_online', true)->count(),
            'statActive' => Subscriber::where('status', 'active')->whereDate('end_date', '>=', $today)->count(),
        ];
    }

    private function dashboardCharts(): array
    {
        $today = Carbon::today();
        $incomeByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = (clone $today)->subDays($i);
            $incomeByDay[] = [
                'label' => $d->locale('ar')->translatedFormat('j M'),
                'value' => (float) Invoice::whereDate('paid_at', $d)->sum('amount'),
            ];
        }

        $planRows = Subscriber::query()
            ->selectRaw('plan_id, count(*) as c')
            ->groupBy('plan_id')
            ->with('plan')
            ->get();

        $donutSegments = $planRows->map(fn ($row) => [
            'label' => $row->plan?->name ?? '—',
            'value' => (int) $row->c,
        ])->filter(fn ($x) => $x['value'] > 0)->take(6)->values()->all();

        $prevMonth = (clone $today)->subMonth();
        $prevMonthIncome = (float) Invoice::whereYear('paid_at', $prevMonth->year)
            ->whereMonth('paid_at', $prevMonth->month)
            ->sum('amount');
        $monthIncome = (float) Invoice::whereYear('paid_at', $today->year)
            ->whereMonth('paid_at', $today->month)
            ->sum('amount');
        $monthGrowthPct = $prevMonthIncome > 0
            ? round((($monthIncome - $prevMonthIncome) / $prevMonthIncome) * 100, 2)
            : null;

        return [
            'incomeByDay' => $incomeByDay,
            'donutSegments' => $donutSegments,
            'monthGrowthPct' => $monthGrowthPct,
        ];
    }

    public function index()
    {
        return view('admin.dashboard', array_merge(
            $this->stats(),
            $this->statusSummary(),
            $this->dashboardCharts()
        ));
    }

    public function subscribers()
    {
        $today = Carbon::today();

        $query = Subscriber::with(['plan', 'router'])
            ->withSum([
                'invoices' => fn ($q) => $q->whereIn('status', ['unpaid', 'partial']),
            ], 'amount')
            ->latest();

        $q = trim((string) request('q'));
        if ($q !== '') {
            $query->where(function ($q2) use ($q) {
                $q2->where('full_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('pppoe_username', 'like', "%{$q}%");
            });
        }

        $filter = (string) request('filter', '');
        if ($filter === '' && request()->boolean('online')) {
            $filter = 'online';
        } elseif ($filter === '' && request()->boolean('offline')) {
            $filter = 'offline';
        }

        match ($filter) {
            'online' => $query->where('is_online', true),
            'offline' => $query->where('is_online', false),
            'expired' => $query->whereDate('end_date', '<', $today),
            default => null,
        };

        $activeFilter = in_array($filter, ['online', 'offline', 'expired'], true) ? $filter : null;

        return view('admin.subscribers', array_merge($this->stats(), $this->statusSummary(), [
            'subscribers' => $query->paginate(15)->withQueryString(),
            'activeFilter' => $activeFilter,
        ]));
    }

    public function plans()
    {
        return view('admin.plans', array_merge($this->stats(), [
            'plans' => Plan::orderBy('price')->get(),
            'routers' => Router::query()->where('is_active', true)->orderBy('name')->get(),
        ]));
    }

    public function updatePlan(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:plans,name,'.$plan->id],
            'speed_mbps' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'mikrotik_profile' => ['nullable', 'string', 'max:100'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $plan->update($data);

        return redirect()->route('web.admin.plans')->with('status', 'تم حفظ الباقة.');
    }

    public function destroyPlan(Plan $plan): RedirectResponse
    {
        if ($plan->subscribers()->exists()) {
            return redirect()->route('web.admin.plans')->with(
                'error',
                'لا يمكن حذف الباقة: يوجد مشتركون مرتبطون بها. انقلهم إلى باقة أخرى أولاً.'
            );
        }

        if ($plan->invoices()->exists()) {
            return redirect()->route('web.admin.plans')->with(
                'error',
                'لا يمكن حذف الباقة: يوجد فواتير مرتبطة بها.'
            );
        }

        $plan->delete();

        return redirect()->route('web.admin.plans')->with('status', 'تم حذف الباقة.');
    }

    public function agents()
    {
        $agents = User::query()
            ->where('role', 'agent')
            ->withSum('collections', 'amount')
            ->withSum('agentSettlements', 'amount')
            ->latest()
            ->paginate(15);

        return view('admin.agents', array_merge($this->stats(), [
            'agents' => $agents,
        ]));
    }

    public function settleAgent(User $agent): View
    {
        if ($agent->role !== 'agent') {
            abort(404);
        }

        $lastSettlementAt = AgentSettlement::where('agent_id', $agent->id)->max('created_at');
        $paymentsBase = Payment::where('collected_by', $agent->id);
        if ($lastSettlementAt) {
            $paymentsBase->where('created_at', '>', $lastSettlementAt);
        }

        $totalCollected = (float) (clone $paymentsBase)->sum('amount');
        $totalRemitted = 0.0;
        $pending = round(max(0, $totalCollected), 2);

        $settlements = AgentSettlement::query()
            ->where('agent_id', $agent->id)
            ->with('recorder:id,name')
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.agents-settle', array_merge($this->stats(), [
            'agent' => $agent,
            'totalCollected' => $totalCollected,
            'totalRemitted' => $totalRemitted,
            'pending' => $pending,
            'settlements' => $settlements,
        ]));
    }

    public function storeSettlement(Request $request, User $agent): RedirectResponse
    {
        if ($agent->role !== 'agent') {
            abort(404);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $lastSettlementAt = AgentSettlement::where('agent_id', $agent->id)->max('created_at');
        $paymentsBase = Payment::where('collected_by', $agent->id);
        if ($lastSettlementAt) {
            $paymentsBase->where('created_at', '>', $lastSettlementAt);
        }
        $pending = round(max(0, (float) (clone $paymentsBase)->sum('amount')), 2);
        if ($pending <= 0.0) {
            return redirect()
                ->route('web.admin.agents.settle', $agent)
                ->with('status', 'لا يوجد أي مبلغ جديد للتسوية حالياً.');
        }

        AgentSettlement::query()->create([
            'agent_id' => $agent->id,
            // التسوية تُغلق الدورة بالكامل وتصفّر العدادات لبداية دورة جديدة.
            'amount' => $pending,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('web.admin.agents.settle', $agent)
            ->with('status', 'تم تسجيل تسليم النقد للشركة بنجاح.');
    }
}
