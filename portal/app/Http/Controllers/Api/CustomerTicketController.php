<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\CustomerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerTicketController extends Controller
{
    public function __construct(private CustomerPortalService $portal)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);

        $tickets = SupportTicket::query()
            ->where('subscriber_id', $subscriber->id)
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (SupportTicket $t) => $this->ticketSummary($t));

        return response()->json(['tickets' => $tickets]);
    }

    public function store(Request $request): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportTicket::query()->create([
            'subscriber_id' => $subscriber->id,
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'status' => 'open',
        ]);

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'sender' => 'customer',
            'body' => $data['body'],
        ]);

        $ticket->load('messages');

        return response()->json([
            'message' => 'تم فتح تذكرة الدعم.',
            'ticket' => $this->ticketDetail($ticket),
        ], 201);
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);
        $this->assertOwns($ticket, $subscriber->id);

        $ticket->load('messages');

        return response()->json(['ticket' => $this->ticketDetail($ticket)]);
    }

    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        $subscriber = $this->requireSubscriber($request);
        $this->assertOwns($ticket, $subscriber->id);

        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'التذكرة مغلقة.'], 422);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'sender' => 'customer',
            'body' => $data['body'],
        ]);

        if ($ticket->status === 'answered') {
            $ticket->status = 'open';
            $ticket->save();
        }

        $ticket->touch();
        $ticket->load('messages');

        return response()->json([
            'message' => 'تم إرسال الرد.',
            'ticket' => $this->ticketDetail($ticket),
        ]);
    }

    private function requireSubscriber(Request $request)
    {
        $subscriber = $this->portal->subscriberFor($request->user());
        if (! $subscriber) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(['message' => 'لا يوجد اشتراك مرتبط بهذا الحساب.'], 422)
            );
        }

        return $subscriber;
    }

    private function assertOwns(SupportTicket $ticket, int $subscriberId): void
    {
        if ((int) $ticket->subscriber_id !== $subscriberId) {
            abort(404);
        }
    }

    private function ticketSummary(SupportTicket $t): array
    {
        return [
            'id' => $t->id,
            'subject' => $t->subject,
            'status' => $t->status,
            'messages_count' => $t->messages_count ?? $t->messages()->count(),
            'created_at' => optional($t->created_at)->toIso8601String(),
            'updated_at' => optional($t->updated_at)->toIso8601String(),
        ];
    }

    private function ticketDetail(SupportTicket $t): array
    {
        return [
            'id' => $t->id,
            'subject' => $t->subject,
            'status' => $t->status,
            'created_at' => optional($t->created_at)->toIso8601String(),
            'updated_at' => optional($t->updated_at)->toIso8601String(),
            'messages' => $t->messages->map(fn (SupportTicketMessage $m) => [
                'id' => $m->id,
                'sender' => $m->sender,
                'body' => $m->body,
                'created_at' => optional($m->created_at)->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
