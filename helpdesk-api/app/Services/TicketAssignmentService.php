<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketAssignmentService
{
    public function assign(
        Ticket $ticket,
        User $agent,
        User $assignedBy
    ): TicketAssignment {
        if (!$assignedBy->hasPermission('tickets.assign')) {
            throw new AuthorizationException(
                'You do not have permission to assign tickets.'
            );
        }

        if (!$agent->hasRole('Agent')) {
            throw ValidationException::withMessages([
                'agent_id' => [
                    'The selected user is not an Agent.'
                ],
            ]);
        }

        if (!$agent->is_active) {
            throw ValidationException::withMessages([
                'agent_id' => [
                    'The selected Agent is inactive.'
                ],
            ]);
        }

        return DB::transaction(function () use (
            $ticket,
            $agent,
            $assignedBy
        ) {
            $currentAssignment = $ticket->assignments()
                ->whereNull('unassigned_at')
                ->first();

            if ($currentAssignment) {
                if ($currentAssignment->agent_id === $agent->id) {
                    throw ValidationException::withMessages([
                        'agent_id' => [
                            'This ticket is already assigned to this Agent.'
                        ],
                    ]);
                }

                $currentAssignment->update([
                    'unassigned_at' => now(),
                ]);
            }

            $assignment = $ticket->assignments()->create([
                'agent_id' => $agent->id,
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(),
            ]);

            app(\App\Services\NotificationService::class)->send(
                $agent,
                'ticket_assigned',
                'New ticket assigned',
                "Ticket {$ticket->ticket_number} has been assigned to you.",
                [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                ]
            );

            app(TicketActivityService::class)->log(
                $ticket,
                $assignedBy,
                'agent_assigned',
                $currentAssignment
                    ? ['agent_id' => $currentAssignment->agent_id]
                    : null,
                ['agent_id' => $agent->id]
            );

            return $assignment->load([
                'agent',
                'assignedBy',
            ]);
        });
    }

    public function claim(
        Ticket $ticket,
        User $agent
    ): TicketAssignment {
        if (!$agent->hasRole('Agent') || !$agent->is_active) {
            throw new AuthorizationException(
                'Only active Agents can claim tickets.'
            );
        }

        return DB::transaction(function () use ($ticket, $agent) {
            $ticket = Ticket::whereKey($ticket->id)
                ->lockForUpdate()
                ->with('status')
                ->firstOrFail();

            if ($ticket->status->name !== 'Open') {
                throw ValidationException::withMessages([
                    'ticket' => [
                        'Only open tickets can be claimed.'
                    ],
                ]);
            }

            $currentAssignment = $ticket->assignments()
                ->whereNull('unassigned_at')
                ->first();

            if ($currentAssignment) {
                throw ValidationException::withMessages([
                    'ticket' => [
                        'This ticket is already assigned to an Agent.'
                    ],
                ]);
            }

            $assignment = $ticket->assignments()->create([
                'agent_id' => $agent->id,
                'assigned_by' => $agent->id,
                'assigned_at' => now(),
            ]);

            app(TicketActivityService::class)->log(
                $ticket,
                $agent,
                'agent_claimed',
                null,
                ['agent_id' => $agent->id]
            );

            $inProgress = \App\Models\TicketStatus::where(
                'name',
                'In Progress'
            )->firstOrFail();

            $ticket->update([
                'status_id' => $inProgress->id,
            ]);

            app(TicketActivityService::class)->log(
                $ticket,
                $agent,
                'status_changed',
                ['status' => 'Open'],
                ['status' => 'In Progress']
            );

            return $assignment->load([
                'agent',
                'assignedBy',
            ]);
        });
    }
}