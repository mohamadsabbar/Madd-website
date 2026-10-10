<?php

namespace App\Services;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetOtpService
{
    public function __construct(private BillingSmsService $sms)
    {
    }

    public function requestOtp(string $login): array
    {
        $user = $this->resolveSubscriberUser($login);
        if (! $user) {
            // Avoid account enumeration
            return ['ok' => true, 'message' => 'إن وُجد الحساب ستصلك رسالة برمز التحقق.'];
        }

        $phone = trim((string) ($user->phone ?: ''));
        if ($phone === '') {
            throw new \RuntimeException('لا يوجد رقم هاتف مرتبط بهذا الحساب.');
        }

        PasswordResetOtp::query()->where('user_id', $user->id)->delete();

        $code = (string) random_int(100000, 999999);
        PasswordResetOtp::query()->create([
            'user_id' => $user->id,
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $company = (string) config('billing.company_name', 'مدد');
        $message = "رمز استعادة كلمة المرور من {$company}: {$code} (صالح 10 دقائق)";

        if ($this->sms->isLive()) {
            if (! $this->sms->send($phone, $message)) {
                throw new \RuntimeException($this->sms->getLastError() ?: 'تعذّر إرسال رمز التحقق.');
            }
        } elseif (app()->environment('local', 'testing')) {
            // Dev fallback — code only in non-production
            return [
                'ok' => true,
                'message' => 'إن وُجد الحساب ستصلك رسالة برمز التحقق.',
                'dev_code' => $code,
            ];
        } else {
            throw new \RuntimeException('خدمة الرسائل غير مفعّلة حالياً. تواصل مع الدعم.');
        }

        return ['ok' => true, 'message' => 'إن وُجد الحساب ستصلك رسالة برمز التحقق.'];
    }

    public function resetPassword(string $login, string $code, string $password): User
    {
        $user = $this->resolveSubscriberUser($login);
        if (! $user) {
            throw new \RuntimeException('بيانات غير صحيحة.');
        }

        $otp = PasswordResetOtp::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw new \RuntimeException('انتهت صلاحية الرمز. اطلب رمزاً جديداً.');
        }

        if ($otp->attempts >= 5) {
            throw new \RuntimeException('تم تجاوز عدد المحاولات. اطلب رمزاً جديداً.');
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw new \RuntimeException('رمز التحقق غير صحيح.');
        }

        $user->password = $password;
        $user->save();
        PasswordResetOtp::query()->where('user_id', $user->id)->delete();

        return $user;
    }

    private function resolveSubscriberUser(string $login): ?User
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        $byPhone = User::query()
            ->where('role', 'subscriber')
            ->where('phone', $login)
            ->first();
        if ($byPhone) {
            return $byPhone;
        }

        // Digits-only match
        $digits = preg_replace('/\D+/', '', $login) ?? '';
        if ($digits !== '') {
            $candidates = User::query()
                ->where('role', 'subscriber')
                ->whereNotNull('phone')
                ->get();
            foreach ($candidates as $u) {
                $p = preg_replace('/\D+/', '', (string) $u->phone) ?? '';
                if ($p !== '' && ($p === $digits || Str::endsWith($p, $digits) || Str::endsWith($digits, $p))) {
                    return $u;
                }
            }
        }

        return User::query()
            ->where('role', 'subscriber')
            ->where('email', $login)
            ->first();
    }
}
