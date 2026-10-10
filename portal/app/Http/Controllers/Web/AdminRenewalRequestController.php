<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CustomerRenewalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRenewalRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        $q = CustomerRenewalRequest::query()
            ->with(['subscriber.plan', 'user'])
            ->orderByDesc('created_at');

        if (in_array($status, ['pending', 'done', 'rejected', 'all'], true) && $status !== 'all') {
            $q->where('status', $status);
        }

        return view('admin.renewal-requests', [
            'requests' => $q->paginate(40)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function update(Request $request, CustomerRenewalRequest $renewalRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,done,rejected'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $renewalRequest->status = $data['status'];
        if (array_key_exists('admin_note', $data) && $data['admin_note'] !== null) {
            $existing = trim((string) $renewalRequest->note);
            $admin = trim((string) $data['admin_note']);
            if ($admin !== '') {
                $renewalRequest->note = $existing !== ''
                    ? $existing."\n---\nملاحظة الإدارة: ".$admin
                    : 'ملاحظة الإدارة: '.$admin;
            }
        }
        $renewalRequest->save();

        return back()->with('status', 'تم تحديث حالة الطلب.');
    }
}
