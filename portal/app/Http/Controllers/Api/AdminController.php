<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscriber;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();

        return response()->json([
            'subscribers_count' => Subscriber::count(),
            'online_subscribers' => Subscriber::where('is_online', true)->count(),
            'expired_subscribers' => Subscriber::whereDate('end_date', '<', $today)->count(),
            'today_income' => Invoice::whereDate('paid_at', $today)->sum('amount'),
            'month_income' => Invoice::whereYear('paid_at', $today->year)
                ->whereMonth('paid_at', $today->month)
                ->sum('amount'),
        ]);
    }
}
