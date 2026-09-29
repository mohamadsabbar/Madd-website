<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\SubscriberTrafficSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminAnalyticsController extends Controller
{
    public function index(): View
    {
        $routerId = request()->filled('router_id') ? (int) request('router_id') : null;
        $hours = min(168, max(1, (int) request('hours', 24)));
        $since = now()->subHours($hours);

        $topVolume = DB::table('subscriber_traffic_snapshots as s')
            ->join('subscribers', 'subscribers.id', '=', 's.subscriber_id')
            ->when($routerId, fn ($q) => $q->where('s.router_id', $routerId))
            ->where('s.recorded_at', '>=', $since)
            ->whereNotNull('s.subscriber_id')
            ->selectRaw('subscribers.id, subscribers.full_name, SUM(COALESCE(s.delta_rx,0) + COALESCE(s.delta_tx,0)) as total_bytes')
            ->groupBy('subscribers.id', 'subscribers.full_name')
            ->havingRaw('SUM(COALESCE(s.delta_rx,0) + COALESCE(s.delta_tx,0)) > 0')
            ->orderByDesc('total_bytes')
            ->limit(15)
            ->get();

        $topDownload = DB::table('subscriber_traffic_snapshots as s')
            ->join('subscribers', 'subscribers.id', '=', 's.subscriber_id')
            ->when($routerId, fn ($q) => $q->where('s.router_id', $routerId))
            ->where('s.recorded_at', '>=', $since)
            ->whereNotNull('s.subscriber_id')
            ->selectRaw('subscribers.id, subscribers.full_name, SUM(COALESCE(s.delta_rx,0)) as total_rx')
            ->groupBy('subscribers.id', 'subscribers.full_name')
            ->havingRaw('SUM(COALESCE(s.delta_rx,0)) > 0')
            ->orderByDesc('total_rx')
            ->limit(15)
            ->get();

        $topUpload = DB::table('subscriber_traffic_snapshots as s')
            ->join('subscribers', 'subscribers.id', '=', 's.subscriber_id')
            ->when($routerId, fn ($q) => $q->where('s.router_id', $routerId))
            ->where('s.recorded_at', '>=', $since)
            ->whereNotNull('s.subscriber_id')
            ->selectRaw('subscribers.id, subscribers.full_name, SUM(COALESCE(s.delta_tx,0)) as total_tx')
            ->groupBy('subscribers.id', 'subscribers.full_name')
            ->havingRaw('SUM(COALESCE(s.delta_tx,0)) > 0')
            ->orderByDesc('total_tx')
            ->limit(15)
            ->get();

        $topPeakRx = DB::table('subscriber_traffic_snapshots as s')
            ->join('subscribers', 'subscribers.id', '=', 's.subscriber_id')
            ->when($routerId, fn ($q) => $q->where('s.router_id', $routerId))
            ->where('s.recorded_at', '>=', $since)
            ->whereNotNull('s.subscriber_id')
            ->whereNotNull('s.rx_rate_bps')
            ->selectRaw('subscribers.id, subscribers.full_name, MAX(s.rx_rate_bps) as peak_rx_bps')
            ->groupBy('subscribers.id', 'subscribers.full_name')
            ->orderByDesc('peak_rx_bps')
            ->limit(15)
            ->get();

        $topPeakTx = DB::table('subscriber_traffic_snapshots as s')
            ->join('subscribers', 'subscribers.id', '=', 's.subscriber_id')
            ->when($routerId, fn ($q) => $q->where('s.router_id', $routerId))
            ->where('s.recorded_at', '>=', $since)
            ->whereNotNull('s.subscriber_id')
            ->whereNotNull('s.tx_rate_bps')
            ->selectRaw('subscribers.id, subscribers.full_name, MAX(s.tx_rate_bps) as peak_tx_bps')
            ->groupBy('subscribers.id', 'subscribers.full_name')
            ->orderByDesc('peak_tx_bps')
            ->limit(15)
            ->get();

        $unmapped = DB::table('subscriber_traffic_snapshots as s')
            ->whereNull('s.subscriber_id')
            ->when($routerId, fn ($q) => $q->where('s.router_id', $routerId))
            ->where('s.recorded_at', '>=', $since)
            ->selectRaw('s.ppp_username, SUM(COALESCE(s.delta_rx,0) + COALESCE(s.delta_tx,0)) as total_bytes')
            ->groupBy('s.ppp_username')
            ->havingRaw('SUM(COALESCE(s.delta_rx,0) + COALESCE(s.delta_tx,0)) > 0')
            ->orderByDesc('total_bytes')
            ->limit(10)
            ->get();

        $hourlyChart = $this->hourlyTotals($since, $routerId);

        $snapshotsCount = SubscriberTrafficSnapshot::query()
            ->when($routerId, fn ($q) => $q->where('router_id', $routerId))
            ->where('recorded_at', '>=', $since)
            ->count();

        $snapshotsTotalInDb = SubscriberTrafficSnapshot::query()->count();

        $hasMeaningfulDelta = SubscriberTrafficSnapshot::query()
            ->when($routerId, fn ($q) => $q->where('router_id', $routerId))
            ->where('recorded_at', '>=', $since)
            ->where(function ($q) {
                $q->where('delta_rx', '>', 0)->orWhere('delta_tx', '>', 0);
            })
            ->exists();

        $oldestInRange = SubscriberTrafficSnapshot::query()
            ->when($routerId, fn ($q) => $q->where('router_id', $routerId))
            ->where('recorded_at', '>=', $since)
            ->min('recorded_at');

        $routers = Router::query()->orderBy('name')->get();

        return view('admin.analytics', [
            'routerId' => $routerId,
            'hours' => $hours,
            'since' => $since,
            'topVolume' => $topVolume,
            'topDownload' => $topDownload,
            'topUpload' => $topUpload,
            'topPeakRx' => $topPeakRx,
            'topPeakTx' => $topPeakTx,
            'unmapped' => $unmapped,
            'hourlyChart' => $hourlyChart,
            'snapshotsCount' => $snapshotsCount,
            'snapshotsTotalInDb' => $snapshotsTotalInDb,
            'hasMeaningfulDelta' => $hasMeaningfulDelta,
            'oldestInRange' => $oldestInRange,
            'routers' => $routers,
        ]);
    }

    /**
     * @return array<int, array{label: string, bytes: int}>
     */
    protected function hourlyTotals(\DateTimeInterface $since, ?int $routerId): array
    {
        $rows = SubscriberTrafficSnapshot::query()
            ->where('recorded_at', '>=', $since)
            ->when($routerId, fn ($q) => $q->where('router_id', $routerId))
            ->orderBy('recorded_at')
            ->get(['recorded_at', 'delta_rx', 'delta_tx']);

        $buckets = [];
        foreach ($rows as $r) {
            $k = $r->recorded_at->format('Y-m-d H').':00';
            $buckets[$k] = ($buckets[$k] ?? 0) + (int) ($r->delta_rx ?? 0) + (int) ($r->delta_tx ?? 0);
        }
        ksort($buckets);

        $out = [];
        foreach ($buckets as $label => $bytes) {
            $out[] = ['label' => $label, 'bytes' => $bytes];
        }

        return $out;
    }
}
