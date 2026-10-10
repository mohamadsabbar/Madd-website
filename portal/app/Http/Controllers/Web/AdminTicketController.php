<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\FcmPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = SupportTicket::query()
            ->with(['subscriber', 'user'])
            ->withCount('messages')
            ->orderByDesc('updated_at');

        if (in_array($status, ['open', 'answered', 'closed'], true)) {
            $q->where('status', $status);
        }

        return view('admin.tickets', [
            'tickets' => $q->paginate(30)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['subscriber', 'user', 'messages.user']);

        return view('admin.ticket-show', ['ticket' => $ticket]);
    }

    public function reply(Request $request, SupportTicket $ticket, FcmPushService $fcm): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        if ($ticket->status === 'closed') {
            return back()->with('error', 'التذكرة مغلقة.');
        }

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'sender' => 'staff',
            'body' => $data['body'],
        ]);

        $ticket->status = 'answered';
        $ticket->save();

        if ($ticket->user) {
            $fcm->sendToUser(
                $ticket->user,
                'رد على تذكرة الدعم',
                'يوجد رد جديد على: '.$ticket->subject,
                ['type' => 'ticket', 'ticket_id' => (string) $ticket->id]
            );
        }

        return back()->with('status', 'تم إرسال الرد.');
    }

    public function close(SupportTicket $ticket): RedirectResponse
    {
        $ticket->status = 'closed';
        $ticket->save();

        return back()->with('status', 'تم إغلاق التذكرة.');
    }

    public function reopen(SupportTicket $ticket): RedirectResponse
    {
        $ticket->status = 'open';
        $ticket->save();

        return back()->with('status', 'تم إعادة فتح التذكرة.');
    }
}
