<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteConfigRecord;
use App\Models\SiteLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteCmsController extends Controller
{
    public function showConfig(): JsonResponse
    {
        $record = SiteConfigRecord::query()->firstOrCreate(
            ['key' => 'main'],
            ['payload' => []]
        );

        return response()->json([
            'ok' => true,
            'updated_at' => optional($record->updated_at)?->toIso8601String(),
            'config' => $record->payload ?? new \stdClass(),
        ]);
    }

    public function updateConfig(Request $request): JsonResponse
    {
        $data = $request->validate([
            'config' => ['required', 'array'],
        ]);

        $record = SiteConfigRecord::query()->updateOrCreate(
            ['key' => 'main'],
            ['payload' => $data['config']]
        );

        return response()->json([
            'ok' => true,
            'updated_at' => optional($record->updated_at)?->toIso8601String(),
            'config' => $record->payload,
        ]);
    }

    public function storeLead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:fiber,programming,business_join'],
            'name' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'area' => ['nullable', 'string', 'max:191'],
            'plan_id' => ['nullable', 'string', 'max:64'],
            'plan_name' => ['nullable', 'string', 'max:191'],
            'message' => ['nullable', 'string', 'max:5000'],
            'meta' => ['nullable', 'array'],
            'source' => ['nullable', 'string', 'max:64'],
        ]);

        $lead = SiteLead::query()->create([
            ...$data,
            'status' => 'new',
            'ip' => $request->ip(),
            'source' => $data['source'] ?? 'website',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'تم استلام طلبك بنجاح. سنتواصل معك قريباً.',
            'lead' => [
                'id' => $lead->id,
                'type' => $lead->type,
                'created_at' => $lead->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function listLeads(Request $request): JsonResponse
    {
        $query = SiteLead::query()->orderByDesc('id');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $leads = $query->limit(200)->get();

        return response()->json([
            'ok' => true,
            'leads' => $leads,
        ]);
    }

    public function updateLeadStatus(Request $request, SiteLead $lead): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:new,contacted,won,lost,archived'],
        ]);

        $lead->update(['status' => $data['status']]);

        return response()->json(['ok' => true, 'lead' => $lead]);
    }
}
