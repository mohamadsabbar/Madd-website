<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\MikrotikService;
use App\Services\SubscriberPortalUserService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminSubscriberController extends Controller
{
    public function __construct(
        private readonly MikrotikService $mikrotikService,
        private readonly SubscriberPortalUserService $subscriberPortalUserService,
    ) {
    }

    public function index(Request $request)
    {
        $query = Subscriber::with(['plan', 'router'])->latest();
        if ($request->boolean('is_online')) {
            $query->where('is_online', true);
        }

        return $query->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'pppoe_username' => ['required', 'string', 'max:100', 'unique:subscribers,pppoe_username'],
            'pppoe_password' => ['required', 'string', 'max:100'],
            'plan_id' => ['required', 'exists:plans,id'],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'router_id' => ['required', 'exists:routers,id'],
            'start_date' => ['required', 'date'],
        ]);

        Plan::findOrFail($data['plan_id']);
        if (array_key_exists('monthly_price', $data)) {
            $data['monthly_price'] = $data['monthly_price'] === null ? null : round((float) $data['monthly_price'], 2);
        }
        $data['end_date'] = Subscriber::cycleEndDateAfterDate($data['start_date']);

        $subscriber = Subscriber::create($data);
        $this->mikrotikService->createPppoeUser($subscriber);
        try {
            $this->subscriberPortalUserService->ensurePortalUser($subscriber->fresh());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $subscriber->load(['plan', 'router']);
    }

    public function show(Subscriber $admin_subscriber)
    {
        return $admin_subscriber->load(['plan', 'router', 'invoices']);
    }

    public function update(Request $request, Subscriber $admin_subscriber)
    {
        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'pppoe_password' => ['sometimes', 'string', 'max:100'],
            'plan_id' => ['sometimes', 'exists:plans,id'],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'router_id' => ['sometimes', 'exists:routers,id'],
            'status' => ['sometimes', 'string', 'in:active,suspended'],
            'end_date' => ['sometimes', 'date'],
        ]);

        if (array_key_exists('monthly_price', $data)) {
            $data['monthly_price'] = $data['monthly_price'] === null ? null : round((float) $data['monthly_price'], 2);
        }

        $admin_subscriber->update($data);
        try {
            $this->subscriberPortalUserService->ensurePortalUser($admin_subscriber->fresh());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $admin_subscriber->refresh()->load(['plan', 'router']);
    }

    public function destroy(Subscriber $admin_subscriber)
    {
        $admin_subscriber->delete();

        return response()->noContent();
    }

    public function suspend(Subscriber $subscriber)
    {
        $subscriber->update([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        $this->mikrotikService->disablePppoeUser($subscriber);

        ActivityLogger::warning('subscriber.suspended', "تعليق يدوي للمشترك {$subscriber->full_name} (تعطيل PPPoE).", [
            'subject' => $subscriber,
            'phone' => $subscriber->phone,
            'meta' => ['source' => 'manual_api'],
        ]);

        return response()->json(['message' => 'Subscriber suspended']);
    }

    public function reconnect(Subscriber $subscriber)
    {
        $subscriber->update([
            'status' => 'active',
            'suspended_at' => null,
        ]);

        $this->mikrotikService->enablePppoeUser($subscriber);

        ActivityLogger::success('subscriber.reconnected', "إعادة وصل المشترك {$subscriber->full_name} (تفعيل PPPoE).", [
            'subject' => $subscriber,
            'phone' => $subscriber->phone,
            'meta' => ['source' => 'manual_api'],
        ]);

        return response()->json(['message' => 'Subscriber reconnected']);
    }

    public function renew(Request $request, Subscriber $subscriber)
    {
        $data = $request->validate([
            'plan_id' => ['nullable', 'exists:plans,id'],
        ]);

        if (isset($data['plan_id'])) {
            $subscriber->plan_id = $data['plan_id'];
        }

        Plan::findOrFail($subscriber->plan_id);
        $baseDate = Carbon::parse($subscriber->end_date)->isFuture()
            ? Carbon::parse($subscriber->end_date)
            : Carbon::today();

        $subscriber->end_date = Subscriber::cycleEndDateAfterDate($baseDate);
        $subscriber->status = 'active';
        $subscriber->suspended_at = null;
        $subscriber->save();

        $this->mikrotikService->enablePppoeUser($subscriber);

        ActivityLogger::success('subscriber.renewed', "تجديد دورة المشترك {$subscriber->full_name} حتى {$subscriber->end_date->format('Y-m-d')}.", [
            'subject' => $subscriber,
            'phone' => $subscriber->phone,
            'meta' => [
                'plan_id' => $subscriber->plan_id,
                'new_end_date' => $subscriber->end_date->toDateString(),
                'source' => 'admin_api',
            ],
        ]);

        return $subscriber->refresh()->load(['plan', 'router']);
    }
}
