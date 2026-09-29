<?php

use App\Http\Controllers\Web\AdminActivityController;
use App\Http\Controllers\Web\AdminAnalyticsController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminRouterController;
use App\Http\Controllers\Web\AdminSmsController;
use App\Http\Controllers\Web\AdminSubscriberController;
use App\Http\Controllers\Web\AgentDashboardController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\CustomerPortalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
 * إن كان document root = مجلد public فقط، قد يصل طلب GET إلى المسار /public/ بالخطأ — نعيد التوجيه إلى الرئيسية.
 */
Route::redirect('/public', '/', 301);
Route::redirect('/public/', '/', 301);

/** الرئيسية: صفحة الشركة للزائر؛ للمسجّل يُوجَّه إلى لوحته. */
Route::get('/', function () {
    if (Auth::check()) {
        return match (Auth::user()->role) {
            'admin' => redirect()->route('web.admin.dashboard'),
            'subscriber' => redirect()->route('web.customer.dashboard'),
            default => redirect()->route('web.agent.dashboard'),
        };
    }

    return view('public.company-home');
})->name('home');

/** توافق مع الروابط القديمة التي كانت تستخدم `/company` */
Route::permanentRedirect('/company', '/');

/** بوابة المشترك — ويب (وأساس تطبيق الجوال عبر /api/customer/*) */
Route::middleware('guest')->group(function () {
    Route::get('/my/login', [CustomerPortalController::class, 'showLogin'])->name('web.customer.login');
    Route::post('/my/login', [CustomerPortalController::class, 'login'])->name('web.customer.login.submit');
});

Route::middleware(['auth', 'role:subscriber'])->prefix('my')->group(function () {
    Route::get('/', [CustomerPortalController::class, 'dashboard'])->name('web.customer.dashboard');
    Route::get('/invoices', [CustomerPortalController::class, 'invoices'])->name('web.customer.invoices');
    Route::get('/usage', [CustomerPortalController::class, 'usage'])->name('web.customer.usage');
    Route::get('/plans', [CustomerPortalController::class, 'plans'])->name('web.customer.plans');
    Route::post('/renewal', [CustomerPortalController::class, 'requestRenewal'])->name('web.customer.renewal');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login'])->name('web.login.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/logout', [AuthWebController::class, 'showLogoutConfirm'])->name('web.logout.confirm');
    Route::post('/logout', [AuthWebController::class, 'logout'])->name('web.logout');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('web.admin.dashboard');
        Route::get('/admin/subscribers', [AdminDashboardController::class, 'subscribers'])->name('web.admin.subscribers');
        Route::get('/admin/subscribers/create', [AdminSubscriberController::class, 'create'])->name('web.admin.subscribers.create');
        Route::post('/admin/subscribers', [AdminSubscriberController::class, 'store'])->name('web.admin.subscribers.store');
        Route::get('/admin/subscribers/{subscriber}/edit', [AdminSubscriberController::class, 'edit'])->name('web.admin.subscribers.edit');
        Route::put('/admin/subscribers/{subscriber}', [AdminSubscriberController::class, 'update'])->name('web.admin.subscribers.update');
        Route::get('/admin/subscribers/{subscriber}', [AdminSubscriberController::class, 'show'])->name('web.admin.subscribers.show');
        Route::post('/admin/subscribers/{subscriber}/extend', [AdminSubscriberController::class, 'extendSubscription'])->name('web.admin.subscribers.extend');
        Route::post('/admin/subscribers/{subscriber}/debt', [AdminSubscriberController::class, 'storeManualDebt'])->name('web.admin.subscribers.debt.store');
        Route::delete('/admin/subscribers/{subscriber}', [AdminSubscriberController::class, 'destroy'])->name('web.admin.subscribers.destroy');
        Route::get('/admin/analytics', [AdminAnalyticsController::class, 'index'])->name('web.admin.analytics');
        Route::get('/admin/activity', [AdminActivityController::class, 'index'])->name('web.admin.activity');
        Route::get('/admin/sms', [AdminSmsController::class, 'index'])->name('web.admin.sms');
        Route::post('/admin/sms/send', [AdminSmsController::class, 'send'])->name('web.admin.sms.send');
        Route::get('/admin/plans', [AdminDashboardController::class, 'plans'])->name('web.admin.plans');
        Route::put('/admin/plans/{plan}', [AdminDashboardController::class, 'updatePlan'])->name('web.admin.plans.update');
        Route::delete('/admin/plans/{plan}', [AdminDashboardController::class, 'destroyPlan'])->name('web.admin.plans.destroy');
        Route::get('/admin/agents', [AdminDashboardController::class, 'agents'])->name('web.admin.agents');
        Route::get('/admin/agents/{agent}/settle', [AdminDashboardController::class, 'settleAgent'])->name('web.admin.agents.settle');
        Route::post('/admin/agents/{agent}/settle', [AdminDashboardController::class, 'storeSettlement'])->name('web.admin.agents.settle.store');

        Route::get('/admin/routers', [AdminRouterController::class, 'index'])->name('web.admin.routers');
        Route::post('/admin/routers', [AdminRouterController::class, 'store'])->name('web.admin.routers.store');
        Route::put('/admin/routers/{router}', [AdminRouterController::class, 'update'])->name('web.admin.routers.update');
        Route::delete('/admin/routers/{router}', [AdminRouterController::class, 'destroy'])->name('web.admin.routers.destroy');
        Route::post('/admin/routers/{router}/test', [AdminRouterController::class, 'test'])->name('web.admin.routers.test');
        Route::post('/admin/routers/{router}/sync', [AdminRouterController::class, 'sync'])->name('web.admin.routers.sync');
        Route::post('/admin/routers/{router}/sync-subscriber-plans', [AdminRouterController::class, 'syncSubscriberPlansFromMikrotik'])->name('web.admin.routers.sync-subscriber-plans');
        Route::get('/admin/routers/{router}/ppp-profiles', [AdminRouterController::class, 'pppProfiles'])->name('web.admin.routers.ppp-profiles');
        Route::post('/admin/routers/{router}/plans-from-profiles', [AdminRouterController::class, 'createPlansFromProfiles'])->name('web.admin.routers.plans-from-profiles');
        Route::get('/admin/routers/{router}/ppp', [AdminRouterController::class, 'ppp'])->name('web.admin.routers.ppp');
        Route::post('/admin/routers/{router}/ppp/sync-secrets', [AdminRouterController::class, 'syncSecrets'])->name('web.admin.routers.ppp.sync-secrets');
        Route::post('/admin/routers/{router}/ppp/import-subscribers', [AdminRouterController::class, 'importSubscribersFromPpp'])->name('web.admin.routers.ppp.import-subscribers');
        Route::post('/admin/routers/{router}/ppp/push-comments', [AdminRouterController::class, 'pushSubscriberCommentsToMikrotik'])->name('web.admin.routers.ppp.push-comments');
    });

    Route::middleware('role:agent')->group(function () {
        Route::get('/agent/dashboard', [AgentDashboardController::class, 'dashboard'])->name('web.agent.dashboard');
        Route::get('/agent/search', [AgentDashboardController::class, 'search'])->name('web.agent.search');
        Route::get('/agent/payments', [AgentDashboardController::class, 'payments'])->name('web.agent.payments');
        Route::get('/agent/invoices', [AgentDashboardController::class, 'invoices'])->name('web.agent.invoices');
        Route::get('/agent/subscribers/{subscriber}', [AgentDashboardController::class, 'showSubscriber'])->name('web.agent.subscribers.show');
        Route::post('/agent/subscribers/{subscriber}/renew', [AgentDashboardController::class, 'renew'])->name('web.agent.subscribers.renew');
        Route::get('/agent/collections', function () {
            return redirect()->route('web.agent.payments');
        })->name('web.agent.collections');
    });
});
