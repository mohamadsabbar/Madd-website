<?php

namespace App\Services;

use App\Models\Subscriber;

class CustomerWifiService
{
    public function __construct(private GenieAcsService $acs) {}

    /**
     * @return array<string, mixed>
     */
    public function status(Subscriber $subscriber, bool $refresh = false): array
    {
        if (! $this->acs->isConfigured()) {
            return $this->payload(false, false, message: 'إدارة الراوتر المنزلي غير مفعّلة بعد. سيظهر التحكم بعد ربط GenieACS.', code: 'acs_unconfigured');
        }

        $this->acs->ensureWifiProvision();

        if (! filled($subscriber->acs_device_id)) {
            return $this->payload(true, false, message: 'أدخل الرقم التسلسلي أو عنوان MAC الموجود خلف راوتر Hikvision لربطه بحسابك.', code: 'not_linked');
        }

        if ($refresh) {
            try {
                $this->acs->refreshWifi((string) $subscriber->acs_device_id);
            } catch (\Throwable) {
                // قائمة الأجهزة تبقى من آخر بيانات معروفة
            }
        }

        $device = $this->acs->getDevice((string) $subscriber->acs_device_id);
        if (! $device) {
            return $this->payload(true, false, message: 'الراوتر غير ظاهر في نظام الإدارة حالياً. تأكد أنه يعمل ثم أعد المحاولة.', code: 'device_missing');
        }

        $wifi = $this->acs->wifiFromDevice($device);
        $hosts = $this->acs->hostsFromDevice($device);

        $message = null;
        $code = null;
        if (! $wifi['wifi_available']) {
            $message = $wifi['vendor'] === 'hikvision'
                ? 'الراوتر مربوط. إن لم يظهر اسم الشبكة بعد، اسحب للأسفل للتحديث — DS-3WR12C يرسل إعدادات الواي فاي بعد إبلاغ نظام الإدارة.'
                : 'الراوتر مربوط لكن اسم الشبكة لم يصل بعد. اسحب للأسفل للتحديث.';
            $code = 'wifi_pending';
        } elseif ($hosts === []) {
            $message = 'قائمة الأجهزة تظهر عند اتصال الهواتف بالواي فاي وبعد تحديث الصفحة.';
            $code = 'hosts_empty';
        }

        return $this->payload(true, true, [
            'online' => (bool) $wifi['online'],
            'ssid' => $wifi['ssid'],
            'password' => $wifi['password'],
            'serial' => $wifi['serial'] ?? $subscriber->acs_serial,
            'last_inform' => $wifi['last_inform'],
            'bands' => $wifi['bands'],
            'devices' => $hosts,
            'device_count' => count($hosts),
            'active_count' => count(array_filter($hosts, fn (array $h) => $h['active'] === true)),
            'model' => $wifi['model'],
            'manufacturer' => $wifi['manufacturer'],
            'product_class' => $wifi['product_class'],
            'vendor' => $wifi['vendor'],
            'dual_band' => $wifi['dual_band'],
            'wifi_available' => $wifi['wifi_available'],
        ], message: $message, code: $code);
    }

    /**
     * @return array<string, mixed>
     */
    public function claim(Subscriber $subscriber, string $serial): array
    {
        if (! $this->acs->isConfigured()) {
            throw new \RuntimeException('إدارة الراوتر غير مفعّلة على الخادم.');
        }

        $serial = $this->acs->normalizeSerial($serial);
        if ($serial === '') {
            throw new \InvalidArgumentException('أدخل الرقم التسلسلي أو عنوان MAC خلف الراوتر.');
        }

        $device = $this->acs->findBySerial($serial);
        if (! $device) {
            throw new \RuntimeException('لم يُعثر على راوتر بهذا الرقم. تأكد من الرقم التسلسلي أو MAC خلف الجهاز وأن الراوتر متصل بالإنترنت.');
        }

        $deviceId = (string) ($device['_id'] ?? '');
        if ($deviceId === '') {
            throw new \RuntimeException('تعذّر قراءة معرّف الراوتر.');
        }

        $taken = Subscriber::query()
            ->where('acs_device_id', $deviceId)
            ->where('id', '!=', $subscriber->id)
            ->exists();
        if ($taken) {
            throw new \RuntimeException('هذا الراوتر مرتبط باشتراك آخر. تواصل مع الدعم.');
        }

        $deviceSerial = data_get($device, '_deviceId._SerialNumber');
        $subscriber->acs_serial = is_string($deviceSerial) && $deviceSerial !== ''
            ? $this->acs->normalizeSerial($deviceSerial)
            : $serial;
        $subscriber->acs_device_id = $deviceId;
        $subscriber->save();

        try {
            $this->acs->refreshWifi($deviceId);
        } catch (\Throwable) {
            // الربط نجح؛ الإعدادات تُستكمل عند أول Inform
        }

        return $this->status($subscriber->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function updateWifi(Subscriber $subscriber, ?string $ssid, ?string $password): array
    {
        $this->requireLinked($subscriber);
        $this->acs->setWifi((string) $subscriber->acs_device_id, $ssid, $password);

        return $this->status($subscriber, refresh: false);
    }

    /**
     * @return array<string, mixed>
     */
    public function reboot(Subscriber $subscriber): array
    {
        $this->requireLinked($subscriber);
        $this->acs->reboot((string) $subscriber->acs_device_id);

        return [
            'ok' => true,
            'message' => 'تم إرسال أمر إعادة التشغيل. قد ينقطع الإنترنت لدقيقة أو اثنتين.',
        ];
    }

    private function requireLinked(Subscriber $subscriber): void
    {
        if (! $this->acs->isConfigured()) {
            throw new \RuntimeException('إدارة الراوتر غير مفعّلة على الخادم.');
        }
        if (! filled($subscriber->acs_device_id)) {
            throw new \RuntimeException('اربط الراوتر أولاً بالرقم التسلسلي أو عنوان MAC.');
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(bool $ok, bool $linked, array $extra = [], ?string $message = null, ?string $code = null): array
    {
        return array_merge([
            'ok' => $ok,
            'linked' => $linked,
            'code' => $code,
            'message' => $message,
            'online' => false,
            'ssid' => null,
            'password' => null,
            'serial' => null,
            'last_inform' => null,
            'bands' => [],
            'devices' => [],
            'device_count' => 0,
            'active_count' => 0,
            'model' => null,
            'manufacturer' => null,
            'product_class' => null,
            'vendor' => null,
            'dual_band' => false,
            'wifi_available' => false,
        ], $extra);
    }
}
