<?php

use App\Http\Controllers\Api\AdminAgentController;
use App\Http\Controllers\Api\AdminRouterController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminInvoiceController;
use App\Http\Controllers\Api\AdminPlanController;
use App\Http\Controllers\Api\AdminSubscriberController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SiteCmsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/** للتحقق من المتصفح: `GET /api` يعيد JSON وليس 404 */
Route::get('/', function () {
    return response()->json([
        'app' => 'MADD API',
        'ok' => true,
    ]);
});

Route::post('/login', [AuthController::class, 'login']);

/** محتوى الموقع التسويقي + الطلبات */
Route::prefix('site')->group(function () {
    Route::get('/config', [SiteCmsController::class, 'showConfig']);
    Route::post('/leads', [SiteCmsController::class, 'storeLead']);

    Route::middleware('site.cms')->group(function () {
        Route::put('/config', [SiteCmsController::class, 'updateConfig']);
        Route::get('/leads', [SiteCmsController::class, 'listLeads']);
        Route::patch('/leads/{lead}', [SiteCmsController::class, 'updateLeadStatus']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        Route::apiResource('subscribers', AdminSubscriberController::class)->parameters([
            'subscribers' => 'admin_subscriber',
        ]);
        Route::post('/subscribers/{subscriber}/suspend', [AdminSubscriberController::class, 'suspend']);
        Route::post('/subscribers/{subscriber}/renew', [AdminSubscriberController::class, 'renew']);
        Route::post('/subscribers/{subscriber}/reconnect', [AdminSubscriberController::class, 'reconnect']);

        Route::apiResource('plans', AdminPlanController::class)->parameters([
            'plans' => 'admin_plan',
        ]);
        Route::apiResource('invoices', AdminInvoiceController::class)->parameters([
            'invoices' => 'admin_invoice',
        ]);
        Route::post('/invoices/{invoice}/pay', [AdminInvoiceController::class, 'pay']);
        Route::apiResource('agents', AdminAgentController::class)->parameters([
            'agents' => 'agent',
        ]);

        Route::post('/routers/{router}/test', [AdminRouterController::class, 'test']);
        Route::post('/routers/{router}/sync', [AdminRouterController::class, 'sync']);
        Route::post('/routers/{router}/sync-subscriber-plans', [AdminRouterController::class, 'syncSubscriberPlansFromMikrotik']);
        Route::post('/routers/{router}/sync-secrets', [AdminRouterController::class, 'syncSecrets']);
        Route::get('/routers/{router}/ppp-profiles', [AdminRouterController::class, 'pppProfiles']);
        Route::post('/routers/{router}/plans-from-profiles', [AdminRouterController::class, 'createPlansFromProfiles']);
        Route::post('/routers/{router}/import-subscribers', [AdminRouterController::class, 'importSubscribersFromPpp']);
        Route::post('/routers/{router}/push-comments', [AdminRouterController::class, 'pushSubscriberCommentsToMikrotik']);
        Route::apiResource('routers', AdminRouterController::class)->parameters([
            'routers' => 'router',
        ]);
    });

    Route::prefix('agent')->middleware('role:agent')->group(function () {
        Route::get('/subscribers/search', [AgentController::class, 'searchSubscribers']);
        Route::post('/subscribers/{subscriber}/renew', [AgentController::class, 'renew']);
        Route::get('/collections', [AgentController::class, 'collections']);
    });

    Route::prefix('customer')->middleware('role:subscriber')->group(function () {
        Route::get('/dashboard', [CustomerController::class, 'dashboard']);
        Route::get('/subscription', [CustomerController::class, 'subscription']);
        Route::get('/invoices', [CustomerController::class, 'invoices']);
        Route::get('/usage', [CustomerController::class, 'usage']);
        Route::get('/plans', [CustomerController::class, 'plans']);
        Route::post('/renewal-request', [CustomerController::class, 'requestRenewal']);
        Route::post('/self-extend', [CustomerController::class, 'selfExtend']);
    });
});
