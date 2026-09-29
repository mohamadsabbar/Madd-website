<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MikrotikPppSecret;
use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRouterController extends Controller
{
    public function __construct(private readonly MikrotikService $mikrotikService)
    {
    }

    public function index(): View
    {
        $routers = Router::query()->orderBy('name')->get();

        return view('admin.routers', compact('routers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Router::create($data);

        return redirect()->route('web.admin.routers')->with('status', 'تمت إضافة الراوتر.');
    }

    public function update(Request $request, Router $router): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'host' => ['sometimes', 'string', 'max:255'],
            'api_port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'username' => ['sometimes', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! $request->filled('password')) {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $router->update($data);

        return redirect()->route('web.admin.routers')->with('status', 'تم تحديث الراوتر.');
    }

    public function destroy(Router $router): RedirectResponse
    {
        $router->delete();

        return redirect()->route('web.admin.routers')->with('status', 'تم حذف الراوتر.');
    }

    public function test(Router $router): RedirectResponse
    {
        try {
            $info = $this->mikrotikService->fetchRouterInfo($router);
            $identityName = $info['identity']['name'] ?? '—';
            $version = $info['resource']['version'] ?? '—';

            return redirect()->route('web.admin.routers')->with(
                'status',
                'اتصال ناجح: '.$identityName.' — RouterOS '.$version.' — PPPoE نشط: '.$info['ppp_active_count']
            );
        } catch (\Throwable $e) {
            return redirect()->route('web.admin.routers')->with(
                'error',
                'فشل الاتصال: '.$e->getMessage()
            );
        }
    }

    public function sync(Router $router): RedirectResponse
    {
        try {
            $stats = $this->mikrotikService->syncRouter($router);

            $msg = 'تمت المزامنة: صفوف نشطة '.$stats['active_rows'].'، مطابقة '.$stats['matched'].'، متصلون الآن '.$stats['online'];
            if (isset($stats['snapshots_stored'])) {
                $msg .= '، لقطات استهلاك مخزّنة '.$stats['snapshots_stored'];
            }
            if (isset($stats['secrets']) && is_array($stats['secrets'])) {
                $msg .= ' — أسرار PPP محفوظة للتحليل: '.$stats['secrets']['count'].' سجل';
                if (($stats['secrets']['plans_updated'] ?? 0) > 0) {
                    $msg .= '، باقات محدّثة حسب profile: '.$stats['secrets']['plans_updated'].' مشترك';
                }
            } elseif (! empty($stats['secrets_error'])) {
                $msg .= ' — تعذّر جلب أسرار PPP (صفحة التحليل قد تبقى فارغة): '.$stats['secrets_error'];
            }
            if ($stats['matched'] === 0 && $stats['active_rows'] > 0 && count($stats['unmatched_sample']) > 0) {
                $msg .= ' — عيّنة أسماء من الراوتر بدون مطابقة في النظام: '.implode('، ', $stats['unmatched_sample']).' (تأكد من ربط المشتركين بهذا الراوتر وتطابق pppoe_username).';
            }

            return redirect()->route('web.admin.routers')->with('status', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('web.admin.routers')->with(
                'error',
                'فشل المزامنة: '.$e->getMessage()
            );
        }
    }

    public function ppp(Router $router): View
    {
        $secrets = MikrotikPppSecret::query()
            ->where('router_id', $router->id)
            ->with([
                'subscriber:id,full_name,pppoe_username,is_online,plan_id',
                'subscriber.plan:id,name,speed_mbps',
            ])
            ->orderBy('ppp_name')
            ->paginate(50);

        $byProfile = MikrotikPppSecret::query()
            ->where('router_id', $router->id)
            ->selectRaw('profile, COUNT(*) as c')
            ->groupBy('profile')
            ->orderByDesc('c')
            ->get();

        $stats = [
            'total' => MikrotikPppSecret::query()->where('router_id', $router->id)->count(),
            'linked' => MikrotikPppSecret::query()->where('router_id', $router->id)->whereNotNull('subscriber_id')->count(),
            'disabled' => MikrotikPppSecret::query()->where('router_id', $router->id)->where('disabled', true)->count(),
            'last_sync' => MikrotikPppSecret::query()->where('router_id', $router->id)->max('synced_at'),
        ];

        $chartLabels = $byProfile->map(fn ($r) => $r->profile ?? '—')->values()->all();
        $chartData = $byProfile->pluck('c')->map(fn ($n) => (int) $n)->values()->all();

        return view('admin.mikrotik-ppp', compact('router', 'secrets', 'byProfile', 'stats', 'chartLabels', 'chartData'));
    }

    public function syncSecrets(Router $router): RedirectResponse
    {
        try {
            $result = $this->mikrotikService->syncPppSecretsToDatabase($router);

            $msg = 'تم جلب أسرار PPPoE: '.$result['count'].' سجل، محذوف قديم غير موجود على الراوتر: '.$result['deleted_orphans'].'.';
            if (($result['plans_updated'] ?? 0) > 0) {
                $msg .= ' تم تحديث الباقة لـ '.$result['plans_updated'].' مشتركاً حسب profile الراوتر.';
            }

            return redirect()->route('web.admin.routers.ppp', $router)->with('status', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('web.admin.routers.ppp', $router)->with(
                'error',
                'فشل الجلب: '.$e->getMessage()
            );
        }
    }

    public function importSubscribersFromPpp(Router $router): RedirectResponse
    {
        try {
            $import = $this->mikrotikService->importSubscribersFromPppSecrets($router);
            $msg = 'استيراد: أُنشئ '.$import['created'].' مشتركاً، تخطّي محلي موجود '.$import['skipped_local'].'، تخطّي (اسم مستخدم مأخوذ في النظام) '.$import['skipped_global'].'.';
            if (count($import['errors']) > 0) {
                $msg .= ' أخطاء: '.implode(' | ', array_slice($import['errors'], 0, 5));
            }

            return redirect()->route('web.admin.routers.ppp', $router)->with('status', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('web.admin.routers.ppp', $router)->with(
                'error',
                'فشل الاستيراد: '.$e->getMessage()
            );
        }
    }

    public function pushSubscriberCommentsToMikrotik(Router $router): RedirectResponse
    {
        try {
            $r = $this->mikrotikService->syncSubscriberCommentsToMikrotik($router);

            $msg = 'تمت مزامنة أسماء المشتركين إلى حقل comment على المايكروتيك: نجح '.$r['updated'].'، بدون سر مطابق '.$r['skipped_no_secret'].'، فشل '.$r['failed'].'.';

            return redirect()->route('web.admin.routers.ppp', $router)->with('status', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('web.admin.routers.ppp', $router)->with(
                'error',
                'فشل المزامنة: '.$e->getMessage()
            );
        }
    }

    public function pppProfiles(Router $router): JsonResponse
    {
        try {
            $profiles = $this->mikrotikService->fetchPppProfiles($router);

            return response()->json(['profiles' => $profiles]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function createPlansFromProfiles(Router $router): RedirectResponse
    {
        try {
            $r = $this->mikrotikService->createPlansFromMikrotikProfiles($router);

            $msg = 'باقات من المايكروتيك: أُنشئ '.$r['created'].' جديداً، رُبِط '.$r['linked'].' باقة موجودة بملف PPP، تخطّي '.$r['skipped'].' (ملف مسجّل مسبقاً). يمكنك تعديل السعر والسرعة من الجدول أدناه.';

            return redirect()->route('web.admin.plans')->with('status', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('web.admin.plans')->with(
                'error',
                'فشل الإنشاء التلقائي: '.$e->getMessage()
            );
        }
    }

    public function syncSubscriberPlansFromMikrotik(Request $request, Router $router): RedirectResponse
    {
        try {
            $r = $this->mikrotikService->syncSubscribersPlansWithMikrotik($router);

            $msg = 'تمت مزامنة باقات المشتركين مع المايكروتيك: حُدّثت باقة '.$r['plans_updated'].' مشتركاً (حسب عمود profile في أسرار PPP على الراوتر، '.$r['count'].' سراً).';

            if ($request->boolean('return_ppp')) {
                return redirect()->route('web.admin.routers.ppp', $router)->with('status', $msg);
            }

            return redirect()->route('web.admin.subscribers')->with('status', $msg);
        } catch (\Throwable $e) {
            if ($request->boolean('return_ppp')) {
                return redirect()->route('web.admin.routers.ppp', $router)->with(
                    'error',
                    'فشل مزامنة الباقات: '.$e->getMessage()
                );
            }

            return redirect()->route('web.admin.routers')->with(
                'error',
                'فشل مزامنة الباقات: '.$e->getMessage()
            );
        }
    }
}
