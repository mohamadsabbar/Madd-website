<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Services\CustomerPortalService;
use App\Services\CustomerWifiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerWifiController extends Controller
{
    public function __construct(
        private CustomerPortalService $portal,
        private CustomerWifiService $wifi,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);

        try {
            return response()->json($this->wifi->status(
                $subscriber,
                $request->boolean('refresh'),
            ));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function claim(Request $request): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);
        $data = $request->validate([
            'serial' => ['required', 'string', 'max:100'],
        ]);

        try {
            return response()->json($this->wifi->claim($subscriber, $data['serial']));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);
        $data = $request->validate([
            'ssid' => ['nullable', 'string', 'min:1', 'max:32'],
            'password' => ['nullable', 'string', 'min:8', 'max:63'],
        ]);

        if (! isset($data['ssid']) && ! isset($data['password'])) {
            return response()->json(['message' => 'أدخل اسم الشبكة أو كلمة المرور.'], 422);
        }

        try {
            $status = $this->wifi->updateWifi(
                $subscriber,
                $data['ssid'] ?? null,
                $data['password'] ?? null,
            );
            $status['message'] = 'تم إرسال التعديل للراوتر. قد تحتاج الأجهزة لإعادة الاتصال.';

            return response()->json($status);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reboot(Request $request): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);

        try {
            return response()->json($this->wifi->reboot($subscriber));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function requireSubscriber(Request $request): Subscriber
    {
        $subscriber = $this->portal->subscriberFor($request->user());
        if (! $subscriber instanceof Subscriber) {
            abort(response()->json(['message' => 'لا يوجد اشتراك مرتبط بهذا الحساب.'], 422));
        }

        return $subscriber;
    }
}
