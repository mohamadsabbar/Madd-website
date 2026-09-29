<?php

namespace App\Services;

use App\Models\MikrotikPppSecret;
use App\Models\Plan;
use App\Models\Router;
use App\Models\RouterSession;
use App\Models\Subscriber;
use App\Models\SubscriberTrafficSnapshot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RouterOS\Client;
use RouterOS\Query;

class MikrotikService
{
    public function client(Router $router): Client
    {
        return new Client([
            'host' => $router->host,
            'user' => $router->username,
            'pass' => $router->password,
            'port' => (int) $router->api_port,
            'timeout' => config('mikrotik.timeout', 10),
            'socket_timeout' => config('mikrotik.socket_timeout', 25),
            'attempts' => 2,
        ]);
    }

    public function fetchRouterInfo(Router $router): array
    {
        $client = $this->client($router);

        $identity = $client->query(new Query('/system/identity/print'))->read();
        $resource = $client->query(new Query('/system/resource/print'))->read();
        $active = $client->query(new Query('/ppp/active/print'))->read();
        $secrets = $client->query(new Query('/ppp/secret/print'))->read();

        return [
            'identity' => $identity[0] ?? [],
            'resource' => $resource[0] ?? [],
            'ppp_active' => is_array($active) ? $active : [],
            'ppp_active_count' => is_array($active) ? count($active) : 0,
            'ppp_secrets_count' => is_array($secrets) ? count($secrets) : 0,
        ];
    }

    /**
     * مطابقة مشترك بالاسم (بعد trim) وبشكل غير حساس لحالة الأحرف، وربطه بهذا الراوتر.
     */
    public function findSubscriberForRouterByPppName(Router $router, string $pppName): ?Subscriber
    {
        $normalized = mb_strtolower(trim($pppName));

        $q = Subscriber::query()
            ->where('router_id', $router->id)
            ->whereRaw('LOWER(TRIM(pppoe_username)) = ?', [$normalized]);

        return $q->first();
    }

    /**
     * جلب كل أسرار PPPoE من الراوتر (للعرض أو التحليل).
     *
     * @return array<int, array<string, string>>
     */
    public function fetchPppSecrets(Router $router): array
    {
        $client = $this->client($router);
        $rows = $client->query(new Query('/ppp/secret/print'))->read();

        return is_array($rows) ? $rows : [];
    }

    /**
     * أسماء ملفات PPP من المايكروتيك (/ppp profile) — يجب أن تطابق باقة النظام حقل mikrotik_profile.
     *
     * @return array<int, string>
     */
    public function fetchPppProfiles(Router $router): array
    {
        $client = $this->client($router);
        $rows = $client->query(new Query('/ppp/profile/print'))->read();
        if (! is_array($rows)) {
            return [];
        }

        $names = [];
        foreach ($rows as $row) {
            $n = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($n !== '') {
                $names[] = $n;
            }
        }

        $names = array_values(array_unique($names));
        sort($names, SORT_NATURAL);

        return $names;
    }

    /**
     * إنشاء باقة لكل اسم ملف PPP على الراوتر إن لم تكن موجودة، أو ربط باقة بنفس الاسم بملف PPP.
     * السرعة: أول رقم في اسم الملف إن وُجد، وإلا القيمة الافتراضية من الإعدادات.
     *
     * @return array{created:int, linked:int, skipped:int}
     */
    public function createPlansFromMikrotikProfiles(Router $router): array
    {
        $names = $this->fetchPppProfiles($router);
        $created = 0;
        $linked = 0;
        $skipped = 0;

        $price = (float) config('mikrotik.auto_plan_price', 0);
        $duration = (int) config('mikrotik.auto_plan_duration_days', 30);
        $fallbackSpeed = (int) config('mikrotik.auto_plan_speed_fallback', 20);

        foreach ($names as $name) {
            $norm = mb_strtolower(trim($name));

            $byProfile = Plan::query()
                ->whereNotNull('mikrotik_profile')
                ->whereRaw('LOWER(TRIM(mikrotik_profile)) = ?', [$norm])
                ->first();
            if ($byProfile !== null) {
                $skipped++;

                continue;
            }

            $byName = Plan::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [$norm])
                ->first();
            if ($byName !== null) {
                $byName->update(['mikrotik_profile' => $name]);
                $linked++;

                continue;
            }

            $speed = $this->guessSpeedMbpsFromProfileName($name) ?? $fallbackSpeed;

            Plan::query()->create([
                'name' => $name,
                'speed_mbps' => $speed,
                'price' => $price,
                'duration_days' => $duration,
                'mikrotik_profile' => $name,
                'is_active' => true,
            ]);
            $created++;
        }

        return [
            'created' => $created,
            'linked' => $linked,
            'skipped' => $skipped,
        ];
    }

    protected function guessSpeedMbpsFromProfileName(string $name): ?int
    {
        if (preg_match('/(\d{1,5})/u', $name, $m)) {
            $n = (int) $m[1];

            return ($n >= 1 && $n <= 100000) ? $n : null;
        }

        return null;
    }

    /**
     * تحديث plan_id لكل مشترك مرتبط بهذا الراوتر بحسب عمود profile في أسرار PPP على المايكروتيك (مصدر الحقيقة).
     *
     * @param  array<int, array<string, string>>  $rows
     */
    protected function syncSubscriberPlansFromPppSecretRows(Router $router, array $rows): int
    {
        $updated = 0;

        foreach ($rows as $row) {
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                continue;
            }

            $profile = isset($row['profile']) ? trim((string) $row['profile']) : '';
            if ($profile === '') {
                continue;
            }

            $subscriber = $this->findSubscriberForRouterByPppName($router, $name);
            if ($subscriber === null) {
                continue;
            }

            $planId = $this->resolvePlanIdForImport($profile);
            if ((int) $subscriber->plan_id !== $planId) {
                $subscriber->update(['plan_id' => $planId]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * جلب أسرار PPP من الراوتر وتحديث باقة كل مشترك ليطابق profile الحقيقي على المايكروتيك، مع تحديث جدول التحليل.
     *
     * @return array{count:int, by_profile: array<string, int>, deleted_orphans:int, plans_updated:int}
     */
    public function syncSubscribersPlansWithMikrotik(Router $router): array
    {
        return $this->syncPppSecretsToDatabase($router);
    }

    /**
     * حفظ نسخة من الأسرار في قاعدة البيانات مع الـ profile للتحليل.
     *
     * @return array{count:int, by_profile: array<string, int>, deleted_orphans:int, plans_updated:int}
     */
    public function syncPppSecretsToDatabase(Router $router): array
    {
        $rows = $this->fetchPppSecrets($router);
        $savedNames = [];
        $byProfile = [];

        foreach ($rows as $row) {
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                continue;
            }
            $savedNames[] = $name;

            $profile = isset($row['profile']) ? (string) $row['profile'] : '';
            $byProfile[$profile] = ($byProfile[$profile] ?? 0) + 1;

            $subscriber = $this->findSubscriberForRouterByPppName($router, $name);

            MikrotikPppSecret::query()->updateOrCreate(
                [
                    'router_id' => $router->id,
                    'ppp_name' => $name,
                ],
                [
                    'mikrotik_internal_id' => $row['.id'] ?? null,
                    'profile' => $profile !== '' ? $profile : null,
                    'service' => $row['service'] ?? null,
                    'disabled' => $this->parseMikrotikDisabled($row['disabled'] ?? null),
                    'comment' => isset($row['comment']) ? (string) $row['comment'] : null,
                    'subscriber_id' => $subscriber?->id,
                    'synced_at' => now(),
                ]
            );
        }

        $deleted = 0;
        if (count($savedNames) > 0) {
            $deleted = MikrotikPppSecret::query()
                ->where('router_id', $router->id)
                ->whereNotIn('ppp_name', array_unique($savedNames))
                ->delete();
        }

        $plansUpdated = $this->syncSubscriberPlansFromPppSecretRows($router, $rows);

        return [
            'count' => count($savedNames),
            'by_profile' => $byProfile,
            'deleted_orphans' => $deleted,
            'plans_updated' => $plansUpdated,
        ];
    }

    /**
     * إنشاء مشتركين في النظام لكل سر PPP على الراوتر لا يوجد له مطابقة محلية.
     * يُفضّل الاسم من حقل comment على المايكروتيك، وإلا اسم السر.
     * كلمة المرور: من الراوتر إن رجعتها الـ API، وإلا تُولَّد وتُحدَّث على الراوتر.
     *
     * @return array{created:int, skipped_local:int, skipped_global:int, errors: array<int, string>}
     */
    public function importSubscribersFromPppSecrets(Router $router): array
    {
        $rows = $this->fetchPppSecrets($router);
        $created = 0;
        $skippedLocal = 0;
        $skippedGlobal = 0;
        $errors = [];

        foreach ($rows as $row) {
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                continue;
            }

            if ($this->findSubscriberForRouterByPppName($router, $name) !== null) {
                $skippedLocal++;

                continue;
            }

            $dup = Subscriber::query()
                ->whereRaw('LOWER(TRIM(pppoe_username)) = ?', [mb_strtolower($name)])
                ->first();
            if ($dup !== null) {
                $skippedGlobal++;

                continue;
            }

            try {
                $mtComment = isset($row['comment']) ? trim((string) $row['comment']) : '';
                $fullName = $mtComment !== '' ? $mtComment : $name;
                $profile = isset($row['profile']) ? trim((string) $row['profile']) : '';
                $planId = $this->resolvePlanIdForImport($profile);

                $passwordPlain = $this->extractPasswordFromSecretRow($row);
                $passwordWasGenerated = $passwordPlain === null || $passwordPlain === '';
                if ($passwordWasGenerated) {
                    $passwordPlain = Str::password(16);
                }

                $start = now()->startOfDay();
                $end = $start->copy()->addDays(config('mikrotik.import_duration_days', 365));

                $subscriber = Subscriber::query()->create([
                    'full_name' => $fullName,
                    'phone' => 'import-'.Str::lower(Str::random(12)),
                    'address' => null,
                    'pppoe_username' => $name,
                    'pppoe_password' => $passwordPlain,
                    'plan_id' => $planId,
                    'router_id' => $router->id,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => 'active',
                    'is_online' => false,
                ]);

                if ($passwordWasGenerated) {
                    $this->setSecretPassword($router, $name, $passwordPlain);
                }

                $this->setSecretComment($router, $name, $subscriber->full_name);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = $name.': '.$e->getMessage();
                Log::warning('MikroTik import subscriber', ['name' => $name, 'error' => $e->getMessage()]);
            }
        }

        $this->syncPppSecretsToDatabase($router);

        return [
            'created' => $created,
            'skipped_local' => $skippedLocal,
            'skipped_global' => $skippedGlobal,
            'errors' => $errors,
        ];
    }

    /**
     * يضبط حقل comment على المايكروتيك ليطابق full_name لكل مشترك مرتبط بهذا الراوتر.
     *
     * @return array{updated:int, skipped_no_secret:int, failed:int}
     */
    public function syncSubscriberCommentsToMikrotik(Router $router): array
    {
        $updated = 0;
        $skippedNoSecret = 0;
        $failed = 0;

        $subs = Subscriber::query()
            ->where('router_id', $router->id)
            ->orderBy('id')
            ->get(['id', 'pppoe_username', 'full_name']);

        foreach ($subs as $subscriber) {
            try {
                if ($this->setSecretComment($router, $subscriber->pppoe_username, $subscriber->full_name)) {
                    $updated++;
                } else {
                    $skippedNoSecret++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('MikroTik set comment', [
                    'subscriber_id' => $subscriber->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['updated' => $updated, 'skipped_no_secret' => $skippedNoSecret, 'failed' => $failed];
    }

    /**
     * مزامنة جلسات PPPoE النشطة مع المشتركين وجدول router_sessions.
     *
     * @return array{online:int, matched:int, active_rows:int, unmatched_sample: array<int, string>, snapshots_stored:int}
     */
    /**
     * قراءة /ppp/active مرة أو مرتين (فاصل ثوانٍ) لحساب دلتا فورية داخل نفس المزامنة.
     *
     * @return array{final: array<int, array<string, mixed>>, before_dual: array<int, array<string, mixed>>|null}
     */
    protected function readPppActiveDoubleSample(Client $client): array
    {
        $before = $client->query(new Query('/ppp/active/print'))->read();
        if (! is_array($before)) {
            $before = [];
        }

        if (! filter_var(config('mikrotik.traffic_double_sample', true), FILTER_VALIDATE_BOOL)) {
            return ['final' => $before, 'before_dual' => null];
        }

        $gapSec = (float) config('mikrotik.traffic_sample_gap_seconds', 2);
        $us = (int) round(max(0.1, $gapSec) * 1_000_000);
        usleep($us);

        $after = $client->query(new Query('/ppp/active/print'))->read();
        if (! is_array($after)) {
            return ['final' => $before, 'before_dual' => null];
        }

        return ['final' => $after, 'before_dual' => $before];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function mapPppActiveRowsByNormalizedName(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                continue;
            }
            $map[mb_strtolower($name)] = $row;
        }

        return $map;
    }

    public function syncActiveSessions(Router $router): array
    {
        $client = $this->client($router);
        $sample = $this->readPppActiveDoubleSample($client);
        $active = $sample['final'];
        $activeBeforeDual = $sample['before_dual'];

        Subscriber::where('router_id', $router->id)->update(['is_online' => false]);

        $matched = 0;
        $unmatchedNames = [];
        foreach ($active as $row) {
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                continue;
            }

            $subscriber = $this->findSubscriberForRouterByPppName($router, $name);

            if (! $subscriber) {
                $unmatchedNames[] = $name;

                continue;
            }

            $matched++;
            $subscriber->update(['is_online' => true]);

            RouterSession::updateOrCreate(
                [
                    'subscriber_id' => $subscriber->id,
                    'router_id' => $router->id,
                ],
                [
                    'ip_address' => $row['address'] ?? null,
                    'uptime' => $row['uptime'] ?? null,
                    'rx_bytes' => $this->parseBytes($row, ['rx-byte', 'bytes-rx', 'rx-byte-total']),
                    'tx_bytes' => $this->parseBytes($row, ['tx-byte', 'bytes-tx', 'tx-byte-total']),
                    'is_active' => true,
                    'last_seen_at' => now(),
                ]
            );
        }

        $snapshotsStored = $this->recordTrafficSnapshotsFromActiveRows($router, $active, $activeBeforeDual);

        return [
            'online' => Subscriber::where('router_id', $router->id)->where('is_online', true)->count(),
            'matched' => $matched,
            'active_rows' => count($active),
            'unmatched_sample' => array_slice(array_values(array_unique($unmatchedNames)), 0, 12),
            'snapshots_stored' => $snapshotsStored,
        ];
    }

    /**
     * تخزين لقطة استهلاك لكل جلسة PPPoE نشطة (للتحليل حتى مع انقطاع الراوتر لاحقاً).
     *
     * @param  array<int, array<string, mixed>>  $activeFinal  صفوف القراءة الأخيرة (العدادات الحالية)
     * @param  array<int, array<string, mixed>>|null  $activeBeforeDual  القراءة السابقة ضمن نفس المزامنة (لحساب دلتا فورية)
     */
    public function recordTrafficSnapshotsFromActiveRows(Router $router, array $activeFinal, ?array $activeBeforeDual = null): int
    {
        $now = now();
        $stored = 0;
        $beforeMap = ($activeBeforeDual !== null && $activeBeforeDual !== [])
            ? $this->mapPppActiveRowsByNormalizedName($activeBeforeDual)
            : null;
        $gapSeconds = max(0.001, (float) config('mikrotik.traffic_sample_gap_seconds', 2));

        foreach ($activeFinal as $row) {
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                continue;
            }

            $rx = $this->parseBytes($row, ['rx-byte', 'bytes-rx', 'rx-byte-total']);
            $tx = $this->parseBytes($row, ['tx-byte', 'bytes-tx', 'tx-byte-total']);

            $instantRxBps = $this->parseOptionalRateBps($row, ['rx-bits-per-second', 'rx-rate', 'rate-rx']);
            $instantTxBps = $this->parseOptionalRateBps($row, ['tx-bits-per-second', 'tx-rate', 'rate-tx']);

            $subscriber = $this->findSubscriberForRouterByPppName($router, $name);

            $deltaRx = null;
            $deltaTx = null;
            $rxBps = $instantRxBps;
            $txBps = $instantTxBps;

            $key = mb_strtolower($name);
            $dualUsed = false;
            if ($beforeMap !== null && isset($beforeMap[$key])) {
                $row0 = $beforeMap[$key];
                $rx0 = $this->parseBytes($row0, ['rx-byte', 'bytes-rx', 'rx-byte-total']);
                $tx0 = $this->parseBytes($row0, ['tx-byte', 'bytes-tx', 'tx-byte-total']);
                $deltaRx = max(0, $rx - $rx0);
                $deltaTx = max(0, $tx - $tx0);
                $rxBps = (int) round(($deltaRx * 8) / $gapSeconds);
                $txBps = (int) round(($deltaTx * 8) / $gapSeconds);
                $dualUsed = true;
            }

            if (! $dualUsed) {
                $last = SubscriberTrafficSnapshot::query()
                    ->where('router_id', $router->id)
                    ->where('ppp_username', $name)
                    ->latest('recorded_at')
                    ->first();

                if ($last !== null) {
                    $deltaRx = $rx >= $last->rx_bytes_total ? $rx - $last->rx_bytes_total : $rx;
                    $deltaTx = $tx >= $last->tx_bytes_total ? $tx - $last->tx_bytes_total : $tx;
                    $deltaRx = max(0, $deltaRx);
                    $deltaTx = max(0, $deltaTx);
                    $seconds = max(1, $last->recorded_at->diffInSeconds($now));
                    if ($rxBps === null) {
                        $rxBps = (int) round(($deltaRx * 8) / $seconds);
                    }
                    if ($txBps === null) {
                        $txBps = (int) round(($deltaTx * 8) / $seconds);
                    }
                }
            }

            SubscriberTrafficSnapshot::query()->create([
                'router_id' => $router->id,
                'subscriber_id' => $subscriber?->id,
                'ppp_username' => $name,
                'rx_bytes_total' => $rx,
                'tx_bytes_total' => $tx,
                'delta_rx' => $deltaRx,
                'delta_tx' => $deltaTx,
                'rx_rate_bps' => $rxBps,
                'tx_rate_bps' => $txBps,
                'recorded_at' => $now,
            ]);
            $stored++;
        }

        return $stored;
    }

    /**
     * لقطة من الراوتر فقط (للجدولة دون إعادة مزامنة كاملة).
     */
    public function recordTrafficSnapshots(Router $router): int
    {
        $client = $this->client($router);
        $sample = $this->readPppActiveDoubleSample($client);

        return $this->recordTrafficSnapshotsFromActiveRows($router, $sample['final'], $sample['before_dual']);
    }

    /**
     * @param  array<int, string>  $keys
     */
    protected function parseOptionalRateBps(array $row, array $keys): ?int
    {
        foreach ($keys as $k) {
            if (! isset($row[$k])) {
                continue;
            }
            $v = $row[$k];
            if (is_numeric($v)) {
                $n = (int) $v;

                return $n >= 0 ? $n : null;
            }
        }

        return null;
    }

    /**
     * مزامنة الجلسات النشطة ثم جلب أسرار PPP وحفظها (لصفحة التحليل).
     *
     * @return array<string, mixed>
     */
    public function syncRouter(Router $router): array
    {
        $sessions = $this->syncActiveSessions($router);

        try {
            $sessions['secrets'] = $this->syncPppSecretsToDatabase($router);
        } catch (\Throwable $e) {
            Log::warning('MikroTik: فشل جلب أسرار PPP بعد مزامنة الجلسات', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);
            $sessions['secrets'] = null;
            $sessions['secrets_error'] = $e->getMessage();
        }

        return $sessions;
    }

    protected function parseRouterBool(mixed $value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }
        if ($value === false || $value === 0 || $value === null || $value === '') {
            return false;
        }

        $s = strtolower(trim((string) $value));

        if (in_array($s, ['false', 'no', 'off', '0'], true)) {
            return false;
        }

        return in_array($s, ['true', 'yes', 'on', '1'], true);
    }

    /** حقل disabled في /ppp/secret (نعم = الحساب معطّل على الراوتر) */
    protected function parseMikrotikDisabled(mixed $value): bool
    {
        return $this->parseRouterBool($value);
    }

    public function createPppoeUser(Subscriber $subscriber): void
    {
        $subscriber->loadMissing(['router', 'plan']);
        $router = $subscriber->router;
        if (! $router || ! $router->is_active) {
            Log::warning('MikroTik: لا يوجد راوتر نشط للمشترك', ['subscriber_id' => $subscriber->id]);

            return;
        }

        try {
            $this->ensurePppoeSecret($subscriber);
        } catch (\Throwable $e) {
            Log::error('MikroTik createPppoeUser', ['error' => $e->getMessage(), 'subscriber' => $subscriber->id]);
            throw $e;
        }
    }

    public function disablePppoeUser(Subscriber $subscriber): void
    {
        $subscriber->loadMissing('router');
        $router = $subscriber->router;
        if (! $router || ! $router->is_active) {
            return;
        }

        try {
            $this->setSecretDisabled($router, $subscriber->pppoe_username, true);
        } catch (\Throwable $e) {
            Log::error('MikroTik disablePppoeUser', ['error' => $e->getMessage(), 'subscriber' => $subscriber->id]);
            throw $e;
        }
    }

    public function enablePppoeUser(Subscriber $subscriber): void
    {
        $subscriber->loadMissing('router');
        $router = $subscriber->router;
        if (! $router || ! $router->is_active) {
            return;
        }

        try {
            $this->ensurePppoeSecret($subscriber);
        } catch (\Throwable $e) {
            Log::error('MikroTik enablePppoeUser', ['error' => $e->getMessage(), 'subscriber' => $subscriber->id]);
            throw $e;
        }
    }

    /**
     * حذف مستخدم PPPoE من المايكروتيك: فصل الجلسة النشطة (إن وُجدت) ثم إزالة السر.
     * يرمي استثناء عند فشل الاتصال بالراوتر؛ غياب السر لا يُعتبر فشلاً.
     */
    public function deletePppoeUser(Subscriber $subscriber): void
    {
        $subscriber->loadMissing('router');
        $router = $subscriber->router;
        if (! $router || ! $router->is_active) {
            return;
        }

        $username = trim((string) $subscriber->pppoe_username);
        if ($username === '') {
            return;
        }

        try {
            $client = $this->client($router);

            $active = $client->query(
                (new Query('/ppp/active/print'))->where('name', $username)
            )->read();

            foreach ($active as $row) {
                if (! isset($row['.id'])) {
                    continue;
                }
                try {
                    $client->query(
                        (new Query('/ppp/active/remove'))->equal('.id', $row['.id'])
                    )->read();
                } catch (\Throwable $e) {
                    Log::warning('MikroTik deletePppoeUser: تعذّر فصل الجلسة', [
                        'router' => $router->id,
                        'user' => $username,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $existing = $client->query(
                (new Query('/ppp/secret/print'))->where('name', $username)
            )->read();

            if (empty($existing) || ! isset($existing[0]['.id'])) {
                Log::info('MikroTik deletePppoeUser: لا يوجد سر لحذفه', [
                    'router' => $router->id,
                    'user' => $username,
                ]);

                return;
            }

            $client->query(
                (new Query('/ppp/secret/remove'))->equal('.id', $existing[0]['.id'])
            )->read();
        } catch (\Throwable $e) {
            Log::error('MikroTik deletePppoeUser', [
                'error' => $e->getMessage(),
                'subscriber' => $subscriber->id,
            ]);
            throw $e;
        }
    }

    protected function ensurePppoeSecret(Subscriber $subscriber): void
    {
        $router = $subscriber->router;
        $client = $this->client($router);
        $profile = $subscriber->plan?->mikrotik_profile
            ?: config('mikrotik.default_ppp_profile', 'default');
        $comment = $this->normalizeCommentForMikrotik($subscriber->full_name);

        $existing = $client->query(
            (new Query('/ppp/secret/print'))->where('name', $subscriber->pppoe_username)
        )->read();

        if (! empty($existing) && isset($existing[0]['.id'])) {
            $id = $existing[0]['.id'];
            $client->query(
                (new Query('/ppp/secret/set'))
                    ->equal('.id', $id)
                    ->equal('password', $subscriber->pppoe_password)
                    ->equal('profile', $profile)
                    ->equal('service', 'pppoe')
                    ->equal('disabled', 'no')
                    ->equal('comment', $comment)
            )->read();

            return;
        }

        $client->query(
            (new Query('/ppp/secret/add'))
                ->equal('name', $subscriber->pppoe_username)
                ->equal('password', $subscriber->pppoe_password)
                ->equal('service', 'pppoe')
                ->equal('profile', $profile)
                ->equal('comment', $comment)
        )->read();
    }

    public function setSecretComment(Router $router, string $pppName, string $commentText): bool
    {
        $client = $this->client($router);
        $existing = $client->query(
            (new Query('/ppp/secret/print'))->where('name', $pppName)
        )->read();

        if (empty($existing) || ! isset($existing[0]['.id'])) {
            return false;
        }

        $id = $existing[0]['.id'];
        $comment = $this->normalizeCommentForMikrotik($commentText);
        $client->query(
            (new Query('/ppp/secret/set'))
                ->equal('.id', $id)
                ->equal('comment', $comment)
        )->read();

        return true;
    }

    public function setSecretPassword(Router $router, string $pppName, string $passwordPlain): bool
    {
        $client = $this->client($router);
        $existing = $client->query(
            (new Query('/ppp/secret/print'))->where('name', $pppName)
        )->read();

        if (empty($existing) || ! isset($existing[0]['.id'])) {
            return false;
        }

        $id = $existing[0]['.id'];
        $client->query(
            (new Query('/ppp/secret/set'))
                ->equal('.id', $id)
                ->equal('password', $passwordPlain)
        )->read();

        return true;
    }

    protected function normalizeCommentForMikrotik(string $text): string
    {
        $t = trim($text);
        if (mb_strlen($t) > 255) {
            $t = mb_substr($t, 0, 252).'...';
        }

        return $t;
    }

    protected function defaultImportPlanId(): int
    {
        $id = config('mikrotik.import_plan_id');
        if ($id) {
            return (int) $id;
        }

        $first = Plan::query()->where('is_active', true)->orderBy('id')->value('id');
        if (! $first) {
            throw new \RuntimeException('لا توجد باقة نشطة. أنشئ باقة أو عرّف MIKROTIK_IMPORT_PLAN_ID في .env');
        }

        return (int) $first;
    }

    protected function resolvePlanIdForImport(string $profile): int
    {
        $trimmed = trim($profile);
        if ($trimmed !== '') {
            $byProfile = Plan::query()
                ->where('is_active', true)
                ->whereNotNull('mikrotik_profile')
                ->whereRaw('LOWER(TRIM(mikrotik_profile)) = ?', [mb_strtolower($trimmed)])
                ->orderBy('id')
                ->value('id');
            if ($byProfile) {
                return (int) $byProfile;
            }

            $unmatched = config('mikrotik.import_unmatched_profile_plan_id');
            if ($unmatched) {
                return (int) $unmatched;
            }

            Log::warning('MikroTik: لا توجد باقة باسم profile مطابق', [
                'profile' => $trimmed,
            ]);
        }

        return $this->defaultImportPlanId();
    }

    protected function extractPasswordFromSecretRow(array $row): ?string
    {
        if (! isset($row['password'])) {
            return null;
        }

        $p = trim((string) $row['password']);
        if ($p === '' || str_starts_with($p, '***')) {
            return null;
        }

        return $p;
    }

    protected function setSecretDisabled(Router $router, string $username, bool $disabled): void
    {
        $client = $this->client($router);
        $existing = $client->query(
            (new Query('/ppp/secret/print'))->where('name', $username)
        )->read();

        if (empty($existing) || ! isset($existing[0]['.id'])) {
            Log::warning('MikroTik: لم يُعثر على سر PPPoE', ['router' => $router->id, 'user' => $username]);

            return;
        }

        $id = $existing[0]['.id'];
        $client->query(
            (new Query('/ppp/secret/set'))
                ->equal('.id', $id)
                ->equal('disabled', $disabled ? 'yes' : 'no')
        )->read();
    }

    /**
     * @param  array<int, string>  $keys
     */
    protected function parseBytes(array $row, array $keys): int
    {
        foreach ($keys as $k) {
            if (isset($row[$k]) && is_numeric($row[$k])) {
                return (int) $row[$k];
            }
        }

        return 0;
    }
}
