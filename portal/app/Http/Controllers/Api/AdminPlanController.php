<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class AdminPlanController extends Controller
{
    public function index()
    {
        return Plan::orderBy('price')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:plans,name'],
            'speed_mbps' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'mikrotik_profile' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return Plan::create($data);
    }

    public function show(Plan $admin_plan)
    {
        return $admin_plan;
    }

    public function update(Request $request, Plan $admin_plan)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100', 'unique:plans,name,'.$admin_plan->id],
            'speed_mbps' => ['sometimes', 'integer', 'min:1'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'duration_days' => ['sometimes', 'integer', 'min:1'],
            'mikrotik_profile' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $admin_plan->update($data);

        return $admin_plan->refresh();
    }

    public function destroy(Plan $admin_plan)
    {
        $admin_plan->delete();

        return response()->noContent();
    }
}
