<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CustomerAccountController extends Controller
{
    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة.'], 422);
        }

        $user->password = $data['password'];
        $user->save();

        return response()->json(['message' => 'تم تحديث كلمة المرور بنجاح.']);
    }

    public function forgotPassword(Request $request, PasswordResetOtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:100'],
        ]);

        try {
            $result = $otp->requestOtp($data['login']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function resetPassword(Request $request, PasswordResetOtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:12'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $user = $otp->resetPassword($data['login'], $data['code'], $data['password']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'تم تعيين كلمة المرور الجديدة.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function storeDeviceToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'in:android,ios'],
        ]);

        $record = DeviceToken::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $data['token'],
            ],
            ['platform' => $data['platform']]
        );

        return response()->json([
            'message' => 'تم تسجيل الجهاز للإشعارات.',
            'id' => $record->id,
        ]);
    }

    public function destroyDeviceToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['message' => 'تم إلغاء تسجيل الجهاز.']);
    }
}
