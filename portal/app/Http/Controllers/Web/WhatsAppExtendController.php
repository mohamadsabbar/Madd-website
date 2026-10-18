<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\CustomerPortalService;
use App\Services\SubscriberPortalUserService;
use App\Services\WhatsAppExtendLinkService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WhatsAppExtendController extends Controller
{
    public function __construct(
        private readonly WhatsAppExtendLinkService $links,
        private readonly CustomerPortalService $portal,
        private readonly SubscriberPortalUserService $portalUsers,
    ) {}

    public function show(string $token): View|Response
    {
        $row = $this->links->findValid($token);

        if (! $row) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'الرابط غير صالح',
                'message' => 'رابط التمديد منتهٍ أو غير صحيح. اطلب رسالة جديدة أو افتح تطبيق مدد.',
            ], 410);
        }

        if ($row->isUsed()) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'تم استخدام الرابط',
                'message' => 'هذا الرابط استُخدم من قبل. إن احتجت تمديداً آخر راجع تطبيق مدد أو تواصل مع الشركة.',
            ], 409);
        }

        $subscriber = $row->subscriber;
        if (! $subscriber) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'المشترك غير موجود',
                'message' => 'تعذّر العثور على الاشتراك المرتبط بهذا الرابط.',
            ], 404);
        }

        $state = $this->portal->selfExtendState($subscriber);

        if (! ($state['can_self_extend'] ?? false)) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'التمديد غير متاح الآن',
                'message' => (string) ($state['message'] ?? 'لا يمكن تفعيل فترة السماح حالياً.'),
                'subscriber' => $subscriber,
            ]);
        }

        try {
            $user = $this->portalUsers->ensurePortalUser($subscriber);
            if (! $user) {
                return response()->view('customer.wa-extend', [
                    'ok' => false,
                    'title' => 'تعذّر التمديد',
                    'message' => 'رقم الهاتف غير صالح لإنشاء حساب البوابة. تواصل مع الشركة.',
                ], 422);
            }

            $result = $this->portal->performSelfExtend($user, $subscriber->fresh());
            $this->links->markUsed($row);

            return response()->view('customer.wa-extend', [
                'ok' => true,
                'title' => 'تم تمديد الخط',
                'message' => "تم تفعيل فترة سماح مجانية لمدة {$result['days']} أيام حتى {$result['new_end_date']}.",
                'days' => $result['days'],
                'new_end_date' => $result['new_end_date'],
                'mikrotik' => $result['mikrotik'] ?? null,
                'subscriber' => $result['subscriber'],
            ]);
        } catch (\Throwable $e) {
            return response()->view('customer.wa-extend', [
                'ok' => false,
                'title' => 'تعذّر التمديد',
                'message' => $e->getMessage() ?: 'حدث خطأ غير متوقع. حاول لاحقاً أو تواصل مع الشركة.',
                'subscriber' => $subscriber,
            ], 422);
        }
    }
}
