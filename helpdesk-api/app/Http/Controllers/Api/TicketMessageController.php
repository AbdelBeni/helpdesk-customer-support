<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketMessageRequest;
use App\Http\Resources\TicketMessageResource;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $messages = $ticket->messages()
            ->with('user')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return TicketMessageResource::collection($messages);
    }

    public function store(
        StoreTicketMessageRequest $request,
        Ticket $ticket
    ): TicketMessageResource {
        $this->authorize('view', $ticket);

        $user = $request->user();

        $message = $ticket->messages()->create([
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        if (
            $ticket->first_response_at === null
            && $user->hasRole('Agent')
        ) {
            $ticket->update([
                'first_response_at' => now(),
            ]);
        }

        $notificationService = app(
            \App\Services\NotificationService::class
        );

        if ($user->hasRole('Agent')) {
            $notificationService->send(
                $ticket->customer,
                'ticket_message',
                'New reply on your ticket',
                "Agent replied to ticket {$ticket->ticket_number}.",
                [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'message_id' => $message->id,
                ]
            );
        } else {
            $agents = $ticket->assignments()
                ->whereNull('unassigned_at')
                ->with('agent')
                ->get()
                ->pluck('agent')
                ->unique('id');

            foreach ($agents as $agent) {
                if ($agent->id === $user->id) {
                    continue;
                }

                $notificationService->send(
                    $agent,
                    'ticket_message',
                    'New customer reply',
                    "Customer replied to ticket {$ticket->ticket_number}.",
                    [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'message_id' => $message->id,
                    ]
                );
            }
        }

        $message->load('user');

        return new TicketMessageResource($message);
    }
}