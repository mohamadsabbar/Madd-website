<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscriber;
use App\Services\AgentRenewalService;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentRenewalService $agentRenewalService
    ) {}

    public function searchSubscribers(Request $request)
    {
        $query = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $term = $query['q'];

        return Subscriber::with('plan')
            ->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('pppoe_username', 'like', "%{$term}%");
            })
            ->limit(20)
            ->get();
    }

    public function renew(Request $request, Subscriber $subscriber)
    {
        $subscriber->loadMissing('plan');

        $updated = $this->agentRenewalService->renewSubscription($subscriber, $request->user());

        return $updated->load('plan');
    }

    public function collections(Request $request)
    {
        $payments = Payment::with('invoice.subscriber')
            ->where('collected_by', $request->user()->id)
            ->latest('paid_at')
            ->get();

        return response()->json([
            'total_collected' => $payments->sum('amount'),
            'payments' => $payments,
        ]);
    }
}
