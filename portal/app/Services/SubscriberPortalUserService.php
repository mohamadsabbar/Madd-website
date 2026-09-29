<?php

namespace App\Services;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SubscriberPortalUserService
{
    public function defaultPassword(): string
    {
        return (string) config('subscriber_portal.default_password', '100200300');
    }

    /**
     * ينشئ أو يربط مستخدم تطبيق عميل (دور subscriber) بنفس رقم هاتف المشترك.
     */
    public function ensurePortalUser(Subscriber $subscriber): ?User
    {
        $subscriber->refresh();

        if ($this->shouldSkipPhone($subscriber->phone ?? '')) {
            return null;
        }

        $subscriber->loadMissing('user');

        if ($subscriber->user_id && $subscriber->user) {
            $this->syncUserFromSubscriber($subscriber);

            return $subscriber->user->fresh();
        }

        $existing = User::query()
            ->where('phone', $subscriber->phone)
            ->where('role', 'subscriber')
            ->first();

        if ($existing !== null) {
            $otherSubscriber = Subscriber::query()
                ->where('user_id', $existing->id)
                ->where('id', '!=', $subscriber->id)
                ->exists();
            if ($otherSubscriber) {
                throw new \RuntimeException('رقم الهاتف مستخدم لحساب عميل آخر في النظام.');
            }

            $subscriber->user_id = $existing->id;
            $subscriber->save();
            $this->syncUserFromSubscriber($subscriber);

            return $existing->fresh();
        }

        $user = User::query()->create([
            'name' => $subscriber->full_name,
            'email' => 'subscriber_'.$subscriber->id.'@portal.olivia',
            'phone' => $subscriber->phone,
            'role' => 'subscriber',
            'is_active' => true,
            'password' => Hash::make($this->defaultPassword()),
        ]);

        $subscriber->user_id = $user->id;
        $subscriber->save();

        return $user;
    }

    public function syncUserFromSubscriber(Subscriber $subscriber): void
    {
        if (! $subscriber->user_id) {
            return;
        }

        $user = User::query()->find($subscriber->user_id);
        if (! $user || $user->role !== 'subscriber') {
            return;
        }

        $user->update([
            'name' => $subscriber->full_name,
            'phone' => $subscriber->phone,
        ]);
    }

    protected function shouldSkipPhone(string $phone): bool
    {
        $phone = trim($phone);

        return $phone === '' || str_starts_with($phone, 'import-');
    }
}
