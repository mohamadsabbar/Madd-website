<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillingSmsService
{
    protected ?string $lastError = null;

    /**
     * جاهز للإرسال الفعلي (مفعّل + رابط + مفتاح id).
     */
    public function isLive(): bool
    {
        if (! filter_var(config('billing.sms.enabled', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        if (trim((string) config('billing.sms.url', '')) === '') {
            return false;
        }

        if (trim((string) config('billing.sms.api_key', '')) === '') {
            return false;
        }

        return true;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * هل يدل نص الرد على أن المزوّد رفض الإرسال (بينما HTTP قد يكون 200).
     */
    protected function responseIndicatesSmsFailure(string $rawBody): bool
    {
        $b = trim($rawBody);
        if ($b === '') {
            return false;
        }

        if (stripos($b, 'Invalid') !== false) {
            return true;
        }

        if (preg_match('/\bH\d{3,}\b/u', $b)) {
            return true;
        }

        $lower = mb_strtolower($b, 'UTF-8');
        foreach (['error', 'failed', 'failure', 'refused', 'reject'] as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        foreach (['خطأ', 'فشل', 'رفض', 'غير صالح'] as $kw) {
            if (str_contains($b, $kw)) {
                return true;
            }
        }

        if ($lower === 'false' || $b === '0') {
            return true;
        }

        return false;
    }

    /**
     * إرسال SMS. يُرجع false إذا لم يُرسل فعلياً أو رفض المزوّد الطلب.
     */
    public function send(string $phone, string $message): bool
    {
        $this->lastError = null;

        $phone = $this->normalizePhone($phone);
        if ($phone === '') {
            $this->lastError = 'رقم الهاتف فارغ أو غير صالح بعد التطبيع.';

            Log::warning('Billing SMS: رقم هاتف فارغ');

            return false;
        }

        if (! $this->isLive()) {
            $this->lastError = 'الإرسال غير مفعّل أو ينقص المفتاح: BILLING_SMS_ENABLED و BILLING_SMS_API_URL و BILLING_SMS_API_KEY في .env';

            Log::info('Billing SMS (لم يُرسل — غير مفعّل أو ناقص الإعدادات)', [
                'phone' => $phone,
            ]);

            return false;
        }

        $url = (string) config('billing.sms.url');
        $body = $this->buildBody($phone, $message);
        $useForm = config('billing.sms.body_style', 'json') === 'form';
        $forcePost = filter_var(config('billing.sms.force_post', false), FILTER_VALIDATE_BOOL);
        $maxGetLen = (int) config('billing.sms.max_get_url_length', 1800);

        try {
            $req = Http::timeout((int) config('billing.sms.timeout', 20))
                ->withOptions([
                    'verify' => filter_var(config('billing.sms.verify_ssl', true), FILTER_VALIDATE_BOOL),
                ]);

            $headers = array_filter((array) config('billing.sms.headers', []));
            if ($headers !== []) {
                $req = $req->withHeaders($headers);
            }

            $method = strtoupper((string) config('billing.sms.method', 'GET'));

            if ($method === 'GET') {
                $qs = http_build_query($body, '', '&', PHP_QUERY_RFC3986);
                $sep = str_contains($url, '?') ? '&' : '?';
                $getUrlLength = strlen($url.$sep.$qs);

                if ($forcePost || $getUrlLength > $maxGetLen) {
                    Log::info('Billing SMS: إرسال عبر POST', [
                        'phone' => $phone,
                        'reason' => $forcePost ? 'BILLING_SMS_FORCE_POST' : 'long_get_url',
                        'get_url_length' => $getUrlLength,
                    ]);
                    $response = $req->asForm()->post($url, $body);
                } else {
                    $response = $this->httpGetWithUtf8Query($req, $url, $body);
                }
            } elseif ($useForm) {
                $response = $req->asForm()->post($url, $body);
            } else {
                $response = $req->asJson()->post($url, $body);
            }

            if ($response->successful()) {
                $rawBody = trim((string) $response->body());
                if ($rawBody !== '' && $this->responseIndicatesSmsFailure($rawBody)) {
                    $this->lastError = 'رد المزوّد: '.Str::limit($rawBody, 300);
                    Log::warning('Billing SMS رفض المزوّد', ['phone' => $phone, 'reply' => Str::limit($rawBody, 500)]);

                    return false;
                }

                Log::info('Billing SMS أُرسل', ['phone' => $phone, 'reply' => Str::limit($rawBody, 200)]);

                return true;
            }

            $this->lastError = 'HTTP '.$response->status().': '.Str::limit((string) $response->body(), 200);
            Log::warning('Billing SMS فشل HTTP', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return false;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('Billing SMS استثناء', ['error' => $e->getMessage(), 'phone' => $phone]);

            return false;
        }
    }

    /**
     * GET مع ترميز UTF-8 للنص العربي في الاستعلام (بعض خوادم ASP تتطلب ذلك).
     *
     * @param  array<string, string|int|float>  $query
     */
    protected function httpGetWithUtf8Query(\Illuminate\Http\Client\PendingRequest $req, string $url, array $query): \Illuminate\Http\Client\Response
    {
        $qs = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $sep = str_contains($url, '?') ? '&' : '?';

        return $req->get($url.$sep.$qs);
    }

    /**
     * @param  array<string, mixed>|null  $template
     * @return array<string, mixed>
     */
    protected function buildBody(string $phone, string $message, ?array $template = null): array
    {
        $template = $template ?? (array) config('billing.sms.body', []);
        $replacements = [
            '@phone' => $phone,
            '@message' => $message,
            '@sender' => (string) config('billing.sms.sender', ''),
            '@api_key' => (string) config('billing.sms.api_key', ''),
        ];

        $out = [];
        foreach ($template as $key => $value) {
            if (is_string($value)) {
                $v = $value;
                foreach ($replacements as $token => $rep) {
                    $v = str_replace($token, $rep, $v);
                }
                $out[$key] = $v;
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        $format = (string) config('billing.sms.phone_format', 'ps970');
        if ($format !== 'ps970') {
            return $digits;
        }

        if (str_starts_with($digits, '00970')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '970')) {
            return strlen($digits) >= 12 ? substr($digits, 0, 12) : $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '970'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '970'.$digits;
        }

        return $digits;
    }
}
