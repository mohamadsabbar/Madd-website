<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\BillingSmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSmsController extends Controller
{
    public function __construct(private readonly BillingSmsService $sms)
    {
    }

    public function index(): View
    {
        return view('admin.sms', [
            'smsConfigured' => $this->sms->isLive(),
            'subscribers' => Subscriber::query()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'phone', 'router_id']),
            'routers' => Router::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'audience' => ['required', 'in:all,subscriber,router'],
            'subscriber_id' => ['required_if:audience,subscriber', 'nullable', 'exists:subscribers,id'],
            'router_id' => ['required_if:audience,router', 'nullable', 'exists:routers,id'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $recipients = match ($data['audience']) {
            'all' => Subscriber::query()
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->get(),
            'subscriber' => Subscriber::query()
                ->whereKey($data['subscriber_id'])
                ->get(),
            'router' => Subscriber::query()
                ->where('router_id', $data['router_id'])
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->get(),
        };

        if ($recipients->isEmpty()) {
            return redirect()->route('web.admin.sms')->with(
                'error',
                'لا يوجد مستلمون: تأكد من وجود أرقام هواتف أو اختر راوتراً فيه مشتركون.'
            );
        }

        if ($data['audience'] === 'subscriber') {
            $one = $recipients->first();
            if ($one && (trim((string) $one->phone) === '')) {
                return redirect()->route('web.admin.sms')->with('error', 'المشترك المختار لا يملك رقماً في النظام.');
            }
        }

        if (! $this->sms->isLive()) {
            return redirect()->route('web.admin.sms')->with(
                'error',
                'الإرسال الفعلي غير جاهز: فعّل BILLING_SMS_ENABLED=true وضع BILLING_SMS_API_URL و BILLING_SMS_API_KEY (قيمة id من HTD) و BILLING_SMS_SENDER ثم نفّذ php artisan config:clear.'
            );
        }

        $success = 0;
        $fail = 0;
        $failures = [];
        $delayMs = max(0, (int) config('billing.sms.between_messages_ms', 150));

        foreach ($recipients as $subscriber) {
            $text = str_replace(
                ['{name}', '{phone}', '{id}'],
                [$subscriber->full_name, (string) $subscriber->phone, (string) $subscriber->id],
                $data['message']
            );

            if ($this->sms->send($subscriber->phone, $text)) {
                $success++;
            } else {
                $fail++;
                $failures[] = [
                    'subscriber_id' => $subscriber->id,
                    'name' => $subscriber->full_name,
                    'phone' => $subscriber->phone,
                    'error' => $this->sms->getLastError(),
                ];
            }

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $summary = "تمت المحاولة: نجح {$success}، فشل {$fail} (من أصل ".$recipients->count().').';
        if ($fail > 0 && $this->sms->getLastError()) {
            $summary .= ' آخر سبب: '.$this->sms->getLastError();
        }

        $audienceLabel = match ($data['audience']) {
            'all' => 'كل المشتركين',
            'router' => 'مشتركي راوتر #'.$data['router_id'],
            'subscriber' => 'مشترك مفرد #'.$data['subscriber_id'],
            default => $data['audience'],
        };

        ActivityLogger::log(
            'sms.bulk',
            "إرسال SMS جماعي إلى {$audienceLabel}: نجح {$success}، فشل {$fail}.",
            [
                'severity' => $fail === 0
                    ? \App\Models\ActivityLog::SEVERITY_SUCCESS
                    : ($success === 0 ? \App\Models\ActivityLog::SEVERITY_ERROR : \App\Models\ActivityLog::SEVERITY_WARNING),
                'meta' => [
                    'audience' => $data['audience'],
                    'subscriber_id' => $data['subscriber_id'] ?? null,
                    'router_id' => $data['router_id'] ?? null,
                    'success' => $success,
                    'fail' => $fail,
                    'total' => $recipients->count(),
                    'message_preview' => mb_substr($data['message'], 0, 200),
                    'failures' => array_slice($failures, 0, 50),
                ],
            ]
        );

        if ($success === 0 && $fail > 0) {
            return redirect()->route('web.admin.sms')->with('error', $summary);
        }

        return redirect()->route('web.admin.sms')->with('status', $summary);
    }
}
