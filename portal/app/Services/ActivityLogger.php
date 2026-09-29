<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * مسجّل العمليات الداخلية: يكتب أحداثاً منظَّمة في جدول activity_logs
 * ليستعرضها الأدمن من «سجل العمليات». لا يرمي استثناءات للحفاظ على المسار الرئيسي.
 */
class ActivityLogger
{
    /**
     * تسجيل حدث عام.
     *
     * @param  array{
     *     subject?: \Illuminate\Database\Eloquent\Model|null,
     *     subject_label?: string|null,
     *     phone?: string|null,
     *     amount?: float|int|null,
     *     severity?: string,
     *     actor?: \App\Models\User|null,
     *     meta?: array<string, mixed>|null
     * }  $opts
     */
    public static function log(string $type, string $message, array $opts = []): ?ActivityLog
    {
        try {
            $actor = $opts['actor'] ?? Auth::user();
            $subject = $opts['subject'] ?? null;
            $severity = $opts['severity'] ?? ActivityLog::SEVERITY_INFO;

            $payload = [
                'type' => $type,
                'severity' => $severity,
                'message' => $message,
                'meta' => $opts['meta'] ?? null,
                'phone' => $opts['phone'] ?? null,
                'amount' => $opts['amount'] ?? null,
                'ip' => self::safeIp(),
            ];

            if ($actor instanceof User) {
                $payload['actor_id'] = $actor->id;
                $payload['actor_name'] = $actor->name;
                $payload['actor_role'] = $actor->role ?? null;
            }

            if ($subject) {
                $payload['subject_type'] = class_basename($subject);
                $payload['subject_id'] = $subject->getKey();
                $payload['subject_label'] = $opts['subject_label']
                    ?? self::extractLabel($subject);
            } elseif (! empty($opts['subject_label'])) {
                $payload['subject_label'] = $opts['subject_label'];
            }

            return ActivityLog::create($payload);
        } catch (\Throwable $e) {
            Log::warning('ActivityLogger: تعذّر تسجيل الحدث', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function success(string $type, string $message, array $opts = []): ?ActivityLog
    {
        $opts['severity'] = ActivityLog::SEVERITY_SUCCESS;

        return self::log($type, $message, $opts);
    }

    public static function warning(string $type, string $message, array $opts = []): ?ActivityLog
    {
        $opts['severity'] = ActivityLog::SEVERITY_WARNING;

        return self::log($type, $message, $opts);
    }

    public static function error(string $type, string $message, array $opts = []): ?ActivityLog
    {
        $opts['severity'] = ActivityLog::SEVERITY_ERROR;

        return self::log($type, $message, $opts);
    }

    /**
     * تُنشئ نص لقطة من الموديل (الاسم/الاسم الكامل/البريد…)
     */
    protected static function extractLabel(object $subject): ?string
    {
        foreach (['full_name', 'name', 'invoice_number', 'email', 'phone'] as $attr) {
            $val = data_get($subject, $attr);
            if (is_string($val) && $val !== '') {
                return $val;
            }
        }

        if ($subject instanceof Subscriber) {
            return (string) $subject->id;
        }

        return null;
    }

    protected static function safeIp(): ?string
    {
        try {
            return Request::ip();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
