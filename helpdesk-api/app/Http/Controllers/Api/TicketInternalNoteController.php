<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInternalNoteRequest;
use App\Http\Resources\TicketInternalNoteResource;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TicketActivityService;
use Illuminate\Http\Request;

class TicketInternalNoteController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorizeStaffAccess($request->user(), $ticket);

        $notes = $ticket->internalNotes()
            ->with('user')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return TicketInternalNoteResource::collection($notes);
    }

    public function store(
        StoreInternalNoteRequest $request,
        Ticket $ticket,
        TicketActivityService $activityService
    ) {
        $this->authorizeStaffAccess($request->user(), $ticket);

        $note = $ticket->internalNotes()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
        ]);

        $staffUsers = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['Admin', 'Agent']);
        })
            ->where('id', '!=', $request->user()->id)
            ->where('is_active', true)
            ->get();

        $notificationService = app(NotificationService::class);

        foreach ($staffUsers as $staffUser) {
            $notificationService->send(
                $staffUser,
                'internal_note_added',
                'New internal note',
                "A new internal note was added to ticket {$ticket->ticket_number}.",
                [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'note_id' => $note->id,
                ]
            );
        }

        $activityService->log(
            $ticket,
            $request->user(),
            'internal_note_added',
            null,
            null,
            [
                'internal_note_id' => $note->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Internal note added successfully.',
            'data' => new TicketInternalNoteResource(
                $note->load('user')
            ),
        ], 201);
    }

    private function authorizeStaffAccess(
        User $user,
        Ticket $ticket
    ): void {
        abort_unless(
            $user->hasPermission('tickets.internal_notes'),
            403
        );

        if ($user->hasRole('Admin')) {
            return;
        }

        $assigned = $ticket->assignments()
            ->where('agent_id', $user->id)
            ->whereNull('unassigned_at')
            ->exists();

        abort_unless(
            $user->hasRole('Agent') && $assigned,
            403
        );
    }
}