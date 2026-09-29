<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\CustomerPortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerPortalController extends Controller
{
    public function __construct(private CustomerPortalService $portal)
    {
    }

    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'subscriber') {
            return redirect()->route('web.customer.dashboard');
        }
        if (Auth::check()) {
            return Auth::user()->role === 'admin'
                ? redirect()->route('web.admin.dashboard')
                : redirect()->route('web.agent.dashboard');
        }

        return view('customer.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);
        $user = $this->findSubscriberUser($login);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'رقم الهاتف أو اسم المستخدم أو كلمة المرور غير صحيحة.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => 'الحساب معطّل. تواصل مع الدعم.',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('web.customer.dashboard'));
    }

    public function dashboard()
    {
        $data = $this->portal->dashboard(Auth::user());

        if (! ($data['ok'] ?? false)) {
            return view('customer.no-subscription', [
                'message' => $data['message'] ?? 'لا يوجد اشتراك.',
            ]);
        }

        return view('customer.dashboard', $data);
    }

    public function invoices()
    {
        $subscriber = $this->requireSubscriber();

        return view('customer.invoices', [
            'account' => $this->portal->accountPayload($subscriber),
            'invoices' => $this->portal->allInvoices($subscriber),
        ]);
    }

    public function usage()
    {
        $subscriber = $this->requireSubscriber();

        return view('customer.usage', [
            'account' => $this->portal->accountPayload($subscriber),
            'usage' => $this->portal->usageByMonth($subscriber, 6),
        ]);
    }

    public function plans()
    {
        $subscriber = $this->requireSubscriber();

        return view('customer.plans', [
            'account' => $this->portal->accountPayload($subscriber),
            'service' => $this->portal->currentServicePayload($subscriber),
            'plans' => $this->portal->availablePlans($subscriber),
            'renewal' => $this->portal->renewalPayload($subscriber),
        ]);
    }

    public function requestRenewal(Request $request)
    {
        $user = Auth::user();
        $subscriber = $this->requireSubscriber();

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'action' => ['nullable', 'in:self_extend,company_request'],
        ]);

        $action = $data['action'] ?? null;
        $state = $this->portal->selfExtendState($subscriber);

        if ($action === 'self_extend' || (($state['can_self_extend'] ?? false) && empty($data['plan_id']) && $action !== 'company_request')) {
            try {
                $result = $this->portal->performSelfExtend($user, $subscriber);
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }

            return back()->with(
                'status',
                "تم تمديد اشتراكك تلقائياً لمدة {$result['days']} أيام حتى {$result['new_end_date']}."
                .($result['mikrotik'] ? ' '.$result['mikrotik'] : '')
            );
        }

        try {
            $this->portal->createRenewalRequest(
                $user,
                $subscriber,
                $data['note'] ?? null,
                isset($data['plan_id']) ? (int) $data['plan_id'] : null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'تم إرسال الطلب بنجاح. سنتواصل معك قريباً.');
    }

    private function requireSubscriber(): Subscriber
    {
        $subscriber = $this->portal->subscriberFor(Auth::user());
        if (! $subscriber) {
            abort(403, 'لا يوجد اشتراك مرتبط.');
        }
        $subscriber->loadMissing(['plan', 'router']);

        return $subscriber;
    }

    private function findSubscriberUser(string $login): ?User
    {
        // هاتف
        $byPhone = User::query()
            ->where('role', 'subscriber')
            ->where('phone', $login)
            ->first();
        if ($byPhone) {
            return $byPhone;
        }

        // بريد
        if (str_contains($login, '@')) {
            return User::query()
                ->where('role', 'subscriber')
                ->where('email', $login)
                ->first();
        }

        // اسم مستخدم PPP عبر جدول المشتركين
        $sub = Subscriber::query()
            ->where('pppoe_username', $login)
            ->whereNotNull('user_id')
            ->first();

        return $sub?->user;
    }
}
