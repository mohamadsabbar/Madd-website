<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['nullable', 'email', 'required_without_all:phone,username,login'],
            'phone' => ['nullable', 'string', 'max:50', 'required_without_all:email,username,login'],
            'username' => ['nullable', 'string', 'max:100', 'required_without_all:email,phone,login'],
            'login' => ['nullable', 'string', 'max:100', 'required_without_all:email,phone,username'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->resolveUser($credentials);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        if (! $user->is_active) {
            abort(403, 'User is disabled.');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $user->loadMissing('subscriberRecord.plan', 'subscriberRecord.router');

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function resolveUser(array $credentials): ?User
    {
        if (! empty($credentials['email'])) {
            return User::where('email', $credentials['email'])->first();
        }

        if (! empty($credentials['phone'])) {
            return User::query()
                ->where('phone', $credentials['phone'])
                ->where('role', 'subscriber')
                ->first();
        }

        $username = $credentials['username'] ?? $credentials['login'] ?? null;
        if (! $username) {
            return null;
        }

        // هاتف كـ login
        $byPhone = User::query()
            ->where('role', 'subscriber')
            ->where('phone', $username)
            ->first();
        if ($byPhone) {
            return $byPhone;
        }

        // اسم مستخدم PPP
        $sub = Subscriber::query()
            ->where('pppoe_username', $username)
            ->whereNotNull('user_id')
            ->first();

        return $sub?->user;
    }
}
