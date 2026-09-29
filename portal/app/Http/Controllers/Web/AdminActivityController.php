<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityController extends Controller
{
    /**
     * عرض سجل العمليات الداخلية مع فلاتر وإحصائيات اليوم.
     */
    public function index(Request $request): View
    {
        $today = Carbon::today();

        $type = $request->string('type')->toString();
        $category = $request->string('category')->toString();
        $severity = $request->string('severity')->toString();
        $q = $request->string('q')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        $query = ActivityLog::query()
            ->with('actor:id,name,role')
            ->latest('created_at');

        if ($type !== '') {
            $query->where('type', $type);
        }
        if ($category !== '') {
            $query->where(function ($qq) use ($category) {
                $qq->where('type', 'like', $category.'.%')
                    ->orWhere('type', $category);
            });
        }
        if ($severity !== '') {
            $query->where('severity', $severity);
        }
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('message', 'like', "%{$q}%")
                    ->orWhere('subject_label', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('actor_name', 'like', "%{$q}%");
            });
        }
        if ($from) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $logs = $query->paginate(40)->withQueryString();

        $stats = [
            'today_total' => ActivityLog::whereDate('created_at', $today)->count(),
            'today_payments' => (float) ActivityLog::whereDate('created_at', $today)
                ->whereIn('type', ['payment.received', 'subscription.renewed_by_agent'])
                ->sum('amount'),
            'today_payment_count' => ActivityLog::whereDate('created_at', $today)
                ->whereIn('type', ['payment.received', 'subscription.renewed_by_agent'])
                ->count(),
            'today_suspended' => ActivityLog::whereDate('created_at', $today)
                ->whereIn('type', [
                    'subscriber.suspended',
                    'subscriber.auto_suspended_expired',
                    'subscriber.auto_suspended_unpaid',
                ])->count(),
            'today_extended' => ActivityLog::whereDate('created_at', $today)
                ->where('type', 'subscriber.extended')->count(),
            'today_sms_sent' => (int) ActivityLog::whereDate('created_at', $today)
                ->whereIn('type', ['sms.bulk', 'sms.reminder_batch', 'sms.expiry_reminder_batch'])
                ->get()->sum(fn ($r) => (int) ($r->meta['success'] ?? 0)),
            'today_sms_failed' => (int) ActivityLog::whereDate('created_at', $today)
                ->whereIn('type', ['sms.bulk', 'sms.reminder_batch', 'sms.expiry_reminder_batch'])
                ->get()->sum(fn ($r) => (int) ($r->meta['fail'] ?? 0)),
            'today_errors' => ActivityLog::whereDate('created_at', $today)
                ->where('severity', ActivityLog::SEVERITY_ERROR)->count(),
        ];

        $availableTypes = ActivityLog::query()
            ->select('type')
            ->groupBy('type')
            ->orderBy('type')
            ->pluck('type');

        return view('admin.activity', [
            'logs' => $logs,
            'stats' => $stats,
            'availableTypes' => $availableTypes,
            'filters' => [
                'type' => $type,
                'category' => $category,
                'severity' => $severity,
                'q' => $q,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ]);
    }
}
