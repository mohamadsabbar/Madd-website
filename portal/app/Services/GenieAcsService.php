<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GenieACS NBI (port 7557) — قراءة/كتابة راوتر الزبون المنزلي عبر TR-069.
 *
 * DS-3WR12C / DS-3WR12C-H / DS-3WR12GC-H: TR-069 TR-098 وTR-181،
 * نطاقا 2.4 و5 GHz (غالباً WLANConfiguration.1 و.2)، وأجهزة مرتبطة عبر AssociatedDevice.
 */
class GenieAcsService
{
    /**
     * @var list<string>
     */
    private const WIFI_REFRESH_OBJECTS = [
        'InternetGatewayDevice.LANDevice.1.WLANConfiguration',
        'InternetGatewayDevice.LANDevice.1.Hosts',
        'Device.WiFi',
        'Device.Hosts',
    ];

    /**
     * @var list<string>
     */
    private const WIFI_PARAMETER_NAMES = [
        'InternetGatewayDevice.LANDevice.1.WLANConfiguration.',
        'InternetGatewayDevice.LANDevice.1.Hosts.',
        'Device.WiFi.',
        'Device.Hosts.',
    ];

    public function isConfigured(): bool
    {
        return trim((string) config('services.genieacs.nbi_url')) !== '';
    }

    /**
     * يثبّت provision يسحب SSID/كلمة المرور/الأجهزة عند كل Inform.
     * بدون ذلك تبقى شجرة Hikvision فارغة (DeviceInfo/WAN فقط).
     */
    public function syncWifiProvision(): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $script = <<<'JS'
const every = Date.now() - (Date.now() % (6 * 3600 * 1000));

declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.SSID", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.Enable", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.Status", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.Channel", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.Standard", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.KeyPassphrase", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.PreSharedKey.*.KeyPassphrase", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.PreSharedKey.*.PreSharedKey", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.X_TP_PreSharedKey", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.AssociatedDevice.*.AssociatedDeviceMACAddress", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.AssociatedDevice.*.AssociatedDeviceIPAddress", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.*.AssociatedDevice.*.AssociatedDeviceHostName", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.Hosts.Host.*.HostName", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.Hosts.Host.*.MACAddress", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.Hosts.Host.*.IPAddress", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.Hosts.Host.*.Active", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.Hosts.Host.*.InterfaceType", {path: every, value: every});
declare("InternetGatewayDevice.LANDevice.1.Hosts.Host.*.Layer2Interface", {path: every, value: every});

declare("Device.WiFi.SSID.*.SSID", {path: every, value: every});
declare("Device.WiFi.SSID.*.Enable", {path: every, value: every});
declare("Device.WiFi.Radio.*.OperatingFrequencyBand", {path: every, value: every});
declare("Device.WiFi.AccessPoint.*.Security.KeyPassphrase", {path: every, value: every});
declare("Device.WiFi.AccessPoint.*.Security.PreSharedKey", {path: every, value: every});
declare("Device.WiFi.AccessPoint.*.AssociatedDevice.*.MACAddress", {path: every, value: every});
declare("Device.WiFi.AccessPoint.*.AssociatedDevice.*.IPAddress", {path: every, value: every});
declare("Device.WiFi.AccessPoint.*.AssociatedDevice.*.HostName", {path: every, value: every});
declare("Device.Hosts.Host.*.HostName", {path: every, value: every});
declare("Device.Hosts.Host.*.MACAddress", {path: every, value: every});
declare("Device.Hosts.Host.*.IPAddress", {path: every, value: every});
declare("Device.Hosts.Host.*.Active", {path: every, value: every});
JS;

        $provisionUrl = $this->nbi().'/provisions/wifi-params';
        $provision = $this->httpRaw()->withBody($script, 'application/javascript')->put($provisionUrl);
        if ($provision->failed()) {
            throw new \RuntimeException('تعذّر حفظ سكربت الواي فاي في GenieACS.');
        }

        $preset = $this->http()->put($this->nbi().'/presets/wifi-params', [
            'weight' => 20,
            'precondition' => 'true',
            'configurations' => [
                [
                    'type' => 'provision',
                    'name' => 'wifi-params',
                    'args' => null,
                ],
            ],
        ]);
        if ($preset->failed()) {
            throw new \RuntimeException('تعذّر حفظ إعداد الواي فاي في GenieACS.');
        }
    }

    public function ensureWifiProvision(): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            Cache::remember('genieacs.wifi_provision_synced', 21600, function () {
                $this->syncWifiProvision();

                return true;
            });
        } catch (\Throwable $e) {
            Log::info('GenieACS wifi provision sync skipped', ['error' => $e->getMessage()]);
        }
    }

    /**
     * الرقم التسلسلي أو MAC المكتوب خلف الراوتر (Hikvision DS-3WR12C).
     *
     * @return array<string, mixed>|null
     */
    public function findBySerial(string $serial): ?array
    {
        $serial = $this->normalizeSerial($serial);
        if ($serial === '') {
            return null;
        }

        $exact = $this->uniqueDevice($this->queryDevices(['_deviceId._SerialNumber' => $serial]));
        if ($exact !== null) {
            return $exact;
        }

        $regex = $this->uniqueDevice($this->queryDevices([
            '_deviceId._SerialNumber' => [
                '$regex' => '^'.preg_quote($serial, '/').'$',
                '$options' => 'i',
            ],
        ]));
        if ($regex !== null) {
            return $regex;
        }

        $mac = $this->normalizeMac($serial);
        if (strlen($mac) === 12) {
            $colon = implode(':', str_split($mac, 2));
            $dash = implode('-', str_split($mac, 2));
            foreach ([$mac, strtolower($mac), $colon, strtolower($colon), $dash] as $candidate) {
                $byMac = $this->uniqueDevice(
                    $this->queryDevices(['_macs' => $candidate]),
                    'وجد أكثر من راوتر بنفس عنوان MAC. تواصل مع الدعم.',
                );
                if ($byMac !== null) {
                    return $byMac;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDevice(string $deviceId): ?array
    {
        $rows = $this->queryDevices(['_id' => $deviceId]);

        return $rows[0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $device
     * @return array{
     *     ssid:?string,
     *     password:?string,
     *     bands:list<array{path:string,ssid:string,band:string,guest:bool}>,
     *     last_inform:?string,
     *     serial:?string,
     *     online:bool,
     *     model:?string,
     *     manufacturer:?string,
     *     product_class:?string,
     *     vendor:string,
     *     dual_band:bool,
     *     wifi_available:bool
     * }
     */
    public function wifiFromDevice(array $device): array
    {
        $flat = $this->flatten($device);
        $bands = [];
        foreach ($flat as $path => $value) {
            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }
            if (! preg_match('/(?:WLANConfiguration|WiFi\.SSID)\.(\d+)\.SSID$/', $path, $m)) {
                continue;
            }
            if (str_ends_with($path, 'BSSID')) {
                continue;
            }
            $ssid = trim((string) $value);
            if ($ssid === '') {
                continue;
            }
            $index = (int) $m[1];
            $bands[] = [
                'path' => $path,
                'ssid' => $ssid,
                'band' => $this->guessBand($path, $index, $flat),
                'guest' => $this->isGuestSsid($ssid),
            ];
        }

        $main = array_values(array_filter($bands, fn (array $b) => $b['guest'] === false));
        $ssid = ($main[0]['ssid'] ?? $bands[0]['ssid'] ?? null);

        $password = null;
        foreach ($flat as $path => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if ($this->isPasswordPath($path)) {
                $password = (string) $value;
                break;
            }
        }

        $lastInform = $device['_lastInform'] ?? null;
        $serial = data_get($device, '_deviceId._SerialNumber');
        $productClass = data_get($device, '_deviceId._ProductClass');
        $manufacturer = data_get($device, '_deviceId._Manufacturer');
        $model = is_string($productClass) && $productClass !== ''
            ? $productClass
            : (is_string(data_get($device, 'InternetGatewayDevice.DeviceInfo.ModelName._value'))
                ? (string) data_get($device, 'InternetGatewayDevice.DeviceInfo.ModelName._value')
                : null);

        $bandNames = array_unique(array_column($main !== [] ? $main : $bands, 'band'));

        return [
            'ssid' => is_string($ssid) && $ssid !== '' ? $ssid : null,
            'password' => $password,
            'bands' => $bands,
            'last_inform' => is_string($lastInform) ? $lastInform : null,
            'serial' => is_string($serial) ? $serial : null,
            'online' => $this->isRecentlyInformed($lastInform),
            'model' => is_string($model) && $model !== '' ? $model : null,
            'manufacturer' => is_string($manufacturer) && $manufacturer !== '' ? $manufacturer : null,
            'product_class' => is_string($productClass) && $productClass !== '' ? $productClass : null,
            'vendor' => $this->detectVendor($device),
            'dual_band' => in_array('2.4GHz', $bandNames, true) && in_array('5GHz', $bandNames, true),
            'wifi_available' => $ssid !== null && $ssid !== '',
        ];
    }

    /**
     * @param  array<string, mixed>  $device
     * @return list<array{name:string,mac:?string,ip:?string,active:bool,kind:string}>
     */
    public function hostsFromDevice(array $device): array
    {
        $flat = $this->flatten($device);
        $byKey = [];

        foreach ($flat as $path => $value) {
            if (! preg_match('/Hosts\.Host\.(\d+)\.(HostName|MACAddress|IPAddress|Active|InterfaceType|Layer2Interface)$/', $path, $m)) {
                continue;
            }
            $key = 'host-'.$m[1];
            $byKey[$key][$m[2]] = $value;
        }

        foreach ($flat as $path => $value) {
            if (preg_match('/WLANConfiguration\.(\d+)\.AssociatedDevice\.(\d+)\.(AssociatedDeviceMACAddress|AssociatedDeviceIPAddress|AssociatedDeviceHostName)$/', $path, $m)) {
                $key = 'assoc-'.$m[1].'-'.$m[2];
                $field = match ($m[3]) {
                    'AssociatedDeviceMACAddress' => 'MACAddress',
                    'AssociatedDeviceIPAddress' => 'IPAddress',
                    default => 'HostName',
                };
                $byKey[$key][$field] = $value;
                $byKey[$key]['Active'] = true;
                $byKey[$key]['InterfaceType'] = '802.11';
                continue;
            }
            if (preg_match('/WiFi\.AccessPoint\.(\d+)\.AssociatedDevice\.(\d+)\.(MACAddress|IPAddress|HostName)$/', $path, $m)) {
                $key = 'ap-'.$m[1].'-'.$m[2];
                $byKey[$key][$m[3]] = $value;
                $byKey[$key]['Active'] = true;
                $byKey[$key]['InterfaceType'] = '802.11';
            }
        }

        $hosts = [];
        $seenMac = [];
        foreach ($byKey as $row) {
            $mac = isset($row['MACAddress']) ? strtoupper(trim((string) $row['MACAddress'])) : null;
            $name = trim((string) ($row['HostName'] ?? ''));
            $ip = isset($row['IPAddress']) ? trim((string) $row['IPAddress']) : null;
            $active = filter_var($row['Active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($name === '' && ($mac === null || $mac === '')) {
                continue;
            }
            if ($mac !== null && $mac !== '') {
                if (isset($seenMac[$mac])) {
                    $i = $seenMac[$mac];
                    if ($hosts[$i]['name'] === ($mac) && $name !== '') {
                        $hosts[$i]['name'] = $name;
                    }
                    if ($hosts[$i]['ip'] === null && $ip !== '' && $ip !== null) {
                        $hosts[$i]['ip'] = $ip;
                    }
                    $hosts[$i]['active'] = $hosts[$i]['active'] || $active;
                    continue;
                }
                $seenMac[$mac] = count($hosts);
            }
            $hosts[] = [
                'name' => $name !== '' ? $name : ($mac ?? 'جهاز'),
                'mac' => $mac !== '' ? $mac : null,
                'ip' => $ip !== '' ? $ip : null,
                'active' => $active,
                'kind' => $this->guessDeviceKind($name, (string) ($row['InterfaceType'] ?? $row['Layer2Interface'] ?? '')),
            ];
        }

        usort($hosts, function (array $a, array $b) {
            return [$b['active'], $a['name']] <=> [$a['active'], $b['name']];
        });

        return $hosts;
    }

    public function setWifi(string $deviceId, ?string $ssid, ?string $password): void
    {
        $device = $this->getDevice($deviceId) ?? [];
        $flat = $this->flatten($device);
        $ssidPaths = $this->discoveredSsidPaths($flat);
        $passwordPaths = $this->discoveredPasswordPaths($flat);

        if (($ssid !== null && $ssid !== '' && $ssidPaths === [])
            || ($password !== null && $password !== '' && $passwordPaths === [])) {
            $this->refreshWifi($deviceId);
            $device = $this->getDevice($deviceId) ?? $device;
            $flat = $this->flatten($device);
            $ssidPaths = $this->discoveredSsidPaths($flat);
            $passwordPaths = $this->discoveredPasswordPaths($flat);
        }

        if ($ssidPaths === []) {
            $ssidPaths = $this->fallbackSsidPaths($flat, $device);
        }
        if ($passwordPaths === []) {
            $passwordPaths = $this->fallbackPasswordPaths($device, $ssidPaths);
        }

        $pairs = [];
        if ($ssid !== null && $ssid !== '') {
            if ($ssidPaths === []) {
                throw new \RuntimeException('الراوتر مربوط لكن إعدادات الواي فاي غير ظاهرة بعد. انتظر دقيقة حتى يبلغ الجهاز نظام الإدارة ثم أعد المحاولة.');
            }
            foreach ($ssidPaths as $path) {
                $pairs[] = [$path, $ssid, 'xsd:string'];
            }
        }

        if ($password !== null && $password !== '') {
            if ($passwordPaths === []) {
                throw new \RuntimeException('تعذّر إيجاد مسار كلمة مرور الواي فاي على هذا الراوتر.');
            }
            foreach ($passwordPaths as $path) {
                $pairs[] = [$path, $password, 'xsd:string'];
            }
        }

        if ($pairs === []) {
            throw new \RuntimeException('لا يوجد ما يتم تحديثه.');
        }

        $this->postTask($deviceId, [
            'name' => 'setParameterValues',
            'parameterValues' => $pairs,
        ]);
    }

    public function reboot(string $deviceId): void
    {
        $this->postTask($deviceId, ['name' => 'reboot']);
    }

    public function refreshHosts(string $deviceId): void
    {
        $this->refreshWifi($deviceId);
    }

    public function refreshWifi(string $deviceId): void
    {
        try {
            $this->postTask($deviceId, [
                'name' => 'getParameterValues',
                'parameterNames' => self::WIFI_PARAMETER_NAMES,
            ]);

            return;
        } catch (\Throwable $e) {
            Log::info('GenieACS getParameterValues Wi-Fi skipped', ['error' => $e->getMessage()]);
        }

        foreach (self::WIFI_REFRESH_OBJECTS as $i => $objectName) {
            try {
                $this->postTask($deviceId, [
                    'name' => 'refreshObject',
                    'objectName' => $objectName,
                ], connectionRequest: $i === 0);
            } catch (\Throwable $e) {
                Log::info('GenieACS refreshObject skipped', [
                    'object' => $objectName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function normalizeSerial(string $serial): string
    {
        $serial = strtoupper(trim($serial));

        return preg_replace('/[\s:.-]+/', '', $serial) ?? $serial;
    }

    public function encodeDeviceId(string $deviceId): string
    {
        return str_replace('%', '%25', rawurlencode($deviceId));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>|null
     */
    private function uniqueDevice(array $rows, string $duplicateMessage = 'وجد أكثر من راوتر بنفس الرقم التسلسلي. تواصل مع الدعم.'): ?array
    {
        if (count($rows) === 1) {
            return $rows[0];
        }
        if (count($rows) > 1) {
            throw new \RuntimeException($duplicateMessage);
        }

        return null;
    }

    public function normalizeMac(string $value): string
    {
        return strtoupper(preg_replace('/[^A-F0-9]/i', '', $value) ?? '');
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<array<string, mixed>>
     */
    private function queryDevices(array $query): array
    {
        $response = $this->http()->get($this->nbi().'/devices/', [
            'query' => json_encode($query, JSON_UNESCAPED_SLASHES),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('تعذّر الاتصال بنظام إدارة الراوترات (GenieACS).');
        }

        $json = $response->json();
        if (! is_array($json)) {
            return [];
        }

        return array_values(array_filter($json, 'is_array'));
    }

    /**
     * @param  array<string, mixed>  $task
     */
    private function postTask(string $deviceId, array $task, bool $connectionRequest = true): void
    {
        $url = $this->nbi().'/devices/'.$this->encodeDeviceId($deviceId).'/tasks';
        if ($connectionRequest) {
            $url .= '?timeout=8000&connection_request';
        }

        $response = $this->http()->post($url, $task);
        if ($response->failed() && $response->status() !== 202) {
            Log::warning('GenieACS task failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 400),
                'task' => $task['name'] ?? null,
            ]);
            throw new \RuntimeException('الراوتر لم يستجب الآن. تأكد أنه متصل بالكهرباء والإنترنت ثم أعد المحاولة.');
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function flatten(array $node, string $prefix = ''): array
    {
        $out = [];
        foreach ($node as $key => $value) {
            if (! is_string($key) && ! is_int($key)) {
                continue;
            }
            $key = (string) $key;
            if (str_starts_with($key, '_')) {
                continue;
            }
            $path = $prefix === '' ? $key : $prefix.'.'.$key;
            if (is_array($value) && array_key_exists('_value', $value)) {
                $out[$path] = $value['_value'];
            } elseif (is_array($value)) {
                $out = array_merge($out, $this->flatten($value, $path));
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $flat
     * @return list<string>
     */
    private function discoveredSsidPaths(array $flat): array
    {
        $paths = [];
        foreach ($flat as $path => $value) {
            if (! preg_match('/(?:WLANConfiguration|WiFi\.SSID)\.(\d+)\.SSID$/', $path, $m)) {
                continue;
            }
            if (str_ends_with($path, 'BSSID')) {
                continue;
            }
            $ssid = trim((string) $value);
            if ($this->isGuestSsid($ssid)) {
                continue;
            }
            $paths[] = $path;
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  array<string, mixed>  $flat
     * @param  array<string, mixed>  $device
     * @return list<string>
     */
    private function fallbackSsidPaths(array $flat, array $device): array
    {
        if ($this->treeHas($flat, 'Device.WiFi')) {
            return [
                'Device.WiFi.SSID.1.SSID',
                'Device.WiFi.SSID.2.SSID',
            ];
        }

        if ($this->isHikvision($device) || $this->treeHas($flat, 'InternetGatewayDevice')) {
            return [
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.SSID',
            ];
        }

        return [
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
            'Device.WiFi.SSID.1.SSID',
        ];
    }

    /**
     * @param  array<string, mixed>  $flat
     * @return list<string>
     */
    private function discoveredPasswordPaths(array $flat): array
    {
        $paths = [];
        foreach (array_keys($flat) as $path) {
            if ($this->isPasswordPath($path) && ! $this->passwordPathIsGuest($path, $flat)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  array<string, mixed>  $device
     * @param  list<string>  $ssidPaths
     * @return list<string>
     */
    private function fallbackPasswordPaths(array $device, array $ssidPaths): array
    {
        $derived = [];
        foreach ($ssidPaths as $ssidPath) {
            if (preg_match('/^(.*WLANConfiguration\.\d+)\.SSID$/', $ssidPath, $m)) {
                $derived[] = $m[1].'.KeyPassphrase';
                continue;
            }
            if (preg_match('/^Device\.WiFi\.SSID\.(\d+)\.SSID$/', $ssidPath, $m)) {
                $derived[] = 'Device.WiFi.AccessPoint.'.$m[1].'.Security.KeyPassphrase';
            }
        }

        if ($derived !== []) {
            return array_values(array_unique($derived));
        }

        if ($this->isHikvision($device)) {
            return [
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase',
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.KeyPassphrase',
            ];
        }

        return [
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase',
            'Device.WiFi.AccessPoint.1.Security.KeyPassphrase',
        ];
    }

    private function isPasswordPath(string $path): bool
    {
        if (str_contains($path, 'SSID')) {
            return false;
        }

        return (bool) preg_match('/(?:KeyPassphrase|X_TP_PreSharedKey|PreSharedKey\.\d+\.(?:PreSharedKey|KeyPassphrase)|AccessPoint\.\d+\.Security\.PreSharedKey)$/', $path);
    }

    /**
     * @param  array<string, mixed>  $flat
     */
    private function passwordPathIsGuest(string $path, array $flat): bool
    {
        if (! preg_match('/WLANConfiguration\.(\d+)\./', $path, $m)) {
            return false;
        }
        $ssid = trim((string) ($flat['InternetGatewayDevice.LANDevice.1.WLANConfiguration.'.$m[1].'.SSID'] ?? ''));

        return $this->isGuestSsid($ssid);
    }

    /**
     * @param  array<string, mixed>  $flat
     */
    private function treeHas(array $flat, string $prefix): bool
    {
        foreach (array_keys($flat) as $path) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function isHikvision(array $device): bool
    {
        return $this->detectVendor($device) === 'hikvision';
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function detectVendor(array $device): string
    {
        $blob = strtoupper(implode(' ', array_filter([
            (string) data_get($device, '_deviceId._Manufacturer'),
            (string) data_get($device, '_deviceId._ProductClass'),
            (string) data_get($device, '_deviceId._OUI'),
        ])));

        return match (true) {
            str_contains($blob, 'HIKVISION') || str_contains($blob, 'DS-3WR') => 'hikvision',
            str_contains($blob, 'MERCUSYS') || str_contains($blob, 'TP-LINK') || str_contains($blob, 'TPLINK') => 'mercusys',
            default => 'generic',
        };
    }

    /**
     * @param  array<string, mixed>  $flat
     */
    private function guessBand(string $path, int $index, array $flat): string
    {
        $prefix = preg_replace('/\.SSID$/', '', $path) ?? $path;
        $candidates = [
            $flat[$prefix.'.OperatingFrequencyBand'] ?? null,
            $flat[$prefix.'.X_Frequency'] ?? null,
            $flat[$prefix.'.Band'] ?? null,
        ];
        foreach ($candidates as $raw) {
            $t = strtoupper((string) $raw);
            if (str_contains($t, '5')) {
                return '5GHz';
            }
            if (str_contains($t, '2.4') || str_contains($t, '24')) {
                return '2.4GHz';
            }
        }

        $channel = (int) ($flat[$prefix.'.Channel'] ?? 0);
        if ($channel >= 36) {
            return '5GHz';
        }
        if ($channel >= 1 && $channel <= 14) {
            return '2.4GHz';
        }

        if (preg_match('/WiFi\.SSID\.(\d+)/', $path, $m)) {
            $radio = $flat['Device.WiFi.SSID.'.$m[1].'.LowerLayers'] ?? null;
            if (is_string($radio) && preg_match('/Radio\.(\d+)/', $radio, $r)) {
                $band = $flat['Device.WiFi.Radio.'.$r[1].'.OperatingFrequencyBand'] ?? null;
                if (is_string($band) && str_contains($band, '5')) {
                    return '5GHz';
                }
                if (is_string($band) && str_contains($band, '2.4')) {
                    return '2.4GHz';
                }
            }
        }

        if (str_contains($path, 'WLANConfiguration.5') || $index >= 5) {
            return '5GHz';
        }
        if ($index === 2) {
            return '5GHz';
        }

        return '2.4GHz';
    }

    private function isGuestSsid(string $ssid): bool
    {
        return (bool) preg_match('/guest|ضيف|visitor|guestnet/i', $ssid);
    }

    private function guessDeviceKind(string $name, string $iface): string
    {
        $t = mb_strtolower($name.' '.$iface);

        return match (true) {
            str_contains($t, 'iphone') || str_contains($t, 'ios') => 'phone',
            str_contains($t, 'ipad') || str_contains($t, 'tablet') || str_contains($t, 'android') => 'tablet',
            str_contains($t, 'tv') || str_contains($t, 'samsung') => 'tv',
            str_contains($t, 'playstation') || str_contains($t, 'xbox') || str_contains($t, 'nintendo') => 'console',
            str_contains($t, 'laptop') || str_contains($t, 'macbook') || str_contains($t, 'windows') || str_contains($t, 'pc') => 'laptop',
            default => 'generic',
        };
    }

    private function isRecentlyInformed(mixed $lastInform): bool
    {
        if (! is_string($lastInform) || $lastInform === '') {
            return false;
        }
        $at = strtotime($lastInform);

        return $at !== false && $at > time() - 900;
    }

    private function http(): PendingRequest
    {
        $req = Http::timeout(max(5, (int) config('services.genieacs.timeout', 20)))
            ->acceptJson()
            ->asJson();

        return $this->withAuth($req);
    }

    private function httpRaw(): PendingRequest
    {
        $req = Http::timeout(max(5, (int) config('services.genieacs.timeout', 20)));

        return $this->withAuth($req);
    }

    private function withAuth(PendingRequest $req): PendingRequest
    {
        $user = trim((string) config('services.genieacs.username'));
        $pass = (string) config('services.genieacs.password');
        if ($user !== '') {
            $req = $req->withBasicAuth($user, $pass);
        }

        return $req;
    }

    private function nbi(): string
    {
        return rtrim((string) config('services.genieacs.nbi_url'), '/');
    }
}
