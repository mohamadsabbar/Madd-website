<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\WhatsappExtendToken;
use App\Services\CustomerPortalService;
use App\Services\SubscriberPortalUserService;
use App\Services\WhatsAppExtendLinkService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WhatsAppExtendController extends Controller
{
    public function __construct(
        private readonly WhatsAppExtendLinkService $links,
        private readonly CustomerPortalService $portal,
        private readonly SubscriberPortalUserService $portalUsers,
    ) {}

    /**
     * Static Meta button landing: https://my.madd.ps/wa/extend
     */
    public function form(): View
    {
        return view('customer.wa-extend', [
            'ok' => null,
            'title' => 'مدد خطك 3 أيام',
            'lead' => 'أدخل رقم الجوال المسجّل لدينا لتفعيل فترة السماح المجانية إن كانت متاحة.',
            'show_form' => true,
        ]);
    }

    public function submit(Request $request): View|Response
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $subscriber = $this->links->findSubscriberByPhone($data['phone']);
        if (! $subscriber) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'لم يُعثر على الاشتراك',
                'lead' => 'تأكد من رقم الجوال كما هو مسجّل في مدد، أو تواصل مع الشركة.',
                'show_form' => true,
                'old_phone' => $data['phone'],
            ], 404);
        }

        return $this->performExtend($subscriber, null);
    }

    public function show(string $token): View|Response
    {
        $row = $this->links->findValid($token);

        if (! $row) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'الرابط غير صالح',
                'lead' => 'رابط التمديد منتهٍ أو غير صحيح. يمكنك التمديد برقم الجوال أدناه.',
                'show_form' => true,
            ], 410);
        }

        if ($row->isUsed()) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'تم استخدام الرابط',
                'lead' => 'هذا الرابط استُخدم من قبل. إن احتجت تمديداً آخر راجع تطبيق مدد أو تواصل مع الشركة.',
                'show_form' => true,
            ], 409);
        }

        $subscriber = $row->subscriber;
        if (! $subscriber) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'المشترك غير موجود',
                'lead' => 'تعذّر العثور على الاشتراك المرتبط بهذا الرابط.',
                'show_form' => true,
            ], 404);
        }

        return $this->performExtend($subscriber, $row);
    }

    private function performExtend(Subscriber $subscriber, ?WhatsappExtendToken $tokenRow = null): View|Response
    {
        $state = $this->portal->selfExtendState($subscriber);

        if (! ($state['can_self_extend'] ?? false)) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'التمديد غير متاح الآن',
                'lead' => (string) ($state['message'] ?? 'لا يمكن تفعيل فترة السماح حالياً.'),
                'subscriber' => $subscriber,
                'show_form' => true,
            ]);
        }

        try {
            $user = $this->portalUsers->ensurePortalUser($subscriber);
            if (! $user) {
                return response()->view('customer.wa-extend', [
                    'ok' => false,
                    'title' => 'تعذّر التمديد',
                    'lead' => 'رقم الهاتف غير صالح لإنشاء حساب البوابة. تواصل مع الشركة.',
                    'show_form' => true,
                ], 422);
            }

            $result = $this->portal->performSelfExtend($user, $subscriber->fresh());
            if ($tokenRow) {
                $this->links->markUsed($tokenRow);
            }

            return response()->view('customer.wa-extend', [
                'ok' => true,
                'title' => 'تم تمديد الخط',
                'lead' => "تم تفعيل فترة سماح مجانية لمدة {$result['days']} أيام حتى {$result['new_end_date']}.",
                'days' => $result['days'],
                'new_end_date' => $result['new_end_date'],
                'mikrotik' => $result['mikrotik'] ?? null,
                'subscriber' => $result['subscriber'],
                'show_form' => false,
            ]);
        } catch (\Throwable $e) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'تعذّر التمديد',
                'lead' => $e->getMessage() ?: 'حدث خطأ غير متوقع. حاول لاحقاً أو تواصل مع الشركة.',
                'subscriber' => $subscriber,
                'show_form' => true,
            ], 422);
        }
    }
}
