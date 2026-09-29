<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Services\CustomerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private CustomerPortalService $portal)
    {
    }

    /** لوحة كاملة — مناسبة لتطبيق الجوال دفعة واحدة */
    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->portal->dashboard($request->user());

        if (! ($data['ok'] ?? false)) {
            return response()->json(['message' => $data['message'] ?? 'خطأ'], 422);
        }

        return response()->json($data);
    }

    public function subscription(Request $request): JsonResponse
    {
        $data = $this->portal->dashboard($request->user());

        if (! ($data['ok'] ?? false)) {
            return response()->json(['message' => $data['message'] ?? 'خطأ'], 422);
        }

        return response()->json([
            'subscriber' => $data['account'],
            'plan' => $data['service'],
            'router' => $data['account']['router'] ?? null,
            'renewal' => $data['renewal'],
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);

        return response()->json([
            'invoices' => $this->portal->allInvoices($subscriber),
        ]);
    }

    public function usage(Request $request): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);

        return response()->json($this->portal->usageByMonth($subscriber, 6));
    }

    public function plans(Request $request): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);

        return response()->json([
            'current' => $this->portal->currentServicePayload($subscriber),
            'plans' => $this->portal->availablePlans($subscriber),
        ]);
    }

    public function requestRenewal(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscriber = $this->requireSubscriber($request);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
        ]);

        // إن أمكن التمديد الذاتي بدون plan_id → نفّذه مباشرة
        $state = $this->portal->selfExtendState($subscriber);
        if (($state['can_self_extend'] ?? false) && empty($data['plan_id'])) {
            try {
                $result = $this->portal->performSelfExtend($user, $subscriber);
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => "تم تمديد اشتراكك تلقائياً لمدة {$result['days']} أيام حتى {$result['new_end_date']}.",
                'self_extend' => true,
                'days' => $result['days'],
                'new_end_date' => $result['new_end_date'],
                'mikrotik' => $result['mikrotik'],
                'renewal' => $this->portal->renewalPayload($result['subscriber']),
            ]);
        }

        try {
            $req = $this->portal->createRenewalRequest(
                $user,
                $subscriber,
                $data['note'] ?? null,
                isset($data['plan_id']) ? (int) $data['plan_id'] : null,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'تم إرسال الطلب. سنتواصل معك قريباً.',
            'self_extend' => false,
            'request' => [
                'id' => $req->id,
                'status' => $req->status,
                'created_at' => $req->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /** تمديد ذاتي صريح */
    public function selfExtend(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscriber = $this->requireSubscriber($request);

        try {
            $result = $this->portal->performSelfExtend($user, $subscriber);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "تم تمديد اشتراكك تلقائياً لمدة {$result['days']} أيام حتى {$result['new_end_date']}.",
            'days' => $result['days'],
            'new_end_date' => $result['new_end_date'],
            'mikrotik' => $result['mikrotik'],
            'account' => $this->portal->accountPayload($result['subscriber']),
            'renewal' => $this->portal->renewalPayload($result['subscriber']),
        ]);
    }

    private function requireSubscriber(Request $request): Subscriber
    {
        $subscriber = $this->portal->subscriberFor($request->user());

        if (! $subscriber instanceof Subscriber) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(['message' => 'لا يوجد اشتراك مرتبط بهذا الحساب.'], 422)
            );
        }

        $subscriber->loadMissing(['plan', 'router']);

        return $subscriber;
    }
}
