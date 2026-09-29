<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminInvoiceController extends Controller
{
    public function __construct(private readonly MikrotikService $mikrotikService)
    {
    }

    public function index()
    {
        return Invoice::with(['subscriber', 'plan', 'creator', 'payments.collector'])
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subscriber_id' => ['required', 'exists:subscribers,id'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $subscriber = Subscriber::findOrFail($data['subscriber_id']);
        $plan = Plan::findOrFail($data['plan_id'] ?? $subscriber->plan_id);

        return Invoice::create([
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
            'subscriber_id' => $subscriber->id,
            'plan_id' => $plan->id,
            'created_by' => $request->user()->id,
            'amount' => $subscriber->amountForInvoicedPlan($plan),
            'due_date' => $data['due_date'],
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function show(Invoice $admin_invoice)
    {
        return $admin_invoice->load(['subscriber', 'plan', 'creator', 'payments.collector']);
    }

    public function update(Request $request, Invoice $admin_invoice)
    {
        $data = $request->validate([
            'due_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:unpaid,paid,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $admin_invoice->update($data);

        return $admin_invoice->refresh();
    }

    public function destroy(Invoice $admin_invoice)
    {
        $admin_invoice->delete();

        return response()->noContent();
    }

    public function pay(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return response()->json(['message' => 'Invoice already paid.'], 422);
        }

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0'],
            'method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $paidAmount = (float) ($data['amount'] ?? $invoice->amount);
        $method = $data['method'] ?? 'cash';

        Payment::create([
            'invoice_id' => $invoice->id,
            'collected_by' => $request->user()->id,
            'amount' => $paidAmount,
            'method' => $method,
            'paid_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        $invoice->update([
            'status' => 'paid',
            'paid_at' => today(),
        ]);

        $subscriber = $invoice->subscriber;
        $wasSuspended = $subscriber->status === 'suspended';
        $subscriber->update([
            'status' => 'active',
            'suspended_at' => null,
        ]);
        $this->mikrotikService->enablePppoeUser($subscriber);

        ActivityLogger::success('payment.received', "تحصيل دفعة بمبلغ {$paidAmount} شيكل من المشترك {$subscriber->full_name} (الفاتورة {$invoice->invoice_number}).", [
            'subject' => $subscriber,
            'phone' => $subscriber->phone,
            'amount' => $paidAmount,
            'meta' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'method' => $method,
                'reactivated' => $wasSuspended,
                'source' => 'admin_api',
            ],
        ]);

        return $invoice->refresh()->load('payments');
    }
}
