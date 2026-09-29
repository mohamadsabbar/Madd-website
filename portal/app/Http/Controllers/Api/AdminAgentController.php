<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAgentController extends Controller
{
    public function index()
    {
        return User::where('role', 'agent')->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $data['role'] = 'agent';
        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }

    public function show(User $agent)
    {
        abort_if($agent->role !== 'agent', 404);

        return $agent;
    }

    public function update(Request $request, User $agent)
    {
        abort_if($agent->role !== 'agent', 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$agent->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['sometimes', 'string', 'min:6'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $agent->update($data);

        return $agent->refresh();
    }

    public function destroy(User $agent)
    {
        abort_if($agent->role !== 'agent', 404);
        $agent->delete();

        return response()->noContent();
    }
}
